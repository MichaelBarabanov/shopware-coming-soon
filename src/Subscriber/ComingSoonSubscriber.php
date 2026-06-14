<?php declare(strict_types=1);

namespace Micha\ComingSoon\Subscriber;

use Micha\ComingSoon\Service\BypassChecker;
use Micha\ComingSoon\Service\ComingSoonConfig;
use Micha\ComingSoon\Service\ComingSoonService;
use Micha\ComingSoon\Service\PathMatcher;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\PlatformRequest;
use Shopware\Core\SalesChannelRequest;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

class ComingSoonSubscriber implements EventSubscriberInterface
{
    private const ATTRIBUTE_SET_PREVIEW_COOKIE = 'micha_coming_soon_set_preview_cookie';
    private const PREVIEW_COOKIE_LIFETIME = 60 * 60 * 24 * 7; // 7 days
    private const TEMPLATE = '@MichaComingSoon/storefront/page/micha-coming-soon/index.html.twig';

    // Request attributes set by Shopware's RequestTransformer. We rely on the
    // string keys instead of the class constants so the plugin does not couple
    // to a specific RequestTransformer implementation.
    private const ATTRIBUTE_ORIGINAL_REQUEST_URI = 'sw-original-request-uri';
    private const ATTRIBUTE_SALES_CHANNEL_BASE_URL = 'sw-sales-channel-base-url';

    public function __construct(
        private readonly ComingSoonService $comingSoonService,
        private readonly BypassChecker $bypassChecker,
        private readonly PathMatcher $pathMatcher,
        private readonly Environment $twig,
        private readonly LoggerInterface $logger
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Priority 35: after Shopware's RequestTransformer (runs before the
            // kernel) but before Symfony routing - catches every storefront
            // request including unknown URLs, without touching /api or /admin.
            KernelEvents::REQUEST => ['onKernelRequest', 35],
            KernelEvents::RESPONSE => ['onKernelResponse', -10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if ($request->attributes->get(SalesChannelRequest::ATTRIBUTE_IS_SALES_CHANNEL_REQUEST) !== true) {
            return;
        }

        $salesChannelId = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_ID);

        if (!\is_string($salesChannelId)) {
            return;
        }

        $config = $this->comingSoonService->getConfig($salesChannelId);

        if (!$this->protectionApplies($request, $config)) {
            return;
        }

        if ($this->bypassChecker->hasValidPreviewQueryToken($request, $config)) {
            // Let the visitor through and remember to set the preview cookie.
            $request->attributes->set(self::ATTRIBUTE_SET_PREVIEW_COOKIE, true);

            return;
        }

        if ($this->bypassChecker->isBypassed($request, $config)) {
            return;
        }

        $response = $this->createComingSoonResponse($request->attributes->get(
            SalesChannelRequest::ATTRIBUTE_DOMAIN_LOCALE,
            'en-GB'
        ), $config);

        if ($response !== null) {
            $event->setResponse($response);
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if ($request->attributes->get(self::ATTRIBUTE_SET_PREVIEW_COOKIE) !== true) {
            return;
        }

        $salesChannelId = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_ID);
        $config = $this->comingSoonService->getConfig(\is_string($salesChannelId) ? $salesChannelId : null);

        if ($config->previewToken === null) {
            return;
        }

        $event->getResponse()->headers->setCookie(Cookie::create(
            BypassChecker::PREVIEW_COOKIE_NAME,
            $this->bypassChecker->getPreviewCookieValue($config),
            time() + self::PREVIEW_COOKIE_LIFETIME,
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            Cookie::SAMESITE_LAX
        ));
    }

    /**
     * Decides whether the current request must be intercepted:
     *  - never, once auto-end has opened the shop;
     *  - always, when the whole sales channel is switched on;
     *  - otherwise only for the explicitly configured paths.
     */
    private function protectionApplies(Request $request, ComingSoonConfig $config): bool
    {
        if ($config->isOpenedByCountdown()) {
            return false;
        }

        if ($config->active) {
            return true;
        }

        return $this->pathMatcher->matchesAny($this->resolveCandidatePaths($request), $config->comingSoonPaths);
    }

    /**
     * Shopware's RequestTransformer rewrites SEO URLs to their technical route
     * (e.g. "/summer-sale" -> "/navigation/<id>") before this subscriber runs,
     * so getPathInfo() alone would never match what the merchant typed. We
     * therefore test against both the technical path AND the original SEO URI
     * that Shopware preserved in the request attributes.
     *
     * @return string[]
     */
    private function resolveCandidatePaths(Request $request): array
    {
        $paths = [$request->getPathInfo()];

        $seoPath = $this->resolveSeoPath($request);

        if ($seoPath !== null) {
            $paths[] = $seoPath;
        }

        return $paths;
    }

    private function resolveSeoPath(Request $request): ?string
    {
        $original = $request->attributes->get(self::ATTRIBUTE_ORIGINAL_REQUEST_URI);

        if (!\is_string($original) || $original === '') {
            return null;
        }

        // Drop the query string and decode the slug (umlauts etc.).
        $path = rawurldecode(explode('?', $original, 2)[0]);

        // Strip the sales channel base url (language/domain path prefix), so the
        // result is relative to the domain - just like the merchant enters it.
        $baseUrl = $request->attributes->get(self::ATTRIBUTE_SALES_CHANNEL_BASE_URL);

        if (\is_string($baseUrl) && $baseUrl !== '' && str_starts_with($path, $baseUrl)) {
            $path = substr($path, \strlen($baseUrl));
        }

        return $path !== '' ? $path : '/';
    }

    private function createComingSoonResponse(string $locale, ComingSoonConfig $config): ?Response
    {
        $context = Context::createDefaultContext();

        try {
            $html = $this->twig->render(self::TEMPLATE, [
                'comingSoonConfig' => $config,
                'backgroundUrl' => $this->comingSoonService->getMediaUrl($config->backgroundMediaId, $context),
                'logoUrl' => $this->comingSoonService->getMediaUrl($config->logoMediaId, $context),
                'locale' => $locale,
                'showCountdown' => $config->countdownActive
                    && $config->countdownDate !== null
                    && !$config->isCountdownExpired(),
                'autoReloadOnEnd' => $config->autoEndOnCountdown,
            ]);
        } catch (\Throwable $e) {
            // Fail open: a broken maintenance page must never take the shop down.
            $this->logger->error('MichaComingSoon: could not render coming soon page.', [
                'exception' => $e,
            ]);

            return null;
        }

        $response = new Response($html, Response::HTTP_SERVICE_UNAVAILABLE);
        $response->headers->set('Retry-After', (string) $this->getRetryAfterSeconds($config));
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');

        return $response;
    }

    private function getRetryAfterSeconds(ComingSoonConfig $config): int
    {
        if ($config->countdownDate !== null && !$config->isCountdownExpired()) {
            return max(60, $config->countdownDate->getTimestamp() - time());
        }

        return 3600;
    }
}

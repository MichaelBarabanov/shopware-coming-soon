<?php declare(strict_types=1);

namespace Micha\ComingSoon\Subscriber;

use Micha\ComingSoon\Service\ComingSoonService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Adapter\Cache\CacheClearer;
use Shopware\Core\System\SystemConfig\Event\SystemConfigChangedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Clears the (HTTP) cache when the coming soon page is toggled. Without this,
 * visitors could still get cached storefront pages while maintenance is on -
 * or the cached coming soon state after switching it off.
 */
class CacheInvalidationSubscriber implements EventSubscriberInterface
{
    /**
     * Config keys that change which visitors get the coming soon page and
     * therefore require a cache flush. Other keys (text, design, ...) only
     * change the page content, which is never cached (no-store response).
     */
    private const INVALIDATING_KEYS = [
        ComingSoonService::CONFIG_DOMAIN . 'active',
        ComingSoonService::CONFIG_DOMAIN . 'comingSoonPaths',
    ];

    private bool $cleared = false;

    public function __construct(
        private readonly CacheClearer $cacheClearer,
        private readonly LoggerInterface $logger
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SystemConfigChangedEvent::class => 'onSystemConfigChanged',
        ];
    }

    public function onSystemConfigChanged(SystemConfigChangedEvent $event): void
    {
        if ($this->cleared || !\in_array($event->getKey(), self::INVALIDATING_KEYS, true)) {
            return;
        }

        // Saving the config fires one event per key - only clear once per request.
        $this->cleared = true;

        try {
            $this->cacheClearer->clear();
        } catch (\Throwable $e) {
            $this->logger->warning('MichaComingSoon: could not clear cache after config change.', [
                'exception' => $e,
            ]);
        }
    }
}

<?php declare(strict_types=1);

namespace Micha\ComingSoon\Service;

use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Request;

class BypassChecker
{
    public const PREVIEW_QUERY_PARAM = 'michaPreview';
    public const PREVIEW_COOKIE_NAME = 'micha-coming-soon-preview';

    public function isBypassed(Request $request, ComingSoonConfig $config): bool
    {
        return $this->isWhitelistedIp($request, $config)
            || $this->hasValidPreviewQueryToken($request, $config)
            || $this->hasValidPreviewCookie($request, $config);
    }

    public function hasValidPreviewQueryToken(Request $request, ComingSoonConfig $config): bool
    {
        if ($config->previewToken === null) {
            return false;
        }

        $token = (string) $request->query->get(self::PREVIEW_QUERY_PARAM, '');

        return $token !== '' && hash_equals($config->previewToken, $token);
    }

    public function getPreviewCookieValue(ComingSoonConfig $config): string
    {
        return hash('sha256', (string) $config->previewToken);
    }

    private function hasValidPreviewCookie(Request $request, ComingSoonConfig $config): bool
    {
        if ($config->previewToken === null) {
            return false;
        }

        $cookie = (string) $request->cookies->get(self::PREVIEW_COOKIE_NAME, '');

        return $cookie !== '' && hash_equals($this->getPreviewCookieValue($config), $cookie);
    }

    private function isWhitelistedIp(Request $request, ComingSoonConfig $config): bool
    {
        if ($config->ipWhitelist === []) {
            return false;
        }

        $clientIp = $request->getClientIp();

        if ($clientIp === null) {
            return false;
        }

        foreach ($config->ipWhitelist as $entry) {
            try {
                if (IpUtils::checkIp($clientIp, $entry)) {
                    return true;
                }
            } catch (\Throwable) {
                // Invalid whitelist entry - ignore it instead of breaking the check.
                continue;
            }
        }

        return false;
    }
}

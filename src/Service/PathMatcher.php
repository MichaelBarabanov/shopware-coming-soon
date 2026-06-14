<?php declare(strict_types=1);

namespace Micha\ComingSoon\Service;

/**
 * Matches a request path against a list of merchant-configured patterns.
 *
 * Matching is case-insensitive and segment-aware:
 *  - "/sale" matches "/sale" and "/sale/shoes" (prefix on path boundary), but
 *    NOT "/salermo".
 *  - "*" acts as a wildcard, e.g. "/sale/*" or "/*-sale".
 *  - A full URL ("https://shop.tld/sale") is reduced to its path.
 *  - "/" only matches the home page, never everything.
 */
final class PathMatcher
{
    /**
     * Returns true if any of the candidate paths matches any of the patterns.
     * Used to test both the visible SEO path and the technical (resolved) path
     * of a request against the merchant's configured patterns.
     *
     * @param string[] $paths
     * @param string[] $patterns
     */
    public function matchesAny(array $paths, array $patterns): bool
    {
        foreach ($paths as $path) {
            if ($this->matches($path, $patterns)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string[] $patterns
     */
    public function matches(string $path, array $patterns): bool
    {
        if ($patterns === []) {
            return false;
        }

        $path = $this->normalize($path);

        if ($path === '') {
            return false;
        }

        foreach ($patterns as $pattern) {
            if ($this->matchesPattern($path, $this->normalize($pattern))) {
                return true;
            }
        }

        return false;
    }

    private function matchesPattern(string $path, string $pattern): bool
    {
        if ($pattern === '') {
            return false;
        }

        // The root must never act as a catch-all prefix.
        if ($pattern === '/') {
            return $path === '/';
        }

        if (str_contains($pattern, '*')) {
            return $this->matchesWildcard($path, $pattern);
        }

        return $path === $pattern || str_starts_with($path, $pattern . '/');
    }

    private function matchesWildcard(string $path, string $pattern): bool
    {
        $regex = '#^' . str_replace('\*', '.*', preg_quote($pattern, '#')) . '$#';

        return preg_match($regex, $path) === 1;
    }

    /**
     * Reduces any input to a comparable, lower-cased path without trailing slash.
     */
    private function normalize(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        // Allow merchants to paste a full URL - keep only the path component.
        if (preg_match('#^https?://#i', $value) === 1) {
            $value = (string) parse_url($value, \PHP_URL_PATH);

            if ($value === '') {
                return '';
            }
        }

        if ($value[0] !== '/') {
            $value = '/' . $value;
        }

        if (\strlen($value) > 1) {
            $value = rtrim($value, '/');

            if ($value === '') {
                $value = '/';
            }
        }

        return mb_strtolower($value);
    }
}

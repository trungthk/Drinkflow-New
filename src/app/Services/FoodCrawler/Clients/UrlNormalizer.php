<?php

declare(strict_types=1);

namespace App\Services\FoodCrawler\Clients;

/**
 * URL helpers shared by HTML crawlers: resolving relative links and building dedupe keys.
 */
final class UrlNormalizer
{
    /**
     * Resolve a (possibly relative) link against a base URL.
     *
     * @param string $href Raw href/src value.
     * @param string $base Absolute URL of the page the link was found on.
     * @return string|null Absolute HTTP(S) URL without fragment, or null for javascript:, mailto:, tel:, etc.
     */
    public static function absolute(string $href, string $base): ?string
    {
        $href = trim(html_entity_decode($href, ENT_QUOTES | ENT_HTML5));
        if ($href === '' || str_starts_with($href, '#') || preg_match('#^(javascript|mailto|tel|data):#i', $href) === 1) {
            return null;
        }

        $baseParts = parse_url($base);
        if (! is_array($baseParts) || ! isset($baseParts['scheme'], $baseParts['host'])) {
            return null;
        }
        $origin = strtolower($baseParts['scheme']).'://'.$baseParts['host'].(isset($baseParts['port']) ? ':'.$baseParts['port'] : '');

        if (str_starts_with($href, '//')) {
            $url = strtolower($baseParts['scheme']).':'.$href;
        } elseif (preg_match('#^https?://#i', $href) === 1) {
            $url = $href;
        } elseif (str_starts_with($href, '/')) {
            $url = $origin.$href;
        } else {
            $path = (string) ($baseParts['path'] ?? '/');
            $dir = substr($path, 0, (int) strrpos($path, '/') + 1);
            $url = $origin.($dir !== '' ? $dir : '/').$href;
        }

        $url = (string) preg_replace('/#.*$/', '', $url);
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true) ? $url : null;
    }

    /**
     * Build the dedupe key of a product URL: lower-case scheme/host, no query, fragment or trailing slash.
     *
     * @param string $url Absolute URL.
     * @return string Normalised key.
     */
    public static function key(string $url): string
    {
        $parts = parse_url(trim($url));
        if (! is_array($parts) || ! isset($parts['host'])) {
            return strtolower(trim($url));
        }
        $path = rtrim((string) ($parts['path'] ?? ''), '/');

        return 'https://'.strtolower($parts['host']).($path === '' ? '/' : $path);
    }
}

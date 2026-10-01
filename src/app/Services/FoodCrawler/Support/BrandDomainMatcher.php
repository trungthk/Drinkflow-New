<?php

declare(strict_types=1);

namespace App\Services\FoodCrawler\Support;

/**
 * Maps a URL to a configured crawlable brand by its host name.
 *
 * Brands and their domains live in config('food-crawler.brands'). A host matches a domain only when it
 * is that exact domain or a real subdomain of it, so look-alike hosts that merely contain the domain
 * (e.g. "highlandscoffee.com.vn.fake-site.test") are rejected.
 */
final class BrandDomainMatcher
{
    /**
     * Resolve the brand key for a URL.
     *
     * @param string $url Absolute URL to inspect.
     * @return string|null Brand key from config, or null when the URL is not HTTP(S) or not allowlisted.
     */
    public function brandFor(string $url): ?string
    {
        $host = $this->host($url);
        if ($host === null) {
            return null;
        }

        foreach ((array) config('food-crawler.brands', []) as $key => $brand) {
            if ($this->hostMatches($host, (array) ($brand['domains'] ?? []))) {
                return (string) $key;
            }
        }

        return null;
    }

    /**
     * Check whether a URL is an HTTP(S) URL on one of the given domains.
     *
     * @param string $url Absolute URL to inspect.
     * @param array<int, string> $domains Allowed registrable domains.
     * @return bool True when the URL host equals a domain or is a subdomain of it.
     */
    public function urlAllowed(string $url, array $domains): bool
    {
        $host = $this->host($url);

        return $host !== null && $this->hostMatches($host, $domains);
    }

    /**
     * Compare a host against allowed domains using exact or dot-suffix matching.
     *
     * @param string $host Lower-case host name.
     * @param array<int, string> $domains Allowed domains.
     * @return bool True on an exact or subdomain match.
     */
    public function hostMatches(string $host, array $domains): bool
    {
        $host = rtrim(strtolower($host), '.');
        foreach ($domains as $domain) {
            $domain = rtrim(strtolower(trim((string) $domain)), '.');
            if ($domain !== '' && ($host === $domain || str_ends_with($host, '.'.$domain))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract the lower-case host of an HTTP(S) URL.
     *
     * @param string $url Absolute URL.
     * @return string|null Host, or null for other schemes, credentials or unparsable input.
     */
    private function host(string $url): ?string
    {
        $parts = parse_url(trim($url));
        if (! is_array($parts) || ! in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true)
            || ! isset($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        return strtolower((string) $parts['host']);
    }
}

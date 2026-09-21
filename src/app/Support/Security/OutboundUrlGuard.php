<?php

declare(strict_types=1);

namespace App\Support\Security;

use InvalidArgumentException;

/**
 * Guards server-side HTTP requests to user-supplied URLs against SSRF.
 *
 * A URL is accepted only when it uses HTTPS, carries no credentials and every address its host
 * resolves to is publicly routable. The validated addresses can be pinned onto the actual request
 * (see {@see self::httpOptions()}) so a DNS rebinding answer cannot swap the target afterwards.
 */
class OutboundUrlGuard
{
    /**
     * Validate a URL and return its parsed target.
     *
     * @param string $url Untrusted URL.
     * @param array<int, string> $allowedHosts Exact host names allowed; empty means any public host.
     * @return array{host: string, port: int, ips: array<int, string>} Validated host, port and resolved addresses.
     * @throws InvalidArgumentException When the URL is not a safe public HTTPS target.
     */
    public function assertSafe(string $url, array $allowedHosts = []): array
    {
        $parts = parse_url(trim($url));
        if (! is_array($parts) || strtolower((string) ($parts['scheme'] ?? '')) !== 'https' || ! isset($parts['host'])) {
            throw new InvalidArgumentException('The URL must be an absolute HTTPS URL.');
        }
        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new InvalidArgumentException('The URL must not contain credentials.');
        }

        $host = strtolower(trim((string) $parts['host'], '[]'));
        if ($allowedHosts !== [] && ! in_array($host, array_map('strtolower', $allowedHosts), true)) {
            throw new InvalidArgumentException('The URL host is not allowed.');
        }
        if ($host === 'localhost' || str_ends_with($host, '.localhost') || str_ends_with($host, '.internal') || str_ends_with($host, '.local')) {
            throw new InvalidArgumentException('The URL must point to a public host.');
        }

        $ips = filter_var($host, FILTER_VALIDATE_IP) !== false ? [$host] : $this->resolveHost($host);
        if ($ips === []) {
            throw new InvalidArgumentException('The URL host could not be resolved.');
        }
        foreach ($ips as $ip) {
            if (! $this->isPublicIp($ip)) {
                throw new InvalidArgumentException('The URL must point to a public host.');
            }
        }

        return ['host' => $host, 'port' => (int) ($parts['port'] ?? 443), 'ips' => $ips];
    }

    /**
     * Check whether a URL passes {@see self::assertSafe()} without throwing.
     *
     * @param string $url Untrusted URL.
     * @param array<int, string> $allowedHosts Exact host names allowed; empty means any public host.
     * @return bool True when the URL is a safe public HTTPS target.
     */
    public function isSafe(string $url, array $allowedHosts = []): bool
    {
        try {
            $this->assertSafe($url, $allowedHosts);
        } catch (InvalidArgumentException) {
            return false;
        }

        return true;
    }

    /**
     * Build HTTP client options that pin the validated addresses and refuse redirects.
     *
     * @param string $url Untrusted URL.
     * @param array<int, string> $allowedHosts Exact host names allowed; empty means any public host.
     * @return array<string, mixed> Guzzle request options.
     * @throws InvalidArgumentException When the URL is not a safe public HTTPS target.
     */
    public function httpOptions(string $url, array $allowedHosts = []): array
    {
        $target = $this->assertSafe($url, $allowedHosts);
        $options = ['allow_redirects' => false];

        if (filter_var($target['host'], FILTER_VALIDATE_IP) === false && defined('CURLOPT_RESOLVE')) {
            $options['curl'] = [CURLOPT_RESOLVE => [$target['host'].':'.$target['port'].':'.implode(',', $target['ips'])]];
        }

        return $options;
    }

    /**
     * Resolve a host name to its IPv4 and IPv6 addresses.
     *
     * @param string $host Host name.
     * @return array<int, string> Resolved addresses, empty when the host does not resolve.
     */
    protected function resolveHost(string $host): array
    {
        $ips = gethostbynamel($host) ?: [];
        foreach (@dns_get_record($host, DNS_AAAA) ?: [] as $record) {
            if (isset($record['ipv6'])) {
                $ips[] = (string) $record['ipv6'];
            }
        }

        return array_values(array_unique($ips));
    }

    /**
     * Determine whether an address is publicly routable.
     *
     * @param string $ip IPv4 or IPv6 address.
     * @return bool False for private, loopback, link-local, reserved and carrier-grade NAT ranges.
     */
    protected function isPublicIp(string $ip): bool
    {
        $packed = @inet_pton($ip);
        if ($packed === false) {
            return false;
        }
        // IPv4-mapped IPv6 (::ffff:a.b.c.d) must be judged by its embedded IPv4 address.
        if (strlen($packed) === 16 && str_starts_with($packed, str_repeat("\0", 10)."\xff\xff")) {
            $ip = (string) inet_ntop(substr($packed, 12));
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            $long = ip2long($ip);

            // 100.64.0.0/10 carrier-grade NAT is not covered by the PHP filter flags.
            return $long === false || ($long & 0xFFC00000) !== 0x64400000;
        }

        return true;
    }
}

<?php

declare(strict_types=1);

namespace App\Services\FoodCrawler\Clients;

use App\Services\FoodCrawler\Exceptions\FoodCrawlerException;
use App\Services\FoodCrawler\Support\BrandDomainMatcher;
use App\Support\Security\OutboundUrlGuard;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

/**
 * Sequential, rate-limited HTML fetcher for brand websites.
 *
 * Every request (including each redirect hop) is checked against the brand domain allowlist and the
 * SSRF guard before it is sent. Responses are memoised for the lifetime of the instance, so one crawl
 * run never downloads the same URL twice. Create a fresh instance per crawl run.
 */
final class BrandHtmlClient
{
    /** @var array<string, string> URL => HTML fetched during this run. */
    private array $cache = [];

    private bool $hasRequested = false;

    public function __construct(
        private readonly OutboundUrlGuard $guard,
        private readonly BrandDomainMatcher $matcher,
    ) {
    }

    /**
     * Fetch a page's HTML.
     *
     * @param string $url Absolute HTTPS URL.
     * @param array<int, string> $domains Domains the URL (and any redirect target) must belong to.
     * @return string Non-empty HTML body.
     * @throws FoodCrawlerException When the URL is not allowed, the request fails or the body is empty.
     */
    public function get(string $url, array $domains): string
    {
        $config = (array) config('food-crawler.http', []);
        $maxRedirects = (int) ($config['max_redirects'] ?? 3);
        $current = $url;

        for ($hop = 0; $hop <= $maxRedirects; $hop++) {
            if (isset($this->cache[$current])) {
                return $this->cache[$current];
            }
            if (! $this->matcher->urlAllowed($current, $domains)) {
                throw new FoodCrawlerException(__('admin.crawler_url_not_allowed'));
            }

            $response = $this->send($current, $config);
            if ($response->redirect()) {
                $location = (string) $response->header('Location');
                if ($location === '') {
                    throw new FoodCrawlerException(__('admin.crawler_http_status', ['status' => $response->status()]));
                }
                $current = UrlNormalizer::absolute($location, $current) ?? '';

                continue;
            }
            if (in_array($response->status(), [401, 403], true)) {
                // The source refuses automated access (e.g. a bot-protection page). This is respected, never bypassed.
                throw new FoodCrawlerException(__('admin.crawler_access_blocked', ['status' => $response->status()]));
            }
            if (! $response->successful()) {
                throw new FoodCrawlerException(__('admin.crawler_http_status', ['status' => $response->status()]));
            }

            $html = (string) $response->body();
            if (trim($html) === '') {
                throw new FoodCrawlerException(__('admin.crawler_empty_page'));
            }

            return $this->cache[$url] = $this->cache[$current] = $html;
        }

        throw new FoodCrawlerException(__('admin.crawler_too_many_redirects'));
    }

    /**
     * Send one GET request with pacing, timeout and bounded retries on connection errors / 5xx / 429.
     *
     * @param string $url Allowlisted URL.
     * @param array<string, mixed> $config HTTP crawler config.
     * @return \Illuminate\Http\Client\Response Final response (redirects are not followed).
     * @throws FoodCrawlerException When the URL fails the SSRF guard or every attempt fails to connect.
     */
    private function send(string $url, array $config): \Illuminate\Http\Client\Response
    {
        try {
            $options = $this->guard->httpOptions($url);
        } catch (InvalidArgumentException) {
            throw new FoodCrawlerException(__('admin.crawler_url_not_allowed'));
        }

        $retries = max(0, (int) ($config['retries'] ?? 2));
        $lastError = null;
        for ($attempt = 0; $attempt <= $retries; $attempt++) {
            $this->pace($attempt === 0 ? (int) ($config['request_delay_ms'] ?? 250) : (int) ($config['retry_delay_ms'] ?? 500) * $attempt);
            try {
                $response = Http::timeout(max(1, (int) ($config['timeout'] ?? 10)))
                    ->withOptions($options)
                    ->withHeaders([
                        'User-Agent' => (string) ($config['user_agent'] ?? 'DrinkFlowMenuBot/1.0'),
                        'Accept' => 'text/html,application/xhtml+xml',
                    ])
                    ->get($url);
            } catch (ConnectionException $exception) {
                $lastError = $exception->getMessage();

                continue;
            }
            if ($response->serverError() || $response->status() === 429) {
                $lastError = 'HTTP '.$response->status();
                if ($attempt < $retries) {
                    continue;
                }
            }

            return $response;
        }

        throw new FoodCrawlerException(__('admin.crawler_connection_failed', ['reason' => (string) $lastError]));
    }

    /**
     * Sleep between requests of this run (never before the very first request).
     *
     * @param int $milliseconds Delay to apply.
     * @return void
     */
    private function pace(int $milliseconds): void
    {
        if ($this->hasRequested && $milliseconds > 0) {
            usleep($milliseconds * 1000);
        }
        $this->hasRequested = true;
    }
}

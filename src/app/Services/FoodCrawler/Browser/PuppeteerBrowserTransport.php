<?php

declare(strict_types=1);

namespace App\Services\FoodCrawler\Browser;

use App\Services\FoodCrawler\Contracts\BrowserTransportInterface;
use App\Services\FoodCrawler\Exceptions\FoodCrawlerException;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;
use Throwable;

final class PuppeteerBrowserTransport implements BrowserTransportInterface
{
    private function browser(string $url): Browsershot
    {
        $chromePath = (string) config('food-crawler.browser.chrome_path');
        $nodeBinary = (string) config('food-crawler.browser.node_binary');
        $nodeModulePath = (string) config('food-crawler.browser.node_module_path');
        if (! is_file($chromePath)) {
            throw new FoodCrawlerException('Chrome executable was not found at the configured path.');
        }
        if (! is_file($nodeBinary)) {
            throw new FoodCrawlerException('Node.js executable was not found at the configured path.');
        }
        if (! is_dir($nodeModulePath) || ! is_dir($nodeModulePath.'/puppeteer')) {
            throw new FoodCrawlerException('Puppeteer was not found in the configured Node module path.');
        }
        putenv('NODE_PATH='.$nodeModulePath);

        return Browsershot::url($url)
            ->setChromePath($chromePath)
            ->setNodeBinary($nodeBinary)
            ->setNpmBinary((string) config('food-crawler.browser.npm_binary'))
            ->setNodeModulePath($nodeModulePath)
            ->noSandbox()
            ->waitUntilNetworkIdle(false)
            ->timeout((int) config('food-crawler.browser.timeout', 30));
    }

    /**
     * Load a public page with Chromium and return its rendered HTML.
     *
     * @param string $url Public page URL.
     * @return string Rendered HTML.
     * @throws FoodCrawlerException When Chromium cannot load the page.
     */
    public function load(string $url): string
    {
        try {
            return $this->browser($url)->bodyHtml();
        } catch (Throwable $exception) {
            throw $this->wrapException($url, $exception);
        }
    }

    public function networkData(string $url): array
    {
        try {
            $script = __DIR__.'/capture-network.cjs';
            $nodeModulePath = (string) config('food-crawler.browser.node_module_path');
            putenv('NODE_PATH='.$nodeModulePath);
            $command = sprintf(
                '"%s" "%s" "%s" 2>&1',
                config('food-crawler.browser.node_binary'),
                $script,
                $url
            );
            $output = shell_exec($command);
            $decoded = json_decode((string) $output, true);
            if (! is_array($decoded)) {
                throw new FoodCrawlerException('Browser did not return network response data.');
            }

            return $decoded;
        } catch (Throwable $exception) {
            throw $this->wrapException($url, $exception);
        }
    }

    private function wrapException(string $url, Throwable $exception): FoodCrawlerException
    {
            Log::error('Food crawler browser transport failed.', [
                'url' => $url,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
            if ($exception instanceof FoodCrawlerException) {
                throw $exception;
            }
        return new FoodCrawlerException('Browser could not load the restaurant page: '.$exception->getMessage(), 0, $exception);
    }
}

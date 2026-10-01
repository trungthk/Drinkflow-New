<?php

$isWindows = PHP_OS_FAMILY === 'Windows';

return [
    'browser' => [
        'chrome_path' => env('FOOD_CRAWLER_CHROME_PATH', $isWindows ? 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe' : '/usr/bin/chromium'),
        'node_binary' => env('FOOD_CRAWLER_NODE_BINARY', $isWindows ? 'C:\\Program Files\\nodejs\\node.exe' : '/usr/bin/node'),
        'npm_binary' => env('FOOD_CRAWLER_NPM_BINARY', $isWindows ? 'C:\\Program Files\\nodejs\\npm.cmd' : '/usr/bin/npm'),
        'node_module_path' => env('FOOD_CRAWLER_NODE_MODULE_PATH', base_path('node_modules')),
        'timeout' => (int) env('FOOD_CRAWLER_BROWSER_TIMEOUT', 30),
        'headless' => filter_var(env('FOOD_CRAWLER_HEADLESS', true), FILTER_VALIDATE_BOOLEAN),
        'user_data_dir' => env('FOOD_CRAWLER_USER_DATA_DIR'),
        'profile_directory' => env('FOOD_CRAWLER_PROFILE_DIRECTORY'),
        'diagnostics_path' => env('FOOD_CRAWLER_DIAGNOSTICS_PATH', storage_path('logs/food-crawler-browser.json')),
    ],
    'providers' => [
        \App\Services\FoodCrawler\Providers\ShopeeFoodProvider::class,
        \App\Services\FoodCrawler\Providers\HighlandsCoffeeProvider::class,
    ],

    // Plain-HTML brand websites crawled over HTTP (no headless browser).
    // A URL belongs to a brand only when its host equals one of `domains` or is a subdomain of one
    // (exact suffix ".domain"); substring matches such as "highlandscoffee.com.vn.evil.test" never pass.
    'http' => [
        'timeout' => (int) env('FOOD_CRAWLER_HTTP_TIMEOUT', 10),
        'retries' => (int) env('FOOD_CRAWLER_HTTP_RETRIES', 2),
        'retry_delay_ms' => (int) env('FOOD_CRAWLER_HTTP_RETRY_DELAY_MS', 500),
        // Pause between two requests of one crawl run so the source site is not hammered.
        'request_delay_ms' => (int) env('FOOD_CRAWLER_HTTP_REQUEST_DELAY_MS', 250),
        'max_redirects' => 3,
        'user_agent' => 'Mozilla/5.0 (compatible; DrinkFlowMenuBot/1.0)',
    ],
    'brands' => [
        'highlands' => [
            'name' => 'Highlands Coffee',
            // highlandscoffee.com is not listed: it did not resolve to a working Highlands site when this
            // crawler was built (HTTP 523). Add it here once it is confirmed to belong to Highlands.
            'domains' => ['highlandscoffee.com.vn'],
            'menu_url' => 'https://www.highlandscoffee.com.vn/vn/san-pham.html',
            // Safety cap on product detail pages fetched in one run.
            'max_products' => 200,
        ],
    ],
];

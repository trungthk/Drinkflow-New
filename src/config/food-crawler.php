<?php

$isWindows = PHP_OS_FAMILY === 'Windows';

return [
    'browser' => [
        'chrome_path' => env('FOOD_CRAWLER_CHROME_PATH', $isWindows ? 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe' : '/usr/bin/chromium'),
        'node_binary' => env('FOOD_CRAWLER_NODE_BINARY', $isWindows ? 'C:\\Program Files\\nodejs\\node.exe' : '/usr/bin/node'),
        'npm_binary' => env('FOOD_CRAWLER_NPM_BINARY', $isWindows ? 'C:\\Program Files\\nodejs\\npm.cmd' : '/usr/bin/npm'),
        'node_module_path' => env('FOOD_CRAWLER_NODE_MODULE_PATH', base_path('node_modules')),
        'timeout' => (int) env('FOOD_CRAWLER_BROWSER_TIMEOUT', 30),
    ],
    'providers' => [
        \App\Services\FoodCrawler\Providers\ShopeeFoodProvider::class,
    ],
];

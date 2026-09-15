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
    ],
];

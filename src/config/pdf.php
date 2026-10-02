<?php

declare(strict_types=1);

/*
 * PDF rendering through headless Chrome (spatie/browsershot + puppeteer, already used by the crawler).
 * Used for downloadable documents such as the Agent's platform invoices (/admin/billing).
 */
$isWindows = PHP_OS_FAMILY === 'Windows';

return [
    // Node.js binary that runs puppeteer.
    'node_binary' => env('PDF_NODE_BINARY', $isWindows ? 'C:\\Program Files\\nodejs\\node.exe' : '/usr/bin/node'),
    // npm binary (Browsershot uses it to locate global modules when node_module_path is empty).
    'npm_binary' => env('PDF_NPM_BINARY', $isWindows ? 'C:\\Program Files\\nodejs\\npm.cmd' : '/usr/bin/npm'),
    // node_modules directory that contains puppeteer.
    'node_module_path' => env('PDF_NODE_MODULE_PATH', base_path('node_modules')),
    // Chrome/Chromium executable; empty lets puppeteer use its bundled browser.
    'chrome_path' => env('PDF_CHROME_PATH', ''),
    // Seconds before rendering is abandoned (the invoice is then shown as a printable page instead).
    'timeout' => (int) env('PDF_TIMEOUT', 30),
    // Disable Chrome's sandbox (needed when the web server runs as root, e.g. some VPS setups).
    'no_sandbox' => (bool) env('PDF_NO_SANDBOX', false),
];

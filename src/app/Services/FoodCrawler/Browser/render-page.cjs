const puppeteer = require('puppeteer');
const { isAllowedUrl } = require('./network-guard.cjs');

async function main() {
    const [url, chromePath, headlessValue, userDataDir, profileDirectory] = process.argv.slice(2);
    const browser = await puppeteer.launch({
        executablePath: chromePath,
        headless: headlessValue !== 'false',
        ...(userDataDir ? { userDataDir } : {}),
        args: ['--no-sandbox', '--disable-blink-features=AutomationControlled', ...(profileDirectory ? [`--profile-directory=${profileDirectory}`] : [])],
    });
    const page = await browser.newPage();
    await page.setViewport({ width: 1440, height: 1000, deviceScaleFactor: 1 });
    await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/131.0.0.0 Safari/537.36');
    await page.setExtraHTTPHeaders({ 'Accept-Language': 'vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7' });
    await page.setRequestInterception(true);
    page.on('request', (request) => {
        isAllowedUrl(request.url()).then((allowed) => (allowed ? request.continue() : request.abort('blockedbyclient')), () => request.abort('failed'));
    });
    await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.evaluate(() => {
        ['Đồng ý', 'Tôi đã hiểu', 'Close', 'Đóng', 'OK'].forEach((label) => {
            [...document.querySelectorAll('button, [role="button"]')].filter((element) => element.textContent?.trim().toLowerCase().includes(label.toLowerCase())).forEach((element) => element.click());
        });
    });
    await page.waitForFunction(() => Boolean(
        document.querySelector('main') ||
        document.querySelector('[class*="menu"]') ||
        document.querySelector('[class*="dish"]') ||
        document.querySelector('[class*="product"]')
    ), { timeout: 15000 }).catch(() => undefined);
    for (let index = 0; index < 8; index++) {
        await page.evaluate(() => window.scrollBy(0, Math.max(window.innerHeight, 700)));
        await new Promise((resolve) => setTimeout(resolve, 1200));
    }
    await new Promise((resolve) => setTimeout(resolve, 3000));
    const html = await page.content();
    await browser.close();
    process.stdout.write(JSON.stringify({ html: Buffer.from(html, 'utf8').toString('base64') }));
}

main().catch((error) => {
    process.stderr.write(error.message);
    process.exit(1);
});

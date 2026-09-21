const puppeteer = require('puppeteer');
const { isAllowedUrl } = require('./network-guard.cjs');

async function main() {
const [url, chromePath, headlessValue, userDataDir, profileDirectory] = process.argv.slice(2);
const headless = headlessValue !== 'false';
const browser = await puppeteer.launch({
    executablePath: chromePath,
    headless,
    ...(userDataDir ? { userDataDir } : {}),
    args: [
        '--no-sandbox',
        '--disable-web-security',
        '--disable-blink-features=AutomationControlled',
        ...(profileDirectory ? [`--profile-directory=${profileDirectory}`] : []),
    ],
});
const page = await browser.newPage();
await page.setViewport({ width: 1440, height: 1000, deviceScaleFactor: 1 });
await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36');
await page.setExtraHTTPHeaders({ 'Accept-Language': 'vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7' });
await page.setRequestInterception(true);
page.on('request', (request) => {
    isAllowedUrl(request.url()).then((allowed) => {
        if (!allowed) {
            request.abort('blockedbyclient');
            return;
        }
        if (!request.url().includes('gappapi.deliverynow.vn')) {
            request.continue();
            return;
        }
        request.continue({
            headers: {
                ...request.headers(),
                Origin: 'https://shopeefood.vn',
                Referer: 'https://shopeefood.vn/',
                'x-foody-api-version': '1',
                'x-foody-app-type': '1004',
                'x-foody-client-language': 'vi',
                'x-foody-client-type': '1',
                'x-foody-client-version': '3.0.0',
            },
        });
    }, () => request.abort('failed'));
});
const payload = {
    restaurant: null,
    dishes: null,
    requests: [],
    pageErrors: [],
    consoleErrors: [],
    finalUrl: null,
    title: null,
    bodyText: null,
};
const responseJobs = [];

page.on('pageerror', (error) => {
    payload.pageErrors.push(error.message);
});
page.on('console', (message) => {
    if (message.type() === 'error') payload.consoleErrors.push(message.text());
});

page.on('response', (response) => {
    const responseUrl = response.url();
    if (!responseUrl.includes('/api/')) {
        return;
    }
    payload.requests.push({ url: responseUrl, status: response.status() });
    if (!responseUrl.includes('/api/delivery/get_from_url') && !responseUrl.includes('/api/dish/get_delivery_dishes')) return;
    responseJobs.push(response.json().then((body) => {
        if (responseUrl.includes('/api/delivery/get_from_url')) payload.restaurant = body;
        if (responseUrl.includes('/api/dish/get_delivery_dishes')) payload.dishes = body;
    }).catch(() => undefined));
});

await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
await page.evaluate(() => {
    const labels = ['Đồng ý', 'Tôi đã hiểu', 'Close', 'Đóng', 'OK'];
    [...document.querySelectorAll('button, [role="button"]')].forEach((element) => {
        if (labels.some((label) => element.textContent?.trim().toLowerCase().includes(label.toLowerCase()))) element.click();
    });
});
for (let index = 0; index < 8; index++) {
    await page.evaluate(() => window.scrollBy(0, Math.max(window.innerHeight, 700)));
    await new Promise((resolve) => setTimeout(resolve, 1500));
    if (payload.dishes) break;
}
await new Promise((resolve) => setTimeout(resolve, 15000));
await Promise.allSettled(responseJobs);
payload.finalUrl = page.url();
payload.title = await page.title();
payload.bodyText = (await page.$eval('body', (body) => body.innerText)).slice(0, 4000);
if (process.env.FOOD_CRAWLER_DIAGNOSTICS_PATH) {
    require('fs').writeFileSync(process.env.FOOD_CRAWLER_DIAGNOSTICS_PATH, JSON.stringify(payload, null, 2));
}
await browser.close();
process.stdout.write(JSON.stringify(payload));
}

main().catch((error) => {
    process.stderr.write(error.message);
    process.exit(1);
});

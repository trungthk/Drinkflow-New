const puppeteer = require('puppeteer');

async function main() {
const [url, chromePath] = process.argv.slice(2);
const browser = await puppeteer.launch({ executablePath: chromePath, headless: true, args: ['--no-sandbox'] });
const page = await browser.newPage();
await page.setUserAgent('Mozilla/5.0');
await page.setExtraHTTPHeaders({
    Origin: 'https://shopeefood.vn',
    Referer: 'https://shopeefood.vn/',
    'x-foody-api-version': '1',
    'x-foody-app-type': '1004',
    'x-foody-client-language': 'vi',
    'x-foody-client-type': '1',
    'x-foody-client-version': '3.0.0',
});
const payload = { restaurant: null, dishes: null, requests: [], pageErrors: [] };
const responseJobs = [];

page.on('pageerror', (error) => {
    payload.pageErrors.push(error.message);
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
await new Promise((resolve) => setTimeout(resolve, 3000));
await Promise.allSettled(responseJobs);
await browser.close();
process.stdout.write(JSON.stringify(payload));
}

main().catch((error) => {
    process.stderr.write(error.message);
    process.exit(1);
});

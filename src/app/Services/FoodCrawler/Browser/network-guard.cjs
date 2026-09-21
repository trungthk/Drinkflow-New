const dns = require('dns').promises;
const net = require('net');

const verdicts = new Map();

function isPrivateIpv4(ip) {
    const [a, b] = ip.split('.').map(Number);
    return a === 0 || a === 10 || a === 127
        || (a === 100 && b >= 64 && b <= 127)
        || (a === 169 && b === 254)
        || (a === 172 && b >= 16 && b <= 31)
        || (a === 192 && b === 168)
        || (a === 192 && b === 0)
        || (a === 198 && (b === 18 || b === 19))
        || a >= 224;
}

function isPrivateIp(ip) {
    if (net.isIPv4(ip)) return isPrivateIpv4(ip);
    const lower = ip.toLowerCase();
    const mapped = lower.match(/^::ffff:(\d+\.\d+\.\d+\.\d+)$/);
    if (mapped) return isPrivateIpv4(mapped[1]);
    return lower === '::' || lower === '::1' || lower.startsWith('fc') || lower.startsWith('fd') || /^fe[89ab]/.test(lower);
}

async function hostIsPublic(host) {
    if (host === 'localhost' || host.endsWith('.localhost') || host.endsWith('.internal') || host.endsWith('.local')) return false;
    if (net.isIP(host)) return !isPrivateIp(host);
    if (!verdicts.has(host)) {
        verdicts.set(host, dns.lookup(host, { all: true }).then(
            (records) => records.length > 0 && records.every((record) => !isPrivateIp(record.address)),
            () => false,
        ));
    }
    return verdicts.get(host);
}

/**
 * Decide whether Chromium may fetch a URL. Blocks non-web schemes and any host that is, or resolves to,
 * a private, loopback or link-local address. Called for every request, so redirects are covered too.
 */
async function isAllowedUrl(raw) {
    if (raw === 'about:blank' || raw.startsWith('data:') || raw.startsWith('blob:')) return true;
    let url;
    try {
        url = new URL(raw);
    } catch {
        return false;
    }
    if (url.protocol !== 'http:' && url.protocol !== 'https:') return false;
    return hostIsPublic(url.hostname.replace(/^\[|\]$/g, ''));
}

module.exports = { isAllowedUrl };

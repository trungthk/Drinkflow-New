<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Constants\AppLocale;
use App\Models\Version;
use App\Services\Guide\UserGuideService;
use App\Support\Helpers\LocaleUrl;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    /**
     * Render robots.txt with the crawl rules and the absolute sitemap URL.
     *
     * Private areas that are never linked from public pages are disallowed. /rooms and /check-order are
     * intentionally left crawlable so crawlers can read their "noindex" header instead of indexing a bare URL.
     *
     * @return Response Plain-text robots.txt response.
     */
    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /superadmin',
            'Disallow: /me',
            'Disallow: /auth/',
            'Disallow: /lang/',
            'Disallow: /logout',
            'Disallow: /dev/',
            'Allow: /',
            '',
            'Sitemap: '.url('/sitemap.xml'),
            '',
        ];

        return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /**
     * Render sitemap.xml listing the public, indexable pages.
     *
     * @return Response XML sitemap response.
     */
    public function sitemap(): Response
    {
        $lastmod = now()->toDateString();
        try {
            $latest = Version::query()->latest('updated_at')->first();
            if ($latest?->updated_at) {
                $lastmod = $latest->updated_at->toDateString();
            }
        } catch (\Throwable) {
            // Fall back to today's date when the versions table is unavailable.
        }

        $paths = [
            ['/', '1.0', 'weekly'],
            ['/versions', '0.6', 'weekly'],
            ['/contact', '0.5', 'monthly'],
            ['/terms', '0.3', 'yearly'],
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'."\n";
        foreach ($paths as [$path, $priority, $freq]) {
            $alternates = '';
            foreach (AppLocale::codes() as $code) {
                $alternates .= '    <xhtml:link rel="alternate" hreflang="'.$code.'" href="'.e(LocaleUrl::url($path, $code)).'"/>'."\n";
            }
            $alternates .= '    <xhtml:link rel="alternate" hreflang="x-default" href="'.e(LocaleUrl::url($path, AppLocale::DEFAULT)).'"/>'."\n";

            foreach (AppLocale::codes() as $code) {
                $xml .= "  <url>\n    <loc>".e(LocaleUrl::url($path, $code))."</loc>\n".$alternates."    <lastmod>{$lastmod}</lastmod>\n    <changefreq>{$freq}</changefreq>\n    <priority>{$priority}</priority>\n  </url>\n";
            }
        }
        $guides = [['/guides', '0.7', 'monthly']];
        foreach (app(UserGuideService::class)->list() as $article) {
            $guides[] = ['/guides/'.$article['slug'], '0.6', 'monthly'];
        }
        foreach ($guides as [$path, $priority, $freq]) {
            $xml .= "  <url>\n    <loc>".e(url($path))."</loc>\n    <lastmod>{$lastmod}</lastmod>\n    <changefreq>{$freq}</changefreq>\n    <priority>{$priority}</priority>\n  </url>\n";
        }
        $xml .= "</urlset>\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}

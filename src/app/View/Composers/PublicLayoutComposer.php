<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Constants\AppLocale;
use App\Support\Helpers\LocaleUrl;
use App\Models\Version;
use Illuminate\View\View;

class PublicLayoutComposer
{
    /**
     * Share common SEO, navigation and authentication URLs with public layouts.
     *
     * @param View $view Public layout view instance.
     * @return void
     */
    public function compose(View $view): void
    {
        $data = $view->getData();
        $locale = app()->getLocale();
        $title = $data['title'] ?? null;
        $description = $data['description'] ?? null;
        $versionString = $data['version'] ?? Version::getLatestVersionString();
        $basePath = LocaleUrl::strip(request()->path());
        $localizable = request()->isMethod('GET') && LocaleUrl::isLocalizable($basePath);
        $localeUrls = [];
        $alternates = [];
        foreach (AppLocale::codes() as $code) {
            if ($localizable) {
                $alternates[$code] = LocaleUrl::url($basePath, $code);
                // Default-language links must reset the session locale first, otherwise "/" would still render the previous language.
                $localeUrls[$code] = $code === AppLocale::DEFAULT
                    ? route('locale.switch', ['locale' => $code, 'to' => $basePath])
                    : $alternates[$code];
            } else {
                $localeUrls[$code] = route('locale.switch', $code);
            }
        }

        $view->with([
            'basePath' => $basePath,
            'localeUrls' => $localeUrls,
            'alternateUrls' => $alternates,
            'version' => $versionString,
            'appVersion' => $data['appVersion'] ?? $versionString,
            'landingUrl' => $data['landingUrl'] ?? LocaleUrl::url('/'),
            'termsUrl' => $data['termsUrl'] ?? LocaleUrl::url('/terms'),
            'versionsUrl' => $data['versionsUrl'] ?? LocaleUrl::url('/versions'),
            'contactUrl' => $data['contactUrl'] ?? LocaleUrl::url('/contact'),
            'googleAuthUrl' => $data['googleAuthUrl'] ?? route('auth.google'),
            'locale' => $locale,
            'ogLocale' => match ($locale) {
                'vi' => 'vi_VN',
                'ja' => 'ja_JP',
                default => 'en_US',
            },
            'pageTitle' => $title ?? __('public.meta.title'),
            'pageDescription' => $description ?? __('public.meta.description'),
            'pageKeywords' => $data['keywords'] ?? __('public.meta.keywords'),
            'pageOgTitle' => $data['ogTitle'] ?? ($title ?? __('public.meta.og_title')),
            'pageOgDescription' => $data['ogDescription'] ?? ($description ?? __('public.meta.og_description')),
            'pageOgImage' => $data['ogImage'] ?? asset('images/home-intro.jpg'),
            'currentUrl' => $data['canonicalUrl'] ?? ($localizable ? $alternates[$locale] : url()->current()),
        ]);
    }
}

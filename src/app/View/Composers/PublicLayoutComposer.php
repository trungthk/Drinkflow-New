<?php

declare(strict_types=1);

namespace App\View\Composers;

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
        $view->with([
            'version' => $versionString,
            'appVersion' => $data['appVersion'] ?? $versionString,
            'landingUrl' => $data['landingUrl'] ?? route('landing'),
            'termsUrl' => $data['termsUrl'] ?? url('/terms'),
            'versionsUrl' => $data['versionsUrl'] ?? url('/versions'),
            'contactUrl' => $data['contactUrl'] ?? route('contact'),
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
            'pageOgImage' => $data['ogImage'] ?? asset('images/default-avatar.svg'),
            'currentUrl' => $data['canonicalUrl'] ?? url()->current(),
        ]);
    }
}

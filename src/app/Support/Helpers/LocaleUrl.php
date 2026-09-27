<?php

declare(strict_types=1);

namespace App\Support\Helpers;

use App\Constants\AppLocale;

class LocaleUrl
{
    /**
     * Locale codes that are served under a URL prefix (every supported locale except the default one).
     *
     * @return array<int, string>
     */
    public static function prefixedLocales(): array
    {
        return array_values(array_diff(AppLocale::codes(), [AppLocale::DEFAULT]));
    }

    /**
     * Remove a leading locale prefix (/en, /ja) from a path.
     *
     * @param string $path Request path such as "/en/terms".
     * @return string Path without locale prefix, always starting with "/".
     */
    public static function strip(string $path): string
    {
        $path = '/'.ltrim($path, '/');
        $codes = implode('|', array_map('preg_quote', self::prefixedLocales()));

        if (preg_match('#^/('.$codes.')(/.*)?$#', $path, $matches)) {
            $rest = $matches[2] ?? '';

            return $rest === '' ? '/' : $rest;
        }

        return $path;
    }

    /**
     * Whether the path (without locale prefix) is a public marketing page available in every language.
     *
     * @param string $path Path without locale prefix.
     * @return bool True for /, /terms, /contact and /versions[/x].
     */
    public static function isLocalizable(string $path): bool
    {
        $path = self::strip($path);

        return in_array($path, ['/', '/terms', '/contact', '/versions'], true) || str_starts_with($path, '/versions/');
    }

    /**
     * Build the path of a public page in the given locale.
     *
     * @param string $path Path with or without locale prefix.
     * @param string|null $locale Target locale; defaults to the active one.
     * @return string Localized path.
     */
    public static function path(string $path, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $path = self::strip($path);

        if (! in_array($locale, self::prefixedLocales(), true)) {
            return $path;
        }

        return $path === '/' ? '/'.$locale : '/'.$locale.$path;
    }

    /**
     * Build the absolute URL of a public page in the given locale.
     *
     * @param string $path Path with or without locale prefix.
     * @param string|null $locale Target locale; defaults to the active one.
     * @return string Absolute URL.
     */
    public static function url(string $path, ?string $locale = null): string
    {
        return url(self::path($path, $locale));
    }
}

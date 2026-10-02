<?php

declare(strict_types=1);

namespace App\Support\Helpers;

/**
 * Captcha helper utility functions
 */
class CaptchaHelper
{
    /**
     * Check if captcha is enabled and available
     *
     * Verifies:
     * - Not in local environment (production and staging always show it)
     * - Not switched off with CAPTCHA_DISABLE
     * - GD extension is loaded
     * - captcha_img function exists
     *
     * Use it both to render the field and to decide whether the request must validate it, so a
     * form never requires a captcha it does not show (or shows one it does not check).
     *
     * @return bool
     */
    public static function isCaptchaEnabled(): bool
    {
        return !app()->isLocal()
            && !config('captcha.disable', false)
            && extension_loaded('gd')
            && function_exists('captcha_img');
    }
}

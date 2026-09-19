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
     * - Not in local environment
     * - GD extension is loaded
     * - captcha_img function exists
     *
     * @return bool
     */
    public static function isCaptchaEnabled(): bool
    {
        return !app()->isLocal()
            && extension_loaded('gd')
            && function_exists('captcha_img');
    }
}

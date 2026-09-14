<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * VietQrPayment Blade Component
 *
 * Renders a payment QR card with an animated "snake border" SVG overlay.
 *
 * Props:
 * @property string       $qrSrc             URL or base64 data-URI of the QR image.
 * @property int|float    $amount            Payment amount in VNĐ (displayed formatted).
 * @property string       $bankName          Bank name shown above the QR.
 * @property string       $accountName       Beneficiary account name.
 * @property string       $accountNumber     Beneficiary account number.
 * @property string       $description       Transfer reference / memo.
 * @property int          $size              QR container width in px (default 300).
 * @property float        $animationDuration Snake animation duration in seconds (default 2.5).
 * @property string       $snakeColor        CSS color of the animated snake stroke.
 * @property string       $trackColor        CSS color of the static border track.
 * @property int          $borderRadius      Border radius of the QR card in px.
 * @property string       $componentId       Unique DOM id prefix (auto-generated if empty).
 */
class VietQrPayment extends Component
{
    /**
     * Unique component instance ID used to scope CSS variables and SVG IDs.
     */
    public readonly string $componentId;

    /**
     * Create a new component instance.
     *
     * @param  string       $qrSrc             QR image source (URL or data URI).
     * @param  int|float    $amount            Amount in VNĐ.
     * @param  string       $bankName          Bank name label.
     * @param  string       $accountName       Account holder name.
     * @param  string       $accountNumber     Account number string.
     * @param  string       $description       Transfer memo / reference label.
     * @param  int          $size              Card/QR width in pixels (default: 300).
     * @param  float        $animationDuration Snake animation duration in seconds (default: 2.5).
     * @param  string       $snakeColor        Animated snake border color (default: #16a34a — green-600).
     * @param  string       $trackColor        Static border track color (default: #e2e8f0 — slate-200).
     * @param  int          $borderRadius      Card border radius in pixels (default: 20).
     * @param  string       $componentId       Override the auto-generated scope ID.
     */
    public function __construct(
        public readonly string      $qrSrc,
        public readonly int|float   $amount            = 0,
        public readonly string      $bankName          = '',
        public readonly string      $accountName       = '',
        public readonly string      $accountNumber     = '',
        public readonly string      $description       = '',
        public readonly int         $size              = 300,
        public readonly float       $animationDuration = 2.5,
        public readonly string      $snakeColor        = '#16a34a',
        public readonly string      $trackColor        = '#e2e8f0',
        public readonly int         $borderRadius      = 20,
        string                      $componentId       = '',
    ) {
        $this->componentId = $componentId !== '' ? $componentId : 'vqr-' . substr(md5(uniqid('', true)), 0, 8);
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View
     */
    public function render(): View
    {
        return view('components.viet-qr-payment');
    }

    /**
     * Return the amount formatted as Vietnamese currency string.
     *
     * @return string e.g. "150.000 ₫"
     */
    public function formattedAmount(): string
    {
        return number_format((float) $this->amount, 0, ',', '.') . ' ₫';
    }

    /**
     * Return the animation duration as a CSS time value string.
     *
     * @return string e.g. "2.5s"
     */
    public function durationCss(): string
    {
        return number_format($this->animationDuration, 1, '.', '') . 's';
    }
}

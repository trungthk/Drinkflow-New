<?php

declare(strict_types=1);

namespace App\Services\Billing\Gateway;

use App\Models\AdminInvoice;
use Illuminate\Http\Request;

/**
 * Online payment provider for platform invoices.
 *
 * The provider hosts the payment page; DrinkFlow only builds the checkout link and trusts a payment
 * once a notification with a valid signature arrives (never the browser redirect).
 */
interface PaymentGateway
{
    /**
     * Whether online payment is configured and switched on.
     *
     * @return bool True when checkout links can be issued.
     */
    public function isEnabled(): bool;

    /**
     * URL of the hosted checkout page for the invoice's remaining amount.
     *
     * @param AdminInvoice $invoice Outstanding invoice.
     * @return string Checkout URL.
     */
    public function checkoutUrl(AdminInvoice $invoice): string;

    /**
     * Verify and parse a payment notification.
     *
     * @param Request $request Incoming webhook request.
     * @return GatewayNotification Parsed notification.
     * @throws InvalidGatewaySignature When the signature is missing or wrong.
     */
    public function parseNotification(Request $request): GatewayNotification;
}

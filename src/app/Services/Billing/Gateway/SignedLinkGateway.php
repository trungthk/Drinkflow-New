<?php

declare(strict_types=1);

namespace App\Services\Billing\Gateway;

use App\Models\AdminInvoice;
use Illuminate\Http\Request;

/**
 * Generic hosted-checkout gateway secured by an HMAC-SHA256 shared secret.
 *
 * Checkout link: `checkout_url?invoice=…&amount=…&return_url=…&signature=hmac(invoice|amount)`.
 * Notification: JSON body {transaction_id, invoice_number, amount, status} signed in the
 * `X-Signature` header as hmac(raw body). Settings live in config('platform.payments').
 */
class SignedLinkGateway implements PaymentGateway
{
    /**
     * Whether online payment is configured and switched on.
     *
     * @return bool True when enabled with a checkout URL and a secret.
     */
    public function isEnabled(): bool
    {
        return (bool) config('platform.payments.enabled')
            && filled(config('platform.payments.checkout_url'))
            && filled(config('platform.payments.webhook_secret'));
    }

    /**
     * URL of the hosted checkout page for the invoice's remaining amount.
     *
     * @param AdminInvoice $invoice Outstanding invoice.
     * @return string Checkout URL.
     */
    public function checkoutUrl(AdminInvoice $invoice): string
    {
        $amount = $invoice->remaining();
        $query = http_build_query([
            'invoice' => $invoice->number,
            'amount' => $amount,
            'return_url' => route('admin.billing.index'),
            'signature' => $this->sign($invoice->number.'|'.$amount),
        ]);
        $base = (string) config('platform.payments.checkout_url');

        return $base.(str_contains($base, '?') ? '&' : '?').$query;
    }

    /**
     * Verify and parse a payment notification.
     *
     * @param Request $request Incoming webhook request.
     * @return GatewayNotification Parsed notification.
     * @throws InvalidGatewaySignature When the signature is missing or wrong, or the payload is incomplete.
     */
    public function parseNotification(Request $request): GatewayNotification
    {
        $body = (string) $request->getContent();
        $signature = (string) $request->header('X-Signature', '');
        if (! $this->isEnabled() || $signature === '' || ! hash_equals($this->sign($body), $signature)) {
            throw new InvalidGatewaySignature('Invalid payment notification signature.');
        }

        $data = json_decode($body, true);
        if (! is_array($data) || ! isset($data['transaction_id'], $data['invoice_number'], $data['amount'], $data['status']) || ! is_numeric($data['amount'])) {
            throw new InvalidGatewaySignature('Incomplete payment notification.');
        }

        return new GatewayNotification((string) $data['transaction_id'], (string) $data['invoice_number'], (int) $data['amount'], (string) $data['status']);
    }

    /**
     * HMAC-SHA256 of a payload with the shared secret.
     *
     * @param string $payload Signed content.
     * @return string Hex signature.
     */
    public function sign(string $payload): string
    {
        return hash_hmac('sha256', $payload, (string) config('platform.payments.webhook_secret'));
    }
}

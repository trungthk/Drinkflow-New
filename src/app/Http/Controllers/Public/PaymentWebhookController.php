<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Billing\Gateway\InvalidGatewaySignature;
use App\Services\Billing\Gateway\PaymentGateway;
use App\Services\Billing\PlatformBillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Payment notifications of the platform invoice gateway (POST /payments/webhook).
 *
 * Only a correctly signed notification can record a payment; replays are harmless because payments
 * are keyed by the gateway transaction ID.
 */
class PaymentWebhookController extends Controller
{
    /**
     * Verify the notification and record the payment.
     *
     * @param Request $request Incoming webhook.
     * @param PaymentGateway $gateway Payment gateway.
     * @param PlatformBillingService $billing Billing service.
     * @return JsonResponse Outcome: recorded or ignored (403 for a bad signature).
     */
    public function __invoke(Request $request, PaymentGateway $gateway, PlatformBillingService $billing): JsonResponse
    {
        try {
            $notification = $gateway->parseNotification($request);
        } catch (InvalidGatewaySignature) {
            return response()->json(['message' => 'Invalid signature.'], Response::HTTP_FORBIDDEN);
        }

        $payment = $billing->recordGatewayPayment($notification);

        return response()->json(['status' => $payment !== null ? 'recorded' : 'ignored', 'payment_id' => $payment?->id]);
    }
}

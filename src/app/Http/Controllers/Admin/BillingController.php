<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminInvoice;
use App\Services\Billing\Gateway\PaymentGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * The Agent's platform billing history (/admin/billing): its own invoices and payments only.
 */
class BillingController extends Controller
{
    /**
     * Invoices, payments and the amount still owed.
     *
     * @param Request $request Incoming request.
     * @param PaymentGateway $gateway Payment gateway (online payment button).
     * @return View Billing history.
     */
    public function index(Request $request, PaymentGateway $gateway): View
    {
        /** @var Admin $admin */
        $admin = $request->user('admin');

        return view('admin.billing.index', [
            'invoices' => $admin->invoices()->with('package:id,name')->latest('period_start')->latest('id')->paginate(\App\Constants\Pagination::ADMIN_PER_PAGE),
            'payments' => $admin->platformPayments()->with('invoice:id,number')->latest('paid_at')->limit(50)->get(),
            'balance' => (int) $admin->invoices()->outstanding()->selectRaw('COALESCE(SUM(total - paid_amount), 0) as due')->value('due'),
            'onlinePayment' => $gateway->isEnabled(),
        ]);
    }

    /**
     * Send the Agent to the provider's checkout page for one of its own outstanding invoices.
     *
     * @param Request $request Incoming request.
     * @param AdminInvoice $invoice Invoice (404 when it belongs to another Agent).
     * @param PaymentGateway $gateway Payment gateway.
     * @return RedirectResponse Redirect to the hosted checkout.
     */
    public function pay(Request $request, AdminInvoice $invoice, PaymentGateway $gateway): RedirectResponse
    {
        abort_unless((int) $invoice->admin_id === (int) $request->user('admin')->id, 404);
        abort_unless($gateway->isEnabled() && $invoice->status->isOutstanding() && $invoice->remaining() > 0, 404);

        return redirect()->away($gateway->checkoutUrl($invoice));
    }
}

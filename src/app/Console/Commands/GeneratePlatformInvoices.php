<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Billing\PlatformBillingService;
use Illuminate\Console\Command;

class GeneratePlatformInvoices extends Command
{
    /** @var string */
    protected $signature = 'billing:generate-invoices';

    /** @var string */
    protected $description = 'Invoice the current period of every active Agent subscription (idempotent).';

    /**
     * Create the missing invoices of the current subscription periods.
     *
     * @param PlatformBillingService $billing Billing service.
     * @return int Process exit code.
     */
    public function handle(PlatformBillingService $billing): int
    {
        $this->info('Invoices created: '.$billing->generateInvoices());

        return self::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Billing\PlatformBillingService;
use Illuminate\Console\Command;

class ProcessOverdueInvoices extends Command
{
    /** @var string */
    protected $signature = 'billing:process-overdue';

    /** @var string */
    protected $description = 'Mark open platform invoices past their due date as overdue.';

    /**
     * Flag overdue invoices.
     *
     * @param PlatformBillingService $billing Billing service.
     * @return int Process exit code.
     */
    public function handle(PlatformBillingService $billing): int
    {
        $this->info('Invoices marked overdue: '.$billing->processOverdue());

        return self::SUCCESS;
    }
}

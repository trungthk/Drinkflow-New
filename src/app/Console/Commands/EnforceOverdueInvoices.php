<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Billing\BillingEnforcementService;
use Illuminate\Console\Command;

class EnforceOverdueInvoices extends Command
{
    /** @var string */
    protected $signature = 'billing:enforce-overdue';

    /** @var string */
    protected $description = 'Suspend Agents whose overdue platform invoices are past the grace period (when PLATFORM_AUTO_SUSPEND is on).';

    /**
     * Apply the automatic suspension rule.
     *
     * @param BillingEnforcementService $enforcement Enforcement service.
     * @return int Process exit code.
     */
    public function handle(BillingEnforcementService $enforcement): int
    {
        $this->info('Agents suspended: '.$enforcement->suspendOverdueAgents());

        return self::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Subscription\SubscriptionService;
use Illuminate\Console\Command;

class ProcessSubscriptions extends Command
{
    /** @var string */
    protected $signature = 'subscriptions:process';

    /** @var string */
    protected $description = 'Close ended Agent subscription periods (renew, scheduled downgrade, cancellation).';

    /**
     * Run the subscription lifecycle; safe to run repeatedly.
     *
     * @param SubscriptionService $subscriptions Subscription service.
     * @return int Process exit code.
     */
    public function handle(SubscriptionService $subscriptions): int
    {
        $result = $subscriptions->processDuePeriods();
        $this->info("Renewed: {$result['renewed']}, changed: {$result['changed']}, cancelled: {$result['cancelled']}");

        return self::SUCCESS;
    }
}

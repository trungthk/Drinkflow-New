<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Subscription\SubscriptionService;
use Illuminate\Console\Command;

class AssignDefaultSubscriptions extends Command
{
    /** @var string */
    protected $signature = 'subscriptions:assign-default';

    /** @var string */
    protected $description = 'Give the default package (PLATFORM_DEFAULT_PACKAGE, "starter") to Agents that never had a subscription.';

    /**
     * Assign the default package; safe to run repeatedly.
     *
     * @param SubscriptionService $subscriptions Subscription service.
     * @return int Process exit code.
     */
    public function handle(SubscriptionService $subscriptions): int
    {
        $this->info('Agents given the default package: '.$subscriptions->assignDefaultToAgentsWithoutSubscription());

        return self::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PruneOperationalData extends Command
{
    /** @var string */
    protected $signature = 'drinkflow:prune-operational-data';

    /** @var string */
    protected $description = 'Run all prune tasks: audit logs, notifications, crawler previews, and storage logs.';

    /**
     * Run all data and log file pruning tasks.
     *
     * @return int Process exit code.
     */
    public function handle(): int
    {
        $this->call('drinkflow:prune-audit-logs');
        $this->call('drinkflow:prune-notifications');
        $this->call('drinkflow:prune-crawler-and-logs');

        return self::SUCCESS;
    }
}

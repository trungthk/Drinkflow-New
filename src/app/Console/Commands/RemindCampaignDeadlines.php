<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Campaign\CampaignDeadlineReminderService;
use Illuminate\Console\Command;

class RemindCampaignDeadlines extends Command
{
    /** @var string */
    protected $signature = 'drinkflow:remind-campaign-deadlines';

    /** @var string */
    protected $description = 'Remind members who have not ordered yet, and room admins, that a campaign deadline is near.';

    /**
     * Send the reminders of every campaign whose ordering deadline is within the configured window.
     *
     * @param CampaignDeadlineReminderService $reminders Reminder service.
     * @return int Process exit code.
     */
    public function handle(CampaignDeadlineReminderService $reminders): int
    {
        $count = $reminders->remindDueCampaigns();
        $this->info("Sent deadline reminders for {$count} campaign(s).");

        return self::SUCCESS;
    }
}

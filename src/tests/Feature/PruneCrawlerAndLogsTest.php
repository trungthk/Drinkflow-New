<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AdminAccount;
use App\Models\CrawlerPreview;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PruneCrawlerAndLogsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that expired crawler previews are deleted while fresh ones remain,
     * and that old log files are deleted while recent ones are kept.
     *
     * @return void
     */
    public function test_command_prunes_expired_crawler_previews_and_old_log_files(): void
    {
        config()->set('retention.crawler_previews_days', 2);
        config()->set('retention.log_files_days', 14);

        $admin = AdminAccount::create(['name' => 'Admin', 'email' => 'crawler-prune@example.test', 'password' => 'password', 'role' => 'admin', 'status' => 'active']);
        $room  = Room::create(['name' => 'Crawler Room', 'slug' => 'crawler-room', 'status' => 'active']);

        // Old preview: created 3 days ago, already expired
        $oldPreview = CrawlerPreview::create([
            'room_id'    => $room->id,
            'admin_id'   => $admin->id,
            'source_url' => 'https://example.test/old',
            'items'      => [],
            'expires_at' => now()->subDay(),
            'created_at' => now()->subDays(3),
        ]);

        // Fresh preview: expires in future
        $freshPreview = CrawlerPreview::create([
            'room_id'    => $room->id,
            'admin_id'   => $admin->id,
            'source_url' => 'https://example.test/new',
            'items'      => [],
            'expires_at' => now()->addDay(),
        ]);

        // Log files: old (15 days) vs recent (2 days)
        $logsDir      = storage_path('logs');
        $oldLogFile   = $logsDir . '/test-old-crawler-prune.log';
        $freshLogFile = $logsDir . '/test-fresh-crawler-prune.log';

        File::put($oldLogFile,   'Old log');
        File::put($freshLogFile, 'Fresh log');
        touch($oldLogFile,   now()->subDays(15)->timestamp);
        touch($freshLogFile, now()->subDays(2)->timestamp);

        try {
            $this->artisan('drinkflow:prune-crawler-and-logs')->assertSuccessful();

            $this->assertDatabaseMissing('crawler_previews', ['id' => $oldPreview->id]);
            $this->assertDatabaseHas('crawler_previews',     ['id' => $freshPreview->id]);
            $this->assertFileDoesNotExist($oldLogFile);
            $this->assertFileExists($freshLogFile);
        } finally {
            if (File::exists($oldLogFile))   { File::delete($oldLogFile); }
            if (File::exists($freshLogFile)) { File::delete($freshLogFile); }
        }
    }
}
<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\CampaignStatus;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class CampaignStatusBadgeTest extends TestCase
{
    /**
     * Every campaign status must have a badge and dot style, and each status must look distinct.
     */
    public function test_every_status_has_a_distinct_badge_style(): void
    {
        $badges = [];
        foreach (CampaignStatus::cases() as $status) {
            $this->assertNotSame('', $status->badgeClass());
            $this->assertNotSame('', $status->dotClass());
            $badges[] = $status->badgeClass();
        }

        $expired = CampaignStatus::Active->badgeClass(true);
        $this->assertNotContains($expired, $badges);

        // Every status (and the expired state) needs its own background hue, not just a slightly different shade.
        $hues = [];
        foreach ([...$badges, $expired] as $badge) {
            $this->assertSame(1, preg_match('/(?:^|\s)bg-([a-z]+)-\d+/', $badge, $match), $badge);
            $hues[] = $match[1];
        }
        $this->assertSame($hues, array_values(array_unique($hues)), 'Two campaign statuses share the same badge background hue.');
        $this->assertSame(__('admin.status_expired'), CampaignStatus::Active->label(true));
    }

    /**
     * The shared Blade component must render the enum's classes and label.
     */
    public function test_badge_component_renders_enum_styles(): void
    {
        foreach (CampaignStatus::cases() as $status) {
            $html = Blade::render('<x-admin.campaign-status-badge :status="$status" />', ['status' => $status]);

            $this->assertStringContainsString($status->badgeClass(), $html);
            $this->assertStringContainsString($status->dotClass(), $html);
            $this->assertStringContainsString($status->label(), $html);
        }
    }
}

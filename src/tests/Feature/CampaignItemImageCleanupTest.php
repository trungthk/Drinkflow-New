<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CampaignItemStatus;
use App\Enums\CampaignStatus;
use App\Models\AdminAccount;
use App\Models\Campaign;
use App\Models\CampaignItem;
use App\Models\PaymentAccount;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CampaignItemImageCleanupTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminWithRoom(): array
    {
        $admin = AdminAccount::create([
            'email' => 'cleanup-admin@example.test',
            'name' => 'Cleanup Admin',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $room = Room::create([
            'slug' => 'cleanup-room',
            'name' => 'Cleanup Room',
            'status' => 'active',
            'created_by_admin_id' => $admin->id,
        ]);

        $admin->rooms()->attach($room);

        PaymentAccount::create([
            'room_id' => $room->id,
            'account_name' => 'Admin Bank',
            'account_number' => '123456789',
            'bank_name' => 'VCB',
            'bank_code' => '970436',
            'status' => 'active',
        ]);

        return [$admin, $room];
    }

    public function test_destroy_campaign_cleans_up_internal_item_images(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('uploads/campaigns/item1.webp', 'image-data-1');
        Storage::disk('public')->put('uploads/campaigns/item2.webp', 'image-data-2');

        [$admin, $room] = $this->createAdminWithRoom();

        $campaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Cleanup Campaign',
            'restaurant' => 'Test Restaurant',
            'status' => CampaignStatus::Draft,
        ]);

        CampaignItem::create([
            'campaign_id' => $campaign->id,
            'name' => 'Drink 1',
            'base_price' => 25000,
            'image_url' => '/storage/uploads/campaigns/item1.webp',
            'status' => CampaignItemStatus::Active,
        ]);

        CampaignItem::create([
            'campaign_id' => $campaign->id,
            'name' => 'Drink 2',
            'base_price' => 30000,
            'image_url' => 'uploads/campaigns/item2.webp',
            'status' => CampaignItemStatus::Active,
        ]);

        CampaignItem::create([
            'campaign_id' => $campaign->id,
            'name' => 'Drink 3 External',
            'base_price' => 35000,
            'image_url' => 'https://external-cdn.example.com/images/drink3.jpg',
            'status' => CampaignItemStatus::Active,
        ]);

        $this->assertTrue(Storage::disk('public')->exists('uploads/campaigns/item1.webp'));
        $this->assertTrue(Storage::disk('public')->exists('uploads/campaigns/item2.webp'));

        $response = $this->actingAs($admin, 'admin')->deleteJson("/admin/{$room->id}/campaigns/{$campaign->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('campaigns', ['id' => $campaign->id]);
        $this->assertDatabaseMissing('campaign_items', ['campaign_id' => $campaign->id]);

        $this->assertFalse(Storage::disk('public')->exists('uploads/campaigns/item1.webp'));
        $this->assertFalse(Storage::disk('public')->exists('uploads/campaigns/item2.webp'));
    }

    public function test_updating_campaign_deletes_images_of_removed_items(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('uploads/campaigns/kept.webp', 'kept-data');
        Storage::disk('public')->put('uploads/campaigns/removed.webp', 'removed-data');

        [$admin, $room] = $this->createAdminWithRoom();

        $campaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Update Test Campaign',
            'restaurant' => 'Test Restaurant',
            'status' => CampaignStatus::Draft,
        ]);

        $keptItem = CampaignItem::create([
            'campaign_id' => $campaign->id,
            'name' => 'Kept Drink',
            'base_price' => 20000,
            'image_url' => '/storage/uploads/campaigns/kept.webp',
            'status' => CampaignItemStatus::Active,
        ]);

        CampaignItem::create([
            'campaign_id' => $campaign->id,
            'name' => 'Removed Drink',
            'base_price' => 25000,
            'image_url' => '/storage/uploads/campaigns/removed.webp',
            'status' => CampaignItemStatus::Active,
        ]);

        $payload = [
            'name' => 'Updated Campaign Title',
            'restaurant' => 'Test Restaurant',
            'sponsor_type' => 'none',
            'items' => [
                [
                    'id' => $keptItem->id,
                    'name' => 'Kept Drink',
                    'price' => 20000,
                    'image_url' => '/storage/uploads/campaigns/kept.webp',
                    'status' => 'active',
                ],
            ],
        ];

        $response = $this->actingAs($admin, 'admin')->patchJson("/admin/{$room->id}/campaigns/{$campaign->id}", $payload);

        $response->assertOk();
        $this->assertTrue(Storage::disk('public')->exists('uploads/campaigns/kept.webp'));
        $this->assertFalse(Storage::disk('public')->exists('uploads/campaigns/removed.webp'));
    }

    public function test_updating_campaign_item_image_deletes_old_internal_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('uploads/campaigns/old.webp', 'old-data');
        Storage::disk('public')->put('uploads/campaigns/new.webp', 'new-data');

        [$admin, $room] = $this->createAdminWithRoom();

        $campaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Update Item Image Campaign',
            'restaurant' => 'Test Restaurant',
            'status' => CampaignStatus::Draft,
        ]);

        $item = CampaignItem::create([
            'campaign_id' => $campaign->id,
            'name' => 'Changing Image Drink',
            'base_price' => 20000,
            'image_url' => '/storage/uploads/campaigns/old.webp',
            'status' => CampaignItemStatus::Active,
        ]);

        $payload = [
            'name' => 'Update Item Image Campaign',
            'restaurant' => 'Test Restaurant',
            'sponsor_type' => 'none',
            'items' => [
                [
                    'id' => $item->id,
                    'name' => 'Changing Image Drink',
                    'price' => 20000,
                    'image_url' => '/storage/uploads/campaigns/new.webp',
                    'status' => 'active',
                ],
            ],
        ];

        $response = $this->actingAs($admin, 'admin')->patchJson("/admin/{$room->id}/campaigns/{$campaign->id}", $payload);

        $response->assertOk();
        $this->assertFalse(Storage::disk('public')->exists('uploads/campaigns/old.webp'));
        $this->assertTrue(Storage::disk('public')->exists('uploads/campaigns/new.webp'));
    }
}

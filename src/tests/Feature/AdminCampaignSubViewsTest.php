<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\CampaignStatus;
use App\Enums\OrderStatus;
use App\Events\CampaignCreated;
use App\Models\AdminAccount;
use App\Models\Campaign;
use App\Models\GlobalUser;
use App\Models\Order;
use App\Models\Room;
use App\Models\RoomUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminCampaignSubViewsTest extends TestCase
{
    use RefreshDatabase;

    private AdminAccount $admin;
    private Room $room;
    private Campaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = AdminAccount::create([
            'name' => 'Room Admin',
            'email' => 'roomadmin@example.test',
            'password' => Hash::make('secret'),
            'role' => AdminRole::Admin,
            'status' => 'active',
        ]);

        $this->room = Room::create([
            'name' => 'Admin Room',
            'slug' => 'test-room',
            'status' => 'active',
        ]);

        $this->admin->rooms()->attach($this->room);

        $this->campaign = Campaign::create([
            'room_id' => $this->room->id,
            'status' => CampaignStatus::Closed,
            'name' => 'Trà sữa buổi chiều',
            'restaurant' => 'Gong Cha',
        ]);
    }

    /**
     * Test admin can access campaign info view via /info route.
     */
    public function test_admin_can_access_campaign_info_route(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get("/admin/{$this->room->slug}/campaigns/{$this->campaign->id}/info");

        $response->assertOk()
            ->assertViewIs('admin.campaign-info')
            ->assertSee('Trà sữa buổi chiều')
            ->assertSee('Gong Cha')
            ->assertSee(__('admin.campaign_nav_info'))
            ->assertSee(__('admin.campaign_nav_orders'))
            ->assertSee(__('admin.financial_settlement_summary'));
    }

    /**
     * Test admin can access campaign orders and items list view via /orders route.
     */
    public function test_admin_can_access_campaign_orders_route(): void
    {
        $globalUser = GlobalUser::create([
            'name' => 'Nguyễn Văn A',
            'email' => 'nguyenvana@example.test',
        ]);

        $roomUser = RoomUser::create([
            'room_id' => $this->room->id,
            'global_user_id' => $globalUser->id,
            'display_name' => 'Nguyễn Văn A',
            'status' => 'active',
        ]);

        Order::create([
            'room_id' => $this->room->id,
            'campaign_id' => $this->campaign->id,
            'room_user_id' => $roomUser->id,
            'subtotal' => 50000,
            'sponsor_amount' => 0,
            'final_amount' => 50000,
            'status' => OrderStatus::Submitted,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get("/admin/{$this->room->slug}/campaigns/{$this->campaign->id}/orders");

        $response->assertOk()
            ->assertViewIs('admin.campaign-orders')
            ->assertSee('Trà sữa buổi chiều')
            ->assertSee(__('admin.campaign_nav_info'))
            ->assertSee(__('admin.campaign_nav_orders'))
            ->assertSee(__('admin.aggregated_items_list'))
            ->assertSee(__('admin.orders_list_tab'))
            ->assertSee('Nguyễn Văn A');
    }

    /**
     * Test a closed campaign cannot have its info, fees, discount or price edited.
     */
    public function test_closed_campaign_rejects_updates(): void
    {
        $base = "/admin/{$this->room->slug}/campaigns/{$this->campaign->id}";
        $item = $this->campaign->items()->create([
            'name' => 'Trà đào',
            'normalized_name' => 'tra dao',
            'base_price' => 30000,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->patchJson($base, ['delivery_fee' => 5000, 'discount' => 1000])
            ->assertStatus(422)
            ->assertJsonPath('message', __('admin.campaign_locked_cannot_modify'));

        $this->actingAs($this->admin, 'admin')
            ->patchJson("{$base}/items/{$item->id}", ['name' => 'Trà đào', 'base_price' => 99000])
            ->assertStatus(422);

        $this->actingAs($this->admin, 'admin')
            ->deleteJson($base)
            ->assertStatus(422);

        $this->actingAs($this->admin, 'admin')
            ->get("{$base}/edit")
            ->assertRedirect("{$base}/info");

        $this->assertSame(0, (int) $this->campaign->fresh()->delivery_fee);
        $this->assertSame(30000, (int) $item->fresh()->base_price);
    }

    /**
     * Test the info page of a closed campaign hides edit, fee adjustment and cancel actions.
     */
    public function test_closed_campaign_info_hides_edit_actions(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get("/admin/{$this->room->slug}/campaigns/{$this->campaign->id}/info")
            ->assertOk()
            ->assertDontSee(__('admin.edit_campaign'))
            ->assertDontSee(__('admin.cancel_campaign_action'))
            ->assertDontSee("openModal('adjust-fee-modal');", false);
    }

    /**
     * Test item, size and topping creation works on an editable (active) campaign and is rejected once closed.
     */
    public function test_item_options_can_be_created_only_while_campaign_is_editable(): void
    {
        $base = "/admin/{$this->room->slug}/campaigns/{$this->campaign->id}";
        $item = $this->campaign->items()->create(['name' => 'Trà đào', 'normalized_name' => 'tra dao', 'base_price' => 30000]);

        $this->actingAs($this->admin, 'admin')
            ->postJson("{$base}/items", ['name' => 'Trà vải', 'base_price' => 32000])
            ->assertStatus(422);
        $this->actingAs($this->admin, 'admin')
            ->postJson("{$base}/items/{$item->id}/toppings", ['name' => 'Trân châu', 'price' => 5000])
            ->assertStatus(422);
        $this->actingAs($this->admin, 'admin')
            ->postJson("{$base}/items/{$item->id}/sizes", ['name' => 'L', 'price_delta' => 5000])
            ->assertStatus(422);

        $this->campaign->update(['status' => CampaignStatus::Active]);

        $this->actingAs($this->admin, 'admin')
            ->postJson("{$base}/items", ['name' => 'Trà vải', 'base_price' => 32000])
            ->assertCreated();
        $this->actingAs($this->admin, 'admin')
            ->postJson("{$base}/items/{$item->id}/toppings", ['name' => 'Trân châu', 'price' => 5000])
            ->assertCreated();
        $this->actingAs($this->admin, 'admin')
            ->postJson("{$base}/items/{$item->id}/sizes", ['name' => 'L', 'price_delta' => 5000])
            ->assertCreated();
    }

    /**
     * Test the info page links to the menu availability page, which lists items and saves toggles.
     */
    public function test_info_page_links_to_menu_page_and_menu_toggles_items(): void
    {
        $this->campaign->update(['status' => CampaignStatus::Active]);
        $item = $this->campaign->items()->create(['name' => 'Trà đào', 'normalized_name' => 'tra dao', 'base_price' => 30000]);
        $base = "/admin/{$this->room->slug}/campaigns/{$this->campaign->id}";
        $menuUrl = route('admin.campaigns.menu', [$this->room->slug, $this->campaign]);

        $this->actingAs($this->admin, 'admin')
            ->get("{$base}/info")
            ->assertOk()
            ->assertSee($menuUrl, false);

        $this->actingAs($this->admin, 'admin')
            ->get($menuUrl)
            ->assertOk()
            ->assertViewIs('admin.campaign-menu')
            ->assertSee('Trà đào')
            ->assertSee("toggleItemStatus({$item->id})", false);

        $this->actingAs($this->admin, 'admin')
            ->patchJson("{$base}/items-batch-status", ['items' => [['id' => $item->id, 'status' => 'inactive']]])
            ->assertOk()
            ->assertJsonPath('data.updated_count', 1);

        $this->assertSame('inactive', $item->fresh()->status->value);
    }

    /**
     * Test the legacy campaign URL redirects HTML requests to the info or orders page and no live view remains.
     */
    public function test_legacy_show_url_redirects_and_live_view_is_gone(): void
    {
        $base = "/admin/{$this->room->slug}/campaigns/{$this->campaign->id}";

        $this->actingAs($this->admin, 'admin')->get("{$base}?view=live")
            ->assertRedirect(route('admin.campaigns.info', [$this->room->slug, $this->campaign]));
        $this->actingAs($this->admin, 'admin')->get("{$base}?view=detail")
            ->assertRedirect(route('admin.campaigns.orders', [$this->room->slug, $this->campaign]));
        $this->actingAs($this->admin, 'admin')->getJson($base)->assertOk()->assertJsonPath('data.id', $this->campaign->id);

        $this->assertFalse(view()->exists('admin.campaign-live'));
    }

    /**
     * Test the menu page is read-only for a closed campaign.
     */
    public function test_menu_page_is_read_only_for_closed_campaign(): void
    {
        $item = $this->campaign->items()->create(['name' => 'Trà đào', 'normalized_name' => 'tra dao', 'base_price' => 30000]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.campaigns.menu', [$this->room->slug, $this->campaign]))
            ->assertOk()
            ->assertSee(__('admin.campaign_locked_cannot_modify'));

        $this->actingAs($this->admin, 'admin')
            ->patchJson("/admin/{$this->room->slug}/campaigns/{$this->campaign->id}/items-batch-status", ['items' => [['id' => $item->id, 'status' => 'inactive']]])
            ->assertStatus(422);
    }

    /**
     * Test the info page lists actions as a vertical stack without the removed buttons or dropdown.
     */
    public function test_info_page_actions_are_a_flat_list_without_removed_buttons(): void
    {
        $this->campaign->update(['status' => CampaignStatus::Active]);

        $this->actingAs($this->admin, 'admin')
            ->get("/admin/{$this->room->slug}/campaigns/{$this->campaign->id}/info")
            ->assertOk()
            ->assertDontSee('action-menu-dropdown', false)
            ->assertDontSee("openModal('adjust-fee-modal');", false)
            ->assertSee(__('admin.close_campaign_action'))
            ->assertSee(__('admin.edit_campaign'))
            ->assertSee(__('admin.confirm_duplicate'))
            ->assertSee(__('admin.cancel_campaign_action'))
            ->assertSee("openModal('confirm-duplicate-modal');", false)
            ->assertSee('id="confirm-duplicate-modal"', false);
    }

    /**
     * Test the info page no longer renders the campaign header block inside the sub-navigation bar.
     */
    public function test_info_page_sub_navigation_has_no_campaign_header(): void
    {
        $html = $this->actingAs($this->admin, 'admin')
            ->get("/admin/{$this->room->slug}/campaigns/{$this->campaign->id}/info")
            ->assertOk()
            ->getContent();

        $nav = (string) str($html)->after('TOP SUB-NAVIGATION BAR')->before('SECTION 1');
        $this->assertStringNotContainsString('<h1', $nav);
        $this->assertStringNotContainsString('#' . $this->campaign->code, $nav);
        $this->assertStringContainsString(route('admin.campaigns.orders', [$this->room->slug, $this->campaign]), $nav);
    }

    /**
     * Test the banner shows the description but neither room nor sponsor details.
     */
    public function test_info_banner_shows_description_without_room_and_sponsors(): void
    {
        $globalUser = GlobalUser::create(['name' => 'Nhà Tài Trợ', 'email' => 'sponsor@example.test']);
        $roomUser = RoomUser::create([
            'room_id' => $this->room->id,
            'global_user_id' => $globalUser->id,
            'display_name' => 'Anh Tài Trợ',
            'status' => 'active',
        ]);
        $this->campaign->update([
            'description' => 'Đặt trước 11h nhé',
            'sponsor_type' => Campaign::SPONSOR_TYPE_FULL,
            'sponsor_description' => 'Sếp bao cả phòng',
            'sponsor_allocations' => [['room_user_id' => $roomUser->id, 'percentage' => 100]],
        ]);

        $html = $this->actingAs($this->admin, 'admin')
            ->get("/admin/{$this->room->slug}/campaigns/{$this->campaign->id}/info")
            ->assertOk()
            ->assertSee('Đặt trước 11h nhé')
            ->getContent();

        $banner = (string) str($html)->after('SECTION 1')->before('SECTION 2');
        $this->assertStringNotContainsString(__('admin.room_label'), $banner);
        $this->assertStringNotContainsString($this->room->name, $banner);
        $this->assertStringNotContainsString(__('admin.sponsor_type_label'), $banner);
        $this->assertStringNotContainsString('Sếp bao cả phòng', $banner);
        $this->assertStringNotContainsString('Anh Tài Trợ', $banner);
    }

    /**
     * Test duplicating a campaign creates a draft copy with its own code.
     */
    public function test_duplicate_creates_draft_copy_with_new_code(): void
    {
        $this->campaign->items()->create(['name' => 'Trà đào', 'normalized_name' => 'tra dao', 'base_price' => 30000]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/admin/{$this->room->slug}/campaigns/{$this->campaign->id}/duplicate")
            ->assertCreated();

        $copy = Campaign::query()->findOrFail($response->json('data.id'));
        $this->assertSame(CampaignStatus::Draft, $copy->status);
        $this->assertNotSame($this->campaign->code, $copy->code);
        $this->assertSame(1, $copy->items()->count());
    }

    /**
     * Test the description is a muted italic line under the campaign name, without a card or title.
     */
    public function test_info_description_is_plain_italic_text_without_card_or_title(): void
    {
        $this->campaign->update(['description' => 'Đặt trước 11h nhé']);

        $html = $this->actingAs($this->admin, 'admin')
            ->get("/admin/{$this->room->slug}/campaigns/{$this->campaign->id}/info")
            ->assertOk()
            ->assertSee('Đặt trước 11h nhé')
            ->assertDontSee(__('admin.campaign_desc_label'))
            ->getContent();

        $this->assertMatchesRegularExpression('/<p class="[^"]*\bitalic\b[^"]*">\s*Đặt trước 11h nhé\s*<\/p>/u', $html);
    }

    /**
     * Test the sponsor policy (type and description) is shown even before any subsidy amount exists.
     */
    public function test_info_shows_sponsor_policy(): void
    {
        $this->campaign->update([
            'sponsor_type' => Campaign::SPONSOR_TYPE_FULL,
            'sponsor_description' => 'Sếp bao cả phòng',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get("/admin/{$this->room->slug}/campaigns/{$this->campaign->id}/info")
            ->assertOk()
            ->assertSee(__('admin.sponsor_type_label'))
            ->assertSee(__('admin.sponsor_type_full'))
            ->assertSee('Sếp bao cả phòng');
    }

    /**
     * Test extend buttons render only for live campaigns with a deadline.
     */
    public function test_info_shows_extend_buttons_only_for_live_campaigns(): void
    {
        $url = "/admin/{$this->room->slug}/campaigns/{$this->campaign->id}/info";

        $this->actingAs($this->admin, 'admin')->get($url)->assertOk()->assertDontSee('onclick="extendDeadline(', false);

        $this->campaign->update(['status' => CampaignStatus::Active, 'deadline' => now()->addHour()]);
        $html = $this->actingAs($this->admin, 'admin')->get($url)->assertOk()->getContent();
        foreach (Campaign::EXTEND_DEADLINE_MINUTES as $minutes) {
            $this->assertStringContainsString("extendDeadline({$minutes})", $html);
        }
    }

    /**
     * Test extending adds minutes to a future deadline, and to "now" once the deadline has passed.
     */
    public function test_extend_deadline_adds_minutes(): void
    {
        $url = "/admin/{$this->room->slug}/campaigns/{$this->campaign->id}/extend-deadline";
        $deadline = now()->addMinutes(30)->startOfSecond();
        $this->campaign->update(['status' => CampaignStatus::Active, 'deadline' => $deadline]);

        $this->actingAs($this->admin, 'admin')->postJson($url, ['minutes' => 20])->assertOk();
        $this->assertSame(
            $deadline->copy()->addMinutes(20)->timestamp,
            $this->campaign->fresh()->deadline->timestamp
        );
        $this->assertDatabaseHas('audit_logs', ['event' => 'campaign.deadline_extended', 'target_id' => $this->campaign->id]);

        $this->campaign->update(['deadline' => now()->subMinutes(15)]);
        $this->actingAs($this->admin, 'admin')->postJson($url, ['minutes' => 10])->assertOk();
        $this->assertEqualsWithDelta(
            now()->addMinutes(10)->timestamp,
            $this->campaign->fresh()->deadline->timestamp,
            5
        );
    }

    /**
     * Test extension rejects unsupported minutes, non-live campaigns and campaigns without a deadline.
     */
    public function test_extend_deadline_rejects_invalid_requests(): void
    {
        $url = "/admin/{$this->room->slug}/campaigns/{$this->campaign->id}/extend-deadline";

        // Closed campaign is locked.
        $this->actingAs($this->admin, 'admin')->postJson($url, ['minutes' => 10])->assertStatus(422);

        $this->campaign->update(['status' => CampaignStatus::Active, 'deadline' => now()->addHour()]);
        $this->actingAs($this->admin, 'admin')->postJson($url, ['minutes' => 15])->assertStatus(422)->assertJsonValidationErrors('minutes');
        $this->actingAs($this->admin, 'admin')->postJson($url, [])->assertStatus(422)->assertJsonValidationErrors('minutes');

        $this->campaign->update(['deadline' => null]);
        $this->actingAs($this->admin, 'admin')->postJson($url, ['minutes' => 10])
            ->assertStatus(422)->assertJsonValidationErrors('deadline');
    }

    /**
     * Test the orders page tooltips, email in the debt ledger, icon-only detail button and read-only debt modal.
     */
    public function test_orders_page_tooltips_ledger_email_and_readonly_debt_modal(): void
    {
        $globalUser = GlobalUser::create(['name' => 'Nguyễn Văn A', 'email' => 'nguyenvana@example.test']);
        $roomUser = RoomUser::create([
            'room_id' => $this->room->id,
            'global_user_id' => $globalUser->id,
            'user_code' => 'USR-777',
            'display_name' => 'Nguyễn Văn A',
            'status' => 'active',
        ]);
        Order::create([
            'room_id' => $this->room->id,
            'campaign_id' => $this->campaign->id,
            'room_user_id' => $roomUser->id,
            'code' => 'ORD-COPY-1',
            'subtotal' => 50000,
            'final_amount' => 50000,
            'status' => OrderStatus::Submitted,
        ]);
        \App\Models\Debt::create([
            'room_id' => $this->room->id,
            'campaign_id' => $this->campaign->id,
            'room_user_id' => $roomUser->id,
            'original_amount' => 50000,
            'remaining_amount' => 50000,
            'status' => \App\Enums\DebtStatus::Unpaid,
        ]);

        $html = $this->actingAs($this->admin, 'admin')
            ->get("/admin/{$this->room->slug}/campaigns/{$this->campaign->id}/orders")
            ->assertOk()
            ->getContent();

        // Shared fixed-position tooltip + tooltip hooks on copy / eye buttons.
        $this->assertStringContainsString('x-ref="tip"', $html);
        $this->assertStringContainsString('data-tip="'.__('admin.copy_order_code').'"', $html);
        $this->assertStringContainsString('data-tip="'.__('admin.view_order_detail').'"', $html);

        // Ledger: email replaces the user code, and the detail button is icon-only.
        $ledger = (string) str($html)->after('x-show="activeTab === \'ledger\'"')->before('TAB 5: DECLINED');
        $this->assertStringContainsString('nguyenvana@example.test', $ledger);
        $this->assertStringNotContainsString('USR-777', $ledger);
        $this->assertStringNotContainsString('<span>'.__('admin.view_order_detail').'</span>', $ledger);

        // Debt detail modal: read-only status and no footer.
        $modal = (string) str($html)->after('MODAL: DEBT DETAILS')->before('function campaignOrdersComponent');
        $this->assertStringContainsString('Status (read-only)', $modal);
        $this->assertStringNotContainsString('updateDebtStatus', $modal);
        $this->assertStringNotContainsString('<select', $modal);
        $this->assertStringNotContainsString('Modal Footer', $modal);

        $this->assertSame(substr_count($html, '<div'), substr_count($html, '</div>'));
    }

    /**
     * Test the department tab groups orders strictly by desk_location, merging spacing/case variants.
     */
    public function test_department_groups_use_normalized_desk_location(): void
    {
        $members = [
            ['A', 'a@example.test', 'Tầng 2', 30000],
            ['B', 'b@example.test', '  tầng   2 ', 20000],
            ['C', 'c@example.test', 'Kế toán', 10000],
            ['D', 'd@example.test', null, 40000],
        ];

        foreach ($members as [$name, $email, $desk, $amount]) {
            $globalUser = GlobalUser::create(['name' => $name, 'email' => $email, 'desk_location' => $desk]);
            $roomUser = RoomUser::create([
                'room_id' => $this->room->id,
                'global_user_id' => $globalUser->id,
                'display_name' => $name,
                'status' => 'active',
            ]);
            $order = Order::create([
                'room_id' => $this->room->id,
                'campaign_id' => $this->campaign->id,
                'room_user_id' => $roomUser->id,
                'subtotal' => $amount,
                'final_amount' => $amount,
                'status' => OrderStatus::Submitted,
            ]);
            $order->items()->create([
                'item_name' => 'Trà đào',
                'unit_price' => $amount,
                'quantity' => 1,
                'line_subtotal' => $amount,
            ]);
        }

        $data = app(\App\Services\Admin\AdminCampaignDetailService::class)
            ->getCampaignViewData($this->room, $this->campaign->fresh());
        $groups = $data['departmentGroups'];

        $this->assertCount(3, $groups);
        $this->assertSame(['Kế toán', 'Tầng 2', __('admin.unassigned_department')], $groups->pluck('department')->all());
        $this->assertSame(2, $groups[1]['members']->count());
        $this->assertSame(50000, $groups[1]['total_amount']);
        $this->assertTrue($groups[2]['is_unassigned']);

        $this->actingAs($this->admin, 'admin')
            ->get("/admin/{$this->room->slug}/campaigns/{$this->campaign->id}/orders")
            ->assertOk()
            ->assertSee('id="department-filter"', false)
            ->assertSee('<option value="1">Tầng 2</option>', false)
            ->assertDontSee('Modal Footer', false);
    }

    /**
     * Test the campaign editor marks the chosen previous menu with a check icon and formats the budget input.
     */
    public function test_edit_page_marks_selected_previous_menu_and_formats_budget(): void
    {
        $draft = Campaign::create([
            'room_id' => $this->room->id,
            'status' => CampaignStatus::Draft,
            'name' => 'Bản nháp',
            'restaurant' => 'Cafe',
        ]);
        $this->campaign->items()->create(['name' => 'Trà đào', 'normalized_name' => 'tra dao', 'base_price' => 30000]);

        $html = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.campaigns.edit', [$this->room->slug, $draft]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('selectedPreviousCampaignId === '.$this->campaign->id, $html);
        $this->assertStringContainsString('>check_circle</span>', $html);
        $this->assertStringContainsString('x-effect="$el.value = formatCurrencyDisplay(form.max_budget)"', $html);

        // Each category chip carries a red delete button that removes the category with its items.
        $this->assertStringContainsString('@click.stop="removeMenuCategory(category)"', $html);
        $this->assertStringContainsString('absolute -top-2 -right-2', $html);
        $this->assertStringContainsString('bg-red-600', $html);
        $this->assertStringNotContainsString('data-category-delete-confirm', $html);

        // Name and shop are single-line (truncated); the item count sits on the same row as the created time.
        $this->assertStringContainsString('class="truncate" title="Trà sữa buổi chiều"', $html);
        $this->assertStringContainsString('class="text-[11px] text-outline mt-1 truncate" title="Gong Cha"', $html);
        $this->assertMatchesRegularExpression('/history<\/span>\s*<span class="truncate">[^<]+<\/span>\s*<\/div>\s*<span class="[^"]*whitespace-nowrap[^"]*">1 /u', $html);
    }

    /**
     * Test the resend button renders only for live campaigns.
     */
    public function test_info_shows_resend_notification_button_only_for_live_campaigns(): void
    {
        $url = "/admin/{$this->room->slug}/campaigns/{$this->campaign->id}/info";

        $this->actingAs($this->admin, 'admin')->get($url)->assertOk()->assertDontSee('id="resend-notification-btn"', false);

        $this->campaign->update(['status' => CampaignStatus::Active, 'deadline' => now()->addHour()]);
        $this->actingAs($this->admin, 'admin')->get($url)->assertOk()->assertSee('id="resend-notification-btn"', false);
    }

    /**
     * Test resending re-dispatches the created event (web, channel and socket fan-out) for live campaigns only.
     */
    public function test_resend_notification_redispatches_created_event_for_live_campaigns(): void
    {
        Event::fake([CampaignCreated::class]);
        $url = "/admin/{$this->room->slug}/campaigns/{$this->campaign->id}/resend-notification";

        // Closed campaign cannot be announced again.
        $this->actingAs($this->admin, 'admin')->postJson($url)->assertStatus(422);
        Event::assertNotDispatched(CampaignCreated::class);

        $this->travel(61)->seconds(); // the rejected attempt above also counts toward the limit
        $this->campaign->update(['status' => CampaignStatus::Active]);
        $this->actingAs($this->admin, 'admin')->postJson($url)->assertOk()->assertJsonPath('message', __('admin.resend_notification_success'));

        Event::assertDispatched(CampaignCreated::class, fn (CampaignCreated $event): bool => $event->campaign->id === $this->campaign->id);
        $this->assertDatabaseHas('audit_logs', ['event' => 'campaign.notification_resent', 'target_id' => $this->campaign->id]);
    }

    /**
     * Test resending is rate limited per campaign with a localized JSON error.
     */
    public function test_resend_notification_is_rate_limited_per_campaign(): void
    {
        Event::fake([CampaignCreated::class]);
        $url = "/admin/{$this->room->slug}/campaigns/{$this->campaign->id}/resend-notification";
        $this->campaign->update(['status' => CampaignStatus::Active]);

        $this->actingAs($this->admin, 'admin')->postJson($url)->assertOk();
        $this->actingAs($this->admin, 'admin')->postJson($url)
            ->assertStatus(429)
            ->assertJsonStructure(['message']);

        Event::assertDispatchedTimes(CampaignCreated::class, 1);
    }

    /**
     * Test the aggregated and orders tabs show toppings and ice/sugar, and split drinks by customization.
     */
    public function test_orders_page_shows_toppings_and_customizations_in_item_columns(): void
    {
        $roomUser = RoomUser::create([
            'room_id' => $this->room->id,
            'global_user_id' => GlobalUser::create(['name' => 'Topping Fan', 'email' => 'fan@example.test'])->id,
            'display_name' => 'Topping Fan',
            'status' => 'active',
        ]);
        $order = Order::create([
            'room_id' => $this->room->id,
            'campaign_id' => $this->campaign->id,
            'room_user_id' => $roomUser->id,
            'subtotal' => 75000,
            'final_amount' => 75000,
            'status' => OrderStatus::Submitted,
        ]);
        $plain = $order->items()->create(['item_name' => 'Trà đào', 'size_name' => 'L', 'unit_price' => 30000, 'quantity' => 1, 'line_subtotal' => 30000]);
        $withPearl = $order->items()->create([
            'item_name' => 'Trà đào', 'size_name' => 'L', 'unit_price' => 30000, 'quantity' => 1,
            'ice_percent' => 50, 'sugar_percent' => 70, 'line_subtotal' => 35000,
        ]);
        $withPearl->toppings()->create(['topping_name' => 'Trân châu', 'unit_price' => 5000, 'quantity' => 1, 'subtotal' => 5000]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get("/admin/{$this->room->slug}/campaigns/{$this->campaign->id}/orders")
            ->assertOk()
            ->assertSee('+ Trân châu', false)
            ->assertSee('50% '.__('room.orders.ice'), false)
            ->assertSee('70% '.__('room.orders.sugar'), false);

        // Same drink with and without toppings is two lines in the aggregated list.
        $aggregated = $response->viewData('aggregatedItems');
        $this->assertCount(2, $aggregated);
        $this->assertSame([1, 1], $aggregated->pluck('quantity')->values()->all());
        $this->assertSame(['Trân châu'], $aggregated->firstWhere('toppings', collect(['Trân châu']))['toppings']->all());

        // The department tab lists the same customizations per department.
        $departments = $response->viewData('departmentGroups');
        $this->assertCount(1, $departments);
        $this->assertCount(2, $departments->first()['items']);
        $this->assertContains(['Trân châu'], $departments->first()['items']->pluck('toppings')->map->all()->all());
    }

    /**
     * Test the close-campaign modal pre-checks "auto create debt records".
     */
    public function test_close_modal_auto_debt_checkbox_is_checked_by_default(): void
    {
        $this->campaign->update(['status' => CampaignStatus::Active, 'deadline' => now()->addHour()]);

        $this->actingAs($this->admin, 'admin')
            ->get("/admin/{$this->room->slug}/campaigns/{$this->campaign->id}/info")
            ->assertOk()
            ->assertSee('id="close-campaign-allow-debt" checked', false);
    }
}

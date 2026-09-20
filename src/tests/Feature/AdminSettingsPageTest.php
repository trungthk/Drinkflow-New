<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Models\AdminAccount;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The redesigned settings page renders, keeps every element id the settings JS relies on, and has balanced markup.
     */
    public function test_settings_page_renders_with_all_script_hooks(): void
    {
        $admin = AdminAccount::create([
            'name' => 'Room Admin',
            'email' => 'settings-admin@example.test',
            'password' => Hash::make('secret'),
            'role' => AdminRole::Admin,
            'status' => 'active',
        ]);
        $room = Room::create(['name' => 'Settings Room', 'slug' => 'settings-room', 'status' => 'active']);
        $admin->rooms()->attach($room);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.page', $room->slug))
            ->assertOk()
            ->assertSee(__('admin.room_settings_subtitle'))
            ->assertSee(__('admin.room_access_title'))
            ->assertSee(__('admin.spending_debt_policy_title'))
            ->assertSee(__('admin.payment_accounts_title'));

        foreach ([
            'room-settings-form', 'save-campaign-settings-btn', 'set-template', 'set-max-budget',
            'set-debt-ceiling', 'set-autolock-debt', 'set-room-public', 'room-public-hint',
            'room-join-link', 'copy-room-join-link', 'accounts-container', 'payment-account-modal',
        ] as $id) {
            $response->assertSee('id="'.$id.'"', false);
        }

        // Copy button is icon-only with a tooltip; the add button reads "Add new"; delete confirm has an icon.
        $response->assertSee('data-tooltip="'.__('admin.copy_room_join_link').'"', false)
            ->assertDontSee('</span>'.__('admin.copy_room_join_link'), false)
            ->assertSee('<span>'.__('admin.add_new_btn').'</span>', false)
            ->assertSee('<span class="material-symbols-outlined text-[16px]">delete</span>', false);
        $this->assertSame('Thêm mới', __('admin.add_new_btn'));

        $html = $response->getContent();
        $this->assertSame(substr_count($html, '<section'), substr_count($html, '</section>'));
        $this->assertSame(substr_count($html, '<form'), substr_count($html, '</form>'));
        $this->assertSame(substr_count($html, '<div'), substr_count($html, '</div>'));
    }

    /**
     * Tables rendered inside modals must opt out of the page-level table minimum height.
     */
    public function test_modal_tables_do_not_inherit_table_min_height(): void
    {
        $css = (string) file_get_contents(resource_path('css/admin.css'));

        $this->assertStringContainsString('min-height: 200px;', $css);
        $this->assertMatchesRegularExpression(
            '/#admin-main-wrapper :is\(\[role="dialog"\], \[aria-modal="true"\], \.fixed\.inset-0\) table tbody\s*\{\s*min-height:\s*0;/',
            $css
        );
    }
}

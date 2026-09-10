<?php

namespace App\Actions\Superadmin;

use App\Enums\AdminRole;
use App\Models\AdminAccount;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ResetSystemAction
{
    public const CONFIRMATION_PHRASE = 'RESET DRINKFLOW';

    public function execute(AdminAccount $admin, string $password, string $phrase): array
    {
        if (! Hash::check($password, $admin->password)) {
            throw ValidationException::withMessages(['password' => 'Mật khẩu xác nhận không đúng.']);
        }
        if (! hash_equals(self::CONFIRMATION_PHRASE, $phrase)) {
            throw ValidationException::withMessages(['phrase' => 'Cụm từ xác nhận không đúng.']);
        }

        return DB::transaction(function (): array {
            foreach (['order_item_toppings', 'order_items', 'orders', 'debt_payments', 'debt_adjustments', 'debts', 'campaign_item_toppings', 'campaign_item_sizes', 'campaign_items', 'campaigns', 'payment_accounts', 'room_user_devices', 'room_users', 'crawler_previews', 'admin_rooms', 'room_settings', 'notification_channels', 'rooms', 'oauth_identities', 'global_users', 'system_notification_channels', 'versions', 'jobs', 'failed_jobs'] as $table) {
                if (DB::getSchemaBuilder()->hasTable($table)) DB::table($table)->delete();
            }
            AdminAccount::query()->where('role', AdminRole::Admin->value)->delete();
            $log = app(AuditService::class)->record('system.reset', 'system', 0, null, [], ['reset' => true]);
            return ['reset' => true, 'audit_id' => $log->id];
        });
    }
}

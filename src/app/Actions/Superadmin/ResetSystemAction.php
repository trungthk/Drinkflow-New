<?php

declare(strict_types=1);

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

    /**
     * Handle the execute operation.
     * @param AdminAccount $admin Parameter value.
     * @param string $password Parameter value.
     * @param string $phrase Parameter value.
     * @return array Result of the operation.
     */
    public function execute(AdminAccount $admin, string $password, string $phrase): array
    {
        if (! Hash::check($password, $admin->password)) {
            throw ValidationException::withMessages(['password' => __('superadmin.actions.reset_password_invalid')]);
        }
        if (! hash_equals(self::CONFIRMATION_PHRASE, $phrase)) {
            throw ValidationException::withMessages(['phrase' => __('superadmin.actions.reset_phrase_invalid')]);
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


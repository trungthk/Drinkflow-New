<?php

declare(strict_types=1);

namespace App\Actions\Room;

use App\Models\PaymentAccount;
use App\Models\Room;
use App\Models\RoomSetting;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class UpdateRoomSettingsAction
{
    public function __construct(protected AuditService $audit)
    {
    }

    /**
     * Update room core settings and room_settings key-values.
     */
    public function execute(Room $room, array $data, array $before): array
    {
        if (array_key_exists('default_payment_account_id', $data) && $data['default_payment_account_id'] !== null) {
            abort_unless(
                PaymentAccount::query()->whereKey($data['default_payment_account_id'])->where('room_id', $room->id)->where('status', 'active')->exists(),
                422,
                __('admin.invalid_payment_account')
            );
        }

        $dynamicKeys = [
            'default_sponsor' => 'string',
            'default_payment_account_id' => 'integer',
            'campaign_title_template' => 'string',
            'max_campaign_budget' => 'integer',
            'personal_debt_ceiling' => 'integer',
            'auto_lock_on_debt_limit' => 'boolean',
        ];

        $updated = DB::transaction(function () use ($room, $data, $dynamicKeys): Room {
            $room->update(collect($data)->only(['name', 'description', 'avatar_url', 'timezone', 'language'])->all());

            foreach ($dynamicKeys as $key => $type) {
                if (array_key_exists($key, $data)) {
                    $rawVal = $data[$key];
                    $val = $rawVal === null ? null : (string) $rawVal;
                    if ($type === 'boolean' && $rawVal !== null) {
                        $val = $rawVal ? '1' : '0';
                    }
                    RoomSetting::updateOrCreate(
                        ['room_id' => $room->id, 'key' => $key],
                        ['value' => $val, 'type' => $type, 'is_secret' => false]
                    );
                }
            }

            return $room->fresh();
        });

        $after = $this->payload($updated);
        $this->audit->record('room.settings_updated', 'room', $room->id, $room->id, $before, $after);

        return $after;
    }

    /**
     * Format payload.
     */
    public function payload(Room $room): array
    {
        $settings = $room->roomSettings()->get()->keyBy('key');

        $extra = [];
        foreach ($settings as $key => $setting) {
            $val = $setting->value;
            if ($setting->type === 'integer') {
                $val = $val !== null ? (int) $val : null;
            } elseif ($setting->type === 'boolean') {
                $val = filter_var($val, FILTER_VALIDATE_BOOLEAN);
            }
            $extra[$key] = $val;
        }

        return array_merge($room->only(['id', 'name', 'slug', 'description', 'avatar_url', 'timezone', 'language', 'status']), $extra, [
            'default_sponsor' => $settings->get('default_sponsor')?->value,
            'default_payment_account_id' => ($settings->get('default_payment_account_id')?->value !== null ? (int) $settings->get('default_payment_account_id')->value : null),
            'campaign_title_template' => $settings->get('campaign_title_template')?->value ?? ('['.$room->name.'] Trà chiều & Cafe {date}'),
            'max_campaign_budget' => $settings->get('max_campaign_budget')?->value !== null ? (int) $settings->get('max_campaign_budget')->value : 2000000,
            'personal_debt_ceiling' => $settings->get('personal_debt_ceiling')?->value !== null ? (int) $settings->get('personal_debt_ceiling')->value : 150000,
            'auto_lock_on_debt_limit' => $settings->get('auto_lock_on_debt_limit')?->value !== null ? filter_var($settings->get('auto_lock_on_debt_limit')->value, FILTER_VALIDATE_BOOLEAN) : true,
        ]);
    }
}

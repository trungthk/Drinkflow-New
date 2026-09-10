<?php

namespace App\Http\Controllers\Superadmin;

use App\Actions\Superadmin\ResetSystemAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\SystemResetRequest;
use App\Http\Requests\SystemSettingsRequest;
use App\Models\SystemSetting;
use App\Services\Audit\AuditService;
use App\Services\System\SystemSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemController extends Controller
{
    public function index(SystemSettingsService $settings): JsonResponse
    {
        $items = SystemSetting::query()->orderBy('key')->get()->map(fn (SystemSetting $setting) => [
            'key' => $setting->key, 'type' => $setting->type, 'is_secret' => $setting->is_secret,
            'value' => $setting->is_secret ? null : $settings->get($setting->key), 'configured' => $setting->value !== null,
        ]);
        return response()->json(['data' => ['settings' => $items, 'maintenance' => $this->maintenanceState($settings)]]);
    }

    public function settings(SystemSettingsRequest $request, SystemSettingsService $service, AuditService $audit): JsonResponse
    {
        $saved = [];
        foreach ($request->validated('settings') as $item) {
            $key = $item['key']; $before = SystemSetting::where('key', $key)->first();
            $setting = $service->set($key, $item['value'] ?? null, $item['type'] ?? 'string', (bool) ($item['is_secret'] ?? false), request()->user('admin')->id);
            $audit->record('system_setting.updated', 'system_setting', $setting->id, null, ['key' => $key, 'configured' => (bool) $before?->value], ['key' => $key, 'configured' => true]);
            $saved[] = ['key' => $key, 'type' => $setting->type, 'is_secret' => $setting->is_secret, 'configured' => true];
        }
        return response()->json(['data' => ['settings' => $saved, 'maintenance' => $this->maintenanceState($service)]]);
    }

    public function maintenance(Request $request, SystemSettingsService $service, AuditService $audit): JsonResponse
    {
        if ($request->isMethod('get')) return response()->json(['data' => $this->maintenanceState($service)]);
        $data = $request->validate(['enabled' => ['required', 'boolean'], 'starts_at' => ['nullable', 'date'], 'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at']]);
        $service->set('maintenance.enabled', $data['enabled'], 'boolean', false, $request->user('admin')->id);
        $service->set('maintenance.starts_at', $data['starts_at'] ?? null, 'string', false, $request->user('admin')->id);
        $service->set('maintenance.ends_at', $data['ends_at'] ?? null, 'string', false, $request->user('admin')->id);
        $audit->record('maintenance.updated', 'system_setting', 0, null, [], ['enabled' => $data['enabled'], 'starts_at' => $data['starts_at'] ?? null, 'ends_at' => $data['ends_at'] ?? null]);
        return response()->json(['data' => $this->maintenanceState($service)]);
    }

    public function reset(SystemResetRequest $request, ResetSystemAction $action): JsonResponse
    {
        return response()->json(['data' => $action->execute(request()->user('admin'), $request->validated('password'), $request->validated('phrase'))]);
    }

    private function maintenanceState(SystemSettingsService $service): array
    {
        $enabled = (bool) $service->get('maintenance.enabled', false); $starts = $service->get('maintenance.starts_at'); $ends = $service->get('maintenance.ends_at');
        $now = now(); $active = $enabled;
        if ($starts && $now->lt($starts)) $active = false;
        if ($ends && $now->gt($ends)) $active = false;
        return ['enabled' => $enabled, 'active' => $active, 'starts_at' => $starts, 'ends_at' => $ends];
    }
}

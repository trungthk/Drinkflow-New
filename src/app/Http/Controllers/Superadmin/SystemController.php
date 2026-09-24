<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Actions\Superadmin\ResetSystemAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\SendTestMailRequest;
use App\Http\Requests\SystemResetRequest;
use App\Http\Requests\SystemSettingsRequest;
use App\Http\Requests\UpdateMaintenanceRequest;
use App\Models\SystemSetting;
use App\Services\Audit\AuditService;
use App\Services\System\MailHealthService;
use App\Services\System\SystemHealthService;
use App\Services\System\SystemSettingsService;
use Illuminate\Http\JsonResponse;

class SystemController extends Controller
{
    /**
     * Handle the index operation.
     * @param SystemSettingsService $settings Parameter value.
     * @param SystemHealthService $health Infrastructure health snapshot service.
     * @return JsonResponse Result of the operation.
     */
    public function index(SystemSettingsService $settings, SystemHealthService $health): JsonResponse
    {
        $items = SystemSetting::query()->orderBy('key')->get()->map(fn(SystemSetting $setting) => [
            'key' => $setting->key,
            'type' => $setting->type,
            'is_secret' => $setting->is_secret,
            'value' => $setting->is_secret ? null : $settings->get($setting->key),
            'configured' => $setting->value !== null,
        ]);
        return response()->json(['data' => [
            'settings' => $items,
            'maintenance' => $this->maintenanceState($settings),
            'health' => $health->snapshot(),
        ]]);
    }

    /**
     * Send a real test email to the address typed in the modal to actively verify the mail transport.
     *
     * @param SendTestMailRequest $request Validated recipient email and optional message (active superadmin only).
     * @param MailHealthService $mail Mail health-check service.
     * @param AuditService $audit Audit trail, so every outbound test (and its recipient) is traceable.
     * @return JsonResponse Whether the mailer accepted the test message.
     */
    public function sendTestMail(SendTestMailRequest $request, MailHealthService $mail, AuditService $audit): JsonResponse
    {
        $recipient = (string) $request->validated('email');
        $sent = $mail->sendTest($recipient, $request->validated('message'));
        $audit->record('system.mail_test', 'system', 0, null, [], [], ['recipient' => $recipient, 'sent' => $sent]);

        return response()->json([
            'data' => ['sent' => $sent],
            'message' => $sent
                ? __('superadmin.system.mail_test_sent_to', ['email' => $recipient])
                : __('superadmin.system.mail_test_failed'),
        ], $sent ? 200 : 422);
    }

    /**
     * Handle the settings operation.
     * @param SystemSettingsRequest $request Parameter value.
     * @param SystemSettingsService $service Parameter value.
     * @param AuditService $audit Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function settings(SystemSettingsRequest $request, SystemSettingsService $service, AuditService $audit): JsonResponse
    {
        $saved = [];
        foreach ($request->validated('settings') as $item) {
            $key = $item['key'];
            $before = SystemSetting::where('key', $key)->first();
            $setting = $service->set($key, $item['value'] ?? null, $item['type'] ?? 'string', (bool) ($item['is_secret'] ?? false), request()->user('admin')->id);
            $audit->record('system_setting.updated', 'system_setting', $setting->id, null, ['key' => $key, 'configured' => (bool) $before?->value], ['key' => $key, 'configured' => true]);
            $saved[] = ['key' => $key, 'type' => $setting->type, 'is_secret' => $setting->is_secret, 'configured' => true];
        }
        return response()->json(['data' => ['settings' => $saved, 'maintenance' => $this->maintenanceState($service)]]);
    }

    /**
     * Handle the maintenance operation.
     * @param Request $request Parameter value.
     * @param SystemSettingsService $service Parameter value.
     * @param AuditService $audit Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function maintenance(UpdateMaintenanceRequest $request, SystemSettingsService $service, AuditService $audit): JsonResponse
    {
        if ($request->isMethod('get'))
            return response()->json(['data' => $this->maintenanceState($service)]);
        $data = $request->validated();
        $service->set('maintenance.enabled', $data['enabled'], 'boolean', false, $request->user('admin')->id);
        $service->set('maintenance.starts_at', $data['starts_at'] ?? null, 'string', false, $request->user('admin')->id);
        $service->set('maintenance.ends_at', $data['ends_at'] ?? null, 'string', false, $request->user('admin')->id);
        $audit->record('maintenance.updated', 'system_setting', 0, null, [], ['enabled' => $data['enabled'], 'starts_at' => $data['starts_at'] ?? null, 'ends_at' => $data['ends_at'] ?? null]);
        return response()->json(['data' => $this->maintenanceState($service)]);
    }

    /**
     * Handle the reset operation.
     * @param SystemResetRequest $request Parameter value.
     * @param ResetSystemAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function reset(SystemResetRequest $request, ResetSystemAction $action, AuditService $audit): JsonResponse
    {
        $result = $action->execute(request()->user('admin'), $request->validated('password'), $request->validated('phrase'));
        $audit->record('system.reset', 'system', 0, null, [], [], ['confirmation' => true]);
        return response()->json(['data' => $result]);
    }

    /**
     * Maintenance state for the JSON API (without the parsed Carbon instances).
     *
     * @param SystemSettingsService $service System settings store.
     * @return array{enabled: bool, active: bool, starts_at: ?string, ends_at: ?string}
     */
    private function maintenanceState(SystemSettingsService $service): array
    {
        $state = $service->maintenanceState();

        return ['enabled' => $state['enabled'], 'active' => $state['active'], 'starts_at' => $state['starts_at'], 'ends_at' => $state['ends_at']];
    }
}

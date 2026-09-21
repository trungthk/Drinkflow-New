<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SetFeedbackStatusRequest;
use App\Models\Feedback;
use App\Services\Audit\AuditService;
use Illuminate\Http\JsonResponse;

class FeedbackController extends Controller
{
    /**
     * Approve (active) or take down (inactive) a feedback entry.
     *
     * @param SetFeedbackStatusRequest $request Validated request carrying the new status.
     * @param Feedback $feedback Feedback being moderated.
     * @param AuditService $audit Audit trail service.
     * @return JsonResponse Updated feedback id and status.
     */
    public function status(SetFeedbackStatusRequest $request, Feedback $feedback, AuditService $audit): JsonResponse
    {
        $before = ['status' => $feedback->status->value];
        $feedback->update(['status' => $request->validated('status')]);
        $feedback->refresh();
        $audit->record('feedback.status_changed', 'feedback', $feedback->id, null, $before, ['status' => $feedback->status->value], ['rating' => $feedback->rating]);

        return response()->json(['data' => ['id' => $feedback->id, 'status' => $feedback->status->value]]);
    }
}

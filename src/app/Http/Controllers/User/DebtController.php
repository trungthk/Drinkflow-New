<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\Debt\UserRoomDebtService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DebtController extends Controller
{
    /**
     * Hiển thị danh sách công nợ trong phòng hoặc trả về JSON API (/rooms/{slug}/debts).
     *
     * @param  \Illuminate\Http\Request  $request  Đối tượng HTTP Request hiện tại
     * @param  \App\Services\Debt\UserRoomDebtService  $service  Service xử lý công nợ phòng
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Contracts\View\View  Phản hồi JSON hoặc Giao diện View
     */
    public function index(Request $request, UserRoomDebtService $service): JsonResponse|View
    {
        $room = $request->attributes->get('room');
        $roomUser = $request->attributes->get('room_user');
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        if ($request->expectsJson()) {
            $debts = $service->queryVisibleDebts($room, $roomUser)->with('campaign.paymentAccount')->latest()->paginate(20);
            return response()->json(['data' => $debts]);
        }

        $data = $service->getDebtViewData($room, $roomUser, $user);

        return view('user.debts', $data);
    }

    /**
     * Submit debt payment confirmation to await admin approval.
     *
     * @param Request $request Incoming HTTP request.
     * @param \App\Actions\Debt\ConfirmDebtPaymentAction $action Domain action.
     * @return JsonResponse Success response.
     */
    public function confirmPayment(Request $request, \App\Actions\Debt\ConfirmDebtPaymentAction $action): JsonResponse
    {
        $room = $request->attributes->get('room');
        $roomUser = $request->attributes->get('room_user');

        $debtId = $request->input('debt_id') ? (int) $request->input('debt_id') : null;
        $content = $request->input('transfer_content') ? (string) $request->input('transfer_content') : null;

        $updatedDebts = $action->execute($room, $roomUser, $debtId, $content);

        return response()->json([
            'success' => true,
            'message' => __('room.orders.payment_submitted_success', ['default' => 'Đã gửi yêu cầu xác nhận thanh toán. Quản trị viên sẽ kiểm tra và phê duyệt.']),
            'payment_status' => 'pending',
            'updated_count' => $updatedDebts->count(),
            'payment_confirmation' => [
                'requestedAt' => now()->format('d/m/Y H:i'),
                'content' => $content,
                'approvedBy' => null,
                'approvedAt' => null,
            ],
        ]);
    }
}

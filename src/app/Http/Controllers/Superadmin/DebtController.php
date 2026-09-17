<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Exports\DebtOverviewExport;
use App\Models\Debt;
use App\Services\Audit\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class DebtController extends Controller
{
    /**
     * Handle the index operation.
     * @param Request $request Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Debt::query()->with(['room:id,name,slug', 'campaign:id,name,room_id', 'roomUser.globalUser:id,name,email'])->latest();
        foreach (['room_id', 'campaign_id', 'room_user_id', 'status'] as $field)
            if ($request->filled($field))
                $query->where($field, $request->input($field));
        return response()->json([
            'data' => [
                'total_debt' => (int) Debt::when($request->filled('room_id'), fn($q) => $q->where('room_id', $request->integer('room_id')))->sum('remaining_amount'),
                'by_room' => Debt::select('room_id', DB::raw('SUM(remaining_amount) as total'), DB::raw('COUNT(*) as debt_count'))->groupBy('room_id')->with('room:id,name,slug')->get(),
                'by_campaign' => Debt::select('campaign_id', DB::raw('SUM(remaining_amount) as total'), DB::raw('COUNT(*) as debt_count'))->groupBy('campaign_id')->with('campaign:id,name')->get(),
                'items' => $query->paginate(20),
            ]
        ]);
    }

    /**
     * Handle the export operation.
     * @param Request $request Parameter value.
     * @return mixed Result of the operation.
     */
    public function export(Request $request, AuditService $audit)
    {
        $debts = Debt::query()->with(['room', 'campaign', 'roomUser.globalUser'])->when($request->filled('room_id'), fn($q) => $q->where('room_id', $request->integer('room_id')))->get();
        $audit->record('debt.exported', 'debt', 0, $request->integer('room_id') ?: null, [], [], ['count' => $debts->count()]);
        return Excel::download(new DebtOverviewExport($debts), 'drinkflow-debt-overview.xlsx');
    }
}

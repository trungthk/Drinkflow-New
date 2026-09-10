<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Services\Audit\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class QueueController extends Controller
{
    public function index(): JsonResponse { return response()->json(['data' => ['connection' => config('queue.default'), 'failed_jobs' => DB::table('failed_jobs')->latest('failed_at')->paginate(50)]]); }

    public function retry(int $failedJob, AuditService $audit): JsonResponse
    {
        $job = DB::table('failed_jobs')->where('id', $failedJob)->firstOrFail();
        Artisan::call('queue:retry', ['id' => [$job->uuid]]);
        $audit->record('queue.failed_job_retried', 'failed_job', $job->id, null, [], ['uuid' => $job->uuid]);
        return response()->json(['data' => ['retried' => true, 'uuid' => $job->uuid]]);
    }

    public function forget(int $failedJob, AuditService $audit): JsonResponse
    {
        $job = DB::table('failed_jobs')->where('id', $failedJob)->firstOrFail();
        Artisan::call('queue:forget', ['id' => $job->uuid]);
        $audit->record('queue.failed_job_deleted', 'failed_job', $job->id, null, [], ['uuid' => $job->uuid]);
        return response()->json(['data' => ['deleted' => true, 'uuid' => $job->uuid]]);
    }
}

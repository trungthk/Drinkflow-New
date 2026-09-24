<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\VersionRequest;
use App\Models\Version;
use App\Services\Audit\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VersionController extends Controller
{
    /**
     * Handle the index operation.
     * @return JsonResponse Result of the operation.
     */
    public function index(): JsonResponse
    {
        return response()->json(['data' => Version::query()->latest('release_date')->paginate(\App\Constants\Pagination::ADMIN_PER_PAGE)]);
    }
    /**
     * Handle the store operation.
     * @param VersionRequest $request Parameter value.
     * @param AuditService $audit Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function store(VersionRequest $request, AuditService $audit): JsonResponse
    {
        $version = Version::create([...$request->validated(), 'created_by_admin_id' => request()->user('admin')->id]);
        $audit->record('version.created', 'version', $version->id, null, [], $version->only(['version', 'title', 'release_date', 'force_refresh', 'important']));
        return response()->json(['data' => $version], 201);
    }
    /**
     * Handle the update operation.
     * @param VersionRequest $request Parameter value.
     * @param Version $version Parameter value.
     * @param AuditService $audit Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function update(VersionRequest $request, Version $version, AuditService $audit): JsonResponse
    {
        $before = $version->only(['version', 'title', 'changelog', 'release_date', 'force_refresh', 'important']);
        $version->update($request->validated());
        $audit->record('version.updated', 'version', $version->id, null, $before, $version->fresh()->only(array_keys($before)));
        return response()->json(['data' => $version->fresh()]);
    }
    /**
     * Render changelog Markdown to HTML for the release editor's preview tab.
     *
     * Raw HTML in the source is escaped and unsafe links (javascript:, data:, …) are dropped,
     * so the returned markup is safe to inject into the superadmin page.
     *
     * @param Request $request Body: changelog (Markdown source).
     * @return JsonResponse Rendered HTML under data.html.
     */
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate(['changelog' => ['nullable', 'string', 'max:'.VersionRequest::CHANGELOG_MAX_LENGTH]]);

        return response()->json(['data' => ['html' => Version::renderMarkdown((string) ($validated['changelog'] ?? ''))]]);
    }
    /**
     * Handle the destroy operation.
     * @param Version $version Parameter value.
     * @param AuditService $audit Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function destroy(Version $version, AuditService $audit): JsonResponse
    {
        $version->delete();
        $audit->record('version.deleted', 'version', $version->id);
        return response()->json(['data' => ['deleted' => true]]);
    }
}

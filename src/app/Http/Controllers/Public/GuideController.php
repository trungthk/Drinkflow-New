<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Guide\UserGuideService;
use Illuminate\Contracts\View\View;

/**
 * Trang hướng dẫn công khai: hai bộ tài liệu song song cho khách truy cập.
 *
 * `/guides` phục vụ Client (thành viên đặt món) và `/guides/agent` phục vụ Đại lý quản trị phòng ban.
 * Cả hai dùng chung một controller và một cặp view, chỉ khác bộ tài liệu (audience) và URL gốc.
 */
class GuideController extends Controller
{
    /**
     * Render the public list of guide articles.
     *
     * @param UserGuideService $service Markdown guide reader.
     * @param string $audience Guide set: client or agent.
     * @param string $basePath Public path of the list page (root for local links).
     * @return View Public guides index view.
     */
    public function index(UserGuideService $service, string $audience = UserGuideService::AUDIENCE_CLIENT, string $basePath = '/guides'): View
    {
        $indexUrl = url($basePath);
        $articles = array_map(
            static fn (array $article): array => $article + ['url' => $indexUrl.'/'.$article['slug']],
            $service->list($audience)
        );

        return view('public.guides', [
            'articles' => $articles,
            'indexUrl' => $indexUrl,
            'audience' => $audience,
            'clientUrl' => url('/guides'),
            'agentUrl' => url('/guides/agent'),
        ]);
    }

    /**
     * Render one public guide article.
     *
     * @param string $slug Article slug (markdown file name without extension).
     * @param UserGuideService $service Markdown guide reader.
     * @param string $audience Guide set: client or agent.
     * @param string $basePath Public path of the list page (root for local links).
     * @return View Public guide article view.
     */
    public function show(string $slug, UserGuideService $service, string $audience = UserGuideService::AUDIENCE_CLIENT, string $basePath = '/guides'): View
    {
        $indexUrl = url($basePath);
        $article = $service->findPublic($slug, $indexUrl, $audience);
        abort_if($article === null, 404);

        return view('public.guide-detail', [
            'article' => $article,
            'indexUrl' => $indexUrl,
            'audience' => $audience,
            'clientUrl' => url('/guides'),
            'agentUrl' => url('/guides/agent'),
        ]);
    }

    /**
     * Hướng dẫn dành cho Đại lý (Agents) tại /guides/agent.
     *
     * @param UserGuideService $service Markdown guide reader.
     * @return View Public guides index view of the agent set.
     */
    public function agentIndex(UserGuideService $service): View
    {
        return $this->index($service, UserGuideService::AUDIENCE_AGENT, '/guides/agent');
    }

    /**
     * Một bài hướng dẫn dành cho Đại lý tại /guides/agent/{slug}.
     *
     * @param string $slug Article slug (markdown file name without extension).
     * @param UserGuideService $service Markdown guide reader.
     * @return View Public guide article view of the agent set.
     */
    public function agentShow(string $slug, UserGuideService $service): View
    {
        return $this->show($slug, $service, UserGuideService::AUDIENCE_AGENT, '/guides/agent');
    }
}

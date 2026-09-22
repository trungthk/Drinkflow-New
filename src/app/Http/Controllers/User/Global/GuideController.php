<?php

declare(strict_types=1);

namespace App\Http\Controllers\User\Global;

use App\Http\Controllers\Controller;
use App\Models\GlobalUser;
use App\Services\Guide\UserGuideService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class GuideController extends Controller
{
    /**
     * Hiển thị danh sách bài hướng dẫn sử dụng DrinkFlow, phạm vi toàn hệ thống (/me/guides).
     *
     * @param  \Illuminate\Http\Request  $request  Đối tượng HTTP Request hiện tại
     * @param  \App\Services\Guide\UserGuideService  $service  Service đọc và render bài hướng dẫn từ Markdown
     * @return \Illuminate\Contracts\View\View  Giao diện danh sách bài hướng dẫn
     */
    public function index(Request $request, UserGuideService $service): View
    {
        /** @var GlobalUser|null $user */
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        $indexUrl = route('user.me.guides');
        $articles = array_map(
            static fn (array $article): array => $article + [
                'url' => $indexUrl . '/' . $article['slug'],
            ],
            $service->list()
        );

        $breadcrumbs = [
            ['label' => __('global.rooms.breadcrumb_personal'), 'url' => route('user.me.dashboard')],
            ['label' => __('guides.page_title'), 'url' => $indexUrl],
        ];

        return view('user.global.guides', [
            'user' => $user,
            'articles' => $articles,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    /**
     * Hiển thị nội dung chi tiết một bài hướng dẫn, phạm vi toàn hệ thống (/me/guides/{slug}).
     *
     * @param  \Illuminate\Http\Request  $request  Đối tượng HTTP Request hiện tại
     * @param  string  $slug  Định danh bài viết trong URL (tên file Markdown không có phần mở rộng)
     * @param  \App\Services\Guide\UserGuideService  $service  Service đọc và render bài hướng dẫn từ Markdown
     * @return \Illuminate\Contracts\View\View  Giao diện chi tiết bài hướng dẫn
     */
    public function show(Request $request, string $slug, UserGuideService $service): View
    {
        /** @var GlobalUser|null $user */
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        $indexUrl = route('user.me.guides');
        $article = $service->find($slug, $indexUrl);

        abort_if($article === null, 404);

        $breadcrumbs = [
            ['label' => __('global.rooms.breadcrumb_personal'), 'url' => route('user.me.dashboard')],
            ['label' => __('guides.page_title'), 'url' => $indexUrl],
            ['label' => $article['title'], 'url' => $indexUrl . '/' . $slug],
        ];

        return view('user.global.guide-detail', [
            'user' => $user,
            'article' => $article,
            'indexUrl' => $indexUrl,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }
}

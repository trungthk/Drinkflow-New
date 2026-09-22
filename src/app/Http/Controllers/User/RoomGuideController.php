<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Services\Guide\UserGuideService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class RoomGuideController extends Controller
{
    /**
     * Hiển thị danh sách bài hướng dẫn sử dụng DrinkFlow trong phạm vi một Room (/rooms/{room}/guides).
     *
     * @param  \Illuminate\Http\Request  $request  Đối tượng HTTP Request hiện tại
     * @param  \App\Models\Room  $room  Đối tượng phòng hiện tại
     * @param  \App\Services\Guide\UserGuideService  $service  Service đọc và render bài hướng dẫn từ Markdown
     * @return \Illuminate\Contracts\View\View  Giao diện danh sách bài hướng dẫn
     */
    public function index(Request $request, Room $room, UserGuideService $service): View
    {
        $room = $request->attributes->get('room') ?? $room;

        $indexUrl = route('user.rooms.guides', $room);
        $articles = array_map(
            static fn (array $article): array => $article + [
                'url' => $indexUrl . '/' . $article['slug'],
            ],
            $service->list()
        );

        return view('user.guides', [
            'room' => $room,
            'articles' => $articles,
        ]);
    }

    /**
     * Hiển thị nội dung chi tiết một bài hướng dẫn trong phạm vi một Room (/rooms/{room}/guides/{slug}).
     *
     * @param  \Illuminate\Http\Request  $request  Đối tượng HTTP Request hiện tại
     * @param  \App\Models\Room  $room  Đối tượng phòng hiện tại
     * @param  string  $slug  Định danh bài viết trong URL (tên file Markdown không có phần mở rộng)
     * @param  \App\Services\Guide\UserGuideService  $service  Service đọc và render bài hướng dẫn từ Markdown
     * @return \Illuminate\Contracts\View\View  Giao diện chi tiết bài hướng dẫn
     */
    public function show(Request $request, Room $room, string $slug, UserGuideService $service): View
    {
        $room = $request->attributes->get('room') ?? $room;

        $indexUrl = route('user.rooms.guides', $room);
        $article = $service->find($slug, $indexUrl);

        abort_if($article === null, 404);

        return view('user.guide-detail', [
            'room' => $room,
            'article' => $article,
            'indexUrl' => $indexUrl,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Guide\UserGuideService;
use Illuminate\Contracts\View\View;

class GuideController extends Controller
{
    /**
     * Render the public list of user guide articles.
     *
     * @param UserGuideService $service Markdown guide reader.
     * @return View Public guides index view.
     */
    public function index(UserGuideService $service): View
    {
        $indexUrl = url('/guides');
        $articles = array_map(
            static fn (array $article): array => $article + ['url' => $indexUrl.'/'.$article['slug']],
            $service->list()
        );

        return view('public.guides', ['articles' => $articles, 'indexUrl' => $indexUrl]);
    }

    /**
     * Render one public guide article.
     *
     * @param string $slug Article slug (markdown file name without extension).
     * @param UserGuideService $service Markdown guide reader.
     * @return View Public guide article view.
     */
    public function show(string $slug, UserGuideService $service): View
    {
        $indexUrl = url('/guides');
        $article = $service->findPublic($slug, $indexUrl);
        abort_if($article === null, 404);

        return view('public.guide-detail', ['article' => $article, 'indexUrl' => $indexUrl]);
    }
}

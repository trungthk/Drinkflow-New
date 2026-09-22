<?php

declare(strict_types=1);

namespace App\Services\Guide;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use League\CommonMark\GithubFlavoredMarkdownConverter;

class UserGuideService
{
    /**
     * Thư mục (tương đối trong public/) chứa các file Markdown hướng dẫn sử dụng cho User.
     */
    private const GUIDES_DIR = 'guildes/user';

    /**
     * Slug không được xem là một bài hướng dẫn thật sự (file mục lục nội bộ).
     */
    private const INDEX_SLUG = 'README';

    /**
     * Lấy danh sách bài hướng dẫn (đã sắp xếp theo số thứ tự trong tên file) để hiển thị dạng lưới.
     *
     * @return array<int, array{slug: string, number: int, title: string, excerpt: string}>
     */
    public function list(): array
    {
        $directory = public_path(self::GUIDES_DIR);

        if (! File::isDirectory($directory)) {
            return [];
        }

        $articles = [];

        foreach (File::files($directory) as $file) {
            if (strtolower($file->getExtension()) !== 'md') {
                continue;
            }

            $slug = $file->getFilenameWithoutExtension();
            if (strcasecmp($slug, self::INDEX_SLUG) === 0) {
                continue;
            }

            $raw = File::get($file->getPathname());

            $articles[] = [
                'slug' => $slug,
                'number' => $this->extractNumber($slug),
                'title' => $this->extractTitle($raw) ?: $slug,
                'excerpt' => $this->extractExcerpt($raw),
            ];
        }

        usort($articles, static fn (array $a, array $b): int => $a['number'] <=> $b['number'] ?: $a['slug'] <=> $b['slug']);

        return $articles;
    }

    /**
     * Đọc và render nội dung một bài hướng dẫn thành HTML an toàn để hiển thị.
     *
     * Đường dẫn trang chi tiết của mỗi bài luôn có dạng "{$indexUrl}/{slug}" (route index + route show
     * dùng chung 1 prefix), nên chỉ cần truyền URL trang danh sách là đủ để viết lại mọi liên kết nội bộ.
     *
     * @param string $slug Định danh file (tên file không có phần mở rộng .md), lấy từ URL.
     * @param string $indexUrl URL của trang danh sách hướng dẫn (route index, dùng làm gốc cho link chi tiết và link README.md).
     * @return array{slug: string, title: string, html: string}|null Dữ liệu bài viết, hoặc null nếu không tìm thấy/slug không hợp lệ.
     */
    public function find(string $slug, string $indexUrl): ?array
    {
        if (! preg_match('/^[a-z0-9\-]+$/', $slug) || strcasecmp($slug, self::INDEX_SLUG) === 0) {
            return null;
        }

        $directory = public_path(self::GUIDES_DIR);
        $path = realpath($directory . DIRECTORY_SEPARATOR . $slug . '.md');
        $baseDirReal = realpath($directory);

        if ($path === false || $baseDirReal === false || ! Str::startsWith($path, $baseDirReal)) {
            return null;
        }

        $raw = File::get($path);

        return [
            'slug' => $slug,
            'title' => $this->extractTitle($raw) ?: $slug,
            'html' => $this->renderHtml($raw, $indexUrl),
        ];
    }

    /**
     * Trích số thứ tự đứng đầu tên file (ví dụ "12-dat-ho..." -> 12) để sắp xếp danh sách.
     *
     * @param string $slug Tên file không có phần mở rộng.
     * @return int Số thứ tự, hoặc PHP_INT_MAX nếu tên file không có số thứ tự.
     */
    private function extractNumber(string $slug): int
    {
        return preg_match('/^(\d+)-/', $slug, $m) ? (int) $m[1] : PHP_INT_MAX;
    }

    /**
     * Lấy tiêu đề bài viết từ dòng heading H1 đầu tiên trong Markdown.
     *
     * @param string $raw Nội dung Markdown gốc.
     * @return string Tiêu đề (rỗng nếu không tìm thấy).
     */
    private function extractTitle(string $raw): string
    {
        return preg_match('/^#\s+(.+)$/m', $raw, $m) ? trim($m[1]) : '';
    }

    /**
     * Trích đoạn mô tả ngắn (đoạn văn bản thuần đầu tiên, bỏ qua heading/nav/ảnh/list) để hiển thị trên thẻ danh sách.
     *
     * @param string $raw Nội dung Markdown gốc.
     * @return string Đoạn mô tả ngắn đã loại bỏ cú pháp Markdown, giới hạn độ dài.
     */
    private function extractExcerpt(string $raw): string
    {
        $withoutNav = $this->stripNavBlock($raw);
        $lines = preg_split('/\r?\n/', $withoutNav) ?: [];

        $paragraph = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                if ($paragraph !== []) {
                    break;
                }
                continue;
            }

            if (preg_match('/^(#{1,6}\s|!\[|<|>|-\s|\d+\.\s|\|)/', $trimmed)) {
                continue;
            }

            $paragraph[] = $trimmed;
        }

        $text = $this->stripInlineMarkdown(implode(' ', $paragraph));

        return Str::limit($text, 160);
    }

    /**
     * Loại bỏ khối điều hướng "<details>...</details>" ở đầu file (chỉ dùng để đọc trên GitHub, không cần hiển thị lại).
     *
     * @param string $raw Nội dung Markdown gốc.
     * @return string Nội dung sau khi loại bỏ khối nav.
     */
    private function stripNavBlock(string $raw): string
    {
        return preg_replace('/<details[^>]*>.*?<\/details>\s*/is', '', $raw, 1) ?? $raw;
    }

    /**
     * Loại bỏ cú pháp Markdown cơ bản (đậm/nghiêng/link/code) khỏi một đoạn văn bản, chỉ giữ lại nội dung hiển thị.
     *
     * @param string $text Đoạn văn bản có thể chứa cú pháp Markdown.
     * @return string Văn bản thuần.
     */
    private function stripInlineMarkdown(string $text): string
    {
        $text = preg_replace('/!\[[^\]]*\]\([^)]*\)/', '', $text) ?? $text;
        $text = preg_replace('/\[([^\]]+)\]\([^)]*\)/', '$1', $text) ?? $text;
        $text = preg_replace('/[*_`]{1,3}/', '', $text) ?? $text;

        return trim($text);
    }

    /**
     * Render Markdown thành HTML: loại nav nội bộ, viết lại đường dẫn ảnh/liên kết nội bộ, rồi convert qua CommonMark (GFM).
     *
     * @param string $raw Nội dung Markdown gốc.
     * @param string $indexUrl URL trang danh sách (gốc cho liên kết README.md và liên kết sang bài khác).
     * @return string HTML đã render, an toàn để hiển thị trực tiếp.
     */
    private function renderHtml(string $raw, string $indexUrl): string
    {
        $content = $this->stripNavBlock($raw);

        // Ảnh tương đối "images/xxx.png" -> đường dẫn tuyệt đối tới thư mục public/guildes/user/images.
        $content = str_replace('](images/', '](/' . self::GUIDES_DIR . '/images/', $content);

        // Liên kết nội bộ tới các bài khác ("12-slug.md" hoặc "12-slug.md#anchor") -> route trang chi tiết tương ứng.
        $content = preg_replace_callback(
            '/\]\((?:\.\/)?([a-z0-9\-]+)\.md(#[^)\s]*)?\)/i',
            function (array $m) use ($indexUrl): string {
                if (strcasecmp($m[1], self::INDEX_SLUG) === 0) {
                    return '](' . $indexUrl . ')';
                }

                return '](' . rtrim($indexUrl, '/') . '/' . $m[1] . ($m[2] ?? '') . ')';
            },
            $content
        ) ?? $content;

        $converter = new GithubFlavoredMarkdownConverter([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]);

        return (string) $converter->convert($content);
    }
}

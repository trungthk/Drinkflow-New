<?php

declare(strict_types=1);

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;
use InvalidArgumentException;
use RuntimeException;

class ImageUploadService
{
    protected ImageManager $imageManager;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new GdDriver());
    }

    /**
     * Tối ưu hóa hình ảnh (resize/scale down, nén) và chuyển đổi sang định dạng WebP trước khi lưu vào public storage.
     *
     * @param  \Illuminate\Http\UploadedFile|string  $file  File upload hoặc đường dẫn tệp ảnh nguồn
     * @param  string  $directory  Thư mục lưu trữ con trong public disk (ví dụ: 'uploads/avatars')
     * @param  int  $maxWidth  Chiều rộng tối đa cho phép (mặc định 1200px)
     * @param  int  $maxHeight  Chiều cao tối đa cho phép (mặc định 1200px)
     * @param  int  $quality  Chất lượng nén WebP từ 1 - 100 (mặc định 82)
     * @return array{path: string, url: string, filename: string, width: int, height: int, size: int}  Thông tin chi tiết của ảnh đã tối ưu
     *
     * @throws \InvalidArgumentException  Nếu file không hợp lệ hoặc không phải là hình ảnh
     * @throws \RuntimeException  Nếu quá trình xử lý hoặc ghi file vào storage thất bại
     */
    public function optimizeAndStore(
        UploadedFile|string $file,
        string $directory = 'uploads',
        int $maxWidth = 1200,
        int $maxHeight = 1200,
        int $quality = 82
    ): array {
        if ($file instanceof UploadedFile && !$file->isValid()) {
            throw new InvalidArgumentException(__('validation.image', ['attribute' => 'file']));
        }

        try {
            $source = $file instanceof UploadedFile ? $file->getRealPath() : $file;
            $image = $this->imageManager->read($source);
        } catch (\Throwable $e) {
            throw new InvalidArgumentException('Không thể đọc file hình ảnh: ' . $e->getMessage(), 0, $e);
        }

        // Tự động scale down giữ nguyên tỷ lệ nếu kích thước vượt ngưỡng
        $image->scaleDown(width: $maxWidth, height: $maxHeight);

        // Chuyển đổi toàn bộ sang định dạng WebP với mức nén tối ưu
        $encoded = $image->toWebp(quality: $quality);
        $encodedBinary = (string) $encoded;

        // Sinh tên file ngẫu nhiên bảo mật với đuôi .webp
        $filename = Str::uuid()->toString() . '.webp';
        $directory = trim($directory, '/\\');
        $storagePath = $directory . '/' . $filename;

        $stored = Storage::disk('public')->put($storagePath, $encodedBinary);

        if (!$stored) {
            throw new RuntimeException('Lỗi lưu trữ tệp tin vào public storage.');
        }

        return [
            'path' => $storagePath,
            'url' => Storage::url($storagePath),
            'filename' => $filename,
            'width' => $image->width(),
            'height' => $image->height(),
            'size' => strlen($encodedBinary),
        ];
    }

    /**
     * Tối ưu và lưu ảnh đại diện (avatar) của người dùng sang WebP.
     *
     * @param  \Illuminate\Http\UploadedFile  $file  File ảnh avatar upload
     * @return string  URL công khai của ảnh avatar đã tối ưu
     */
    public function uploadAvatar(UploadedFile $file): string
    {
        $result = $this->optimizeAndStore(
            file: $file,
            directory: 'uploads/avatars',
            maxWidth: 400,
            maxHeight: 400,
            quality: 85
        );

        return $result['url'];
    }

    /**
     * Tối ưu và lưu ảnh đại diện hoặc ảnh bìa của Phòng (Room) sang WebP.
     *
     * @param  \Illuminate\Http\UploadedFile  $file  File ảnh phòng upload
     * @return string  URL công khai của ảnh phòng đã tối ưu
     */
    public function uploadRoomAvatar(UploadedFile $file): string
    {
        $result = $this->optimizeAndStore(
            file: $file,
            directory: 'uploads/rooms',
            maxWidth: 600,
            maxHeight: 600,
            quality: 85
        );

        return $result['url'];
    }

    /**
     * Tối ưu và lưu ảnh món đồ uống/thức ăn của chiến dịch sang WebP.
     *
     * @param  \Illuminate\Http\UploadedFile  $file  File ảnh món upload
     * @return string  URL công khai của ảnh món đã tối ưu
     */
    public function uploadCampaignImage(UploadedFile $file): string
    {
        $result = $this->optimizeAndStore(
            file: $file,
            directory: 'uploads/campaigns',
            maxWidth: 800,
            maxHeight: 800,
            quality: 82
        );

        return $result['url'];
    }

    /**
     * Xóa tệp ảnh khỏi public storage nếu tồn tại.
     *
     * @param  string|null  $urlOrPath  URL hoặc đường dẫn tương đối của file
     * @return bool  True nếu đã xóa thành công, False nếu không tồn tại hoặc lỗi
     */
    public function deleteFile(?string $urlOrPath): bool
    {
        if (empty($urlOrPath)) {
            return false;
        }

        // Bóc tách đường dẫn tương đối nếu truyền vào full URL
        $path = Str::after($urlOrPath, '/storage/');
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }

        return false;
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Common;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class BankService
{
    public const CACHE_KEY = 'drinkflow_supported_banks_list';

    /**
     * Lấy toàn bộ danh sách ngân hàng Việt Nam từ tệp banks.json kèm cache.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>  Danh sách các ngân hàng đã được chuẩn hóa
     */
    public function getAllBanks(): Collection
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $filePath = public_path('files/banks.json');
            if (!File::exists($filePath)) {
                return collect($this->getDefaultFallbackBanks());
            }

            try {
                $jsonContent = File::get($filePath);
                $decoded = json_decode($jsonContent, true);

                if (!is_array($decoded) || empty($decoded)) {
                    return collect($this->getDefaultFallbackBanks());
                }

                return collect($decoded)->map(function (array $bank) {
                    return [
                        'id' => $bank['id'] ?? null,
                        'name' => (string) ($bank['name'] ?? ''),
                        'code' => strtoupper((string) ($bank['code'] ?? '')),
                        'bin' => (string) ($bank['bin'] ?? ''),
                        'short_name' => (string) ($bank['short_name'] ?? $bank['shortName'] ?? $bank['code'] ?? ''),
                        'logo' => (string) ($bank['logo'] ?? ''),
                        'swift_code' => (string) ($bank['swift_code'] ?? ''),
                        'transfer_supported' => (bool) ($bank['transferSupported'] ?? $bank['isTransfer'] ?? true),
                        'lookup_supported' => (bool) ($bank['lookupSupported'] ?? true),
                    ];
                });
            } catch (\Throwable) {
                return collect($this->getDefaultFallbackBanks());
            }
        });
    }

    /**
     * Tra cứu thông tin chi tiết một ngân hàng theo mã viết tắt (code).
     *
     * @param  string  $code  Mã ngân hàng (ví dụ: 'VCB', 'MB', 'TCB')
     * @return array<string, mixed>|null  Thông tin ngân hàng hoặc null nếu không tìm thấy
     */
    public function getBankByCode(string $code): ?array
    {
        $normalizedCode = strtoupper(trim($code));

        return $this->getAllBanks()->first(function (array $bank) use ($normalizedCode) {
            return strtoupper($bank['code']) === $normalizedCode;
        });
    }

    /**
     * Lấy danh sách ngân hàng dạng mảng key-value phục vụ dropdown/select HTML.
     *
     * @return array<string, string>  Mảng [code => 'short_name - name']
     */
    public function getBankSelectOptions(): array
    {
        $options = [];
        foreach ($this->getAllBanks() as $bank) {
            $code = $bank['code'];
            $shortName = $bank['short_name'];
            $name = $bank['name'];
            $options[$code] = "{$code} - {$shortName} ({$name})";
        }

        return $options;
    }

    /**
     * Lấy đường dẫn ảnh logo chính thức của ngân hàng theo mã.
     *
     * @param  string  $code  Mã ngân hàng
     * @return string|null  URL ảnh logo ngân hàng
     */
    public function getBankLogo(string $code): ?string
    {
        $bank = $this->getBankByCode($code);

        return $bank['logo'] ?? null;
    }

    /**
     * Xóa cache danh sách ngân hàng để tải lại tệp banks.json.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Danh sách ngân hàng phổ biến dự phòng khi thiếu tệp JSON.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getDefaultFallbackBanks(): array
    {
        return [
            ['id' => 43, 'name' => 'Ngân hàng TMCP Ngoại Thương Việt Nam', 'code' => 'VCB', 'bin' => '970436', 'short_name' => 'Vietcombank', 'logo' => 'https://cdn.vietqr.io/img/VCB.png', 'transfer_supported' => true],
            ['id' => 21, 'name' => 'Ngân hàng TMCP Quân đội', 'code' => 'MB', 'bin' => '970422', 'short_name' => 'MBBank', 'logo' => 'https://cdn.vietqr.io/img/MB.png', 'transfer_supported' => true],
            ['id' => 38, 'name' => 'Ngân hàng TMCP Kỹ thương Việt Nam', 'code' => 'TCB', 'bin' => '970407', 'short_name' => 'Techcombank', 'logo' => 'https://cdn.vietqr.io/img/TCB.png', 'transfer_supported' => true],
            ['id' => 17, 'name' => 'Ngân hàng TMCP Công thương Việt Nam', 'code' => 'ICB', 'bin' => '970415', 'short_name' => 'VietinBank', 'logo' => 'https://cdn.vietqr.io/img/ICB.png', 'transfer_supported' => true],
            ['id' => 4, 'name' => 'Ngân hàng TMCP Đầu tư và Phát triển Việt Nam', 'code' => 'BIDV', 'bin' => '970418', 'short_name' => 'BIDV', 'logo' => 'https://cdn.vietqr.io/img/BIDV.png', 'transfer_supported' => true],
            ['id' => 42, 'name' => 'Ngân hàng Nông nghiệp và Phát triển Nông thôn Việt Nam', 'code' => 'VBA', 'bin' => '970405', 'short_name' => 'Agribank', 'logo' => 'https://cdn.vietqr.io/img/VBA.png', 'transfer_supported' => true],
        ];
    }
}

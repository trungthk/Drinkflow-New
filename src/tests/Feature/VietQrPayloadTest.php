<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PaymentAccount;
use App\Models\Room;
use App\Services\Payment\VietQrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VietQrPayloadTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Split an EMVCo TLV string into an id => value map (values of nested tags stay as raw strings).
     *
     * @return array<string, string>
     */
    private function parseTlv(string $data): array
    {
        $result = [];
        $offset = 0;

        while ($offset < strlen($data)) {
            $id = substr($data, $offset, 2);
            $length = (int) substr($data, $offset + 2, 2);
            $result[$id] = substr($data, $offset + 4, $length);
            $offset += 4 + $length;
        }

        $this->assertSame(strlen($data), $offset, 'TLV lengths must add up exactly to the payload length.');

        return $result;
    }

    /** Independent CRC-16/CCITT-FALSE used to verify the payload checksum. */
    private function crc16(string $data): string
    {
        $crc = 0xFFFF;
        foreach (str_split($data) as $char) {
            $crc ^= ord($char) << 8;
            for ($bit = 0; $bit < 8; $bit++) {
                $crc = ($crc & 0x8000) !== 0 ? (($crc << 1) ^ 0x1021) & 0xFFFF : ($crc << 1) & 0xFFFF;
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }

    private function account(string $bankCode = 'VCB'): PaymentAccount
    {
        $room = Room::create(['name' => 'QR Room', 'slug' => 'qr-room', 'status' => 'active']);

        return PaymentAccount::create([
            'room_id' => $room->id,
            'bank_code' => $bankCode,
            'bank_name' => 'Vietcombank',
            'account_number' => '0123456789012',
            'account_name' => 'NGUYEN VAN A',
            'is_default' => true,
            'status' => 'active',
        ]);
    }

    /** The payload uses the NAPAS layout: nested beneficiary (BIN + account), service code and purpose (content). */
    public function test_payload_follows_napas_vietqr_layout(): void
    {
        $payload = app(VietQrService::class)->generate($this->account(), 125000, 'ORD1-20260921-001 Tra sua');

        $root = $this->parseTlv($payload);
        $this->assertSame('01', $root['00']);
        $this->assertSame('12', $root['01'], 'A payload with an amount is a dynamic QR.');
        $this->assertSame('704', $root['53']);
        $this->assertSame('125000', $root['54']);
        $this->assertSame('VN', $root['58']);
        $this->assertArrayNotHasKey('52', $root);
        $this->assertArrayNotHasKey('59', $root);
        $this->assertArrayNotHasKey('60', $root);

        $account = $this->parseTlv($root['38']);
        $this->assertSame('A000000727', $account['00']);
        $this->assertSame('QRIBFTTA', $account['02']);
        $beneficiary = $this->parseTlv($account['01']);
        $this->assertSame('970436', $beneficiary['00'], 'Vietcombank BIN.');
        $this->assertSame('0123456789012', $beneficiary['01']);

        $additional = $this->parseTlv($root['62']);
        $this->assertSame('ORD1-20260921-001 Tra sua', $additional['08'], 'Transfer content lives in purpose tag 08.');
        $this->assertArrayNotHasKey('05', $additional);
    }

    /** The CRC at the end of the payload matches CRC-16/CCITT-FALSE over everything before it. */
    public function test_payload_crc_is_valid(): void
    {
        $payload = app(VietQrService::class)->generate($this->account('MB'), 50000, 'Thanh toan don');

        $this->assertSame('6304', substr($payload, -8, 4));
        $this->assertSame($this->crc16(substr($payload, 0, -4)), substr($payload, -4));
    }

    /** Without an amount the QR is static and carries no amount tag; empty content falls back to the default memo. */
    public function test_static_payload_has_no_amount_and_default_content(): void
    {
        $root = $this->parseTlv(app(VietQrService::class)->generate($this->account(), 0, ''));

        $this->assertSame('11', $root['01']);
        $this->assertArrayNotHasKey('54', $root);
        $this->assertSame('DRINKFLOW', $this->parseTlv($root['62'])['08']);
    }

    /** Vietnamese diacritics are stripped and the content is limited to 25 ASCII characters. */
    public function test_transfer_content_is_ascii_and_limited_to_25_characters(): void
    {
        $root = $this->parseTlv(app(VietQrService::class)->generate($this->account(), 10000, 'Thanh toán đơn hàng số 1234567890'));

        $content = $this->parseTlv($root['62'])['08'];
        $this->assertSame('Thanh toan don hang so 12', $content);
        $this->assertLessThanOrEqual(25, strlen($content));
    }
}

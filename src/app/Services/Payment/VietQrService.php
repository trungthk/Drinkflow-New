<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Models\PaymentAccount;
use App\Services\Common\BankService;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * VietQrService
 *
 * Generates a fully compliant EMVCo / NAPAS 247 VietQR payload string.
 *
 * Specification references:
 *  - EMVCo Merchant-Presented QR Code Specification (EMV QRCPS v1.1)
 *  - NAPAS VietQR Technical Standard (revision 2024-Q2)
 *
 * Payload structure (root TLVs, assembled in order):
 *  ID 00 – Payload Format Indicator  → "01"
 *  ID 01 – Point of Initiation Mode  → "12" dynamic | "11" static
 *  ID 38 – Merchant Account Info (NAPAS)
 *          ├─ sub-00 GUID       "A000000727"
 *          ├─ sub-01 BIN       bank BIN (6 digits)
 *          └─ sub-02 AccountNo  account number
 *  ID 52 – Merchant Category Code   → "0000"
 *  ID 53 – Transaction Currency     → "704" (VND)
 *  ID 54 – Transaction Amount       → omitted when 0
 *  ID 58 – Country Code             → "VN"
 *  ID 59 – Merchant Name            → account_name (≤ 25 chars, ASCII uppercase)
 *  ID 60 – Merchant City            → "HO CHI MINH"
 *  ID 62 – Additional Data
 *          └─ sub-05 Reference Label → transfer content (≤ 25 chars, ASCII)
 *  ID 63 – CRC-16/CCITT-FALSE (4 hex chars, uppercase)
 */
class VietQrService
{
    /**
     * NAPAS AID registered with EMVCo.
     */
    private const NAPAS_GUID = 'A000000727';

    // Root-level EMVCo tag IDs
    private const TAG_PAYLOAD_FORMAT_INDICATOR = '00';
    private const TAG_INITIATION_MODE          = '01';
    private const TAG_MERCHANT_ACCOUNT_NAPAS   = '38';
    private const TAG_MERCHANT_CATEGORY_CODE   = '52';
    private const TAG_TRANSACTION_CURRENCY     = '53';
    private const TAG_TRANSACTION_AMOUNT       = '54';
    private const TAG_COUNTRY_CODE             = '58';
    private const TAG_MERCHANT_NAME            = '59';
    private const TAG_MERCHANT_CITY            = '60';
    private const TAG_ADDITIONAL_DATA          = '62';
    private const TAG_CRC                      = '63';

    // Merchant Account Info (ID 38) sub-tag IDs
    private const SUB_TAG_GUID       = '00';
    private const SUB_TAG_BANK_BIN   = '01';
    private const SUB_TAG_ACCOUNT_NO = '02';

    // Additional Data Field (ID 62) sub-tag IDs
    private const SUB_TAG_REFERENCE_LABEL = '05';

    // Initiation modes
    private const MODE_DYNAMIC = '12';
    private const MODE_STATIC  = '11';

    // Fixed field values
    private const VND_CURRENCY_CODE      = '704';
    private const COUNTRY_CODE_VN        = 'VN';
    private const DEFAULT_MERCHANT_CITY  = 'HO CHI MINH';
    private const DEFAULT_REFERENCE      = 'DRINKFLOW';

    public function __construct(private readonly BankService $bankService) {}

    // =========================================================================
    // Public API
    // =========================================================================

    /**
     * Generate a VietQR / EMVCo QR payload string.
     *
     * @param  PaymentAccount  $account          Bank account to receive the transfer.
     * @param  int             $amount           Transfer amount in VNĐ (0 = static QR, no amount field).
     * @param  string          $transferContent  Reference / memo shown to the payer (will be ASCII-sanitized, ≤ 25 chars).
     * @param  string|null     $merchantCity     Override merchant city label; defaults to "HO CHI MINH".
     * @return string                            Complete EMVCo QR payload string ready for QR encoding.
     *
     * @throws \InvalidArgumentException When the bank BIN cannot be resolved from the banks catalogue.
     */
    public function generate(
        PaymentAccount $account,
        int $amount = 0,
        string $transferContent = '',
        ?string $merchantCity = null,
    ): string {
        // ── 1. Resolve bank BIN ──────────────────────────────────────────────
        $bin = $this->resolveBankBin((string) $account->bank_code);

        $accountNo    = (string) $account->account_number;
        $merchantName = Str::upper($this->truncate((string) $account->account_name, 25));
        $city         = Str::upper($merchantCity ?? self::DEFAULT_MERCHANT_CITY);
        $reference    = $this->sanitizeReference($transferContent);

        // ── 2. Build Merchant Account Information (ID 38) ────────────────────
        $merchantAccountInfo = $this->buildMerchantAccountInfo($bin, $accountNo);

        // ── 3. Build Additional Data Field (ID 62) ───────────────────────────
        $additionalData = $this->buildAdditionalData($reference);

        // ── 4. Assemble root-level TLVs (EMVCo-prescribed order) ────────────
        $mode    = $amount > 0 ? self::MODE_DYNAMIC : self::MODE_STATIC;
        $payload = '';

        $payload .= $this->tlv(self::TAG_PAYLOAD_FORMAT_INDICATOR, '01');
        $payload .= $this->tlv(self::TAG_INITIATION_MODE,          $mode);
        $payload .= $this->tlv(self::TAG_MERCHANT_ACCOUNT_NAPAS,   $merchantAccountInfo);
        $payload .= $this->tlv(self::TAG_MERCHANT_CATEGORY_CODE,   '0000');
        $payload .= $this->tlv(self::TAG_TRANSACTION_CURRENCY,     self::VND_CURRENCY_CODE);

        if ($amount > 0) {
            $payload .= $this->tlv(self::TAG_TRANSACTION_AMOUNT, (string) $amount);
        }

        $payload .= $this->tlv(self::TAG_COUNTRY_CODE,    self::COUNTRY_CODE_VN);
        $payload .= $this->tlv(self::TAG_MERCHANT_NAME,   $merchantName);
        $payload .= $this->tlv(self::TAG_MERCHANT_CITY,   $city);
        $payload .= $this->tlv(self::TAG_ADDITIONAL_DATA, $additionalData);

        // ── 5. Append CRC stub and compute CRC-16 ───────────────────────────
        // EMVCo spec §4.7: CRC covers everything from tag 00 up to and including
        // the CRC tag ID (63) and its length field (04), but NOT the 4-digit value.
        $payloadWithStub = $payload . self::TAG_CRC . '04';
        $crc             = $this->crc16($payloadWithStub);

        // ── 6. Return final payload ──────────────────────────────────────────
        return $payloadWithStub . $crc;
    }

    /**
     * Generate a VietQR image URL via the img.vietqr.io CDN (for server-side embedding).
     *
     * Use this when you need a pre-rendered PNG (e.g., email, PDF) and an internet
     * connection is available. For offline / privacy-sensitive scenarios prefer
     * generate() + a local QR encoder.
     *
     * @param  PaymentAccount  $account          Bank account.
     * @param  int             $amount           Amount in VNĐ (0 = omit parameter).
     * @param  string          $transferContent  Reference / memo.
     * @param  string          $template         VietQR image template ("compact2", "qr_only", "print", …).
     * @return string                            CDN PNG URL.
     */
    public function imageUrl(
        PaymentAccount $account,
        int $amount = 0,
        string $transferContent = '',
        string $template = 'compact2',
    ): string {
        $base = sprintf(
            'https://img.vietqr.io/image/%s-%s-%s.png',
            rawurlencode((string) $account->bank_code),
            rawurlencode((string) $account->account_number),
            rawurlencode($template),
        );

        $params = array_filter([
            'amount'      => $amount > 0 ? $amount : null,
            'addInfo'     => $transferContent !== '' ? $this->sanitizeReference($transferContent) : null,
            'accountName' => Str::upper($this->truncate((string) $account->account_name, 25)),
        ], fn ($v) => $v !== null && $v !== '');

        $query = http_build_query($params);

        return $query !== '' ? $base . '?' . $query : $base;
    }

    // =========================================================================
    // Payload-building helpers
    // =========================================================================

    /**
     * Build the Merchant Account Information value string for tag ID 38.
     *
     * Sub-TLVs assembled in order:
     *   00 → NAPAS GUID  "A000000727"
     *   01 → Bank BIN    (6 digits, e.g. "970436")
     *   02 → Account No  (numeric string)
     *
     * @param  string  $bin       NAPAS 6-digit BIN.
     * @param  string  $accountNo Account number.
     * @return string             Encoded inner-TLV string (value portion of tag 38).
     */
    private function buildMerchantAccountInfo(string $bin, string $accountNo): string
    {
        $inner  = $this->tlv(self::SUB_TAG_GUID,       self::NAPAS_GUID);
        $inner .= $this->tlv(self::SUB_TAG_BANK_BIN,   $bin);
        $inner .= $this->tlv(self::SUB_TAG_ACCOUNT_NO, $accountNo);

        return $inner;
    }

    /**
     * Build the Additional Data Field value string for tag ID 62.
     *
     * Only sub-tag 05 (Reference Label) is populated; other sub-tags are omitted
     * to keep the payload compact.
     *
     * @param  string  $referenceLabel  Sanitized, ASCII-safe, ≤ 25-char transfer memo.
     * @return string                   Encoded inner-TLV string (value portion of tag 62).
     */
    private function buildAdditionalData(string $referenceLabel): string
    {
        $label = $referenceLabel !== '' ? $referenceLabel : self::DEFAULT_REFERENCE;

        return $this->tlv(self::SUB_TAG_REFERENCE_LABEL, $label);
    }

    // =========================================================================
    // TLV encoding
    // =========================================================================

    /**
     * Encode a single EMVCo TLV element.
     *
     * Format: {tag-id (2 chars)}{length (2 chars, zero-padded)}{value}
     *
     * Length is measured in UTF-8 characters (code points), matching the VietQR
     * spec which targets single-byte ASCII content for all fields.
     *
     * @param  string  $id     Two-digit tag identifier (e.g. "00", "38").
     * @param  string  $value  Field value string.
     * @return string          Encoded TLV fragment.
     */
    private function tlv(string $id, string $value): string
    {
        $len = mb_strlen($value, 'UTF-8');

        return $id . str_pad((string) $len, 2, '0', STR_PAD_LEFT) . $value;
    }

    // =========================================================================
    // CRC-16/CCITT-FALSE
    // =========================================================================

    /**
     * Compute CRC-16/CCITT-FALSE and return as a 4-character uppercase hex string.
     *
     * Parameters:
     *   Width    : 16
     *   Poly     : 0x1021
     *   Init     : 0xFFFF
     *   RefIn    : false
     *   RefOut   : false
     *   XorOut   : 0x0000
     *   Check    : 0x29B1  (for input "123456789")
     *
     * @param  string  $data  Data to checksum (must end with the CRC stub "6304").
     * @return string         4-character uppercase hex CRC value (e.g. "A3F2").
     */
    private function crc16(string $data): string
    {
        $crc  = 0xFFFF;
        $poly = 0x1021;

        /** @var int[] $bytes */
        $bytes = array_values(unpack('C*', $data));

        foreach ($bytes as $byte) {
            $crc ^= ($byte << 8);

            for ($bit = 0; $bit < 8; $bit++) {
                if (($crc & 0x8000) !== 0) {
                    $crc = (($crc << 1) ^ $poly) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }

    // =========================================================================
    // Bank BIN resolution
    // =========================================================================

    /**
     * Resolve the 6-digit NAPAS BIN for the given short bank code.
     *
     * @param  string  $bankCode  Short bank code (e.g. "VCB", "MB", "TCB").
     * @return string             6-digit NAPAS BIN (e.g. "970436").
     *
     * @throws \InvalidArgumentException If the bank code is unrecognised or the BIN is missing.
     */
    private function resolveBankBin(string $bankCode): string
    {
        $bank = $this->bankService->getBankByCode($bankCode);

        if ($bank === null || empty($bank['bin'])) {
            throw new InvalidArgumentException(
                "VietQrService: Cannot resolve NAPAS BIN for bank code \"{$bankCode}\". "
                . 'Ensure the bank is listed in banks.json with a valid "bin" field.'
            );
        }

        return (string) $bank['bin'];
    }

    // =========================================================================
    // String utilities
    // =========================================================================

    /**
     * Sanitize a transfer reference / memo string for embedding in the QR payload.
     *
     * Steps:
     *  1. Remove Vietnamese diacritics (NAPAS only accepts ASCII in the reference label).
     *  2. Strip characters outside [A-Za-z0-9 \-\.].
     *  3. Collapse consecutive spaces into a single space and trim.
     *  4. Truncate to 25 characters.
     *
     * @param  string  $input  Raw transfer memo from the application layer.
     * @return string          Sanitized, ASCII-safe, ≤ 25-char string.
     */
    private function sanitizeReference(string $input): string
    {
        $ascii   = $this->removeAccents($input);
        $cleaned = (string) preg_replace('/[^A-Za-z0-9 \-\.]/', '', $ascii);
        $trimmed = (string) preg_replace('/\s+/', ' ', trim($cleaned));

        return $this->truncate($trimmed, 25);
    }

    /**
     * Remove Vietnamese and common Latin diacritics, returning an ASCII equivalent.
     *
     * The mapping covers the full Unicode Vietnamese character set used in names
     * and common transfer references (tones, circumflexes, breves, horns).
     *
     * @param  string  $str  UTF-8 input string.
     * @return string        ASCII-safe output string.
     */
    private function removeAccents(string $str): string
    {
        $map = [
            // Lowercase Vietnamese vowels with tones
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'ý' => 'y', 'ÿ' => 'y',
            'ç' => 'c', 'ñ' => 'n', 'ð' => 'd', 'þ' => 'th', 'æ' => 'ae',
            // Uppercase
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A',
            'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O',
            'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'Ý' => 'Y', 'Ç' => 'C', 'Ñ' => 'N', 'Ð' => 'D', 'Þ' => 'TH', 'Æ' => 'AE',
            // Vietnamese ă / Ă
            'ă' => 'a', 'ắ' => 'a', 'ặ' => 'a', 'ằ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a',
            'Ă' => 'A', 'Ắ' => 'A', 'Ặ' => 'A', 'Ằ' => 'A', 'Ẳ' => 'A', 'Ẵ' => 'A',
            // Vietnamese â (with tones)
            'ấ' => 'a', 'ậ' => 'a', 'ầ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a',
            'Ấ' => 'A', 'Ậ' => 'A', 'Ầ' => 'A', 'Ẩ' => 'A', 'Ẫ' => 'A',
            // Vietnamese đ / Đ
            'đ' => 'd', 'Đ' => 'D',
            // Vietnamese ê (with tones)
            'ế' => 'e', 'ệ' => 'e', 'ề' => 'e', 'ể' => 'e', 'ễ' => 'e',
            'Ế' => 'E', 'Ệ' => 'E', 'Ề' => 'E', 'Ể' => 'E', 'Ễ' => 'E',
            // Vietnamese ô (with tones)
            'ố' => 'o', 'ộ' => 'o', 'ồ' => 'o', 'ổ' => 'o', 'ỗ' => 'o',
            'Ố' => 'O', 'Ộ' => 'O', 'Ồ' => 'O', 'Ổ' => 'O', 'Ỗ' => 'O',
            // Vietnamese ơ / Ơ (with tones)
            'ơ' => 'o', 'ớ' => 'o', 'ợ' => 'o', 'ờ' => 'o', 'ở' => 'o', 'ỡ' => 'o',
            'Ơ' => 'O', 'Ớ' => 'O', 'Ợ' => 'O', 'Ờ' => 'O', 'Ở' => 'O', 'Ỡ' => 'O',
            // Vietnamese ư / Ư (with tones)
            'ư' => 'u', 'ứ' => 'u', 'ự' => 'u', 'ừ' => 'u', 'ử' => 'u', 'ữ' => 'u',
            'Ư' => 'U', 'Ứ' => 'U', 'Ự' => 'U', 'Ừ' => 'U', 'Ử' => 'U', 'Ữ' => 'U',
            // Vietnamese ỳ / ỷ / ỵ / ỹ
            'ỳ' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y',
            'Ỳ' => 'Y', 'Ỵ' => 'Y', 'Ỷ' => 'Y', 'Ỹ' => 'Y',
            // Vietnamese ỉ / ị
            'ỉ' => 'i', 'ị' => 'i', 'Ỉ' => 'I', 'Ị' => 'I',
            // Standalone tone marks on a / o / u without combining diacritics
            'ả' => 'a', 'ạ' => 'a', 'Ả' => 'A', 'Ạ' => 'A',
            'ụ' => 'u', 'Ụ' => 'U',
        ];

        return strtr($str, $map);
    }

    /**
     * Truncate a string to at most $maxLength characters (multibyte-safe).
     *
     * @param  string  $str        UTF-8 input.
     * @param  int     $maxLength  Maximum allowed character count.
     * @return string              Possibly truncated string.
     */
    private function truncate(string $str, int $maxLength): string
    {
        return mb_strlen($str, 'UTF-8') > $maxLength
            ? mb_substr($str, 0, $maxLength, 'UTF-8')
            : $str;
    }
}

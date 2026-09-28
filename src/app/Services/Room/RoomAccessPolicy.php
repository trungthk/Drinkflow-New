<?php

declare(strict_types=1);

namespace App\Services\Room;

use App\Exceptions\RoomAccessDeniedException;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Models\RoomSetting;

/**
 * Per-room access rules configured by the room admin (room_settings, JSON lists):
 * - allowed_email_domains: only accounts whose email domain is listed may join (empty = any domain);
 * - allowed_ips: only these client IPs may open the room (empty = any IP);
 * - blocked_ips: these client IPs may never open the room (always wins over allowed_ips).
 *
 * IPs are compared in binary form (inet_pton), so "::1" and "0:0:0:0:0:0:0:1" are the same address.
 */
class RoomAccessPolicy
{
    public const ALLOWED_EMAIL_DOMAINS = 'allowed_email_domains';

    public const ALLOWED_IPS = 'allowed_ips';

    public const BLOCKED_IPS = 'blocked_ips';

    /** Setting keys that hold a list, in the order they are shown on the settings page. */
    public const LIST_KEYS = [self::ALLOWED_EMAIL_DOMAINS, self::ALLOWED_IPS, self::BLOCKED_IPS];

    /** Maximum number of entries per list. */
    public const MAX_ENTRIES = 100;

    /** Domain name (IDN already converted to ASCII is not required; letters, digits, hyphens and dots). */
    public const DOMAIN_PATTERN = '/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i';

    /** @var array<int, array<string, list<string>>> Lists already read, per room id, for this request. */
    private array $cache = [];

    /**
     * Read one list setting of a room.
     *
     * @param Room $room Room whose settings are read.
     * @param string $key One of self::LIST_KEYS.
     * @return list<string> Stored (normalized) entries; empty when not configured.
     */
    public function list(Room $room, string $key): array
    {
        if (! isset($this->cache[$room->id])) {
            $this->cache[$room->id] = RoomSetting::query()
                ->where('room_id', $room->id)
                ->whereIn('key', self::LIST_KEYS)
                ->pluck('value', 'key')
                ->map(static fn (?string $value): array => self::decode($value))
                ->all();
        }

        return $this->cache[$room->id][$key] ?? [];
    }

    /**
     * Forget cached lists of a room (after its settings change).
     *
     * @param Room $room Room whose lists changed.
     */
    public function forget(Room $room): void
    {
        unset($this->cache[$room->id]);
    }

    /**
     * Whether an email address may join the room.
     *
     * @param Room $room Target room.
     * @param string|null $email Account email.
     * @return bool True when no domain is configured or the email's domain is listed.
     */
    public function emailAllowed(Room $room, ?string $email): bool
    {
        $domains = $this->list($room, self::ALLOWED_EMAIL_DOMAINS);
        if ($domains === []) {
            return true;
        }

        $domain = self::emailDomain($email);

        return $domain !== null && in_array($domain, $domains, true);
    }

    /**
     * Whether a client IP may open the room: never when blocked, otherwise only when allowed (or no allow list).
     *
     * @param Room $room Target room.
     * @param string|null $ip Client IP address.
     * @return bool True when access is allowed.
     */
    public function ipAllowed(Room $room, ?string $ip): bool
    {
        $address = self::normalizeIp($ip);
        if ($address !== null && in_array($address, $this->list($room, self::BLOCKED_IPS), true)) {
            return false;
        }

        $allowed = $this->list($room, self::ALLOWED_IPS);

        return $allowed === [] || ($address !== null && in_array($address, $allowed, true));
    }

    /**
     * Throw when the client IP may not open the room.
     *
     * @param Room $room Target room.
     * @param string|null $ip Client IP address.
     * @throws RoomAccessDeniedException When the IP is blocked or not in the allow list.
     */
    public function ensureIpAllowed(Room $room, ?string $ip): void
    {
        if (! $this->ipAllowed($room, $ip)) {
            throw new RoomAccessDeniedException(__('room.access.ip_denied', ['ip' => (string) $ip]));
        }
    }

    /**
     * Throw when the account's email domain may not join the room.
     *
     * @param Room $room Target room.
     * @param GlobalUser $user Account joining.
     * @throws RoomAccessDeniedException When the domain is not allowed.
     */
    public function ensureEmailAllowed(Room $room, GlobalUser $user): void
    {
        if (! $this->emailAllowed($room, $user->email)) {
            throw new RoomAccessDeniedException($this->emailDeniedMessage($room));
        }
    }

    /**
     * Translated "email domain not allowed" message listing the accepted domains.
     *
     * @param Room $room Target room.
     * @return string Message.
     */
    public function emailDeniedMessage(Room $room): string
    {
        return __('room.access.email_domain_denied', [
            'domains' => implode(', ', array_map(static fn (string $domain): string => '@'.$domain, $this->list($room, self::ALLOWED_EMAIL_DOMAINS))),
        ]);
    }

    /**
     * Normalize submitted domains: trim, lower-case, drop a leading "@", remove duplicates.
     *
     * @param array<int, mixed> $values Raw entries.
     * @return list<string> Normalized domains.
     */
    public static function normalizeDomains(array $values): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $value): string => ltrim(strtolower(trim((string) $value)), '@'),
            $values
        ), static fn (string $value): bool => $value !== '')));
    }

    /**
     * Normalize submitted IPs to their canonical text form and remove duplicates.
     *
     * @param array<int, mixed> $values Raw entries (already validated as IPv4/IPv6).
     * @return list<string> Canonical IPs.
     */
    public static function normalizeIps(array $values): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $value): ?string => self::normalizeIp((string) $value),
            $values
        ))));
    }

    /**
     * Canonical text form of an IPv4/IPv6 address (e.g. "0:0::1" → "::1").
     *
     * @param string|null $ip Raw address.
     * @return string|null Canonical address, or null when invalid.
     */
    public static function normalizeIp(?string $ip): ?string
    {
        $ip = trim((string) $ip);
        if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return null;
        }

        $packed = @inet_pton($ip);

        return $packed === false ? null : (string) inet_ntop($packed);
    }

    /**
     * Lower-cased domain part of an email address.
     *
     * @param string|null $email Email address.
     * @return string|null Domain, or null when the email has none.
     */
    public static function emailDomain(?string $email): ?string
    {
        $at = strrpos((string) $email, '@');
        if ($at === false) {
            return null;
        }

        $domain = strtolower(trim(substr((string) $email, $at + 1)));

        return $domain === '' ? null : $domain;
    }

    /**
     * Decode a stored JSON list.
     *
     * @param string|null $value Stored value.
     * @return list<string> Entries (empty when missing or malformed).
     */
    private static function decode(?string $value): array
    {
        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? array_values(array_filter($decoded, 'is_string')) : [];
    }
}

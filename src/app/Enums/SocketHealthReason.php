<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Why the realtime gateway health check did not succeed (translated via superadmin.socket.reason_*).
 */
enum SocketHealthReason: string
{
    /** REALTIME_URL is empty. */
    case NotConfigured = 'not_configured';
    /** REALTIME_INTERNAL_SECRET is empty on the Laravel side. */
    case SecretMissing = 'secret_missing';
    /** Gateway answered 401/403: REALTIME_INTERNAL_SECRET differs between Laravel and the gateway. */
    case SecretMismatch = 'secret_mismatch';
    /** No TCP/HTTP connection (gateway down, wrong host/port, firewall, timeout). */
    case ConnectionFailed = 'connection_failed';
    /** Gateway answered with another non-2xx status (e.g. 404 from a proxy path, 502). */
    case HttpError = 'http_error';
    /** Gateway answered 2xx but the body was not the expected JSON. */
    case InvalidResponse = 'invalid_response';

    /**
     * Localized explanation with the config to check, shown under the Socket.IO status pill.
     *
     * @param int|null $httpStatus HTTP status the gateway answered with, when it answered.
     * @return string Translated message for the current locale.
     */
    public function message(?int $httpStatus = null): string
    {
        return __('superadmin.socket.reason_'.$this->value, ['status' => (string) ($httpStatus ?? '')]);
    }
}

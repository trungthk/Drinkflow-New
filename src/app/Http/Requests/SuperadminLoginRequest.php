<?php

declare(strict_types=1);

namespace App\Http\Requests;

/**
 * Superadmin sign-in form: same fields, captcha and lockout rules as the admin form.
 */
class SuperadminLoginRequest extends AdminLoginRequest
{
    /**
     * Actor recorded on security events raised by this login form.
     *
     * @return string Actor type.
     */
    protected function securityActor(): string
    {
        return 'superadmin';
    }
}

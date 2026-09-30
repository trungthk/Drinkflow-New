<?php

declare(strict_types=1);

namespace App\Services\Billing\Gateway;

use RuntimeException;

/**
 * A payment notification whose signature could not be verified.
 */
class InvalidGatewaySignature extends RuntimeException
{
}

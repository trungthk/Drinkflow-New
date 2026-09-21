<?php

namespace Tests;

use App\Support\Security\OutboundUrlGuard;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Keep tests offline: every host name resolves to a fixed public address, so only IP literals and
        // reserved names such as "localhost" can exercise the private-address checks of the SSRF guard.
        $this->app->bind(OutboundUrlGuard::class, fn (): OutboundUrlGuard => new class extends OutboundUrlGuard {
            protected function resolveHost(string $host): array
            {
                return ['93.184.216.34'];
            }
        });
    }
}

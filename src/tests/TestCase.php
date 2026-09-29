<?php

namespace Tests;

use App\Models\Superadmin;
use App\Services\Authorization\PermissionCatalogService;
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

    /**
     * Create a superadmin holding every permission with the `all` scope (platform owner).
     *
     * @param array<string, mixed> $attributes Superadmin attributes.
     * @return Superadmin Created superadmin.
     */
    protected function createSuperadmin(array $attributes): Superadmin
    {
        $superadmin = Superadmin::create($attributes);
        app(PermissionCatalogService::class)->grantAll($superadmin);

        return $superadmin;
    }
}

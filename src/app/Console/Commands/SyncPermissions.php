<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Authorization\PermissionCatalogService;
use Illuminate\Console\Command;

class SyncPermissions extends Command
{
    /** @var string */
    protected $signature = 'permissions:sync {--prune : Delete permissions that are no longer in the catalog}';

    /** @var string */
    protected $description = 'Write new Superadmin permissions from App\Enums\Permission to the database.';

    /**
     * Sync the permission catalog; run after deploying code that adds permissions.
     *
     * @param PermissionCatalogService $catalog Catalog service.
     * @return int Process exit code.
     */
    public function handle(PermissionCatalogService $catalog): int
    {
        $result = $catalog->sync((bool) $this->option('prune'));

        $this->info('Added: '.($result['added'] === [] ? 'none' : implode(', ', $result['added'])));
        if ($result['stale'] !== []) {
            $this->warn(($result['pruned'] ? 'Pruned: ' : 'Stale (use --prune to delete): ').implode(', ', $result['stale']));
        }

        return self::SUCCESS;
    }
}

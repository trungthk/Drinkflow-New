<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Distinguish period invoices from package-upgrade invoices (App\Enums\InvoiceType).
     *
     * Existing rows are period invoices, hence the default.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('admin_invoices', function (Blueprint $table): void {
            $table->string('type', 20)->default('subscription')->after('admin_subscription_id');
            // "Does this Agent have an open upgrade request?" lookups.
            $table->index(['admin_id', 'type', 'status']);
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('admin_invoices', function (Blueprint $table): void {
            $table->dropIndex(['admin_id', 'type', 'status']);
            $table->dropColumn('type');
        });
    }
};

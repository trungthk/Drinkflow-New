<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('global_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('normalized_name')->index();
            $table->string('email')->unique();
            $table->string('avatar_url')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });

        Schema::create('oauth_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('global_user_id')->constrained('global_users')->restrictOnDelete();
            $table->string('provider');
            $table->string('provider_user_id');
            $table->string('provider_email');
            $table->timestamps();
            $table->unique(['provider', 'provider_user_id']);
            $table->index('global_user_id');
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('avatar_url')->nullable();
            $table->string('status')->default('active')->index();
            $table->string('timezone')->default('Asia/Ho_Chi_Minh');
            $table->string('language', 10)->default('vi');
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('room_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->restrictOnDelete();
            $table->foreignId('global_user_id')->constrained()->restrictOnDelete();
            $table->string('user_code');
            $table->string('display_name');
            $table->string('normalized_name')->index();
            $table->string('status')->default('active');
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();
            $table->unique(['room_id', 'global_user_id']);
            $table->unique(['room_id', 'user_code']);
            $table->index(['room_id', 'status']);
        });

        Schema::create('room_user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_user_id')->constrained()->cascadeOnDelete();
            $table->string('device_uuid');
            $table->string('token_hash');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->unique(['room_user_id', 'device_uuid']);
            $table->index('device_uuid');
        });

        Schema::create('admin_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('admin')->index();
            $table->string('status')->default('active')->index();
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('admin_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('admin_accounts')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['admin_id', 'room_id']);
        });

        Schema::create('payment_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->restrictOnDelete();
            $table->string('bank_code');
            $table->string('bank_name');
            $table->string('account_number');
            $table->string('account_name');
            $table->boolean('is_default')->default(false);
            $table->string('status')->default('active')->index();
            $table->timestamps();
            $table->index(['room_id', 'status']);
        });

        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('restaurant');
            $table->foreignId('creator_admin_id')->nullable()->constrained('admin_accounts')->nullOnDelete();
            $table->string('sponsor_name')->nullable();
            $table->timestamp('deadline')->nullable();
            $table->unsignedBigInteger('max_budget')->nullable();
            $table->unsignedBigInteger('flat_price')->nullable();
            $table->unsignedBigInteger('delivery_fee')->default(0);
            $table->unsignedBigInteger('discount')->default(0);
            $table->foreignId('payment_account_id')->nullable()->constrained()->nullOnDelete();
            $table->text('description')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->index(['room_id', 'status']);
        });

        Schema::create('campaign_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('normalized_name')->index();
            $table->string('category')->nullable()->index();
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->unsignedBigInteger('base_price');
            $table->string('status')->default('active')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('source_url')->nullable();
            $table->string('source_item_key')->nullable();
            $table->timestamps();
            $table->index(['campaign_id', 'status']);
        });

        Schema::create('campaign_item_toppings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_item_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedBigInteger('price')->default(0);
            $table->string('status')->default('active');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->restrictOnDelete();
            $table->foreignId('campaign_id')->constrained()->restrictOnDelete();
            $table->foreignId('room_user_id')->constrained()->restrictOnDelete();
            $table->string('payment_method')->nullable();
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('delivery_amount')->default(0);
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedBigInteger('sponsor_amount')->default(0);
            $table->unsignedBigInteger('final_amount');
            $table->string('status')->default('submitted')->index();
            $table->text('note')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->index(['campaign_id', 'status']);
            $table->index(['room_user_id', 'created_at']);
            $table->index(['room_id', 'created_at']);
        });

        if (in_array(DB::getDriverName(), ['pgsql', 'sqlite'], true)) {
            DB::statement("CREATE UNIQUE INDEX orders_one_active_per_user_campaign ON orders (campaign_id, room_user_id) WHERE status IN ('submitted','confirmed','ordering','ordered','delivering')");
        }

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('item_name');
            $table->string('size_name')->nullable();
            $table->unsignedBigInteger('unit_price');
            $table->unsignedInteger('quantity');
            $table->unsignedTinyInteger('ice_percent')->nullable();
            $table->unsignedTinyInteger('sugar_percent')->nullable();
            $table->unsignedBigInteger('line_subtotal');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('order_item_toppings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_item_topping_id')->nullable()->constrained()->nullOnDelete();
            $table->string('topping_name');
            $table->unsignedBigInteger('unit_price');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('subtotal');
            $table->timestamps();
        });

        Schema::create('debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->restrictOnDelete();
            $table->foreignId('campaign_id')->constrained()->restrictOnDelete();
            $table->foreignId('room_user_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('original_amount');
            $table->unsignedBigInteger('sponsor_amount')->default(0);
            $table->bigInteger('adjustment_amount')->default(0);
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->unsignedBigInteger('remaining_amount');
            $table->string('status')->default('unpaid')->index();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['campaign_id', 'room_user_id']);
            $table->index(['room_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debts');
        Schema::dropIfExists('order_item_toppings');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('campaign_item_toppings');
        Schema::dropIfExists('campaign_items');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('payment_accounts');
        Schema::dropIfExists('admin_rooms');
        Schema::dropIfExists('admin_accounts');
        Schema::dropIfExists('room_user_devices');
        Schema::dropIfExists('room_users');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('oauth_identities');
        Schema::dropIfExists('global_users');
    }
};

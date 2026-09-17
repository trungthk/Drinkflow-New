<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('admin_accounts', function (Blueprint $table): void {
            $table->text('phone')->nullable()->change();
        });

        Schema::table('global_users', function (Blueprint $table): void {
            $table->text('phone')->nullable()->change();
        });

        // Encrypt any existing plain-text phone numbers in admin_accounts
        $adminAccounts = DB::table('admin_accounts')->whereNotNull('phone')->get(['id', 'phone']);
        foreach ($adminAccounts as $account) {
            if ($account->phone === null || $account->phone === '') {
                continue;
            }
            try {
                Crypt::decryptString($account->phone);
            } catch (\Throwable) {
                // Not encrypted yet, encrypt it
                DB::table('admin_accounts')->where('id', $account->id)->update([
                    'phone' => Crypt::encryptString($account->phone),
                ]);
            }
        }

        // Encrypt any existing plain-text phone numbers in global_users
        $globalUsers = DB::table('global_users')->whereNotNull('phone')->get(['id', 'phone']);
        foreach ($globalUsers as $user) {
            if ($user->phone === null || $user->phone === '') {
                continue;
            }
            try {
                Crypt::decryptString($user->phone);
            } catch (\Throwable) {
                // Not encrypted yet, encrypt it
                DB::table('global_users')->where('id', $user->id)->update([
                    'phone' => Crypt::encryptString($user->phone),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Decrypt phone numbers back to plain-text for admin_accounts
        $adminAccounts = DB::table('admin_accounts')->whereNotNull('phone')->get(['id', 'phone']);
        foreach ($adminAccounts as $account) {
            if ($account->phone === null || $account->phone === '') {
                continue;
            }
            try {
                $decrypted = Crypt::decryptString($account->phone);
                DB::table('admin_accounts')->where('id', $account->id)->update([
                    'phone' => substr($decrypted, 0, 30),
                ]);
            } catch (\Throwable) {
                // Already plain or invalid
            }
        }

        // Decrypt phone numbers back to plain-text for global_users
        $globalUsers = DB::table('global_users')->whereNotNull('phone')->get(['id', 'phone']);
        foreach ($globalUsers as $user) {
            if ($user->phone === null || $user->phone === '') {
                continue;
            }
            try {
                $decrypted = Crypt::decryptString($user->phone);
                DB::table('global_users')->where('id', $user->id)->update([
                    'phone' => $decrypted,
                ]);
            } catch (\Throwable) {
                // Already plain or invalid
            }
        }

        Schema::table('admin_accounts', function (Blueprint $table): void {
            $table->string('phone', 30)->nullable()->change();
        });

        Schema::table('global_users', function (Blueprint $table): void {
            $table->string('phone')->nullable()->change();
        });
    }
};

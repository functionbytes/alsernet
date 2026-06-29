<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('chat_accounts')) {
            return;
        }

        $accountId = DB::table('chat_accounts')->value('id');

        if (! $accountId) {
            $accountId = DB::table('chat_accounts')->insertGetId([
                'name' => 'Default Account',
                'default_locale' => 'es',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! Schema::hasColumn('users', 'account_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('account_id')->nullable()->after('id');
            });
        }

        DB::table('users')->whereNull('account_id')->update(['account_id' => $accountId]);

        $userIds = DB::table('users')->pluck('id');
        foreach ($userIds as $userId) {
            $exists = DB::table('chat_accounts_user')
                ->where('user_id', $userId)
                ->where('account_id', $accountId)
                ->exists();

            if (! $exists) {
                DB::table('chat_accounts_user')->insert([
                    'user_id' => $userId,
                    'account_id' => $accountId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'account_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('account_id');
            });
        }
    }
};

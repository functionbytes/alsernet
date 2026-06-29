<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('social_accounts', 'network_id')) {
                $table->string('network_id')->nullable()->after('network');
                $table->index('network_id');
            }

            if (! Schema::hasColumn('social_accounts', 'id_secure')) {
                $table->string('id_secure')->unique()->nullable()->after('id');
                $table->index('id_secure');
            }

            if (! Schema::hasColumn('social_accounts', 'proxy_id')) {
                $table->foreignId('proxy_id')->nullable()->after('account_id')->constrained('proxies')->nullOnDelete();
            }

            if (! Schema::hasColumn('social_accounts', 'category')) {
                $table->string('category')->nullable()->after('channel_type');
            }

            if (! Schema::hasColumn('social_accounts', 'profile_data')) {
                $table->json('profile_data')->nullable()->after('data');
            }

            if (! Schema::hasColumn('social_accounts', 'token_expires_at')) {
                $table->timestamp('token_expires_at')->nullable()->after('refresh_token');
            }

            if (! Schema::hasColumn('social_accounts', 'channel_type')) {
                $table->string('channel_type')->nullable()->after('network_id');
            }
        });

        // Add composite index if not present
        try {
            Schema::table('social_accounts', function (Blueprint $table) {
                $table->index(['network', 'network_id']);
            });
        } catch (QueryException $e) {
            // Index may already exist
        }

        // Drop old token_expiry column if exists
        if (Schema::hasColumn('social_accounts', 'token_expiry')) {
            Schema::table('social_accounts', function (Blueprint $table) {
                $table->dropColumn('token_expiry');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('social_accounts', 'network_id')) {
                $columns[] = 'network_id';
            }
            if (Schema::hasColumn('social_accounts', 'id_secure')) {
                $columns[] = 'id_secure';
            }
            if (Schema::hasColumn('social_accounts', 'proxy_id')) {
                $columns[] = 'proxy_id';
            }
            if (Schema::hasColumn('social_accounts', 'category')) {
                $columns[] = 'category';
            }
            if (Schema::hasColumn('social_accounts', 'profile_data')) {
                $columns[] = 'profile_data';
            }
            if (Schema::hasColumn('social_accounts', 'token_expires_at')) {
                $columns[] = 'token_expires_at';
            }
            if (Schema::hasColumn('social_accounts', 'channel_type')) {
                $columns[] = 'channel_type';
            }

            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });

        // Re-add token_expiry if needed
        if (! Schema::hasColumn('social_accounts', 'token_expiry')) {
            Schema::table('social_accounts', function (Blueprint $table) {
                $table->timestamp('token_expiry')->nullable()->after('access_token');
            });
        }
    }
};

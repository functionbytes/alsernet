<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('review_google_connections', function (Blueprint $table) {
            // Add expires_at as a dedicated connection expiry timestamp (distinct from token_expires_at)
            // status column already exists with a superset of values (pending, active, expired, revoked, error)
            if (! Schema::hasColumn('review_google_connections', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('token_expires_at')
                    ->comment('When this connection authorization expires');
            }
        });
    }

    public function down(): void
    {
        Schema::table('review_google_connections', function (Blueprint $table) {
            if (Schema::hasColumn('review_google_connections', 'expires_at')) {
                $table->dropColumn('expires_at');
            }
        });
    }
};

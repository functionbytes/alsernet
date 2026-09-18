<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('activity_log', 'hmac_signature')) {
            Schema::table('activity_log', function (Blueprint $table) {
                $table->string('hmac_signature', 64)->nullable()->after('properties');
                $table->index('hmac_signature');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('activity_log', 'hmac_signature')) {
            Schema::table('activity_log', function (Blueprint $table) {
                $table->dropIndex(['hmac_signature']);
                $table->dropColumn('hmac_signature');
            });
        }
    }
};

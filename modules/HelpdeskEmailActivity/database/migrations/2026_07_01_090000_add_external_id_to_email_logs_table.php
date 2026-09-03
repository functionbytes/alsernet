<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('email_logs')) {
            return;
        }

        if (! Schema::hasColumn('email_logs', 'external_id')) {
            Schema::table('email_logs', function (Blueprint $table) {
                $table->string('external_id')->nullable()->after('entity_id')->index();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('email_logs')) {
            return;
        }

        Schema::table('email_logs', function (Blueprint $table) {
            if (Schema::hasColumn('email_logs', 'external_id')) {
                $table->dropColumn('external_id');
            }
        });
    }
};

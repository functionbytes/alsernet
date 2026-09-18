<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_sync_failures', function (Blueprint $table) {
            $table->string('failure_type', 50)->nullable()->after('sync_type')->index();
        });
    }

    public function down(): void
    {
        Schema::table('supplier_sync_failures', function (Blueprint $table) {
            $table->dropColumn('failure_type');
        });
    }
};

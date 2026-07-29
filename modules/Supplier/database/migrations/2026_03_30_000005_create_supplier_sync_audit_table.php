<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_sync_audit', function (Blueprint $table) {
            $table->id();
            $table->char('uid', 36)->unique();
            $table->string('entity_type', 50)->index();
            $table->string('sync_direction', 50);
            $table->unsignedInteger('records_synced')->default(0);
            $table->unsignedInteger('records_failed')->default(0);
            $table->unsignedInteger('records_skipped')->default(0);
            $table->float('elapsed_seconds')->default(0);
            $table->float('memory_mb')->nullable();
            $table->float('peak_memory_mb')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedInteger('cycle_number')->nullable();
            $table->string('triggered_by', 50)->nullable();
            $table->timestamp('synced_at')->index();
            $table->timestamps();

            $table->index(['entity_type', 'sync_direction']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_sync_audit');
    }
};

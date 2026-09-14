<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_sync_schedules', function (Blueprint $table) {
            $table->id();
            $table->char('uid', 36)->unique();
            $table->string('sync_type', 50);
            $table->string('label', 100);
            $table->json('schedule_hours');
            $table->boolean('is_enabled')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->string('last_run_status', 20)->nullable();
            $table->unsignedBigInteger('last_batch_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('sync_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_sync_schedules');
    }
};

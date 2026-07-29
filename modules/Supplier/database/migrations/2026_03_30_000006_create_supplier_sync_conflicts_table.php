<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_sync_conflicts', function (Blueprint $table) {
            $table->id();
            $table->char('uid', 36)->unique();
            $table->string('entity_type', 50)->index();
            $table->unsignedBigInteger('entity_id')->nullable()->index();
            $table->unsignedBigInteger('erp_id')->nullable();
            $table->string('resolution_strategy', 50)->nullable();
            $table->json('local_data')->nullable();
            $table->json('erp_data')->nullable();
            $table->json('resolved_data')->nullable();
            $table->json('changed_fields')->nullable();
            $table->timestamp('conflict_detected_at')->nullable()->index();
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('resolved_by_user_id')->nullable();
            $table->string('resolved_by_ip', 45)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'resolved_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_sync_conflicts');
    }
};

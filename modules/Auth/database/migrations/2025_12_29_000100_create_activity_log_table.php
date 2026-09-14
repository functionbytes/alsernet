<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create activity_log table for Spatie Activity Log package
     * Registers all user actions (create, update, delete) on models
     */
    public function up(): void
    {
        if (! Schema::hasTable('activity_log')) {
            Schema::create('activity_log', function (Blueprint $table) {
                $table->id();
                $table->string('log_name')->nullable()->index();
                $table->text('description');
                $table->nullableUlidMorphs('subject', 'subject');
                $table->nullableUlidMorphs('causer', 'causer');
                $table->json('properties')->nullable();
                $table->string('batch_uuid')->nullable()->index();
                $table->timestamps();
                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_sla_policies', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 36)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('priority_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedSmallInteger('first_response_time_hours');
            $table->unsignedSmallInteger('resolution_time_hours');
            $table->boolean('business_hours_only')->default(true);
            $table->unsignedTinyInteger('warning_threshold_percent')->default(80);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('priority_id')->references('id')->on('helpdesk_priorities')->onDelete('set null');
            $table->foreign('category_id')->references('id')->on('helpdesk_categories')->onDelete('set null');

            // Indexes
            $table->index('priority_id');
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_sla_policies');
    }
};

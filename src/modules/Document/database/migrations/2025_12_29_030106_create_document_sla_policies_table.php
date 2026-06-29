<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_sla_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('upload_request_time')->comment('minutes');
            $table->integer('review_time')->comment('minutes');
            $table->integer('approval_time')->comment('minutes');
            $table->boolean('business_hours_only')->default(false);
            $table->json('business_hours')->nullable();
            $table->string('timezone')->default('UTC');
            $table->json('document_type_multipliers')->nullable();
            $table->boolean('enable_escalation')->default(false);
            $table->integer('escalation_threshold_percent')->nullable();
            $table->json('escalation_recipients')->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index('is_default');
            $table->index('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_sla_policies');
    }
};

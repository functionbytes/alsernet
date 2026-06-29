<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('document_stage_email_actions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('validation_stage');
            $table->string('email_action');
            $table->boolean('is_enabled')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['validation_stage', 'email_action'], 'idx_7693');
            $table->index('validation_stage');
            $table->index('is_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_stage_email_actions');
    }
};

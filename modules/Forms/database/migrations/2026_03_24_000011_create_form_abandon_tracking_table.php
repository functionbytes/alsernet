<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_abandon_tracking', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_id');
            $table->string('session_token', 64);
            $table->string('email', 255)->nullable();
            $table->json('partial_data')->nullable();
            $table->unsignedTinyInteger('current_step')->default(1);
            $table->string('last_field_key', 100)->nullable();
            $table->timestamp('started_at');
            $table->timestamp('last_activity_at');
            $table->timestamp('reminder_sent_at')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->timestamps();

            $table->index('form_id');
            $table->index('is_completed');
            $table->index('reminder_sent_at');
            $table->index(['form_id', 'is_completed']);

            $table->foreign('form_id')
                ->references('id')
                ->on('forms')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_abandon_tracking');
    }
};

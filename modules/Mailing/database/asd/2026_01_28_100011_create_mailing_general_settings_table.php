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
        Schema::create('mailing_general_settings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->unsignedBigInteger('user_id');
            $table->string('setting_key');
            $table->text('setting_value')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            // Unique constraint: one setting key per user
            $table->unique(['user_id', 'setting_key'], 'mailing_settings_user_key_unique');

            // Indexes for faster queries
            $table->index('user_id');
            $table->index('setting_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mailing_general_settings');
    }
};

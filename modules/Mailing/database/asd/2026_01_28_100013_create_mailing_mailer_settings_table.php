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
        Schema::create('mailing_mailer_settings', function (Blueprint $table) {
            $table->id();
            $table->string('uid')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('from_email');
            $table->string('from_name')->nullable();
            $table->string('reply_to')->nullable();
            $table->string('return_path')->nullable();
            $table->string('bounce_email')->nullable();
            $table->string('complaint_email')->nullable();
            $table->boolean('dkim_enabled')->default(false);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('uid');
            $table->index('from_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mailing_mailer_settings');
    }
};

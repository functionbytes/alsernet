<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_log_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_log_link_id')->constrained('email_log_links')->cascadeOnDelete();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('clicked_at');

            $table->index(['email_log_link_id', 'clicked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_log_clicks');
    }
};

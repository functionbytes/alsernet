<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_bounce_logs', function (Blueprint $table) {
            $table->id();
            $table->char('runtime_message_id', 191)->nullable();
            $table->char('message_id', 191)->nullable();
            $table->char('bounce_type', 191);
            $table->text('raw');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->text('status_code')->nullable();
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_bounce_logs');
    }
};

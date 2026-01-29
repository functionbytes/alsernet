<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_open_logs', function (Blueprint $table) {
            $table->id();
            $table->char('message_id', 191);
            $table->index('message_id');
            $table->char('ip_address', 191)->nullable();
            $table->index('ip_address');
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Foreign Keys
            $table->foreign('message_id')
                ->references('message_id')
                ->on('mailing_tracking_logs')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_open_logs');
    }
};

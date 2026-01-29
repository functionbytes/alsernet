<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_unsubscribe_logs', function (Blueprint $table) {
            $table->id();
            $table->char('message_id', 191)->nullable();
            $table->index('message_id');
            $table->text('ip_address')->nullable();
            $table->char('user_agent', 191)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->foreignId('subscriber_id')->nullable();

            // Foreign Keys
            $table->foreign('subscriber_id')
                ->references('id')
                ->on('mailing_subscribers')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_unsubscribe_logs');
    }
};

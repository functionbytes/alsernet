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
        Schema::create('whatsapp_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('whatsapp_id')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('event_type')->nullable(); // messages, messages.update, qrcode, connection, etc.
            $table->longText('payload'); // Full webhook payload as JSON
            $table->boolean('processed')->default(false);
            $table->text('error')->nullable(); // Error message if processing failed
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->foreign('whatsapp_id')
                ->references('id')
                ->on('chat_channel_whatsapps')
                ->onDelete('cascade');

            $table->index('whatsapp_id');
            $table->index('phone_number');
            $table->index('event_type');
            $table->index('processed');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_webhook_logs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_sending_server_bounce_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->index();
            $table->string('bounce_type', 16)->default('soft'); // hard|soft
            $table->string('message_id')->nullable()->index();
            $table->text('description')->nullable();
            $table->foreignId('bounce_handler_id')
                ->nullable()
                ->constrained('campaign_sending_server_bounce_handlers')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_sending_server_bounce_logs');
    }
};

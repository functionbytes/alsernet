<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_tracking_logs_archive', function (Blueprint $table): void {
            $table->id();
            $table->string('uid', 36)->nullable();
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->unsignedBigInteger('subscriber_id')->nullable();
            $table->unsignedBigInteger('sending_server_id')->nullable();
            $table->string('email')->nullable();
            $table->string('message_id')->nullable();
            $table->string('runtime_message_id')->nullable();
            $table->string('status', 20)->nullable();
            $table->text('error')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['campaign_id', 'created_at']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_tracking_logs_archive');
    }
};

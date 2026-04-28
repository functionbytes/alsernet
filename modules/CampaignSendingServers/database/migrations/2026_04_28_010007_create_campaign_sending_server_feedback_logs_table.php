<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_sending_server_feedback_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->index();
            $table->string('message_id')->nullable()->index();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('feedback_loop_handler_id')->nullable();
            $table->timestamps();

            $table->index('feedback_loop_handler_id', 'cssfl_handler_id_idx');
            $table->foreign('feedback_loop_handler_id', 'cssfl_handler_id_fk')
                ->references('id')
                ->on('campaign_sending_server_feedback_handlers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_sending_server_feedback_logs');
    }
};

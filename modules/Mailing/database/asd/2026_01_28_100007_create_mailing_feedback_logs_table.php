<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mailing_feedback_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->foreignId('feedback_loop_handler_id')->constrained('mailing_feedback_loop_handlers')->onDelete('cascade');

            $table->string('email');
            $table->string('complaint_type')->nullable(); // spam, abuse, not-spam, fraud
            $table->text('feedback_report')->nullable();
            $table->text('raw_message')->nullable();
            $table->json('parsed_data')->nullable();

            $table->boolean('subscriber_updated')->default(false);
            $table->boolean('admin_notified')->default(false);

            $table->timestamps();

            // Indexes
            $table->index('feedback_loop_handler_id');
            $table->index('email');
            $table->index('complaint_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailing_feedback_logs');
    }
};

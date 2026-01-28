<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mailing_bounce_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->foreignId('bounce_handler_id')->constrained('mailing_bounce_handlers')->onDelete('cascade');

            $table->string('email');
            $table->enum('bounce_type', ['hard', 'soft', 'complaint', 'unknown'])->default('unknown');
            $table->string('reason')->nullable();
            $table->text('raw_message')->nullable();
            $table->json('parsed_data')->nullable();

            $table->boolean('subscriber_updated')->default(false);

            $table->timestamps();

            // Indexes
            $table->index('bounce_handler_id');
            $table->index('email');
            $table->index('bounce_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailing_bounce_logs');
    }
};

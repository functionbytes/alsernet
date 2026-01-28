<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mailing_feedback_loop_handlers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Handler identification
            $table->string('name');
            $table->enum('type', ['imap', 'pop3', 'webhook', 'api'])->default('imap');
            $table->enum('status', ['active', 'inactive', 'error'])->default('inactive');

            // IMAP/POP3 Connection details
            $table->string('host')->nullable();
            $table->integer('port')->nullable();
            $table->enum('protocol', ['imap', 'pop3'])->nullable();
            $table->enum('encryption', ['ssl', 'tls', 'none'])->nullable();
            $table->string('username')->nullable();
            $table->text('password')->nullable(); // Will be encrypted in model
            $table->string('email')->nullable();

            // Webhook/API configuration
            $table->string('webhook_token')->nullable();
            $table->string('webhook_secret')->nullable();
            $table->string('provider')->nullable(); // gmail, yahoo, aol, outlook, custom
            $table->string('feedback_type')->nullable(); // abuse, arf

            // Processing configuration
            $table->json('rules')->nullable();
            $table->boolean('auto_check')->default(true);
            $table->integer('check_interval')->default(60); // minutes
            $table->boolean('delete_after_process')->default(false);
            $table->boolean('auto_unsubscribe')->default(true);
            $table->boolean('notify_admin')->default(true);

            // Statistics
            $table->integer('complaints_processed')->default(0);
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_complaint_at')->nullable();

            // Error tracking
            $table->text('last_error')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('status');
            $table->index('type');
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailing_feedback_loop_handlers');
    }
};

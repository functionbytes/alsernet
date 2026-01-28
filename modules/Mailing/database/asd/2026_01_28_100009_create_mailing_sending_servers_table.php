<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mailing_sending_servers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Server identification
            $table->string('name');
            $table->enum('type', ['smtp', 'sendgrid', 'mailgun', 'ses', 'postmark', 'sparkpost', 'mailjet'])->default('smtp');
            $table->enum('status', ['active', 'inactive', 'error'])->default('inactive');

            // SMTP Connection details
            $table->string('host')->nullable();
            $table->integer('port')->nullable();
            $table->enum('encryption', ['tls', 'ssl', 'none'])->nullable();
            $table->string('username')->nullable();
            $table->text('password')->nullable(); // Will be encrypted in model

            // API Keys & Configuration
            $table->text('api_key')->nullable(); // Will be encrypted in model
            $table->text('api_secret')->nullable(); // Will be encrypted in model
            $table->string('api_region')->nullable(); // For AWS SES, etc.

            // Sending Configuration
            $table->string('from_email')->nullable();
            $table->string('from_name')->nullable();
            $table->string('reply_to_email')->nullable();

            // Bounce & Feedback Loop handlers
            $table->foreignId('bounce_handler_id')->nullable()
                ->constrained('mailing_bounce_handlers')->onDelete('set null');
            $table->foreignId('feedback_loop_handler_id')->nullable()
                ->constrained('mailing_feedback_loop_handlers')->onDelete('set null');

            // Quota & Rate limiting
            $table->integer('quota_value')->default(0); // 0 = unlimited
            $table->integer('quota_base')->default(1);
            $table->enum('quota_unit', ['minute', 'hour', 'day'])->nullable();
            $table->integer('sending_limit_per_minute')->default(10);
            $table->integer('sending_limit_per_hour')->default(100);

            // Usage tracking
            $table->integer('emails_sent_today')->default(0);
            $table->integer('emails_sent_this_month')->default(0);
            $table->integer('total_emails_sent')->default(0);
            $table->decimal('bounce_rate', 5, 2)->default(0.00);
            $table->decimal('complaint_rate', 5, 2)->default(0.00);

            // Advanced options
            $table->json('options')->nullable();
            $table->boolean('allow_verify_domain')->default(false);
            $table->boolean('allow_verify_email')->default(false);
            $table->boolean('allow_custom_return_path')->default(false);

            // Monitoring
            $table->timestamp('last_connection_check_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('last_sent_at')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('status');
            $table->index('type');
            $table->index(['user_id', 'status']);
            $table->index('bounce_handler_id');
            $table->index('feedback_loop_handler_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailing_sending_servers');
    }
};

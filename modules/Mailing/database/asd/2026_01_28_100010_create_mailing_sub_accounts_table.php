<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mailing_sub_accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->foreignId('sending_server_id')->constrained('mailing_sending_servers')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Sub-account details
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'suspended', 'inactive'])->default('active');

            // Credentials
            $table->text('api_key')->nullable(); // Will be encrypted in model
            $table->string('username')->nullable();
            $table->string('email')->nullable();

            // Permissions
            $table->json('permissions')->nullable();
            $table->boolean('can_create_campaigns')->default(true);
            $table->boolean('can_manage_subscribers')->default(true);
            $table->boolean('can_view_reports')->default(true);
            $table->boolean('can_create_sending_servers')->default(false);

            // Quotas
            $table->integer('email_quota_per_day')->nullable();
            $table->integer('email_quota_per_month')->nullable();
            $table->integer('subscriber_quota')->nullable();
            $table->integer('campaign_quota')->nullable();

            // Usage tracking
            $table->integer('emails_sent_today')->default(0);
            $table->integer('emails_sent_this_month')->default(0);
            $table->integer('total_emails_sent')->default(0);

            // Access control
            $table->json('allowed_sending_server_ids')->nullable();
            $table->json('allowed_list_ids')->nullable();

            // Monitoring
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // Indexes
            $table->index('sending_server_id');
            $table->index('user_id');
            $table->index('status');
            $table->index(['sending_server_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailing_sub_accounts');
    }
};

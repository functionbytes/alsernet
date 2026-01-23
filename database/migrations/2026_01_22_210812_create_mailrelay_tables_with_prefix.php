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
        // Create mails_lists table
        if (! Schema::hasTable('mails_lists')) {
            Schema::create('mails_lists', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // Create mails_subscribers table
        if (! Schema::hasTable('mails_subscribers')) {
            Schema::create('mails_subscribers', function (Blueprint $table) {
                $table->ulid('id')->primary();
                $table->string('email')->unique();
                $table->string('name')->nullable();
                $table->foreignId('list_id')->nullable()->constrained('mails_lists')->onDelete('cascade');
                $table->string('status')->default('subscribed');
                $table->timestamps();
                $table->index('status');
                $table->index('list_id');
            });
        }

        // Create mails_campaigns table
        if (! Schema::hasTable('mails_campaigns')) {
            Schema::create('mails_campaigns', function (Blueprint $table) {
                $table->ulid('id')->primary();
                $table->string('name');
                $table->text('description')->nullable();
                $table->foreignId('list_id')->nullable()->constrained('mails_lists')->onDelete('cascade');
                $table->string('subject');
                $table->longText('body');
                $table->string('status')->default('draft');
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
                $table->index('status');
            });
        }

        // Create mails_import_jobs table
        if (! Schema::hasTable('mails_import_jobs')) {
            Schema::create('mails_import_jobs', function (Blueprint $table) {
                $table->ulid('id')->primary();
                $table->string('filename');
                $table->string('status')->default('pending');
                $table->integer('total_emails')->default(0);
                $table->integer('processed_emails')->default(0);
                $table->integer('valid_emails')->default(0);
                $table->integer('invalid_emails')->default(0);
                $table->json('report')->nullable();
                $table->json('options')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
                $table->index('status');
            });
        }

        // Create mails_campaign_analytics table
        if (! Schema::hasTable('mails_campaign_analytics')) {
            Schema::create('mails_campaign_analytics', function (Blueprint $table) {
                $table->id();
                $table->foreignUlid('campaign_id')->constrained('mails_campaigns')->onDelete('cascade');
                $table->integer('sent')->default(0);
                $table->integer('opened')->default(0);
                $table->integer('clicked')->default(0);
                $table->integer('bounced')->default(0);
                $table->integer('unsubscribed')->default(0);
                $table->timestamps();
            });
        }

        // Create mails_email_validations table
        if (! Schema::hasTable('mails_email_validations')) {
            Schema::create('mails_email_validations', function (Blueprint $table) {
                $table->id();
                $table->string('email');
                $table->boolean('is_valid')->default(false);
                $table->string('reason')->nullable();
                $table->timestamps();
                $table->index('email');
            });
        }

        // Create mails_groups table
        if (! Schema::hasTable('mails_groups')) {
            Schema::create('mails_groups', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // Create mails_group_subscriber table
        if (! Schema::hasTable('mails_group_subscriber')) {
            Schema::create('mails_group_subscriber', function (Blueprint $table) {
                $table->id();
                $table->foreignId('group_id')->constrained('mails_groups')->onDelete('cascade');
                $table->foreignUlid('subscriber_id')->constrained('mails_subscribers')->onDelete('cascade');
                $table->timestamps();
                $table->unique(['group_id', 'subscriber_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mails_group_subscriber');
        Schema::dropIfExists('mails_groups');
        Schema::dropIfExists('mails_email_validations');
        Schema::dropIfExists('mails_campaign_analytics');
        Schema::dropIfExists('mails_import_jobs');
        Schema::dropIfExists('mails_campaigns');
        Schema::dropIfExists('mails_subscribers');
        Schema::dropIfExists('mails_lists');
    }
};

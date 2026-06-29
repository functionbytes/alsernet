<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('helpdesk')->create('engagement_email_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inbox_id')->constrained('helpdesk_inboxes')->cascadeOnDelete();
            $table->string('name');
            $table->string('subject');
            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            $table->text('html_content')->nullable();
            $table->text('text_content')->nullable();
            $table->string('provider', 50)->default('mailchimp');
            $table->string('provider_list_id')->nullable();
            $table->string('provider_campaign_id')->nullable();
            $table->string('status', 30)->default('draft');
            $table->json('segment_conditions')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('open_count')->default(0);
            $table->unsignedInteger('click_count')->default(0);
            $table->unsignedInteger('bounce_count')->default(0);
            $table->unsignedInteger('unsubscribe_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['inbox_id', 'status']);
            $table->index(['provider', 'provider_campaign_id']);
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->dropIfExists('engagement_email_campaigns');
    }
};

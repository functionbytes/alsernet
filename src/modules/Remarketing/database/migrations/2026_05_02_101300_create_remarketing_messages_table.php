<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remarketing_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('remarketing_stores')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('remarketing_customers')->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('remarketing_campaigns')->nullOnDelete();
            $table->foreignId('automation_run_id')->nullable()->constrained('remarketing_automation_runs')->nullOnDelete();
            $table->string('email');
            $table->string('subject');
            $table->enum('status', ['queued', 'sent', 'delivered', 'opened', 'clicked', 'bounced', 'complained', 'failed', 'suppressed'])->default('queued');
            $table->string('provider', 30)->nullable();
            $table->string('provider_message_id')->nullable();
            $table->string('open_token', 64)->unique();
            $table->string('click_token', 64)->unique();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('bounced_at')->nullable();
            $table->enum('bounce_type', ['hard', 'soft'])->nullable();
            $table->timestamp('complained_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->decimal('revenue', 12, 2)->default(0);
            $table->timestamps();

            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'sent_at']);
            $table->index(['customer_id', 'sent_at']);
            $table->index('campaign_id');
            $table->index('automation_run_id');
            $table->index('provider_message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remarketing_messages');
    }
};

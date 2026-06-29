<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remarketing_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('remarketing_stores')->cascadeOnDelete();
            $table->string('name');
            $table->string('subject');
            $table->string('preheader')->nullable();
            $table->string('from_name', 100);
            $table->string('from_email');
            $table->foreignId('template_id')->nullable()->constrained('remarketing_templates')->nullOnDelete();
            $table->foreignId('segment_id')->nullable()->constrained('remarketing_segments')->nullOnDelete();
            $table->enum('status', ['draft', 'scheduled', 'sending', 'sent', 'cancelled', 'paused'])->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('recipients_total')->default(0);
            $table->unsignedInteger('sent')->default(0);
            $table->unsignedInteger('delivered')->default(0);
            $table->unsignedInteger('bounced')->default(0);
            $table->unsignedInteger('opened')->default(0);
            $table->unsignedInteger('clicked')->default(0);
            $table->unsignedInteger('unsubscribed')->default(0);
            $table->unsignedInteger('complained')->default(0);
            $table->decimal('revenue', 12, 2)->default(0);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'scheduled_at']);
            $table->index('template_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remarketing_campaigns');
    }
};

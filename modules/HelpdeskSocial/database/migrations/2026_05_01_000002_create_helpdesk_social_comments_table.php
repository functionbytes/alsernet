<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_social_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_account_id')->constrained('helpdesk_social_accounts')->cascadeOnDelete();
            $table->unsignedBigInteger('helpdesk_conversation_id')->nullable();
            $table->string('platform', 32);
            $table->string('external_comment_id');
            $table->string('external_post_id');
            $table->string('external_parent_id')->nullable(); // para respuestas a comentarios
            $table->string('external_user_id');
            $table->string('author_name')->nullable();
            $table->string('author_username')->nullable();
            $table->text('body');
            $table->string('intent', 32)->nullable(); // query, complaint, purchase_interest, spam, positive, neutral
            $table->decimal('intent_confidence', 3, 2)->nullable();
            $table->string('urgency', 16)->nullable(); // low, medium, high, critical
            $table->json('keywords')->nullable();
            $table->json('sentiment')->nullable();
            $table->string('status', 32)->default('pending'); // pending, replied, auto_replied, escalated, spam, resolved
            $table->string('reply_type', 32)->nullable(); // manual, auto, suggested
            $table->text('reply_body')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->unsignedBigInteger('replied_by_user_id')->nullable();
            $table->string('external_reply_id')->nullable();
            $table->boolean('is_hidden')->default(false);
            $table->boolean('is_spam')->default(false);
            $table->boolean('is_mention')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamp('posted_at');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['platform', 'external_comment_id']);
            $table->index(['social_account_id', 'status']);
            $table->index(['platform', 'external_post_id']);
            $table->index(['external_user_id', 'platform']);
            $table->index('intent');
            $table->index('status');
            $table->index('posted_at');
            $table->index('is_spam');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_social_comments');
    }
};

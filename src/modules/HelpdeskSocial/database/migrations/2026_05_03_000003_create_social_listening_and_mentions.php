<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_social_listening_keywords', function (Blueprint $table) {
            $table->id();
            $table->string('keyword');
            $table->json('platforms')->nullable(); // ['facebook', 'instagram', 'x']
            $table->string('match_type', 16)->default('contains'); // exact, contains, regex
            $table->string('sentiment_filter', 16)->nullable(); // positive, negative, neutral, any
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('helpdesk_social_mentions', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 32);
            $table->string('external_id');
            $table->string('author_name')->nullable();
            $table->string('author_username')->nullable();
            $table->text('body');
            $table->string('url')->nullable();
            $table->json('matched_keywords')->nullable();
            $table->string('sentiment', 16)->nullable(); // positive, negative, neutral, mixed
            $table->decimal('sentiment_score', 4, 3)->nullable();
            $table->unsignedInteger('engagement_count')->default(0); // likes + shares + replies
            $table->string('status', 16)->default('new'); // new, reviewed, escalated, ignored
            $table->timestamp('discovered_at');
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->unique(['platform', 'external_id']);
            $table->index(['status', 'discovered_at']);
            $table->index('sentiment');
            $table->index('platform');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_social_mentions');
        Schema::dropIfExists('helpdesk_social_listening_keywords');
    }
};

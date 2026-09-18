<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_social_intents', function (Blueprint $table) {
            $table->id();
            $table->morphs('classifiable'); // social_comments o conversations
            $table->string('platform', 32);
            $table->string('intent', 32); // query, complaint, purchase_interest, spam, positive, neutral, other
            $table->decimal('confidence', 3, 2)->default(0.0);
            $table->string('classifier', 16)->default('rules'); // rules, openai, hybrid
            $table->string('urgency', 16)->nullable(); // low, medium, high, critical
            $table->json('keywords_matched')->nullable();
            $table->json('entities')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamp('classified_at');
            $table->timestamps();

            $table->index('intent');
            $table->index(['platform', 'intent']);
            $table->index('classifier');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_social_intents');
    }
};

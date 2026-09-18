<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_social_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('platform', 32)->nullable(); // null = all platforms
            $table->text('body');
            $table->json('variables')->nullable(); // [{name, description, example}]
            $table->json('quick_replies')->nullable(); // para Messenger/IG
            $table->string('category', 32)->nullable(); // greeting, pricing, support, complaint, etc.
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedBigInteger('usage_count')->default(0);
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['platform', 'category']);
            $table->index('is_active');
            $table->index('is_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_social_templates');
    }
};

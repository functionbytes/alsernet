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
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subject', 200);
            $table->text('html_content');
            $table->text('text_content')->nullable();
            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            $table->string('reply_to')->nullable();
            $table->enum('type', ['transactional', 'marketing', 'automated'])->default('marketing');
            $table->boolean('is_active')->default(true);
            $table->json('variables')->nullable(); // Available template variables
            $table->string('category')->nullable();
            $table->string('preview_text')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->integer('times_used')->default(0);
            $table->timestamps();

            $table->index('is_active');
            $table->index('type');
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};

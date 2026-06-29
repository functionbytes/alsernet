<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mailer_endpoints', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('source');
            $table->string('type');
            $table->text('description')->nullable();
            $table->foreignId('mailer_template_id')->nullable()->constrained('mailer_templates')->onDelete('set null');
            $table->foreignId('lang_id')->constrained('langs')->onDelete('cascade');
            $table->json('expected_variables')->nullable();
            $table->json('required_variables')->nullable();
            $table->json('variable_mappings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('api_token')->unique();
            $table->integer('requests_count')->default(0);
            $table->timestamp('last_request_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('slug');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailer_endpoints');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remarketing_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->nullable()->constrained('remarketing_stores')->nullOnDelete();
            $table->string('name');
            $table->enum('type', ['campaign', 'automation', 'transactional'])->default('campaign');
            $table->string('subject')->nullable();
            $table->string('preheader')->nullable();
            $table->longText('html_content');
            $table->json('json_content')->nullable();
            $table->text('thumbnail_url')->nullable();
            $table->tinyInteger('is_global')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('store_id');
            $table->index(['type', 'is_global']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remarketing_templates');
    }
};

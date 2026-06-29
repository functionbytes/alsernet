<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_layouts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('name');
            $table->longText('content')->nullable();
            $table->boolean('default')->default(false);
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('campaign_template_categories', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('name');
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('campaign_templates', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('name');
            $table->string('subject')->nullable();
            $table->longText('content')->nullable(); // JSON estructurado del builder o HTML directo
            $table->longText('html')->nullable();    // HTML compilado
            $table->longText('plain')->nullable();
            $table->string('image')->nullable();
            $table->boolean('shared')->default(false);
            $table->foreignId('layout_id')
                ->nullable()
                ->constrained('campaign_layouts')
                ->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('campaign_templates_categories', function (Blueprint $table): void {
            $table->foreignId('template_id')
                ->constrained('campaign_templates')
                ->cascadeOnDelete();
            $table->foreignId('category_id')
                ->constrained('campaign_template_categories')
                ->cascadeOnDelete();
            $table->primary(['template_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_templates_categories');
        Schema::dropIfExists('campaign_templates');
        Schema::dropIfExists('campaign_template_categories');
        Schema::dropIfExists('campaign_layouts');
    }
};

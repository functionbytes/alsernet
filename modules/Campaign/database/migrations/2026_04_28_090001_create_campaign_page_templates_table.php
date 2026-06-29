<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Page Templates — wrapper 1:1 sobre campaign_templates, para plantillas de
 * página/landing/funnel editables con el builder BuilderJS portado de acellemail.
 *
 * Cada fila posee exactamente un campaign_templates vía template_id (cascade delete).
 * Toda la lógica de escritura vive en PageTemplateService (Model = schema + relaciones).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_page_templates', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('name');
            $table->foreignId('template_id')
                ->constrained('campaign_templates')
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_page_templates');
    }
};

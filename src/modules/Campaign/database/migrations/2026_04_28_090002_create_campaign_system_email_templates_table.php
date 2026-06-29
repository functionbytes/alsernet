<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * System Email Templates — wrapper 1:1 sobre campaign_templates, para las
 * plantillas de email del admin editables con el builder BuilderJS
 * (builderTemplateKind='email'). Espejo de campaign_page_templates.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_system_email_templates', function (Blueprint $table): void {
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
        Schema::dropIfExists('campaign_system_email_templates');
    }
};

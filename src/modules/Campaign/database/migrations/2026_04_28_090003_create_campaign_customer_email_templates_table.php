<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer Email Templates — copias de trabajo (editables) creadas por el
 * usuario a partir de la galería de SystemEmailTemplate. Wrapper 1:1 sobre
 * campaign_templates. Portado de acellemail (CustomerEmailTemplate); en este
 * destino no-SaaS son globales (sin customer_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_customer_email_templates', function (Blueprint $table): void {
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
        Schema::dropIfExists('campaign_customer_email_templates');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Funnels — embudos de páginas (landing/optin/thank-you/...). Portado de
 * acellemail (Funnel + FunnelStep). Cada funnel tiene pasos ordenados; cada
 * paso envuelve un Template editable con el builder (kind=page), partiendo de
 * las page-templates ya sembradas. Global (no-SaaS); se omiten products/domains.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_funnels', function (Blueprint $table): void {
            $table->id();
            $table->char('uid', 36)->unique();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('mail_list_id')->nullable()
                ->constrained('campaign_maillists')->nullOnDelete();
            $table->json('settings')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('campaign_funnel_steps', function (Blueprint $table): void {
            $table->id();
            $table->char('uid', 36)->unique();
            $table->foreignId('funnel_id')->constrained('campaign_funnels')->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('landing');
            $table->foreignId('template_id')->nullable()
                ->constrained('campaign_templates')->nullOnDelete();
            $table->integer('sort_order')->default(0);
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_funnel_steps');
        Schema::dropIfExists('campaign_funnels');
    }
};

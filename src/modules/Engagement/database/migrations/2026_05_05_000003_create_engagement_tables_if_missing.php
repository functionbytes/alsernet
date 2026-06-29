<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fresh-install fallback: creates the 13 Engagement tables if they don't already
 * exist (the rename migration before this only renames if old tables exist).
 *
 * In existing installs this migration is a no-op since the rename migration
 * has already moved the tables.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $this->createEvents();
        $this->createVisitorScores();
        $this->createVisitorContexts();
        $this->createTriggerRules();
        $this->createPersonalizationRules();
        $this->createRecommendationProfiles();
        $this->createCatalogProducts();
        $this->createPlatformIntegrations();
        $this->createWebhookLogs();
        $this->createAutomationFlows();
        $this->createAutomationRuns();
        $this->createConversionGoals();
        $this->createAuditLogs();
    }

    public function down(): void
    {
        // No-op — drops handled by individual migrations or rename rollback.
    }

    private function createEvents(): void
    {
        if (Schema::connection('helpdesk')->hasTable('engagement_events')) {
            return;
        }
        Schema::connection('helpdesk')->create('engagement_events', function (Blueprint $table) {
            $table->id();
            $table->string('session_token', 64);
            $table->unsignedBigInteger('inbox_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('event_name', 100);
            $table->string('platform', 20)->nullable();
            $table->json('properties')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
            $table->index(['session_token', 'occurred_at']);
            $table->index(['customer_id', 'event_name', 'occurred_at']);
            $table->index(['inbox_id', 'event_name', 'occurred_at']);
            $table->index(['event_name', 'occurred_at']);
            $table->index(['platform', 'event_name', 'occurred_at']);
        });
    }

    private function createVisitorScores(): void
    {
        if (Schema::connection('helpdesk')->hasTable('engagement_visitor_scores')) {
            return;
        }
        Schema::connection('helpdesk')->create('engagement_visitor_scores', function (Blueprint $table) {
            $table->id();
            $table->string('session_token', 64)->unique();
            $table->unsignedBigInteger('inbox_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedTinyInteger('score')->default(0);
            $table->enum('segment', ['cold', 'warm', 'hot'])->default('cold');
            $table->timestamps();
            $table->index(['inbox_id', 'segment']);
        });
    }

    private function createVisitorContexts(): void
    {
        if (Schema::connection('helpdesk')->hasTable('engagement_visitor_contexts')) {
            return;
        }
        Schema::connection('helpdesk')->create('engagement_visitor_contexts', function (Blueprint $table) {
            $table->id();
            $table->string('session_token', 64)->unique();
            $table->unsignedBigInteger('inbox_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->json('context')->nullable();
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 150)->nullable();
            $table->string('utm_term', 150)->nullable();
            $table->string('utm_content', 150)->nullable();
            $table->string('referrer_host', 255)->nullable();
            $table->timestamps();
            $table->index(['utm_source', 'utm_campaign']);
        });
    }

    private function createTriggerRules(): void
    {
        if (Schema::connection('helpdesk')->hasTable('engagement_trigger_rules')) {
            return;
        }
        Schema::connection('helpdesk')->create('engagement_trigger_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inbox_id');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->json('conditions');
            $table->json('action');
            $table->unsignedTinyInteger('priority')->default(50);
            $table->string('variant_group', 50)->nullable();
            $table->unsignedTinyInteger('variant_weight')->default(50);
            $table->unsignedTinyInteger('fires_per_session')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['inbox_id', 'is_active', 'priority']);
        });
    }

    private function createPersonalizationRules(): void
    {
        if (Schema::connection('helpdesk')->hasTable('engagement_personalization_rules')) {
            return;
        }
        Schema::connection('helpdesk')->create('engagement_personalization_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inbox_id');
            $table->string('name', 255);
            $table->string('selector', 500);
            $table->string('variant_group', 50)->nullable();
            $table->unsignedTinyInteger('variant_weight')->default(50);
            $table->json('conditions')->nullable();
            $table->json('mutation');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['inbox_id', 'is_active']);
        });
    }

    private function createRecommendationProfiles(): void
    {
        if (Schema::connection('helpdesk')->hasTable('engagement_recommendation_profiles')) {
            return;
        }
        Schema::connection('helpdesk')->create('engagement_recommendation_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->unique();
            $table->json('viewed_products')->nullable();
            $table->json('categories')->nullable();
            $table->json('cart_history')->nullable();
            $table->timestamps();
        });
    }

    private function createCatalogProducts(): void
    {
        if (Schema::connection('helpdesk')->hasTable('engagement_catalog_products')) {
            return;
        }
        Schema::connection('helpdesk')->create('engagement_catalog_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inbox_id');
            $table->string('platform_product_id', 100);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->string('url')->nullable();
            $table->string('image_url')->nullable();
            $table->string('category', 150)->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->unique(['inbox_id', 'platform_product_id']);
            $table->index(['inbox_id', 'is_active', 'category']);
        });
    }

    private function createPlatformIntegrations(): void
    {
        if (Schema::connection('helpdesk')->hasTable('engagement_platform_integrations')) {
            return;
        }
        Schema::connection('helpdesk')->create('engagement_platform_integrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inbox_id');
            $table->enum('platform', ['prestashop', 'shopify', 'woocommerce', 'custom']);
            $table->string('store_url')->nullable();
            $table->string('webhook_secret', 64);
            $table->json('config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_event_at')->nullable();
            $table->timestamps();
            $table->unique(['inbox_id', 'platform']);
            $table->index(['platform', 'is_active']);
        });
    }

    private function createWebhookLogs(): void
    {
        if (Schema::connection('helpdesk')->hasTable('engagement_webhook_logs')) {
            return;
        }
        Schema::connection('helpdesk')->create('engagement_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('platform_integration_id');
            $table->string('platform', 20);
            $table->string('topic', 100);
            $table->json('payload');
            $table->enum('status', ['received', 'processed', 'failed', 'dead'])->default('received');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->index(['platform_integration_id', 'status', 'created_at'], 'ewl_pi_status_created_idx');
            $table->index(['status', 'attempts']);
        });
    }

    private function createAutomationFlows(): void
    {
        if (Schema::connection('helpdesk')->hasTable('engagement_automation_flows')) {
            return;
        }
        Schema::connection('helpdesk')->create('engagement_automation_flows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inbox_id');
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->enum('trigger_type', ['manual', 'event', 'schedule'])->default('manual');
            $table->string('trigger_event', 100)->nullable();
            $table->json('nodes');
            $table->json('edges');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['inbox_id', 'is_active']);
            $table->index(['trigger_type', 'trigger_event']);
        });
    }

    private function createAutomationRuns(): void
    {
        if (Schema::connection('helpdesk')->hasTable('engagement_automation_runs')) {
            return;
        }
        Schema::connection('helpdesk')->create('engagement_automation_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('flow_id');
            $table->string('session_token', 64);
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('current_node_id', 50)->nullable();
            $table->enum('status', ['running', 'completed', 'failed', 'abandoned'])->default('running');
            $table->json('context')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->index(['session_token', 'status']);
            $table->index(['flow_id', 'status']);
        });
    }

    private function createConversionGoals(): void
    {
        if (Schema::connection('helpdesk')->hasTable('engagement_conversion_goals')) {
            return;
        }
        Schema::connection('helpdesk')->create('engagement_conversion_goals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inbox_id');
            $table->string('name', 150);
            $table->enum('type', ['event', 'url_visit', 'segment_change']);
            $table->string('event_name', 100)->nullable();
            $table->string('url_pattern', 255)->nullable();
            $table->string('target_segment', 20)->nullable();
            $table->decimal('value', 12, 2)->default(0);
            $table->json('funnel_steps')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['inbox_id', 'is_active']);
        });
    }

    private function createAuditLogs(): void
    {
        if (Schema::connection('helpdesk')->hasTable('engagement_audit_logs')) {
            return;
        }
        Schema::connection('helpdesk')->create('engagement_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action', 50);
            $table->string('entity_type', 50);
            $table->unsignedBigInteger('entity_id');
            $table->json('changes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->index(['entity_type', 'entity_id']);
            $table->index(['user_id', 'created_at']);
        });
    }
};

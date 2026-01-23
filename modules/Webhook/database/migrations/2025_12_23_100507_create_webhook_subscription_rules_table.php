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
        Schema::create('webhook_subscription_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')
                ->constrained('webhook_subscriptions')
                ->onDelete('cascade');

            $table->enum('rule_type', ['all', 'any'])->default('all')
                ->comment('all = AND, any = OR');

            $table->json('conditions')->comment('
                [
                    {"field": "data.order.total", "operator": "gt", "value": 100},
                    {"field": "data.customer.email", "operator": "contains", "value": "@example.com"}
                ]
            ');

            $table->json('transform_template')->nullable()->comment('
                {
                    "customerEmail": "data.customer.email",
                    "orderTotal": "data.order.total"
                }
            ');

            $table->timestamps();

            $table->index('subscription_id', 'idx_rules_subscription');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_subscription_rules');
    }
};

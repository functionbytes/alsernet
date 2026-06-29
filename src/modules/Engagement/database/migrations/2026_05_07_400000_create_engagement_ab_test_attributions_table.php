<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('engagement_ab_test_attributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ab_test_id')->constrained('engagement_ab_tests')->cascadeOnDelete();
            $table->foreignId('ab_test_variant_id')->constrained('engagement_ab_test_variants')->cascadeOnDelete();
            $table->string('session_token', 64);
            $table->unsignedBigInteger('external_order_id');
            $table->unsignedBigInteger('external_customer_id')->nullable();
            $table->decimal('revenue', 12, 4);
            $table->string('currency', 3)->default('EUR');
            $table->json('order_payload')->nullable();
            $table->timestamp('attributed_at');
            $table->index(['ab_test_id', 'ab_test_variant_id'], 'eng_abattr_test_variant_idx');
            $table->index(['session_token', 'attributed_at'], 'eng_abattr_session_attributed_idx');
            $table->unique(['ab_test_variant_id', 'external_order_id'], 'uq_variant_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('engagement_ab_test_attributions');
    }
};

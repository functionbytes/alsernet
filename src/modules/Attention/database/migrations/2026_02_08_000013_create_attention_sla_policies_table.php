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
        Schema::create('attention_sla_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('response_time')->comment('minutes');
            $table->integer('resolution_time')->comment('minutes');
            $table->integer('closure_time')->comment('minutes');
            $table->boolean('business_hours_only')->default(false);
            $table->json('business_hours')->nullable();
            $table->string('timezone')->default('UTC');
            $table->json('type_multipliers')->nullable();
            $table->boolean('enable_escalation')->default(false);
            $table->integer('escalation_threshold_percent')->nullable();
            $table->json('escalation_recipients')->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index('is_default');
            $table->index('active');
        });

        Schema::create('attention_sla_breaches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attention_id')->constrained('attentions')->cascadeOnDelete();
            $table->foreignId('sla_policy_id')->constrained('attention_sla_policies')->cascadeOnDelete();
            $table->string('breach_type');
            $table->integer('minutes_over')->default(0);
            $table->boolean('escalated')->default(false);
            $table->timestamp('escalated_at')->nullable();
            $table->boolean('resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('attention_id');
            $table->index('sla_policy_id');
            $table->index('breach_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attention_sla_breaches');
        Schema::dropIfExists('attention_sla_policies');
    }
};

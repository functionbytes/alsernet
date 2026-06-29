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
        if (Schema::hasTable('attention_sla_breaches')) {
            return;
        }

        Schema::create('attention_sla_breaches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attention_id')->constrained('attentions')->onDelete('cascade');
            $table->foreignId('sla_policy_id')->constrained('attention_sla_policies')->onDelete('cascade');
            $table->enum('breach_type', ['response', 'resolution', 'closure']);
            $table->integer('minutes_over');
            $table->boolean('escalated')->default(false);
            $table->timestamp('escalated_at')->nullable();
            $table->boolean('resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Índices
            $table->index('attention_id');
            $table->index('sla_policy_id');
            $table->index('breach_type');
            $table->index('escalated');
            $table->index('resolved');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attention_sla_breaches');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_sla_breaches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('sla_policy_id')->constrained('document_sla_policies')->cascadeOnDelete();
            $table->string('breach_type');
            $table->integer('minutes_over')->default(0);
            $table->boolean('escalated')->default(false);
            $table->timestamp('escalated_at')->nullable();
            $table->boolean('resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('document_id');
            $table->index('sla_policy_id');
            $table->index('breach_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_sla_breaches');
    }
};

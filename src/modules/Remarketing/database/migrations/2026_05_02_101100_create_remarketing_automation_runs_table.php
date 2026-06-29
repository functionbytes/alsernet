<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remarketing_automation_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->constrained('remarketing_automations')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('remarketing_customers')->cascadeOnDelete();
            $table->unsignedTinyInteger('current_step')->default(0);
            $table->enum('status', ['running', 'completed', 'cancelled', 'failed'])->default('running');
            $table->json('context')->nullable();
            $table->timestamp('next_step_at')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['automation_id', 'customer_id']);
            $table->index(['status', 'next_step_at']);
            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remarketing_automation_runs');
    }
};

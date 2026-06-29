<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remarketing_automations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('remarketing_stores')->cascadeOnDelete();
            $table->string('name');
            $table->string('trigger', 60);
            $table->json('trigger_config')->nullable();
            $table->enum('status', ['active', 'paused', 'draft'])->default('draft');
            $table->unsignedInteger('runs_total')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['store_id', 'trigger', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remarketing_automations');
    }
};

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
        Schema::create('supplier_automation_chains', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 36)->unique();

            $table->string('name', 100);
            $table->text('description')->nullable();

            // Pipeline definition
            $table->json('chain_definition');
            // Structure of stages and conditions

            // Configuration
            $table->string('fail_strategy', 30)->default('stop_chain');
            // 'stop_chain', 'skip_stage', 'continue', 'rollback'

            $table->boolean('parallel_stages')->default(false);
            $table->integer('max_parallel')->default(3);

            $table->integer('timeout_minutes')->default(180);

            $table->boolean('is_enabled')->default(true);

            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_automation_chains');
    }
};

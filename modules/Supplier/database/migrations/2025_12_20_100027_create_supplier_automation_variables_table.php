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
        Schema::create('supplier_automation_variables', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 36)->unique();

            $table->string('name', 100);
            $table->text('description')->nullable();

            // Scope
            $table->string('scope', 30)->default('global');
            // 'global', 'supplier', 'source', 'workflow'
            $table->bigInteger('scope_id')->nullable();
            // ID of the scope element (NULL for global)

            // Value
            $table->string('variable_type', 30);
            // 'string', 'number', 'boolean', 'json', 'secret', 'expression'
            $table->text('value')->nullable();
            $table->binary('encrypted_value')->nullable();
            // For secrets

            // Validation
            $table->string('validation_regex', 255)->nullable();
            $table->json('allowed_values')->nullable();

            // Behavior
            $table->boolean('is_required')->default(false);
            $table->text('default_value')->nullable();

            // Protection
            $table->boolean('is_system')->default(false);
            // Not editable by users
            $table->boolean('is_sensitive')->default(false);
            // Hide in logs

            $table->timestampsTz();

            // Indexes
            $table->unique(['scope', 'scope_id', 'name'], 'idx_82201');
            $table->index(['scope', 'scope_id'], 'variables_scope_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_automation_variables');
    }
};

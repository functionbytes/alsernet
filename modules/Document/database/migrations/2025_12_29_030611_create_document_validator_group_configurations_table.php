<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Configuraciones específicas por grupo de validadores.
     * Permite personalizar comportamientos como aprobación automática,
     * acciones de email, workflows, etc. por grupo.
     */
    public function up(): void
    {
        Schema::create('document_validator_group_configurations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('validator_group_id');
            $table->foreign('validator_group_id', 'dvgc_group_fk')
                ->references('id')
                ->on('document_validator_groups')
                ->onDelete('cascade');
            $table->string('key')->comment('Configuration key (e.g., enable_auto_approval)');
            $table->string('label')->comment('Display label');
            $table->text('description')->nullable()->comment('Description of the configuration');
            $table->boolean('value')->default(false)->comment('Configuration value');
            $table->string('category')->nullable()->comment('Category (e.g., email_actions, workflow)');
            $table->integer('order')->default(0)->comment('Sort order');
            $table->boolean('is_active')->default(true)->comment('Whether this config is active');
            $table->timestamps();

            // Indexes
            $table->unique(['validator_group_id', 'key'], 'idx_vg_key_unique');
            $table->index(['validator_group_id', 'category'], 'idx_vg_category');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_validator_group_configurations');
    }
};

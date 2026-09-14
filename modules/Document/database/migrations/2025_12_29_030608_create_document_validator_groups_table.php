<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Crea la tabla document_validator_groups que define grupos de validadores
     * para validación polimórfica de documentos en múltiples etapas.
     */
    public function up(): void
    {
        Schema::create('document_validator_groups', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('name', 191);
            $table->string('key', 100)->unique();
            $table->text('description')->nullable();
            $table->enum('assignment_mode', ['manual', 'round_robin', 'load_balanced'])->default('manual');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('key');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_validator_groups');
    }
};

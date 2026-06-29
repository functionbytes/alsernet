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
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('field_key')->unique(); // Unique identifier for the field
            $table->enum('type', ['text', 'number', 'date', 'boolean', 'select'])->default('text');
            $table->json('options')->nullable(); // Options for select type
            $table->boolean('is_required')->default(false);
            $table->string('default_value')->nullable();
            $table->string('placeholder')->nullable();
            $table->text('help_text')->nullable();
            $table->json('validation_rules')->nullable();
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('field_key');
            $table->index('type');
            $table->index('is_active');
            $table->index('display_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_fields');
    }
};

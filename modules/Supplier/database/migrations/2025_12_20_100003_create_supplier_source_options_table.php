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
        Schema::create('supplier_source_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained('supplier_sources')->onDelete('cascade');
            $table->string('option_key', 100)->comment('Configuration option key');
            $table->text('option_value')->nullable()->comment('Configuration option value');
            $table->string('option_type', 50)->default('string')->comment('Data type: string, url, json, path');
            $table->boolean('is_required')->default(false)->comment('Is this option required?');
            $table->timestamps();

            $table->unique(['source_id', 'option_key'], 'idx_99013');
            $table->index('source_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_source_options');
    }
};

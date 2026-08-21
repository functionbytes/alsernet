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
        Schema::create('supplier_variant_characteristics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('erp_id')->unique()->comment('ID Oracle w_perfiles_prod');
            $table->unsignedBigInteger('erp_article_id')->comment('IDARTICULO Oracle');
            $table->unsignedBigInteger('erp_model_id')->nullable()->comment('IDMODELO Oracle (desnormalizado)');
            $table->foreignId('characteristic_id')->constrained('supplier_erp_characteristics')->cascadeOnDelete();
            $table->foreignId('value_id')->constrained('supplier_erp_characteristic_values')->cascadeOnDelete();
            $table->integer('orden')->nullable();
            $table->boolean('estado')->default(true);
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->index('erp_article_id');
            $table->index('erp_model_id');
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_variant_characteristics');
    }
};

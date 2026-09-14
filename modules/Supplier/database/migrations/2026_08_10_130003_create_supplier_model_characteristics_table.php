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
        Schema::create('supplier_model_characteristics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('erp_id')->unique()->comment('ID Oracle w_caracteristicas_orden');
            $table->unsignedBigInteger('erp_model_id')->comment('IDMODELO Oracle');
            $table->foreignId('characteristic_id')->constrained('supplier_erp_characteristics')->cascadeOnDelete();
            $table->integer('orden')->nullable();
            $table->boolean('estado')->default(true);
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->index('erp_model_id');
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_model_characteristics');
    }
};

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
        Schema::create('supplier_erp_characteristic_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('erp_id')->unique()->comment('ID_VALOR Oracle w_valores_prod');
            $table->foreignId('characteristic_id')->nullable()->constrained('supplier_erp_characteristics')->nullOnDelete();
            $table->string('nombre', 255);
            $table->boolean('estado')->default(true);
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->index('characteristic_id');
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_erp_characteristic_values');
    }
};

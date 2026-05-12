<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remarketing_consumable_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('remarketing_stores')->cascadeOnDelete();
            $table->unsignedBigInteger('external_product_id');
            $table->unsignedSmallInteger('reorder_days');
            $table->string('name')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'external_product_id'], 'rmkt_consdef_store_product_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remarketing_consumable_definitions');
    }
};

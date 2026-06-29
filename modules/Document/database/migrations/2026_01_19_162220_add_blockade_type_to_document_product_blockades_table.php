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
        Schema::table('document_product_blockades', function (Blueprint $table) {
            $table->string('blockade_type')->nullable()->after('document_type_id')->comment('PrestaShop label that triggered this blockade (DNI, CORTA, BALINES45, etc.)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_product_blockades', function (Blueprint $table) {
            $table->dropColumn('blockade_type');
        });
    }
};

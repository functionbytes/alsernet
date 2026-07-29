<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_ai_contents', function (Blueprint $table) {
            $table->json('sources_history')->nullable()->after('sources_used')
                ->comment('Historial de fuentes web de regeneraciones anteriores (máx. 10 entradas)');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_ai_contents', function (Blueprint $table) {
            $table->dropColumn('sources_history');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Agrega campo archived_at para implementar política de retención de datos
     * según Ley General de Archivos (Ley 594/2000)
     */
    public function up(): void
    {
        Schema::table('attentions', function (Blueprint $table) {
            $table->timestamp('archived_at')
                ->nullable()
                ->after('deleted_at')
                ->comment('Fecha de archivado según Ley 594/2000');

            // Índice para consultas de archivo
            $table->index('archived_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attentions', function (Blueprint $table) {
            $table->dropIndex(['archived_at']);
            $table->dropColumn('archived_at');
        });
    }
};

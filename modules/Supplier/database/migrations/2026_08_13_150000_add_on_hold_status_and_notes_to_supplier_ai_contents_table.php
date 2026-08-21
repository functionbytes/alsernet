<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE supplier_ai_contents MODIFY COLUMN status ENUM(
            'pending_generation',
            'generating',
            'pending_validation',
            'in_review',
            'needs_revision',
            'validated',
            'published',
            'published_hidden',
            'rejected',
            'error_insufficient_info',
            'error_source_unavailable',
            'error_generation_failed',
            'on_hold'
        ) NOT NULL DEFAULT 'pending_generation'");

        Schema::table('supplier_ai_contents', function (Blueprint $table) {
            // Notas internas del equipo — visibles en el espacio "Sin contenido"
            // (ej. "No subir hasta Septiembre"). Independientes del contenido IA.
            $table->text('notes')->nullable()->after('rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_ai_contents', function (Blueprint $table) {
            $table->dropColumn('notes');
        });

        DB::statement("ALTER TABLE supplier_ai_contents MODIFY COLUMN status ENUM(
            'pending_generation',
            'generating',
            'pending_validation',
            'in_review',
            'needs_revision',
            'validated',
            'published',
            'published_hidden',
            'rejected',
            'error_insufficient_info',
            'error_source_unavailable',
            'error_generation_failed'
        ) NOT NULL DEFAULT 'pending_generation'");
    }
};

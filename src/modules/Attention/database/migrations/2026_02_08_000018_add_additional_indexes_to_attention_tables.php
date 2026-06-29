<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agregar índices adicionales para mejorar el rendimiento
     * Esta migración agrega índices compuestos y adicionales que no estaban
     * en las migraciones originales pero que mejorarán las consultas comunes.
     */
    public function up(): void
    {
        // Índices compuestos para consultas comunes en attentions
        Schema::table('attentions', function (Blueprint $table) {
            // Búsquedas por estado y fecha
            $table->index(['status', 'created_at'], 'idx_status_created');

            // Búsquedas por departamento y estado
            $table->index(['department_id', 'status'], 'idx_department_status');

            // Búsquedas por usuario asignado y estado
            $table->index(['assigned_user_id', 'status'], 'idx_assigned_status');

            // Búsquedas por tipo y estado
            $table->index(['type_id', 'status'], 'idx_type_status');

            // Búsquedas por sede y fecha
            $table->index(['sede_id', 'created_at'], 'idx_sede_created');
        });

        // Índices para mejorar ordenamiento en attention_notes
        Schema::table('attention_notes', function (Blueprint $table) {
            // Índice compuesto para obtener notas de una atención ordenadas por fecha
            $table->index(['attention_id', 'created_at'], 'idx_attention_created');
        });

        // Índices para mejorar ordenamiento en attention_actions
        Schema::table('attention_actions', function (Blueprint $table) {
            // Índice compuesto para obtener acciones de una atención ordenadas por fecha
            $table->index(['attention_id', 'created_at'], 'idx_attention_action_created');

            // Índice para filtrar por tipo de acción y fecha
            $table->index(['action', 'created_at'], 'idx_action_created');
        });

        // Índices para mejorar búsquedas de emails
        Schema::table('attention_mails', function (Blueprint $table) {
            // Índice compuesto para emails de una atención
            $table->index(['attention_id', 'sent_at'], 'idx_attention_mail_sent');

            // Índice para buscar emails por destinatario
            $table->index('recipient_email', 'idx_recipient_email');

            // Índice para tipo de email
            $table->index('email_type', 'idx_email_type');
        });

        // Índices para categorías y tipos
        Schema::table('attention_categories', function (Blueprint $table) {
            // Índice compuesto para obtener categorías activas ordenadas
            $table->index(['is_active', 'order'], 'idx_active_order');
        });

        Schema::table('attention_types', function (Blueprint $table) {
            // Índice compuesto para obtener tipos activos ordenados
            $table->index(['is_active', 'order'], 'idx_type_active_order');

            // Índice para búsquedas por código
            $table->index('code', 'idx_type_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attentions', function (Blueprint $table) {
            $table->dropIndex('idx_status_created');
            $table->dropIndex('idx_department_status');
            $table->dropIndex('idx_assigned_status');
            $table->dropIndex('idx_type_status');
            $table->dropIndex('idx_sede_created');
        });

        Schema::table('attention_notes', function (Blueprint $table) {
            $table->dropIndex('idx_attention_created');
        });

        Schema::table('attention_actions', function (Blueprint $table) {
            $table->dropIndex('idx_attention_action_created');
            $table->dropIndex('idx_action_created');
        });

        Schema::table('attention_mails', function (Blueprint $table) {
            $table->dropIndex('idx_attention_mail_sent');
            $table->dropIndex('idx_recipient_email');
            $table->dropIndex('idx_email_type');
        });

        Schema::table('attention_categories', function (Blueprint $table) {
            $table->dropIndex('idx_active_order');
        });

        Schema::table('attention_types', function (Blueprint $table) {
            $table->dropIndex('idx_type_active_order');
            $table->dropIndex('idx_type_code');
        });
    }
};

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
        Schema::table('documents', function (Blueprint $table) {
            // Optimizar índice de uid (ya existe como unique, pero asegurar)
            if (! $this->hasIndex('documents', 'documents_uid_unique') && ! $this->hasIndex('documents', 'documents_uid_index')) {
                $table->unique('uid', 'documents_uid_unique');
            }

            // Índice para búsquedas por email de cliente (usado frecuentemente en API)
            if (! $this->hasIndex('documents', 'documents_customer_email_index')) {
                $table->index('customer_email', 'documents_customer_email_index');
            }

            // Índice compuesto para reportes y dashboard (status + fecha)
            if (! $this->hasIndex('documents', 'documents_status_created_index')) {
                $table->index(['status_id', 'created_at'], 'documents_status_created_index');
            }

            // Índice para orden temporal (usado en listados)
            if (! $this->hasIndex('documents', 'documents_created_at_index')) {
                $table->index('created_at', 'documents_created_at_index');
            }

            // Índice compuesto para búsquedas por pedido + cliente
            if (! $this->hasIndex('documents', 'documents_order_customer_index')) {
                $table->index(['order_id', 'customer_id'], 'documents_order_customer_index');
            }

            // Índice para current_stage (usado en workflow de validación)
            if (! $this->hasIndex('documents', 'documents_current_stage_index')) {
                $table->index('current_stage', 'documents_current_stage_index');
            }

            // Índice para assigned_user_id (usado para filtrar documentos asignados)
            if (! $this->hasIndex('documents', 'documents_assigned_user_index')) {
                $table->index('assigned_user_id', 'documents_assigned_user_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Eliminar índices en orden inverso
            $this->dropIndexIfExists('documents', 'documents_assigned_user_index');
            $this->dropIndexIfExists('documents', 'documents_current_stage_index');
            $this->dropIndexIfExists('documents', 'documents_order_customer_index');
            $this->dropIndexIfExists('documents', 'documents_created_at_index');
            $this->dropIndexIfExists('documents', 'documents_status_created_index');
            $this->dropIndexIfExists('documents', 'documents_customer_email_index');
        });
    }

    /**
     * Check if index exists
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        // SQLite doesn't support SHOW INDEX, use schema-agnostic method
        if (config('database.default') === 'sqlite') {
            try {
                $indexes = \Illuminate\Support\Facades\DB::select(
                    "SELECT name FROM sqlite_master WHERE type='index' AND name=?",
                    [$indexName]
                );

                return ! empty($indexes);
            } catch (\Exception $e) {
                return false;
            }
        }

        // MySQL/PostgreSQL
        $indexes = \Illuminate\Support\Facades\DB::select("SHOW INDEX FROM {$table} WHERE Key_name = '{$indexName}'");

        return ! empty($indexes);
    }

    /**
     * Drop index if it exists
     */
    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if ($this->hasIndex($table, $indexName)) {
            Schema::table($table, function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        }
    }
};

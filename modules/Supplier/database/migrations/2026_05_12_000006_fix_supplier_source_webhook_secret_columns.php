<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hace `supplier_source_webhooks.secret_key` y `auth_config` compatibles con los casts
 * `encrypted` / `encrypted:array` añadidos al modelo SourceWebhook:
 *  - `secret_key` era varchar(255); el payload cifrado de un secreto de 64 chars supera 255 → se amplía a text.
 *  - `auth_config` es longtext pero MariaDB le añadió un CHECK json_valid(auth_config) al declararse como json();
 *    el cast encrypted:array escribe un string base64 que NO es JSON válido → hay que quitar ese CHECK.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('supplier_source_webhooks')) {
            return;
        }

        if (Schema::hasColumn('supplier_source_webhooks', 'secret_key')) {
            Schema::table('supplier_source_webhooks', function (Blueprint $table) {
                $table->text('secret_key')->nullable()->change();
            });
        }

        // Quitar el CHECK json_valid() implícito de auth_config: MariaDB lo añade al declarar una columna
        // como json(); redefinir la columna como LONGTEXT plano lo elimina (DROP CONSTRAINT/DROP CHECK no
        // funciona con este CHECK implícito en varias versiones de MariaDB).
        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true) && Schema::hasColumn('supplier_source_webhooks', 'auth_config')) {
            $stillJsonChecked = DB::selectOne(
                'SELECT 1 AS found FROM information_schema.CHECK_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = DATABASE()
                   AND TABLE_NAME = ?
                   AND CONSTRAINT_NAME = ?',
                ['supplier_source_webhooks', 'auth_config']
            );

            if ($stillJsonChecked) {
                DB::statement('ALTER TABLE `supplier_source_webhooks` MODIFY `auth_config` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL');
            }
        }
    }

    public function down(): void
    {
        // No se restaura el CHECK json_valid(auth_config): el contenido cifrado ya no es JSON válido,
        // así que re-añadirlo fallaría con cualquier fila existente. secret_key se revierte a varchar(255)
        // de forma best-effort (puede truncar payloads cifrados largos — por eso este down no es seguro
        // en datos reales y solo debería usarse en entornos limpios).
        if (Schema::hasTable('supplier_source_webhooks') && Schema::hasColumn('supplier_source_webhooks', 'secret_key')) {
            Schema::table('supplier_source_webhooks', function (Blueprint $table) {
                $table->string('secret_key', 255)->nullable()->change();
            });
        }
    }
};

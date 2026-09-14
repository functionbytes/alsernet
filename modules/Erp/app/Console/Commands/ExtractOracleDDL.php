<?php

namespace Modules\Erp\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExtractOracleDDL extends Command
{
    protected $signature = 'oracle:extract-ddl {--output=oracle_schema.sql}';

    protected $description = 'Extrae el DDL de todas las tablas de Oracle';

    public function handle()
    {
        $this->info('🔍 Conectando a Oracle...');

        try {
            // Verificar conexión
            $test = DB::connection('oracle')->select('SELECT 1 as test FROM dual');
            $this->info('✅ Conexión exitosa');
        } catch (\Exception $e) {
            $this->error('❌ Error de conexión: '.$e->getMessage());

            return Command::FAILURE;
        }

        // Obtener lista de tablas
        $this->info('📋 Obteniendo lista de tablas...');

        $tables = DB::connection('oracle')->select('
            SELECT table_name
            FROM user_tables
            ORDER BY table_name
        ');

        $this->info('Encontradas: '.count($tables).' tablas');
        $this->newLine();

        $outputFile = storage_path('app/'.$this->option('output'));
        $content = '';

        $content .= "-- ============================================\n";
        $content .= "-- ORACLE SCHEMA DDL\n";
        $content .= '-- Generado: '.date('Y-m-d H:i:s')."\n";
        $content .= "-- Base de datos: GESTION\n";
        $content .= '-- Total de tablas: '.count($tables)."\n";
        $content .= "-- ============================================\n\n";

        $bar = $this->output->createProgressBar(count($tables));
        $bar->start();

        $processed = 0;
        $errors = 0;

        foreach ($tables as $table) {
            $tableName = $table->table_name ?? $table->TABLE_NAME;

            try {
                // Obtener columnas
                $columns = DB::connection('oracle')->select('
                    SELECT
                        column_name,
                        data_type,
                        data_length,
                        data_precision,
                        data_scale,
                        nullable,
                        data_default
                    FROM user_tab_columns
                    WHERE table_name = :table_name
                    ORDER BY column_id
                ', ['table_name' => $tableName]);

                $content .= "\n-- ============================================\n";
                $content .= "-- Tabla: {$tableName}\n";
                $content .= "-- ============================================\n\n";
                $content .= "CREATE TABLE {$tableName} (\n";

                $columnDefs = [];
                foreach ($columns as $col) {
                    $colName = $col->column_name ?? $col->COLUMN_NAME;
                    $dataType = $col->data_type ?? $col->DATA_TYPE;
                    $length = $col->data_length ?? $col->DATA_LENGTH;
                    $precision = $col->data_precision ?? $col->DATA_PRECISION;
                    $scale = $col->data_scale ?? $col->DATA_SCALE;
                    $nullable = $col->nullable ?? $col->NULLABLE;
                    $default = $col->data_default ?? $col->DATA_DEFAULT;

                    $colDef = "  {$colName} ";

                    // Tipo de dato
                    if ($dataType === 'NUMBER') {
                        if ($precision && $scale) {
                            $colDef .= "NUMBER({$precision},{$scale})";
                        } elseif ($precision) {
                            $colDef .= "NUMBER({$precision})";
                        } else {
                            $colDef .= 'NUMBER';
                        }
                    } elseif (in_array($dataType, ['VARCHAR2', 'CHAR'])) {
                        $colDef .= "{$dataType}({$length})";
                    } else {
                        $colDef .= $dataType;
                    }

                    // Default
                    if ($default) {
                        $colDef .= ' DEFAULT '.trim($default);
                    }

                    // Not Null
                    if ($nullable === 'N') {
                        $colDef .= ' NOT NULL';
                    }

                    $columnDefs[] = $colDef;
                }

                $content .= implode(",\n", $columnDefs);
                $content .= "\n);\n\n";

                // Obtener Primary Key
                $pks = DB::connection('oracle')->select("
                    SELECT cols.column_name
                    FROM user_constraints cons
                    JOIN user_cons_columns cols ON cons.constraint_name = cols.constraint_name
                    WHERE cons.table_name = :table_name
                    AND cons.constraint_type = 'P'
                    ORDER BY cols.position
                ", ['table_name' => $tableName]);

                if (! empty($pks)) {
                    $pkCols = array_map(function ($pk) {
                        return $pk->column_name ?? $pk->COLUMN_NAME;
                    }, $pks);

                    $content .= "ALTER TABLE {$tableName}\n";
                    $content .= "  ADD CONSTRAINT PK_{$tableName} PRIMARY KEY (".implode(', ', $pkCols).");\n\n";
                }

                // Obtener Foreign Keys
                $fks = DB::connection('oracle')->select("
                    SELECT
                        cons.constraint_name,
                        cols.column_name,
                        ref_cons.table_name as ref_table,
                        ref_cols.column_name as ref_column
                    FROM user_constraints cons
                    JOIN user_cons_columns cols ON cons.constraint_name = cols.constraint_name
                    JOIN user_constraints ref_cons ON cons.r_constraint_name = ref_cons.constraint_name
                    JOIN user_cons_columns ref_cols ON ref_cons.constraint_name = ref_cols.constraint_name
                    WHERE cons.table_name = :table_name
                    AND cons.constraint_type = 'R'
                ", ['table_name' => $tableName]);

                foreach ($fks as $fk) {
                    $fkName = $fk->constraint_name ?? $fk->CONSTRAINT_NAME;
                    $fkCol = $fk->column_name ?? $fk->COLUMN_NAME;
                    $refTable = $fk->ref_table ?? $fk->REF_TABLE;
                    $refCol = $fk->ref_column ?? $fk->REF_COLUMN;

                    $content .= "ALTER TABLE {$tableName}\n";
                    $content .= "  ADD CONSTRAINT {$fkName} FOREIGN KEY ({$fkCol})\n";
                    $content .= "  REFERENCES {$refTable}({$refCol});\n\n";
                }

                // Obtener índices
                $indexes = DB::connection('oracle')->select("
                    SELECT
                        index_name,
                        uniqueness
                    FROM user_indexes
                    WHERE table_name = :table_name
                    AND index_name NOT LIKE 'PK_%'
                    AND index_name NOT LIKE 'SYS_%'
                ", ['table_name' => $tableName]);

                foreach ($indexes as $idx) {
                    $idxName = $idx->index_name ?? $idx->INDEX_NAME;
                    $unique = ($idx->uniqueness ?? $idx->UNIQUENESS) === 'UNIQUE' ? 'UNIQUE ' : '';

                    $idxCols = DB::connection('oracle')->select('
                        SELECT column_name
                        FROM user_ind_columns
                        WHERE index_name = :index_name
                        ORDER BY column_position
                    ', ['index_name' => $idxName]);

                    $colNames = array_map(function ($c) {
                        return $c->column_name ?? $c->COLUMN_NAME;
                    }, $idxCols);

                    $content .= "CREATE {$unique}INDEX {$idxName} ON {$tableName}(".implode(', ', $colNames).");\n";
                }

                $content .= "\n";

                $processed++;

            } catch (\Exception $e) {
                $content .= "-- ERROR en tabla {$tableName}: ".$e->getMessage()."\n\n";
                $errors++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $content .= "\n-- ============================================\n";
        $content .= "-- FIN DEL DDL\n";
        $content .= "-- Tablas procesadas: {$processed}\n";
        $content .= "-- Errores: {$errors}\n";
        $content .= "-- ============================================\n";

        // Guardar archivo
        file_put_contents($outputFile, $content);

        $this->info('✅ Proceso completado!');
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Tablas procesadas', $processed],
                ['Errores', $errors],
                ['Archivo generado', $outputFile],
            ]
        );

        $this->newLine();
        $this->info('Puedes ver el archivo con:');
        $this->line("  cat {$outputFile}");
        $this->line("  less {$outputFile}");

        return Command::SUCCESS;
    }
}

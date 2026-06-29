<?php

namespace Modules\Attention\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Attention\Enums\AttentionStatus;
use Modules\Attention\Models\Attention;

class MigrateAttentionDataCommand extends Command
{
    protected $signature = 'attention:migrate-data
                            {--dry-run : Ejecutar sin guardar cambios}
                            {--limit=100 : Número de registros por lote}';

    protected $description = 'Migrar datos de Attention_OLD a Attention nuevo';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $this->info('🔄 Iniciando migración de datos...');

        if ($dryRun) {
            $this->warn('⚠️  Modo DRY RUN - No se guardarán cambios');
        }

        // Verificar que existe la tabla antigua
        if (! $this->tableExists('attentions_old')) {
            $this->error('❌ La tabla attentions_old no existe. Primero renombra la tabla antigua.');

            return 1;
        }

        // Paso 1: Migrar registros principales
        $this->migrateAttentions($dryRun, $limit);

        // Paso 2: Migrar archivos
        $this->migrateMedia($dryRun);

        // Paso 3: Migrar notas y acciones
        $this->migrateNotesAndActions($dryRun);

        $this->info('✅ Migración completada');

        return 0;
    }

    protected function tableExists(string $table): bool
    {
        return DB::getSchemaBuilder()->hasTable($table);
    }

    protected function migrateAttentions($dryRun, $limit)
    {
        $this->info('📝 Migrando registros de attentions...');

        $oldAttentions = DB::table('attentions_old')->limit($limit)->get();

        if ($oldAttentions->isEmpty()) {
            $this->warn('⚠️  No se encontraron registros para migrar');

            return;
        }

        $bar = $this->output->createProgressBar(count($oldAttentions));

        $migrated = 0;
        $errors = 0;

        foreach ($oldAttentions as $old) {
            try {
                // Mapear estado antiguo a nuevo
                $newStatus = $this->mapStatus($old->status_id ?? null);

                $data = [
                    'uid' => $old->uid ?? \Str::uuid(),
                    'radicado' => $old->radicado ?? Attention::generateRadicado(),
                    'type_id' => $this->mapTypeId($old->type_id ?? null),
                    'category_id' => $old->category_id,
                    'sede_id' => $old->sede_id,
                    'customer_firstname' => $old->customer_firstname,
                    'customer_lastname' => $old->customer_lastname,
                    'customer_email' => $old->customer_email,
                    'customer_cellphone' => $old->customer_cellphone,
                    'customer_dni' => $old->customer_dni,
                    'customer_address' => $old->customer_address ?? null,
                    'is_anonymous' => $old->is_anonymous ?? false,
                    'subject' => $old->subject ?? 'Sin asunto',
                    'description' => $old->description ?? '',
                    'status' => $newStatus,
                    'department_id' => $old->department_id ?? null,
                    'assigned_user_id' => $old->assigned_user_id ?? null,
                    'response_type' => $old->response_type ?? null,
                    'resolution' => $old->resolution ?? null,
                    'resolved_at' => $old->resolved_at,
                    'closed_at' => $old->closed_at,
                    'satisfaction_rating' => $old->satisfaction_rating ?? null,
                    'created_at' => $old->created_at,
                    'updated_at' => $old->updated_at,
                ];

                if (! $dryRun) {
                    Attention::create($data);
                }

                $migrated++;
            } catch (\Exception $e) {
                $errors++;
                $this->error("\nError migrando ID {$old->id}: ".$e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✅ Migrados: {$migrated} | ❌ Errores: {$errors}");
    }

    protected function migrateMedia($dryRun)
    {
        $this->info('📎 Migrando archivos adjuntos...');

        // Los archivos de Spatie se mantienen en la tabla 'media'
        // Solo necesitamos actualizar model_type si cambió

        $count = DB::table('media')
            ->where('model_type', 'LIKE', '%Attention_OLD%')
            ->count();

        if ($count === 0) {
            $this->info('ℹ️  No hay archivos para migrar');

            return;
        }

        if (! $dryRun) {
            DB::table('media')
                ->where('model_type', 'LIKE', '%Attention_OLD%')
                ->update([
                    'model_type' => 'Modules\\Attention\\Models\\Attention',
                ]);
        }

        $this->info("✅ {$count} archivos procesados");
    }

    protected function migrateNotesAndActions($dryRun)
    {
        $this->info('📋 Migrando notas y acciones...');

        // Las tablas ya existen, los datos deberían ser compatibles
        // Solo verificar que las relaciones sean correctas

        if (! $this->tableExists('attention_notes')) {
            $this->warn('⚠️  La tabla attention_notes no existe');

            return;
        }

        if (! $this->tableExists('attention_actions')) {
            $this->warn('⚠️  La tabla attention_actions no existe');

            return;
        }

        $notes = DB::table('attention_notes')->count();
        $actions = DB::table('attention_actions')->count();

        $this->info("✅ {$notes} notas | {$actions} acciones");
    }

    protected function mapStatus($oldStatusId): AttentionStatus
    {
        // Mapear IDs de estado antiguo a nuevos enums
        return match ((int) $oldStatusId) {
            1 => AttentionStatus::RECEIVED,
            2 => AttentionStatus::IN_PROCESS,
            3 => AttentionStatus::RESOLVED,
            4 => AttentionStatus::CLOSED,
            default => AttentionStatus::RECEIVED,
        };
    }

    protected function mapTypeId($oldTypeId): int
    {
        // Los IDs de tipos deberían mantenerse iguales
        // Si no existe, asignar tipo por defecto (1 = Petición)
        return $oldTypeId ?? 1;
    }
}

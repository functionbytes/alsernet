<?php

namespace Modules\Attention\Console\Commands;

use Illuminate\Console\Command;
use Modules\Attention\Models\Attention;

/**
 * Command para validar si un PQRSF puede ser eliminado según Ley 594/2000
 *
 * Verifica que se cumpla el tiempo mínimo de retención legal antes
 * de permitir la eliminación permanente de registros.
 */
class ValidateAttentionDeletionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attention:validate-deletion {radicado}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Valida si un PQRSF puede ser eliminado según Ley 594/2000';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $radicado = $this->argument('radicado');

        // Buscar el PQRSF incluyendo eliminados suavemente
        $attention = Attention::where('radicado', $radicado)
            ->withTrashed()
            ->first();

        if (! $attention) {
            $this->error("❌ PQRSF {$radicado} no encontrado");

            return self::FAILURE;
        }

        // Obtener configuración de retención
        $minYears = config('attention.data_retention.minimum_retention_years', 5);
        $warnYears = config('attention.data_retention.warn_before_delete_years', 3);

        // Usar fecha de cierre o fecha de creación si no ha sido cerrado
        $closedAt = $attention->closed_at ?? $attention->created_at;
        $yearsSinceClosed = $closedAt->diffInYears(now());

        // Mostrar información del PQRSF
        $this->newLine();
        $this->info('📋 Información del PQRSF');
        $this->table(
            ['Campo', 'Valor'],
            [
                ['Radicado', $attention->radicado],
                ['Estado', $attention->status_label],
                ['Fecha de creación', $attention->created_at->format('d/m/Y H:i')],
                ['Fecha de cierre', $closedAt->format('d/m/Y H:i')],
                ['Años transcurridos', $yearsSinceClosed],
                ['Eliminado (soft delete)', $attention->trashed() ? 'SÍ' : 'NO'],
                ['Archivado', $attention->archived_at ? 'SÍ ('.$attention->archived_at->format('d/m/Y').')' : 'NO'],
            ]
        );

        $this->newLine();

        // Validación según normativa
        if ($yearsSinceClosed < $minYears) {
            $this->error('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->error('❌ NO PUEDE ELIMINARSE PERMANENTEMENTE');
            $this->error('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->newLine();
            $this->warn('📜 Fundamento Legal:');
            $this->line('   Ley 594 de 2000 (Ley General de Archivos)');
            $this->line('   Artículo 46: Conservación de documentos');
            $this->newLine();
            $this->warn("⏱️  Tiempo de retención requerido: {$minYears} años");
            $this->warn("⏱️  Tiempo transcurrido: {$yearsSinceClosed} años");
            $this->error('⏱️  Faltan '.($minYears - $yearsSinceClosed).' año(s) para cumplir el plazo legal');
            $this->newLine();

            return self::FAILURE;
        }

        // Advertencia si está dentro del rango de precaución
        if ($yearsSinceClosed < $warnYears) {
            $this->warn('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->warn('⚠️  PUEDE ELIMINARSE (CON PRECAUCIÓN)');
            $this->warn('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->newLine();
            $this->warn("El PQRSF cumple el plazo legal mínimo de {$minYears} años.");
            $this->warn("Sin embargo, está cerca del límite ({$yearsSinceClosed} años).");
            $this->newLine();
            $this->info('💡 Recomendación: Verificar si existen procesos legales');
            $this->info('   o administrativos activos relacionados antes de eliminar.');
            $this->newLine();

            return self::SUCCESS;
        }

        // Puede eliminarse sin restricciones
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('✅ PUEDE ELIMINARSE PERMANENTEMENTE');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();
        $this->info('El PQRSF cumple con el plazo legal de retención.');
        $this->info("Tiempo transcurrido: {$yearsSinceClosed} años");
        $this->info("Plazo mínimo legal: {$minYears} años");
        $this->newLine();

        // Información adicional sobre archivo histórico
        $historicYears = config('attention.data_retention.permanent_archive_years', 15);
        if ($yearsSinceClosed >= $historicYears) {
            $this->warn('📚 NOTA: Este PQRSF tiene valor histórico');
            $this->warn("   (>= {$historicYears} años). Considere su conservación");
            $this->warn('   como archivo histórico antes de eliminar.');
            $this->newLine();
        }

        return self::SUCCESS;
    }
}

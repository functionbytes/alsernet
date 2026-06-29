<?php

namespace Modules\Erp\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Erp\Jobs\V2\ValidatePriceFromGestion;
use Modules\Erp\Models\Core\ScheduledPriceValidation;

class SyncSpecificPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'specific-price:sync
                            {--all : Sincronizar todos los specific_price activos}
                            {--new : Solo sincronizar nuevos (no existen en scheduled_validations)}
                            {--execute : Ejecutar validaciones inmediatamente si están en periodo válido}
                            {--limit= : Limitar cantidad de registros a procesar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincronizar specific_price de Prestashop y crear validaciones programadas';

    protected int $created = 0;

    protected int $updated = 0;

    protected int $skipped = 0;

    protected int $executed = 0;

    protected int $errors = 0;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Sincronizando Specific Prices de Prestashop...');
        $this->newLine();

        $specificPrices = $this->getSpecificPrices();

        if ($specificPrices->isEmpty()) {
            $this->warn('No se encontraron specific_price para procesar.');

            return Command::SUCCESS;
        }

        $this->info("Encontrados: {$specificPrices->count()} specific_price");
        $bar = $this->output->createProgressBar($specificPrices->count());
        $bar->start();

        foreach ($specificPrices as $sp) {
            try {
                $result = $this->processSpecificPrice($sp);

                if ($result === 'created') {
                    $this->created++;
                } elseif ($result === 'updated') {
                    $this->updated++;
                } elseif ($result === 'skipped') {
                    $this->skipped++;
                }

            } catch (\Exception $e) {
                $this->errors++;
                $this->error("\nError en specific_price {$sp->id_specific_price}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->showStatistics();

        return Command::SUCCESS;
    }

    /**
     * Obtener specific_price de Prestashop
     */
    protected function getSpecificPrices()
    {
        $query = DB::connection('prestashop')
            ->table('aalv_specific_price as sp');

        if ($this->option('new')) {
            // Solo los que NO existen en scheduled_price_validations
            $existingIds = ScheduledPriceValidation::pluck('id_specific_price')->toArray();
            $query->whereNotIn('sp.id_specific_price', $existingIds);
        }

        if ($this->option('all')) {
            // Todos los activos (que no hayan expirado)
            $query->where(function ($q) {
                $q->whereNull('sp.to')
                    ->orWhere('sp.to', '=', '0000-00-00 00:00:00')
                    ->orWhere('sp.to', '>=', now());
            });
        } else {
            // Solo los que están en periodo válido AHORA
            $query->where(function ($q) {
                $q->whereNull('sp.from')
                    ->orWhere('sp.from', '=', '0000-00-00 00:00:00')
                    ->orWhere('sp.from', '<=', now());
            })
                ->where(function ($q) {
                    $q->whereNull('sp.to')
                        ->orWhere('sp.to', '=', '0000-00-00 00:00:00')
                        ->orWhere('sp.to', '>=', now());
                });
        }

        if ($limit = $this->option('limit')) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Procesar un specific_price
     */
    protected function processSpecificPrice($specificPrice): string
    {
        // Verificar si ya existe
        $existing = ScheduledPriceValidation::where('id_specific_price', $specificPrice->id_specific_price)->first();

        // Obtener referencia del producto
        $reference = $this->getProductReference(
            $specificPrice->id_product,
            $specificPrice->id_product_attribute ?? 0
        );

        // Calcular next_execution_at
        $nextExecution = $this->calculateNextExecution($specificPrice);

        // Crear o actualizar
        $scheduled = ScheduledPriceValidation::updateOrCreate(
            [
                'id_specific_price' => $specificPrice->id_specific_price,
            ],
            [
                'id_product' => $specificPrice->id_product,
                'id_product_attribute' => $specificPrice->id_product_attribute ?? 0,
                'id_country' => $specificPrice->id_country ?? 0,
                'reference' => $reference,
                'valid_from' => $this->parseDate($specificPrice->from),
                'valid_to' => $this->parseDate($specificPrice->to),
                'status' => 'scheduled',
                'next_execution_at' => $nextExecution,
            ]
        );

        $action = $existing ? 'updated' : 'created';

        $scheduled->addExecutionLog(
            'scheduled',
            "Sincronizado automáticamente ({$action})",
            [
                'specific_price_id' => $specificPrice->id_specific_price,
                'next_execution' => $nextExecution,
            ]
        );

        // Ejecutar inmediatamente si está en periodo válido y se solicitó
        if ($this->option('execute') && $this->isInValidPeriod($specificPrice)) {
            ValidatePriceFromGestion::dispatch($scheduled);
            $this->executed++;
        }

        return $action;
    }

    /**
     * Obtener referencia del producto
     */
    protected function getProductReference(int $idProduct, int $idProductAttribute = 0): ?string
    {
        if ($idProductAttribute > 0) {
            $combination = DB::connection('prestashop')
                ->table('aalv_product_attribute')
                ->where('id_product_attribute', $idProductAttribute)
                ->first();

            if ($combination && ! empty($combination->reference)) {
                return $combination->reference;
            }
        }

        $product = DB::connection('prestashop')
            ->table('aalv_product')
            ->where('id_product', $idProduct)
            ->first();

        return $product->reference ?? null;
    }

    /**
     * Calcular próxima ejecución
     */
    protected function calculateNextExecution($specificPrice): ?Carbon
    {
        $from = $this->parseDate($specificPrice->from);
        $to = $this->parseDate($specificPrice->to);
        $now = now();

        // Si aún no ha comenzado, ejecutar en fecha from
        if ($from && $from > $now) {
            return $from;
        }

        // Si está en curso, ejecutar ahora
        if ((! $from || $from <= $now) && (! $to || $to >= $now)) {
            return $now;
        }

        // Si ya terminó, marcar para no ejecutar
        if ($to && $to < $now) {
            return null;
        }

        return $now;
    }

    /**
     * Verificar si está en periodo válido
     */
    protected function isInValidPeriod($specificPrice): bool
    {
        $from = $this->parseDate($specificPrice->from);
        $to = $this->parseDate($specificPrice->to);
        $now = now();

        $afterFrom = ! $from || $from <= $now;
        $beforeTo = ! $to || $to >= $now;

        return $afterFrom && $beforeTo;
    }

    /**
     * Parsear fecha de Prestashop
     */
    protected function parseDate($date): ?Carbon
    {
        if (empty($date) || $date === '0000-00-00 00:00:00') {
            return null;
        }

        try {
            return Carbon::parse($date);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Mostrar estadísticas
     */
    protected function showStatistics(): void
    {
        $this->info('✅ Sincronización completada:');
        $this->table(
            ['Tipo', 'Cantidad'],
            [
                ['Creados', $this->created],
                ['Actualizados', $this->updated],
                ['Omitidos', $this->skipped],
                ['Ejecutados inmediatamente', $this->executed],
                ['Errores', $this->errors],
                ['Total procesados', $this->created + $this->updated + $this->skipped],
            ]
        );

        // Estadísticas generales
        $this->newLine();
        $this->info('📊 Estadísticas Generales:');

        $total = ScheduledPriceValidation::count();
        $scheduled = ScheduledPriceValidation::where('status', 'scheduled')->count();
        $completed = ScheduledPriceValidation::where('status', 'completed')->count();
        $failed = ScheduledPriceValidation::where('status', 'failed')->count();
        $pending = ScheduledPriceValidation::pendingExecution()->count();

        $this->table(
            ['Estado', 'Cantidad'],
            [
                ['Total', $total],
                ['Programados', $scheduled],
                ['Completados', $completed],
                ['Fallidos', $failed],
                ['Pendientes de ejecución', $pending],
            ]
        );
    }
}

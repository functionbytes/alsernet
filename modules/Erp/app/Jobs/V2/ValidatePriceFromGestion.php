<?php

namespace Modules\Erp\Jobs\V2;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Erp\Models\Core\PriceValidation;
use Modules\Erp\Models\Core\ScheduledPriceValidation;
use Modules\Erp\Services\GestionPriceService;

class ValidatePriceFromGestion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 300;

    public $backoff = [60, 300, 900]; // Reintentos: 1min, 5min, 15min

    protected ScheduledPriceValidation $scheduledValidation;

    /**
     * Create a new job instance.
     */
    public function __construct(ScheduledPriceValidation $scheduledValidation)
    {
        $this->scheduledValidation = $scheduledValidation;
    }

    /**
     * Execute the job.
     */
    public function handle(GestionPriceService $gestionService): void
    {
        try {
            Log::info('Iniciando validación automática de precio', [
                'scheduled_id' => $this->scheduledValidation->id,
                'reference' => $this->scheduledValidation->reference,
                'country' => $this->scheduledValidation->id_country,
            ]);

            // Marcar como en procesamiento
            $this->scheduledValidation->update(['status' => 'processing']);
            $this->scheduledValidation->addExecutionLog('processing', 'Iniciando validación');

            // 1. Obtener precio de Gestión (Oracle)
            $gestionResult = $gestionService->getPriceByReference(
                $this->scheduledValidation->reference,
                $this->scheduledValidation->id_country
            );

            if (! $gestionResult['found']) {
                $this->handleNotFound($gestionResult['error']);

                return;
            }

            $gestionPrice = $gestionResult['price'];
            Log::info('Precio obtenido de Gestión', ['price' => $gestionPrice]);

            // 2. Calcular precio de Prestashop
            $prestashopPrice = $this->calculatePrestashopPrice(
                $this->scheduledValidation->id_product,
                $this->scheduledValidation->id_product_attribute,
                $this->scheduledValidation->id_country
            );

            Log::info('Precio calculado de Prestashop', ['price' => $prestashopPrice]);

            // 3. Comparar precios
            $difference = $prestashopPrice - $gestionPrice;
            $hasDifference = abs($difference) > 0.01; // Tolerancia de 1 céntimo

            // 4. Actualizar scheduled validation
            $this->scheduledValidation->markAsExecuted($gestionPrice, $prestashopPrice);

            // 5. Si hay diferencia, crear validación para revisión manual
            if ($hasDifference) {
                $this->createPriceValidation($gestionPrice, $prestashopPrice, $difference);
            }

            // 6. Marcar como completado
            $this->scheduledValidation->markAsCompleted();
            $this->scheduledValidation->addExecutionLog(
                'completed',
                $hasDifference ? 'Diferencia detectada - Validación creada' : 'Sin diferencias',
                [
                    'gestion_price' => $gestionPrice,
                    'prestashop_price' => $prestashopPrice,
                    'difference' => $difference,
                ]
            );

            // 7. Programar próxima ejecución si está vigente
            $this->scheduleNextExecution();

            Log::info('Validación automática completada exitosamente');

        } catch (\Exception $e) {
            $this->handleFailure($e);
            throw $e;
        }
    }

    /**
     * Calcular precio de Prestashop
     */
    protected function calculatePrestashopPrice(int $idProduct, int $idProductAttribute, int $idCountry): float
    {
        // Obtener precio base
        $product = DB::connection('prestashop')
            ->table('aalv_product')
            ->where('id_product', $idProduct)
            ->first();

        if (! $product) {
            throw new \Exception("Producto {$idProduct} no encontrado en Prestashop");
        }

        $priceBase = (float) $product->price;

        // Si tiene combinación, sumar el impacto
        if ($idProductAttribute > 0) {
            $combination = DB::connection('prestashop')
                ->table('aalv_product_attribute')
                ->where('id_product_attribute', $idProductAttribute)
                ->first();

            if ($combination) {
                $priceBase += (float) $combination->price;
            }
        }

        // Buscar precio específico activo
        $specificPrice = DB::connection('prestashop')
            ->table('aalv_specific_price')
            ->where('id_product', $idProduct)
            ->where(function ($query) use ($idProductAttribute) {
                $query->where('id_product_attribute', $idProductAttribute)
                    ->orWhere('id_product_attribute', 0);
            })
            ->where(function ($query) use ($idCountry) {
                $query->where('id_country', $idCountry)
                    ->orWhere('id_country', 0);
            })
            ->where(function ($query) {
                $query->where('from', '<=', now())
                    ->orWhere('from', '0000-00-00 00:00:00')
                    ->orWhereNull('from');
            })
            ->where(function ($query) {
                $query->where('to', '>=', now())
                    ->orWhere('to', '0000-00-00 00:00:00')
                    ->orWhereNull('to');
            })
            ->orderByRaw('id_country DESC, id_product_attribute DESC, from_quantity ASC')
            ->first();

        if ($specificPrice) {
            // Aplicar precio específico
            if ($specificPrice->price >= 0) {
                $priceBase = (float) $specificPrice->price;
            }

            // Aplicar reducción si existe
            if ($specificPrice->reduction > 0) {
                if ($specificPrice->reduction_type === 'amount') {
                    $priceBase -= (float) $specificPrice->reduction;
                } elseif ($specificPrice->reduction_type === 'percentage') {
                    $priceBase -= $priceBase * ((float) $specificPrice->reduction / 100);
                }
            }
        }

        // Aplicar impuestos
        $taxRate = $this->getTaxRate($product->id_tax_rules_group, $idCountry);
        $priceWithTax = $priceBase * (1 + $taxRate / 100);

        return round($priceWithTax, 2);
    }

    /**
     * Obtener tasa de impuesto
     */
    protected function getTaxRate(?int $idTaxRulesGroup, int $idCountry): float
    {
        if (! $idTaxRulesGroup) {
            return 0;
        }

        $taxRule = DB::connection('prestashop')
            ->table('aalv_tax_rule')
            ->where('id_tax_rules_group', $idTaxRulesGroup)
            ->where('id_country', $idCountry)
            ->first();

        if (! $taxRule) {
            return 0;
        }

        $tax = DB::connection('prestashop')
            ->table('aalv_tax')
            ->where('id_tax', $taxRule->id_tax)
            ->first();

        return $tax ? (float) $tax->rate : 0;
    }

    /**
     * Crear validación de precio para revisión manual
     */
    protected function createPriceValidation(float $gestionPrice, float $prestashopPrice, float $difference): void
    {
        $differencePercentage = $gestionPrice > 0
            ? (($difference / $gestionPrice) * 100)
            : 0;

        $validation = PriceValidation::create([
            'id_product' => $this->scheduledValidation->id_product,
            'id_product_attribute' => $this->scheduledValidation->id_product_attribute,
            'id_country' => $this->scheduledValidation->id_country,
            'reference' => $this->scheduledValidation->reference,
            'product_name' => '', // Se puede obtener de Prestashop si es necesario
            'old_price' => $gestionPrice,
            'new_price' => $prestashopPrice,
            'difference' => $difference,
            'difference_percentage' => round($differencePercentage, 2),
            'status' => 'pending',
            'validation_type' => 'automatic',
            'priority' => PriceValidation::calculatePriority($differencePercentage),
            'stock' => $this->getStock(),
            'is_available' => true,
            'is_second_hand' => false,
            'notes' => "Validación automática desde SpecificPrice #{$this->scheduledValidation->id_specific_price}",
            'batch_id' => "AUTO-SP-{$this->scheduledValidation->id_specific_price}",
            'processed_at' => now(),
        ]);

        // Agregar al historial
        $validation->addHistory(
            action: 'created',
            newStatus: 'pending',
            newPrice: $prestashopPrice,
            changedBy: 'system-auto',
            notes: 'Validación automática desde Gestión'
        );

        // Vincular con scheduled validation
        $this->scheduledValidation->update([
            'price_validation_id' => $validation->id,
        ]);

        Log::info('Validación de precio creada', [
            'validation_id' => $validation->id,
            'difference' => $difference,
            'priority' => $validation->priority,
        ]);
    }

    /**
     * Obtener stock del producto
     */
    protected function getStock(): int
    {
        $stock = DB::connection('prestashop')
            ->table('aalv_stock_available')
            ->where('id_product', $this->scheduledValidation->id_product)
            ->where('id_product_attribute', $this->scheduledValidation->id_product_attribute)
            ->where('id_shop', 1)
            ->first();

        return $stock ? (int) $stock->quantity : 0;
    }

    /**
     * Programar próxima ejecución
     */
    protected function scheduleNextExecution(): void
    {
        // Si tiene fecha de fin y ya pasó, marcar como completado
        if ($this->scheduledValidation->valid_to && $this->scheduledValidation->valid_to < now()) {
            $this->scheduledValidation->update([
                'status' => 'completed',
                'next_execution_at' => null,
            ]);

            return;
        }

        // Programar para dentro de 1 día (diario mientras esté vigente)
        $this->scheduledValidation->update([
            'status' => 'scheduled',
            'next_execution_at' => now()->addDay(),
        ]);
    }

    /**
     * Manejar caso cuando no se encuentra en Gestión
     */
    protected function handleNotFound(?string $error): void
    {
        $this->scheduledValidation->addExecutionLog(
            'not_found',
            'Producto no encontrado en Gestión',
            ['error' => $error]
        );

        $this->scheduledValidation->update([
            'status' => 'completed',
            'last_error' => "No encontrado en Gestión: {$error}",
        ]);

        Log::warning('Producto no encontrado en Gestión', [
            'reference' => $this->scheduledValidation->reference,
            'error' => $error,
        ]);
    }

    /**
     * Manejar fallo del job
     */
    protected function handleFailure(\Exception $e): void
    {
        Log::error('Error en validación automática de precio', [
            'scheduled_id' => $this->scheduledValidation->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        $this->scheduledValidation->markAsFailed($e->getMessage());
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->handleFailure($exception);
    }
}

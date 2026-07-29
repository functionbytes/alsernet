<?php

namespace Modules\Supplier\Services;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Modules\Erp\Models\Oracle\Articulo\Articulo;
use Modules\Erp\Models\Oracle\Proveedor\ArtiprovTarifapro;
use Modules\Erp\Models\Oracle\Proveedor\Proveedor;
use Modules\Supplier\Models\Product\Product;
use Modules\Supplier\Models\Supplier\Supplier;
use Modules\Supplier\Models\SupplierProductPrice;
use Modules\Supplier\Models\Sync\SyncConflict;

/**
 * Servicio centralizado para sincronización inversa (Supplier → ERP)
 *
 * Maneja:
 * - Sincronización de precios de productos (SupplierProductPrice ↔ ArtiprovTarifapro)
 * - Sincronización de proveedores (SupplierErpProvider ↔ Proveedor)
 * - Sincronización de productos (Product ↔ Articulo)
 * - Detección y resolución de conflictos (ERP siempre gana)
 * - Prevención de loops infinitos mediante cache flags
 */
class ErpSyncService
{
    /**
     * Sincronizar precio de Supplier → Oracle
     *
     * Estrategia:
     * 1. Buscar precio en Oracle por ID
     * 2. Detectar conflicto si ERP fue modificado después del último sync
     * 3. Si hay conflicto: ERP gana (re-sincronizar desde Oracle)
     * 4. Si no hay conflicto: actualizar Oracle con cambios de Supplier
     * 5. Actualizar last_synced_at en Supplier
     *
     * @param  SupplierProductPrice  $price  Precio a sincronizar
     * @param  array  $changedFields  Campos que cambiaron (ej: ['cost', 'discount1'])
     *
     * @throws Exception Si el precio no existe en Oracle
     */
    public function syncPriceToOracle(SupplierProductPrice $price, array $changedFields): void
    {
        $oraclePrice = ArtiprovTarifapro::on('oracle')
            ->find($price->erp_price_id);

        if (! $oraclePrice) {
            throw new Exception("Oracle price not found: {$price->erp_price_id}");
        }

        // CONFLICT DETECTION: Si ERP cambió después del último sync
        if ($this->hasConflict($oraclePrice, $price)) {
            Log::warning('Conflict detected in price sync - ERP takes precedence', [
                'supplier_price_id' => $price->id,
                'erp_price_id' => $oraclePrice->idartiprov_tarifapro,
                'supplier_updated_at' => $price->updated_at,
                'erp_updated_at' => $oraclePrice->fmodificacion,
                'changed_fields' => $changedFields,
            ]);

            // Register conflict for auditing
            $this->registerConflict('price', $price, $oraclePrice, $changedFields);

            // Re-sincronizar DESDE ERP (ERP gana)
            $this->syncPriceFromOracle($oraclePrice, $price);

            return;
        }

        // No hay conflicto: actualizar Oracle con cambios de Supplier
        $updateData = [];

        if (in_array('cost', $changedFields)) {
            $updateData['pcosto'] = $price->cost;
        }

        if (in_array('discount1', $changedFields)) {
            $updateData['dto1'] = $price->discount1 ?? 0;
        }

        if (in_array('discount2', $changedFields)) {
            $updateData['dto2'] = $price->discount2 ?? 0;
        }

        if (in_array('effective_date', $changedFields)) {
            $updateData['fecha'] = $price->effective_date;
        }

        if (in_array('is_active', $changedFields)) {
            $updateData['estado'] = $price->is_active ? 1 : 0;
        }

        // Actualizar Oracle
        $oraclePrice->update($updateData);

        // Actualizar timestamp de último sync en Supplier
        $price->update(['last_synced_at' => now()]);

        Log::info('Price synced to ERP successfully', [
            'supplier_price_id' => $price->id,
            'erp_price_id' => $oraclePrice->idartiprov_tarifapro,
            'changed_fields' => $changedFields,
        ]);
    }

    /**
     * Detectar conflicto: ¿ERP fue modificado después del último sync de Supplier?
     *
     * @return bool True si hay conflicto
     */
    protected function hasConflict(ArtiprovTarifapro $oraclePrice, SupplierProductPrice $supplierPrice): bool
    {
        // Si ERP no tiene last_synced_at, es primera vez → sin conflicto
        if (! $supplierPrice->last_synced_at) {
            return false;
        }

        // Si ERP fue modificado después del último sync de Supplier → conflicto
        return $oraclePrice->fmodificacion > $supplierPrice->last_synced_at;
    }

    /**
     * Re-sincronizar DESDE ERP cuando hay conflicto (ERP gana)
     *
     * Copia valores de Oracle a Supplier
     */
    protected function syncPriceFromOracle(ArtiprovTarifapro $oraclePrice, SupplierProductPrice $supplierPrice): void
    {
        $supplierPrice->update([
            'cost' => $oraclePrice->pcosto,
            'discount1' => $oraclePrice->dto1 ?? 0,
            'discount2' => $oraclePrice->dto2 ?? 0,
            'effective_date' => $oraclePrice->fecha,
            'is_active' => (bool) $oraclePrice->estado,
            'erp_updated_at' => $oraclePrice->fmodificacion,
            'last_synced_at' => now(),
        ]);

        Log::warning('Price conflict resolved - synced from ERP (ERP value kept)', [
            'supplier_price_id' => $supplierPrice->id,
            'erp_price_id' => $oraclePrice->idartiprov_tarifapro,
        ]);
    }

    /**
     * Sincronizar proveedor de Supplier → Oracle
     *
     * @throws Exception
     */
    public function syncProviderToOracle(Supplier $provider, array $changedFields): void
    {
        $oracleProvider = Proveedor::on('oracle')
            ->find($provider->erp_id);

        if (! $oracleProvider) {
            throw new Exception("Oracle provider not found: {$provider->erp_id}");
        }

        // Conflict detection
        if ($this->hasProviderConflict($oracleProvider, $provider)) {
            Log::warning('Conflict detected in provider sync - ERP takes precedence', [
                'supplier_provider_id' => $provider->id,
                'erp_provider_id' => $oracleProvider->idproveedor,
                'changed_fields' => $changedFields,
            ]);

            // Register conflict for auditing
            $this->registerConflict('provider', $provider, $oracleProvider, $changedFields);

            $this->syncProviderFromOracle($oracleProvider, $provider);

            return;
        }

        // Map fields: Supplier → Oracle
        $updateData = [];

        if (in_array('name', $changedFields)) {
            $updateData['nombre'] = $provider->name;
        }

        if (in_array('email', $changedFields)) {
            $updateData['email'] = $provider->email;
        }

        if (in_array('phone', $changedFields)) {
            $updateData['telefono1'] = $provider->phone;
        }

        if (in_array('website', $changedFields)) {
            $updateData['web'] = $provider->website;
        }

        if (in_array('shipping_cost', $changedFields)) {
            $updateData['portes'] = $provider->shipping_cost;
        }

        if (in_array('discount_percent', $changedFields)) {
            $updateData['dto'] = $provider->discount_percent;
        }

        if (in_array('partial_delivery_days', $changedFields)) {
            $updateData['dias_servir_parcial'] = $provider->partial_delivery_days;
        }

        $oracleProvider->update($updateData);
        $provider->update(['last_synced_at' => now()]);

        Log::info('Provider synced to ERP successfully', [
            'supplier_provider_id' => $provider->id,
            'erp_provider_id' => $oracleProvider->idproveedor,
            'changed_fields' => $changedFields,
        ]);
    }

    /**
     * Detectar conflicto en proveedor
     */
    protected function hasProviderConflict(Proveedor $oracleProvider, Supplier $supplierProvider): bool
    {
        if (! $supplierProvider->last_synced_at) {
            return false;
        }

        return $oracleProvider->fmodificacion > $supplierProvider->last_synced_at;
    }

    /**
     * Re-sincronizar proveedor desde Oracle
     */
    protected function syncProviderFromOracle(Proveedor $oracleProvider, Supplier $supplierProvider): void
    {
        $supplierProvider->update([
            'name' => $oracleProvider->nombre,
            'email' => $oracleProvider->email,
            'phone' => $oracleProvider->telefono1,
            'website' => $oracleProvider->web,
            'shipping_cost' => $oracleProvider->portes,
            'discount_percent' => $oracleProvider->dto,
            'partial_delivery_days' => $oracleProvider->dias_servir_parcial,
            'erp_updated_at' => $oracleProvider->fmodificacion,
            'last_synced_at' => now(),
        ]);

        Log::warning('Provider conflict resolved - synced from ERP (ERP value kept)', [
            'supplier_provider_id' => $supplierProvider->id,
            'erp_provider_id' => $oracleProvider->idproveedor,
        ]);
    }

    /**
     * Sincronizar producto de Supplier → Oracle
     *
     * @throws Exception
     */
    public function syncProductToOracle(Product $product, array $changedFields): void
    {
        $oracleProduct = Articulo::on('oracle')
            ->find($product->erp_id);

        if (! $oracleProduct) {
            throw new Exception("Oracle product not found: {$product->erp_id}");
        }

        // Conflict detection
        if ($this->hasProductConflict($oracleProduct, $product)) {
            Log::warning('Conflict detected in product sync - ERP takes precedence', [
                'supplier_product_id' => $product->id,
                'erp_product_id' => $oracleProduct->idarticulo,
                'changed_fields' => $changedFields,
            ]);

            // Register conflict for auditing
            $this->registerConflict('product', $product, $oracleProduct, $changedFields);

            $this->syncProductFromOracle($oracleProduct, $product);

            return;
        }

        // Map fields: Supplier → Oracle
        $updateData = [];

        if (in_array('name', $changedFields)) {
            $updateData['descripcion'] = $product->name;
        }

        if (in_array('barcode', $changedFields)) {
            $updateData['codbar'] = $product->barcode;
        }

        if (in_array('recommended_price', $changedFields)) {
            $updateData['preciorecomendadoprov'] = $product->recommended_price;
        }

        if (in_array('is_active', $changedFields)) {
            $updateData['estado'] = $product->is_active ? 1 : 0;
        }

        $oracleProduct->update($updateData);
        $product->update(['last_synced_at' => now()]);

        Log::info('Product synced to ERP successfully', [
            'supplier_product_id' => $product->id,
            'erp_product_id' => $oracleProduct->idarticulo,
            'changed_fields' => $changedFields,
        ]);
    }

    /**
     * Detectar conflicto en producto
     */
    protected function hasProductConflict(Articulo $oracleProduct, Product $supplierProduct): bool
    {
        if (! $supplierProduct->last_synced_at) {
            return false;
        }

        return $oracleProduct->fmodificacion > $supplierProduct->last_synced_at;
    }

    /**
     * Re-sincronizar producto desde Oracle
     */
    protected function syncProductFromOracle(Articulo $oracleProduct, Product $supplierProduct): void
    {
        $supplierProduct->update([
            'name' => $oracleProduct->descripcion,
            'barcode' => $oracleProduct->codbar,
            'recommended_price' => $oracleProduct->preciorecomendadoprov,
            'is_active' => (bool) $oracleProduct->estado,
            'erp_updated_at' => $oracleProduct->fmodificacion,
            'last_synced_at' => now(),
        ]);

        Log::warning('Product conflict resolved - synced from ERP (ERP value kept)', [
            'supplier_product_id' => $supplierProduct->id,
            'erp_product_id' => $oracleProduct->idarticulo,
        ]);
    }

    /**
     * Register conflict for auditing purposes
     *
     * @param  string  $entityType  'price', 'product', or 'provider'
     * @param  SupplierProductPrice|Product|Supplier  $localEntity  Local Supplier-side model instance
     * @param  ArtiprovTarifapro|Articulo|Proveedor  $erpEntity  Oracle model instance
     * @param  array<int, string>  $changedFields  Fields that were modified
     */
    protected function registerConflict(string $entityType, Model $localEntity, Model $erpEntity, array $changedFields): void
    {
        try {
            // Extract local data based on entity type
            $localData = $this->extractEntityData($entityType, $localEntity);

            // Extract ERP data based on entity type
            $erpData = $this->extractErpEntityData($entityType, $erpEntity);

            // Create conflict record
            SyncConflict::create([
                'entity_type' => $entityType,
                'entity_id' => $localEntity->id,
                'erp_id' => $this->getErpId($entityType, $erpEntity),
                'resolution_strategy' => SyncConflict::STRATEGY_ERP_WINS,
                'local_data' => $localData,
                'erp_data' => $erpData,
                'resolved_data' => $erpData, // ERP wins, so resolved data = ERP data
                'changed_fields' => $changedFields,
                'conflict_detected_at' => now(),
                'resolved_at' => now(), // Auto-resolved by strategy
                'resolved_by_user_id' => null, // System resolved
                'resolved_by_ip' => request()?->ip(),
            ]);

            Log::info('Conflict registered for auditing', [
                'entity_type' => $entityType,
                'entity_id' => $localEntity->id,
                'changed_fields' => $changedFields,
            ]);
        } catch (Exception $e) {
            // Don't fail sync if conflict registration fails
            Log::error('Failed to register conflict', [
                'entity_type' => $entityType,
                'entity_id' => $localEntity->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Extract relevant data from local entity
     *
     * @param  SupplierProductPrice|Product|Supplier  $entity
     * @return array<string, mixed>
     */
    protected function extractEntityData(string $entityType, Model $entity): array
    {
        return match ($entityType) {
            'price' => [
                'cost' => $entity->cost,
                'discount1' => $entity->discount1,
                'discount2' => $entity->discount2,
                'effective_date' => $entity->effective_date?->toDateTimeString(),
                'is_active' => $entity->is_active,
                'updated_at' => $entity->updated_at?->toDateTimeString(),
                'last_synced_at' => $entity->last_synced_at?->toDateTimeString(),
            ],
            'product' => [
                'name' => $entity->name,
                'barcode' => $entity->barcode,
                'recommended_price' => $entity->recommended_price,
                'is_active' => $entity->is_active,
                'updated_at' => $entity->updated_at?->toDateTimeString(),
                'last_synced_at' => $entity->last_synced_at?->toDateTimeString(),
            ],
            'provider' => [
                'name' => $entity->name,
                'email' => $entity->email,
                'phone' => $entity->phone,
                'website' => $entity->website,
                'shipping_cost' => $entity->shipping_cost,
                'discount_percent' => $entity->discount_percent,
                'updated_at' => $entity->updated_at?->toDateTimeString(),
                'last_synced_at' => $entity->last_synced_at?->toDateTimeString(),
            ],
            default => throw new InvalidArgumentException("Unsupported entity type: {$entityType}"),
        };
    }

    /**
     * Extract relevant data from ERP entity
     *
     * @param  ArtiprovTarifapro|Articulo|Proveedor  $entity
     * @return array<string, mixed>
     */
    protected function extractErpEntityData(string $entityType, Model $entity): array
    {
        return match ($entityType) {
            'price' => [
                'pcosto' => $entity->pcosto,
                'dto1' => $entity->dto1,
                'dto2' => $entity->dto2,
                'fecha' => $entity->fecha,
                'estado' => $entity->estado,
                'fmodificacion' => $entity->fmodificacion?->toDateTimeString(),
            ],
            'product' => [
                'descripcion' => $entity->descripcion,
                'codbar' => $entity->codbar,
                'preciorecomendadoprov' => $entity->preciorecomendadoprov,
                'estado' => $entity->estado,
                'fmodificacion' => $entity->fmodificacion?->toDateTimeString(),
            ],
            'provider' => [
                'nombre' => $entity->nombre,
                'email' => $entity->email,
                'telefono1' => $entity->telefono1,
                'web' => $entity->web,
                'portes' => $entity->portes,
                'dto' => $entity->dto,
                'fmodificacion' => $entity->fmodificacion?->toDateTimeString(),
            ],
            default => throw new InvalidArgumentException("Unsupported entity type: {$entityType}"),
        };
    }

    /**
     * Get ERP ID based on entity type
     *
     * @param  ArtiprovTarifapro|Articulo|Proveedor  $entity
     */
    protected function getErpId(string $entityType, Model $entity): ?int
    {
        return match ($entityType) {
            'price' => $entity->idartiprov_tarifapro ?? null,
            'product' => $entity->idarticulo ?? null,
            'provider' => $entity->idproveedor ?? null,
            default => throw new InvalidArgumentException("Unsupported entity type: {$entityType}"),
        };
    }
}

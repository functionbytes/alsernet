<?php

namespace App\Services\Return;

use App\Models\Warranty;
use App\Models\WarrantyClaim;
use App\Models\WarrantyType;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WarrantyService
{
    /**
     * Crear garantías automáticamente para una orden
     */
    public function createWarrantiesFromOrder(Order $order): array
    {
        $warranties = [];
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($order->items as $item) {
                $product = $item->product;

                if (!$product->has_warranty) {
                    continue;
                }

                // Obtener tipos de garantía disponibles para el producto
                $availableTypes = $this->getAvailableWarrantyTypes($product);

                foreach ($availableTypes as $warrantyType) {
                    try {
                        $warranty = $this->createWarranty($order, $product, $warrantyType, [
                            'quantity' => $item->quantity,
                            'product_price' => $item->price,
                            'auto_created' => true,
                        ]);

                        $warranties[] = $warranty;

                        // Auto-activar si está configurado
                        if ($product->auto_warranty_registration) {
                            $warranty->activate();
                        }

                    } catch (\Exception $e) {
                        $errors[] = [
                            'product_id' => $product->id,
                            'warranty_type' => $warrantyType->code,
                            'error' => $e->getMessage(),
                        ];
                    }
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }

        return [
            'warranties' => $warranties,
            'errors' => $errors,
        ];
    }

    /**
     * Crear una garantía individual
     */
    public function createWarranty(
        Order $order,
        Product $product,
        WarrantyType $warrantyType,
        array $additionalData = []
    ): Warranty {
        $warrantyData = array_merge([
            'warranty_number' => Warranty::generateWarrantyNumber(),
            'order_id' => $order->id,
            'product_id' => $product->id,
            'warranty_type_id' => $warrantyType->id,
            'manufacturer_id' => $product->manufacturer_id,
            'user_id' => $order->user_id,
            'product_model' => $product->model ?? $product->sku,
            'product_price' => $product->price,
            'quantity' => 1,
            'purchase_date' => $order->created_at->toDateString(),
            'warranty_start_date' => $order->created_at->toDateString(),
            'warranty_duration_months' => $product->default_warranty_months,
            'warranty_cost' => $warrantyType->calculateCost($product->price),
            'is_paid' => true,
            'payment_date' => $order->created_at,
            'status' => Warranty::STATUS_ACTIVE,
        ], $additionalData);

        // Calcular fecha de fin
        $startDate = Carbon::parse($warrantyData['warranty_start_date']);
        $warrantyData['warranty_end_date'] = $startDate
            ->copy()
            ->addMonths($warrantyData['warranty_duration_months'])
            ->toDateString();

        $warranty = Warranty::create($warrantyData);

        // Registrar automáticamente con fabricante si está configurado
        if ($product->manufacturer && $product->manufacturer->auto_warranty_registration) {
            $this->registerWarrantyWithManufacturer($warranty);
        }

        return $warranty;
    }

    /**
     * Obtener tipos de garantía disponibles para un producto
     */
    public function getAvailableWarrantyTypes(Product $product): array
    {
        // Si el producto tiene tipos específicos configurados
        if ($product->warranty_types_available) {
            return WarrantyType::whereIn('id', $product->warranty_types_available)
                ->active()
                ->byPriority()
                ->get()
                ->toArray();
        }

        // Tipos por defecto según el fabricante
        $types = WarrantyType::active()->byPriority()->get();

        // Filtrar por fabricante si tiene políticas específicas
        if ($product->manufacturer && $product->manufacturer->warranty_policies) {
            $allowedTypes = $product->manufacturer->warranty_policies['allowed_types'] ?? [];
            if (!empty($allowedTypes)) {
                $types = $types->whereIn('code', $allowedTypes);
            }
        }

        return $types->toArray();
    }

    /**
     * Registrar garantía con fabricante
     */
    public function registerWarrantyWithManufacturer(Warranty $warranty): array
    {
        if (!$warranty->manufacturer) {
            return [
                'success' => false,
                'message' => 'No hay fabricante asociado',
            ];
        }

        $result = $warranty->registerWithManufacturer();

        if ($result['success']) {
            Log::info('Garantía registrada con fabricante', [
                'warranty_id' => $warranty->id,
                'manufacturer_id' => $warranty->manufacturer->id,
                'manufacturer_warranty_id' => $result['manufacturer_warranty_id'] ?? null,
            ]);
        } else {
            Log::warning('Error registrando garantía con fabricante', [
                'warranty_id' => $warranty->id,
                'error' => $result['message'],
            ]);
        }

        return $result;
    }

    /**
     * Crear reclamo de garantía
     */
    public function createWarrantyClaim(
        Warranty $warranty,
        User $user,
        array $claimData
    ): WarrantyClaim {
        if (!$warranty->isActive()) {
            throw new \InvalidArgumentException('La garantía no está activa');
        }

        $claim = WarrantyClaim::create(array_merge([
            'claim_number' => WarrantyClaim::generateClaimNumber(),
            'warranty_id' => $warranty->id,
            'user_id' => $user->id,
            'status' => WarrantyClaim::STATUS_SUBMITTED,
            'priority' => $this->calculateClaimPriority($claimData),
            'response_due_date' => now()->addHours(24), // SLA configurable
            'resolution_due_date' => now()->addDays(3), // SLA configurable
        ], $claimData));

        // Determinar departamento de asignación
        $department = $this->determineAssignmentDepartment($warranty, $claimData);

        if ($department === 'manufacturer' && $warranty->manufacturer?->has_api_integration) {
            // Enviar directamente al fabricante
            $claim->submitToManufacturer();
        } else {
            // Asignar internamente
            $this->autoAssignClaim($claim);
        }

        return $claim;
    }

    /**
     * Calcular prioridad del reclamo
     */
    protected function calculateClaimPriority(array $claimData): string
    {
        $category = $claimData['issue_category'] ?? 'unknown';
        $symptoms = $claimData['symptoms'] ?? [];

        // Prioridad crítica para ciertos problemas
        $criticalIssues = ['safety_hazard', 'fire_risk', 'electrical_failure'];
        if (in_array($category, $criticalIssues)) {
            return WarrantyClaim::PRIORITY_CRITICAL;
        }

        // Prioridad alta para problemas severos
        $highPriorityIssues = ['complete_failure', 'data_loss', 'security_breach'];
        if (in_array($category, $highPriorityIssues)) {
            return WarrantyClaim::PRIORITY_HIGH;
        }

        // Verificar síntomas para determinar prioridad
        $severeSymptons = ['smoking', 'overheating', 'unusual_noise', 'sparks'];
        if (array_intersect($symptoms, $severeSymptons)) {
            return WarrantyClaim::PRIORITY_HIGH;
        }

        return WarrantyClaim::PRIORITY_MEDIUM;
    }

    /**
     * Determinar departamento de asignación
     */
    protected function determineAssignmentDepartment(Warranty $warranty, array $claimData): string
    {
        $category = $claimData['issue_category'] ?? 'unknown';

        // Problemas de software van a soporte técnico
        if (in_array($category, ['software', 'driver', 'configuration'])) {
            return 'technical';
        }

        // Problemas de hardware van al fabricante si es posible
        if ($warranty->manufacturer && $warranty->manufacturer->canHandleIssue($category)) {
            return 'manufacturer';
        }

        // Por defecto, soporte técnico interno
        return 'technical';
    }

    /**
     * Auto-asignar reclamo
     */
    protected function autoAssignClaim(WarrantyClaim $claim): void
    {
        // Lógica para asignar automáticamente según carga de trabajo
        // Por simplicidad, asignar al usuario con menos reclamos activos
        $technician = User::whereHas('roles', function ($query) {
            $query->where('name', 'technician');
        })
            ->withCount(['assignedClaims' => function ($query) {
                $query->active();
            }])
            ->orderBy('assigned_claims_count')
            ->first();

        if ($technician) {
            $claim->assignTo($technician, 'technical');
        }
    }

    /**
     * Extender garantía
     */
    public function extendWarranty(
        Warranty $warranty,
        int $additionalMonths,
        WarrantyType $extensionType,
        array $paymentData = []
    ): \App\Models\WarrantyExtension {
        if ($warranty->isExpired()) {
            throw new \InvalidArgumentException('No se puede extender una garantía expirada');
        }

        $cost = $extensionType->calculateCost($warranty->product_price, $additionalMonths);

        return $warranty->extend($additionalMonths, $extensionType, $cost);
    }

    /**
     * Transferir garantía
     */
    public function transferWarranty(
        Warranty $warranty,
        User $newOwner,
        array $transferData = []
    ): bool {
        if (!$warranty->warrantyType->transferable) {
            throw new \InvalidArgumentException('Esta garantía no es transferible');
        }

        return $warranty->transferTo($newOwner, $transferData);
    }

    /**
     * Verificar garantías próximas a vencer
     */
    public function checkExpiringWarranties(int $days = 30): array
    {
        $expiringWarranties = Warranty::expiringSoon($days)
            ->with(['user', 'product', 'warrantyType'])
            ->get();

        $notifications = [];

        foreach ($expiringWarranties as $warranty) {
            $remainingDays = $warranty->getRemainingDays();

            $notifications[] = [
                'warranty_id' => $warranty->id,
                'user_id' => $warranty->user_id,
                'product_name' => $warranty->product->name,
                'warranty_number' => $warranty->warranty_number,
                'remaining_days' => $remainingDays,
                'expiry_date' => $warranty->warranty_end_date,
                'can_extend' => $this->canExtendWarranty($warranty),
            ];
        }

        return $notifications;
    }

    /**
     * Verificar si una garantía puede ser extendida
     */
    public function canExtendWarranty(Warranty $warranty): bool
    {
        // No puede extender si ya expiró
        if ($warranty->isExpired()) {
            return false;
        }

        // Verificar si hay tipos de extensión disponibles
        $extensionTypes = WarrantyType::where('code', 'EXTENDED')
            ->active()
            ->first();

        if (!$extensionTypes) {
            return false;
        }

        // Verificar políticas del fabricante
        if ($warranty->manufacturer && $warranty->manufacturer->warranty_policies) {
            $allowExtensions = $warranty->manufacturer->warranty_policies['allow_extensions'] ?? true;
            if (!$allowExtensions) {
                return false;
            }
        }

        return true;
    }

    /**
     * Procesar notificaciones de garantías
     */
    public function processWarrantyNotifications(): array
    {
        $processed = [];

        // Notificar garantías próximas a vencer
        $expiring = $this->checkExpiringWarranties(30);
        foreach ($expiring as $notification) {
            // Enviar notificación al usuario
            // event(new WarrantyExpiringNotification($notification));
            $processed[] = $notification;
        }

        // Marcar garantías expiradas
        $expired = Warranty::where('warranty_end_date', '<', now()->toDateString())
            ->where('status', Warranty::STATUS_ACTIVE)
            ->update(['status' => Warranty::STATUS_EXPIRED]);

        return [
            'expiring_notifications' => count($expiring),
            'expired_warranties' => $expired,
            'processed_notifications' => $processed,
        ];
    }

    /**
     * Sincronizar con fabricantes
     */
    public function syncWithManufacturers(): array
    {
        $results = [];

        $warranties = Warranty::where('is_registered_with_manufacturer', true)
            ->whereHas('manufacturer', function ($query) {
                $query->where('has_api_integration', true);
            })
            ->with('manufacturer')
            ->get();

        foreach ($warranties as $warranty) {
            try {
                // Verificar estado en el fabricante
                $result = $warranty->manufacturer->lookupWarranty($warranty->product_serial_number);

                if ($result['success']) {
                    $warrantyInfo = $result['warranty_info'];

                    // Actualizar información si es necesaria
                    if (isset($warrantyInfo['status']) && $warrantyInfo['status'] !== $warranty->status) {
                        $warranty->update(['status' => $warrantyInfo['status']]);
                    }
                }

                $results[] = [
                    'warranty_id' => $warranty->id,
                    'manufacturer' => $warranty->manufacturer->name,
                    'status' => 'success',
                ];

            } catch (\Exception $e) {
                $results[] = [
                    'warranty_id' => $warranty->id,
                    'manufacturer' => $warranty->manufacturer->name,
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }
}

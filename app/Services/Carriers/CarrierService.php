<?php

namespace App\Services\Carriers;

use App\Models\Carrier;
use App\Models\CarrierPickupRequest;
use App\Models\ReturnRequest;
use App\Models\StoreLocation;
use App\Contracts\Carriers\CarrierInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CarrierService
{
    protected $carriers = [];

    /**
     * Obtener instancia del carrier
     */
    public function getCarrier(string $code): ?CarrierInterface
    {
        if (!isset($this->carriers[$code])) {
            $carrier = Carrier::where('code', $code)->first();

            if (!$carrier || !$carrier->is_active) {
                return null;
            }

            // Instanciar la clase específica del carrier
            $className = "App\\Services\\Carriers\\{$code}Carrier";

            if (!class_exists($className)) {
                Log::warning("Carrier class not found: {$className}");
                return null;
            }

            $this->carriers[$code] = new $className();
        }

        return $this->carriers[$code];
    }

    /**
     * Obtener carriers disponibles para un código postal
     */
    public function getAvailableCarriers(string $postalCode, string $type = null): \Illuminate\Support\Collection
    {
        $query = Carrier::active();

        if ($type) {
            $query->where('type', $type);
        }

        return $query->get()->filter(function ($carrier) use ($postalCode) {
            return $carrier->isAvailableForPostalCode($postalCode);
        });
    }

    /**
     * Crear solicitud de recogida
     */
    public function createPickupRequest(ReturnRequest $returnRequest, array $data): CarrierPickupRequest
    {
        DB::beginTransaction();

        try {
            // Crear registro de solicitud
            $pickupRequest = CarrierPickupRequest::create([
                'return_request_id' => $returnRequest->id,
                'carrier_id' => $data['carrier_id'],
                'pickup_date' => $data['pickup_date'],
                'pickup_time_slot' => $data['pickup_time_slot'],
                'pickup_address' => $data['pickup_address'],
                'contact_name' => $data['contact_name'],
                'contact_phone' => $data['contact_phone'],
                'contact_email' => $data['contact_email'] ?? null,
                'packages_count' => $data['packages_count'] ?? 1,
                'total_weight' => $data['total_weight'] ?? $this->calculateWeight($returnRequest),
                'dimensions' => $data['dimensions'] ?? null,
                'status' => CarrierPickupRequest::STATUS_PENDING
            ]);

            // Obtener carrier
            $carrier = Carrier::find($data['carrier_id']);
            $carrierService = $this->getCarrier($carrier->code);

            if (!$carrierService) {
                throw new \Exception("Servicio de carrier no disponible: {$carrier->code}");
            }

            // Preparar datos para el carrier
            $carrierData = array_merge($data, [
                'return_number' => $returnRequest->getReturnNumber()
            ]);

            // Crear pickup en el servicio del carrier
            $result = $carrierService->createPickup($carrierData);

            if ($result['success']) {
                $pickupRequest->markAsConfirmed(
                    $result['pickup_code'] ?? null,
                    $result['response'] ?? null
                );

                if (isset($result['tracking_number'])) {
                    $pickupRequest->update(['tracking_number' => $result['tracking_number']]);
                    $returnRequest->update(['tracking_number' => $result['tracking_number']]);
                }

                DB::commit();

                Log::info('Pickup request created successfully', [
                    'return_id' => $returnRequest->id,
                    'carrier' => $carrier->code,
                    'pickup_code' => $result['pickup_code'] ?? null
                ]);

                return $pickupRequest;
            } else {
                throw new \Exception($result['error'] ?? 'Error desconocido al crear la recogida');
            }

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error creating pickup request', [
                'return_id' => $returnRequest->id,
                'error' => $e->getMessage()
            ]);

            if (isset($pickupRequest)) {
                $pickupRequest->markAsFailed($e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Cancelar solicitud de recogida
     */
    public function cancelPickupRequest(CarrierPickupRequest $pickupRequest): bool
    {
        if (!$pickupRequest->canBeCancelled()) {
            return false;
        }

        try {
            $carrier = $pickupRequest->carrier;
            $carrierService = $this->getCarrier($carrier->code);

            if ($carrierService && $pickupRequest->pickup_code) {
                $cancelled = $carrierService->cancelPickup($pickupRequest->pickup_code);

                if (!$cancelled) {
                    Log::warning('Carrier failed to cancel pickup', [
                        'pickup_id' => $pickupRequest->id,
                        'carrier' => $carrier->code
                    ]);
                }
            }

            $pickupRequest->cancel('Cancelado por el usuario');

            return true;

        } catch (\Exception $e) {
            Log::error('Error cancelling pickup request', [
                'pickup_id' => $pickupRequest->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Obtener estado de tracking
     */
    public function getTrackingStatus(string $trackingNumber, string $carrierCode): array
    {
        try {
            $carrierService = $this->getCarrier($carrierCode);

            if (!$carrierService) {
                return [
                    'success' => false,
                    'error' => 'Carrier no disponible'
                ];
            }

            return $carrierService->getTrackingStatus($trackingNumber);

        } catch (\Exception $e) {
            Log::error('Error getting tracking status', [
                'tracking' => $trackingNumber,
                'carrier' => $carrierCode,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generar etiqueta de envío
     */
    public function generateShippingLabel(CarrierPickupRequest $pickupRequest): string
    {
        try {
            $carrier = $pickupRequest->carrier;
            $carrierService = $this->getCarrier($carrier->code);

            if (!$carrierService) {
                throw new \Exception("Servicio de carrier no disponible");
            }

            $labelPath = $carrierService->generateLabel([
                'tracking_number' => $pickupRequest->tracking_number,
                'pickup_code' => $pickupRequest->pickup_code,
                'format' => 'PDF',
                'size' => 'A4'
            ]);

            // Registrar como documento
            \App\Models\Return\ReturnDocument::create([
                'return_request_id' => $pickupRequest->return_request_id,
                'document_type' => 'shipping_label',
                'file_path' => $labelPath,
                'file_name' => basename($labelPath),
                'file_size' => \Storage::size($labelPath),
                'generated_at' => now(),
                'metadata' => [
                    'carrier' => $carrier->code,
                    'tracking_number' => $pickupRequest->tracking_number
                ]
            ]);

            return $labelPath;

        } catch (\Exception $e) {
            Log::error('Error generating shipping label', [
                'pickup_id' => $pickupRequest->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Programar entrega en tienda
     */
    public function scheduleStoreDelivery(ReturnRequest $returnRequest, array $data): \App\Models\ReturnRequestStore
    {
        DB::beginTransaction();

        try {
            $store = StoreLocation::findOrFail($data['store_location_id']);

            // Verificar disponibilidad
            if (!$store->hasAvailability($data['expected_delivery_date'])) {
                throw new \Exception('La tienda no tiene disponibilidad para esa fecha');
            }

            // Crear registro
            $storeDelivery = \App\Models\ReturnRequestStore::create([
                'return_request_id' => $returnRequest->id,
                'store_location_id' => $store->id,
                'expected_delivery_date' => $data['expected_delivery_date'],
                'confirmation_code' => $this->generateConfirmationCode(),
                'status' => 'scheduled',
                'notes' => $data['notes'] ?? null
            ]);

            // Actualizar return request
            $returnRequest->update([
                'logistics_mode' => 'store_delivery',
                'carrier_id' => null
            ]);

            DB::commit();

            // Enviar confirmación al cliente
            $this->sendStoreDeliveryConfirmation($storeDelivery);

            return $storeDelivery;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error scheduling store delivery', [
                'return_id' => $returnRequest->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Buscar tiendas cercanas
     */
    public function findNearbyStores($latitude, $longitude, $radius = 10): \Illuminate\Support\Collection
    {
        return StoreLocation::active()
            ->acceptsReturns()
            ->nearby($latitude, $longitude, $radius)
            ->get()
            ->map(function ($store) {
                return [
                    'id' => $store->id,
                    'store_code' => $store->store_code,
                    'name' => $store->name,
                    'address' => $store->getFullAddress(),
                    'distance' => round($store->distance, 2),
                    'is_open' => $store->isOpen(),
                    'today_hours' => $store->getTodayHours(),
                    'phone' => $store->phone,
                    'maps_url' => $store->getGoogleMapsUrl()
                ];
            });
    }

    /**
     * Obtener horarios disponibles para recogida
     */
    public function getPickupTimeSlots(Carrier $carrier, string $postalCode, string $date): array
    {
        // Primero intentar obtener del servicio del carrier
        $carrierService = $this->getCarrier($carrier->code);

        if ($carrierService) {
            try {
                return $carrierService->getAvailableTimeSlots($postalCode, $date);
            } catch (\Exception $e) {
                Log::warning('Could not get time slots from carrier service', [
                    'carrier' => $carrier->code,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Si no, usar los horarios configurados en el carrier
        return $carrier->getAvailableTimeSlots(\Carbon\Carbon::parse($date));
    }

    /**
     * Calcular peso total de la devolución
     */
    protected function calculateWeight(ReturnRequest $returnRequest): float
    {
        return $returnRequest->products->sum(function ($product) {
            $orderProduct = $product->orderProduct;
            $weight = $orderProduct->weight ?? 0.5; // Peso por defecto si no está definido
            return $weight * $product->quantity;
        });
    }

    /**
     * Generar código de confirmación
     */
    protected function generateConfirmationCode(): string
    {
        return strtoupper(\Str::random(3) . '-' . \Str::random(3));
    }

    /**
     * Enviar confirmación de entrega en tienda
     */
    protected function sendStoreDeliveryConfirmation($storeDelivery): void
    {
        // Implementar envío de email/SMS con los detalles
        Log::info('Store delivery confirmation sent', [
            'return_id' => $storeDelivery->return_request_id,
            'store_id' => $storeDelivery->store_location_id,
            'confirmation_code' => $storeDelivery->confirmation_code
        ]);
    }

    /**
     * Actualizar estado de tracking masivo
     */
    public function updateTrackingStatuses(): void
    {
        $pickupRequests = CarrierPickupRequest::whereIn('status', [
            CarrierPickupRequest::STATUS_CONFIRMED,
            CarrierPickupRequest::STATUS_IN_TRANSIT,
            CarrierPickupRequest::STATUS_COLLECTED
        ])
            ->whereNotNull('tracking_number')
            ->get();

        foreach ($pickupRequests as $request) {
            try {
                $status = $this->getTrackingStatus(
                    $request->tracking_number,
                    $request->carrier->code
                );

                if ($status['success']) {
                    // Actualizar estado según respuesta
                    if ($status['delivered']) {
                        $request->markAsDelivered();
                    } elseif ($status['status'] === 'COLLECTED') {
                        $request->markAsCollected();
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error updating tracking status', [
                    'pickup_id' => $request->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}


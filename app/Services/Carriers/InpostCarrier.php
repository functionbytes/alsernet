<?php

namespace App\Services\Carriers;

use App\Contracts\Carriers\CarrierInterface;
use App\Models\Carrier\Carrier;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InpostCarrier implements CarrierInterface
{
    protected $carrier;
    protected $config;

    public function __construct()
    {
        $this->carrier = Carrier::where('code', 'INPOST')->first();
        $this->config = $this->carrier->getApiConfig();
    }

    public function createPickup(array $data): array
    {
        try {
            // InPost usa lockers, no recogida a domicilio
            // Crear envío para depositar en locker
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config['auth']['token'],
                'Content-Type' => 'application/json'
            ])->post($this->config['endpoint'] . '/parcels', [
                'sender' => [
                    'name' => $data['contact_name'],
                    'email' => $data['contact_email'],
                    'phone' => $this->formatPhoneNumber($data['contact_phone'])
                ],
                'receiver' => [
                    'name' => config('company.name'),
                    'email' => config('returns.email'),
                    'phone' => $this->formatPhoneNumber(config('warehouse.phone'))
                ],
                'parcels' => [
                    [
                        'template' => $this->getParcelSize($data['total_weight'], $data['dimensions'] ?? null),
                        'reference' => $data['return_number'],
                        'insurance' => [
                            'amount' => $data['insurance_amount'] ?? 0,
                            'currency' => 'EUR'
                        ]
                    ]
                ],
                'custom_attributes' => [
                    'target_point' => $data['locker_id'] ?? null,
                    'sending_method' => 'parcel_locker',
                    'return_shipment' => true
                ],
                'service' => 'inpost_locker_standard',
                'reference' => $data['return_number'],
                'comments' => 'Devolución de pedido'
            ]);

            if ($response->successful()) {
                $result = $response->json();

                return [
                    'success' => true,
                    'pickup_code' => $result['id'],
                    'tracking_number' => $result['tracking_number'],
                    'locker_id' => $result['custom_attributes']['target_point'],
                    'qr_code' => $result['qr_code'] ?? null,
                    'response' => $result
                ];
            }

            return [
                'success' => false,
                'error' => 'Error en la respuesta de InPost',
                'response' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error('InPost Parcel Creation Error', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function cancelPickup(string $pickupCode): bool
    {
        try {
            // En InPost se cancela el envío completo
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config['auth']['token']
            ])->delete($this->config['endpoint'] . '/parcels/' . $pickupCode);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('InPost Cancel Error', [
                'error' => $e->getMessage(),
                'parcel_id' => $pickupCode
            ]);
            return false;
        }
    }

    public function getTrackingStatus(string $trackingNumber): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config['auth']['token']
            ])->get($this->config['endpoint'] . '/parcels/tracking/' . $trackingNumber);

            if ($response->successful()) {
                $data = $response->json();

                // Mapear estados de InPost
                $statusMap = [
                    'created' => 'CREATED',
                    'offers_prepared' => 'PREPARED',
                    'offer_selected' => 'READY',
                    'dispatched_by_sender' => 'DISPATCHED',
                    'collected_from_sender' => 'COLLECTED',
                    'taken_by_courier' => 'IN_TRANSIT',
                    'adopted_at_source_branch' => 'IN_TRANSIT',
                    'sent_from_source_branch' => 'IN_TRANSIT',
                    'ready_to_pickup' => 'AVAILABLE_FOR_PICKUP',
                    'out_for_delivery' => 'OUT_FOR_DELIVERY',
                    'delivered' => 'DELIVERED',
                    'pickup_reminder_sent' => 'REMINDER_SENT',
                    'avizo' => 'AVIZO',
                    'claimed' => 'CLAIMED',
                    'returned_to_sender' => 'RETURNED'
                ];

                $currentStatus = $data['status'] ?? 'UNKNOWN';
                $mappedStatus = $statusMap[$currentStatus] ?? $currentStatus;

                return [
                    'success' => true,
                    'status' => $mappedStatus,
                    'location' => $data['custom_attributes']['target_point'] ?? null,
                    'locker_name' => $data['custom_attributes']['target_point_name'] ?? null,
                    'events' => $this->mapTrackingEvents($data['tracking_details'] ?? []),
                    'delivered' => in_array($currentStatus, ['delivered', 'picked_up']),
                    'available_for_pickup' => $currentStatus === 'ready_to_pickup',
                    'pickup_deadline' => $data['custom_attributes']['pickup_deadline'] ?? null,
                    'delivery_date' => $data['delivered_at'] ?? null,
                    'qr_code' => $data['qr_code'] ?? null
                ];
            }

            return [
                'success' => false,
                'error' => 'No se pudo obtener el estado del envío'
            ];

        } catch (\Exception $e) {
            Log::error('InPost Tracking Error', [
                'error' => $e->getMessage(),
                'tracking' => $trackingNumber
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function generateLabel(array $data): string
    {
        try {
            // InPost genera etiquetas automáticamente
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config['auth']['token']
            ])->get($this->config['endpoint'] . '/parcels/' . $data['pickup_code'] . '/label', [
                'format' => $data['format'] ?? 'pdf',
                'type' => $data['type'] ?? 'a4'
            ]);

            if ($response->successful()) {
                $pdfContent = $response->body();

                $path = 'carriers/labels/inpost/' . $data['tracking_number'] . '.pdf';
                \Storage::put($path, $pdfContent);

                return $path;
            }

            throw new \Exception('Error al generar etiqueta InPost');

        } catch (\Exception $e) {
            Log::error('InPost Label Generation Error', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    public function validateAddress(array $address): bool
    {
        // InPost no valida direcciones porque usa lockers
        // Validar que se haya seleccionado un locker
        return isset($address['locker_id']) && !empty($address['locker_id']);
    }

    public function getRates(array $package): array
    {
        try {
            // InPost tiene tarifas fijas por tamaño
            $size = $this->getParcelSize($package['weight'], $package['dimensions'] ?? null);

            $rates = [
                'A' => ['price' => 3.99, 'name' => 'Paquete pequeño (8x38x64cm, max 25kg)'],
                'B' => ['price' => 4.99, 'name' => 'Paquete mediano (19x38x64cm, max 25kg)'],
                'C' => ['price' => 5.99, 'name' => 'Paquete grande (41x38x64cm, max 25kg)']
            ];

            if (isset($rates[$size])) {
                return [[
                    'service' => 'inpost_locker_standard',
                    'size' => $size,
                    'price' => $rates[$size]['price'],
                    'name' => $rates[$size]['name'],
                    'delivery_time' => '24-48 horas'
                ]];
            }

            return [];
        } catch (\Exception $e) {
            Log::error('InPost Rates Error', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function getAvailableTimeSlots(string $postalCode, string $date): array
    {
        // InPost no tiene slots de tiempo porque es autoservicio
        // Devolver información de disponibilidad 24/7
        return [[
            'start' => '00:00',
            'end' => '23:59',
            'label' => 'Disponible 24/7 - Deposite cuando lo desee'
        ]];
    }

    /**
     * Obtener lockers cercanos
     */
    public function getNearbyLockers($latitude, $longitude, $radius = 5): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config['auth']['token']
            ])->get($this->config['endpoint'] . '/points', [
                'near_lat' => $latitude,
                'near_lng' => $longitude,
                'max_distance' => $radius * 1000, // metros
                'type' => 'parcel_locker',
                'functions' => 'parcel_send',
                'limit' => 20
            ]);

            if ($response->successful()) {
                $points = $response->json()['items'] ?? [];

                return array_map(function ($point) {
                    return [
                        'id' => $point['name'],
                        'name' => $point['name'],
                        'address' => $point['address']['line1'] . ', ' . $point['address']['line2'],
                        'city' => $point['address']['city'],
                        'postal_code' => $point['address']['post_code'],
                        'location' => [
                            'latitude' => $point['location']['latitude'],
                            'longitude' => $point['location']['longitude']
                        ],
                        'distance' => $point['distance'] ?? null,
                        'available' => $point['status'] === 'Operating',
                        'type' => $point['type'],
                        'opening_hours' => '24/7',
                        'payment_available' => $point['payment_available'] ?? false,
                        'functions' => $point['functions'] ?? []
                    ];
                }, $points);
            }

            return [];
        } catch (\Exception $e) {
            Log::error('InPost Get Lockers Error', [
                'error' => $e->getMessage(),
                'location' => [$latitude, $longitude]
            ]);
            return [];
        }
    }

    /**
     * Obtener detalles de un locker específico
     */
    public function getLockerDetails($lockerId): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config['auth']['token']
            ])->get($this->config['endpoint'] . '/points/' . $lockerId);

            if ($response->successful()) {
                $point = $response->json();

                return [
                    'id' => $point['name'],
                    'name' => $point['name'],
                    'address' => $point['address'],
                    'location' => $point['location'],
                    'status' => $point['status'],
                    'type' => $point['type'],
                    'opening_hours' => $point['opening_hours'] ?? '24/7',
                    'available_services' => $point['functions'] ?? [],
                    'payment_methods' => $point['payment_type'] ?? [],
                    'photo_url' => $point['image_url'] ?? null,
                    'directions' => $point['location_description'] ?? null
                ];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('InPost Locker Details Error', [
                'error' => $e->getMessage(),
                'locker_id' => $lockerId
            ]);
            return null;
        }
    }

    /**
     * Formatear número de teléfono para InPost
     */
    protected function formatPhoneNumber($phone): string
    {
        // InPost requiere formato internacional
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($phone) === 9 && in_array(substr($phone, 0, 1), ['6', '7', '9'])) {
            // Número español
            return '34' . $phone;
        }

        return $phone;
    }

    /**
     * Determinar tamaño del paquete
     */
    protected function getParcelSize($weight, $dimensions = null): string
    {
        if ($dimensions) {
            $length = $dimensions['length'] ?? 0;
            $width = $dimensions['width'] ?? 0;
            $height = $dimensions['height'] ?? 0;

            // Tamaños InPost
            if ($height <= 8 && $width <= 38 && $length <= 64) {
                return 'A';
            } elseif ($height <= 19 && $width <= 38 && $length <= 64) {
                return 'B';
            } elseif ($height <= 41 && $width <= 38 && $length <= 64) {
                return 'C';
            }
        }

        // Por defecto según peso
        if ($weight <= 5) {
            return 'A';
        } elseif ($weight <= 15) {
            return 'B';
        } else {
            return 'C';
        }
    }

    /**
     * Mapear eventos de tracking
     */
    protected function mapTrackingEvents(array $events): array
    {
        return array_map(function ($event) {
            return [
                'date' => $event['datetime'] ?? null,
                'status' => $event['status'] ?? null,
                'description' => $event['description'] ?? null,
                'location' => $event['location'] ?? null
            ];
        }, $events);
    }

    /**
     * Generar código QR para el paquete
     */
    public function generateQRCode($parcelId): ?string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config['auth']['token']
            ])->get($this->config['endpoint'] . '/parcels/' . $parcelId . '/qrcode', [
                'format' => 'png',
                'size' => 300
            ]);

            if ($response->successful()) {
                $qrContent = $response->body();

                $path = 'carriers/qrcodes/inpost/' . $parcelId . '.png';
                \Storage::put($path, $qrContent);

                return $path;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('InPost QR Generation Error', [
                'error' => $e->getMessage(),
                'parcel_id' => $parcelId
            ]);
            return null;
        }
    }
}

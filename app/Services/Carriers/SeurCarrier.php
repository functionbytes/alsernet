<?php

namespace App\Services\Carriers;

use App\Contracts\Carriers\CarrierInterface;
use App\Models\Carrier\Carrier;
use App\Models\Carrier\CarrierPickupRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SeurCarrier implements CarrierInterface
{
    protected ?Carrier $carrier = null;
    protected array $config = [];

    public function __construct()
    {
        $this->carrier = Carrier::where('code', 'SEUR')->first();

        if (!$this->carrier) {
            Log::error('SEUR Carrier not found in database');
            return;
        }

        $this->config = $this->carrier->getApiConfig() ?? [];
    }

    protected function getAuthHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . ($this->config['auth']['key'] ?? ''),
            'Content-Type' => 'application/json',
        ];
    }

    public function createPickup(array $data): array
    {
        try {
            $response = Http::withHeaders($this->getAuthHeaders())
                ->post($this->config['endpoint'] . '/pickup/create', [
                    'pickup_date' => $data['pickup_date'],
                    'pickup_time' => $data['pickup_time_slot'],
                    'address' => $data['pickup_address'],
                    'contact' => [
                        'name' => $data['contact_name'],
                        'phone' => $data['contact_phone'],
                        'email' => $data['contact_email']
                    ],
                    'packages' => $data['packages_count'],
                    'weight' => $data['total_weight'],
                    'reference' => $data['return_number']
                ]);

            if ($response->successful()) {
                $result = $response->json();
                return [
                    'success' => true,
                    'pickup_code' => $result['pickup_code'] ?? null,
                    'tracking_number' => $result['tracking_number'] ?? null,
                    'response' => $result
                ];
            }

            return [
                'success' => false,
                'error' => 'Error en la respuesta del servicio',
                'response' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error('SEUR Pickup Error', [
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
            $response = Http::withHeaders($this->getAuthHeaders())
                ->delete($this->config['endpoint'] . '/pickup/' . $pickupCode);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('SEUR Cancel Pickup Error', [
                'error' => $e->getMessage(),
                'pickup_code' => $pickupCode
            ]);
            return false;
        }
    }

    public function getTrackingStatus(string $trackingNumber): array
    {
        try {
            $response = Http::withHeaders($this->getAuthHeaders())
                ->get($this->config['endpoint'] . '/tracking/' . $trackingNumber);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'status' => $data['status'] ?? null,
                    'location' => $data['current_location'] ?? null,
                    'events' => $data['tracking_events'] ?? [],
                    'delivered' => ($data['status'] ?? '') === 'DELIVERED',
                    'delivery_date' => $data['delivery_date'] ?? null
                ];
            }

            return [
                'success' => false,
                'error' => 'No se pudo obtener el estado del envío'
            ];
        } catch (\Exception $e) {
            Log::error('SEUR Tracking Error', [
                'error' => $e->getMessage(),
                'tracking' => $trackingNumber
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function generateLabel(array $data): array
    {
        try {
            $response = Http::withHeaders($this->getAuthHeaders())
                ->post($this->config['endpoint'] . '/label/generate', [
                    'tracking_number' => $data['tracking_number'],
                    'format' => $data['format'] ?? 'PDF',
                    'size' => $data['size'] ?? 'A4'
                ]);

            if ($response->successful()) {
                $result = $response->json();

                $pdfContent = base64_decode($result['label_data']);
                $path = 'carriers/labels/seur/' . $data['tracking_number'] . '.pdf';
                Storage::put($path, $pdfContent);

                return [
                    'success' => true,
                    'path' => $path
                ];
            }

            return [
                'success' => false,
                'error' => 'Error al generar la etiqueta',
                'response' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('SEUR Label Generation Error', [
                'error' => $e->getMessage(),
                'tracking_number' => $data['tracking_number']
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

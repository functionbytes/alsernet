<?php

namespace App\Services\Carriers;

use App\Contracts\Carriers\CarrierInterface;
use App\Models\Carrier\Carrier;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CorreosCarrier implements CarrierInterface
{
    protected $carrier;
    protected $config;

    public function __construct()
    {
        $this->carrier = Carrier::where('code', 'CORREOS')->first();
        $this->config = $this->carrier->getApiConfig();
    }

    public function createPickup(array $data): array
    {
        try {
            // Autenticación con CORREOS
            $auth = $this->authenticate();

            if (!$auth['success']) {
                return $auth;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $auth['token'],
                'Content-Type' => 'application/json'
            ])->post($this->config['endpoint'] . '/recogidas', [
                'codContrato' => $this->config['contract_code'],
                'fechaRecogida' => $data['pickup_date'],
                'horaRecogidaDesde' => explode('-', $data['pickup_time_slot'])[0] ?? '09:00',
                'horaRecogidaHasta' => explode('-', $data['pickup_time_slot'])[1] ?? '14:00',
                'numeroEnvios' => $data['packages_count'],
                'peso' => $data['total_weight'] * 1000, // CORREOS usa gramos
                'remitente' => [
                    'nombre' => $data['contact_name'],
                    'direccion' => $data['pickup_address']['street'] . ' ' . $data['pickup_address']['number'],
                    'localidad' => $data['pickup_address']['city'],
                    'provincia' => $data['pickup_address']['province'],
                    'cp' => $data['pickup_address']['postal_code'],
                    'telefonoContacto' => $data['contact_phone'],
                    'email' => $data['contact_email']
                ],
                'observaciones' => 'Devolución ' . $data['return_number']
            ]);

            if ($response->successful()) {
                $result = $response->json();
                return [
                    'success' => true,
                    'pickup_code' => $result['codRecogida'],
                    'tracking_number' => $result['codEnvio'] ?? null,
                    'response' => $result
                ];
            }

            return [
                'success' => false,
                'error' => 'Error en la respuesta de CORREOS',
                'response' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error('CORREOS Pickup Error', [
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
            $auth = $this->authenticate();

            if (!$auth['success']) {
                return false;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $auth['token']
            ])->delete($this->config['endpoint'] . '/recogidas/' . $pickupCode);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('CORREOS Cancel Pickup Error', [
                'error' => $e->getMessage(),
                'pickup_code' => $pickupCode
            ]);
            return false;
        }
    }

    public function getTrackingStatus(string $trackingNumber): array
    {
        try {
            // CORREOS no requiere autenticación para tracking
            $response = Http::get($this->config['tracking_endpoint'] . '/' . $trackingNumber);

            if ($response->successful()) {
                $data = $response->json();

                // Mapear estados de CORREOS a estados genéricos
                $statusMap = [
                    'ADMITIDO' => 'ACCEPTED',
                    'EN TRANSITO' => 'IN_TRANSIT',
                    'EN REPARTO' => 'OUT_FOR_DELIVERY',
                    'ENTREGADO' => 'DELIVERED',
                    'DEVUELTO' => 'RETURNED'
                ];

                $currentStatus = $data['estado'] ?? 'UNKNOWN';
                $mappedStatus = $statusMap[$currentStatus] ?? $currentStatus;

                return [
                    'success' => true,
                    'status' => $mappedStatus,
                    'location' => $data['ultimaUbicacion'] ?? null,
                    'events' => $this->mapTrackingEvents($data['eventos'] ?? []),
                    'delivered' => $mappedStatus === 'DELIVERED',
                    'delivery_date' => $data['fechaEntrega'] ?? null
                ];
            }

            return [
                'success' => false,
                'error' => 'No se pudo obtener el estado del envío'
            ];

        } catch (\Exception $e) {
            Log::error('CORREOS Tracking Error', [
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
            $auth = $this->authenticate();

            if (!$auth['success']) {
                throw new \Exception('Error de autenticación con CORREOS');
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $auth['token']
            ])->post($this->config['endpoint'] . '/etiquetas', [
                'codEnvio' => $data['tracking_number'],
                'formato' => 'PDF',
                'tamano' => 'A4'
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $pdfContent = base64_decode($result['etiqueta']);

                $path = 'carriers/labels/correos/' . $data['tracking_number'] . '.pdf';
                \Storage::put($path, $pdfContent);

                return $path;
            }

            throw new \Exception('Error al generar etiqueta');

        } catch (\Exception $e) {
            Log::error('CORREOS Label Generation Error', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    public function validateAddress(array $address): bool
    {
        // CORREOS puede validar direcciones españolas
        try {
            $response = Http::get($this->config['endpoint'] . '/validar-direccion', [
                'direccion' => $address['street'] . ' ' . $address['number'],
                'cp' => $address['postal_code'],
                'localidad' => $address['city']
            ]);

            return $response->successful() && $response->json()['valida'] === true;
        } catch (\Exception $e) {
            Log::warning('CORREOS Address Validation Error', [
                'error' => $e->getMessage()
            ]);
            return true; // Asumir válida si el servicio falla
        }
    }

    public function getRates(array $package): array
    {
        try {
            $auth = $this->authenticate();

            if (!$auth['success']) {
                return [];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $auth['token']
            ])->post($this->config['endpoint'] . '/tarifas', [
                'peso' => $package['weight'] * 1000, // gramos
                'cpOrigen' => $package['origin_postal_code'],
                'cpDestino' => $package['destination_postal_code'],
                'largo' => $package['dimensions']['length'] ?? 30,
                'ancho' => $package['dimensions']['width'] ?? 20,
                'alto' => $package['dimensions']['height'] ?? 10
            ]);

            if ($response->successful()) {
                return array_map(function ($rate) {
                    return [
                        'service' => $rate['producto'],
                        'price' => $rate['precio'],
                        'delivery_time' => $rate['plazoEntrega']
                    ];
                }, $response->json()['tarifas'] ?? []);
            }

            return [];
        } catch (\Exception $e) {
            Log::error('CORREOS Rates Error', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function getAvailableTimeSlots(string $postalCode, string $date): array
    {
        // CORREOS tiene horarios fijos por zona
        $zone = $this->getZoneByPostalCode($postalCode);

        $slots = [
            'morning' => ['09:00-14:00', '10:00-13:00'],
            'afternoon' => ['16:00-20:00', '17:00-19:00']
        ];

        // Ajustar según la zona
        if ($zone === 'remote') {
            return [
                ['start' => '09:00', 'end' => '14:00', 'label' => 'Mañana (09:00-14:00)']
            ];
        }

        $availableSlots = [];
        foreach ($slots as $period => $times) {
            foreach ($times as $time) {
                list($start, $end) = explode('-', $time);
                $availableSlots[] = [
                    'start' => $start,
                    'end' => $end,
                    'label' => ucfirst($period) . " ({$time})"
                ];
            }
        }

        return $availableSlots;
    }

    /**
     * Autenticar con CORREOS
     */
    protected function authenticate(): array
    {
        try {
            $response = Http::post($this->config['auth_endpoint'], [
                'usuario' => $this->config['auth']['username'],
                'password' => $this->config['auth']['secret']
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'token' => $response->json()['token']
                ];
            }

            return [
                'success' => false,
                'error' => 'Error de autenticación'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Mapear eventos de tracking
     */
    protected function mapTrackingEvents(array $events): array
    {
        return array_map(function ($event) {
            return [
                'date' => $event['fecha'],
                'time' => $event['hora'],
                'status' => $event['estado'],
                'location' => $event['oficina'] ?? $event['localidad'] ?? null,
                'description' => $event['descripcion'] ?? null
            ];
        }, $events);
    }

    /**
     * Obtener zona por código postal
     */
    protected function getZoneByPostalCode(string $postalCode): string
    {
        $prefix = substr($postalCode, 0, 2);

        // Zonas remotas (Canarias, Ceuta, Melilla)
        if (in_array($prefix, ['35', '38', '51', '52'])) {
            return 'remote';
        }

        // Zonas urbanas principales
        if (in_array($prefix, ['28', '08', '46', '41', '48'])) {
            return 'urban';
        }

        return 'standard';
    }

}

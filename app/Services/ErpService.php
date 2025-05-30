<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class ErpService
{
    protected Client $client;
    protected string $urlErp;

    // Constantes para formas de pago
    const PAYMENT_CASHONDELIVERY = 1;
    const PAYMENT_WIRE = 2;
    const PAYMENT_CREDITCARD = 3;
    const PAYMENT_BIZUM = 4;
    const PAYMENT_REDSYS = 5;
    const PAYMENT_GOOGLE = 6;
    const PAYMENT_APPLE = 7;
    const PAYMENT_PAYPAL = 8;
    const PAYMENT_FINANCE = 9;
    const PAYMENT_SEQURA = 10;
    const PAYMENT_ALSERNETFINANCE = 11;
    const PAYMENT_TRANSFERENCIA_ONLINE = 12;
    const PAYMENT_BAN_LENDISMART = 13;

    public function __construct()
    {
        $this->urlErp = rtrim(config('services.erp.url', env('ERP_URL')), '/');

        $this->client = new Client([
            'base_uri' => $this->urlErp,
            'timeout' => config('services.erp.timeout', 30),
            'connect_timeout' => config('services.erp.connect_timeout', 30),
            'http_errors' => false,
            'headers' => [
                'User-Agent' => 'Laravel/ErpService',
                'Accept' => 'application/xml',
                'Connection' => 'close',
            ],
            'curl' => [
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            ],
        ]);
    }

    /**
     * Realizar petición GET al ERP
     */
    public function get(string $endpoint, array $params = []): ?array
    {
        try {
            $response = $this->client->get($endpoint, [
                'query' => $params,
            ]);

            $status = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            if ($status === 200 && !empty($body)) {
                return $this->parseXmlResponse($body, $endpoint);
            }

            Log::error("GET {$endpoint} -> Respuesta no exitosa", [
                'status' => $status,
                'body' => $body
            ]);
            return null;

        } catch (RequestException $e) {
            $this->logRequestException($e, 'GET', $endpoint);
            return null;
        } catch (\Exception $e) {
            Log::error("GET {$endpoint} -> Error inesperado: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Realizar petición POST al ERP
     */
    public function post(string $endpoint, array $data = []): ?array
    {
        try {
            $response = $this->client->post($endpoint, [
                'form_params' => $data,
            ]);

            $status = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            if ($status === 200 && !empty($body)) {
                return $this->parseXmlResponse($body, $endpoint);
            }

            Log::error("POST {$endpoint} -> Respuesta no exitosa", [
                'status' => $status,
                'body' => $body
            ]);
            return null;

        } catch (RequestException $e) {
            $this->logRequestException($e, 'POST', $endpoint);
            return null;
        } catch (\Exception $e) {
            Log::error("POST {$endpoint} -> Error inesperado: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Realizar petición PUT al ERP
     */
    public function put(string $endpoint, array $data = []): ?array
    {
        try {
            $response = $this->client->put($endpoint, [
                'form_params' => $data,
                'headers' => [
                    'Accept' => 'application/xml',
                ],
            ]);

            $status = $response->getStatusCode();
            $body = trim($response->getBody()->getContents());

            Log::info("PUT {$endpoint} -> Raw Response", ['body' => $body]);

            // Manejar respuesta "OK" simple
            if ($status === 200 && $body === 'OK') {
                return [
                    'status' => 'success',
                    'message' => 'Operation completed successfully.'
                ];
            }

            if ($status === 200 && !empty($body)) {
                return $this->parseXmlResponse($body, $endpoint);
            }

            Log::error("PUT {$endpoint} -> Respuesta no exitosa", [
                'status' => $status,
                'body' => $body
            ]);
            return null;

        } catch (RequestException $e) {
            $this->logRequestException($e, 'PUT', $endpoint);
            return null;
        } catch (\Exception $e) {
            Log::error("PUT {$endpoint} -> Error inesperado: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Realizar petición DELETE al ERP
     */
    public function delete(string $endpoint, array $params = []): ?array
    {
        try {
            $response = $this->client->delete($endpoint, [
                'query' => $params,
            ]);

            $status = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            if ($status === 200 && !empty($body)) {
                return $this->parseXmlResponse($body, $endpoint);
            }

            Log::error("DELETE {$endpoint} -> Respuesta no exitosa", [
                'status' => $status,
                'body' => $body
            ]);
            return null;

        } catch (RequestException $e) {
            $this->logRequestException($e, 'DELETE', $endpoint);
            return null;
        } catch (\Exception $e) {
            Log::error("DELETE {$endpoint} -> Error inesperado: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Parsear respuesta XML a array
     */
    private function parseXmlResponse(string $xmlContent, string $endpoint): ?array
    {
        libxml_use_internal_errors(true);
        $xmlObject = simplexml_load_string($xmlContent);

        if ($xmlObject === false) {
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                Log::error("XML Parsing Error", [
                    'endpoint' => $endpoint,
                    'error' => $error->message
                ]);
            }
            libxml_clear_errors();
            return null;
        }

        try {
            $json = json_encode($xmlObject, JSON_THROW_ON_ERROR);
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            Log::info("Datos recibidos de {$endpoint}", ['data' => $data]);
            return $data;
        } catch (\JsonException $e) {
            Log::error("Error al convertir JSON en {$endpoint}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Registrar excepciones de peticiones HTTP
     */
    private function logRequestException(RequestException $e, string $method, string $endpoint): void
    {
        $response = $e->getResponse();
        $errorBody = $response ? $response->getBody()->getContents() : 'No response body';
        $statusCode = $response ? $response->getStatusCode() : 'No status code';

        Log::error("Error HTTP en {$method} {$endpoint}", [
            'message' => $e->getMessage(),
            'status' => $statusCode,
            'body' => $errorBody
        ]);
    }

    // ------------------------------------------------------------------
    // MÉTODOS DE NEGOCIO
    // ------------------------------------------------------------------

    /**
     * Recuperar cliente del ERP por ID Web
     */
    public function recuperarClienteErp(int $idWeb): ?array
    {
        $endpoint = "/api-gestion/cliente/";
        $params = ['idclienteweb' => $idWeb];

        return $this->get($endpoint, $params);
    }

    /**
     * Recuperar ID de cliente ERP por ID Web
     */
    public function recuperarIdClienteErp(int $idWeb): ?string
    {
        $data = $this->recuperarClienteErp($idWeb);

        if ($data && isset($data['idcliente'])) {
            return $data['idcliente'];
        }

        return null;
    }

    /**
     * Recuperar pedidos de cliente
     */
    public function recuperarPedidosCliente(int $idWeb): ?array
    {
        $idCliente = $this->recuperarIdClienteErp($idWeb);

        if (!$idCliente) {
            return null;
        }

        $endpoint = "/api-gestion/pedido-cliente/";
        $params = ['idcliente' => $idCliente];

        $data = $this->get($endpoint, $params);

        return $data ? $this->formatOrderArrayErp($data) : null;
    }

    /**
     * Recuperar pedido por número y serie
     */
    public function recuperarPedido(string $nPedidoCli, string $serie): ?array
    {
        $endpoint = "/api-gestion/pedido-cliente/";
        $params = [
            'serie' => $serie,
            'npedidocli' => $nPedidoCli
        ];

        $data = $this->get($endpoint, $params);

        return $data ? $this->formatOrderArrayErp($data) : null;
    }

    /**
     * Recuperar pedido por identificador de origen
     */
    public function retrieveOrderById(string $identificadorOrigen): ?array
    {
        $endpoint = '/api-gestion/pedido-cliente/';
        $params = ['identificadororigen' => $identificadorOrigen];

        $data = $this->get($endpoint, $params);

        if (!$data) {
            Log::warning("No se pudo recuperar el pedido con identificador: {$identificadorOrigen}");
            return null;
        }

        return $this->formatOrderArrayErp($data);
    }

    public function retrieveOrderHistoryById(string $identificadorOrigen): ?array
    {
        $endpoint = '/api-gestion/pedido-cliente-hist/';
        $params = ['identificadororigen' => $identificadorOrigen];

        $data = $this->get($endpoint, $params);

        if (!$data) {
            Log::warning("No se pudo recuperar el pedido con identificador: {$identificadorOrigen}");
            return null;
        }

        return $this->formatOrderArrayErp($data);
    }


    /**
     * Recuperar cliente del ERP por email
     */
    public function retrieveErpClientId(string $identificadorOrigen): ?array
    {
        $endpoint = '/api-gestion/cliente/';
        $params = ['idcliente_gestion' => $identificadorOrigen];

        $data = $this->get($endpoint, $params);

        if (!$data) {
            return null;
        }

        return $data;
    }


    /**
     * Guardar cliente en ERP
     */
    public function saveErpClient(array $clientData): JsonResponse
    {
        try {
            // Filtrar datos vacíos
            $data = array_filter($clientData);

            $response = $this->post('/api-gestion/cliente/', $data);

            if (is_array($response) && isset($response['status']) && $response['status'] === 'success') {
                return response()->json([
                    'status' => 'success',
                    'body' => $response,
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Error saving ERP client.',
            ], 400);

        } catch (\Exception $e) {
            Log::error('Error guardando cliente: ' . $e->getMessage());

            return response()->json([
                'error' => 'Error saving client: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Guardar LOPD del cliente
     */
    public function saveLopd(string $email, string $date, string $commercial, string $parties): JsonResponse
    {
        if (!$email) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email is required.',
            ], 400);
        }

        $data = [
            'cliente_email' => $email,
            'cliente_faceptacion_lopd' => date('Y-m-d\TH:i:s', strtotime($date)),
            'cliente_no_info_comercial' => $commercial,
            'cliente_no_datos_a_terceros' => $parties,
        ];

        try {
            $response = $this->put('/api-gestion/cliente/', $data);

            if (is_array($response) && isset($response['status']) && $response['status'] === 'success') {
                return response()->json([
                    'status' => 'success',
                    'body' => $response,
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Error saving LOPD data.',
            ], 400);

        } catch (\Exception $e) {
            Log::error('Error guardando LOPD: ' . $e->getMessage());

            return response()->json([
                'error' => 'Error saving LOPD: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Recuperar stock central
     */
    public function recuperarStockCentral(int $idArticulo): float
    {
        $endpoint = "/api-gestion/stock-central-web/{$idArticulo}/";
        $data = $this->get($endpoint);

        if ($data && isset($data['unidades'])) {
            return (float) $data['unidades'];
        }

        return 0.0;
    }

    /**
     * Recuperar ID de artículo por código
     */
    public function recuperarIdArticulo(string $codigo): ?string
    {
        $endpoint = "/api-gestion/articulo/{$codigo}/";
        $data = $this->get($endpoint);

        if ($data && isset($data['idarticulo'])) {
            return $data['idarticulo'];
        }

        return null;
    }

    /**
     * Consultar bono
     */
    public function consultaBono(string $idBono, string $codigoVerificacion, float $importeVenta, string $origen): array
    {
        $endpoint = "/api-gestion/bono/{$idBono}/";
        $params = [
            'codigo_verificacion' => $codigoVerificacion,
            'importe_venta' => $importeVenta,
            'origen' => $origen
        ];

        $data = $this->get($endpoint, $params);

        if ($data) {
            return [
                'success' => true,
                'data' => $data
            ];
        }

        return [
            'success' => false,
            'message' => 'Bono no encontrado o inválido'
        ];
    }

    /**
     * Marcar bono como usado
     */
    public function marcarBono(
        string $idBono,
        string $operacion,
        string $codigoVerificacion,
        float $importeVenta,
        float $importeInicialTarjetaRegalo,
        string $origen
    ): ?array {
        $endpoint = "/api-gestion/bono/{$idBono}/";
        $data = [
            'operacion' => $operacion,
            'codigo_verificacion' => $codigoVerificacion,
            'importe_venta' => $importeVenta,
            'importe_inicial_tarjeta_regalo' => $importeInicialTarjetaRegalo,
            'origen' => $origen
        ];

        return $this->put($endpoint, $data);
    }

    /**
     * Formatear array de pedidos del ERP
     */
    private function formatOrderArrayErp(?array $data): ?array
    {
        if (!$data) {
            return null;
        }

        return $data;
    }

    /**
     * Obtener idioma de gestión
     */
    public function getIdiomaGestion(int $lang): int
    {
        $idiomas = [
            1 => 1,
            2 => 6,
            3 => 3,
            4 => 4,
            5 => 5,
            6 => 6,
            7 => 9,
            8 => 10,
            9 => 1395,
        ];

        return $idiomas[$lang] ?? 1;
    }

    /**
     * Obtener país de gestión
     */
    public function getPaisGestion(int $lang): int
    {
        $paises = [
            1 => 1,
            2 => 48,
            3 => 4,
            4 => 2,
            5 => 3,
            6 => 42,
        ];

        return $paises[$lang] ?? 1;
    }

    /**
     * Verificar si es teléfono móvil
     */
    public function isMobilePhone(string $number, int $countryId): bool
    {
        // Limpiar número
        $number = preg_replace('/[^0-9]/', '', $number);

        // Verificar por país (ejemplo para España)
        if ($countryId === 6) { // España
            return preg_match('/^(6|7)\d{8}$/', $number) === 1;
        }

        // Agregar más países según necesidad

        return false;
    }

    /**
     * Enviar pedido al ERP
     */
    public function mandarPedido(int $idPedido, ?string $idClienteGestion = null): ?array
    {
        $data = $this->construirDatosPedido($idPedido, $idClienteGestion);

        if (!$data) {
            Log::error("No se pudieron construir los datos del pedido {$idPedido}");
            return null;
        }

        $endpoint = "/api-gestion/pedido-cliente/";
        return $this->post($endpoint, $data);
    }

    /**
     * Construir datos de pedido para envío
     */
    private function construirDatosPedido(int $idPedido, ?string $idClienteGestion): ?array
    {
        // Esta función necesitaría acceso a los modelos de tu aplicación
        // Por ejemplo: Order, Customer, Address, etc.
        // Aquí un ejemplo básico:

        try {
            // Obtener pedido de la base de datos
            $order = DB::table('orders')->find($idPedido);

            if (!$order) {
                return null;
            }

            // Construir array de datos según formato del ERP
            $data = [
                'identificador_origen' => $idPedido,
                'fecha_pedido' => date('Y-m-d\TH:i:s', strtotime($order->created_at)),
                'zona_fiscal' => $this->determinarZonaFiscal($order->country_id),
                // ... más campos según necesidad
            ];

            if ($idClienteGestion) {
                $data['idcliente_gestion'] = $idClienteGestion;
            }

            return $data;

        } catch (\Exception $e) {
            Log::error("Error construyendo datos de pedido: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Determinar zona fiscal según país
     */
    private function determinarZonaFiscal(int $countryId): int
    {
        $paisesZona1 = [242, 6, 244, 40, 45, 2, 47, 3, 52, 231, 233, 76, 106, 74, 20, 37, 191, 86, 7, 8, 93, 97, 9, 101, 142, 26, 108, 10, 115, 124, 129, 130, 12, 132, 138, 146, 147, 149, 23, 13, 14, 16, 36, 175, 184, 188, 18, 19, 209, 214, 1];

        if (in_array($countryId, $paisesZona1)) {
            return 1;
        } elseif (in_array($countryId, [15, 245])) {
            return 2;
        } else {
            return 3;
        }
    }

    /**
     * Obtener forma de pago según módulo
     */
    public function getFormaPago(string $module, int $idPedido): int
    {
        $formasPago = [
            'ps_cashondelivery' => self::PAYMENT_CASHONDELIVERY,
            'ps_wirepayment' => self::PAYMENT_WIRE,
            'paypal' => self::PAYMENT_PAYPAL,
            'sequra' => self::PAYMENT_SEQURA,
            'alsernetfinance' => self::PAYMENT_ALSERNETFINANCE,
            'inespay' => self::PAYMENT_TRANSFERENCIA_ONLINE,
            'banlendismart' => self::PAYMENT_BAN_LENDISMART,
        ];

        // Casos especiales
        if ($module === 'ceca') {
            // Verificar si es Bizum consultando la BD
            $tpv = DB::table('ceca_transactions')
                ->where('order_id', $idPedido)
                ->value('ceca_tpv_id');

            return $tpv == config('services.erp.bizum_tpv_id')
                ? self::PAYMENT_BIZUM
                : self::PAYMENT_CREDITCARD;
        }

        if ($module === 'redsys') {
            // Similar lógica para Redsys
            $tpv = DB::table('redsys_transactions')
                ->where('order_id', $idPedido)
                ->value('tpv_id');

            if ($tpv == config('services.erp.google_tpv_id')) {
                return self::PAYMENT_GOOGLE;
            }
            if ($tpv == config('services.erp.apple_tpv_id')) {
                return self::PAYMENT_APPLE;
            }
            return self::PAYMENT_REDSYS;
        }

        return $formasPago[$module] ?? -1;
    }

    /**
     * Cache de respuestas del ERP
     */
    protected function cacheGet(string $key, callable $callback, int $ttl = 3600)
    {
        return Cache::remember($key, $ttl, $callback);
    }
}

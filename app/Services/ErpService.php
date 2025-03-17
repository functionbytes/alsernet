<?php

namespace App\Services;

use Buzz\Exception\RequestException;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use JsonException;

class ErpService
{
    protected $client;
    protected $urlErp;

    public function __construct()
    {
        $this->urlErp = env('ERP_URL');
        $this->client = new Client([
            'base_uri' => $this->urlErp,
            'timeout' => 30,
            'connect_timeout' => 30,
            'http_errors' => false,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0',
                'Accept' => 'application/xml',
                'Connection' => 'close',
            ],
            'curl' => [
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            ],
        ]);
    }

    public function get(string $endpoint, array $params = [])
    {
        try {

            $response = $this->client->get($endpoint, [
                'query' => $params,
            ]);

            $status = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            if ($status === 200 && !empty($body)) {

                $xmlObject = @simplexml_load_string($body);

                if ($xmlObject === false) {
                    Log::error("GET {$endpoint} -> Error al parsear XML: {$body}");
                    return null;
                }

                $json = json_encode($xmlObject, JSON_THROW_ON_ERROR);
                $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

                Log::info("GET {$endpoint} -> Datos recibidos", ['data' => $data]);
                return $data;

            }

            Log::error("GET {$endpoint} -> Respuesta no exitosa: {$status}, Body: {$body}");
            return null;

        } catch (\JsonException $e) {
            Log::error("GET {$endpoint} -> Error al convertir JSON: " . $e->getMessage());
            return null;

        } catch (RequestException $e) {
            $this->logRequestException($e, 'GET', $endpoint);
            return null;

        } catch (\Exception $e) {
            Log::error("GET {$endpoint} -> Error inesperado: " . $e->getMessage());
            return null;
        }
    }

    // Método POST
    public function post(string $endpoint, array $data = [])
    {
        try {
            $response = $this->client->post($endpoint, [
                'form_params' => $data,
            ]);

            $status = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            if ($status === 200 && !empty($body)) {
                $xmlObject = @simplexml_load_string($body);
                if ($xmlObject === false) {
                    Log::error("POST {$endpoint} -> Error al parsear XML: {$body}");
                    return null;
                }

                $json = json_encode($xmlObject, JSON_THROW_ON_ERROR);
                $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

                Log::info("POST {$endpoint} -> Datos recibidos", ['data' => $data]);
                return $data;
            }

            Log::error("POST {$endpoint} -> Respuesta no exitosa: {$status}, Body: {$body}");
            return null;

        } catch (\JsonException $e) {
            Log::error("POST {$endpoint} -> Error al convertir JSON: " . $e->getMessage());
            return null;

        } catch (RequestException $e) {
            $this->logRequestException($e, 'POST', $endpoint);
            return null;

        } catch (\Exception $e) {
            Log::error("POST {$endpoint} -> Error inesperado: " . $e->getMessage());
            return null;
        }
    }

// Método PUT
    public function put(string $endpoint, array $data = [])
    {
        try {
            // Send PUT request with form parameters
            $response = $this->client->put($endpoint, [
                'form_params' => $data,
                'headers' => [
                    'Accept' => 'application/xml',
                ],
            ]);

            $status = $response->getStatusCode();
            $body = trim($response->getBody()->getContents());

            // Log the raw body for debugging
            Log::info("PUT {$endpoint} -> Raw Response Body", ['body' => $body]);

            // Handle plain text "OK" response
            if ($status === 200 && $body === 'OK') {
                return [
                    'status' => 'success',
                    'message' => 'Operation completed successfully.'
                ];
            }

            // Attempt to parse XML if response is not empty
            if ($status === 200 && !empty($body)) {
                libxml_use_internal_errors(true);
                $xmlObject = simplexml_load_string($body);

                if ($xmlObject === false) {
                    $errors = libxml_get_errors();
                    foreach ($errors as $error) {
                        Log::error("XML Parsing Error: " . $error->message);
                    }
                    libxml_clear_errors();

                    Log::error("PUT {$endpoint} -> Error al parsear XML: {$body}");
                    return null;
                }

                try {
                    $json = json_encode($xmlObject, JSON_THROW_ON_ERROR);
                    $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

                    Log::info("PUT {$endpoint} -> Datos recibidos", ['data' => $data]);
                    return $data;
                } catch (\JsonException $e) {
                    Log::error("PUT {$endpoint} -> Error al convertir JSON: " . $e->getMessage());
                    return null;
                }
            }

            // Log for non-200 or empty responses
            Log::error("PUT {$endpoint} -> Respuesta no exitosa: {$status}, Body: {$body}");
            return null;

        } catch (RequestException $e) {
            $this->logRequestException($e, 'PUT', $endpoint);
            return null;

        } catch (\Exception $e) {
            Log::error("PUT {$endpoint} -> Error inesperado: " . $e->getMessage());
            return null;
        }
    }



// Método DELETE
    public function delete(string $endpoint, array $data = [])
    {
        try {
            $response = $this->client->delete($endpoint, [
                'query' => $data,
            ]);

            $status = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            if ($status === 200 && !empty($body)) {
                $xmlObject = @simplexml_load_string($body);
                if ($xmlObject === false) {
                    Log::error("DELETE {$endpoint} -> Error al parsear XML: {$body}");
                    return null;
                }

                $json = json_encode($xmlObject, JSON_THROW_ON_ERROR);
                $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

                Log::info("DELETE {$endpoint} -> Datos recibidos", ['data' => $data]);
                return $data;
            }

            Log::error("DELETE {$endpoint} -> Respuesta no exitosa: {$status}, Body: {$body}");
            return null;

        } catch (\JsonException $e) {
            Log::error("DELETE {$endpoint} -> Error al convertir JSON: " . $e->getMessage());
            return null;

        } catch (RequestException $e) {
            $this->logRequestException($e, 'DELETE', $endpoint);
            return null;

        } catch (\Exception $e) {
            Log::error("DELETE {$endpoint} -> Error inesperado: " . $e->getMessage());
            return null;
        }
    }

// Método para loguear errores detallados de RequestException
    private function logRequestException(RequestException $e, string $method, string $endpoint)
    {
        $response = $e->getResponse();
        $errorBody = $response ? $response->getBody()->getContents() : 'No response body';
        $statusCode = $response ? $response->getStatusCode() : 'No status code';

        Log::error("{$method} {$endpoint} -> Error HTTP: " . $e->getMessage() . ", Status: {$statusCode}, Body: {$errorBody}");
    }



    // ------------------------------------------------------------------
    // MÉTODOS DE NEGOCIO (adaptados para usar los métodos HTTP anteriores)
    // ------------------------------------------------------------------

    public function recuperarClienteErp($idWeb)
    {
        $endpoint = $this->urlErp."cliente/";
        $params = ['idclienteweb' => $idWeb];
        $content = $this->get($endpoint, $params);
        if ($content && $content !== 'Not Found') {
            $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
            $json = json_encode($xml);
            return json_decode($json, true);
        }
        return null;
    }

    public function recuperaridclienteerp($idweb)
    {
        $endpoint = $this->urlErp."cliente/?idclienteweb=" . $idweb;
        $content = $this->get($endpoint);
        if ($content != "") {
            if ($content != "Not Found") {
                $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
                $json = json_encode($xml);
                $array = json_decode($json, true);
                return (isset($array['idcliente']) && $array['idcliente']) ? $array['idcliente'] : '';
            } else {
                return '';
            }
        } else {
            return '';
        }
    }

    public function recuperarpedidoscliente($idweb)
    {
        $idcliente = $this->recuperaridclienteerp($idweb);
        if ($idcliente != '') {
            $endpoint = "pedido-cliente/?idcliente=" . $idcliente;
            $content = $this->get($endpoint);
            $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
            $json = json_encode($xml);
            $array = json_decode($json, true);
            return $this->formatOrderArrayErp($array);
        } else {
            return false;
        }
    }

    public function recuperarpedido($npedidocli, $serie)
    {
        $endpoint = "pedido-cliente/?serie=" . $serie . "&npedidocli=" . $npedidocli;
        $content = $this->get($endpoint);
        $xml = simplexml_load_string($content, "SimpleXMLElement", LIBXML_NOCDATA);
        $json = json_encode($xml);
        $array = json_decode($json, true);
        return $this->formatOrderArrayErp($array);
    }

    public function recuperarpedidoporid($identificadororigen)
    {
        $endpoint = "pedido-cliente/?identificadororigen=" . $identificadororigen;
        $content = $this->get($endpoint);
        $xml = simplexml_load_string($content, "SimpleXMLElement", LIBXML_NOCDATA);
        $json = json_encode($xml);
        $array = json_decode($json, true);
        return $this->formatOrderArrayErp($array);
    }



    public function recuperardatosclienteerp($dni, $apellidos, $email, $telefono)
    {
        $endpoint = $this->urlErp.$this->urlErp."cliente/?dni=" . $dni . "&apellidos=" . $apellidos . "&email=" . $email . "&telefono1=" . $telefono;
        return $this->get($endpoint);
    }

    public function recuperardatosclienteerpporidweb($idweb)
    {
        $endpoint = $this->urlErp."cliente/?idclienteweb=" . $idweb;
        return $this->get($endpoint);
    }

    public function recuperardatosclienteerpporidgestion($idgestion)
    {
        $endpoint = $this->urlErp."cliente/?idcliente_gestion=" . $idgestion;
        return $this->get($endpoint);
    }

    public function getIdiomaGestion($lang)
    {
        switch ($lang) {
            case 1: return 1;
            case 2: return 6;
            case 3: return 3;
            case 4: return 4;
            case 5: return 5;
            case 6: return 6;
            case 7: return 9;
            case 8: return 10;
            case 9: return 1395;
            default: return false;
        }
    }

    public function getPaisGestion($lang)
    {
        switch ($lang) {
            case 1: return 1;
            case 2: return 48;
            case 3: return 4;
            case 4: return 2;
            case 5: return 3;
            case 6: return 42;
            default: return false;
        }
    }


    public function retrieveErpClient($email)
    {
        try {

            $response = $this->get('/api-gestion/cliente/', [
                'email' => $email,
            ]);

            if ($response) {
                return response()->json([
                    'status' => 'success',
                    'body' => $response,
                ]);
            }
            return response()->json([
                'status' => 'error',
                'message' => 'No client data found.',
            ], 404);

        } catch (RequestException $e) {
            Log::error('Error al conectar con la API: ' . $e->getMessage());

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);

        } catch (\Exception $e) {
            Log::error('Error inesperado: ' . $e->getMessage());

            return response()->json([
                'error' => 'Unexpected error: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function saveErpClient(
        $idcliente_gestion,
        $cliente_nombre,
        $cliente_apellidos,
        $cliente_cif,
        $cliente_email,
        $cliente_percontacto,
        $cliente_observaciones,
        $cliente_idioma,
        $cliente_codigo_internet,
        $cliente_calle,
        $cliente_codigopostal,
        $cliente_poblacion,
        $cliente_provincia,
        $cliente_pais,
        $cliente_calle_observaciones,
        $cliente_telefono,
        $cliente_telefono_observacion,
        $cliente_telefono_envio_sms,
        $cliente_zona_fiscal,
        $cliente_genero,
        $cliente_fnacimiento,
        $cliente_faceptacion_lopd,
        $cliente_no_info_comercial,
        $cliente_no_datos_a_terceros,
        $cliente_idcatalogo,
        $prefijo_telefono,
        $cliente_forzar_creacion
    ) {
        $data = array_filter([
            'idcliente_gestion' => $idcliente_gestion,
            'cliente_nombre' => $cliente_nombre,
            'cliente_apellidos' => $cliente_apellidos,
            'cliente_cif' => $cliente_cif,
            'cliente_email' => $cliente_email,
            'cliente_percontacto' => $cliente_percontacto,
            'cliente_observaciones' => $cliente_observaciones,
            'cliente_idioma' => $cliente_idioma,
            'cliente_codigo_internet' => $cliente_codigo_internet,
            'cliente_calle' => $cliente_calle,
            'cliente_codigopostal' => $cliente_codigopostal,
            'cliente_poblacion' => $cliente_poblacion,
            'cliente_provincia' => $cliente_provincia,
            'cliente_pais' => $cliente_pais,
            'cliente_calle_observaciones' => $cliente_calle_observaciones,
            'cliente_telefono' => $cliente_telefono,
            'cliente_telefono_observacion' => $cliente_telefono_observacion,
            'cliente_telefono_envio_sms' => $cliente_telefono_envio_sms,
            'cliente_zona_fiscal' => $cliente_zona_fiscal,
            'cliente_genero' => $cliente_genero,
            'cliente_fnacimiento' => $cliente_fnacimiento,
            'cliente_faceptacion_lopd' => $cliente_faceptacion_lopd,
            'cliente_no_info_comercial' => $cliente_no_info_comercial,
            'cliente_no_datos_a_terceros' => $cliente_no_datos_a_terceros,
            'cliente_idcatalogo' => $cliente_idcatalogo,
            'prefijo_telefono' => $prefijo_telefono,
            'cliente_forzar_creacion' => $cliente_forzar_creacion,
        ]);

        try {

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

        } catch (RequestException $e) {
            Log::error('Error connecting to the API: ' . $e->getMessage());

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);

        } catch (\Exception $e) {
            Log::error('Unexpected error: ' . $e->getMessage());

            return response()->json([
                'error' => 'Unexpected error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function saveLopd($email, $date, $commercial, $parties)
    {
        if (!$email) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email is required.',
            ], 400);
        }

        // Ensure data is in correct format
        $data = [
            'cliente_email' => $email,
            'cliente_faceptacion_lopd' => date('Y-m-d\TH:i:s', strtotime($date)),
            'cliente_no_info_comercial' => (string) $commercial,
            'cliente_no_datos_a_terceros' => (string) $parties,
        ];

        try {
            // Use the custom PUT method
            $response = $this->put('/api-gestion/cliente/', $data);

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
            Log::error('Unexpected error: ' . $e->getMessage());

            return response()->json([
                'error' => 'Unexpected error: ' . $e->getMessage(),
            ], 500);
        }
    }




    public function recuperarcatalogosclienteerp($idcliente_gestion)
    {
        $endpoint = $this->urlErp."clientecatalogo/?idcliente_gestion=" . $idcliente_gestion;
        return $this->get($endpoint);
    }

    public function suscribircatalogosporeamilerp($cliente_email, $cliente_idcatalogo)
    {
        $endpoint = $this->urlErp."clientecatalogo/";
        $data = $this->urlErp."cliente_email=" . $cliente_email . "&cliente_idcatalogo=" . $cliente_idcatalogo;
        return $this->post($endpoint, ['data' => $data]);
    }

    public function delsuscribircatalogosporeamilerp($cliente_email, $cliente_idcatalogo)
    {
        $data = $this->urlErp."cliente_email=" . $cliente_email . "&cliente_idcatalogo=" . $cliente_idcatalogo;
        $endpoint = $this->urlErp."clientecatalogo/?" . $data;
        return $this->delete($endpoint);
    }

    public function recuperarstockcentral($idarticulo)
    {
        $endpoint = "stock-central-web/" . $idarticulo . "/";
        $content = $this->get($endpoint);
        if ($content != "") {
            if ($content != "Not Found") {
                $xml = simplexml_load_string($content);
                return (float)$xml->unidades;
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }

    public function recuperaridarticulo($codigo)
    {
        $endpoint = "articulo/" . $codigo . "/";
        $content = $this->get($endpoint);
        if ($content != "") {
            if ($content != "Not Found") {
                $xml = simplexml_load_string($content);
                return $xml->idarticulo;
            } else {
                return "0";
            }
        } else {
            return "0";
        }
    }

    public function consultabono($idbono, $codigo_verificacion, $importe_venta, $origen)
    {
        $endpoint = "bono/" . $idbono . "/?codigo_verificacion=" . $codigo_verificacion . "&importe_venta=" . $importe_venta . "&origen=" . $origen;
        $content = trim($this->get($endpoint));
        $data = [];
        if (substr($content, 0, 5) === '<?xml') {
            $xml = simplexml_load_string($content, "SimpleXMLElement", LIBXML_NOCDATA);
            $json = json_encode($xml);
            $array = json_decode($json, true);
            $data['success'] = true;
            $data['data'] = $array;
        } else {
            $data['success'] = false;
            $data['message'] = $content;
        }
        return $data;
    }

    public function marcarbono($idbono, $operacion, $codigo_verificacion, $importe_venta, $importe_inicial_tarjeta_regalo, $origen)
    {
        $endpoint = "bono/" . $idbono . "/?origen=" . $origen;
        $data = "operacion=" . $operacion . "&codigo_verificacion=" . $codigo_verificacion . "&importe_venta=" . $importe_venta . "&importe_inicial_tarjeta_regalo=" . $importe_inicial_tarjeta_regalo;
        return $this->put($endpoint, ['data' => $data]);
    }

    public function consultavalecompra($idvale)
    {
        $endpoint = "vale/" . $idvale . "/";
        return $this->get($endpoint);
    }

    public function actualizarvalecompra($idvale, $operacion, $motivo)
    {
        $endpoint = "vale/" . $idvale . "/";
        $data = "operacion=" . $operacion . "&motivo=" . $motivo;
        return $this->put($endpoint, ['data' => $data]);
    }

    public function crearvalecompra($importe, $tipo, $idalmacen, $idcliente, $observaciones, $tiene_codigo_comprobacion, $id_vale_original, $id_vale_anterior)
    {
        $endpoint = "vale/";
        $data = "importe=" . $importe . "&tipo=" . $tipo . "&idalmacen=" . $idalmacen . "&idcliente=" . $idcliente . "&observaciones=" . $observaciones . "&tiene_codigo_comprobacion=" . $tiene_codigo_comprobacion . "&id_vale_original=" . $id_vale_original . "&id_vale_anterior=" . $id_vale_anterior;
        return $this->post($endpoint, ['data' => $data]);
    }

    public function tienetarifaplana($idweb)
    {
        // Funcionalidad desactivada.
        return false;
    }

    // MÉTODOS DE APOYO (dependen de la lógica de negocio; deben adaptarse a Laravel)
    private function getCatalogo($idproduct)
    {
        $catdef = "" . Db::getInstance()->getValue("SELECT id_category_default FROM aalv_product ap WHERE id_product = " . $idproduct);
        if ($catdef <= 2) {
            $catdef = "" . Db::getInstance()->getValue("SELECT min(id_category) FROM aalv_category_product WHERE id_product=" . $idproduct . " and id_category not in (0,2)");
        } else {
            $catdef = Category::getCategoryGrandFather($catdef);
        }
        if ($catdef == "") {
            $catdef = 4;
        }
        if ($catdef > 11) {
            $catdef = Category::getCategoryGrandFather($catdef);
        }
        switch ($catdef) {
            case 3:
                $catdefault = 1;
                break;
            case 4:
                $catdefault = 5;
                break;
            case 5:
                $catdefault = 6;
                break;
            case 6:
                $catdefault = 3;
                break;
            case 7:
                $catdefault = 4;
                break;
            case 8:
                $catdefault = 2;
                break;
            case 9:
                $catdefault = 9;
                break;
            case 10:
                $catdefault = 1395;
                break;
            case 11:
                $catdefault = 10;
                break;
            default:
                $catdefault = 5;
                break;
        }
        return $catdefault;
    }

    private function getXmlLinesLottery($orderId)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>';
        $oOrder = new Order($orderId);
        $productList = $oOrder->getOrderDetailList();
        if (!empty($productList)) {
            $xml .= '<lineas>';
            foreach ($productList as $item) {
                $xml .= '<linea><referencia>' . $item["product_reference"] . '</referencia><unidades>' . $item["product_quantity"] . '</unidades><precio>' . $item["unit_price_tax_incl"] . '</precio></linea>';
            }
            $xml .= '</lineas>';
        }
        return $xml;
    }

    private function getxmllineas($order)
    {
        $prioridad = 5;
        $cartrules = Db::getInstance()->ExecuteS("SELECT b.* FROM aalv_order_cart_rule a inner join aalv_cart_rule b on a.id_order=" . $order->id . " and a.id_cart_rule=b.id_cart_rule");
        $product_gift = [];
        foreach ($cartrules as $cartrule) {
            $product_gift[] = "" . $cartrule["gift_product"] . "|" . $cartrule["gift_product_attribute"];
        }
        $addresdelivery = new Address($order->id_address_delivery);
        $xml = '<?xml version="1.0" encoding="UTF-8" ?><lineas>';
        $product_list = $order->getOrderDetailList();
        foreach ($product_list as $product) {
            $dto = 0;
            if ($product["id_warehouse"] != 9) {
                $prioridad = 3;
            }
            $idproduct = $product["product_id"];
            $rowslotes = Db::getInstance()->ExecuteS("SELECT * FROM aalv_wk_bundle_order_detail WHERE id_order=" . $order->id . " and id_ps_product=" . $idproduct . " and id_customization=" . $product["id_customization"]);
            if ($rowslotes) {
                $seclotenum = 0;
                foreach ($rowslotes as $rowlote) {
                    $seclotenum++;
                    $bundlesection = $rowlote["id_wk_bundle_section"];
                    $idprodbundle = $rowlote["id_product"];
                    $idprodattribute = $rowlote["id_product_attribute"];
                    if ($idprodattribute == 0) {
                        $ref = Db::getInstance()->getValue("SELECT reference FROM aalv_product WHERE id_product=" . $idprodbundle);
                    } else {
                        $ref = Db::getInstance()->getValue("SELECT reference FROM aalv_product_attribute WHERE id_product_attribute=" . $idprodattribute);
                    }
                    $uni = $rowlote["product_qty"] * $product["product_quantity"];
                    $seclote = Db::getInstance()->getValue("SELECT idllote FROM aalv_llote_import WHERE bundle_section=" . $bundlesection);
                    if (($addresdelivery->id_country == 242) || ($addresdelivery->id_country == 243)) {
                        $pre = Db::getInstance()->getValue("SELECT precio FROM aalv_tarifalote_import WHERE idllote=" . $seclote . " and idttarifa=1 and estado=1");
                    } else {
                        $pre = Db::getInstance()->getValue("SELECT precio_con_impuestos FROM aalv_tarifalote_import WHERE idllote=" . $seclote . " and idttarifa=1 and estado=1");
                        if ($addresdelivery->id_country == 15) {
                            $pre = round(($pre / 1.21) * 1.23, 6);
                        }
                    }
                    $pre = $pre / $rowlote["product_qty"];
                    $idlote = Db::getInstance()->getValue("SELECT idlote FROM aalv_lote_import WHERE bundle_product in (SELECT id_wk_bundle_product FROM aalv_wk_bundle_section_map WHERE id_wk_bundle_section=" . $bundlesection . ")");
                    $idcatalogo = $this->getCatalogo($idprodbundle);
                    $xml .= '<linea><referencia>' . $ref . '</referencia><unidades>' . $uni . '</unidades><precio>' . $pre . '</precio><dto>0</dto><nota_general></nota_general><idlote>' . $idlote . '</idlote><seclote>' . $seclotenum . '</seclote><idcatalogo>' . $idcatalogo . '</idcatalogo></linea>';
                }
            } else {
                $posibleregalo = "" . $product["product_id"] . "|" . $product["product_attribute_id"];
                $ref = $product["product_reference"];
                $uni = $product["product_quantity"];
                $pre = $product["unit_price_tax_incl"];
                if ($product["product_id"] == 65732) {
                    $ref = 'TARJETA';
                    $prioridad = 1;
                }
                if (in_array($posibleregalo, $product_gift)) {
                    $pre = 0;
                }
                $idcatalogo = $this->getCatalogo($idproduct);
                $xml .= '<linea><referencia>' . $ref . '</referencia><unidades>' . $uni . '</unidades><precio>' . $pre . '</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo>' . $idcatalogo . '</idcatalogo></linea>';
            }
        }
        $addresdelivery = new Address($order->id_address_delivery);
        if (($addresdelivery->id_country == 242) || ($addresdelivery->id_country == 243)) {
            $xml .= '<linea><referencia>ADUANAS</referencia><unidades>1</unidades><precio>0</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo>5</idcatalogo></linea>';
        }
        if (($addresdelivery->id_country != 242) && ($addresdelivery->id_country != 243) && ($addresdelivery->id_country != 244) && ($addresdelivery->id_country != 245) && ($addresdelivery->id_country != 6) && ($addresdelivery->id_country != 15)) {
            $xml .= '<linea><referencia>CHECK-ORDER</referencia><unidades>1</unidades><precio>0</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';
        }
        $descripcioncupon = "" . Db::getInstance()->getValue("SELECT name FROM aalv_order_cart_rule where id_order=" . $order->id);
        if ($descripcioncupon != "") {
            $desccupon = Db::getInstance()->getValue("SELECT value FROM aalv_order_cart_rule where id_order=" . $order->id);
            $desccupon = -$desccupon;
            if ($descripcioncupon == "CHEQUE PADRE 2024") {
                $xml .= '<linea><referencia>CHEQUE-PADRE</referencia><unidades>1</unidades><precio>' . $desccupon . '</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';
            }
            if ($descripcioncupon == "Cheque cumpleaños generado desde la web") {
                $xml .= '<linea><referencia>CUMPLEAÑOS</referencia><unidades>1</unidades><precio>' . $desccupon . '</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';
            }
            if ($descripcioncupon == "CHEQUE MADRE 2024") {
                $xml .= '<linea><referencia>CHEQUE-MADRE</referencia><unidades>1</unidades><precio>' . $desccupon . '</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';
            }
            if ($descripcioncupon == "Bono fidelización") {
                $xml .= '<linea><referencia>FIDELIZACION</referencia><unidades>1</unidades><precio>' . $desccupon . '</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';
            }
            if ($descripcioncupon == "OFERTA 3x2 Bolas Wilson") {
                $xml .= '<linea><referencia>OFERTA</referencia><unidades>1</unidades><precio>' . $desccupon . '</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';
            }
            if ($descripcioncupon == "SAN VALENTIN 2025") {
                $xml .= '<linea><referencia>CHEQUE-VALENTIN</referencia><unidades>1</unidades><precio>' . $desccupon . '</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';
            }
            if ($descripcioncupon == "Reserva fittings") {
                $xml .= '<linea><referencia>GFITTING-2</referencia><unidades>1</unidades><precio>' . $desccupon . '</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';
            }
            if ($descripcioncupon == "PLAN RENOVE ARMAS DE BALINES") {
                $xml .= '<linea><referencia>PROMO-CARABINA</referencia><unidades>1</unidades><precio>' . $desccupon . '</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';
            }
            if ($descripcioncupon == "Plan Renove Armas de Balines") {
                $xml .= '<linea><referencia>PROMO-CARABINA</referencia><unidades>1</unidades><precio>' . $desccupon . '</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';
            }
            if ($descripcioncupon == "PLANO RENOVE ESTREIE ARMA DE CHUMBOS") {
                $xml .= '<linea><referencia>PROMO-CARABINA</referencia><unidades>1</unidades><precio>' . $desccupon . '</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';
            }
            if ($descripcioncupon == "Plan Renove Drivers") {
                $xml .= '<linea><referencia>RENOVEDRIVER</referencia><unidades>1</unidades><precio>' . $desccupon . '</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';
            }
            if ($descripcioncupon == "2x1 HILOS IMPERATOR") {
                $xml .= '<linea><referencia>OFERTA</referencia><unidades>1</unidades><precio>' . $desccupon . '</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';
            }
            if ($descripcioncupon == "Tarjeta regalo") {
                $xml .= '<linea><referencia>T-GENER</referencia><unidades>1</unidades><precio>' . $desccupon . '</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';
            }
            if ($descripcioncupon == "Bono promoción por catálogo") {
                $xml .= '<linea><referencia>PROMOCION</referencia><unidades>1</unidades><precio>' . $desccupon . '</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';
            }
        }
        if ($order->total_shipping <= 0) {
            $xml .= '<linea><referencia>PORTES-GRATIS</referencia><unidades>1</unidades><precio>0</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';
        }
        $xml .= '</lineas>';
        return array("xml" => $xml, "prioridad" => $prioridad);
    }

    private function getProvinciaPorCP($cp)
    {
        $provinces = [
            '01' => "Alava",
            '02' => "Albacete",
            '03' => "Alicante",
            '04' => "Almería",
            '05' => "Ávila",
            '06' => "Badajoz",
            '07' => "Baleares",
            '08' => "Barcelona",
            '09' => "Burgos",
            '10' => "Cáceres",
            '11' => "Cádiz",
            '12' => "Castellón",
            '13' => "Ciudad Real",
            '14' => "Córdoba",
            '15' => "A Corunya",
            '16' => "Cuenca",
            '17' => "Girona",
            '18' => "Granada",
            '19' => "Guadalajara",
            '20' => "Guipuzkoa",
            '21' => "Huelva",
            '22' => "Huesca",
            '23' => "Jaen",
            '24' => "León",
            '25' => "Lleida",
            '26' => "La Rioja",
            '27' => "Lugo",
            '28' => "Madrid",
            '29' => "Málaga",
            '30' => "Murcia",
            '31' => "Navarra",
            '32' => "Orense",
            '33' => "Asturias",
            '34' => "Palencia",
            '35' => "Las Palmas",
            '36' => "Pontevedra",
            '37' => "Salamanca",
            '38' => "Sta. Cruz Tenerife",
            '39' => "Cantabria",
            '40' => "Segovia",
            '41' => "Sevilla",
            '42' => "Soria",
            '43' => "Tarragona",
            '44' => "Teruel",
            '45' => "Toledo",
            '46' => "Valencia",
            '47' => "Valladolid",
            '48' => "Vizcaya",
            '49' => "Zamora",
            '50' => "Zaragoza",
            '51' => "Ceuta",
            '52' => "Melilla"
        ];
        $key = substr($cp, 0, 2);
        return isset($provinces[$key]) ? $provinces[$key] : $provinces['01'];
    }

    public function toGestion($cadena)
    {
        $key = 'aK-#s$q_Fs1?b*EE';
        $key .= substr($key, 0, 8);
        $iv = 'w=c@@ZqP';
        return base64_encode(mcrypt_encrypt(MCRYPT_TRIPLEDES, $key, $cadena, MCRYPT_MODE_CFB, $iv));
    }

    public function construirdatospedido($idpedido, $idclientegestion)
    {
        // NOTA: Deberás adaptar la lógica para obtener Order, Customer, Address, etc. (por ejemplo, usando Eloquent)
        $order = $this->getOrderFromId($idpedido);
        $customer = $this->getCustomerFromOrder($order);
        $addresinvoice = $this->getAddressInvoiceFromOrder($order);
        $addresdelivery = $this->getAddressDeliveryFromOrder($order);

        $data = [];
        $sql = "SELECT force_type FROM aalv_orders_envio_gestion WHERE id_order=" . $idpedido . " and posible_enviar=1 and fecha_envio is null order by id desc";
        $force_type = "" . Db::getInstance()->getValue($sql);
        if ($force_type == "3") {
            $data[$this->urlErp."cliente_forzar_creacion"] = 1;
        } else {
            if ("" . $idclientegestion != "") {
                $data["idcliente_gestion"] = $idclientegestion;
            }
        }
        $data["fecha_pedido"] = str_replace(" ", "T", $order->date_add);
        $paiseszona1 = "242,6,244,40,45,2,47,3,52,231,233,76,106,74,20,37,191,86,7,8,93,97,9,101,142,26,108,10,115,124,129,130,12,132,138,146,147,149,23,13,14,16,36,175,184,188,18,19,209,214,1";
        $paiseszona1arr = explode(",", $paiseszona1);
        $zona_fiscal = 1;
        if (in_array($addresdelivery->id_country, $paiseszona1arr)) {
            $zona_fiscal = 1;
        } else {
            if (($addresdelivery->id_country == 15) || ($addresdelivery->id_country == 245)) {
                $zona_fiscal = 2;
            } else {
                $zona_fiscal = 3;
            }
        }
        $data["zona_fiscal"] = $zona_fiscal;
        $data["telefono_contacto"] = substr($addresdelivery->phone, 0, 20);
        $data["identificador_origen"] = $idpedido;
        if ($order->gift) {
            $data["envoltorio_regalo"] = 1;
            $data["texto_regalo"] = substr($order->gift_message, 0, 255);
        } else {
            $data["envoltorio_regalo"] = 0;
        }
        $data["solicita_club_mas"] = 0;
        $solicita_factura = "" . Db::getInstance()->getValue("select need_invoice from aalv_cart where id_cart in (SELECT id_cart FROM aalv_orders WHERE id_order=" . $idpedido . ")");
        if ($solicita_factura == "") {
            $solicita_factura = "0";
        }
        $data["solicita_factura"] = (int)$solicita_factura;
        $data[$this->urlErp."cliente_nombre"] = strtoupper(substr($addresinvoice->firstname, 0, 50));
        $data[$this->urlErp."cliente_apellidos"] = strtoupper(substr($addresinvoice->lastname, 0, 60));
        $data[$this->urlErp."cliente_cif"] = strtoupper(substr($addresinvoice->vat_number, 0, 20));
        $data[$this->urlErp."cliente_email"] = $customer->email;
        switch ($addresdelivery->id_country) {
            case 15:
            case 245:
                $cliente_idioma = 7;
                break;
            case 1:
                $cliente_idioma = 1;
                break;
            case 8:
                $cliente_idioma = 5;
                break;
            case 17:
            case 21:
                $cliente_idioma = 6;
                break;
            default:
                $cliente_idioma = 2;
                break;
        }
        $data[$this->urlErp."cliente_idioma"] = $cliente_idioma;
        if (!$customer->is_guest) {
            $data[$this->urlErp."cliente_codigo_internet"] = $customer->id;
        }
        $addressCompl = (!empty($addresinvoice->address2)) ? (" (" . $addresinvoice->address2 . ")") : '';
        $data[$this->urlErp."cliente_calle"] = strtoupper(substr($addresinvoice->address1 . $addressCompl, 0, 255));
        $data[$this->urlErp."cliente_codigopostal"] = strtoupper(substr($addresinvoice->postcode, 0, 20));
        $data[$this->urlErp."cliente_poblacion"] = strtoupper(substr($addresinvoice->city, 0, 50));
        if ($addresinvoice->id_state != 0) {
            $prov = new State($addresinvoice->id_state);
            $data[$this->urlErp."cliente_provincia"] = strtoupper(substr($prov->name, 0, 50));
        }
        $pais = new Country($addresinvoice->id_country);
        $data[$this->urlErp."cliente_pais"] = strtoupper(substr($pais->name[1], 0, 50));
        if (($addresinvoice->id_country == 6) || ($addresinvoice->id_country == 242) || ($addresinvoice->id_country == 243) || ($addresinvoice->id_country == 244)) {
            $data[$this->urlErp."cliente_pais"] = "ESPAÑA";
        }
        if (($addresinvoice->id_country == 15) || ($addresinvoice->id_country == 245)) {
            $data[$this->urlErp."cliente_pais"] = "PORTUGAL";
        }
        $data[$this->urlErp."cliente_telefono"] = substr($addresinvoice->phone, 0, 20);
        $data[$this->urlErp."cliente_telefono_envio_sms"] = $this->isMobilePhone($addresinvoice->phone, $addresinvoice->id_country);
        $cliente_zona_fiscal = 1;
        if (in_array($addresinvoice->id_country, $paiseszona1arr)) {
            $cliente_zona_fiscal = 1;
        } else {
            if (($addresinvoice->id_country == 15) || ($addresinvoice->id_country == 245)) {
                $cliente_zona_fiscal = 2;
            } else {
                $cliente_zona_fiscal = 3;
            }
        }
        $data[$this->urlErp."cliente_zona_fiscal"] = $cliente_zona_fiscal;
        if ($customer->birthday != "0000-00-00") {
            $data[$this->urlErp."cliente_fnacimiento"] = $customer->birthday . "T00:00:00";
        }
        $data[$this->urlErp."cliente_faceptacion_lopd"] = substr($customer->date_add, 0, 10) . "T00:00:00";
        $data[$this->urlErp."cliente_no_info_comercial"] = 0;
        $data[$this->urlErp."cliente_no_datos_a_terceros"] = 0;
        $data["prefijo_telefono"] = "00" . $pais->call_prefix;
        $tienefreeshipping = "" . Db::getInstance()->getValue("SELECT id_order_cart_rule FROM aalv_order_cart_rule WHERE id_order=" . $idpedido . " and deleted=0 and free_shipping=1");
        $envio_coste = $order->total_shipping_tax_incl;
        if ($tienefreeshipping != "") {
            $envio_coste = 0;
        }
        $textocorreos = '';
        $sql = 'SELECT `value`
                FROM `' . _DB_PREFIX_ . 'configuration`
                WHERE `id_shop_group`=' . $order->id_shop_group . ' AND `id_shop`=' . $order->id_shop . ' AND name = \'CEX_REMITENTE_DEFECTO\'';
        $id_sender = Db::getInstance()->getValue($sql);
        if ($id_sender && is_numeric($id_sender)) {
            $sql = 'SELECT `id_customer_code`
                    FROM `' . _DB_PREFIX_ . 'cex_savedsenders`
                    WHERE `id_sender`=' . (int)$id_sender;
            $id_customer_code = Db::getInstance()->getValue($sql);
            if ($id_customer_code && is_numeric($id_customer_code)) {
                $sql = 'SELECT `id_bc`
                        FROM `' . _DB_PREFIX_ . 'cex_savedmodeships`
                        WHERE `id_carrier` LIKE \'%;' . pSQL($order->id_carrier) . ';%\' AND `id_customer_code`=' . (int)$id_customer_code;
                $result = Db::getInstance()->getValue($sql);
                if ($result) {
                    $sql = 'SELECT `texto_oficina`
                            FROM `' . _DB_PREFIX_ . 'orders` o
                            INNER JOIN `' . _DB_PREFIX_ . 'cex_officedeliverycorreo` odc ON odc.`id_cart`=o.`id_cart`
                            WHERE `id_order`=' . $order->id;
                    $textocorreos = Db::getInstance()->getValue($sql);
                }
            }
        }
        if ($textocorreos == "") {
            $addressCompl = (!empty($addresdelivery->address2)) ? (" (" . $addresdelivery->address2 . ")") : '';
            $data["envio_calle"] = strtoupper(substr($addresdelivery->address1 . $addressCompl, 0, 255));
            $data["envio_localidad"] = strtoupper(substr($addresdelivery->city, 0, 50));
            $data["envio_cp"] = strtoupper(substr($addresdelivery->postcode, 0, 20));
            if ($addresdelivery->id_state != 0) {
                $provd = new State($addresdelivery->id_state);
                $data["envio_provincia"] = strtoupper(substr($provd->name, 0, 50));
            }
            $paisd = new Country($addresdelivery->id_country);
            $data["envio_pais"] = strtoupper(substr($paisd->name[1], 0, 50));
            if (($addresdelivery->id_country == 6) || ($addresdelivery->id_country == 242) || ($addresdelivery->id_country == 243) || ($addresdelivery->id_country == 244)) {
                $data["envio_pais"] = "ESPAÑA";
            }
            if (($addresdelivery->id_country == 15) || ($addresdelivery->id_country == 245)) {
                $data["envio_pais"] = "PORTUGAL";
            }
            $data["envio_coste"] = $envio_coste;
            $data["envio_destinatario"] = strtoupper($addresdelivery->firstname . " " . $addresdelivery->lastname);
            $data["envio_telefono"] = substr($addresdelivery->phone, 0, 20);
            $esrecogidaentienda = "" . Db::getInstance()->getValue("SELECT id_store FROM aalv_kb_pickup_store_address_mapping WHERE id_address=" . $order->id_address_delivery);
            if ($esrecogidaentienda == "") {
                $data["envio_recogida_tienda"] = 0;
            } else {
                if (($esrecogidaentienda == "8") || ($esrecogidaentienda == "6") || ($esrecogidaentienda == "7")) {
                    $data["envio_recogida_tienda"] = 1;
                    $almacenrecogida = "";
                    if ($esrecogidaentienda == "8") {
                        $almacenrecogida = "1";
                        $data["envio_idtransportista"] = Configuration::get('ALSERNET_TRANSPORTISTA_TIENDA_CORUNA');
                    }
                    if ($esrecogidaentienda == "6") {
                        $almacenrecogida = "3";
                        $data["envio_idtransportista"] = Configuration::get('ALSERNET_TRANSPORTISTA_TIENDA_CAPITAN_HAYA');
                    }
                    if ($esrecogidaentienda == "7") {
                        $almacenrecogida = "4";
                        $data["envio_idtransportista"] = Configuration::get('ALSERNET_TRANSPORTISTA_TIENDA_DIEGO_DE_LEON');
                    }
                    $data["envio_idalmacen_recogida"] = $almacenrecogida;
                    $data["envio_destinatario"] = strtoupper($customer->firstname . " " . $customer->lastname);
                    $data["envio_telefono"] = substr($addresinvoice->phone, 0, 20);
                    $data["telefono_contacto"] = substr($addresinvoice->phone, 0, 20);
                } else {
                    $data["envio_recogida_tienda"] = 0;
                }
            }
        } else {
            $datosoficina = explode("#!#", $textocorreos);
            $data["envio_calle"] = strtoupper(substr($datosoficina[1] . " (Oficina de correos:" . $datosoficina[2] . ")", 0, 255));
            $data["envio_localidad"] = strtoupper(substr($datosoficina[4], 0, 50));
            $data["envio_cp"] = strtoupper(substr($datosoficina[3], 0, 20));
            $data["envio_provincia"] = strtoupper($this->getProvinciaPorCP($datosoficina[3]));
            $data["envio_pais"] = "ESPAÑA";
            $data["envio_coste"] = $envio_coste;
            $data["envio_destinatario"] = strtoupper($addresdelivery->firstname . " " . $addresdelivery->lastname);
            $data["envio_telefono"] = substr($addresdelivery->phone, 0, 20);
            $data["envio_recogida_tienda"] = 0;
        }
        $data["envio_idtransportista"] = $this->transportistaGestion($addresdelivery, $order, $textocorreos);
        if (Cart::haveMultipleProductTypes($order->id_cart)) {
            $data["observaciones"] = strtoupper(AddressFormat::generateAddress($addresdelivery));
            $addressCompl = (!empty($addresinvoice->address2)) ? (" (" . $addresinvoice->address2 . ")") : '';
            $data["envio_calle"] = strtoupper(substr($addresinvoice->address1 . $addressCompl, 0, 255));
            $data["envio_localidad"] = strtoupper(substr($addresinvoice->city, 0, 50));
            $data["envio_cp"] = strtoupper(substr($addresinvoice->postcode, 0, 20));
            if ($addresinvoice->id_state != 0) {
                $prov = new State($addresinvoice->id_state);
                $data["envio_provincia"] = strtoupper(substr($prov->name, 0, 50));
            }
            $paisd = new Country($addresinvoice->id_country);
            $data["envio_pais"] = strtoupper(substr($paisd->name[1], 0, 50));
            if (($addresinvoice->id_country == 6) || ($addresinvoice->id_country == 242) || ($addresinvoice->id_country == 243) || ($addresinvoice->id_country == 244)) {
                $data["envio_pais"] = "ESPAÑA";
            }
            if (($addresinvoice->id_country == 15) || ($addresinvoice->id_country == 245)) {
                $data["envio_pais"] = "PORTUGAL";
            }
            $data["envio_coste"] = $envio_coste;
            $data["envio_destinatario"] = strtoupper($addresinvoice->firstname . " " . $addresinvoice->lastname);
            $data["envio_telefono"] = substr($addresinvoice->phone, 0, 20);
        }
        $data["envio_email"] = $customer->email;
        $carrier = Db::getInstance()->getValue("select concat(rela.selected_relay_country_iso,'-',rela.selected_relay_num) AS selected_relay_num from aalv_orders ord inner join aalv_carrier carr on carr.id_carrier = ord.id_carrier inner join aalv_mondialrelay_selected_relay rela on rela.id_order = ord.id_order where ord.id_order = " . $order->id);
        if ($carrier) {
            $data["envio_codigo_destino"] = $carrier;
            $data["envio_idtransportista"] = 100000283;
            $data["envio_provincia"] = '';
        }
        $tarjeta = Db::getInstance()->getValue("select product_id from aalv_order_detail aod where id_order = " . $order->id . " and product_name LIKE '% (TARJETA:%'");
        if ($tarjeta) {
            $data["envio_idtransportista"] = 100000304;
        }
        $pago_forma_pago = $this->forma_pago($order->module, $idpedido);
        if ($pago_forma_pago == self::PAYMENT_CREDITCARD ||
            $pago_forma_pago == self::PAYMENT_BIZUM ||
            $pago_forma_pago == self::PAYMENT_REDSYS ||
            $pago_forma_pago == self::PAYMENT_GOOGLE ||
            $pago_forma_pago == self::PAYMENT_APPLE) {
            $transid = "" . Db::getInstance()->getValue("SELECT transaction_id FROM aalv_order_payment WHERE order_reference='" . $order->reference . "'");
            $fechatrans = "" . Db::getInstance()->getValue("SELECT date_add FROM aalv_order_payment WHERE order_reference='" . $order->reference . "'");
            $data["pago_codigo_autorizacion"] = strtoupper($transid);
            if ($fechatrans != "") {
                $data["pago_fecha_autorizacion"] = str_replace(" ", "T", $fechatrans);
            }
        }
        if ($pago_forma_pago == self::PAYMENT_PAYPAL) {
            $data["pago_fecha_pago"] = str_replace(" ", "T", $order->date_add);
            $data["pago_fecha_autorizacion"] = str_replace(" ", "T", $order->date_add);
            $transid = "" . Db::getInstance()->getValue("SELECT id_transaction FROM aalv_paypal_order WHERE id_order=" . $order->id);
            $data["pago_codigo_autorizacion"] = strtoupper($transid);
        }
        if ($pago_forma_pago == self::PAYMENT_SEQURA) {
            $data["pago_fecha_pago"] = str_replace(" ", "T", $order->date_add);
            $data["pago_fecha_autorizacion"] = str_replace(" ", "T", $order->date_add);
            $transaction_id = "" . Db::getInstance()->getValue("SELECT transaction_id FROM aalv_order_payment aop WHERE order_reference = '" . $order->reference . "'");
            $data["pago_codigo_autorizacion"] = strtoupper($transaction_id);
        }
        if ($customer->email == 'reservafitting@alsernet.es') {
            $pago_forma_pago = 24;
            $data["pago_fecha_pago"] = str_replace(" ", "T", $order->date_add);
        }
        if ($pago_forma_pago == self::PAYMENT_BAN_LENDISMART) {
            $data["pago_codigo_autorizacion"] = Db::getInstance()->getValue("SELECT transaction_id FROM aalv_order_payment WHERE order_reference='" . $order->reference . "'");
        }
        $data["pago_forma_pago"] = $pago_forma_pago;
        $data["pago_importe"] = $order->total_paid_tax_incl;
        $isLotteryOrderId = Db::getInstance()->getValue("SELECT o.id_order
            FROM aalv_configuration cf
            INNER JOIN aalv_feature_product f ON (cf.value = f.id_feature_value)
            INNER JOIN aalv_order_detail od ON (f.id_product = od.product_id)
            INNER JOIN aalv_orders o ON (od.id_order = o.id_order)
            WHERE cf.name = '" . _PSALV_CONFIG_PRODUCTTYPE_LOTTERY_ . "' AND o.id_order=" . $order->id . "
            GROUP BY o.id_order");
        if (!empty($isLotteryOrderId)) {
            $data["xml_lineas_loteria"] = $this->getXmlLinesLottery($order->id);
            $data["prioridad"] = 3;
        } else {
            $xml_lineas = $this->getxmllineas($order);
            $data["prioridad"] = $xml_lineas["prioridad"];
            $data["xml_lineas_pedido"] = $xml_lineas["xml"];
        }
        $fields_string = http_build_query($data);
        return $fields_string;
    }

    public function isMobilePhoneWrapper($num, $idCountry)
    {
        return $this->isMobilePhone($num, $idCountry);
    }

    public function mandarpedido($idpedido, $idclientegestion)
    {
        $data = $this->construirdatospedido($idpedido, $idclientegestion);
        $endpoint = "pedido-cliente/";
        return $this->post($endpoint, ['data' => $data]);
    }

    public function forma_pago($module, $idpedido)
    {
        switch ($module) {
            case 'ps_cashondelivery':
                return config('erp.PAYMENT_CASHONDELIVERY');
            case 'ps_wirepayment':
                return config('erp.PAYMENT_WIRE');
            case 'ceca':
                $ceca_tpv = Db::getInstance()->getValue("SELECT id_ceca_tpv from aalv_ceca_transaction where id_order = " . $idpedido);
                if ($ceca_tpv == config('erp.PAYMENT_BIZUM_TPV')) {
                    return config('erp.PAYMENT_BIZUM');
                } else {
                    return config('erp.PAYMENT_CREDITCARD');
                }
            case 'redsys':
                $redsys_tpv = Db::getInstance()->getValue("SELECT id_tpv from aalv_redsys_transaction where id_order = " . $idpedido);
                if ($redsys_tpv == config('erp.PAYMENT_GOOGLE_TPV')) {
                    return config('erp.PAYMENT_GOOGLE');
                }
                if ($redsys_tpv == config('erp.PAYMENT_APPLE_TPV')) {
                    return config('erp.PAYMENT_APPLE');
                } else {
                    return config('erp.PAYMENT_REDSYS');
                }
            case 'paypal':
                return config('erp.PAYMENT_PAYPAL');
            case 'caixabankconsumerfinance':
                return config('erp.PAYMENT_FINANCE');
            case 'sequra':
                return config('erp.PAYMENT_SEQURA');
            case 'alsernetfinance':
                return config('erp.PAYMENT_ALSERNETFINANCE');
            case 'inespay':
                return config('erp.PAYMENT_TRANSFERENCIA_ONLINE');
            case 'banlendismart':
                return config('erp.PAYMENT_BAN_LENDISMART');
            default:
                return -1;
        }
    }

    public function transportistaGestion($addresdelivery, $order, $textocorreos)
    {
        $array_europa_seur = explode(',', Configuration::get('ALSERNET_TRANSPORTISTA_EUROPA_SI_SEUR'));
        $array_europa_no_seur = explode(',', Configuration::get('ALSERNET_TRANSPORTISTA_EUROPA_NO_SEUR'));
        $idtransportista = '';
        if (in_array($addresdelivery->id_country, [244, 243, 242])) {
            $idtransportista = Configuration::get('ALSERNET_TRANSPORTISTA_ESPANIA_NO_PENINSULAR');
        }
        if ($order->module != 'ps_cashondelivery') {
            if (in_array($addresdelivery->id_country, [6, 15, 244, 243, 242, 245])) {
                if (in_array($addresdelivery->id_country, [6]) && preg_match('/^(15|36|32|27)/', $addresdelivery->postcode)) {
                    $idtransportista = Configuration::get('ALSERNET_TRANSPORTISTA_ESPANIA_GALICIA');
                } elseif (in_array($addresdelivery->id_country, [6, 15])) {
                    $idtransportista = Configuration::get('ALSERNET_TRANSPORTISTA_ESPANIA_Y_PORTUGAL');
                } else {
                    $idtransportista = Configuration::get('ALSERNET_TRANSPORTISTA_RESTO_DE_ESPANIA');
                }
            } elseif (in_array($addresdelivery->id_country, $array_europa_seur)) {
                $idtransportista = Configuration::get('ALSERNET_TRANSPORTISTA_EUROPA_SI_SEUR_ID');
            } elseif (in_array($addresdelivery->id_country, $array_europa_no_seur)) {
                $idtransportista = Configuration::get('ALSERNET_TRANSPORTISTA_EUROPA_NO_SEUR_ID');
            } else {
                $idtransportista = Configuration::get('ALSERNET_TRANSPORTISTA_RESTO_DEL_MUNDO');
            }
            $inpost = Db::getInstance()->executeS("SELECT * FROM aalv_mondialrelay_selected_relay WHERE id_order = " . $order->id);
            if (count($inpost) > 0) {
                $idtransportista = Configuration::get('ALSERNET_TRANSPORTISTA_ES_INPOST');
            }
            foreach ($order->lineas_pedido_cliente as $product) {
                if ($product['armas']) {
                    $idtransportista = Configuration::get('ALSERNET_TRANSPORTISTA_ES_ARMA');
                } elseif ($product['cartucho']) {
                    $idtransportista = Configuration::get('ALSERNET_TRANSPORTISTA_ES_CARTUCHO');
                } elseif ($product['card']) {
                    $idtransportista = Configuration::get('ALSERNET_TRANSPORTISTA_ES_TARJETAS_REGALO');
                }
            }
            if ($textocorreos != '') {
                $idtransportista = Configuration::get('ALSERNET_TRANSPORTISTA_CORREOSEXPRESS');
            }
        } else {
            if (in_array($addresdelivery->id_country, [6, 15])) {
                $idtransportista = Configuration::get('ALSERNET_TRANSPORTISTA_ESPANIA_Y_PORTUGAL_CONTRA_REEMBOLSO');
            }
        }
        return $idtransportista;
    }

    // Métodos ficticios de obtención (a implementar según la lógica de Laravel)
    protected function getOrderFromId($idpedido)
    {
        // Implementa la lógica para obtener el pedido (por ejemplo, usando Eloquent)
        return null;
    }

    protected function getCustomerFromOrder($order)
    {
        return null;
    }

    protected function getAddressInvoiceFromOrder($order)
    {
        return null;
    }

    protected function getAddressDeliveryFromOrder($order)
    {
        return null;
    }
}

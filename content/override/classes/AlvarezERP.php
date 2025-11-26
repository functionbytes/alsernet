<?php

class AlvarezERP
{

    const URL_ERP = 'http://127.0.0.1:58002/api-gestion/';

    const BONO_ORIGEN_WEB = 'web';
    const BONO_ORIGEN_GESTION = 'gestion';

    const MARCAR_BONO_ANULAR = 0;
    const MARCAR_BONO_RECARGAR = 1;
    const MARCAR_BONO_CONSUMIR = 2;


    const PAYMENT_CASHONDELIVERY = 1;
    const PAYMENT_WIRE = 3;
    const PAYMENT_CREDITCARD = 7;
    const PAYMENT_REDSYS = 22;
    const PAYMENT_BIZUM = 8;
    const PAYMENT_GOOGLE = 26;
    const PAYMENT_APPLE = 27;
    const PAYMENT_PAYPAL = 10;
    const PAYMENT_FINANCE = 11;
    const PAYMENT_SEQURA = 100000101;
    const PAYMENT_ALSERNETFINANCE = 5;
    const PAYMENT_TRANSFERENCIA_ONLINE = '25';
    const PAYMENT_BAN_LENDISMART = 28;

    const PAYMENT_BIZUM_TPV = 2;
    const PAYMENT_GOOGLE_TPV = 3;
    const PAYMENT_APPLE_TPV = 2;

    /*HIPAY Formas de Pago*/
    const PAYMENT_MULTIBANCO = 29;
    const PAYMENT_MBWAY = 30;
    const PAYMENT_VISA = 31;
    const PAYMENT_MASTERCARD = 32;
    const PAYMENT_CARTE_BANCAIRE = 33;
    const PAYMENT_MAESTRO = 34;
    const PAYMENT_BANCONTACT = 35;
    const PAYMENT_AMERICAN_EXPRESS = 36;
    const PAYMENT_MYBANK = 37;
    const PAYMENT_KLARNA = 38;

    const PAYMENT_REVER = 39;


    private static function peticionget($url, $CallerFunction = "")
    {
        //DAG20231123-s�lo llamadas �tiles
        if (substr($_SERVER['REQUEST_URI'], 0, 7) === "/upload" || strpos($_SERVER['REQUEST_URI'], "bancron") !== false || strpos($_SERVER['REQUEST_URI'], "ajax") !== false) {
            return '';
        }
        // dump('peticionget');
        $tiempo_inicial = microtime(true);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);

        $content = curl_exec($ch);
        curl_close($ch);
        $tiempo_final = microtime(true);
        $tiempo = $tiempo_final - $tiempo_inicial;
        if (isset($_SERVER["REMOTE_ADDR"])) {
            //if ($_SERVER["REMOTE_ADDR"] == '77.225.101.140') {
            // AddisLogger::log(__FILE__, __FUNCTION__, null, 'IP:'.$_SERVER["REMOTE_ADDR"].', Petición GET : '.$url.' ; Tiempo: '.$tiempo.' ; URL: '.$_SERVER['REQUEST_URI']);
            AddisLogger::log(__FILE__, __FUNCTION__, null, 'Petición GET (' . $CallerFunction . ') : ' . $url . ' ; Tiempo: ' . $tiempo . ' ; URL: ' . $_SERVER['REQUEST_URI'] . ' ; CONTENT: #' . $content . '#');
            //}
        }
        return $content;
    }


    private static function peticionpost($url, $data, $CallerFunction = "")
    {
        // dump('peticionpost');
        $tiempo_inicial = microtime(true);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);

        $content = curl_exec($ch);
        curl_close($ch);
        $tiempo_final = microtime(true);
        $tiempo = $tiempo_final - $tiempo_inicial;
        if (isset($_SERVER["REMOTE_ADDR"])) {
            //if ($_SERVER["REMOTE_ADDR"] == '77.225.101.140') {
            AddisLogger::log(__FILE__, __FUNCTION__, null, 'Petición POST (' . $CallerFunction . ') : ' . $url . '; data: ' . $data . ' ; Tiempo: ' . $tiempo . ' ; URL: ' . $_SERVER['REQUEST_URI']);
            //}
        }
        return $content;
    }

    private static function peticiondelete($url, $data, $CallerFunction = "")
    {
        // dump('peticiondelete');
        $tiempo_inicial = microtime(true);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Length: ' . strlen($data)));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);

        $content = curl_exec($ch);
        curl_close($ch);
        $tiempo_final = microtime(true);
        $tiempo = $tiempo_final - $tiempo_inicial;
        if (isset($_SERVER["REMOTE_ADDR"])) {
            //if ($_SERVER["REMOTE_ADDR"] == '77.225.101.140') {
            AddisLogger::log(__FILE__, __FUNCTION__, null, 'Petición DELETE (' . $CallerFunction . ') : ' . $url . '; data: ' . $data . ' ; Tiempo: ' . $tiempo . ' ; URL: ' . $_SERVER['REQUEST_URI']);
            //}
        }
        return $content;
    }


    private static function peticionput($url, $data)
    {
        // dump('peticionput');
        $tiempo_inicial = microtime(true);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Length: ' . strlen($data)));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);

        $content = curl_exec($ch);
        //$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $tiempo_final = microtime(true);
        $tiempo = $tiempo_final - $tiempo_inicial;
        if (isset($_SERVER["REMOTE_ADDR"])) {
            //if ($_SERVER["REMOTE_ADDR"] == '77.225.101.140') {
            AddisLogger::log(__FILE__, __FUNCTION__, null, 'Petición PUT : ' . $url . '; data: ' . $data . ' ; Tiempo: ' . $tiempo . ' ; URL: ' . $_SERVER['REQUEST_URI'] . '; CONTENT: ' . $content);
            //}
        }
        return $content;
    }


    public static function recuperarclienteerp($idweb)
    {
        // dump('recuperarclienteerp');
        $url = self::URL_ERP . "cliente/?idclienteweb=" . $idweb;

        $content = self::peticionget($url, 'recuperarclienteerp');
        return $content;
        if ($content != "") {
            if ($content != "Not Found") {
                $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
                $json = json_encode($xml);
                $array = json_decode($json, TRUE);
                return $array;
            } else {
                return '';
            }
        } else {
            return '';
        }
        //return '';
    }

    public static function recuperaridclienteerp($idweb)
    {
        // dump('recuperaridclienteerp');
        $url = self::URL_ERP . "cliente/?idclienteweb=" . $idweb;

        $content = self::peticionget($url, 'recuperaridclienteerp');
        if ($content != "") {
            if ($content != "Not Found") {

                //$response = new SimpleXMLElement($content);
                $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
                $json = json_encode($xml);
                $array = json_decode($json, TRUE);

                //return $response->idcliente;
                if (isset($array['idcliente']) && $array['idcliente']) {
                    return $array['idcliente'];
                } else {
                    return '';
                }
            } else {
                return '';
            }
        } else {
            return '';
        }
        //return '';
    }

    public static function recuperarpedidoscliente($idweb)
    {
        // dump('recuperarpedidoscliente');
        $idcliente = self::recuperaridclienteerp($idweb);

        if ($idcliente != '') {
            $url = self::URL_ERP . 'pedido-cliente/?idcliente=' . $idcliente;
            $content = self::peticionget($url, 'recuperarpedidoscliente');

            $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
            $json = json_encode($xml);
            $array = json_decode($json, TRUE);

            // formatear pedido ERP
            return self::formatOrderArrayErp($array);
        } else {
            return false;
        }
        //return false;
    }

    public static function recuperarpedido($npedidocli, $serie)
    {
        // dump('recuperarpedido');
        $url = self::URL_ERP . "pedido-cliente/?serie=" . $serie . "&npedidocli=" . $npedidocli;
        $content = self::peticionget($url, 'recuperarpedido');

        $xml = simplexml_load_string($content, "SimpleXMLElement", LIBXML_NOCDATA);
        $json = json_encode($xml);
        $array = json_decode($json, TRUE);
        //return $array;
        // formatear pedido ERP

        $array['resource']['idinternet'] = Db::getInstance()->getValue("SELECT id_internet FROM seguimiento_pedidos WHERE serie = '" . $serie . "' AND id_gestion = '" . $npedidocli . "';");

        return self::formatOrderArrayErp($array);
        //return false;

    }

    public static function recuperarpedidoporid($identificadororigen)
    {
        // dump('recuperarpedidoporid');
        $url = self::URL_ERP . "pedido-cliente/?identificadororigen=" . $identificadororigen;
        $content = self::peticionget($url, 'recuperarpedidoporid');

        $xml = simplexml_load_string($content, "SimpleXMLElement", LIBXML_NOCDATA);
        $json = json_encode($xml);
        $array = json_decode($json, TRUE);

        //return $array;
        // formatear pedido ERP
        return self::formatOrderArrayErp($array);
        //return false;
    }

    public static function recuperarclienteerpAlsernet($idweb)
    {
        // dump('recuperarclienteerpAlsernet');
        $url = self::URL_ERP . "cliente/?email=" . $idweb;

        $content = self::peticionget($url, 'recuperarclienteerpAlsernet');
        if ($content != "") {
            if ($content != "Not Found") {

                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }


    /**
     *  JLP - 14/03/2022 - Formatear array para que me devuelva array con tantas posiciones como pedidos tenga
     *
     * Esto es porque si tiene varios pedidos, devuelve un array con cada pedido en cada posicion del array, PERFECTO!
     * pero si sólo tiene 1 pedido no devuelve un array con 1 posicion, devuelve un array con 12 posiciones y cada posicion es propiedades del pedido
     * Esto es un problema porque al recorrer el array no sé si estoy recorriendo un pedido o un array.
     *
     * Después de este formateo, si sólo hay un pedido, devuelvo un array de 1 posición, y siempre estaré recorriendo un array de tantas posiciones como pedidos tenga.
     */
    protected function formatOrderArrayErp($array)
    {
        if ($array && count($array)) {
            foreach ($array as $key1 => $item1) {
                if ($item1) {
                    foreach ($item1 as $key2 => $item2) {
                        if (!is_array($item2)) {
                            $array[$key1] = [$array[$key1]];
                        }
                        break;
                    }
                }
                break;
            }


            // formatear array pedido del ERP
            foreach ($array as $key1 => $item1) {
                if ($item1) {
                    foreach ($item1 as $key2 => $item2) {
                        if ($item2) {

                            $array[$key1][$key2] = self::formatOrderErp($item2);
                        }
                    }
                }
            }
        } else {
            return false;
        }

        // devolver array de pedidos
        if (isset($array['resource']) && $array['resource']) {
            $orders = $array['resource'];

            usort($orders, function ($a, $b) {
                return strcmp($b['fpedido'], $a['fpedido']);
            });

            return $orders;
        }

        return false;
    }

    protected static function formatOrderErp($order)
    {
        /**
         * JLP - 15/03/2022 - Quitar resource y poner array
         */
        foreach ($order as $key1 => $item1) {
            if ($item1 && is_array($item1)) {
                foreach ($item1 as $key2 => $item2) {
                    if ($item2 && is_array($item2)) {
                        if ($key2 == 'resource') {
                            $order[$key1] = self::formatArray($order[$key1][$key2]);
                        }
                    }
                }
            }
        }

        /**
         * JLP - 15/03/2022 - Añadir campos precio formateados x.xxx,xx €
         */
        // precio total
        $order_total = $order['total_con_impuestos'];
        if (is_numeric($order_total)) {


            $order_total = Tools::displayPrice((float)$order_total);
        }

        $order['total_con_impuestos_display'] = $order_total;

        // precio productos
        foreach ($order['lineas_pedido_cliente'] as $key_product => $product) {
            $product_price = $product['total_con_impuestos'];
            if (is_numeric($product_price)) {
                $product_price = Tools::displayPrice((float)$product_price);
            }
            $order['lineas_pedido_cliente'][$key_product]['total_con_impuestos_display'] = $product_price;
        }

        // coste envío
        $shipping_cost = $order['envio']['coste'];
        if (is_numeric($shipping_cost)) {
            $shipping_cost = Tools::displayPrice((float)$shipping_cost);
        }
        $order['envio']['coste_display'] = $shipping_cost;

        //pagos
        foreach ($order['forma_pago_pedido_cliente'] as $key_payment => $payment) {
            $payment_amount = $payment['importe'];
            if (is_numeric($payment_amount)) {
                $payment_amount = Tools::displayPrice((float)$payment_amount);
            }
            $order['forma_pago_pedido_cliente'][$key_payment]['importe_display'] = $payment_amount;

            // forma de pago
            $formapago = $payment['idformapago'];
            if (Module::isEnabled('alvarezhistoricopedidos')) {
                require_once _PS_MODULE_DIR_ . 'alvarezhistoricopedidos/classes/Formapago.php';
                $formapago = Formapago::getNameByIdErp((int)$formapago);
            }
            $order['forma_pago_pedido_cliente'][$key_payment]['formapago'] = $formapago;
        }

        $order_detail_url = '';
        $order_seguimiento_url = '';
        if (Module::isEnabled('alvarezhistoricopedidos')) {
            $order_detail_url = Context::getContext()->link->getModuleLink('alvarezhistoricopedidos', 'detallepedido', ['npedidocli' => $order['npedidocli'], 'serie' => $order['serie']['descripcorta']]);
            $order_seguimiento_url = Context::getContext()->link->getModuleLink('alvarezhistoricopedidos', 'detallepedido', ['npedidocli' => $order['npedidocli'], 'serie' => $order['serie']['descripcorta'], 'phone' => $order['envio']['telefono']]);
        }
        $order['detalle_url'] = $order_detail_url;
        $order['seguimiento_url'] = $order_seguimiento_url;
        $order['referencia'] = $order['serie']['descripcorta'] . '-' . $order['npedidocli'];

        return $order;
    }

    protected static function formatArray($array)
    {
        foreach ($array as $key => $item) {
            if (!is_array($item)) {
                $array = [$array];
            }
            break;
        }

        return $array;
    }


    public static function recuperardatosclienteerp($dni, $apellidos, $email, $telefono)
    {

        $url = self::URL_ERP . "cliente/?dni=" . $dni . "&apellidos=" . $apellidos . "&email=" . $email . "&telefono1=" . $telefono;
        $content = self::peticionget($url, 'recuperardatosclienteerp');

        return $content;
        // return '';
    }


    public static function recuperardatosclienteerpporidweb($idweb)
    {
        $url = self::URL_ERP . "cliente/?idclienteweb=" . $idweb;
        $content = self::peticionget($url, 'recuperardatosclienteerpporidweb');

        return $content;
        //return '';

    }

    public static function recuperardatosclienteerpporidgestion($idgestion)
    {

        $url = self::URL_ERP . "cliente/?idcliente_gestion=" . $idgestion;
        $content = self::peticionget($url, 'recuperardatosclienteerpporidgestion');

        return $content;
        //return '';

    }

    public static function getIdiomaGestion($id_lang_ps)
    {
        switch ($id_lang_ps) {
            case 1:
                return 2;
                break; //es
            case 2:
                return 6;
                break; //en
            case 3:
                return 5;
                break; //fr
            case 4:
                return 7;
                break; //pt
            case 5:
                return 1;
                break; //de
            case 6:
                return 42;
                break; //it
            //case 7: return 34; break; //nl
            default:
                return false;
                break;
        }
    }

    public static function getPaisGestion($id_lang_ps)
    {
        switch ($id_lang_ps) {
            case 1:
                return 1;
                break; //es
            case 2:
                return 48;
                break; //en
            case 3:
                return 4;
                break; //fr
            case 4:
                return 2;
                break; //pt
            case 5:
                return 3;
                break; //de
            case 6:
                return 42;
                break; //it
            //case 7: return 34; break; //nl
            default:
                return false;
                break;
        }
    }

    public static function guardardatosclienteerp(
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

        $url = self::URL_ERP . "cliente/";

        $data = "";

        if ($idcliente_gestion != "") $data .= "&idcliente_gestion=" . $idcliente_gestion;
        if ($cliente_nombre != "") $data .= "&cliente_nombre=" . $cliente_nombre;
        if ($cliente_apellidos != "") $data .= "&cliente_apellidos=" . $cliente_apellidos;
        if ($cliente_cif != "") $data .= "&cliente_cif=" . $cliente_cif;
        if ($cliente_email != "") $data .= "&cliente_email=" . $cliente_email;
        if ($cliente_percontacto != "") $data .= "&cliente_percontacto=" . $cliente_percontacto;
        if ($cliente_observaciones != "") $data .= "&cliente_observaciones=" . $cliente_observaciones;
        if ($cliente_idioma != "") $data .= "&cliente_idioma=" . $cliente_idioma;
        if ($cliente_codigo_internet != "") $data .= "&cliente_codigo_internet=" . $cliente_codigo_internet;
        if ($cliente_calle != "") $data .= "&cliente_calle=" . $cliente_calle;
        if ($cliente_codigopostal != "") $data .= "&cliente_codigopostal=" . $cliente_codigopostal;
        if ($cliente_poblacion != "") $data .= "&cliente_poblacion=" . $cliente_poblacion;
        if ($cliente_provincia != "") $data .= "&cliente_provincia=" . $cliente_provincia;
        if ($cliente_pais != "") $data .= "&cliente_pais=" . $cliente_pais;
        if ($cliente_calle_observaciones != "") $data .= "&cliente_calle_observaciones=" . $cliente_calle_observaciones;
        if ($cliente_telefono != "") $data .= "&cliente_telefono=" . $cliente_telefono;
        if ($cliente_telefono_observacion != "") $data .= "&cliente_telefono_observacion=" . $cliente_telefono_observacion;
        if ($cliente_telefono_envio_sms != "") $data .= "&cliente_telefono_envio_sms=" . $cliente_telefono_envio_sms;
        if ($cliente_zona_fiscal != "") $data .= "&cliente_zona_fiscal=" . $cliente_zona_fiscal;
        if ($cliente_genero != "") $data .= "&cliente_genero=" . $cliente_genero;
        if ($cliente_fnacimiento != "") $data .= "&cliente_fnacimiento=" . $cliente_fnacimiento;
        if ($cliente_faceptacion_lopd != "") $data .= "&cliente_faceptacion_lopd=" . $cliente_faceptacion_lopd;
        if ($cliente_no_info_comercial != "") $data .= "&cliente_no_info_comercial=" . $cliente_no_info_comercial;
        if ($cliente_no_datos_a_terceros != "") $data .= "&cliente_no_datos_a_terceros=" . $cliente_no_datos_a_terceros;
        if ($cliente_idcatalogo != "") $data .= "&cliente_idcatalogo=" . $cliente_idcatalogo;
        if ($prefijo_telefono != "") $data .= "&prefijo_telefono=" . $prefijo_telefono;
        if ($cliente_forzar_creacion != "") $data .= "&cliente_forzar_creacion=" . $cliente_forzar_creacion;


        $content = self::peticionpost($url, $data, 'guardardatosclienteerp');

        return $content;
        //return '';

    }


    public static function recuperarcatalogosclienteerp($idcliente_gestion)
    {
        $url = self::URL_ERP . "clientecatalogo/?idcliente_gestion=" . $idcliente_gestion;
        $content = self::peticionget($url, 'recuperarcatalogosclienteerp');
        return $content;
        //return '';
    }

    public static function suscribircatalogosporeamilerp($cliente_email, $cliente_idcatalogo)
    {
        $url = self::URL_ERP . "clientecatalogo/";
        $data = "cliente_email=" . $cliente_email . "&cliente_idcatalogo=" . $cliente_idcatalogo;
        $content = self::peticionpost($url, $data, 'suscribircatalogosporeamilerp');
        return $content;
        //return '';
    }

    public static function delsuscribircatalogosporeamilerps($cliente_email, $cliente_idcatalogo = null)
    {
        $url = self::URL_ERP . "clientecatalogo/";
        $data = "cliente_email=" . $cliente_email . "&cliente_idcatalogo=" . $cliente_idcatalogo;
        $url .= '?' . $data;
        $data = '';
        $content = self::peticiondelete($url, $data, 'delsuscribircatalogosporeamilerp');
        return $content;
    }

    public static function delsuscribircatalogosporeamilerp($cliente_email, $cliente_idcatalogo = null)
    {

        $params = ['cliente_email=' . urlencode($cliente_email)];

        if (!is_null($cliente_idcatalogo) && is_numeric($cliente_idcatalogo)) {
            $params[] = 'cliente_idcatalogo=' . urlencode($cliente_idcatalogo);
        }

        $url = self::URL_ERP . "clientecatalogo/?" . implode('&', $params);

        $content = self::peticiondelete($url, '', 'delsuscribircatalogosporeamilerp');

        return $content;
    }


    public static function savelopd($email, $fecha, $no_info_comercial, $no_datos_a_terceros)
    {

        $data = [];
        $data["cliente_email"] = $email;
        $data["cliente_faceptacion_lopd"] = $fecha;
        $data["cliente_no_info_comercial"] = $no_info_comercial;
        $data["cliente_no_datos_a_terceros"] = $no_datos_a_terceros;

        foreach ($data as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }
        $fields_string = rtrim($fields_string, '&');

        if (!$email) return true;

        AddisLogger::log(__FILE__, __FUNCTION__, null, 'Se envía a gestión la aceptación de RGPD: ' . $no_info_comercial . ' (0 - Acepta), Correo: ' . $email . ', Formulario: ' . Tools::getValue('formulario_nombre'));
        return self::peticionput(self::URL_ERP . "cliente/", $fields_string);
        //return '';

    }


    public static function recuperarstockcentral($idarticulo)
    {
        $url = self::URL_ERP . "stock-central-web/" . $idarticulo . "/";
        $content = self::peticionget($url, 'recuperarstockcentral');

        if ($content != "") {
            if ($content != "Not Found") {
                $response = new SimpleXMLElement($content);
                return (float)$response->unidades;
            } else {
                return 0;
            }
        } else {
            return 0;
        }
        //return 0;
    }

    public static function recuperaridarticulo($codigo)
    {
        $url = self::URL_ERP . "articulo/" . $codigo . "/";
        $content = self::peticionget($url, 'recuperaridarticulo');

        if ($content != "") {
            if ($content != "Not Found") {
                $response = new SimpleXMLElement($content);

                return $response->idarticulo;
            } else {
                return "0";
            }
        } else {
            return "0";
        }
        //return 0;
    }


    public static function consultabono($idbono, $codigo_verificacion, $importe_venta, $origen)
    {
        $url = self::URL_ERP . "bono/" . $idbono . "/?codigo_verificacion=" . $codigo_verificacion . "&importe_venta=" . $importe_venta . "&origen=" . $origen;
        $content = trim(self::peticionget($url, 'consultabono'));

        $data = [];
        if (substr($content, 0, 5) == '<?xml') { // TODO OK
            $xml = simplexml_load_string($content, "SimpleXMLElement", LIBXML_NOCDATA);
            $json = json_encode($xml);
            $array = json_decode($json, TRUE);

            $data['success'] = true;
            $data['data'] = $array;
        } else { // ERP HA DEVUELTO ERROR
            $data['success'] = false;
            $data['message'] = $content;
        }

        //return $content;
        return $data;
        //return ['success' => false, 'message' => ''];
    }


    public static function marcarbono($idbono, $operacion, $codigo_verificacion, $importe_venta, $importe_inicial_tarjeta_regalo, $origen)
    {
        $url = self::URL_ERP . "bono/" . $idbono . "/?origen=" . $origen;
        $data = "operacion=" . $operacion . "&codigo_verificacion=" . $codigo_verificacion . "&importe_venta=" . $importe_venta . "&importe_inicial_tarjeta_regalo=" . $importe_inicial_tarjeta_regalo;
        $content = self::peticionput($url, $data);
        return $content;
        //return '';
    }


    public static function consultavalecompra($idvale)
    {
        $url = self::URL_ERP . "vale/" . $idvale . "/";
        $content = self::peticionget($url, 'consultavalecompra');
        return $content;
        //return '';
    }


    public static function actualizarvalecompra($idvale, $operacion, $motivo)
    {
        $url = self::URL_ERP . "vale/" . $idvale . "/";
        $data = "operacion=" . $operacion . "&motivo=" . $motivo;
        $content = self::peticionput($url, $data);
        return $content;
        //return '';
    }

    public static function crearvalecompra($importe, $tipo, $idalmacen, $idcliente, $observaciones, $tiene_codigo_comprobacion, $id_vale_original, $id_vale_anterior)
    {
        $url = self::URL_ERP . "vale/";
        $data = "importe=" . $importe . "&tipo=" . $tipo . "&idalmacen=" . $idalmacen . "&idcliente=" . $idcliente . "&observaciones=" . $observaciones . "&tiene_codigo_comprobacion=" . $tiene_codigo_comprobacion . "&id_vale_original=" . $id_vale_original . "&id_vale_anterior=" . $id_vale_anterior;

        $content = self::peticionpost($url, $data, 'crearvalecompra');
        return $content;
        //return '';
    }


    public static function tienetarifaplana($idweb)
    {
        //Se desactivan las tarifas planas y ya no es necesario consultar a gestión
        return false;

        $url = self::URL_ERP . "cliente/?idclienteweb=" . $idweb;

        $content = self::peticionget($url, 'tienetarifaplana');
        //dump($content);
        if ($content != "") {
            if ($content != "Not Found") {
                $response = new SimpleXMLElement($content);

                if ($response->cliente_cuota) {

                    $resources = $response->cliente_cuota->children();

                    $date = new DateTime();
                    $hoy = date_format($date, 'Y-m-d');


                    foreach ($resources as $resource) {

                        $finservicio = $resource->ffinservicio;
                        $fcontratacion = $resource->fcontratacion;
                        $estado = $resource->estado;
                        $codigo = $resource->articulo->codigo;

                        //echo $finservicio. " " . $fcontratacion . " " . $estado . " ". $codigo;

                        if ($finservicio >= $hoy) {
                            return true;
                        }
                    }


                    return false;
                } else {

                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
        return false;
    }


    private static function getCatalogo($idproduct)
    {

        //coger el deporte de la categoria "abuela"
        // $prod = new Product($idproduct);
        // $catdef = $prod->id_category_default;

        //PrestaShopLogger::addLog("Categoria defecto en producto:".$catdef);

        // if (($catdef==2) || ($catdef==0)){
        // $catdef = "".Db::getInstance()->getValue("SELECT min(id_category) FROM aalv_category_product WHERE id_product=".$idproduct." and id_category not in (0,2)");
        $catdef = "" . Db::getInstance()->getValue("SELECT id_category_default FROM aalv_product ap WHERE id_product = " . $idproduct);

        // dump("idproduct => ".$idproduct);

        // dump("id_category_default => ".$catdef);
        if ($catdef <= 2) {
            $catdef = "" . Db::getInstance()->getValue("SELECT min(id_category) FROM aalv_category_product WHERE id_product=" . $idproduct . " and id_category not in (0,2)");
            // dump("catdef TRUE => ".$catdef);
        } else {
            $catdef = Category::getCategoryGrandFather($catdef);
            // dump("catdef FALSE => ".$catdef);
        }


        if ($catdef == "") {
            $catdef = 4; //por defecto caza
        }

        if ($catdef > 11) {
            $catdef = Category::getCategoryGrandFather($catdef);
        }


        // }

        //PrestaShopLogger::addLog("Categoria defecto:".$catdef);
        /* $catdefault => idcatalog_web en Gestion, Tabla catalogo */
        switch ($catdef) {
            case 3: // ID PS GOLF
                $catdefault = 1; // ID GESTION GOLF ESPAÑA
                break;
            case 4: // ID PS CAZA
                $catdefault = 5; // ID GESTION CAZA ESPAÑA
                break;
            case 5: // ID PS PESCA
                $catdefault = 6; // ID GESTION PESCA ESPAÑA
                break;
            case 6: // ID PS HIPICA
                $catdefault = 3; // ID GESTION HIPICA ESPAÑA
                break;
            case 7: // ID PS BUCEO
                $catdefault = 4; // ID GESTION BUCEO ESPAÑA
                break;
            case 8: // ID PS NAUTICA
                $catdefault = 2; // ID GESTION NAUTICA ESPAÑA
                break;
            case 9: // ID PS ESQUI
                $catdefault = 9; // ID GESTION ESQUI ESPAÑA
                break;
            case 10: // ID PS PADEL
                $catdefault = 1395; // ID GESTION PADEL ESPAÑA
                break;
            case 11: // ID PS AVENTIRA [AGREGADO]
                $catdefault = 10; // GENERICO AVENTURA
                break;
            default:
                $catdefault = 5; //por defecto caza
                break;
        }

        //
        // if ($deporte==0){
        //     $deporte=5; //por defecto caza
        // }
        return $catdefault;
    }


    private static function getXmlLinesLottery($orderId)
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


    private static function getxmllineas($order,$data)
    {

        /*
        <?xml version="1.0" encoding="UTF-8" ?>
        <lineas>
        <linea>
        <referencia>TARIFAPLANA</referencia>
        <unidades>1</unidades>
        <precio>10</precio>
        <dto>0</dto>
        <nota_general>ALGO </nota_general>
        <idlote></idlote>
        <seclote></seclote>
        <idcatalogo>3</idcatalogo>
        </linea>
        </lineas>
        */


        //recuperar productos regalo si los hay
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

            if ($product["id_warehouse"] != 9) $prioridad = 3;


            $idproduct = $product["product_id"];
            // ver si es lote
            $rowslotes = Db::getInstance()->ExecuteS("SELECT * FROM aalv_wk_bundle_order_detail WHERE id_order=" . $order->id . " and id_ps_product=" . $idproduct . " and id_customization=" . $product["id_customization"]);
            if ($rowslotes) {

                $seclotenum = 0;


                foreach ($rowslotes as $rowlote) {

                    $seclotenum = $seclotenum + 1;
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


                    $idcatalogo = self::getCatalogo($idprodbundle);


                    $xml = $xml . '<linea><referencia>' . $ref . '</referencia><unidades>' . $uni . '</unidades><precio>' . $pre . '</precio><dto>0</dto><nota_general></nota_general><idlote>' . $idlote . '</idlote><seclote>' . $seclotenum . '</seclote><idcatalogo>' . $idcatalogo . '</idcatalogo></linea>';
                }
            }
            // else if ($product["product_reference"] == 'PACKMAXXIM45' || $product["product_reference"] == 'PACKMAXXIM55'){
            //     switch ($product["product_reference"]) {
            //         case 'PACKMAXXIM45':
            //             $id =
            //             $uni = $rowlote["product_qty"];
            //             // CB278-4,5
            //             // MBIM5-45
            //             // PB6
            //             // PB6D
            //             # code...
            //             break;
            //         case 'PACKMAXXIM55':
            //             # code...
            //             break;

            //     }
            // }
            else {


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


                $idcatalogo = self::getCatalogo($idproduct);

                $xml = $xml . '<linea><referencia>' . $ref . '</referencia><unidades>' . $uni . '</unidades><precio>' . $pre . '</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo>' . $idcatalogo . '</idcatalogo></linea>';
            }


            /*

            $ref=$product["product_reference"];
            $uni=$product["product_quantity"];
            $pre=$product["unit_price_tax_incl"];

            $xml = $xml.'<linea><referencia>'.$ref.'</referencia><unidades>'.$uni.'</unidades><precio>'.$pre.'</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo>3</idcatalogo></linea>';
            */
        }

        $addresdelivery = new Address($order->id_address_delivery);
        if (($addresdelivery->id_country == 242) || ($addresdelivery->id_country == 243)) {

            $xml = $xml . '<linea><referencia>ADUANAS</referencia><unidades>1</unidades><precio>0</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo>5</idcatalogo></linea>';
        }


        if (($addresdelivery->id_country != 242) && ($addresdelivery->id_country != 243) && ($addresdelivery->id_country != 244) && ($addresdelivery->id_country != 245) && ($addresdelivery->id_country != 6) && ($addresdelivery->id_country != 15)) {

            $xml = $xml . '<linea><referencia>CHECK-ORDER</referencia><unidades>1</unidades><precio>0</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';
        }


        $descripcioncupon = Db::getInstance()->ExecuteS("SELECT name,value FROM aalv_order_cart_rule where id_order=" . $order->id);

        if (count($descripcioncupon) != 0) {

            foreach ($descripcioncupon as $key => $value) {
                # code...


                // $desccupon = Db::getInstance()->getValue("SELECT value FROM aalv_order_cart_rule where id_order=" . $order->id);
                $desccupon = -abs($value['value']);

                if ($value['name'] == "CHEQUE PADRE 2024") {
                    $xml = $xml . '<linea><referencia>CHEQUE-PADRE</referencia><unidades>1</unidades><precio>' . $desccupon . '</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';
                }
                if ($value['name'] == "Cheque cumpleaños generado desde la web") {
                    $xml = $xml . '<linea><referencia>CUMPLEAÑOS</referencia><unidades>1</unidades><precio>' . $desccupon . '</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';
                }

                if ($value['name'] == "CHEQUE MADRE 2024") {
                    $xml = $xml . '<linea><referencia>CHEQUE-MADRE</referencia><unidades>1</unidades><precio>' . $desccupon . '</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';
                }

                if ($value['name'] == "Bono fidelización") {
                    $xml = $xml . '<linea><referencia>FIDELIZACION</referencia><unidades>1</unidades><precio>' . $desccupon . '</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';
                }

                if ($value['name'] == "OFERTA 3x2 Bolas Wilson") {
                    $xml = $xml . '<linea><referencia>OFERTA</referencia><unidades>1</unidades><precio>' . $desccupon . '</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';
                }

                if ($value['name'] == "SAN VALENTIN 2025") {
                    $xml = $xml . '<linea><referencia>CHEQUE-VALENTIN</referencia><unidades>1</unidades><precio>' . $desccupon . '</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';
                }

                if ($value['name'] == "Reserva fittings") {
                    $xml = $xml . '<linea><referencia>GFITTING-2</referencia><unidades>1</unidades><precio>' . $desccupon . '</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';
                }

                if ($value['name'] == "PLAN RENOVE ARMAS DE BALINES") {
                    $xml = $xml . '<linea><referencia>PROMO-CARABINA</referencia><unidades>1</unidades><precio>' . $desccupon . '</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';
                }

                if ($value['name'] == "Plan Renove Armas de Balines") {
                    $xml = $xml . '<linea><referencia>PROMO-CARABINA</referencia><unidades>1</unidades><precio>' . $desccupon . '</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';
                }

                if ($value['name'] == "PLANO RENOVE ESTREIE ARMA DE CHUMBOS") {
                    $xml = $xml . '<linea><referencia>PROMO-CARABINA</referencia><unidades>1</unidades><precio>' . $desccupon . '</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';
                }

                // if ($value['name'] =="Plan Renove Drivers"){
                //     $xml = $xml.'<linea><referencia>RENOVEDRIVER</referencia><unidades>1</unidades><precio>'.$desccupon.'</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';

                // }

                if ($value['name'] == "2x1 HILOS IMPERATOR") {
                    $xml = $xml . '<linea><referencia>OFERTA</referencia><unidades>1</unidades><precio>' . $desccupon . '</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';
                }

                // if ($value['name'] =="2x1 Filamentos Imperator"){
                //     $xml = $xml.'<linea><referencia>OFERTA</referencia><unidades>1</unidades><precio>'.$desccupon.'</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';

                // }

                if ($value['name'] == "Tarjeta regalo") {
                    $xml = $xml . '<linea><referencia>T-GENER</referencia><unidades>1</unidades><precio>' . $desccupon . '</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';
                }

                if ($value['name'] == "Bono promoción por catálogo") {
                    $xml = $xml . '<linea><referencia>PROMOCION</referencia><unidades>1</unidades><precio>' . $desccupon . '</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';
                }

                if ($value['name'] == "Plan Renove Drivers") {
                    $xml = $xml . '<linea><referencia>RENOVEDRIVER</referencia><unidades>1</unidades><precio>' . $desccupon . '</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';
                }

                if ($value['name'] == "CHEQUE MADRE 2025") {
                    $xml = $xml . '<linea><referencia>CHEQUE-MADRE</referencia><unidades>1</unidades><precio>' . $desccupon . '</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';
                }

                if ($value['name'] == "PLAN RENOVE ESTRENE CASCOS") {
                    $xml = $xml . '<linea><referencia>RENOVECASCOS</referencia><unidades>1</unidades><precio>' . $desccupon . '</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';
                }

                if ($value['name'] == "RENOVE 2025") {
                    $xml = $xml . '<linea><referencia>PROMO-CARABINA</referencia><unidades>1</unidades><precio>' . $desccupon . '</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';
                }

                if ($value['name'] == "REGALO CHALECO por la compra de 10 cajas de cartuchos Imperator") {
                    $xml = $xml . '<linea><referencia>IMPERATOR</referencia><unidades>1</unidades><precio>' . $desccupon . '</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';
                }

                if ($value['name'] == "REGALO GORRA") {
                    $xml = $xml . '<linea><referencia>PROMOCION</referencia><unidades>1</unidades><precio>' . $desccupon . '</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';
                }



                if($data['idorigenpedidocli'] == 4){
                    if (preg_match('/REVER/', $value['name'])) {
                        $xml = $xml . '<linea><referencia>CHEQUE-REVER</referencia><unidades>1</unidades><precio>' . $desccupon . '</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';
                    }
                }elseif($data['idorigenpedidocli'] == 6){
                    $sql = '
                            SELECT
                             CAST(REGEXP_SUBSTR(cm.message, "[0-9]+(?:\\\\.[0-9]+)?(?=\\\\sEUR)") AS DECIMAL(10,2)) AS amount
                            FROM aalv_customer_message cm
                            JOIN aalv_customer_thread ct ON cm.id_customer_thread = ct.id_customer_thread
                            WHERE cm.message LIKE "%REVER Checkout Payment%"
                            AND ct.id_order = '.$order->id;
                    $pago = Db::getInstance()->ExecuteS($sql);

                    $total_products_wt = ($order->total_products_wt > 0) ? -abs($order->total_products_wt) : $order->total_products_wt;

                    $total_products_wt = $total_products_wt + $pago[0]['amount'];
                    $xml = $xml . '<linea><referencia>CAMBIO</referencia><unidades>1</unidades><precio>' . $total_products_wt . '</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';
                }
            }

        }


        if ($order->total_shipping <= 0) {
            $xml = $xml . '<linea><referencia>PORTES-GRATIS</referencia><unidades>1</unidades><precio>0</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo></idcatalogo></linea>';
        }


        $xml = $xml . '</lineas>';
        return array("xml" => $xml, "prioridad" => $prioridad);
    }


    private static function getProvinciaPorCP($cp)
    {

        $provinces = array(
            '01' => "Alava",         // alava
            '02' => "Albacete",         // albacete
            '03' => "Alicante",         // alicante
            '04' => "Almería",         // almeria
            '05' => "Ávila",         // avila
            '06' => "Badajoz",         // badajoz
            '07' => "Baleares",         // baleares
            '08' => "Barcelona",         // barcelona
            '09' => "Burgos",         // burgos
            '10' => "Cáceres",         // caceres
            '11' => "Cádiz",         // cadiz
            '12' => "Castellón",         // castellon
            '13' => "Ciudad Real",         // ciudad real
            '14' => "Córdoba",         // cordoba
            '15' => "A Corunya",         // a corunya
            '16' => "Cuenca",         // cuenca
            '17' => "Girona",         // girona
            '18' => "Granada",         // granada
            '19' => "Guadalajara",         // guadalajara
            '20' => "Guipuzkoa",         // guipuzcoa
            '21' => "Huelva",         // huelva
            '22' => "Huesca",         // huesca
            '23' => "Jaen",         // jaen
            '24' => "León",         // leon
            '25' => "Lleida",         // lleida
            '26' => "La Rioja",         // la rioja
            '27' => "Lugo",         // lugo
            '28' => "Madrid",         // madrid
            '29' => "Málaga",         // malaga
            '30' => "Murcia",         // murcia
            '31' => "Navarra",         // navarra
            '32' => "Orense",         // orense
            '33' => "Asturias",         // asturias
            '34' => "Palencia",         // palencia
            '35' => "Las Palmas",         // las palmas
            '36' => "Pontevedra",         // pontevedra
            '37' => "Salamanca",         // salamanca
            '38' => "Sta. Cruz Tenerife",         // sta cruz tenerife
            '39' => "Cantabria",         // cantabria
            '40' => "Segovia",         // segovia
            '41' => "Sevilla",         // sevilla
            '42' => "Soria",         // soria
            '43' => "Tarragona",         // tarragona
            '44' => "Teruel",         // teruel
            '45' => "Toledo",         // toledo
            '46' => "Valencia",         // valencia
            '47' => "Valladolid",         // valladolid
            '48' => "Vizcaya",         // vizcaya
            '49' => "Zamora",         // zamora
            '50' => "Zaragoza",         // zaragoza
            '51' => "Ceuta",         // ceuta
            '52' => "Melilla"                          // melilla
        );

        if (isset($provinces[substr($cp, 0, 2)])) {
            return $provinces[substr($cp, 0, 2)];
        } else {
            return $provinces['01'];
        }
    }


    public static function toGestion($cadena)
    {
        $key = 'aK-#s$q_Fs1?b*EE';
        $key .= substr($key, 0, 8);
        $iv = 'w=c@@ZqP';
        return base64_encode(mcrypt_encrypt(MCRYPT_TRIPLEDES, $key, $cadena, MCRYPT_MODE_CFB, $iv));
    }


    public static function construirdatospedido($idpedido, $idclientegestion)
    {

        $order = new Order($idpedido);

        $customer = new Customer($order->id_customer);

        //echo $order->id_address_invoice . " ". $order->id_address_invoice."<br/>";


        $addresinvoice = new Address($order->id_address_invoice);
        $addresdelivery = new Address($order->id_address_delivery);

        $data = [];



        $sql = "SELECT force_type FROM aalv_orders_envio_gestion WHERE id_order=" . $idpedido . " and posible_enviar=1 and fecha_envio is null order by id desc";

        $force_type = "" . Db::getInstance()->getValue($sql);

        if ($force_type == "3") {
            $data["cliente_forzar_creacion"] = 1;
        } else {
            if ("" . $idclientegestion != "") {
                $data["idcliente_gestion"] = $idclientegestion;
            }
        }


        $data["fecha_pedido"] = str_replace(" ", "T", $order->date_add);

        $data["idorigenpedidocli"] = 4;

        $paiseszona1 = "
                        3,
                        6,
                        7,
                        9,
                        12,
                        13,
                        14,
                        16,
                        18,
                        19,
                        20,
                        23,
                        26,
                        36,
                        37,
                        40,
                        45,
                        47,
                        52,
                        74,
                        76,
                        86,
                        93,
                        97,
                        242,
                        244,
                        231,
                        233,
                        106,
                        191,
                        101,
                        142,
                        108,
                        115,
                        124,
                        129,
                        130,
                        132,
                        138,
                        146,
                        147,
                        149,
                        175,
                        184,
                        188,
                        209,
                        214,
                        ";
        $paiseszona1arr = explode(",", $paiseszona1);


        // $zona_fiscal=1;
        // //if (($addresdelivery->id_country==6)||($addresdelivery->id_country==244)){
        // if (in_array($addresdelivery->id_country, $paiseszona1arr)){
        //     $zona_fiscal=1;
        // }
        // else{

        //     if (($addresdelivery->id_country==15)||($addresdelivery->id_country==245)){
        //         $zona_fiscal=2;
        //     }
        //     else
        //     {
        //         $zona_fiscal=3;
        //     }
        // }

        $zona_fiscal = 1;
        // Ajustes específicos
        if ($addresdelivery->id_country == 1) { // Alemania
            $zona_fiscal = 5; // Id de Gestion Alemania
        } elseif ($addresdelivery->id_country == 8) { // Francia
            $zona_fiscal = 4; // Id de Gestion Francia
        } elseif ($addresdelivery->id_country == 10) { // Italia
            $zona_fiscal = 6; // Id de Gestion Italia
        } elseif ($addresdelivery->id_country == 2) { // Austria
            $zona_fiscal = 7; // Id de Gestion Austria
        } else {
            if (in_array($addresdelivery->id_country, $paiseszona1arr)) {
                $zona_fiscal = 1; // Id de Gestion España
            } elseif ($addresdelivery->id_country == 15 || $addresdelivery->id_country == 245) {
                $zona_fiscal = 2; // ID de Gestion Portugal
            } else {
                $zona_fiscal = 3; // Id de Gestion resto del mundo
            }
        }

        $data["zona_fiscal"] = $zona_fiscal;

        $data["telefono_contacto"] = substr($addresdelivery->phone, 0, 20);
        $data["identificador_origen"] = $idpedido;
        if ($order->gift || $order->gift_message != '') {
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

        $data["cliente_nombre"] = strtoupper(substr($addresinvoice->firstname, 0, 50));

        /*
        if (strpos($addresinvoice->lastname, " ")){
            $data["cliente_apellido1"] = substr($addresinvoice->lastname,0,strpos($addresinvoice->lastname, " "));
            $data["cliente_apellido2"] = substr($addresinvoice->lastname,1+strpos($addresinvoice->lastname, " "));
        }
        else{
            $data["cliente_apellido1"] = $addresinvoice->lastname;
        }
        */

        $data["cliente_apellidos"] = strtoupper(substr($addresinvoice->lastname, 0, 60));

        $data["cliente_cif"] = strtoupper(substr($addresinvoice->vat_number, 0, 20));
        $data["cliente_email"] = $customer->email;

        switch ($addresinvoice->id_country) {
            case 6:
            case 242:
            case 243:
            case 244:
            case 44:
            case 34:
            case 68:
            case 69:
            case 73:
            case 81:
            case 100:
            case 144:
            case 169:
            case 219:
                $cliente_idioma = 2; //ID DEL IDIOMAS DE GESTION EN CASTELLANO
                break;
            case 15:
            case 245:
            case 58:
                $cliente_idioma = 7; // ID DEL IDIOMAS DE GESTION EN PORTUGUES
                break;
            case 1:
            case 2:
                $cliente_idioma = 1; // ID DEL IDIOMAS DE GESTION EN ALEMAN
                break;
            case 8:
            case 3:
                $cliente_idioma = 5; //ID DEL IDIOMAS DE GESTION EN FRANCES
                break;
            case 17: // Reino Unido
            case 21: // Estados Unidos
                $cliente_idioma = 6; //ID DEL IDIOMAS DE GESTION EN INLGES
                break;
            case 10: // Italiano
                $cliente_idioma = 100000000; //ID DEL IDIOMAS DE GESTION EN Italiano
                break;
            default:
                $cliente_idioma = 2; //POR DEFECTO LOS CLIENTES VAN A TENER EL IDIOMA ID DEL IDIOMAS DE GESTION EN INLGES
                break;
        }
        $data["cliente_idioma"] = $cliente_idioma;

        if (!$customer->is_guest) {
            $data["cliente_codigo_internet"] = $customer->id;
        }

        $addressCompl = (!empty($addresinvoice->address2)) ? (" (" . $addresinvoice->address2 . ")") : '';
        $data["cliente_calle"] = strtoupper(substr($addresinvoice->address1 . $addressCompl, 0, 255));


        $data["cliente_codigopostal"] = strtoupper(substr($addresinvoice->postcode, 0, 20));
        $data["cliente_poblacion"] = strtoupper(substr($addresinvoice->city, 0, 50));

        if ($addresinvoice->id_state != 0) {
            $prov = new State($addresinvoice->id_state);
            $data["cliente_provincia"] = strtoupper(substr($prov->name, 0, 50));
        }
        $pais = new Country($addresinvoice->id_country);
        $data["cliente_pais"] = strtoupper(substr($pais->name[1], 0, 50));

        if (($addresinvoice->id_country == 6) || ($addresinvoice->id_country == 242) || ($addresinvoice->id_country == 243) || ($addresinvoice->id_country == 244)) {
            $data["cliente_pais"] = "ESPAÑA";
        }

        if (($addresinvoice->id_country == 15) || ($addresinvoice->id_country == 245)) {
            $data["cliente_pais"] = "PORTUGAL";
        }


        $data["cliente_telefono"] = substr($addresinvoice->phone, 0, 20);

        $data["cliente_telefono_envio_sms"] = self::isMobilePhone($addresinvoice->phone, $addresinvoice->id_country);


        // $cliente_zona_fiscal=1;

        // //if (($addresinvoice->id_country==6)||($addresinvoice->id_country==244)){
        // if (in_array($addresinvoice->id_country, $paiseszona1arr)){
        //     $cliente_zona_fiscal=1;
        // }
        // else{

        //     if (($addresinvoice->id_country==15)||($addresinvoice->id_country==245)){
        //         $cliente_zona_fiscal=2;
        //     }
        //     else
        //     {
        //         $cliente_zona_fiscal=3;

        //     }
        // }

        $cliente_zona_fiscal = 1;
        // Ajustes específicos
        if ($addresinvoice->id_country == 1) { // Alemania
            $cliente_zona_fiscal = 5; // Id de Gestion Alemania
        } elseif ($addresinvoice->id_country == 8) { // Francia
            $cliente_zona_fiscal = 4; // Id de Gestion Francia
        } elseif ($addresinvoice->id_country == 15 || $addresinvoice->id_country == 245) { //Portugal
            $cliente_zona_fiscal = 2; // ID de Gestion Portugal
        } elseif ($addresinvoice->id_country == 10) { // Italia
            $cliente_zona_fiscal = 6; // Id de Gestion Italia
        } elseif ($addresinvoice->id_country == 2) { // Austria
            $cliente_zona_fiscal = 7; // Id de Gestion Austria
        } else {
            if (in_array($addresinvoice->id_country, $paiseszona1arr)) {
                $cliente_zona_fiscal = 1; // Id de Gestion España
            } else {
                $cliente_zona_fiscal = 3; // Id de Gestion resto del mundo
            }
        }

        $data["cliente_zona_fiscal"] = $cliente_zona_fiscal;


        if ($customer->birthday != "0000-00-00") {
            $data["cliente_fnacimiento"] = $customer->birthday . "T00:00:00";
        }
        $data["cliente_faceptacion_lopd"] = substr($customer->date_add, 0, 10) . "T00:00:00";
        $data["cliente_no_info_comercial"] = 0;
        $data["cliente_no_datos_a_terceros"] = 0;

        $data["prefijo_telefono"] = "00" . $pais->call_prefix; //"0034";


        //ver si tiene freeshipping en cart_rule
        $tienefreeshipping = "" . Db::getInstance()->getValue("SELECT id_order_cart_rule FROM aalv_order_cart_rule WHERE id_order=" . $idpedido . " and deleted=0 and free_shipping=1");
        $envio_coste = $order->total_shipping_tax_incl;

        if ($tienefreeshipping != "") {
            $envio_coste = 0;
        }


        //ver si el pedido es de recogida en correos
        $textocorreos = '';
        //$textocorreos="".Db::getInstance()->getValue("SELECT texto_oficina FROM aalv_cex_officedeliverycorreo WHERE id_cart=".$order->id_cart);

        // código sacado del modulo correosexpress para saber si un pedido es de recogida en correos
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

                if ($result) { // se trata de un pedido con recogida en oficina de correos
                    $sql = 'SELECT `texto_oficina`
                            FROM `' . _DB_PREFIX_ . 'orders` o
                            INNER JOIN `' . _DB_PREFIX_ . 'cex_officedeliverycorreo` odc ON odc.`id_cart`=o.`id_cart`
                            WHERE `id_order`=' . $order->id;
                    $textocorreos = DB::getInstance()->getValue($sql);
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


            //ver si es de recogida en tienda
            $esrecogidaentienda = "" . Db::getInstance()->getValue("SELECT id_store FROM aalv_kb_pickup_store_address_mapping WHERE id_address=" . $order->id_address_delivery);

            if ($esrecogidaentienda == "") {
                $data["envio_recogida_tienda"] = 0;
                // $data["envio_idtransportista"] = 21;
            } else {

                if (($esrecogidaentienda == "8") || ($esrecogidaentienda == "6") || ($esrecogidaentienda == "7")) {
                    $data["envio_recogida_tienda"] = 1;
                    //averiguar el almacen
                    $almacenrecogida = "";
                    if ($esrecogidaentienda == "8") {
                        $almacenrecogida = "1";  //coruña ALSERNET_TRANSPORTISTA_
                        $data["envio_idtransportista"] = Configuration::get('ALSERNET_TRANSPORTISTA_TIENDA_CORUNA');
                    }
                    if ($esrecogidaentienda == "6") {
                        $almacenrecogida = "3";  //capitan haya
                        $data["envio_idtransportista"] = Configuration::get('ALSERNET_TRANSPORTISTA_TIENDA_CAPITAN_HAYA');
                    }
                    if ($esrecogidaentienda == "7") {
                        $almacenrecogida = "4";  //diego leon
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
            // 4614494#!#C/ GENERAL BARROSO Nº 20 #!#OF.CORREOS: VALENCIA SUC 2 - 4614494#!#46017#!#VALENCIA

            $data["envio_calle"] = strtoupper(substr($datosoficina[1] . " (Oficina de correos:" . $datosoficina[2] . ")", 0, 255));
            $data["envio_localidad"] = strtoupper(substr($datosoficina[4], 0, 50));
            $data["envio_cp"] = strtoupper(substr($datosoficina[3], 0, 20));
            $data["envio_provincia"] = strtoupper(self::getProvinciaPorCP($datosoficina[3]));
            $data["envio_pais"] = "ESPAÑA";
            $data["envio_coste"] = $envio_coste;
            $data["envio_destinatario"] = strtoupper($addresdelivery->firstname . " " . $addresdelivery->lastname);
            $data["envio_telefono"] = substr($addresdelivery->phone, 0, 20); //dejar el del cliente?
            $data["envio_recogida_tienda"] = 0;
        }

        if (!isset($data["envio_idalmacen_recogida"])) {
            $data["envio_idtransportista"] = AlvarezERP::transportistaGestion($addresdelivery, $order, $textocorreos);
        }

        //tema pedido mixto armas
        if (Cart::haveMultipleProductTypes($order->id_cart)) {

            $data["observaciones"] = strtoupper(AddressFormat::generateAddress($addresdelivery));

            $addressCompl = (!empty($addresinvoice->address2)) ? (" (" . $addresinvoice->address2 . ")") : '';
            $data["envio_calle"] = strtoupper(substr($addresinvoice->address1 . $addressCompl, 0, 255));
            $data["envio_localidad"] = strtoupper(substr($addresinvoice->city, 0, 50));
            $data["envio_cp"] = strtoupper(substr($addresinvoice->postcode, 0, 20));

            if ($addresinvoice->id_state != 0) {
                $provd = new State($addresinvoice->id_state);
                $data["envio_provincia"] = strtoupper(substr($provd->name, 0, 50));
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

        /*  InPost
        *   Campo envio_codigo_destino (Opcional)
        */
        // Buscamos el transportista
        $carrier = Db::getInstance()->getValue("select
                                                   concat(rela.selected_relay_country_iso,'-',rela.selected_relay_num) AS selected_relay_num
                                                from
                                                    aalv_orders ord
                                                    inner join aalv_carrier carr on carr.id_carrier = ord.id_carrier
                                                    inner join aalv_mondialrelay_selected_relay rela on rela.id_order = ord.id_order
                                                where
                                                    ord.id_order =  " . $order->id);
        if ($carrier) {
            $data["envio_codigo_destino"] = $carrier;
            $data["envio_idtransportista"] = 100000283;
            $data["envio_provincia"] = '';
        }

        //Buscamos por el pedido si es una tarjeta de regalo
        $tarjeta = Db::getInstance()->getValue("select product_id from aalv_order_detail aod where id_order = " . $order->id . " and product_name LIKE '% (TARJETA:%'");

        if ($tarjeta) {
            $data["envio_idtransportista"] = 100000304;
        }

        //$pago_forma_pago = 7;
        //if ($order->module=="caixabankconsumerfinance") $pago_forma_pago = 11;
        //if ($order->module=="ps_cashondelivery") $pago_forma_pago = 1;
        //if ($order->module=="ps_wirepayment") $pago_forma_pago = 3;
        //if ($order->module=="ceca") $pago_forma_pago = 7;
        //if ($order->module=="paypal") $pago_forma_pago = 10;

        $pago_forma_pago = self::forma_pago($order->module, $idpedido);


        //if ($pago_forma_pago == 7){
        if ($pago_forma_pago == self::PAYMENT_CREDITCARD || $pago_forma_pago == self::PAYMENT_BIZUM || $pago_forma_pago == self::PAYMENT_REDSYS || $pago_forma_pago == self::PAYMENT_GOOGLE || $pago_forma_pago == self::PAYMENT_APPLE) {

            $transid = "" . Db::getInstance()->getValue("SELECT transaction_id FROM aalv_order_payment WHERE order_reference='" . $order->reference . "'");
            $fechatrans = "" . Db::getInstance()->getValue("SELECT date_add FROM aalv_order_payment WHERE order_reference='" . $order->reference . "'");
            $data["pago_codigo_autorizacion"] = strtoupper($transid);

            if ($fechatrans != "") {
                $data["pago_fecha_autorizacion"] = str_replace(" ", "T", $fechatrans);
            }
        }


        //if ($pago_forma_pago == 10){
        if ($pago_forma_pago == self::PAYMENT_PAYPAL) {

            /*
               pago_paypal_authorization_id: YA NO SE ENVÍA ESTE CAMPO.
               pago_fecha_pago: fecha del pago/pedido.
               pago_fecha_autorizacion: fecha del pago/pedido.
               pago_codigo_autorizacion: ID transacción PayPal SIN ENCRIPTAR.
            */
            $data["pago_fecha_pago"] = str_replace(" ", "T", $order->date_add);
            $data["pago_fecha_autorizacion"] = str_replace(" ", "T", $order->date_add);
            //$transid = "".Db::getInstance()->getValue("SELECT transaction_id FROM aalv_order_payment WHERE order_reference='".$order->reference."'");
            $transid = "" . Db::getInstance()->getValue("SELECT id_transaction FROM aalv_paypal_order WHERE id_order=" . $order->id);
            $data["pago_codigo_autorizacion"] = strtoupper($transid);
        }

        if ($pago_forma_pago == self::PAYMENT_SEQURA) {
            $data["pago_fecha_pago"] = str_replace(" ", "T", $order->date_add);
            $data["pago_fecha_autorizacion"] = str_replace(" ", "T", $order->date_add);
            $transaction_id = "" . Db::getInstance()->getValue("SELECT transaction_id FROM aalv_order_payment aop WHERE order_reference = '" . $order->reference . "'");
            $data["pago_codigo_autorizacion"] = strtoupper($transaction_id);
        }

        if (
            $pago_forma_pago == self::PAYMENT_MULTIBANCO || $pago_forma_pago == self::PAYMENT_MBWAY
            || $pago_forma_pago == self::PAYMENT_VISA || $pago_forma_pago == self::PAYMENT_MASTERCARD
            || $pago_forma_pago == self::PAYMENT_CARTE_BANCAIRE || $pago_forma_pago == self::PAYMENT_MAESTRO
            || $pago_forma_pago == self::PAYMENT_BANCONTACT || $pago_forma_pago == self::PAYMENT_AMERICAN_EXPRESS
            || $pago_forma_pago == self::PAYMENT_MYBANK
        ) {
            $data["pago_fecha_pago"] = str_replace(" ", "T", $order->date_add);
            $data["pago_fecha_autorizacion"] = str_replace(" ", "T", $order->date_add);
            $transid = "" . Db::getInstance()->getValue("SELECT transaction_ref FROM aalv_hipay_transaction WHERE  state = 'completed' AND message in ('Captured','Authorized') AND order_id =" . $order->id);
            $data["pago_codigo_autorizacion"] = strtoupper($transid);
        }

        if ($pago_forma_pago == self::PAYMENT_KLARNA) {
            $data["pago_fecha_pago"] = str_replace(" ", "T", $order->date_add);
            $data["pago_fecha_autorizacion"] = str_replace(" ", "T", $order->date_add);
            $transaction_id = "" . Db::getInstance()->getValue("SELECT klarna_reference FROM aalv_klarna_payment_orders aop WHERE id_internal = " . $order->id);
            $data["pago_codigo_autorizacion"] = strtoupper($transaction_id);
        }

        //añadir las que queden cuando sepamos los módulos
        // tarjeta 7 Bizum 8 Paypal 10 Financiado manual y Tarjeta VIP 4 Pago aplazado 6 Tarjeta no securizada 2


        if ($customer->email == 'reservafitting@alsernet.es') {
            $pago_forma_pago = 24;
            $data["pago_fecha_pago"] = str_replace(" ", "T", $order->date_add);
            // $data["pago_fecha_autorizacion"] = str_replace(" ", "T", $order->date_add);
            // $data["pago_codigo_autorizacion"] = strtoupper($order->id);
        }

        if ($pago_forma_pago == self::PAYMENT_BAN_LENDISMART) {
            $data["pago_codigo_autorizacion"] = Db::getInstance()->getValue("SELECT transaction_id FROM aalv_order_payment WHERE order_reference='" . $order->reference . "'");
        }

        if ($pago_forma_pago == self::PAYMENT_REVER){
            $data["idorigenpedidocli"] = 6;
        }

        $data["pago_forma_pago"] = $pago_forma_pago;
        $data["pago_importe"] = $order->total_paid_tax_incl;


        // 20237021 - Diferencia si es un pedido "habitual" o de Lotería
        // $data["xml_lineas_pedido"] = self::getxmllineas($order);
        $isLotteryOrderId = Db::getInstance()->getValue("SELECT o.id_order
                                                            FROM aalv_configuration cf
                                                            INNER JOIN aalv_feature_product f ON (cf.value = f.id_feature_value)
                                                            INNER JOIN aalv_order_detail od ON (f.id_product = od.product_id)
                                                            INNER JOIN aalv_orders o ON (od.id_order = o.id_order)
                                                            WHERE cf.name = '" . _PSALV_CONFIG_PRODUCTTYPE_LOTTERY_ . "' AND o.id_order=" . $order->id . "
                                                            GROUP BY o.id_order");

        if (!empty($isLotteryOrderId)) { // Es un pedido de Lotería
            $data["prioridad"] = 3;
            $data["xml_lineas_loteria"] = self::getXmlLinesLottery($order->id);
        } else { // Es un pedido "común"
            $xml_lineas = self::getxmllineas($order,$data);
            $data["prioridad"] = $xml_lineas["prioridad"];
            $data["xml_lineas_pedido"] = $xml_lineas["xml"];
        }


        foreach ($data as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }
        $fields_string = rtrim($fields_string, '&');


        return $fields_string;
    }


    public static function isMobilePhone($num, $idCountry)
    {
        $return = false;
        if (empty($num)) return (int)$return;

        $num = str_replace(' ', '', $num);
        $isES = in_array($idCountry, [_PSALV_COUNTRY_ID_ES_PENINSULA_, _PSALV_COUNTRY_ID_ES_BALEARES_, _PSALV_COUNTRY_ID_ES_CANARIAS_, _PSALV_COUNTRY_ID_ES_CEUTA_]);
        $isPT = in_array($idCountry, [_PSALV_COUNTRY_ID_PT_PENINSULA_, _PSALV_COUNTRY_ID_PT_AZORES_]);

        // Mirar el prefijo
        if (substr(trim($num), 0, 4) === "+351") {
            $isPT = true;
        }

        // Recuperar el primer dígito del teléfono
        $firstNum = substr(trim($num), 0, 1);
        $lastNum = substr(trim($num), -9, 1);

        // Comprobar según zona
        if ($isES) {
            $return = $firstNum == "6" || $firstNum == "7" || $lastNum == "6" || $lastNum == "7";
        } elseif ($isPT) {
            $return = $firstNum == "9" || $lastNum == "9";
        }

        return (int)$return;
    }


    public static function mandarpedido($idpedido, $idclientegestion)
    {


        $data = self::construirdatospedido($idpedido, $idclientegestion);
        $resp = self::peticionpost(self::URL_ERP . "pedido-cliente/", $data, 'mandarpedido');

        return $resp;
        //return '';

    }


    public function forma_pago($module, $idpedido)
    {
        switch ($module) {
            case 'ps_cashondelivery':
                return self::PAYMENT_CASHONDELIVERY;
                break;
            case 'ps_wirepayment':
                return self::PAYMENT_WIRE;
                break;
            case 'ceca':
                $ceca_tpv = Db::getInstance()->getValue("SELECT id_ceca_tpv from aalv_ceca_transaction where id_order = " . $idpedido);
                if ($ceca_tpv == self::PAYMENT_BIZUM_TPV) {
                    return self::PAYMENT_BIZUM;
                } else {
                    return self::PAYMENT_CREDITCARD;
                }
                break;

            case 'redsys':
                $redsys_tpv = Db::getInstance()->getValue("SELECT id_tpv from aalv_redsys_transaction where id_order = " . $idpedido);

                if ($redsys_tpv == self::PAYMENT_GOOGLE_TPV) {
                    return self::PAYMENT_GOOGLE;
                }
                if ($redsys_tpv == self::PAYMENT_APPLE_TPV) {
                    return self::PAYMENT_APPLE;
                } else {
                    return self::PAYMENT_REDSYS;
                }
                break;

            case 'paypal':
                return self::PAYMENT_PAYPAL;
                break;
            case 'caixabankconsumerfinance':
                return self::PAYMENT_FINANCE;
                break;
            case 'sequra':
                return self::PAYMENT_SEQURA;
                break;
            case 'alsernetfinance':
                return self::PAYMENT_ALSERNETFINANCE;
                break;
            case 'inespay':
                return self::PAYMENT_TRANSFERENCIA_ONLINE;
                break;
            case 'banlendismart':
                return self::PAYMENT_BAN_LENDISMART;
                break;
            case 'klarnapayment':
                return self::PAYMENT_KLARNA;
                break;
            case 'hipay_enterprise':
                $hipay_tpv = Db::getInstance()->getValue("SELECT payment_product  from aalv_hipay_transaction WHERE  state = 'completed' AND message in ('Captured','Authorized') AND order_id = " . $idpedido);
                if ($hipay_tpv == 'mbway') {
                    return self::PAYMENT_MBWAY;
                }
                if ($hipay_tpv == 'multibanco') {
                    return self::PAYMENT_MULTIBANCO;
                }
                if ($hipay_tpv == 'visa') {
                    return self::PAYMENT_VISA;
                }
                if ($hipay_tpv == 'mastercard') {
                    return self::PAYMENT_MASTERCARD;
                }
                if ($hipay_tpv == 'cb') {
                    return self::PAYMENT_CARTE_BANCAIRE;
                }
                if ($hipay_tpv == 'maestro') {
                    return self::PAYMENT_MAESTRO;
                }
                if ($hipay_tpv == 'bancontact') {
                    return self::PAYMENT_BANCONTACT;
                }
                if ($hipay_tpv == 'american-express') {
                    return self::PAYMENT_AMERICAN_EXPRESS;
                }
                if ($hipay_tpv == 'mybank') {
                    return self::PAYMENT_MYBANK;
                }

                break;
            case 'rever':
                return self::PAYMENT_REVER;
            default:
                return -1;
                break;
        }
    }


    public function transportistaGestion($addresdelivery, $order, $textocorreos)
    {

        $array_europa_seur = explode(',', Configuration::get('ALSERNET_TRANSPORTISTA_EUROPA_SI_SEUR'));

        $array_europa_no_seur = explode(',', Configuration::get('ALSERNET_TRANSPORTISTA_EUROPA_NO_SEUR'));

        $idtransportista = '';


        /* España NO peninsular
        * España - Baleares => Id pais 224
        * España - Canarias => Id pais 243
        * España - Ceuta y Melilla => Id pais 242
        */
        // if (in_array($addresdelivery->id_country, [244, 243, 242])) {
        //     $idtransportista = Configuration::get('ALSERNET_TRANSPORTISTA_ESPANIA_NO_PENINSULAR');
        // }

        // CASO 1: CANARIAS - ahora va solo
        if (in_array($addresdelivery->id_country, [243])) {
            $idtransportista = 21;//Configuration::get('ALSERNET_TRANSPORTISTA_SOLO_CANARIAS');
        }

        // CASO 2: Otras zonas NO peninsulares (Ceuta, Melilla, Baleares)
        if (in_array($addresdelivery->id_country, [244, 242])) {
            $idtransportista = Configuration::get('ALSERNET_TRANSPORTISTA_ESPANIA_NO_PENINSULAR');
        }

        /* Metodo de pago contra rembolso
        */
        if ($order->module != 'ps_cashondelivery') {

            /*ENVIOS A ESPAÑA Y PORTUGAL*/
            if (in_array($addresdelivery->id_country, [6, 15, 244, 243, 242, 245])) {

                /* GALICIA
                * La coruña => Inicio codigo postal 15
                * Lugo => Inicio codigo postal 24
                * Pontevedra => Inicio codigo postal 36
                * Ourence => Inicio codigo postal 32
                */
                if (in_array($addresdelivery->id_country, [6]) && preg_match('/^(15|36|32|27)/', $addresdelivery->postcode)) {
                    $idtransportista = Configuration::get('ALSERNET_TRANSPORTISTA_ESPANIA_GALICIA');
                }

                if (in_array($addresdelivery->id_country, [6]) && preg_match('/^(07|52|51)/', $addresdelivery->postcode)) {
                    $idtransportista = 2;
                }

                if (in_array($addresdelivery->id_country, [6]) && preg_match('/^(33|39|48|01|20)/', $addresdelivery->postcode)) {
                    $idtransportista = 100000045;
                }

                if (in_array($addresdelivery->id_country, [6]) && preg_match('/^(47|11|03)/', $addresdelivery->postcode)) {
                    $idtransportista = 21;
                } /* PENÍNSULA Y PORTUGAL
                * España => Id pais 6
                * Portugal => 15
                */
                elseif (in_array($addresdelivery->id_country, [6, 15]) && !preg_match('/^(15|36|32|27)/', $addresdelivery->postcode)) {
                    $idtransportista = Configuration::get('ALSERNET_TRANSPORTISTA_ESPANIA_Y_PORTUGAL');
                } /*RESTO DE ESPAÑA*/
                elseif($idtransportista == '') {
                    $idtransportista = Configuration::get('ALSERNET_TRANSPORTISTA_RESTO_DE_ESPANIA');
                }


            } /*ENVIOS A EUROPA SI SEUR*/
            elseif (in_array($addresdelivery->id_country, $array_europa_seur)) {
                $idtransportista = Configuration::get('ALSERNET_TRANSPORTISTA_EUROPA_SI_SEUR_ID');
            } /*ENVIOS A EUROPA NO SEUR*/
            elseif (in_array($addresdelivery->id_country, $array_europa_no_seur)) {
                $idtransportista = Configuration::get('ALSERNET_TRANSPORTISTA_EUROPA_NO_SEUR_ID');
            } /*RESTO DEL MUNDO*/
            else {
                $idtransportista = Configuration::get('ALSERNET_TRANSPORTISTA_RESTO_DEL_MUNDO');
            }

            /* Mondial Relay / InPost
            *
            */
            $inpost = Db::getInstance()->executeS("SELECT * FROM aalv_mondialrelay_selected_relay WHERE id_order = " . $order->id);
            if (count($inpost) > 0) {
                $idtransportista = Configuration::get('ALSERNET_TRANSPORTISTA_ES_INPOST');
            }

            /* Validar el tipo de producto, con el siguiente orden
            * Armas
            * Cartuchos
            * Tarjetas de regalos
            */
            foreach ($order->getProducts() as $producto_pedido) {
                // $product = Product::getProductProperties(1, [
                //     'id_product' => $producto_pedido['product_id']
                // ]);

                // if ($product['armas']) {
                //     $idtransportista = Configuration::get('ALSERNET_TRANSPORTISTA_ES_ARMA');
                // } elseif ($product['cartucho']) {
                //     $idtransportista = Configuration::get('ALSERNET_TRANSPORTISTA_ES_CARTUCHO');
                // } elseif ($product['card']) {
                //     $idtransportista = Configuration::get('ALSERNET_TRANSPORTISTA_ES_TARJETAS_REGALO');
                // }
                $product = new Product($producto_pedido['product_id']);

                if (Product::hasFeature($producto_pedido['product_id'], "Armas")) {
                    $idtransportista = Configuration::get('ALSERNET_TRANSPORTISTA_ES_ARMA');
                } elseif (Product::hasFeature($producto_pedido['product_id'], "Cartuchos")) {
                    $idtransportista = Configuration::get('ALSERNET_TRANSPORTISTA_ES_CARTUCHO');
                } elseif (Product::hasFeature($producto_pedido['product_id'], "Tarjeta regalo")) {
                    $idtransportista = Configuration::get('ALSERNET_TRANSPORTISTA_ES_TARJETAS_REGALO');
                } elseif (Product::hasFeature($producto_pedido['product_id'], "Armero")) {
                    $idtransportista = 17;
                } elseif (Product::hasFeature($producto_pedido['product_id'], "Licencia")) {
                    $idtransportista = 10;
                } elseif (in_array($addresdelivery->id_country, [244, 242]) && self::isArmasBalines($product->id_category_default)) {
                    $idtransportista = 21;
                } elseif (Product::hasFeature($producto_pedido['product_id'], "Remolques")) {
                    $idtransportista = 17;
                }
            }

            if ($textocorreos != '') {
                $idtransportista = Configuration::get('ALSERNET_TRANSPORTISTA_CORREOSEXPRESS');
            }

        } else {
            /* PENÍNSULA Y PORTUGAL
            * España => Id pais 6
            * Portugal => 15
            */
            if (in_array($addresdelivery->id_country, [6, 15])) {
                $idtransportista = Configuration::get('ALSERNET_TRANSPORTISTA_ESPANIA_Y_PORTUGAL_CONTRA_REEMBOLSO');
            }
        }

        return $idtransportista;
    }

    public function isArmasBalines($id_category_default)
    {
        $categoryDefault = new Category($id_category_default);
        $category_list = [(int)$categoryDefault->id];
        $category_armas_balines_ids = [176, 180, 213, 239, 1449, 1452, 1457, 1458, 1459];

        foreach ($categoryDefault->getAllParents() as $category) {

            if ($category->id_parent != 0 && !$category->is_root_category && $category->active) {
                $category_list[] = (int)$category->id;
            }
        }

        $interseccion = array_intersect($category_list, $category_armas_balines_ids);
        if (!empty($interseccion)) {
            return true;
        }

        return false;
    }


}

<?php

namespace App\Http\Controllers\Api;

use App\Services\ErpService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ErpController extends Controller
{
    protected $erpService;

    public function __construct(ErpService $erpService)
    {
        $this->erpService = $erpService;
    }

    // 1. Recupera cliente ERP por idweb
    public function recuperarclienteerp(Request $request)
    {
        $idweb = $request->get('idweb');
        $result = $this->erpService->get('cliente/', ['idclienteweb' => $idweb]);
        return response()->json(['result' => $result]);
    }

    // 2. Recupera id de cliente ERP a partir de idweb
    public function recuperaridclienteerp(Request $request)
    {
        $idweb = $request->get('idweb');
        $result = $this->erpService->get('cliente/', ['idclienteweb' => $idweb]);
        // Se debe procesar el XML para extraer el id, aquí se ejemplifica:
        if ($result && $result !== "Not Found") {
            $xml = simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA);
            $array = json_decode(json_encode($xml), true);
            $idcliente = isset($array['idcliente']) ? $array['idcliente'] : '';
        } else {
            $idcliente = '';
        }
        return response()->json(['result' => $idcliente]);
    }

    // 3. Recupera los pedidos de un cliente (por idweb)
    public function recuperarpedidoscliente(Request $request)
    {
        $idweb = $request->get('idweb');
        // Primero se obtiene el id de cliente
        $idcliente = $this->obtenerIdCliente($idweb);
        if ($idcliente) {
            $result = $this->erpService->get('pedido-cliente/', ['idcliente' => $idcliente]);
        } else {
            $result = false;
        }
        return response()->json(['result' => $result]);
    }

    // 4. Recupera un pedido (por número de pedido cliente y serie)
    public function recuperarpedido(Request $request)
    {
        $npedidocli = $request->get('npedidocli');
        $serie = $request->get('serie');
        $result = $this->erpService->get('pedido-cliente/', ['serie' => $serie, 'npedidocli' => $npedidocli]);
        return response()->json(['result' => $result]);
    }

    // 5. Recupera un pedido por su identificador de origen
    public function recuperarpedidoporid(Request $request)
    {
        $identificadororigen = $request->get('identificadororigen');
        $result = $this->erpService->get('pedido-cliente/', ['identificadororigen' => $identificadororigen]);
        return response()->json(['result' => $result]);
    }

    // 6. Recupera cliente ERP para Alsernet (por email/idweb)
    public function recuperarclienteerpAlsernet(Request $request)
    {
        $idweb = $request->get('idweb');
        $result = $this->erpService->get('cliente/', ['email' => $idweb]);
        // Se considera que si se devuelve algo distinto de "Not Found" es verdadero
        $res = ($result && $result !== "Not Found") ? true : false;
        return response()->json(['result' => $res]);
    }

    // 7. Recupera datos de cliente ERP a partir de DNI, apellidos, email y teléfono
    public function recuperardatosclienteerp(Request $request)
    {
        $dni       = $request->get('dni');
        $apellidos = $request->get('apellidos');
        $email     = $request->get('email');
        $telefono  = $request->get('telefono');
        $result = $this->erpService->get('cliente/', [
            'dni' => $dni,
            'apellidos' => $apellidos,
            'email' => $email,
            'telefono1' => $telefono
        ]);
        return response()->json(['result' => $result]);
    }

    // 8. Recupera datos de cliente ERP por idweb
    public function recuperardatosclienteerpporidweb(Request $request)
    {
        $idweb = $request->get('idweb');
        $result = $this->erpService->get('cliente/', ['idclienteweb' => $idweb]);
        return response()->json(['result' => $result]);
    }

    // 9. Recupera datos de cliente ERP por id de gestión
    public function recuperardatosclienteerpporidgestion(Request $request)
    {
        $idgestion = $request->get('idgestion');
        $result = $this->erpService->get('cliente/', ['idcliente_gestion' => $idgestion]);
        return response()->json(['result' => $result]);
    }

    // 10. Obtiene el idioma de gestión según id_lang_ps
    public function getIdiomaGestion(Request $request)
    {
        $id_lang_ps = $request->get('id_lang_ps');
        // La lógica puede usarse directamente desde el servicio o delegarse a otra clase
        $result = $this->erpService->getIdiomaGestion($id_lang_ps);
        return response()->json(['result' => $result]);
    }

    // 11. Obtiene el país de gestión según id_lang_ps
    public function getPaisGestion(Request $request)
    {
        $id_lang_ps = $request->get('id_lang_ps');
        $result = $this->erpService->getPaisGestion($id_lang_ps);
        return response()->json(['result' => $result]);
    }

    // 12. Guarda datos de cliente ERP (se envían por POST)
    public function guardardatosclienteerp(Request $request)
    {
        $params = $request->all();
        // Se invoca el método con todos los parámetros en orden
        $result = call_user_func_array([$this->erpService, 'guardardatosclienteerp'], array_values($params));
        return response()->json(['result' => $result]);
    }

    // 13. Recupera catálogos de cliente ERP por id de gestión
    public function recuperarcatalogosclienteerp(Request $request)
    {
        $idcliente_gestion = $request->get('idcliente_gestion');
        $result = $this->erpService->get('clientecatalogo/', ['idcliente_gestion' => $idcliente_gestion]);
        return response()->json(['result' => $result]);
    }

    // 14. Suscribe catálogos por e-mail a ERP
    public function suscribircatalogosporeamilerp(Request $request)
    {
        $cliente_email = $request->get('cliente_email');
        $cliente_idcatalogo = $request->get('cliente_idcatalogo');
        $data = "cliente_email=" . $cliente_email . "&cliente_idcatalogo=" . $cliente_idcatalogo;
        $result = $this->erpService->post('clientecatalogo/', ['data' => $data]);
        return response()->json(['result' => $result]);
    }

    // 15. Elimina la suscripción a catálogos por e-mail
    public function delsuscribircatalogosporeamilerp(Request $request)
    {
        $cliente_email = $request->get('cliente_email');
        $cliente_idcatalogo = $request->get('cliente_idcatalogo');
        $data = "cliente_email=" . $cliente_email . "&cliente_idcatalogo=" . $cliente_idcatalogo;
        $endpoint = 'clientecatalogo/?' . $data;
        $result = $this->erpService->delete($endpoint);
        return response()->json(['result' => $result]);
    }

    // 16. Guarda la aceptación de LOPD
    public function savelopd(Request $request)
    {
        $email = $request->get('email');
        $fecha = $request->get('fecha');
        $no_info_comercial = $request->get('no_info_comercial');
        $no_datos_a_terceros = $request->get('no_datos_a_terceros');
        $data = http_build_query([
            'cliente_email' => $email,
            'cliente_faceptacion_lopd' => $fecha,
            'cliente_no_info_comercial' => $no_info_comercial,
            'cliente_no_datos_a_terceros' => $no_datos_a_terceros
        ]);
        $result = $this->erpService->put('cliente/', ['data' => $data]);
        return response()->json(['result' => $result]);
    }

    // 17. Recupera el stock central de un artículo
    public function recuperarstockcentral(Request $request)
    {
        $idarticulo = $request->get('idarticulo');
        $result = $this->erpService->get('stock-central-web/' . $idarticulo . '/');
        return response()->json(['result' => $result]);
    }

    // 18. Recupera el id de un artículo a partir de su código
    public function recuperaridarticulo(Request $request)
    {
        $codigo = $request->get('codigo');
        $result = $this->erpService->get('articulo/' . $codigo . '/');
        return response()->json(['result' => $result]);
    }

    // 19. Consulta un bono
    public function consultabono(Request $request)
    {
        $idbono = $request->get('idbono');
        $codigo_verificacion = $request->get('codigo_verificacion');
        $importe_venta = $request->get('importe_venta');
        $origen = $request->get('origen');
        $result = $this->erpService->get('bono/' . $idbono . '/', [
            'codigo_verificacion' => $codigo_verificacion,
            'importe_venta' => $importe_venta,
            'origen' => $origen
        ]);
        // Se procesa la respuesta XML si es necesario:
        if (substr($result, 0, 5) === '<?xml') {
            $xml = simplexml_load_string($result, "SimpleXMLElement", LIBXML_NOCDATA);
            $result = [
                'success' => true,
                'data' => json_decode(json_encode($xml), true)
            ];
        } else {
            $result = [
                'success' => false,
                'message' => $result
            ];
        }
        return response()->json(['result' => $result]);
    }

    // 20. Marca un bono (por ejemplo, anular, recargar o consumir)
    public function marcarbono(Request $request)
    {
        $idbono = $request->get('idbono');
        $operacion = $request->get('operacion');
        $codigo_verificacion = $request->get('codigo_verificacion');
        $importe_venta = $request->get('importe_venta');
        $importe_inicial_tarjeta_regalo = $request->get('importe_inicial_tarjeta_regalo');
        $origen = $request->get('origen');
        $data = "operacion=" . $operacion . "&codigo_verificacion=" . $codigo_verificacion . "&importe_venta=" . $importe_venta . "&importe_inicial_tarjeta_regalo=" . $importe_inicial_tarjeta_regalo;
        $result = $this->erpService->put('bono/' . $idbono . '/?origen=' . $origen, ['data' => $data]);
        return response()->json(['result' => $result]);
    }

    // 21. Consulta un vale de compra
    public function consultavalecompra(Request $request)
    {
        $idvale = $request->get('idvale');
        $result = $this->erpService->get('vale/' . $idvale . '/');
        return response()->json(['result' => $result]);
    }

    // 22. Actualiza un vale de compra
    public function actualizarvalecompra(Request $request)
    {
        $idvale = $request->get('idvale');
        $operacion = $request->get('operacion');
        $motivo = $request->get('motivo');
        $data = "operacion=" . $operacion . "&motivo=" . $motivo;
        $result = $this->erpService->put('vale/' . $idvale . '/', ['data' => $data]);
        return response()->json(['result' => $result]);
    }

    // 23. Crea un vale de compra
    public function crearvalecompra(Request $request)
    {
        $importe = $request->get('importe');
        $tipo = $request->get('tipo');
        $idalmacen = $request->get('idalmacen');
        $idcliente = $request->get('idcliente');
        $observaciones = $request->get('observaciones');
        $tiene_codigo_comprobacion = $request->get('tiene_codigo_comprobacion');
        $id_vale_original = $request->get('id_vale_original');
        $id_vale_anterior = $request->get('id_vale_anterior');
        $data = "importe=" . $importe . "&tipo=" . $tipo . "&idalmacen=" . $idalmacen . "&idcliente=" . $idcliente . "&observaciones=" . $observaciones . "&tiene_codigo_comprobacion=" . $tiene_codigo_comprobacion . "&id_vale_original=" . $id_vale_original . "&id_vale_anterior=" . $id_vale_anterior;
        $result = $this->erpService->post('vale/', ['data' => $data]);
        return response()->json(['result' => $result]);
    }

    // 24. Verifica si el cliente tiene tarifa plana
    public function tienetarifaplana(Request $request)
    {
        $idweb = $request->get('idweb');
        $result = $this->erpService->get('cliente/', ['idclienteweb' => $idweb]);
        // En este ejemplo se retorna false, ya que la funcionalidad está desactivada.
        return response()->json(['result' => false]);
    }

    // 25. Convierte una cadena para enviarla a gestión
    public function toGestion(Request $request)
    {
        $cadena = $request->get('cadena');
        $result = $this->erpService->toGestion($cadena);
        return response()->json(['result' => $result]);
    }

    // 26. Construye los datos de un pedido
    public function construirdatospedido(Request $request)
    {
        $idpedido = $request->get('idpedido');
        $idclientegestion = $request->get('idclientegestion');
        $result = $this->erpService->construirdatospedido($idpedido, $idclientegestion);
        return response()->json(['result' => $result]);
    }

    // 27. Determina si un número es teléfono móvil
    public function isMobilePhone(Request $request)
    {
        $num = $request->get('num');
        $idCountry = $request->get('idCountry');
        $result = $this->erpService->isMobilePhone($num, $idCountry);
        return response()->json(['result' => $result]);
    }

    // 28. Envía un pedido al ERP
    public function mandarpedido(Request $request)
    {
        $idpedido = $request->get('idpedido');
        $idclientegestion = $request->get('idclientegestion');
        $result = $this->erpService->mandarpedido($idpedido, $idclientegestion);
        return response()->json(['result' => $result]);
    }

    // 29. Determina la forma de pago según el módulo y pedido
    public function forma_pago(Request $request)
    {
        $module = $request->get('module');
        $idpedido = $request->get('idpedido');
        $result = $this->erpService->forma_pago($module, $idpedido);
        return response()->json(['result' => $result]);
    }

    // Método auxiliar para obtener id de cliente (ejemplo simple)
    protected function obtenerIdCliente($idweb)
    {
        $data = $this->erpService->get('cliente/', ['idclienteweb' => $idweb]);
        if ($data && $data !== "Not Found") {
            $xml = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
            $array = json_decode(json_encode($xml), true);
            return isset($array['idcliente']) ? $array['idcliente'] : null;
        }
        return null;
    }
}

<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

function peticionpost($url, $data)
{

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $content = curl_exec($ch);
    curl_close($ch);

    return $content;

}

function peticionget($url)
{

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $content = curl_exec($ch);
    curl_close($ch);

    return $content;

}

function mandarpedido($idpedido)
{

    $data = construirdatospedido($idpedido);

    echo '<code>'.$data.'</code>';

    $resp = peticionpost('http://127.0.0.1:58002/api-gestion/pedido-cliente/', $data);

    dump($resp);

}

function getxmllineas($order)
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

    $xml = '<?xml version="1.0" encoding="UTF-8" ?><lineas>';

    $product_list = $order->getOrderDetailList();
    foreach ($product_list as $product) {

        $ref = $product['product_reference'];
        $uni = $product['product_quantity'];
        $pre = $product['unit_price_tax_incl'];

        $xml = $xml.'<linea><referencia>'.$ref.'</referencia><unidades>'.$uni.'</unidades><precio>'.$pre.'</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo>3</idcatalogo></linea>';

    }

    $xml = $xml.'</lineas>';

    return $xml;
}

function construirdatospedido($idpedido)
{

    $order = new Order($idpedido);

    $customer = new Customer($order->id_customer);

    echo $order->id_address_invoice.' '.$order->id_address_invoice.'<br/>';

    $addresinvoice = new Address($order->id_address_invoice);
    $addresdelivery = new Address($order->id_address_invoice);

    $data = [];

    $data['fecha_pedido'] = str_replace(' ', 'T', $order->date_add);
    $data['zona_fiscal'] = 1;
    $data['telefono_contacto'] = $addresdelivery->phone;
    $data['identificador_origen'] = $idpedido;
    if ($order->gift) {
        $data['envoltorio_regalo'] = 1;
        $data['texto_regalo'] = $order->gift_message;
    } else {
        $data['envoltorio_regalo'] = 0;
    }
    $data['solicita_club_mas'] = 0;
    $data['solicita_factura'] = 0;

    $data['cliente_nombre'] = $addresinvoice->firstname;

    if (strpos($addresinvoice->lastname, ' ')) {
        $data['cliente_apellido1'] = substr($addresinvoice->lastname, 0, strpos($addresinvoice->lastname, ' '));
        $data['cliente_apellido2'] = substr($addresinvoice->lastname, 1 + strpos($addresinvoice->lastname, ' '));
    } else {
        $data['cliente_apellido1'] = $addresinvoice->lastname;
    }
    $data['cliente_cif'] = $addresinvoice->vat_number;
    $data['cliente_email'] = $customer->email;
    $data['cliente_codigo_internet'] = $customer->id;

    $data['cliente_calle'] = $addresinvoice->address1;

    $data['cliente_codigopostal'] = $addresinvoice->postcode;
    $data['cliente_poblacion'] = $addresinvoice->city;

    if ($addresinvoice->id_state != 0) {
        $prov = new State($addresinvoice->id_state);
        $data['cliente_provincia'] = $prov->name;
    }
    $pais = new Country($addresinvoice->id_country);
    $data['cliente_pais'] = $pais->name[1];

    $data['cliente_telefono'] = $addresinvoice->phone;
    $data['cliente_zona_fiscal'] = 1;

    if ($customer->birthday != '0000-00-00') {
        $data['cliente_fnacimiento'] = $customer->birthday.'T00:00:00';
    }
    $data['cliente_faceptacion_lopd'] = substr($customer->date_add, 0, 10).'T00:00:00';
    $data['cliente_no_info_comercial'] = 0;
    $data['cliente_no_datos_a_terceros'] = 0;

    $data['prefijo_telefono'] = str_pad($pais->call_prefix, 4, '0', STR_PAD_LEFT); // "0034";

    $data['envio_calle'] = $addresdelivery->address1;
    $data['envio_localidad'] = $addresdelivery->city;
    $data['envio_cp'] = $addresdelivery->postcode;

    if ($addresdelivery->id_state != 0) {
        $provd = new State($addresdelivery->id_state);
        $data['envio_provincia'] = $provd->name;
    }
    $paisd = new Country($addresdelivery->id_country);
    $data['envio_pais'] = $paisd->name[1];

    $data['envio_coste'] = $order->total_shipping_tax_incl;

    $data['envio_destinatario'] = $addresdelivery->firstname.' '.$addresdelivery->lastname;
    $data['envio_telefono'] = $addresdelivery->phone;

    $data['envio_recogida_tienda'] = 0;

    $data['pago_forma_pago'] = 7;
    $data['pago_importe'] = $order->total_paid_tax_incl;

    $data['xml_lineas_pedido'] = getxmllineas($order);

    foreach ($data as $key => $value) {
        $fields_string .= $key.'='.$value.'&';
    }
    $fields_string = rtrim($fields_string, '&');

    return $fields_string;

    // return str_replace("%3A",":",http_build_query($data))."&xml_lineas_pedido=".getxmllineas($order);

    // return str_replace("%3A",":",http_build_query($data));

    // return http_build_query($data);

}

// header('Content-Type: application/xml; charset=utf-8');
echo mandarpedido(Tools::getValue('id'));

<?php

const MAXIMOENVIO = 100;

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';
include _PS_ADMIN_DIR_.'/../init.php';

function haylote($id_order)
{
    $existe = Db::getInstance()->getValue('SELECT count(*) FROM aalv_wk_bundle_order_detail WHERE id_order='.$id_order);

    return $existe > 0;
}

function haynota($id_order)
{
    $existe = Db::getInstance()->getValue('SELECT count(*) FROM aalv_message WHERE id_order='.$id_order.' and private=0');

    return $existe > 0;
}

function hayarma($id_order)
{
    $existe = Db::getInstance()->getValue('SELECT count(*) FROM aalv_feature_product WHERE id_feature=5 and id_feature_value=10100 and id_product in (SELECT product_id FROM aalv_order_detail WHERE id_order='.$id_order.')');

    return $existe > 0;
}

function excedegastoenvio($id_order, $maxshipping)
{
    $existe = Db::getInstance()->getValue('SELECT count(*) FROM aalv_orders WHERE id_order='.$id_order.' and total_shipping>='.$maxshipping);

    return $existe > 0;
}

function referencianoexiste($id_order)
{
    $lineas = Db::getInstance()->ExecuteS('SELECT product_reference FROM aalv_order_detail WHERE id_order='.$id_order);
    foreach ($lineas as $linea) {
        $idart = AlvarezERP::recuperaridarticulo($linea['product_reference']);
        if ($idart == 0) {
            return true;
        }
    }

    return false;
}

function debeBloquearsePedido($id_order, $maxshipping, &$motivo)
{

    if (haylote($id_order)) {
        $motivo = 'Existe lote o licencia (producto bundle)';

        return true;
    }

    if (haynota($id_order)) {
        $motivo = 'Existe nota en el pedido';

        return true;
    }

    if (hayarma($id_order)) {
        $motivo = 'Existe arma en el pedido';

        return true;
    }
    if (excedegastoenvio($id_order, $maxshipping)) {
        $motivo = 'Gasto de envio elevado';

        return true;
    }
    if (referencianoexiste($id_order)) {
        $motivo = 'Al menos una referencia no existe en el ERP';

        return true;
    }

    return false;
}

function enviarpedidoerp($idorder)
{

    $motivo = '';

    $valor = debeBloquearsePedido($idorder, MAXIMOENVIO, $motivo);

    // echo "Pedido: ".$pedido["id_order"]." debeBloquearsePedido: ".$valor." motivo: ".$motivo."<br/>";

    if ($valor) {
        // bloquear: cambiar estado y rellenar tabla bloqueados
        Db::getInstance()->Execute('INSERT INTO aalv_orders_bloqueados(id_order, fecha_bloqueo, motivo, xmlerp) VALUES ('.$pedido['id_order'].",now(),'".$motivo."','')");
        $order = new Order($idorder);
        $order->setCurrentState(28);
    } else {
        // intentar mandar ERP
        $content = AlvarezERP::mandarpedido($idorder);
        // dump($content);
        $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
        $json = json_encode($xml);
        $array = json_decode($json, true);

        // dump($array);

        $idresult = ''.$array[0];

        if ($idresult != '') {

            Db::getInstance()->Execute('INSERT INTO aalv_orders_erp(id_order, fecha_envio, referencia_erp) VALUES ('.$idorder.",now(),'".$idresult."')");
            $order = new Order($idorder);
            $order->setCurrentState(27);

            return true;
        } else {
            $motivo = 'Fallo comunicación ERP';
            Db::getInstance()->Execute('INSERT INTO aalv_orders_bloqueados(id_order, fecha_bloqueo, motivo, xmlerp) VALUES ('.$idorder.",now(),'".$motivo."','".str_replace("'", '"', $content)."')");
            $order = new Order($idorder);
            $order->setCurrentState(28);

        }

    }

    return false;

}

function createclienteerp($idpedido)
{

    // coger id cliente web del pedido
    $custid = Db::getInstance()->getValue('SELECT id_customer FROM aalv_orders WHERE id_order='.$idpedido);
    $addressid = Db::getInstance()->getValue('SELECT id_address_invoice FROM aalv_orders WHERE id_order='.$idpedido);
    $addressshipid = Db::getInstance()->getValue('SELECT id_address_delivery FROM aalv_orders WHERE id_order='.$idpedido);

    $customer = new Customer($custid);
    $addressinvoice = new Address($addressid);
    $addressdelivery = new Address($addressshipid);

    // dump($customer);
    // dump($addressinvoice);

    // $content=AlvarezERP::recuperardatosclienteerpporidweb("335866");
    // dump($content);

    $idioma = $customer->id_lang;

    $idiomages = '2';

    if ($idioma == 1) {
        $idiomages = '2';
    }
    if ($idioma == 2) {
        $idiomages = '6';
    }
    if ($idioma == 3) {
        $idiomages = '2';
    }
    if ($idioma == 4) {
        $idiomages = '7';
    }
    if ($idioma == 5) {
        $idiomages = '2';
    }

    $idiomages = '';

    $birthday = $customer->birthday;

    if ($birthday == '0000-00-00') {
        $birthday = '';
    } else {
        $birthday = $birthday.'T00:00:00';
    }

    $pais = '';
    if (($addressdelivery->id_country == 6) || ($addressdelivery->id_country == 242) || ($addressdelivery->id_country == 243) || ($addressdelivery->id_country == 244)) {
        $pais = 'España';
    } else {

        if (($addressdelivery->id_country == 15) || ($addressdelivery->id_country == 245)) {
            $pais = 'Portugal';
        } else {
            $pais = $addressdelivery->country;

        }
    }

    $provincia = ''.Db::getInstance()->getValue('SELECT name FROM aalv_state WHERE id_state='.$addressdelivery->id_state);

    $idcatalogo = ''.Db::getInstance()->getValue("SELECT ids_alta_baja FROM aalv_susc_newsletter WHERE email='".$customer->email."' and tipo=1 order by id_susc_newsletter DESC");

    $fechalopd = str_replace(' ', 'T', $customer->date_add);

    $content = AlvarezERP::guardardatosclienteerp('', $customer->firstname, $customer->lastname, $addressinvoice->vat_number, $customer->email, '', '', $idiomages, $ustomer->id, $addressdelivery->address1.' '.$addressdelivery->address2, $addressdelivery->postcode, $addressdelivery->city, $provincia, $pais, '', $addressdelivery->phone, '', '', '', '', $birthday, $fechalopd, '0', '0', $idcatalogo, '', '1');

    // echo $idgestion." ". $content." ";

    $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
    $json = json_encode($xml);
    $array = json_decode($json, true);

    $idresult = ''.$array[0];

    if ($idresult != '') {
        // hacer pedido

        if (enviarpedidoerp($idpedido)) {
            return 'Creado cliente y creado el pedido';
        } else {
            return 'Creado cliente pero no se ha podido realizar el pedido';
        }

    } else {
        // no hacer pedido
        return 'No se ha podido crear el cliente';
    }

}

echo createclienteerp(Tools::getValue('id_order'));

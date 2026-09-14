<?php

const MAXIMOIMPORTE = 3000;

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';
// include _PS_ADMIN_DIR_.'/../init.php';

function esportestandard($porte)
{

    $portestandard = [];

    $portestandard[] = 'A';
    $portestandard[] = 'CM1';
    $portestandard[] = 'B';
    $portestandard[] = 'AA';
    $portestandard[] = 'ALEMANIA STANDARD';
    $portestandard[] = 'ALEMANIA  EXPRESS';
    $portestandard[] = 'AUSTRIA STANDARD';
    $portestandard[] = 'AUSTRIA EXPRESS';
    $portestandard[] = 'BELGICA STANDARD';
    $portestandard[] = 'BELGICA EXPRESS';
    $portestandard[] = 'CHIPRE STANDARD';
    $portestandard[] = 'CHIPRE  EXPRESS';
    $portestandard[] = 'DINAMARCA STANDARD';
    $portestandard[] = 'DINAMARCA  EXPRESS';
    $portestandard[] = 'ESLOVAQUIA STANDARD';
    $portestandard[] = 'ESLOVAQUIA  EXPRESS';
    $portestandard[] = 'ESLOVENIA STANDARD';
    $portestandard[] = 'ESLOVENIA  EXPRESS';
    $portestandard[] = 'ESTONIA STANDARD';
    $portestandard[] = 'ESTONIA  EXPRESS';
    $portestandard[] = 'FINLANDIA STANDARD';
    $portestandard[] = 'FINLANDIA  EXPRESS';
    $portestandard[] = 'FRANCIA STANDARD';
    $portestandard[] = 'FRANCIA EXPRESS';
    $portestandard[] = 'GRECIA STANDARD';
    $portestandard[] = 'GRECIA  EXPRESS';
    $portestandard[] = 'HUNGRIA STANDARD';
    $portestandard[] = 'HUNGRIA  EXPRESS';
    $portestandard[] = 'IRLANDA STANDARD';
    $portestandard[] = 'IRLANDA  EXPRESS';
    $portestandard[] = 'ITALIA STANDARD';
    $portestandard[] = 'ITALIA EXPRESS';
    $portestandard[] = 'LETONIA STANDARD';
    $portestandard[] = 'LETONIA  EXPRESS';
    $portestandard[] = 'LITUANIA STANDARD';
    $portestandard[] = 'LITUANIA  EXPRESS';
    $portestandard[] = 'LUXEMBURGO STANDARD';
    $portestandard[] = 'LUXEMBURGO  EXPRESS';
    $portestandard[] = 'MALTA STANDARD';
    $portestandard[] = 'MALTA  EXPRESS';
    $portestandard[] = 'HOLANDA STANDARD';
    $portestandard[] = 'HOLANDA  EXPRESS';
    $portestandard[] = 'POLONIA STANDARD';
    $portestandard[] = 'POLONIA  EXPRESS';
    $portestandard[] = 'REINO UNIDO STANDARD';
    $portestandard[] = 'REINO UNIDO  EXPRESS';
    $portestandard[] = 'REP. CHECA STANDARD';
    $portestandard[] = 'REP. CHECA  EXPRESS';
    $portestandard[] = 'SUECIA STANDARD';
    $portestandard[] = 'SUECIA  EXPRESS';
    $portestandard[] = 'GUERNSEY STANDARD';
    $portestandard[] = 'JERSEY STANDARD';
    $portestandard[] = 'SUIZA STANDARD';
    $portestandard[] = 'SUIZA EXPRESS';
    $portestandard[] = 'RUMANIA STANDARD';
    $portestandard[] = 'RUMANIA EXPRESS';
    $portestandard[] = 'NORUEGA STANDARD';
    $portestandard[] = 'NORUEGA EXPRESS';
    $portestandard[] = 'BULGARIA STANDARD';
    $portestandard[] = 'BULGARIA  EXPRESS';
    $portestandard[] = 'TURQUIA STANDARD';
    $portestandard[] = 'TURQUIA EXPRESS';
    $portestandard[] = 'RUSIA EXPRESS';
    $portestandard[] = 'LIECHTESTEIN EXPRESS';
    $portestandard[] = 'MONACO EXPRESS';
    $portestandard[] = 'ISLANDIA EXPRESS';
    $portestandard[] = 'CROACIA EXPRESS';
    $portestandard[] = 'ALBANIA EXPRESS';
    $portestandard[] = 'BOSNIA EXPRESS';
    $portestandard[] = 'CROACIA EXPRESS';
    $portestandard[] = 'MONTENEGRO EXPRESS';
    $portestandard[] = 'MACEDONIA EXPRESS';
    $portestandard[] = 'SERVIA EXPRESS';
    $portestandard[] = 'BIELORRUSIA EXPRESS';
    $portestandard[] = 'MOLDAVIA EXPRESS';
    $portestandard[] = 'UCRANIA EXPRESS';
    $portestandard[] = 'ARMENIA EXPRESS';
    $portestandard[] = 'GEORGIA EXPRESS';
    $portestandard[] = 'COSTA RICA EXPRESS';
    $portestandard[] = 'EL SALVADOR EXPRESS';
    $portestandard[] = 'GUATEMALA EXPRESS';
    $portestandard[] = 'HONDURAS EXPRESS';
    $portestandard[] = 'NICARAGUA EXPRESS';
    $portestandard[] = 'PANAMA EXPRESS';
    $portestandard[] = 'BRASIL EXPRESS';
    $portestandard[] = 'ARGENTINA EXPRESS';
    $portestandard[] = 'PERU EXPRESS';
    $portestandard[] = 'COLOMBIA EXPRESS';
    $portestandard[] = 'BOLIVIA EXPRESS';
    $portestandard[] = 'VENEZUELA EXPRESS';
    $portestandard[] = 'CHILE EXPRESS';
    $portestandard[] = 'PARAGUAY EXPRESS';
    $portestandard[] = 'ECUADOR EXPRESS';
    $portestandard[] = 'URUGUAY EXPRESS';
    $portestandard[] = 'MEXICO EXPRESS';
    $portestandard[] = 'ESTADOS UNIDOS EXPRESS';
    $portestandard[] = 'CANADA EXPRESS';
    $portestandard[] = 'REP. DOMINICANA EXPRESS';
    $portestandard[] = 'CUBA EXPRESS';
    $portestandard[] = 'PUERTO RICO EXPRESS';
    $portestandard[] = 'JAMAICA EXPRESS';
    $portestandard[] = 'CORCEGA STANDARD';
    $portestandard[] = 'IRLANDA DEL NORTE STANDARD';
    $portestandard[] = 'HONG KONG';
    $portestandard[] = 'INDONESIA EXPRESS';
    $portestandard[] = 'JAPON EXPRESS';
    $portestandard[] = 'SINGAPUR EXPRESS';
    $portestandard[] = 'TAIWAN EXPRESS';
    $portestandard[] = 'ARABIA SAUDI EXPRESS';
    $portestandard[] = 'ARGELIA EXPRESS';
    $portestandard[] = 'EGIPTO EXPRESS';
    $portestandard[] = 'EMIRATOS ARABES UNIDOS EXPRESS';
    $portestandard[] = 'CAMERUN EXPRESS';
    $portestandard[] = 'COSTA DE MARFIL EXPRESS';
    $portestandard[] = 'KUWAIT EXPRESS';
    $portestandard[] = 'MARRUECOS EXPRESS';
    $portestandard[] = 'QATAR EXPRESS';
    $portestandard[] = 'SURAFRICA EXPRESS';
    $portestandard[] = 'TUNEZ EXPRESS';
    $portestandard[] = 'ANGOLA EXPRESS';
    $portestandard[] = 'ARMENIA EXPRESS';
    $portestandard[] = 'AUSTRALIA EXPRESS';
    $portestandard[] = 'CHINA EXPRESS';
    $portestandard[] = 'FILIPINAS EXPRESS';
    $portestandard[] = 'GUINEA EXPRESS';
    $portestandard[] = 'INDIA EXPRESS';
    $portestandard[] = 'ISRAEL EXPRESS';
    $portestandard[] = 'JORDANIA EXPRESS';
    $portestandard[] = 'KENIA EXPRESS';
    $portestandard[] = 'LIBANO EXPRESS';
    $portestandard[] = 'MADAGASCAR EXPRESS';
    $portestandard[] = 'MAURITANIA EXPRESS';
    $portestandard[] = 'NUEVA ZELANDA EXPRESS';
    $portestandard[] = 'SENEGAL EXPRESS';
    $portestandard[] = 'UGANDA EXPRESS';
    $portestandard[] = 'VIETNAM EXPRESS';
    $portestandard[] = 'ZAMBIA EXPRESS';
    $portestandard[] = 'ZIMBAWE EXPRESS';

    return in_array($porte, $portestandard);

}

function haylote($id_order)
{
    $existe = Db::getInstance()->getValue('SELECT count(*) FROM aalv_wk_bundle_order_detail WHERE id_order='.$id_order);

    if ($existe == 0) {
        return false;
    } else {

        // ver las lineas si el producto es bundle
        $bundles = Db::getInstance()->ExecuteS('SELECT id_ps_product FROM aalv_wk_bundle_order_detail WHERE id_order='.$id_order);

        foreach ($bundles as $bundle) {

            $existelote = Db::getInstance()->getValue('SELECT count(*) FROM aalv_feature_product WHERE id_feature=6 and id_feature_value=24178 and id_product='.$bundle['id_ps_product']);
            if ($existelote == 0) {
                return false;
            } else {
                return true;
            }

        }

        return false;

    }

}

function haybundle($id_order)
{
    $existe = Db::getInstance()->getValue('SELECT count(*) FROM aalv_wk_bundle_order_detail WHERE id_order='.$id_order);

    if ($existe == 0) {
        return false;
    } else {

        // ver las lineas si el producto es bundle
        $bundles = Db::getInstance()->ExecuteS('SELECT id_ps_product FROM aalv_wk_bundle_order_detail WHERE id_order='.$id_order);

        foreach ($bundles as $bundle) {

            $existelote = Db::getInstance()->getValue('SELECT count(*) FROM aalv_feature_product WHERE id_feature=6 and id_feature_value=24178 and id_product='.$bundle['id_ps_product']);
            if ($existelote == 0) {
                return true;
            }

        }

        return false;

    }

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

function excedeimporte($id_order, $maximporte)
{
    $existe = Db::getInstance()->getValue('SELECT count(*) FROM aalv_orders WHERE id_order='.$id_order.' and total_paid>='.$maximporte);

    return $existe > 0;
}

function existeporteespecial($id_order)
{

    $codigos = Db::getInstance()->getValue("SELECT ifnull(GROUP_CONCAT(codigo),'') FROM aalv_portes_producto inner join aalv_order_detail on id_product=product_id and id_product_attribute=product_attribute_id and id_order=".$id_order);

    if ($codigos == '') {
        return false;
    } else {

        $arcodigo = explode(',', $codigos);
        foreach ($arcodigo as $cod) {

            if (! esportestandard($cod)) {
                return true;
            }

        }

        return false;
    }

}

function posibleEnviar($id_order, $maximporte, &$motivo)
{

    $motivo = '';
    $breturn = true;

    if (haybundle($id_order)) {
        $motivo = $motivo.'Existe producto bundle que no es lote. ';
        $breturn = false;
    }

    if (haylote($id_order)) {
        $motivo = $motivo.'Existe lote. ';
        $breturn = false;
    }

    if (haynota($id_order)) {
        $motivo = $motivo.'Existe nota en el pedido. ';
        $breturn = false;
    }

    if (hayarma($id_order)) {
        $motivo = $motivo.'Existe arma en el pedido.';
        $breturn = false;
    }

    if (excedeimporte($id_order, $maximporte)) {
        $motivo = $motivo.'Importe elevado.';
        $breturn = false;
    }

    /*
    if (existeporteespecial($id_order)){
        $motivo=$motivo."Producto con porte especial.";
        $breturn=false;
    }
    */

    return $breturn;
}

function rellenartableenviogestion()
{

    $sql = 'SELECT id_order FROM aalv_orders WHERE current_state NOT in (0,6,26,20,21,24,25) and id_order not in (select id_order from aalv_orders_envio_gestion) order by 1';

    $pedidos = Db::getInstance()->ExecuteS($sql);

    foreach ($pedidos as $pedido) {

        $motivo = '';
        $valor = posibleEnviar($pedido['id_order'], MAXIMOIMPORTE, $motivo);

        $posible_enviar = 1;
        if ($valor) {
            $posible_enviar = 1;
        } else {
            $posible_enviar = 0;
        }

        Db::getInstance()->Execute('INSERT INTO aalv_orders_envio_gestion(id_order, posible_enviar, motivo_no_enviar, fecha_envio, error_gestion, id_pedido_gestion, id_usuario_gestion, force_type) VALUES ('.$pedido['id_order'].','.$posible_enviar.",'".$motivo."',null,'','','',0)");

    }

}

function updateclienteweb($idpedido, $idgestion)
{

    // coger id cliente web del pedido
    $custid = Db::getInstance()->getValue('SELECT id_customer FROM aalv_orders WHERE id_order='.$idpedido);
    $addressid = Db::getInstance()->getValue('SELECT id_address_invoice FROM aalv_orders WHERE id_order='.$idpedido);
    $addressshipid = Db::getInstance()->getValue('SELECT id_address_delivery FROM aalv_orders WHERE id_order='.$idpedido);

    $customer = new Customer($custid);
    $addressinvoice = new Address($addressid);
    $addressdelivery = new Address($addressshipid);

    $content = AlvarezERP::recuperardatosclienteerpporidgestion($idgestion);
    $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
    $json = json_encode($xml);
    $array = json_decode($json, true);

    $cif = $array['cif'];
    $nombre = $array['nombre'];
    $apellidos = $array['apellidos'];
    $codigo_internet = $array['codigo_internet'];
    $email = $array['email'];
    $no_informacion_comercial_lopd = $array['no_informacion_comercial_lopd'];
    $no_datos_a_terceros_lopd = $array['no_datos_a_terceros_lopd'];

    $catalogos = $array['cliente_catalogo']['resource'];

    $idcatalogos = [];
    foreach ($catalogos as $catalogo) {
        $idcatalogos[] = $catalogo['idcatalogo'];
    }

    $listacatalogos = implode(',', $idcatalogos);

    $customer->firstname = $nombre;
    $customer->lastname = $apellidos;
    $actualizado = $customer->update();

    return $actualizado;

}

function updateclienteerp($idpedido, $idgestion)
{

    // coger id cliente web del pedido
    $custid = Db::getInstance()->getValue('SELECT id_customer FROM aalv_orders WHERE id_order='.$idpedido);
    $addressid = Db::getInstance()->getValue('SELECT id_address_invoice FROM aalv_orders WHERE id_order='.$idpedido);
    $addressshipid = Db::getInstance()->getValue('SELECT id_address_delivery FROM aalv_orders WHERE id_order='.$idpedido);

    $customer = new Customer($custid);
    $addressinvoice = new Address($addressid);
    $addressdelivery = new Address($addressshipid);

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

    $content = AlvarezERP::guardardatosclienteerp($idgestion, $customer->firstname, $customer->lastname, $addressinvoice->vat_number, $customer->email, '', '', $idiomages, $ustomer->id, $addressdelivery->address1.' '.$addressdelivery->address2, $addressdelivery->postcode, $addressdelivery->city, $provincia, $pais, '', $addressdelivery->phone, '', '', '', '', $birthday, '', '', '', $idcatalogo, '', '0');

    // echo $idgestion." ". $content." ";

    $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
    $json = json_encode($xml);
    $array = json_decode($json, true);

    $idresult = ''.$array[0];

    return $idresult != '';

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

    $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
    $json = json_encode($xml);
    $array = json_decode($json, true);

    $idresult = ''.$array[0];

    return $idresult != '';

}

function enviarerppedidosposibleenviar()
{

    $sql = 'SELECT * FROM aalv_orders_envio_gestion WHERE posible_enviar=1 and fecha_envio is null order by id desc';

    $datos = Db::getInstance()->ExecuteS($sql);

    foreach ($datos as $item) {

        $doorder = true;

        if ($item['force_type'] == 1) {
            // forzar en gestion y enviar pedido
            // $doorder = updateclienteerp($item["id_order"], $item["id_usuario_gestion"]);
        }

        if ($item['force_type'] == 2) {
            // forzar en web y enviar pedido
            $doorder = updateclienteweb($item['id_order'], $item['id_usuario_gestion']);
        }

        if ($item['force_type'] == 3) {
            // forzar creacion y enviar pedido
            // $doorder = createclienteerp($item["id_order"]);
        }

        // if ($doorder) {

        $content = AlvarezERP::mandarpedido($item['id_order'], ''.$item['id_usuario_gestion']);
        // dump($content);
        $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
        $json = json_encode($xml);
        $array = json_decode($json, true);

        $idresult = ''.$array[0];

        if ($idresult != '') {
            Db::getInstance()->Execute('UPDATE aalv_orders_envio_gestion set fecha_envio=now(), id_pedido_gestion='.$idresult.' where id='.$item['id']);
        } else {

            echo $content.'<br/>';

            if ($content != 'Servidor ocupado') {

                $pos = strpos($content, '20451:');
                if ($pos > 0) {

                    $idresult = ''.AlvarezERP::recuperarpedidoporid(Tools::getValue('id'), '')[0]['idpedidocli'];
                    if ($idresult != '') {
                        Db::getInstance()->Execute('UPDATE aalv_orders_envio_gestion set fecha_envio=now(), id_pedido_gestion='.$idresult.' where id='.$item['id']);
                    } else {
                        $content = 'Pedido gestión procesado';
                        Db::getInstance()->Execute("UPDATE aalv_orders_envio_gestion set fecha_envio=now(), error_gestion='".str_replace("'", '"', $content)."' where id=".$item['id']);
                    }

                } else {
                    Db::getInstance()->Execute("UPDATE aalv_orders_envio_gestion set fecha_envio=now(), error_gestion='".str_replace("'", '"', $content)."' where id=".$item['id']);
                }
            }
        }

        // }

    }

}

function iniciocron()
{

    rellenartableenviogestion();

    enviarerppedidosposibleenviar();

}

iniciocron();
echo 'acaba';

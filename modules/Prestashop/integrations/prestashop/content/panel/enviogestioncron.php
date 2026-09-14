<?php

const MAXIMOIMPORTE = 3000;

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';
include _PS_ADMIN_DIR_.'/../init.php';

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

function hayProductoTipoLote($id_order)
{
    $existe = Db::getInstance()->getValue('SELECT count(*) FROM aalv_feature_product WHERE id_feature=5 and id_feature_value=254099 and id_product in (SELECT product_id FROM aalv_order_detail WHERE id_order='.$id_order.')');

    return $existe > 0;
}

function hayProductoBalines($id_order)
{
    $existe = Db::getInstance()->getValue('SELECT count(*) FROM aalv_category_product acp WHERE id_category in (587, 588) AND id_product in (SELECT product_id FROM aalv_order_detail WHERE id_order='.$id_order.')');

    return $existe > 0;
}

function hayarma($id_order)
{
    $existe = Db::getInstance()->getValue('SELECT count(*) FROM aalv_feature_product WHERE id_feature=5 and id_feature_value=10100 and id_product in (SELECT product_id FROM aalv_order_detail WHERE id_order='.$id_order.')');

    return $existe > 0;
}

function hayProductoEnvioGestionManual($id_order)
{
    $existe = Db::getInstance()->getValue('SELECT count(*) FROM aalv_feature_product WHERE id_feature=24 and id_feature_value=270352 and id_product in (SELECT product_id FROM aalv_order_detail WHERE id_order='.$id_order.')');

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

function espaypal($id_order)
{

    $module = ''.Db::getInstance()->getValue('SELECT module FROM aalv_orders WHERE  id_order ='.$id_order);

    if ($module == 'paypal') {
        return true;
    } else {
        return false;
    }

}

function haylicencia($id_order)
{

    $lineas = Db::getInstance()->ExecuteS('SELECT product_id FROM aalv_order_detail WHERE id_order='.$id_order);

    foreach ($lineas as $linea) {

        $existelicencia = Db::getInstance()->getValue('SELECT count(*) FROM aalv_feature_product WHERE id_feature=7 and id_product='.$linea['product_id']);
        if ($existelicencia != 0) {
            return true;
        }

    }

    return false;

}

function hayCartuchosImperator($id_order)
{
    $existe = Db::getInstance()->getValue('SELECT count(*) FROM aalv_order_detail aod WHERE aod.id_order ='.$id_order.' AND aod.product_id in (58,60,61,62,63,64,14518,14520,61293)');

    return $existe > 0;
}

function haydescuento($id_order)
{

    $descuento = ''.Db::getInstance()->getValue('SELECT id_order FROM aalv_order_cart_rule where id_order='.$id_order);

    if ($descuento == (''.$id_order)) {

        $descripcion = ''.Db::getInstance()->getValue('SELECT name FROM aalv_order_cart_rule where id_order='.$id_order);

        if (
            ($descripcion == 'CHEQUE PADRE 2023')
            || ($descripcion == 'Cheque cumpleaños generado desde la web')
            || ($descripcion == 'CHEQUE MADRE 2023')
            || ($descripcion == 'Bono fidelización')
            || ($descripcion == 'OFERTA 3x2 Bolas Wilson')
            || ($descripcion == 'SAN VALENTIN 2025')) {
            return false;
        } else {
            return true;
        }

    } else {
        return false;
    }

}

function hayReferenciasBloqueantes($orderId)
{
    $refToBlock = ['G-REGALO-MG3' => '[SAC8129]: Hay que llamar al cliente para que seleccione el WEDGE de REGALO'];

    $alias = 'listref';
    $listReferences = Db::getInstance()->executeS(
        'SELECT IFNULL(pa.reference, p.reference) AS '.$alias.'
                    FROM aalv_order_detail od
                    INNER JOIN aalv_product AS p ON (od.product_id = p.id_product)
                    LEFT JOIN  aalv_product_attribute AS pa ON (od.product_id = pa.id_product AND od.product_attribute_id = pa.id_product_attribute)
                    WHERE od.id_order = '.$orderId);

    if (! empty($listReferences)) {
        foreach ($listReferences as $ref) {
            // echo $ref['listref'] . "\n";
            if (! empty($refToBlock[$ref[$alias]])) {
                return $refToBlock[$ref[$alias]];
            }
        }
    }

    return '';
}

function hayportugalislas($id_order)
{

    $addressshipid = Db::getInstance()->getValue('SELECT id_address_delivery FROM aalv_orders WHERE id_order='.$id_order);
    $address = new Address($addressshipid);

    if ($address->id_country == 15) {
        if (substr($address->postcode, 0) == '9') {
            return true;
        }
    }

    return false;

}

function hayloteria($id_order)
{
    return Db::getInstance()->getValue('SELECT id_order FROM aalv_order_detail aod WHERE aod.product_id IN (70717,70718) AND aod.id_order = '.$id_order);
}

function tarjetaderegalo($id_order)
{
    return Db::getInstance()->getValue('SELECT id_order FROM aalv_order_detail aod WHERE aod.product_id IN (65732) AND aod.id_order = '.$id_order);
}

function hayCartuchos($id_order)
{
    $existe = Db::getInstance()->getValue('SELECT count(*) FROM aalv_feature_product WHERE id_feature=5 and id_feature_value=10101 and id_product in (SELECT product_id FROM aalv_order_detail WHERE id_order='.$id_order.')');

    return $existe > 0;
}

function haydevolucion($id_order)
{
    $existe = Db::getInstance()->getValue('select count(*) from aalv_orders ors where ors.current_state = 78 and ors.id_order = '.$id_order);

    return $existe > 0;
}

function posibleEnviar($id_order, $maximporte, &$motivo)
{

    $motivo = '';
    $breturn = true;

    if (hayportugalislas($id_order)) {
        $motivo = $motivo.'Envio Portugal peninsular con código postal de islas. ';
        $breturn = false;
    }

    if (haybundle($id_order)) {
        $motivo = $motivo.'Existe producto bundle que no es lote. ';
        $breturn = false;
    }

    if (haylote($id_order)) {
        $motivo = $motivo.'Existe lote. ';
        $breturn = false;
    }

    // if (hayloteria($id_order)) {
    //     $existe_producto_aparte = Db::getInstance()->getValue("SELECT id_order FROM aalv_order_detail aod WHERE aod.product_id NOT IN (65104,65102) AND aod.id_order = " . $id_order);
    //     if ($existe_producto_aparte) {
    //         $motivo = $motivo . "Existe Loteria. ";
    //         $breturn = false;
    //     }
    // }

    // if (haynota($id_order)) {
    //     $motivo = $motivo . "Existe nota en el pedido. ";
    //     $breturn = false;
    // }

    if (hayProductoTipoLote($id_order)) {
        $motivo = $motivo.'Existe un producto tipo lote en el pedido. ';
        $breturn = false;
    }

    // if (hayProductoBalines($id_order)) {
    //     $motivo = $motivo . "Existe un producto de Balines en el pedido. ";
    //     $breturn = false;
    // }

    if (hayarma($id_order)) {
        $motivo = $motivo.'Existe arma en el pedido. ';
        $breturn = false;
    }

    if (hayProductoEnvioGestionManual($id_order)) {
        $motivo = $motivo.'Existe un producto marcado para procesamiento manual en gestión. ';
        $breturn = false;
    }

    if (excedeimporte($id_order, $maximporte)) {
        $motivo = $motivo.'Importe elevado. ';
        $breturn = false;
    }

    if (haylicencia($id_order)) {
        $motivo = $motivo.'Existe licencia. ';
        $breturn = false;
    }

    if (hayCartuchosImperator($id_order)) {
        $motivo = $motivo.'Existe Cartuchos Imperator. Chequear promoción Chaleco ';
        $breturn = false;
    }

    // if (haydescuento($id_order)) {
    //     $motivo = $motivo . "Existe descuento en el pedido. ";
    //     $breturn = false;
    // }

    if (haydevolucion($id_order)) {
        $motivo = $motivo.'Existe Devolucion en el pedido. ';
        $breturn = false;
    }

    // if (hayCartuchos($id_order)) {
    //     $motivo = $motivo . "Existe Cartuchos. ";
    //     $breturn = false;
    // }

    if (tarjetaderegalo($id_order)) {
        $existe_producto_aparte = Db::getInstance()->getValue('SELECT id_order FROM aalv_order_detail aod WHERE aod.product_id NOT IN (65732) AND aod.id_order = '.$id_order);
        if ($existe_producto_aparte) {
            $motivo = $motivo.'Existe Tarjeta de regalo. ';
            $breturn = false;
        }
    }

    $msg = hayReferenciasBloqueantes($id_order);
    if (! empty($msg)) {
        $motivo .= ' '.$msg;
        $breturn = false;
    }

    /*
        if (espaypal($id_order)){
            $motivo=$motivo."Pago por Paypal.";
            $breturn=false;

        }
    */

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

    // $sql = "SELECT id_order FROM aalv_orders WHERE current_state NOT in (0,6,26,20,21,23,24,25,33,34,35,36,37,27,38,5,39,41,55,56,57) and id_order not in (select id_order from aalv_orders_envio_gestion) order by 1";
    $sql = 'SELECT id_order FROM aalv_orders WHERE current_state IN (2,3,9,10,11,22,40,43,45,54,67,68,78) AND id_order NOT IN (SELECT id_order FROM aalv_orders_envio_gestion) ORDER by 1';

    $pedidos = Db::getInstance()->ExecuteS($sql);
    // if(empty($pedidos)){
    //     return FALSE;
    // }
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
    // return TRUE;
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

    $envioSms = AlvarezERP::isMobilePhone($addressinvoice->phone, $addressinvoice->id_country);

    $content = AlvarezERP::guardardatosclienteerp($idgestion, $customer->firstname, $customer->lastname, $addressinvoice->vat_number, $customer->email, '', '', $idiomages, $customer->id, $addressdelivery->address1.' '.$addressdelivery->address2, $addressdelivery->postcode, $addressdelivery->city, $provincia, $pais, '', $addressdelivery->phone, '', $envioSms, '', '', $birthday, '', '', '', $idcatalogo, '', '0');

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

    $envioSms = AlvarezERP::isMobilePhone($addressinvoice->phone, $addressinvoice->id_country);

    $content = AlvarezERP::guardardatosclienteerp('', $customer->firstname, $customer->lastname, $addressinvoice->vat_number, $customer->email, '', '', $idiomages, $customer->id, $addressdelivery->address1.' '.$addressdelivery->address2, $addressdelivery->postcode, $addressdelivery->city, $provincia, $pais, '', $addressdelivery->phone, '', $envioSms, '', '', $birthday, $fechalopd, '0', '0', $idcatalogo, '', '1');

    $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
    $json = json_encode($xml);
    $array = json_decode($json, true);

    $idresult = ''.$array[0];

    return $idresult != '';

}

function enviarerppedidosposibleenviar()
{

    $sql = 'SELECT * FROM aalv_orders_envio_gestion WHERE posible_enviar=1 and fecha_envio is null order by id';

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
        // dump($content);die();
        $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
        $json = json_encode($xml);
        $array = json_decode($json, true);

        $idresult = ''.$array[0];

        if ($idresult != '') {
            Db::getInstance()->Execute('UPDATE aalv_orders_envio_gestion set fecha_envio=now(), id_pedido_gestion='.$idresult.' where id='.$item['id']);

            // 20230906 - Si tenemos el ID el pedido Gestion, se cambia el estado del pedido a "Pedido en ERP"
            Db::getInstance()->executeS('UPDATE aalv_orders SET current_state = '._PSALV_ORDER_STATUS_INERP_.' WHERE id_order = '.$item['id_order'].';');
            Db::getInstance()->executeS('INSERT INTO aalv_order_history (id_employee, id_order, id_order_state, date_add) VALUES ('._PSALV_EMPLOYEE_ERP_.', '.$item['id_order'].', '._PSALV_ORDER_STATUS_INERP_.', now());');

            if (haynota($item['id_order'])) {
                $nota = Db::getInstance()->getValue('SELECT message FROM aalv_message WHERE id_order='.$item['id_order'].' and private=0');
                $dest = [];
                // $dest[] = "alvarez@alsernet.es";
                $dest[] = 'attcliente@a-alvarez.com';
                $dest[] = 'graci@a-alvarez.com';
                $dest[] = 'clientes@a-alvarez.com';
                $data = ['{message}' => $nota];

                Mail::Send(1,
                    'integracion',
                    'Existe Nota - '.$idresult,
                    $data,
                    $dest,
                    Configuration::get('PS_SHOP_NAME'),
                    'desarrollotest@a-alvarez.com',
                    'desarrollotest',
                    [],
                    null,
                    _PS_MAIL_DIR_,
                    false,
                    1
                );
            }

            if (hayloteria($item['id_order'])) {
                $existe_producto_aparte = Db::getInstance()->getValue('SELECT id_order FROM aalv_order_detail aod WHERE aod.product_id NOT IN (70717,70718) AND aod.id_order = '.$item['id_order']);
                if ($existe_producto_aparte) {
                    $dest = [];
                    // $dest[] = "alvarez@alsernet.es";
                    $dest[] = 'mcancela@a-alvarez.com';
                    $dest[] = 'attcliente@a-alvarez.com';

                    $orderns = Db::getInstance()->ExecuteS('select id_order, payment , total_paid, reference  from aalv_orders where id_order = '.$item['id_order']);

                    $transaccion = Db::getInstance()->ExecuteS("SELECT transaction_id as transacction FROM aalv_order_payment WHERE order_reference = '".$orderns[0]['reference']."'
                                                                UNION
                                                                SELECT id_transaction as transacction FROM aalv_paypal_order WHERE id_order = ".$orderns[0]['id_order']."
                                                                UNION
                                                                SELECT transaction_ref as transacction FROM aalv_hipay_transaction WHERE state = 'completed' AND message in ('Captured','Authorized') AND order_id = ".$orderns[0]['id_order'].'
                                                                UNION
                                                                SELECT klarna_reference as transacction FROM aalv_klarna_payment_orders aop WHERE id_internal = '.$orderns[0]['id_order'].'
                                                                ');

                    $mensaje = 'Pago => '.round($orderns[0]['total_paid'], 2).' €<br>';
                    $mensaje .= 'Pago codigo autorizacion => '.$transaccion[0]['transacction'].'<br>';
                    $mensaje .= 'Forma de pago => '.$orderns[0]['payment'];
                    $dataa = ['{message}' => $mensaje];

                    Mail::Send(1,
                        'integracion',
                        'Pedido de Loteria - '.$orderns[0]['id_order'],
                        $dataa,
                        $dest,
                        Configuration::get('PS_SHOP_NAME'),
                        'desarrollotest@a-alvarez.com',
                        'desarrollotest',
                        [],
                        null,
                        _PS_MAIL_DIR_,
                        false,
                        1
                    );
                }
            }
        } else {

            echo $content.'<br/>';

            if ($content != 'Servidor ocupado') {

                $pos = strpos($content, '20451:');
                if ($pos > 0) {

                    //   idpedidocli = 1907675)

                    $pospedcli = strpos($content, 'idpedidocli');
                    if ($pospedcli > 0) {
                        $idpedidocli = substr($content, $pospedcli);

                        $idpedidoclifinal = str_replace('=', '', str_replace(')', '', str_replace(' ', '', str_replace('idpedidocli', '', $idpedidocli))));

                        Db::getInstance()->Execute("UPDATE aalv_orders_envio_gestion set fecha_envio=now(), error_gestion='', id_pedido_gestion=".$idpedidoclifinal.' where id='.$item['id']);

                        // 20230906 - Si tenemos el ID el pedido Gestion, se cambia el estado del pedido a "Pedido en ERP"
                        Db::getInstance()->executeS('UPDATE aalv_orders SET current_state = '._PSALV_ORDER_STATUS_INERP_.' WHERE id_order = '.$item['id_order'].';');
                        Db::getInstance()->executeS('INSERT INTO aalv_order_history (id_employee, id_order, id_order_state, date_add) VALUES ('._PSALV_EMPLOYEE_ERP_.', '.$item['id_order'].', '._PSALV_ORDER_STATUS_INERP_.', now());');
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
    // if(

    rellenartableenviogestion();
    // ){

    enviarerppedidosposibleenviar();
    // }
}

iniciocron();
echo 'acaba';

<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include dirname(__FILE__).'/../config/config.inc.php';
include dirname(__FILE__).'/../init.php';

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

    $idcatalogo = 3;

    $content = AlvarezERP::guardardatosclienteerp('', $customer->firstname, $customer->lastname, $addressinvoice->vat_number, $customer->email, '', '', $idiomages, $ustomer->id, $addressdelivery->address1.' '.$addressdelivery->address2, $addressdelivery->postcode, $addressdelivery->city, $provincia, $pais, '', $addressdelivery->phone, '', $envioSms, '', '', $birthday, $fechalopd, '0', '0', $idcatalogo, '', '1');

    dump($content);
    exit();

    $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
    $json = json_encode($xml);
    $array = json_decode($json, true);

    $idresult = ''.$array[0];

    dump($array);
    exit();

    return $idresult != '';

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

// createclienteerp(734860);
updateclienteweb(734860, 101519060);
echo 'acaba';

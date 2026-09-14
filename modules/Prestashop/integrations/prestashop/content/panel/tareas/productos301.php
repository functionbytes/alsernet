<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';
include _PS_ADMIN_DIR_.'/../init.php';

function escriberedireccion($urlantigua, $urlnueva)
{

    $stdout = fopen(dirname(__FILE__).'/redirecciones.txt', 'a');
    fwrite($stdout, "$urlantigua;$urlnueva;301;1\n");
    fclose($stdout);

}

// abrir xml sitemap
function peticionget($url)
{

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $content = curl_exec($ch);
    curl_close($ch);

    return $content;

}

function ProcesarXMLSitemap($ruta)
{

    $content = peticionget($ruta);

    $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
    $json = json_encode($xml);
    $array = json_decode($json, true);

    foreach ($array['url'] as $url) {
        procesarurl($url['loc']);
    }

}

function procesarurl($url)
{

    preg_match('/(\/[0-9]*.\/)/', $url, $matches);

    if ($matches) {
        $modelo = str_replace('/', '', $matches[0]);

        $idproduct = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_product_import WHERE id_modelo='.$modelo);
        if ($idproduct != '') {

            $link = new Link;
            $urlnueva = $link->getProductLink($idproduct);
            escriberedireccion(str_replace('https://www.a-alvarez.com', '', $url), $urlnueva);
        } else {

            $stdout = fopen(dirname(__FILE__).'/redireccioneserrores.txt', 'a');
            fwrite($stdout, "No existe el modelo $modelo \n");
            fclose($stdout);

        }

    }

}

ProcesarXMLSitemap('https://www.a-alvarez.com/sitemap_es_aventura.xml');
ProcesarXMLSitemap('https://www.a-alvarez.com/sitemap_es_buceo.xml');
ProcesarXMLSitemap('https://www.a-alvarez.com/sitemap_es_caza.xml');
ProcesarXMLSitemap('https://www.a-alvarez.com/sitemap_es_esqui.xml');
ProcesarXMLSitemap('https://www.a-alvarez.com/sitemap_es_golf.xml');
ProcesarXMLSitemap('https://www.a-alvarez.com/sitemap_es_hipica.xml');
ProcesarXMLSitemap('https://www.a-alvarez.com/sitemap_es_nautica.xml');
ProcesarXMLSitemap('https://www.a-alvarez.com/sitemap_es_otros.xml');
ProcesarXMLSitemap('https://www.a-alvarez.com/sitemap_es_padel.xml');
ProcesarXMLSitemap('https://www.a-alvarez.com/sitemap_es_pesca.xml');

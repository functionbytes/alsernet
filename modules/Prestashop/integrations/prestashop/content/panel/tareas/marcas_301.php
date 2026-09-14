<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';
include _PS_ADMIN_DIR_.'/../init.php';

function safeName($sString)
{
    $sReturn = $sString;

    $sReturn = strtr($sReturn, "()!$'?: ,&+-/.", '');

    $a = ['', '', '', '', '', '', '', '¥', 'µ', 'À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ð', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ'];
    $b = ['S', 'O', 'Z', 's', 'o', 'z', 'Y', 'Y', 'u', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'o', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y'];
    $sReturn = str_replace($a, $b, $sReturn);
    $sReturn = trim(preg_replace('/\s+/', ' ', preg_replace('/[^a-zA-Z0-9\s]/', '', strtolower($sReturn))));

    $sReturn = str_replace(' ', '_', $sReturn);

    return $sReturn;
}

function escriberedireccion($urlantigua, $urlnueva)
{

    $stdout = fopen(dirname(__FILE__).'/redireccionesmarcas.txt', 'a');
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

    $rowsmarcas = Db::getInstance()->ExecuteS('SELECT name FROM aalv_manufacturer');

    foreach ($array['url'] as $url) {
        procesarurl($url['loc'], $rowsmarcas);
    }

}

function procesarurl($url, $rowsmarcas)
{

    // /m/drop_shot-outlet_y_liquidaciones
    // https://www.a-alvarez.com/drop-shot/outlet_y_liquidaciones

    $segmentos = explode('/', $url);

    if (count($segmentos) >= 4) {

        $marcarewrite = $segmentos[3];

        foreach ($rowsmarcas as $marca) {

            if (safeName($marca['name']) == $marcarewrite) {

                $urlnueva = '/m/'.Tools::link_rewrite($marca['name']);
                if (count($segmentos) > 4) {

                    $otras = explode('-', $segmentos[4]);

                    $urlnueva = $urlnueva.'-'.end($otras);
                }

                escriberedireccion(str_replace('https://www.a-alvarez.com', '', $url), $urlnueva);
            }

        }

    }

}

ProcesarXMLSitemap('https://www.a-alvarez.com/sitemap_es_otros.xml');

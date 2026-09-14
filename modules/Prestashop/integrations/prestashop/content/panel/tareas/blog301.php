<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';
include _PS_ADMIN_DIR_.'/../init.php';

function escriberedireccion($urlantigua, $urlnueva)
{

    $stdout = fopen(dirname(__FILE__).'/redireccionesblog.txt', 'a');
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

    $partes = explode('/', $url);

    $ultimo = end($partes);

    if (is_numeric($ultimo)) {
        $id_blog = $ultimo;
        echo $id_blog." <- post $url<br/>";
        $idpost = ''.Db::getInstance()->getValue('SELECT id_post FROM aalv_blog_post_import WHERE id_post_source='.$id_blog);
        if ($idpost != '') {

            $module = Module::getInstanceByName('ybc_blog');

            $urlnueva = $module->getLink('blog', ['id_post' => $idpost]);
            escriberedireccion(str_replace('https://www.a-alvarez.com', '', $url), $urlnueva);
        } else {

            $stdout = fopen(dirname(__FILE__).'/redireccionesblogerrores.txt', 'a');
            fwrite($stdout, "No existe el post $id_blog \n");
            fclose($stdout);

        }

    } else {
        // será categoria buscar slug
        $idcat = ''.Db::getInstance()->getValue("SELECT id_category FROM aalv_ybc_blog_category_lang WHERE id_lang=1 and url_alias='".$ultimo."'");

        if ($idcat != '') {

            $module = Module::getInstanceByName('ybc_blog');

            $urlnueva = $module->getLink('blog', ['id_category' => $idcat]);
            escriberedireccion(str_replace('https://www.a-alvarez.com', '', $url), $urlnueva);

        } else {
            $stdout = fopen(dirname(__FILE__).'/redireccionesblogerrores.txt', 'a');
            fwrite($stdout, "No existe la categoria $ultimo \n");
            fclose($stdout);
        }

    }

}

ProcesarXMLSitemap('https://www.a-alvarez.com/sitemap_blog_aventura.xml');
ProcesarXMLSitemap('https://www.a-alvarez.com/sitemap_blog_buceo.xml');
ProcesarXMLSitemap('https://www.a-alvarez.com/sitemap_blog_caza.xml');
ProcesarXMLSitemap('https://www.a-alvarez.com/sitemap_blog_esqui.xml');
ProcesarXMLSitemap('https://www.a-alvarez.com/sitemap_blog_golf.xml');
ProcesarXMLSitemap('https://www.a-alvarez.com/sitemap_blog_hipica.xml');
ProcesarXMLSitemap('https://www.a-alvarez.com/sitemap_blog_nautica.xml');
ProcesarXMLSitemap('https://www.a-alvarez.com/sitemap_blog_padel.xml');
ProcesarXMLSitemap('https://www.a-alvarez.com/sitemap_blog_pesca.xml');

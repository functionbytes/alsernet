<?php

ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../../config/config.inc.php';

$sql_blog_query = 'SELECT url_alias, id_post FROM '._DB_PREFIX_.'ybc_blog_post_lang aybpl WHERE id_lang = 1 ORDER BY id_post DESC';
$blogs = Db::getInstance()->executeS($sql_blog_query);
$datos_correctos = '';
$datos_incorrectos = '';
$total_procesados = 0;

foreach ($blogs as $blog) {
    /**
     * BUSCAR SI LA URL NO ESTÁ ALMACENADA EN LA TABLA lgseoredirect Y AÑADIRLA
     */
    $sql_url_not_present = $sql = 'SELECT * FROM '._DB_PREFIX_."lgseoredirect WHERE url_old = '/blog/post/".$blog['url_alias']."'";
    $url_not_present = DB::getInstance()->executeS($sql_url_not_present);
    if (! count($url_not_present)) {
        $categorias_url = getCategoriasUrl($blog['id_post']);

        if (getRequestStatus('https://'.$_SERVER['SERVER_NAME'].'/blog/post/'.$blog['url_alias']) &&
            getRequestStatus('https://'.$_SERVER['SERVER_NAME'].'/blog/'.$categorias_url.$blog['url_alias'])) {
            $datos_correctos .= '/blog/post/'.$blog['url_alias'].' ==> /blog/'.$categorias_url.$blog['url_alias'].'<br><br>';
            $old_url = '/blog/post/'.$blog['url_alias'];
            $new_url = 'https://'.$_SERVER['SERVER_NAME'].'/blog/'.$categorias_url.$blog['url_alias'];
            $insert_sql = 'INSERT INTO '._DB_PREFIX_."lgseoredirect VALUES (NULL, '$old_url', '$new_url' , 301, NOW(), 1, 0);";
            Db::getInstance()->execute($insert_sql);
            echo "AÑADIDO ----- $old_url ==> $new_url\n";
        } else {
            $datos_incorrectos .= '/blog/post/'.$blog['url_alias'].' ==> /blog/'.$categorias_url.$blog['url_alias'].'<br><br>';
            echo 'ERROR ----- /blog/post/'.$blog['url_alias'].' ==> /blog/'.$categorias_url.$blog['url_alias']."\n";
        }
        $total_procesados++;
    }

    /**
     * LA URL YA EXISTE PERO TIENE PROBLEMAS EN EL REDIRECT
     */
    /*$sql_url_wrong_redirection = "SELECT * FROM " . _DB_PREFIX_ . "lgseoredirect WHERE url_old LIKE '%/blog/%' AND url_new LIKE '%" . $blog['url_alias'] . "%' ORDER BY id DESC ";
    $url_wrong_redirection = Db::getInstance()->executeS($sql_url_wrong_redirection);

    if (count($url_wrong_redirection) == 0) {

        $categorias_url = getCategoriasUrl($blog['id_post']);
        if (getRequestStatus("https://" . $_SERVER['SERVER_NAME'] . "/blog/post/" . $blog['url_alias']) &&
            getRequestStatus("https://" . $_SERVER['SERVER_NAME'] . "/blog/" . $categorias_url . $blog['url_alias'])) {
            $datos_correctos = "/blog/post/" . $blog['url_alias'] . " ==> /blog/" . $categorias_url . $blog['url_alias'] . "<br>/blog/" . $categorias_url . $blog['url_alias'] . "/0 ==> /blog/" . $categorias_url . $blog['url_alias'] . "<br><br>";
            Db::getInstance()->execute("INSERT INTO " . _DB_PREFIX_ . "lgseoredirect VALUES (NULL, '/blog/post/" . $blog['url_alias'] . "', 'https://www.a-alvarez.com/blog/" . $categorias_url . $blog['url_alias'] . "', 301, NOW(), 1, 0);");
            Db::getInstance()->execute("INSERT INTO " . _DB_PREFIX_ . "lgseoredirect VALUES (NULL, '/blog/" . $categorias_url . $blog['url_alias'] . "/0', 'https://www.a-alvarez.com/blog/" . $categorias_url . $blog['url_alias'] . "', 301, NOW(), 1, 0);");
        } else {
            $datos_incorrectos = "/blog/post/" . $blog['url_alias'] . " ==> /blog/" . $categorias_url . $blog['url_alias'] . "<br>/blog/" . $categorias_url . $blog['url_alias'] . "/0 ==> /blog/" . $categorias_url . $blog['url_alias'] . "<br><br>";
        }
    }*/
}

echo "TOTAL URLS PROCESADAS ----- $total_procesados";
// echo $datos_correctos;
if ($datos_correctos != '' ||
    $datos_incorrectos != '') {
    $html = 'INCORRECTOS<br>'.$datos_incorrectos.'<hr>CORRECTOS<br>'.$datos_correctos;
    sendmailPruebas($html);
    echo 'enviado';
}
exit();

function getRequestStatus($value): bool
{
    // Obtener los encabezados de la URL
    $headers = get_headers($value);

    // Verificar si los encabezados se obtuvieron correctamente
    if ($headers !== false) {
        // Obtener el primer encabezado (que contiene el código de estado HTTP)
        $http_status = $headers[0];

        // Verificar si el código de estado es 200 OK o 404 Not Found
        if (strpos($http_status, '200') !== false) {
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

/**
 * @return void
 */
function sendmailPruebas($mensaje)
{
    $dest = [];
    $dest[] = 'alvarez@alsernet.es';

    $data = ['{message}' => $mensaje];
    Mail::Send(
        1,
        'integracionV2',
        'Blog redireccion',
        $data,
        $dest,
        Configuration::get('PS_SHOP_NAME'),
        'desarrollotest@a-alvarez.net',
        'desarrollotest',
        [],
        null,
        _PS_MAIL_DIR_,
        false,
        1
    );
}

/**
 * @throws PrestaShopDatabaseException
 */
function getCategoriasUrl($id_post): string
{

    $url = '';
    $categorias = Db::getInstance()->executeS(' SELECT
                                                        aybcl.url_alias
                                                    FROM
                                                        '._DB_PREFIX_.'ybc_blog_post_category aybpc
                                                        LEFT JOIN '._DB_PREFIX_.'ybc_blog_category_lang aybcl ON aybcl.id_category = aybpc.id_category
                                                    WHERE
                                                        aybpc.id_post = '.$id_post.'
                                                        AND aybcl.id_lang = 1
                                                    ORDER BY aybpc.`position`, aybpc.id_category ASC');

    foreach ($categorias as $val) {
        $url .= $val['url_alias'].'/';
    }

    return $url;
}

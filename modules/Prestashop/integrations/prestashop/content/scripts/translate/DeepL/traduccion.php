<?php

require dirname(__FILE__).'/../../config/config.panel.inc.php';
require dirname(__FILE__).'/vendor/autoload.php';

define('_DEF_authKey', 'af12b059-74f8-4417-8476-2f6785526670'); // Replace with your key

global $productos;
$productos = [];
$dbOkitup = connectOkitup();
mysqli_set_charset($dbOkitup, 'utf8mb4');

$idiomas_traduccion = [];
$productos_traduccion = false;
$categorias_traduccion = false;
$todo = true;
$id_producto = '';
$id_categoria = '';
$where_categoria = '';
$productos_lista = [];
$listado_productos = '';
$productos_pendientes = false;

if (is_array($argv)) {
    foreach ($argv as $parametro) {
        if (strpos($parametro, 'idiomas') !== false) {
            $idiomas_traduccion = explode(',', str_replace('idiomas=', '', $parametro));
        }
        if (strpos($parametro, 'producto=') !== false) {
            $id_producto = str_replace('producto=', '', $parametro);

        }
        if (strpos($parametro, 'productos=') !== false) {
            $listado_productos = str_replace('productos=', '', $parametro);

        }
        if (strpos($parametro, 'categorias=') !== false) {
            $id_categoria = str_replace('categorias=', '', $parametro);
            $where_categoria = ' AND c.id_category IN ('.$id_categoria.')';
        }
        if ($parametro == 'categorias') {
            $categorias_traduccion = false; // Por ahora no se traducen
            $todo = false;
        }
        if ($parametro == 'productos') {
            $productos_pendientes = true;
            $todo = false;
        }
        if ($parametro == 'sobreescribe') {
            $sobreescribe = true;
        }
        if ($parametro == 'diccionario') {
            $diccionario_traduccion = true;
            $todo = false;
        }
        if ($parametro == 'debug') {
            $debug = true;
        }
    }
}

if ($idiomas_traduccion) {
    $idiomas = [];
    foreach ($idiomas_traduccion as $iso_code) {
        $r = mysqli_query($dbOkitup, "SELECT * FROM aalv_lang WHERE iso_code = '".$iso_code."'");
        $idioma = mysqli_fetch_assoc($r);
        if ($idioma) {
            $idiomas[] = $idioma;
        }
    }
} else {
    $r = mysqli_query($dbOkitup, 'SELECT * FROM aalv_lang WHERE id_lang > 1');
    while ($idiomas_bd = mysqli_fetch_assoc($r)) {
        $idiomas[] = $idiomas_bd;
    }
}

if ($listado_productos) {
    echo "Traducimos productos\n";
    foreach ($idiomas as $idioma) {
        traducirProductos($dbOkitup, $idioma, $listado_productos);
    }
}

if ($productos_pendientes) {
    echo "Traducimos productos pendientes\n";
    foreach ($idiomas as $idioma) {
        $sql = 'SELECT *
            FROM (SELECT
                    p.id_product,
                    (select l1.description
                      from aalv_product_lang l1
                      where l1.id_product=p.id_product
                          AND l1.id_lang=1) as es,
                    (select l2.description
                        from aalv_product_lang l2
                        where l2.id_product=p.id_product
                            AND l2.id_lang='.$idioma['id_lang'].') as '.$idioma['iso_code']."
                  FROM aalv_product p
                  WHERE p.active = 1) as traducciones
            WHERE es!='' AND (es=".$idioma['iso_code'].' OR '.$idioma['iso_code']." = '')
            ORDER by id_product DESC";

        $r = mysqli_query($dbOkitup, $sql);
        while ($producto = mysqli_fetch_assoc($r)) {
            $productos_lista[] = $producto['id_product'];
        }
        if ($productos_lista) {
            $listado_productos = implode(',', $productos_lista);
            traducirProductos($dbOkitup, $idioma, $listado_productos);
        }
    }
}

if ($where_categoria) {
    echo "Traducimos productos de categorías\n";
    foreach ($idiomas as $idioma) {
        $r = mysqli_query($dbOkitup, 'select c.* from aalv_category c WHERE c.active=1'.$where_categoria);
        while ($categoria = mysqli_fetch_assoc($r)) {
            echo 'CATEGORIA=>'.$categoria['id_category']."\n";
            // peticionget("https://www.a-alvarez.com/?fc=module&module=pagecache&controller=clearcache&token=ApbUf8KuFaGPBhAk&category=" . $categoria['id_category']);
            traducirProductos($dbOkitup, $idioma, false, $categoria['id_category']);
            subcategorias($idioma, $dbOkitup, $categoria['id_category']);
        }
    }
}
echo "\nFin\n";
exit;

function traducirProductos($dbOkitup, $idioma, $id_products = false, $id_category = false)
{

    global $productos;

    $translator = new \DeepL\Translator(_DEF_authKey);

    $where_categoria = '';
    $where_productos = '';
    $traduccion = '';

    if ($id_category) {
        $where_categoria = ' AND p.id_product IN (select id_product from aalv_category_product where id_category='.$id_category.')';
    }

    if ($id_products) {
        $where_productos = ' AND p.id_product IN ('.$id_products.')';
    }

    $sql = 'select
    p.id_product,
    pl.name,
    pl.description,
    pl.link_rewrite,
    pl.meta_description,
    pl.meta_title
    from
    aalv_product_lang pl
    left join
    aalv_product p
    on pl.id_product = p.id_product
    where
    pl.id_lang = 1
    and pl.id_shop = 1
    and p.active = 1'.$where_categoria.$where_productos;

    $r = mysqli_query($dbOkitup, $sql);
    while ($producto = mysqli_fetch_assoc($r)) {
        $sql = 'SELECT no_traducir FROM aalv_alsernet_traducciones WHERE id_product='.$producto['id_product'];
        $r2 = mysqli_query($dbOkitup, $sql);
        $alser_traducciones = mysqli_fetch_assoc($r2);
        if ($alser_traducciones['no_traducir'] == 1) {
            echo 'TRADUCCIÓN MANUAL => '.$producto['id_product']."\n";

            continue;
        }
        if (in_array($producto['id_product'], $productos)) {
            echo 'PRODUCTO YA TRADUCIDO => '.$producto['id_product']."\n";

            continue;
        }
        echo $producto['id_product']."\n";

        switch ($idioma['iso_code']) {
            case 'pt':
            case 'en':
                $target_lang = $idioma['locale'];
                break;
            case 'es':
            case 'fr':
            case 'de':
            default:
                $target_lang = $idioma['iso_code'];
                break;

        }

        $textos = [$producto['name'], $producto['description'], $producto['link_rewrite'], $producto['meta_description'], $producto['meta_title']];
        $options = [
            'context' => strip_tags($producto['description']),
            'glossary' => 'e2024c80-ee78-47ba-8247-0a046ae164fa',
            'formality' => 'prefer_more',
        ];
        $traduccion = $translator->translateText($textos, 'es', $target_lang, $options);

        if ($traduccion) {

            $productos[] = $producto['id_product'];
            $link_rewrite = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $traduccion[2]->text);
            $link_rewrite = str_replace('.', '', $link_rewrite);
            $link_rewrite = Tools::link_rewrite($link_rewrite);
            $description = str_replace('<li><li>', '<li>', $traduccion[1]->text);
            $description = str_replace('</li></li>', '</li>', $description);
            $description = str_replace('</li>.', '.</li>', $description);
            $description = str_replace('<ul><ul>', '<ul>', $description);
            $description = str_replace('</ul></ul>', '</ul>', $description);

            $sql_update = "UPDATE aalv_product_lang SET
          name='".str_replace("'", "\'", $traduccion[0]->text)."',
          description='".str_replace("'", "\'", $description)."',
          link_rewrite='".str_replace(' ', '-', str_replace("'", '', $link_rewrite))."',
          meta_description='".str_replace("'", "\'", $traduccion[3]->text)."',
          meta_title='".str_replace('Zen Cart !', 'Álvarez', str_replace("'", "\'", str_replace(':', '|', $traduccion[4]->text)))."'
          WHERE id_product='".$producto['id_product']."' AND id_lang=".$idioma['id_lang'].' AND id_shop=1';
            mysqli_query($dbOkitup, $sql_update);

            peticionget('https://www.a-alvarez.com/?fc=module&module=pagecache&controller=clearcache&token=ApbUf8KuFaGPBhAk&product='.$producto['id_product']);
        } else {
            echo 'No traducido => '.$producto['id_product']."\n";
        }
    }
}

function subcategorias($idioma, $dbOkitup, $id_categoria)
{
    $r = mysqli_query($dbOkitup, 'select c.* from aalv_category c WHERE c.active=1 and c.id_parent = '.$id_categoria);

    while ($categoria = mysqli_fetch_assoc($r)) {
        // if ($categoria['name'] == "m") continue;
        echo 'CATEGORIA=>'.$categoria['id_category']."\n";
        // peticionget("https://www.a-alvarez.com/?fc=module&module=pagecache&controller=clearcache&token=ApbUf8KuFaGPBhAk&category=" . $categoria['id_category']);
        traducirProductos($dbOkitup, $idioma, false, $categoria['id_category']);
        subcategorias($idioma, $dbOkitup, $categoria['id_category']);
    }

}

function connectOkitup()
{
    return $dbcon;
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

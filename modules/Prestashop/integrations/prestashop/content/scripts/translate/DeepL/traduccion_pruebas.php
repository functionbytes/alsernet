<?php

require 'vendor/autoload.php';

define('_DEF_authKey', 'af12b059-74f8-4417-8476-2f6785526670'); // Replace with your key

$dbOkitup = connectOkitup();
mysqli_set_charset($dbOkitup, 'utf8mb4');

$translator = new \DeepL\Translator(_DEF_authKey);

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
    and p.active = 1
    and p.id_product = 47153';

$r = mysqli_query($dbOkitup, $sql);
while ($producto = mysqli_fetch_assoc($r)) {
    echo 'PRODUCTO => '.$producto['id_product']."\n";
    $textos = [$producto['name'], $producto['description'], $producto['link_rewrite'], $producto['meta_description'], $producto['meta_title']];
    $traduccion = $translator->translateText($textos, 'es', 'fr', ['context' => $producto['description']]);
    echo $traduccion[2]->text."\n";
    echo iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $traduccion[2]->text)."\n";
    exit;

    if ($traduccion) {
        $productos[] = $producto['id_product'];
        $sql_update = "UPDATE aalv_product_lang SET
            name='".str_replace("'", "\'", $traduccion[0]->text)."',
            description='".str_replace("'", "\'", $traduccion[1]->text)."',
            link_rewrite='".str_replace(' ', '-', str_replace("'", '', $traduccion[2]->text))."',
            meta_description='".str_replace("'", "\'", $traduccion[3]->text)."',
            meta_title='".str_replace("'", "\'", $traduccion[4]->text)."'
            WHERE id_product='".$producto['id_product']."' AND id_lang=3 AND id_shop=1";
        mysqli_query($dbOkitup, $sql_update);
    } else {
        echo 'No traducido => '.$producto['id_product']."\n";
    }
    exit;
}

function connectOkitup()
{

    return $dbcon;
}

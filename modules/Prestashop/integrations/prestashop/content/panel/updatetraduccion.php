<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

function getfieldvalue($dbh, $sql)
{
    $rows = $dbh->query($sql);
    foreach ($rows as $row) {
        return $row[0];
    }
}

function getdatarows($dbh, $sql)
{
    return $dbh->query($sql);
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

function TraducirProd($idps, $idmodelo, $dbh)
{

    $sufixtitle = ' | Comprar online | Alvarez';

    $sufixdescription = [];
    $sufixdescription[1] = ' al mejor precio en Deportes Álvarez. Compra online tu equipo de caza en Álvarez y ✅ no te pierdas nuestras ofertas ✅';
    $sufixdescription[2] = " at the best price at Alvarez's Sports. Purchase online your hunting en Álvarez y ✅ don't miss our offers ✅";
    $sufixdescription[3] = ' au meilleur prix à Álvarez Sports. Achetez votre équipe chasse en ligne à Álvarez et ✅ ne manquez pas nos offres ✅';
    $sufixdescription[4] = ' ao melhor preço em Desportos Álvarez. Compre online o seu equipamento de caca em Álvarez e ✅ não perca as nossas ofertas ✅';
    $sufixdescription[5] = ' zum besten Preis in Álvarez Sports. Kaufen Sie Ihr Team von jagd online in Álvarez und ✅ verpassen Sie nicht unsere Angebote ✅';

    $product = new Product($idps);
    $nombre = $product->name[1];

    $nombreen = getfieldvalue($dbh, 'SELECT nombre FROM modelo_idioma where id_modelo='.$idmodelo." and idioma='en'");
    $nombrede = getfieldvalue($dbh, 'SELECT nombre FROM modelo_idioma where id_modelo='.$idmodelo." and idioma='de'");
    $nombrefr = getfieldvalue($dbh, 'SELECT nombre FROM modelo_idioma where id_modelo='.$idmodelo." and idioma='fr'");
    $nombrept = getfieldvalue($dbh, 'SELECT nombre FROM modelo_idioma where id_modelo='.$idmodelo." and idioma='pt'");

    $descripcion = $product->description[1];
    $descripcionen = getfieldvalue($dbh, 'SELECT descripcion FROM modelo_idioma where id_modelo='.$idmodelo." and idioma='en'");
    $descripcionde = getfieldvalue($dbh, 'SELECT descripcion FROM modelo_idioma where id_modelo='.$idmodelo." and idioma='de'");
    $descripcionfr = getfieldvalue($dbh, 'SELECT descripcion FROM modelo_idioma where id_modelo='.$idmodelo." and idioma='fr'");
    $descripcionpt = getfieldvalue($dbh, 'SELECT descripcion FROM modelo_idioma where id_modelo='.$idmodelo." and idioma='pt'");

    if ($nombreen == '') {
        $nombreen = getfieldvalue($dbh, 'SELECT nombre FROM modelo_traduccion_auto where id_modelo='.$idmodelo." and idioma='en'");
    }
    if ($nombrede == '') {
        $nombrede = getfieldvalue($dbh, 'SELECT nombre FROM modelo_traduccion_auto where id_modelo='.$idmodelo." and idioma='de'");
    }
    if ($nombrefr == '') {
        $nombrefr = getfieldvalue($dbh, 'SELECT nombre FROM modelo_traduccion_auto where id_modelo='.$idmodelo." and idioma='fr'");
    }
    if ($nombrept == '') {
        $nombrept = getfieldvalue($dbh, 'SELECT nombre FROM modelo_traduccion_auto where id_modelo='.$idmodelo." and idioma='pt'");
    }

    if ($descripcionen == '') {
        $descripcionen = getfieldvalue($dbh, 'SELECT descripcion FROM modelo_traduccion_auto where id_modelo='.$idmodelo." and idioma='en'");
    }
    if ($descripcionde == '') {
        $descripcionde = getfieldvalue($dbh, 'SELECT descripcion FROM modelo_traduccion_auto where id_modelo='.$idmodelo." and idioma='de'");
    }
    if ($descripcionfr == '') {
        $descripcionfr = getfieldvalue($dbh, 'SELECT descripcion FROM modelo_traduccion_auto where id_modelo='.$idmodelo." and idioma='fr'");
    }
    if ($descripcionpt == '') {
        $descripcionpt = getfieldvalue($dbh, 'SELECT descripcion FROM modelo_traduccion_auto where id_modelo='.$idmodelo." and idioma='pt'");
    }

    if ($nombreen == '') {
        $nombreen = $nombre;
    }
    if ($nombrede == '') {
        $nombrede = $nombre;
    }
    if ($nombrefr == '') {
        $nombrefr = $nombre;
    }
    if ($nombrept == '') {
        $nombrept = $nombre;
    }

    if ($descripcionen == '') {
        $descripcionen = $descripcion;
    }
    if ($descripcionde == '') {
        $descripcionde = $descripcion;
    }
    if ($descripcionfr == '') {
        $descripcionfr = $descripcion;
    }
    if ($descripcionpt == '') {
        $descripcionpt = $descripcion;
    }

    $nombreen = str_replace('&quot;', '"', $nombreen);
    $nombrede = str_replace('&quot;', '"', $nombrede);
    $nombrefr = str_replace('&quot;', '"', $nombrefr);
    $nombrept = str_replace('&quot;', '"', $nombrept);

    $nombreen = str_replace('&#39;', "'", $nombreen);
    $nombrede = str_replace('&#39;', "'", $nombrede);
    $nombrefr = str_replace('&#39;', "'", $nombrefr);
    $nombrept = str_replace('&#39;', "'", $nombrept);

    $nombreen = str_replace('#', '', $nombreen);
    $nombrede = str_replace('#', '', $nombrede);
    $nombrefr = str_replace('#', '', $nombrefr);
    $nombrept = str_replace('#', '', $nombrept);

    $nombreen = str_replace('&amp;', '&', $nombreen);
    $nombrede = str_replace('&amp;', '&', $nombrede);
    $nombrefr = str_replace('&amp;', '&', $nombrefr);
    $nombrept = str_replace('&amp;', '&', $nombrept);

    $nombreen = str_replace('&empty;', ' ', $nombreen);
    $nombrede = str_replace('&empty;', ' ', $nombrede);
    $nombrefr = str_replace('&empty;', ' ', $nombrefr);
    $nombrept = str_replace('&empty;', ' ', $nombrept);

    $nombreen = str_replace(';', ',', $nombreen);
    $nombrede = str_replace(';', ',', $nombrede);
    $nombrefr = str_replace(';', ',', $nombrefr);
    $nombrept = str_replace(';', ',', $nombrept);

    $nombreen = str_replace('=', ' ', $nombreen);
    $nombrede = str_replace('=', ' ', $nombrede);
    $nombrefr = str_replace('=', ' ', $nombrefr);
    $nombrept = str_replace('=', ' ', $nombrept);

    $nombreen = strip_tags($nombreen);
    $nombrede = strip_tags($nombrede);
    $nombrefr = strip_tags($nombrefr);
    $nombrept = strip_tags($nombrept);

    $nombreen = substr($nombreen, 0, 128);
    $nombrede = substr($nombrede, 0, 128);
    $nombrefr = substr($nombrefr, 0, 128);
    $nombrept = substr($nombrept, 0, 128);

    $product->name[2] = $nombreen;
    $product->name[3] = $nombrefr;
    $product->name[4] = $nombrept;
    $product->name[5] = $nombrede;

    $product->description[2] = $descripcionen;
    $product->description[3] = $descripcionfr;
    $product->description[4] = $descripcionpt;
    $product->description[5] = $descripcionde;

    $product->link_rewrite[2] = Tools::link_rewrite(substr($product->name[2], 0, 128));
    $product->link_rewrite[3] = Tools::link_rewrite(substr($product->name[3], 0, 128));
    $product->link_rewrite[4] = Tools::link_rewrite(substr($product->name[4], 0, 128));
    $product->link_rewrite[5] = Tools::link_rewrite(substr($product->name[5], 0, 128));

    $product->meta_title[2] = $product->name[2].$sufixtitle;
    $product->meta_description[2] = $product->name[2].$sufixdescription[2];
    $product->meta_title[3] = $product->name[3].$sufixtitle;
    $product->meta_description[3] = $product->name[3].$sufixdescription[3];
    $product->meta_title[4] = $product->name[4].$sufixtitle;
    $product->meta_description[4] = $product->name[4].$sufixdescription[4];
    $product->meta_title[5] = $product->name[5].$sufixtitle;
    $product->meta_description[5] = $product->name[5].$sufixdescription[5];

    $product->update();

    peticionget('https://www.a-alvarez.com/?fc=module&module=pagecache&controller=clearcache&token=ApbUf8KuFaGPBhAk&product='.$idps);

}

try {

    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

$rows = Db::getInstance()->ExecuteS('select id_product, id_modelo from aalv_product_import WHERE id_product>=55053');

foreach ($rows as $row) {
    TraducirProd($row['id_product'], $row['id_modelo'], $dbh);

}
echo 'acaba';

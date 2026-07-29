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

function buscarseo($dbh, $url, $category, $deporte, $idiomaalvarez, $idioma, $catorigen)
{

    global $textosarellenar;

    $encuentra = false;
    $rows = getdatarows($dbh, "SELECT * FROM textos_categorias where url='".$url."' and id_deporte=".$deporte." and idioma='".$idiomaalvarez."'");
    foreach ($rows as $row) {
        $encuentra = true;

        $meta_title = trim($row['title']);
        $meta_desc = trim($row['description']);
        $descripcion = $row['texto_superior'].$row['texto_inferior'];
        $h1 = trim($row['h1']);
        break;

    }
    if (! $encuentra) {

        if ($catorigen->id != 4) {
            $meta_title = $category->name.' - '.$catorigen->name[$idioma].' - Álvarez Deportes';
        } else {
            $meta_title = $category->name.' - '.$catorigen->name[$idioma].' - Armería Álvarez';
        }

        $meta_desc = str_replace('{DEPORTE}', $catorigen->name[$idioma], str_replace('{CAT}', $category->name, $textosarellenar[$idiomaalvarez]));
        $h1 = $category->name;

        $descripcion = '';

    }

    $catupdate = new Category($category->id);

    $catupdate->meta_title[$idioma] = $meta_title;
    $catupdate->meta_description[$idioma] = str_replace('https://www.a-alvarez.com', '', $meta_desc);
    $catupdate->description[$idioma] = str_replace('https://www.a-alvarez.com', '', $descripcion);
    $catupdate->update();

    Db::getInstance()->Execute('REPLACE INTO aalv_appagebuilder_extracat(id_category, id_shop, id_lang, tituloh1) VALUES ('.$category->id.',1,'.$idioma.",'".$h1."')");

}

function RellenarSeoMarcas($datos, $dbh)
{

    $id_marca = $datos['id_manufacturer'];

    $marca = new Manufacturer($id_marca);

    switch ($id_marca) {
        case 134: // Beretta
            $titlerellenar = [];
            $titlerellenar['es'] = 'Beretta: Armas, Ropa y Accesiorios de Caza | Armería Álvarez';
            $titlerellenar['pt'] = 'Beretta: Armas, Ropa y Accesiorios de Caza | Armería Álvarez';
            $titlerellenar['en'] = 'Beretta: Armas, Ropa y Accesiorios de Caza | Armería Álvarez';
            $titlerellenar['de'] = 'Beretta: Armas, Ropa y Accesiorios de Caza | Armería Álvarez';
            $titlerellenar['fr'] = 'Beretta: Armas, Ropa y Accesiorios de Caza | Armería Álvarez';

            $h1 = [];
            $h1['es'] = 'Beretta: Armas, pistolas y accesorios de caza';
            $h1['pt'] = 'Beretta: Armas, pistolas y accesorios de caza';
            $h1['en'] = 'Beretta: Armas, pistolas y accesorios de caza';
            $h1['de'] = 'Beretta: Armas, pistolas y accesorios de caza';
            $h1['fr'] = 'Beretta: Armas, pistolas y accesorios de caza';

            break;
        case 135: // Benelli
            $titlerellenar = [];
            $titlerellenar['es'] = 'Benelli: Armas de caza y tiro, escopetas y rifles | Armería Álvarez';
            $titlerellenar['pt'] = 'Benelli: Armas de caza y tiro, escopetas y rifles | Armería Álvarez';
            $titlerellenar['en'] = 'Benelli: Armas de caza y tiro, escopetas y rifles | Armería Álvarez';
            $titlerellenar['de'] = 'Benelli: Armas de caza y tiro, escopetas y rifles | Armería Álvarez';
            $titlerellenar['fr'] = 'Benelli: Armas de caza y tiro, escopetas y rifles | Armería Álvarez';

            $h1 = [];
            $h1['es'] = 'Benelli: Armas de caza y tiro, escopetas y accesorios';
            $h1['pt'] = 'Benelli: Armas de caza y tiro, escopetas y accesorios';
            $h1['en'] = 'Benelli: Armas de caza y tiro, escopetas y accesorios';
            $h1['de'] = 'Benelli: Armas de caza y tiro, escopetas y accesorios';
            $h1['fr'] = 'Benelli: Armas de caza y tiro, escopetas y accesorios';

            break;
        case 162: // Glock
            $titlerellenar = [];
            $titlerellenar['es'] = 'Pistolas Glock | Compra online | Armería Álvarez';
            $titlerellenar['pt'] = 'Pistolas Glock | Compra online | Armería Álvarez';
            $titlerellenar['en'] = 'Pistolas Glock | Compra online | Armería Álvarez';
            $titlerellenar['de'] = 'Pistolas Glock | Compra online | Armería Álvarez';
            $titlerellenar['fr'] = 'Pistolas Glock | Compra online | Armería Álvarez';

            $h1 = [];
            $h1['es'] = 'Productos '.$marca->name;
            $h1['pt'] = 'Produtos '.$marca->name;
            $h1['en'] = 'Products '.$marca->name;
            $h1['de'] = 'Produkte '.$marca->name;
            $h1['fr'] = 'Produits '.$marca->name;

            break;
        default:
            // los demás
            $titlerellenar = [];
            $titlerellenar['es'] = 'Productos '.$marca->name.' - Álvarez';
            $titlerellenar['pt'] = 'Produtos '.$marca->name.' - Álvarez';
            $titlerellenar['en'] = 'Products '.$marca->name.' - Álvarez';
            $titlerellenar['de'] = 'Produkte '.$marca->name.' - Álvarez';
            $titlerellenar['fr'] = 'Produits '.$marca->name.' - Álvarez';

            $h1 = [];
            $h1['es'] = 'Productos '.$marca->name;
            $h1['pt'] = 'Produtos '.$marca->name;
            $h1['en'] = 'Products '.$marca->name;
            $h1['de'] = 'Produkte '.$marca->name;
            $h1['fr'] = 'Produits '.$marca->name;

    }

    $metadesrellenar = [];
    $metadesrellenar['es'] = 'Descubre nuestra selección de productos '.$marca->name.' y cómpralos online al mejor precio. Alvarez, la mayor tienda de deporte y tiempo libre';
    $metadesrellenar['pt'] = 'Descubre nuestra selección de productos '.$marca->name.' y cómpralos online al mejor precio. Alvarez, la mayor tienda de deporte y tiempo libre';
    $metadesrellenar['en'] = 'Descubre nuestra selección de productos '.$marca->name.' y cómpralos online al mejor precio. Alvarez, la mayor tienda de deporte y tiempo libre';
    $metadesrellenar['de'] = 'Descubre nuestra selección de productos '.$marca->name.' y cómpralos online al mejor precio. Alvarez, la mayor tienda de deporte y tiempo libre';
    $metadesrellenar['fr'] = 'Descubre nuestra selección de productos '.$marca->name.' y cómpralos online al mejor precio. Alvarez, la mayor tienda de deporte y tiempo libre';

    $textosup = getfieldvalue($dbh, "select texto_superior from textos_marcas where url='/".$marca->link_rewrite."'");
    $textoinf = getfieldvalue($dbh, "select texto_inferior from textos_marcas where url='/".$marca->link_rewrite."'");

    $textosup = str_replace('https://www.a-alvarez.com/', '/', $textosup);
    $textosup = str_replace('https://www.a-alvarez.com', '/', $textosup);

    $textoinf = str_replace('https://www.a-alvarez.com/', '/', $textoinf);
    $textoinf = str_replace('https://www.a-alvarez.com', '/', $textoinf);

    $desc = $textosup.$textoinf;

    // $product->name[1] = $nombre;
    // $product->name[2] = $nombreen;
    // $product->name[3] = $nombrefr;
    // $product->name[4] = $nombrept;
    // $product->name[5] = $nombrede;

    $marca->short_description[1] = $h1['es'];
    $marca->short_description[2] = $h1['en'];
    $marca->short_description[3] = $h1['fr'];
    $marca->short_description[4] = $h1['pt'];
    $marca->short_description[5] = $h1['de'];

    $marca->description[1] = $desc;
    $marca->description[2] = $desc;
    $marca->description[3] = $desc;
    $marca->description[4] = $desc;
    $marca->description[5] = $desc;

    $marca->meta_title[1] = $titlerellenar['es'];
    $marca->meta_title[2] = $titlerellenar['en'];
    $marca->meta_title[3] = $titlerellenar['fr'];
    $marca->meta_title[4] = $titlerellenar['pt'];
    $marca->meta_title[5] = $titlerellenar['de'];

    $marca->meta_description[1] = $metadesrellenar['es'];
    $marca->meta_description[2] = $metadesrellenar['en'];
    $marca->meta_description[3] = $metadesrellenar['fr'];
    $marca->meta_description[4] = $metadesrellenar['pt'];
    $marca->meta_description[5] = $metadesrellenar['de'];

    $marca->update();

}

try {
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

$sql = 'select id_manufacturer from aalv_manufacturer';

$rows = Db::getInstance()->ExecuteS($sql);

foreach ($rows as $row) {
    RellenarSeoMarcas($row, $dbh);
}

echo 'acaba';

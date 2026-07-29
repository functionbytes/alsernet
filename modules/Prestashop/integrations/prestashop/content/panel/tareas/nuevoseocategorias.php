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

function getcategory($listacat, $catraiz)
{

    /* $catprin = $listacat[count($listacat)-1];

     $idcats = Db::getInstance()->ExecuteS("SELECT id_cat FROM aalv_category_import WHERE id_origen=".$catprin);

     foreach($idcats as $idcat){


     }*/

    $categories = [];
    foreach ($listacat as $itemcat) {
        $categories[] = Db::getInstance()->ExecuteS('SELECT id_cat FROM aalv_category_import WHERE id_origen='.$itemcat);
    }

    $catparent = $catraiz;

    // echo count($categories)."<br/>";
    for ($i = 0; $i < count($categories); $i++) {

        foreach ($categories[$i] as $category) {

            // echo "entra cat ". $category["id_cat"]. "<br/>";
            $cat = new Category($category['id_cat']);

            if ($cat->id_parent == $catparent) {

                $catparent = $category['id_cat'];
                // echo "llega ". $catparent. "<br/>";

            }

        }

    }

    if ($catparent != $catraiz) {
        return $catparent;
    } else {
        return '';
    }

}

function RellenarSeoCategorias($datos, $dbh)
{

    try {

        $categoriasorigen = $datos['id_categoria'];

        $meta_title = $datos['title'];

        $meta_desc = $datos['description'];

        $descripcion = $datos['texto_superior'].$datos['texto_inferior'];

        $deporte = $datos['id_deporte'];

        $idioma = $datos['idioma'];

        if ($idioma == 'es') {
            $idlang = 1;
        }
        if ($idioma == 'pt') {
            $idlang = 4;
        }

        $listacat = explode('-', $categoriasorigen);

        $ultimacat = end($listacat);

        $ultimacatps = Db::getInstance()->ExecuteS('SELECT id_cat  FROM aalv_category_import inner join aalv_category on id_cat=id_category WHERE id_origen = '.$ultimacat);

        foreach ($ultimacatps as $ups) {

            $category = new Category($ups['id_cat']);

            if ($category->sport == $deporte) {

                $category->meta_title[$idlang] = $meta_title;
                $category->meta_description[$idlang] = str_replace('https://www.a-alvarez.com', '', $meta_desc);
                $category->description[$idlang] = str_replace('https://www.a-alvarez.com', '', $descripcion);
                $category->update();

                Db::getInstance()->Execute('REPLACE INTO aalv_appagebuilder_extracat(id_category, id_shop, id_lang, tituloh1) VALUES ('.$category->id.',1,'.$idlang.",'".$datos['h1']."')");
            }

        }

        /*
        $idcat = getcategory($listacat, $catraiz);


        echo $idcat. " ". $categoriasorigen. "<br/>";


        if ($idcat!=""){

            $catlast="".Db::getInstance()->ExecuteS("SELECT id_cat FROM aalv_category_import WHERE id_cat=".$idcat." and id_origen=".end($listacat));

            if ($catlast!=""){

                echo "<br/>llega ".$idcat;
                echo "<br/>meta_title ".$meta_title;
                echo "<br/>meta_desc ".$meta_desc;
                echo "<br/>descripcion ".$descripcion;
                echo "<br/>h1  ".$datos["h1"];



                $category = new Category($idcat);

                $category->meta_title[$idlang] = $meta_title;
                $category->meta_description[$idlang] = str_replace("https://www.a-alvarez.com","",$meta_desc);
                $category->description[$idlang] = str_replace("https://www.a-alvarez.com","",$descripcion);
                $category->update();

                Db::getInstance()->Execute("REPLACE INTO aalv_appagebuilder_extracat(id_category, id_shop, id_lang, tituloh1) VALUES (".$idcat.",1,".$idlang.",'".$datos["h1"]."')");

            }
            else{
                echo "Falla $idcat" ;

            }



        }*/

    } catch (Exception $e) {

        $d = new DateTime;
        $stdout = fopen(dirname(__FILE__).'/seocategoriaserrores.txt', 'a');
        fwrite($stdout, $d->format("Y-m-d\TH:i:sP").' --- error '.$e->getMessage());
        fwrite($stdout, "\n");
        fwrite($stdout, ' --- datos '.$datos[0]);
        fwrite($stdout, "\n");
        fclose($stdout);

    }

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

function RecorrerDep($dbh, $id, $iddeporte)
{

    RecorreDeporte($dbh, $id, 1, 'es', $iddeporte);
    RecorreDeporte($dbh, $id, 2, 'en', $iddeporte);
    RecorreDeporte($dbh, $id, 3, 'fr', $iddeporte);
    RecorreDeporte($dbh, $id, 4, 'pt', $iddeporte);
    RecorreDeporte($dbh, $id, 5, 'de', $iddeporte);

}

function RecorreDeporte($dbh, $id, $idioma, $idiomaalvarez, $iddeporte)
{

    $cat = new Category($id);
    $colecctiocat = $cat->getAllChildren($idioma)->getAll();
    foreach ($colecctiocat as $category) {
        $enlace = $category->getLink(null, $idioma);
        buscarseo($dbh, end(explode('/', $enlace)), $category, $iddeporte, $idiomaalvarez, $idioma, $cat);
    }

}

try {

    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

/*
$rows = getdatarows($dbh,"SELECT * FROM textos_categorias where id_categoria is not null");
foreach($rows as $row){
    RellenarSeoCategorias($row, $dbh);
}*/

$textosarellenar = [];
$textosarellenar['es'] = 'Encuentra todos los productos de {CAT} en la tienda online de {DEPORTE} - Álvarez';
$textosarellenar['pt'] = 'Encontra todos os produtos de {CAT} na loja online de {DEPORTE} - Álvarez';
$textosarellenar['en'] = 'Find all inventaries of {CAT} in online shop of {DEPORTE} - Álvarez';
$textosarellenar['de'] = 'Finden Sie alle Produkte von {CAT} in Online-Shop {DEPORTE} - Álvarez';
$textosarellenar['fr'] = 'Trouve tous les produits de {CAT} dans la boutique online de {DEPORTE} - Álvarez';

RecorrerDep($dbh, 3, 1);
RecorrerDep($dbh, 4, 5);
RecorrerDep($dbh, 5, 6);
RecorrerDep($dbh, 6, 3);
RecorrerDep($dbh, 7, 4);
RecorrerDep($dbh, 8, 2);
RecorrerDep($dbh, 9, 9);
RecorrerDep($dbh, 10, 1395);
RecorrerDep($dbh, 11, 10);

echo 'acaba';

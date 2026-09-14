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

try {

    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

$rows = getdatarows($dbh, 'SELECT * FROM textos_categorias where id_categoria is not null');
foreach ($rows as $row) {
    RellenarSeoCategorias($row, $dbh);
}
echo 'acaba';

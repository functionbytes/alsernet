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

    echo '<br/>Categoria '.$category->id;
    echo '<br/>url '.$url;

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

        echo '<br/>No encuentra seo';

    }

    echo "<br/>title<br/> $meta_title";
    echo "<br/>meta_desc<br/> $meta_desc";
    echo "<br/>descripcion<br/> $descripcion";
    echo "<br/>h1<br/> $h1";

    echo '<br/>********************************************';

}

function RecorrerDep($dbh, $id, $iddeporte)
{

    RecorreDeporte($dbh, $id, 1, 'es', $iddeporte);
    // RecorreDeporte($dbh,$id,2,"en",$iddeporte);
    // RecorreDeporte($dbh,$id,3,"fr",$iddeporte);
    // RecorreDeporte($dbh,$id,4,"pt",$iddeporte);
    // RecorreDeporte($dbh,$id,5,"de",$iddeporte);

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

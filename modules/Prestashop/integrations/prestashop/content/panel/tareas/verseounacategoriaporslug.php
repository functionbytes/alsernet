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

function buscarseo($dbh, $url)
{

    global $textosarellenar;

    echo '<br/>url '.$url;

    $encuentra = false;
    $rows = getdatarows($dbh, "SELECT * FROM textos_categorias where url='".$url."' and idioma='es'");
    foreach ($rows as $row) {
        $encuentra = true;

        $meta_title = trim($row['title']);
        $meta_desc = trim($row['description']);
        $descripcion = $row['texto_superior'].$row['texto_inferior'];
        $h1 = trim($row['h1']);
        break;

    }

    echo "<br/>title<br/> $meta_title";
    echo "<br/>meta_desc<br/> $meta_desc";
    echo "<br/>descripcion<br/> $descripcion";
    echo "<br/>h1<br/> $h1";

    echo '<br/>********************************************';

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

buscarseo($dbh, Tools::getValue('slug'));

echo '<br/>acaba';

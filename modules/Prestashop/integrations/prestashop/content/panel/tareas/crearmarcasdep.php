<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

// <add key="aDSNMYSQL" value="DRIVER={MySQL ODBC 3.51 Driver};SERVER=82.223.36.198;DATABASE=psaddis_lacasadelosaromas;UID=psaddis_aromas;PWD=1@p.i5HS1y;OPTION=3;" />

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

function CrearMarcaDeporte($idmarca, $deporte, $dbh, $dbh2)
{

    // $idmarca = $datos['id_marca'];
    // $deporte = $datos['deporte'];

    // $buscarenseo="/".getfieldvalue($dbh, "select safe_name from marcas where id=".$idmarca);
    // $shortdesc="".getfieldvalue($dbh2,"SELECT texto_superior FROM textos_marcas where url='".$buscarenseo."'");
    // $desc="".getfieldvalue($dbh2,"SELECT texto_inferior FROM textos_marcas where url='".$buscarenseo."'");

    // $shortdesc=str_replace("https://www.a-alvarez.com", "", $shortdesc);
    // $desc=str_replace("https://www.a-alvarez.com", "", $desc);

    $categoriadeporte = Db::getInstance()->getValue('SELECT id_cat FROM aalv_category_import WHERE id_origen='.$deporte);

    if (''.$categoriadeporte != '') {

        $existe = ''.Db::getInstance()->getValue('select id from aalv_manufacturer_deporte where id_manufacturer='.$idmarca.' and id_category_deporte='.$categoriadeporte);

        if ($existe == '') {
            $sql = 'insert INTO aalv_manufacturer_deporte(id_manufacturer, id_category_deporte, destacado, orden, tiene_productos) VALUES ('.$idmarca.','.$categoriadeporte.',0,0,0)';
            Db::getInstance()->Execute($sql);
            $idmd = Db::getInstance()->Insert_ID();
            $sql = 'insert INTO aalv_manufacturer_deporte_lang(id, id_lang, texto_superior, texto_inferior) VALUES ('.$idmd.",1,'','')";
            Db::getInstance()->Execute($sql);
        } else {

        }

    } else {
        echo 'no existe '.$idmarca.' '.$deporte;
    }

}

try {

    $dsn = 'mysql:host=127.0.0.1;dbname=alvarez_migracion_db';
    $dbh = new PDO($dsn, 'alvarez_migracion_dbu', 'N4p42#l6d');
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

$rows = getdatarows($dbh, 'SELECT * FROM marcas_deporte order by id_marca');

$i = 1;

foreach ($rows as $row) {
    CrearMarcaDeporte($row['id_marca'], $row['deporte'], $dbh, $dbh2);
}

echo 'Proceso acabado';

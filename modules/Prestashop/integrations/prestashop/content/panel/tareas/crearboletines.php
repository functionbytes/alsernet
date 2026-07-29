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

// copyImg($manufacturer->id, null, $img_url, 'manufacturers');

function CrearBoletines($boletin, $dbh, $id_deporte, $id_idioma)
{

    $titulo = ''.$boletin['titulo'];
    $contenido = ''.$boletin['contenidos'];
    $visible = ''.$boletin['visible'];

    if ($visible == 'si') {
        $visible = '1';
    } else {
        $visible = '0';
    }

    $fecha = ''.$boletin['fecha'];
    $nombre_archivo = ''.$boletin['nombre_archivo'];

    Db::getInstance()->Execute('INSERT INTO aalv_boletines(id_deporte, titulo, contenido, fecha, nombre_fichero, id_idioma, visible) VALUES ('.$id_deporte.",'".$titulo."','".$contenido."','".$fecha."','".$nombre_archivo."',".$id_idioma.','.$visible.')');

    echo "Título $titulo <br/>";

}

try {

    $dsn = 'mysql:host=195.55.36.104;dbname=tienda';
    $dbh = new PDO($dsn, 'tiendalvad', 'Nov.299909');
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

Db::getInstance()->Execute('truncate table aalv_boletines');

$rows = getdatarows($dbh, 'SELECT titulo,contenidos,fecha,nombre_archivo,visible FROM caza_boletines');
foreach ($rows as $row) {
    CrearBoletines($row, $dbh, 5, 1);
}

$rows = getdatarows($dbh, 'SELECT titulo,contenidos,fecha,nombre_archivo,visible FROM caza_boletines_pt');
foreach ($rows as $row) {
    CrearBoletines($row, $dbh, 5, 4);
}

$rows = getdatarows($dbh, 'SELECT titulo,contenidos,fecha,nombre_archivo,visible FROM equitacion_boletines');
foreach ($rows as $row) {
    CrearBoletines($row, $dbh, 3, 1);
}

$rows = getdatarows($dbh, 'SELECT titulo,contenidos,fecha,nombre_archivo,visible FROM equitacion_boletines_pt');
foreach ($rows as $row) {
    CrearBoletines($row, $dbh, 3, 4);
}

$rows = getdatarows($dbh, 'SELECT titulo,contenidos,fecha,nombre_archivo,visible FROM esqui_boletines');
foreach ($rows as $row) {
    CrearBoletines($row, $dbh, 9, 1);
}

$rows = getdatarows($dbh, 'SELECT titulo,contenidos,fecha,nombre_archivo,visible FROM esqui_boletines_pt');
foreach ($rows as $row) {
    CrearBoletines($row, $dbh, 9, 4);
}

$rows = getdatarows($dbh, 'SELECT titulo,contenidos,fecha,nombre_archivo,visible FROM golf_boletines');
foreach ($rows as $row) {
    CrearBoletines($row, $dbh, 1, 1);
}

$rows = getdatarows($dbh, 'SELECT titulo,contenidos,fecha,nombre_archivo,visible FROM golf_boletines_pt');
foreach ($rows as $row) {
    CrearBoletines($row, $dbh, 1, 4);
}

$rows = getdatarows($dbh, 'SELECT titulo,contenidos,fecha,nombre_archivo,visible FROM nautica_boletines');
foreach ($rows as $row) {
    CrearBoletines($row, $dbh, 2, 1);
}

$rows = getdatarows($dbh, 'SELECT titulo,contenidos,fecha,nombre_archivo,visible FROM nautica_boletines_pt');
foreach ($rows as $row) {
    CrearBoletines($row, $dbh, 2, 4);
}

$rows = getdatarows($dbh, 'SELECT titulo,contenidos,fecha,nombre_archivo,visible FROM pesca_boletines');
foreach ($rows as $row) {
    CrearBoletines($row, $dbh, 6, 1);
}

$rows = getdatarows($dbh, 'SELECT titulo,contenidos,fecha,nombre_archivo,visible FROM pesca_boletines_pt');
foreach ($rows as $row) {
    CrearBoletines($row, $dbh, 6, 4);
}

$rows = getdatarows($dbh, 'SELECT titulo,contenidos,fecha,nombre_archivo,visible FROM sub_boletines');
foreach ($rows as $row) {
    CrearBoletines($row, $dbh, 4, 1);
}

$rows = getdatarows($dbh, 'SELECT titulo,contenidos,fecha,nombre_archivo,visible FROM sub_boletines_pt');
foreach ($rows as $row) {
    CrearBoletines($row, $dbh, 4, 4);
}

echo 'Proceso acabado';

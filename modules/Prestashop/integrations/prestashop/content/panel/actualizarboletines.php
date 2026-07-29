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

function getTitulo($boletin, $idioma)
{

    $numero = $boletin['id'];
    $mes = substr($boletin['fecha_creacion'], 5, 2);

    if ($idioma == '1') {

        $meses = [];
        $meses['01'] = 'Enero';
        $meses['02'] = 'Febrero';
        $meses['03'] = 'Marzo';
        $meses['04'] = 'Abril';
        $meses['05'] = 'Mayo';
        $meses['06'] = 'Junio';
        $meses['07'] = 'Julio';
        $meses['08'] = 'Agosto';
        $meses['09'] = 'Septiembre';
        $meses['10'] = 'Octubre';
        $meses['11'] = 'Noviembre';
        $meses['12'] = 'Diciembre';

        $titulo = 'Boletín número '.$numero.'. '.$meses[$mes].' '.substr($boletin['fecha_creacion'], 0, 4);
    } else {

        $meses = [];
        $meses['01'] = 'Janeiro';
        $meses['02'] = 'Fevereiro';
        $meses['03'] = 'março';
        $meses['04'] = 'Abril';
        $meses['05'] = 'Maio';
        $meses['06'] = 'Junho';
        $meses['07'] = 'Julho';
        $meses['08'] = 'Agosto';
        $meses['09'] = 'Setembro';
        $meses['10'] = 'Outubro';
        $meses['11'] = 'Novembro';
        $meses['12'] = 'Dezembro';

        $titulo = 'Boletim número '.$numero.'. '.$meses[$mes].' '.substr($boletin['fecha_creacion'], 0, 4);

    }

    return $titulo;

}

function CrearBoletines($boletin)
{

    $contenido = ''.$boletin['descripcion'];
    $visible = ''.$boletin['activo'];

    $fecha = ''.$boletin['fecha_creacion'];

    $deporte = ''.$boletin['deporte'];

    if ($deporte == 'HIPICA') {
        $id_deporte = '3';
    }
    if ($deporte == 'ESQUI') {
        $id_deporte = '9';
    }
    if ($deporte == 'NAUTICA') {
        $id_deporte = '2';
    }
    if ($deporte == 'PESCA') {
        $id_deporte = '6';
    }
    if ($deporte == 'BUCEO') {
        $id_deporte = '4';
    }
    if ($deporte == 'CAZA') {
        $id_deporte = '5';
    }
    if ($deporte == 'GOLF') {
        $id_deporte = '1';
    }

    $nombre_archivo = ''.$boletin['slug'].'-es.pdf';
    $titulo = getTitulo($boletin, '1');
    $id_idioma = '1';

    Db::getInstance()->Execute('INSERT INTO aalv_boletines(id_deporte, titulo, contenido, fecha, nombre_fichero, id_idioma, visible) VALUES ('.$id_deporte.",'".$titulo."','".$contenido."','".$fecha."','".$nombre_archivo."',".$id_idioma.','.$visible.')');

    $nombre_archivo = ''.$boletin['slug'].'-pt.pdf';
    $titulo = getTitulo($boletin, '4');
    $id_idioma = '4';

    Db::getInstance()->Execute('INSERT INTO aalv_boletines(id_deporte, titulo, contenido, fecha, nombre_fichero, id_idioma, visible) VALUES ('.$id_deporte.",'".$titulo."','".$contenido."','".$fecha."','".$nombre_archivo."',".$id_idioma.','.$visible.')');

}

try {

    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

$rows = getdatarows($dbh, 'select * from boletines where id>=66');
foreach ($rows as $row) {
    CrearBoletines($row);
    // dump($row);
}

echo 'Proceso acabado';

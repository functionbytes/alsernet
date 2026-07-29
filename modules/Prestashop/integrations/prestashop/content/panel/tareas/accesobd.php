<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

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

function addsql($texto)
{
    $stdout = fopen(dirname(__FILE__).'/corregiridiomas.txt', 'a');
    fwrite($stdout, $texto);
    fwrite($stdout, "\n");
    fclose($stdout);
}

try {

    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

// $armas = getfieldvalue($dbh, "SELECT count( distinct id_modelo) FROM producto where es_arma=1");

$rows = getdatarows($dbh, 'SELECT distinct id_modelo FROM producto where es_arma=1');

$spreadsheet = new Spreadsheet;

$fila = 1;
foreach ($rows as $row) {
    $spreadsheet->getActiveSheet()->setCellValue('A'.$fila, $row['id_modelo']);
    $fila = $fila + 1;
}

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="modelosarmaserp.xlsx"');
$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save('php://output');

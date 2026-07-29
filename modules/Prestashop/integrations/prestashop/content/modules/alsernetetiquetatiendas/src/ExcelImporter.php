<?php
namespace Alsernet\Etiquetas;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelImporter
{
    /**
     * Lee XLSX/XLS y devuelve array de arrays con columnas:
     * REFERENCIA | DESCRIPCION | PVP RECOM PROV | PVP
     */
    public function read($file)
    {
        $reader = IOFactory::createReaderForFile($file);
        $spread = $reader->load($file);
        $sheet = $spread->getActiveSheet();
        $rows = [];
        foreach ($sheet->getRowIterator(2) as $row) { // asume fila 1 = cabeceras
            $c = [];
            $c['referencia']  = (string)$sheet->getCell('A'.$row->getRowIndex())->getCalculatedValue();
            $c['descripcion'] = (string)$sheet->getCell('B'.$row->getRowIndex())->getCalculatedValue();
            $c['pvprp']       = (string)$sheet->getCell('C'.$row->getRowIndex())->getCalculatedValue();
            $c['pvp']         = (string)$sheet->getCell('D'.$row->getRowIndex())->getCalculatedValue();
            if (implode('', $c) !== '') { $rows[] = $c; }
        }
        return $rows;
    }
}
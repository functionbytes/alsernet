<?php

namespace Modules\PriceLabels\Services;

use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Modules\PriceLabels\Imports\PriceLabelRowsImport;

class PriceLabelExcelImportService
{
    /**
     * Lee el Excel usando el mapeo de columnas de la plantilla (fila 1 = cabecera).
     *
     * @param  UploadedFile|string  $file  archivo subido, o ruta relativa dentro de $disk
     * @param  array<string, string>  $columnMap  clave de campo => letra de columna Excel (ej. ['referencia' => 'A'])
     * @return array<int, array<string, string>>
     */
    public function read(UploadedFile|string $file, array $columnMap, ?string $disk = null): array
    {
        $import = new PriceLabelRowsImport($columnMap);

        Excel::import($import, $file, $disk);

        return $import->rows;
    }
}

<?php

namespace App\Http\Controllers\Managers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Warehouse\WarehouseLocationStyle;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class WarehouseLocationStylesImportController extends Controller
{
    /**
     * Show import form
     * Ruta: GET /manager/warehouse/warehouses/{warehouse_uid}/styles/import
     */
    public function showForm($warehouse_uid = null)
    {
        return view('managers.views.warehouse.styles.import', [
            'warehouse_uid' => $warehouse_uid,
        ]);
    }

    /**
     * Process Excel import
     * Ruta: POST /manager/warehouse/warehouses/{warehouse_uid}/styles/import
     */
    public function import(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $file = $validated['file'];
            $filePath = $file->path();

            // Cargar archivo Excel
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Validar encabezados
            $headers = array_map('strtolower', array_map('trim', $rows[0]));
            $expectedHeaders = ['code', 'name', 'faces', 'default_levels', 'default_sections', 'description', 'available'];

            if (!$this->validateHeaders($headers, $expectedHeaders)) {
                return back()->with('error', 'El archivo no tiene los encabezados correctos.
                    Esperado: ' . implode(', ', $expectedHeaders));
            }

            $imported = 0;
            $errors = [];
            $warnings = [];

            // Procesar filas (saltando encabezado)
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];

                // Saltar filas vacías
                if (empty($row[0]) && empty($row[1])) {
                    continue;
                }

                try {
                    $styleData = $this->parseRow($row, $headers);

                    // Validar datos
                    $validation = $this->validateStyleData($styleData);
                    if ($validation['valid'] === false) {
                        $warnings[] = "Fila " . ($i + 1) . ": " . $validation['message'];
                        continue;
                    }

                    // Verificar si ya existe
                    $existing = WarehouseLocationStyle::where('code', $styleData['code'])->first();

                    if ($existing) {
                        // Actualizar
                        $existing->update([
                            'name' => $styleData['name'],
                            'faces' => $styleData['faces'],
                            'default_levels' => $styleData['default_levels'],
                            'default_sections' => $styleData['default_sections'],
                            'description' => $styleData['description'],
                            'available' => $styleData['available'],
                        ]);
                    } else {
                        // Crear nuevo
                        WarehouseLocationStyle::create([
                            'uid' => Str::uuid(),
                            'code' => $styleData['code'],
                            'name' => $styleData['name'],
                            'faces' => $styleData['faces'],
                            'default_levels' => $styleData['default_levels'],
                            'default_sections' => $styleData['default_sections'],
                            'description' => $styleData['description'],
                            'available' => $styleData['available'],
                        ]);
                    }

                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Fila " . ($i + 1) . ": " . $e->getMessage();
                }
            }

            // Preparar respuesta
            $message = "✅ Se importaron $imported estilos correctamente";

            if (!empty($warnings)) {
                $message .= "\n⚠️ Advertencias: " . count($warnings) . " filas ignoradas";
            }

            if (!empty($errors)) {
                return back()->with('error', "❌ Errores en importación:\n" . implode("\n", $errors));
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', 'Error al procesar archivo: ' . $e->getMessage());
        }
    }

    /**
     * Download Excel template
     * Ruta: GET /manager/warehouse/styles/template
     */
    public function downloadTemplate($type = 'all')
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Encabezados
        $headers = ['Code', 'Name', 'Faces', 'Default Levels', 'Default Sections', 'Description', 'Available'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
            $sheet->getColumnDimensionByColumn($col + 1)->setWidth(20);
        }

        // Estilos en negrilla
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->getStyle('A1:G1')->getFill()->setFillType('solid')->getStartColor()->setRGB('4472C4');
        $sheet->getStyle('A1:G1')->getFont()->getColor()->setRGB('FFFFFF');

        // Agregar ejemplos según tipo
        $examples = $this->getExamples($type);
        $row = 2;

        foreach ($examples as $example) {
            $sheet->setCellValueByColumnAndRow(1, $row, $example['code']);
            $sheet->setCellValueByColumnAndRow(2, $row, $example['name']);
            $sheet->setCellValueByColumnAndRow(3, $row, $example['faces']);
            $sheet->setCellValueByColumnAndRow(4, $row, $example['default_levels']);
            $sheet->setCellValueByColumnAndRow(5, $row, $example['default_sections']);
            $sheet->setCellValueByColumnAndRow(6, $row, $example['description']);
            $sheet->setCellValueByColumnAndRow(7, $row, $example['available']);
            $row++;
        }

        // Agregar hoja de instrucciones
        $instructionsSheet = $spreadsheet->createSheet();
        $instructionsSheet->setTitle('Instructions');

        $instructions = [
            ['INSTRUCCIONES DE IMPORTACIÓN'],
            [],
            ['CAMPOS REQUERIDOS:'],
            ['Code', 'Código único del estilo (sin espacios)'],
            ['Name', 'Nombre descriptivo del estilo'],
            ['Faces', 'Número de caras: 1, 2 ó 4'],
            ['Default Levels', 'Número de niveles por defecto'],
            ['Default Sections', 'Número de secciones por defecto'],
            ['Description', 'Descripción del estilo (opcional)'],
            ['Available', 'Disponible: 1=Sí, 0=No'],
            [],
            ['EJEMPLOS DE FACES:'],
            ['1', 'front'],
            ['2', 'front,back'],
            ['4', 'front,back,left,right'],
            [],
            ['NOTAS:'],
            ['- Cada fila debe tener al menos Code y Name'],
            ['- Faces debe contener valores válidos separados por coma'],
            ['- Si Code ya existe, se actualizará'],
            ['- Default Levels y Default Sections deben ser números > 0'],
        ];

        foreach ($instructions as $rowIndex => $instruction) {
            foreach ($instruction as $colIndex => $value) {
                $instructionsSheet->setCellValueByColumnAndRow($colIndex + 1, $rowIndex + 1, $value);
            }
        }

        // Descargar
        $filename = 'warehouse_styles_template_' . date('Y-m-d') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Obtener ejemplos según tipo
     */
    private function getExamples($type = 'all')
    {
        $examples = [];

        if ($type === 'all' || $type === '1') {
            $examples[] = [
                'code' => 'STY-1CARA-A',
                'name' => 'Estilo 1 Cara - Tipo A',
                'faces' => 'front',
                'default_levels' => 3,
                'default_sections' => 5,
                'description' => 'Estante de una sola cara frontal',
                'available' => 1,
            ];
        }

        if ($type === 'all' || $type === '2') {
            $examples[] = [
                'code' => 'STY-2CARAS-A',
                'name' => 'Estilo 2 Caras - Tipo A',
                'faces' => 'front,back',
                'default_levels' => 3,
                'default_sections' => 5,
                'description' => 'Estante accesible desde dos lados',
                'available' => 1,
            ];
            $examples[] = [
                'code' => 'STY-2CARAS-B',
                'name' => 'Estilo 2 Caras - Tipo B',
                'faces' => 'left,right',
                'default_levels' => 4,
                'default_sections' => 6,
                'description' => 'Estante con acceso lateral',
                'available' => 1,
            ];
        }

        if ($type === 'all' || $type === '4') {
            $examples[] = [
                'code' => 'STY-4CARAS',
                'name' => 'Estilo 4 Caras - Isla',
                'faces' => 'front,back,left,right',
                'default_levels' => 3,
                'default_sections' => 5,
                'description' => 'Estante tipo isla accesible desde 4 lados',
                'available' => 1,
            ];
        }

        return $examples;
    }

    /**
     * Validar encabezados
     */
    private function validateHeaders($actual, $expected)
    {
        $actualLower = array_map('strtolower', $actual);
        $expectedLower = array_map('strtolower', $expected);

        // Verificar que al menos tengan los campos requeridos
        foreach (['code', 'name', 'faces'] as $required) {
            if (!in_array($required, $actualLower)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Parsear fila de Excel
     */
    private function parseRow($row, $headers)
    {
        $data = [];
        foreach ($headers as $index => $header) {
            $data[$header] = trim($row[$index] ?? '');
        }

        // Procesar faces
        $facesStr = $data['faces'] ?? '';
        $faces = array_filter(array_map('trim', explode(',', $facesStr)));

        return [
            'code' => $data['code'] ?? '',
            'name' => $data['name'] ?? '',
            'faces' => json_encode($faces),
            'default_levels' => (int) ($data['default_levels'] ?? 3),
            'default_sections' => (int) ($data['default_sections'] ?? 5),
            'description' => $data['description'] ?? '',
            'available' => (bool) (int) ($data['available'] ?? 1),
        ];
    }

    /**
     * Validar datos del estilo
     */
    private function validateStyleData($data)
    {
        if (empty($data['code'])) {
            return ['valid' => false, 'message' => 'Code es requerido'];
        }

        if (empty($data['name'])) {
            return ['valid' => false, 'message' => 'Name es requerido'];
        }

        $faces = json_decode($data['faces'], true);
        if (empty($faces)) {
            return ['valid' => false, 'message' => 'Faces es requerido (formato: front o front,back)'];
        }

        $validFaces = ['front', 'back', 'left', 'right'];
        foreach ($faces as $face) {
            if (!in_array($face, $validFaces)) {
                return ['valid' => false, 'message' => "Face inválida: $face (válidas: " . implode(', ', $validFaces) . ")"];
            }
        }

        if ($data['default_levels'] < 1) {
            return ['valid' => false, 'message' => 'Default Levels debe ser > 0'];
        }

        if ($data['default_sections'] < 1) {
            return ['valid' => false, 'message' => 'Default Sections debe ser > 0'];
        }

        return ['valid' => true];
    }
}

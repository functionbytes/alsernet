<?php

namespace Modules\Attention\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Servicio de exportación de PQRSF
 *
 * Gestiona la exportación de PQRSF a diferentes formatos (Excel, PDF, CSV)
 * con generación de tokens de descarga seguros y limpieza automática de archivos antiguos.
 */
class AttentionExportService
{
    /**
     * Directorio de almacenamiento de exports
     */
    protected const EXPORT_DIRECTORY = 'exports/attentions';

    /**
     * Tiempo de expiración de exports en horas
     */
    protected const EXPORT_EXPIRATION_HOURS = 24;

    /**
     * Genera un token único para descarga segura
     *
     * @return string Token único de 32 caracteres
     */
    public static function generateExportToken(): string
    {
        return Str::random(32);
    }

    /**
     * Exporta PQRSF a formato Excel
     *
     * @param  Collection  $attentions  Colección de PQRSF
     * @param  array  $columns  Columnas a exportar (vacío = todas por defecto)
     * @return string Ruta del archivo generado
     *
     * @throws \Exception Si falla la exportación
     */
    public static function exportToExcel(Collection $attentions, array $columns = []): string
    {
        try {
            $token = self::generateExportToken();
            $filename = "attentions_export_{$token}.xlsx";
            $path = self::EXPORT_DIRECTORY.'/'.$filename;

            $columns = empty($columns) ? self::getDefaultColumns() : $columns;

            $export = new class($attentions, $columns) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\ShouldAutoSize, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithStyles
            {
                protected $attentions;

                protected $columns;

                public function __construct($attentions, $columns)
                {
                    $this->attentions = $attentions;
                    $this->columns = $columns;
                }

                public function collection()
                {
                    return $this->attentions->map(function ($attention) {
                        $row = [];
                        foreach ($this->columns as $column) {
                            $row[$column] = self::formatValue($attention, $column);
                        }

                        return $row;
                    });
                }

                public function headings(): array
                {
                    return array_map(function ($column) {
                        return ucwords(str_replace('_', ' ', $column));
                    }, $this->columns);
                }

                public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
                {
                    return [
                        1 => ['font' => ['bold' => true, 'size' => 12]],
                    ];
                }

                protected static function formatValue($attention, $column)
                {
                    if (in_array($column, ['created_at', 'updated_at', 'resolved_at', 'closed_at'])) {
                        return $attention->$column ? $attention->$column->format('Y-m-d H:i:s') : 'N/A';
                    }

                    return $attention->$column ?? 'N/A';
                }
            };

            Excel::store($export, $path, 'local');

            self::saveExportMetadata($token, 'xlsx', $path);

            Log::info('Excel export created', ['token' => $token, 'records' => $attentions->count()]);

            return $token;
        } catch (\Exception $e) {
            Log::error('Excel export failed', ['error' => $e->getMessage()]);
            throw new \Exception('Error al exportar a Excel: '.$e->getMessage());
        }
    }

    /**
     * Exporta PQRSF a formato PDF
     *
     * @param  Collection  $attentions  Colección de PQRSF
     * @param  array  $columns  Columnas a exportar (vacío = todas por defecto)
     * @return string Token del archivo generado
     *
     * @throws \Exception Si falla la exportación
     */
    public static function exportToPdf(Collection $attentions, array $columns = []): string
    {
        try {
            $token = self::generateExportToken();
            $filename = "attentions_export_{$token}.pdf";
            $path = self::EXPORT_DIRECTORY.'/'.$filename;

            $columns = empty($columns) ? self::getDefaultColumns() : $columns;

            $data = [
                'attentions' => $attentions,
                'columns' => $columns,
                'generated_at' => now()->format('Y-m-d H:i:s'),
                'total' => $attentions->count(),
            ];

            $pdf = Pdf::loadView('attention::exports.pdf', $data)
                ->setPaper('a4', 'landscape')
                ->setOption('margin-top', 10)
                ->setOption('margin-bottom', 10)
                ->setOption('margin-left', 10)
                ->setOption('margin-right', 10);

            Storage::put($path, $pdf->output());

            self::saveExportMetadata($token, 'pdf', $path);

            Log::info('PDF export created', ['token' => $token, 'records' => $attentions->count()]);

            return $token;
        } catch (\Exception $e) {
            Log::error('PDF export failed', ['error' => $e->getMessage()]);
            throw new \Exception('Error al exportar a PDF: '.$e->getMessage());
        }
    }

    /**
     * Exporta PQRSF a formato CSV
     *
     * @param  Collection  $attentions  Colección de PQRSF
     * @param  array  $columns  Columnas a exportar (vacío = todas por defecto)
     * @return string Token del archivo generado
     *
     * @throws \Exception Si falla la exportación
     */
    public static function exportToCsv(Collection $attentions, array $columns = []): string
    {
        try {
            $token = self::generateExportToken();
            $filename = "attentions_export_{$token}.csv";
            $path = self::EXPORT_DIRECTORY.'/'.$filename;

            $columns = empty($columns) ? self::getDefaultColumns() : $columns;

            $content = '';
            $handle = fopen('php://temp', 'r+');

            // Headers
            fputcsv($handle, array_map(function ($column) {
                return ucwords(str_replace('_', ' ', $column));
            }, $columns));

            // Data rows
            foreach ($attentions as $attention) {
                $row = [];
                foreach ($columns as $column) {
                    $value = $attention->$column;

                    if ($value instanceof Carbon) {
                        $value = $value->format('Y-m-d H:i:s');
                    }

                    $row[] = $value ?? 'N/A';
                }
                fputcsv($handle, $row);
            }

            rewind($handle);
            $content = stream_get_contents($handle);
            fclose($handle);

            Storage::put($path, $content);

            self::saveExportMetadata($token, 'csv', $path);

            Log::info('CSV export created', ['token' => $token, 'records' => $attentions->count()]);

            return $token;
        } catch (\Exception $e) {
            Log::error('CSV export failed', ['error' => $e->getMessage()]);
            throw new \Exception('Error al exportar a CSV: '.$e->getMessage());
        }
    }

    /**
     * Guarda metadata del export
     *
     * @param  string  $token  Token único
     * @param  string  $format  Formato del archivo
     * @param  string  $path  Ruta del archivo
     */
    protected static function saveExportMetadata(string $token, string $format, string $path): void
    {
        $metadataPath = self::EXPORT_DIRECTORY."/{$token}.meta";
        $metadata = [
            'token' => $token,
            'format' => $format,
            'path' => $path,
            'created_at' => now()->toDateTimeString(),
            'expires_at' => now()->addHours(self::EXPORT_EXPIRATION_HOURS)->toDateTimeString(),
        ];

        Storage::put($metadataPath, json_encode($metadata));
    }

    /**
     * Obtiene archivo de export por token
     *
     * @param  string  $token  Token único de descarga
     * @return array|null Array con 'path' y 'format' o null si no existe/expiró
     */
    public static function getExportFile(string $token): ?array
    {
        try {
            $metadataPath = self::EXPORT_DIRECTORY."/{$token}.meta";

            if (! Storage::exists($metadataPath)) {
                return null;
            }

            $metadata = json_decode(Storage::get($metadataPath), true);

            // Verificar expiración
            if (Carbon::parse($metadata['expires_at'])->isPast()) {
                self::deleteExport($token, $metadata);

                return null;
            }

            // Verificar que el archivo existe
            if (! Storage::exists($metadata['path'])) {
                return null;
            }

            return [
                'path' => Storage::path($metadata['path']),
                'format' => $metadata['format'],
                'filename' => basename($metadata['path']),
            ];
        } catch (\Exception $e) {
            Log::error('Error getting export file', ['token' => $token, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Limpia exports antiguos (>24h)
     *
     * @return int Cantidad de archivos eliminados
     */
    public static function cleanOldExports(): int
    {
        try {
            $deleted = 0;
            $metaFiles = Storage::files(self::EXPORT_DIRECTORY);

            foreach ($metaFiles as $metaFile) {
                if (! str_ends_with($metaFile, '.meta')) {
                    continue;
                }

                $metadata = json_decode(Storage::get($metaFile), true);

                if (Carbon::parse($metadata['expires_at'])->isPast()) {
                    self::deleteExport($metadata['token'], $metadata);
                    $deleted++;
                }
            }

            Log::info('Old exports cleaned', ['deleted' => $deleted]);

            return $deleted;
        } catch (\Exception $e) {
            Log::error('Error cleaning old exports', ['error' => $e->getMessage()]);

            return 0;
        }
    }

    /**
     * Elimina un export específico
     *
     * @param  string  $token  Token del export
     * @param  array  $metadata  Metadata del export
     */
    protected static function deleteExport(string $token, array $metadata): void
    {
        try {
            // Eliminar archivo
            if (isset($metadata['path']) && Storage::exists($metadata['path'])) {
                Storage::delete($metadata['path']);
            }

            // Eliminar metadata
            $metadataPath = self::EXPORT_DIRECTORY."/{$token}.meta";
            if (Storage::exists($metadataPath)) {
                Storage::delete($metadataPath);
            }
        } catch (\Exception $e) {
            Log::error('Error deleting export', ['token' => $token, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Obtiene columnas por defecto para exportación
     *
     * @return array Lista de nombres de columnas
     */
    protected static function getDefaultColumns(): array
    {
        return [
            'radicado',
            'type',
            'category',
            'customer_name',
            'subject',
            'status',
            'created_at',
            'resolved_at',
        ];
    }
}

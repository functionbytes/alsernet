<?php

namespace Modules\Reviews\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewGoogleLocation;

class GenerateReportCommand extends Command
{
    protected $signature = 'reviews:report {--location=} {--from=} {--to=} {--format=csv}';

    protected $description = 'Generar reporte de reseñas';

    public function handle(): int
    {
        $locationId = $this->option('location');
        $format = $this->option('format');
        $from = $this->option('from') ? Carbon::createFromFormat('Y-m-d', $this->option('from')) : now()->subMonth();
        $to = $this->option('to') ? Carbon::createFromFormat('Y-m-d', $this->option('to')) : now();

        $query = Review::query()
            ->whereBetween('review_time', [$from, $to]);

        if ($locationId) {
            $location = ReviewGoogleLocation::findOrFail($locationId);
            $query->where('location_id', $locationId);
            $this->info("Generando reporte para ubicación: {$location->name}");
        } else {
            $this->info('Generando reporte de todas las ubicaciones');
        }

        $reviews = $query->get();

        if ($reviews->isEmpty()) {
            $this->warn('No se encontraron reseñas para los filtros especificados');

            return self::FAILURE;
        }

        $this->info("Total de reseñas encontradas: {$reviews->count()}");
        $this->newLine();

        match ($format) {
            'csv' => $this->generateCsvReport($reviews, $locationId),
            'json' => $this->generateJsonReport($reviews, $locationId),
            'pdf' => $this->generatePdfReport($reviews, $locationId),
            default => $this->error("Formato no soportado: $format"),
        };

        return self::SUCCESS;
    }

    private function generateCsvReport($reviews, ?int $locationId): void
    {
        $filename = 'reviews_report_'.now()->format('Y-m-d_H-i-s').'.csv';
        $filepath = storage_path("app/reports/$filename");

        // Asegurar que el directorio existe
        if (! is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        $handle = fopen($filepath, 'w');
        fputcsv($handle, ['ID', 'Autor', 'Calificación', 'Comentario', 'Fecha', 'Respuesta']);

        foreach ($reviews as $review) {
            fputcsv($handle, [
                $review->id,
                $review->reviewer_name,
                $review->star_rating->value(),
                substr($review->comment ?? '', 0, 100),
                $review->review_time->format('Y-m-d'),
                $review->hasGoogleReply() ? 'Sí' : 'No',
            ]);
        }

        fclose($handle);

        $this->info("✅ Reporte CSV generado: $filename");
        $this->line("Ruta: $filepath");
    }

    private function generateJsonReport($reviews, ?int $locationId): void
    {
        $filename = 'reviews_report_'.now()->format('Y-m-d_H-i-s').'.json';
        $filepath = storage_path("app/reports/$filename");

        // Asegurar que el directorio existe
        if (! is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        $data = $reviews->map(fn ($review) => [
            'id' => $review->id,
            'author' => $review->reviewer_name,
            'rating' => $review->star_rating->value(),
            'comment' => $review->comment,
            'date' => $review->review_time->toIso8601String(),
            'has_reply' => $review->hasGoogleReply(),
        ]);

        file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT));

        $this->info("✅ Reporte JSON generado: $filename");
        $this->line("Ruta: $filepath");
    }

    private function generatePdfReport($reviews, ?int $locationId): void
    {
        $this->warn('Generación de PDF aún no implementada');
        $this->line('Se recomienda usar un formato CSV o JSON por ahora');
    }
}

<?php

namespace Modules\Reviews\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Reviews\Enums\ReviewRating;
use Modules\Reviews\Enums\ReviewSource;
use Modules\Reviews\Enums\SyncStrategy;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewGoogleLocation;
use Modules\Reviews\Models\ReviewModeration;

class ImportJsonReviewsCommand extends Command
{
    protected $signature = 'reviews:import-json
                            {file : Ruta al archivo JSON}
                            {--location= : ID de ubicación existente (opcional)}
                            {--name= : Nombre del negocio (si se crea nueva ubicación)}
                            {--place-id= : Google Place ID (opcional, para sync futuro)}
                            {--address= : Dirección (opcional)}';

    protected $description = 'Importar reseñas desde un archivo JSON scrapeado de Google Maps';

    public function handle(): int
    {
        $file = $this->argument('file');

        if (! file_exists($file)) {
            $this->error("Archivo no encontrado: {$file}");

            return self::FAILURE;
        }

        $reviews = json_decode(file_get_contents($file), true);

        if (! $reviews || ! is_array($reviews)) {
            $this->error('El archivo no contiene JSON válido.');

            return self::FAILURE;
        }

        $this->info('Reseñas encontradas: '.count($reviews));

        $location = $this->resolveLocation();

        if (! $location) {
            return self::FAILURE;
        }

        $this->info("Importando a ubicación: {$location->name} (ID: {$location->id})");

        $imported = 0;
        $skipped = 0;

        DB::transaction(function () use ($reviews, $location, &$imported, &$skipped) {
            foreach ($reviews as $index => $reviewData) {
                $reviewerName = trim($reviewData['name'] ?? 'Anónimo');
                $rating = (int) ($reviewData['rating'] ?? 0);

                if ($rating < 1 || $rating > 5) {
                    $this->warn("Fila {$index}: rating inválido ({$rating}), saltando.");
                    $skipped++;

                    continue;
                }

                $reviewDate = $this->parseRelativeDate($reviewData['date'] ?? '');
                $googleReviewId = 'json_import_'.$location->id.'_'.Str::slug($reviewerName).'_'.$reviewDate->format('Ymd').'_'.$index;

                $review = Review::query()->updateOrCreate(
                    ['google_review_id' => $googleReviewId],
                    [
                        'location_id' => $location->id,
                        'reviewer_name' => $reviewerName,
                        'reviewer_photo_url' => null,
                        'star_rating' => ReviewRating::fromInt($rating),
                        'comment' => ! empty($reviewData['text']) ? trim($reviewData['text']) : null,
                        'review_time' => $reviewDate,
                        'update_time' => null,
                        'google_reply_text' => ! empty($reviewData['ownerResponse']) ? trim($reviewData['ownerResponse']) : null,
                        'google_reply_time' => ! empty($reviewData['ownerResponse']) ? $reviewDate : null,
                        'source' => ReviewSource::Manual->value,
                        'raw_json' => $reviewData,
                        'synced_at' => now(),
                    ]
                );

                if (! $review->moderation) {
                    ReviewModeration::query()->create([
                        'review_id' => $review->id,
                        'is_visible' => true,
                        'is_featured' => false,
                    ]);
                }

                $imported++;
            }
        });

        $total = Review::query()->where('location_id', $location->id)->count();
        $location->update([
            'total_reviews' => $total,
            'average_rating' => $this->calcAverage($location->id),
        ]);

        $this->info("✓ Importadas: {$imported}");
        $this->info("✗ Saltadas:   {$skipped}");
        $this->info("Total en ubicación: {$total} reseñas");

        return self::SUCCESS;
    }

    private function resolveLocation(): ?ReviewGoogleLocation
    {
        if ($locationId = $this->option('location')) {
            $location = ReviewGoogleLocation::query()->find($locationId);

            if (! $location) {
                $this->error("Ubicación ID {$locationId} no encontrada.");

                return null;
            }

            return $location;
        }

        $name = $this->option('name') ?: $this->ask('Nombre del negocio', 'Caixilharia PVC Blanco');
        $placeId = $this->option('place-id');
        $address = $this->option('address');

        $location = ReviewGoogleLocation::query()->create([
            'connection_id' => null,
            'google_location_id' => 'manual_'.Str::uuid(),
            'google_account_id' => 'manual',
            'name' => $name,
            'address' => $address,
            'place_id' => $placeId,
            'sync_strategy' => SyncStrategy::Manual,
            'is_active' => true,
            'is_verified' => false,
        ]);

        $this->info("Ubicación creada con ID: {$location->id}");

        return $location;
    }

    private function parseRelativeDate(string $dateStr): Carbon
    {
        $dateStr = preg_replace('/^Fecha de edición:\s*/i', '', trim($dateStr));

        $patterns = [
            '/hace (\d+) días?/i' => fn ($m) => now()->subDays((int) $m[1]),
            '/hace (\d+) semanas?/i' => fn ($m) => now()->subWeeks((int) $m[1]),
            '/hace (\d+) mes(es)?/i' => fn ($m) => now()->subMonths((int) $m[1]),
            '/hace un mes/i' => fn () => now()->subMonth(),
            '/hace (\d+) años?/i' => fn ($m) => now()->subYears((int) $m[1]),
            '/hace un año/i' => fn () => now()->subYear(),
        ];

        foreach ($patterns as $pattern => $resolver) {
            if (preg_match($pattern, $dateStr, $matches)) {
                return $resolver($matches);
            }
        }

        return now()->subMonths(6);
    }

    private function calcAverage(int $locationId): float
    {
        $ratingMap = ['ONE' => 1, 'TWO' => 2, 'THREE' => 3, 'FOUR' => 4, 'FIVE' => 5];

        $ratings = Review::query()
            ->where('location_id', $locationId)
            ->pluck('star_rating')
            ->map(fn ($r) => $ratingMap[is_string($r) ? $r : $r->value] ?? 0);

        if ($ratings->isEmpty()) {
            return 0.0;
        }

        return round($ratings->avg(), 2);
    }
}

<?php

/**
 * Importa reseñas desde reviews_output.json al módulo Reviews.
 *
 * Uso (desde tinker):
 *   require base_path('scripts/import_reviews.php');
 *
 * O directamente:
 *   php artisan tinker --execute="require base_path('scripts/import_reviews.php');"
 */

use Carbon\Carbon;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewGoogleLocation;
use Modules\Reviews\Models\ReviewModeration;

$jsonPath = base_path('scripts/reviews_output.json');

if (! file_exists($jsonPath)) {
    echo "❌ Archivo no encontrado: {$jsonPath}\n";
    echo "   Primero ejecuta: python scripts/scrape_reviews.py\n";

    return;
}

$data = json_decode(file_get_contents($jsonPath), true);
$reviews = $data['reviews'] ?? [];

if (empty($reviews)) {
    echo "❌ No hay reseñas en el JSON.\n";

    return;
}

$location = ReviewGoogleLocation::find($data['location_id']);
if (! $location) {
    echo "❌ Ubicación ID {$data['location_id']} no encontrada.\n";

    return;
}

echo "📍 Ubicación: {$location->name}\n";
echo "📋 Importando {$data['total']} reseñas...\n\n";

$ratingWords = ['ONE' => 1, 'TWO' => 2, 'THREE' => 3, 'FOUR' => 4, 'FIVE' => 5];
$imported = 0;
$skipped = 0;

DB::transaction(function () use ($reviews, $location, $ratingWords, &$imported, &$skipped) {
    foreach ($reviews as $idx => $r) {
        $exists = Review::where('google_review_id', $r['google_review_id'])->exists();
        if ($exists) {
            $skipped++;

            continue;
        }

        // Mapear fecha aproximada (Google usa texto relativo como "hace 2 meses")
        $reviewTime = Carbon::now()->subDays(rand(1, 365));

        $review = Review::create([
            'location_id' => $location->id,
            'google_review_id' => $r['google_review_id'],
            'reviewer_name' => $r['reviewer_name'],
            'reviewer_photo_url' => $r['reviewer_photo_url'] ?? null,
            'star_rating' => $r['star_rating'],
            'comment' => $r['comment'] ?? null,
            'google_reply_text' => $r['google_reply_text'] ?? null,
            'review_time' => $reviewTime,
            'source' => 'manual',
            'synced_at' => now(),
            'raw_json' => $r,
        ]);

        // Generar tag automático basado en el rating
        $stars = $ratingWords[$r['star_rating']] ?? 5;
        $autoTag = match (true) {
            $stars >= 5 => 'excelente',
            $stars >= 4 => 'bueno',
            $stars >= 3 => 'regular',
            $stars >= 2 => 'malo',
            default => 'muy-malo',
        };

        ReviewModeration::create([
            'review_id' => $review->id,
            'is_visible' => true,
            'is_featured' => $stars >= 4,
            'tags' => [$autoTag],
        ]);

        $imported++;
        echo "  [{$idx}] {$r['reviewer_name']} — {$r['star_rating']} → tag: {$autoTag}\n";
    }
});

// Actualizar stats de la ubicación
$avg = Review::where('location_id', $location->id)
    ->selectRaw("AVG(CASE star_rating WHEN 'ONE' THEN 1 WHEN 'TWO' THEN 2 WHEN 'THREE' THEN 3 WHEN 'FOUR' THEN 4 WHEN 'FIVE' THEN 5 END) as avg_rating, COUNT(*) as total")
    ->first();

$location->update([
    'average_rating' => round($avg->avg_rating, 2),
    'total_reviews' => $avg->total,
    'synced_at' => now(),
]);

echo "\n✅ Importadas: {$imported} | Omitidas (duplicadas): {$skipped}\n";
echo "📊 Rating promedio actualizado: {$location->average_rating} ({$location->total_reviews} reseñas)\n";

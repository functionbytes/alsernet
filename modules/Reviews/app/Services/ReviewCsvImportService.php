<?php

namespace Modules\Reviews\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Reviews\Enums\ReviewRating;
use Modules\Reviews\Enums\ReviewSource;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewGoogleLocation;
use Modules\Reviews\Models\ReviewModeration;

class ReviewCsvImportService
{
    public function import(ReviewGoogleLocation $location, string $filePath): int
    {
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            Log::warning('ReviewCsvImportService: could not open file', ['path' => $filePath]);

            return 0;
        }

        // Strip UTF-8 BOM if present (common in Excel CSV exports)
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $headers = fgetcsv($handle);

        if (! $headers) {
            fclose($handle);

            return 0;
        }

        $headers = array_map('trim', $headers);
        $imported = 0;
        $rowNumber = 1;

        DB::transaction(function () use ($handle, $headers, $location, &$imported, &$rowNumber) {
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                if (count($row) !== count($headers)) {
                    Log::warning('ReviewCsvImportService: skipping row with wrong column count', [
                        'row' => $rowNumber,
                        'expected' => count($headers),
                        'got' => count($row),
                    ]);

                    continue;
                }

                $data = array_combine($headers, $row);

                if (empty(trim($data['reviewer_name'] ?? ''))) {
                    Log::warning('ReviewCsvImportService: skipping row missing reviewer_name', ['row' => $rowNumber]);

                    continue;
                }

                $starRatingInt = (int) ($data['star_rating'] ?? 0);

                if ($starRatingInt < 1 || $starRatingInt > 5) {
                    Log::warning('ReviewCsvImportService: skipping row with invalid star_rating', [
                        'row' => $rowNumber,
                        'value' => $data['star_rating'] ?? null,
                    ]);

                    continue;
                }

                $reviewerName = trim($data['reviewer_name']);
                $reviewDate = ! empty($data['review_date'] ?? '')
                    ? Carbon::parse($data['review_date'])
                    : now();

                $googleReviewId = 'csv_import_'.$location->id.'_'.Str::slug($reviewerName).'_'.$reviewDate->format('Ymd');

                $review = Review::query()->updateOrCreate(
                    ['google_review_id' => $googleReviewId],
                    [
                        'location_id' => $location->id,
                        'reviewer_name' => $reviewerName,
                        'reviewer_photo_url' => null,
                        'star_rating' => ReviewRating::fromInt($starRatingInt),
                        'comment' => ! empty($data['comment'] ?? '') ? trim($data['comment']) : null,
                        'review_time' => $reviewDate,
                        'update_time' => null,
                        'google_reply_text' => ! empty($data['reply_text'] ?? '') ? trim($data['reply_text']) : null,
                        'google_reply_time' => ! empty($data['reply_text'] ?? '') ? now() : null,
                        'source' => ReviewSource::Csv->value,
                        'synced_at' => now(),
                    ]
                );

                if (! $review->moderation) {
                    ReviewModeration::query()->create([
                        'review_id' => $review->id,
                        'is_visible' => config('reviews.general.default_moderation_visible', true),
                        'is_featured' => false,
                    ]);
                }

                $imported++;
            }
        });

        fclose($handle);

        Log::info('ReviewCsvImportService: import complete', [
            'location_id' => $location->id,
            'imported' => $imported,
            'total_rows' => $rowNumber - 1,
        ]);

        return $imported;
    }
}

<?php

namespace Modules\Reviews\Services;

use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Reviews\Exports\ReviewsExport;
use Modules\Reviews\Models\Review;

class ReviewExportService
{
    /**
     * Queue an Excel export and return the target filepath.
     * Caller is responsible for chaining a notification job.
     */
    public function queueExcelExport(array $filters = [], ?int $userId = null): string
    {
        $userSegment = $userId !== null ? '_'.$userId.'_' : '_';
        $filename = 'reviews'.$userSegment.now()->format('Y-m-d_His').'.xlsx';
        $filepath = 'exports/'.$filename;

        Storage::disk('local')->makeDirectory('exports');

        Excel::store(new ReviewsExport($filters), $filepath, 'local');

        return $filepath;
    }

    /**
     * Store a CSV export synchronously within a queued job context and return the relative path.
     * The user ID is embedded in the filename for ownership verification on download.
     */
    public function exportToCsv(array $filters = [], ?int $userId = null): string
    {
        $userSegment = $userId !== null ? '_'.$userId.'_' : '_';
        $filename = 'reviews'.$userSegment.now()->format('Y-m-d_His').'.csv';
        $filepath = 'exports/'.$filename;

        Storage::disk('local')->makeDirectory('exports');

        Excel::store(new ReviewsExport($filters), $filepath, 'local', \Maatwebsite\Excel\Excel::CSV);

        return $filepath;
    }

    public function exportToArray(array $filters = []): array
    {
        return ReviewQueryBuilder::applyFilters(
            Review::query()->with(['location', 'moderation']),
            $filters
        )
            ->latest('review_time')
            ->get()
            ->map(fn ($review) => [
                'ID' => $review->id,
                'Location' => $review->location->name,
                'Reviewer' => $review->reviewer_name,
                'Rating' => $review->star_rating->value(),
                'Comment' => $review->comment,
                'Review Date' => $review->review_time->format('Y-m-d H:i:s'),
                'Has Reply' => $review->hasGoogleReply() ? 'Yes' : 'No',
                'Reply Text' => $review->google_reply_text,
                'Visible' => $review->isVisible() ? 'Yes' : 'No',
                'Featured' => $review->isFeatured() ? 'Yes' : 'No',
                'Synced At' => $review->synced_at?->format('Y-m-d H:i:s'),
            ])
            ->toArray();
    }
}

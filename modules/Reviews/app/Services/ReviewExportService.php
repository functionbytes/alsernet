<?php

namespace Modules\Reviews\Services;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Reviews\Exports\ReviewsExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReviewExportService
{
    public function exportToExcel(array $filters = []): BinaryFileResponse
    {
        $filename = 'reviews_'.now()->format('Y-m-d_His').'.xlsx';

        return Excel::download(new ReviewsExport($filters), $filename);
    }

    public function exportToCsv(array $filters = []): string
    {
        $filename = 'reviews_'.now()->format('Y-m-d_His').'.csv';
        $path = storage_path('app/exports/'.$filename);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        Excel::store(new ReviewsExport($filters), 'exports/'.$filename, 'local', \Maatwebsite\Excel\Excel::CSV);

        return $path;
    }

    public function exportToArray(array $filters = []): array
    {
        $reviews = $this->getFilteredReviews($filters);

        return $reviews->map(function ($review) {
            return [
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
            ];
        })->toArray();
    }

    private function getFilteredReviews(array $filters = []): Collection
    {
        $query = \Modules\Reviews\Models\Review::query()
            ->with(['location', 'moderation']);

        if (isset($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (isset($filters['star_rating'])) {
            $query->where('star_rating', $filters['star_rating']);
        }

        if (isset($filters['has_comment'])) {
            $filters['has_comment']
                ? $query->whereNotNull('comment')
                : $query->whereNull('comment');
        }

        if (isset($filters['has_reply'])) {
            $filters['has_reply']
                ? $query->whereNotNull('google_reply_text')
                : $query->whereNull('google_reply_text');
        }

        if (isset($filters['is_visible'])) {
            $query->whereHas('moderation', function ($q) use ($filters) {
                $q->where('is_visible', $filters['is_visible']);
            });
        }

        if (isset($filters['date_from'])) {
            $query->where('review_time', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('review_time', '<=', $filters['date_to']);
        }

        return $query->latest('review_time')->get();
    }
}

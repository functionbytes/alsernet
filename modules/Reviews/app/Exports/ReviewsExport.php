<?php

namespace Modules\Reviews\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Reviews\Models\Review;

class ReviewsExport implements FromQuery, WithChunkReading, WithHeadings, WithMapping
{
    public function __construct(private array $filters = []) {}

    public function query(): Builder
    {
        $query = Review::query()->with(['location', 'moderation']);

        if (isset($this->filters['location_id'])) {
            $query->where('location_id', $this->filters['location_id']);
        }

        if (isset($this->filters['star_rating'])) {
            $query->where('star_rating', $this->filters['star_rating']);
        }

        if (isset($this->filters['date_from'])) {
            $query->where('review_time', '>=', $this->filters['date_from']);
        }

        if (isset($this->filters['date_to'])) {
            $query->where('review_time', '<=', $this->filters['date_to']);
        }

        return $query->latest('review_time');
    }

    public function chunkSize(): int
    {
        return 200;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Location',
            'Reviewer',
            'Rating',
            'Comment',
            'Review Date',
            'Has Reply',
            'Reply Text',
            'Visible',
            'Featured',
            'Synced At',
        ];
    }

    public function map($review): array
    {
        return [
            $review->id,
            $review->location->name,
            $review->reviewer_name,
            $review->star_rating->value(),
            $review->comment,
            $review->review_time->format('Y-m-d H:i:s'),
            $review->hasGoogleReply() ? 'Yes' : 'No',
            $review->google_reply_text,
            $review->isVisible() ? 'Yes' : 'No',
            $review->isFeatured() ? 'Yes' : 'No',
            $review->synced_at?->format('Y-m-d H:i:s'),
        ];
    }
}

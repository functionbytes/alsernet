<?php

namespace Modules\Ecommerce\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Ecommerce\Models\NewsletterSubscriber;

class NewsletterSubscriberExport implements FromQuery, WithChunkReading, WithHeadings, WithMapping
{
    public function query(): Builder
    {
        return NewsletterSubscriber::query()->latest();
    }

    public function chunkSize(): int
    {
        return 200;
    }

    public function headings(): array
    {
        return ['Email', 'Nombre', 'Estado', 'Origen', 'Suscrito el'];
    }

    public function map($subscriber): array
    {
        return [
            $subscriber->email,
            $subscriber->name ?? '',
            $subscriber->status,
            $subscriber->source ?? '',
            $subscriber->subscribed_at?->format('Y-m-d H:i:s') ?? '',
        ];
    }
}

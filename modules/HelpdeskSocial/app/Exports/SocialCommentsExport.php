<?php

namespace Modules\HelpdeskSocial\Exports;

use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\HelpdeskSocial\Models\SocialComment;

class SocialCommentsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        private readonly ?string $since = null,
        private readonly ?string $until = null,
        private readonly ?string $platform = null,
        private readonly ?string $status = null,
    ) {}

    public function collection(): Collection
    {
        return SocialComment::query()
            ->when($this->since, fn ($q) => $q->whereDate('posted_at', '>=', $this->since))
            ->when($this->until, fn ($q) => $q->whereDate('posted_at', '<=', $this->until))
            ->when($this->platform, fn ($q) => $q->where('platform', $this->platform))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->orderByDesc('posted_at')
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID', 'Plataforma', 'Autor', 'Contenido', 'Intent',
            'Urgencia', 'Status', 'Mención', 'Respuesta',
            'Respuesta automática', 'Creado', 'Publicado',
        ];
    }

    public function map(mixed $row): array
    {
        return [
            $row->id,
            $row->platform,
            $row->author_name,
            $row->body,
            $row->intent,
            $row->urgency,
            $row->status,
            $row->is_mention ? 'Sí' : 'No',
            $row->reply_body ? mb_substr($row->reply_body, 0, 100) : '',
            $row->auto_replied ? 'Sí' : 'No',
            $row->created_at?->format('Y-m-d H:i'),
            $row->posted_at?->format('Y-m-d H:i'),
        ];
    }
}

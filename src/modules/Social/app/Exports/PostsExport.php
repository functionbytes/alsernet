<?php

namespace Modules\Social\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Modules\Social\Models\Post;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PostsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $accountId;

    protected $filters;

    public function __construct($accountId, array $filters = [])
    {
        $this->accountId = $accountId;
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Post::where('account_id', $this->accountId)
            ->with(['socialAccount', 'campaign', 'creator']);

        if (isset($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (isset($this->filters['date_from'])) {
            $query->where('created_at', '>=', $this->filters['date_from']);
        }

        if (isset($this->filters['date_to'])) {
            $query->where('created_at', '<=', $this->filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Red Social',
            'Campaña',
            'Estado',
            'Contenido',
            'Programado',
            'Publicado',
            'Likes',
            'Comentarios',
            'Compartidos',
            'Engagement Total',
            'Creado Por',
            'Fecha Creación',
        ];
    }

    public function map($post): array
    {
        return [
            $post->id,
            $post->socialAccount?->name ?? 'N/A',
            $post->campaign?->name ?? 'Sin campaña',
            $post->status->value,
            substr($post->content, 0, 100).'...',
            $post->scheduled_at?->format('Y-m-d H:i') ?? 'N/A',
            $post->published_at?->format('Y-m-d H:i') ?? 'N/A',
            $post->likes_count,
            $post->comments_count,
            $post->shares_count,
            $post->getTotalEngagement(),
            $post->creator?->name ?? 'N/A',
            $post->created_at->format('Y-m-d H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

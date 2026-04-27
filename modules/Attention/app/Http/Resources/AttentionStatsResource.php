<?php

namespace Modules\Attention\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttentionStatsResource extends JsonResource
{
    /**
     * @param  array{total: int, pending: int, inProcess: int, resolved: int, closed: int, thisMonth: int, overdueCount?: int}  $resource
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'total' => $this->resource['total'] ?? 0,
            'pending' => $this->resource['pending'] ?? 0,
            'inProcess' => $this->resource['inProcess'] ?? 0,
            'resolved' => $this->resource['resolved'] ?? 0,
            'closed' => $this->resource['closed'] ?? 0,
            'thisMonth' => $this->resource['thisMonth'] ?? 0,
            'overdueCount' => $this->resource['overdueCount'] ?? 0,
        ];
    }
}

<?php

namespace Modules\Erp\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlbaranResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->idalbarancli_central,
            'idAlbaran' => $this->idalbarancli,
            'numero' => $this->nalbarancli,
            'estado' => $this->estado,
            'tipo' => $this->tipo,
            'observaciones' => $this->utf8Clean($this->observaciones),
            'fechaAlbaran' => $this->falbaran?->format('Y-m-d H:i:s'),
            'fechaCreacion' => $this->fcreacion?->format('Y-m-d H:i:s'),
            'fechaModificacion' => $this->fmodificacion?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Clean UTF-8 encoding from Oracle strings.
     */
    private function utf8Clean(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }
}

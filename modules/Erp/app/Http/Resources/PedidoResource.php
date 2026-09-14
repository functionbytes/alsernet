<?php

namespace Modules\Erp\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PedidoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->idpedidocli_central,
            'idPedido' => $this->idpedidocli,
            'numero' => $this->npedidocli,
            'estado' => $this->estado,
            'observaciones' => $this->utf8Clean($this->observaciones),
            'fechaPedido' => $this->fpedido?->format('Y-m-d H:i:s'),
            'fechaPrevista' => $this->fprevista?->format('Y-m-d'),
            'fechaServido' => $this->fservido?->format('Y-m-d H:i:s'),
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

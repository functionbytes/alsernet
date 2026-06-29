<?php

namespace Modules\Erp\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TarifaLineaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->idtarifa_linea,
            'estado' => $this->estado,
            'unidadesDesde' => $this->udesde,
            'baseImponible' => $this->baseimp,
            'pvp' => $this->pvp,
            'pvpExento' => $this->pvp_exento,
            'descuento' => $this->dto,
            'mostrarDescuento' => $this->mostrar_dto,
            'motivoDescuento' => $this->motivo_dto,
            'generaPuntosFidelidad' => $this->genera_puntos_fid,
            'aplicarOfertas' => $this->aplicar_ofertas,

            // Timestamps
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

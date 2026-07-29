<?php

namespace Modules\Erp\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubfamiliaClResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->idsubfamilia_cl,
            'descripcion' => $this->utf8Clean($this->descripcion),
            'descripcionCorta' => $this->utf8Clean($this->desc_corta),
            'estado' => $this->estado,
            'esCartucheria' => $this->escartucheria,
            'esMunicionMetalica' => $this->esmunicionmetalica,
            'mostrarLotes' => $this->mostrarlotes,

            // Familia padre
            'familia' => $this->when($this->familiaCl, [
                'id' => $this->familiaCl?->idfamilia_cl,
                'descripcion' => $this->utf8Clean($this->familiaCl?->descripcion),
            ]),

            // Grupos de esta subfamilia
            'grupos' => $this->when(
                $this->relationLoaded('grupos'),
                $this->grupos->map(fn ($grupo) => [
                    'id' => $grupo->idgrupo_cl,
                    'descripcion' => $this->utf8Clean($grupo->descripcion),
                    'descripcionCorta' => $this->utf8Clean($grupo->desc_corta),
                    'estado' => $grupo->estado,
                    'prefijo' => $this->utf8Clean($grupo->prefijo),
                ])
            ),

            // Estadísticas
            'estadisticas' => $this->when(
                $this->relationLoaded('grupos'),
                [
                    'totalGrupos' => $this->grupos->count(),
                    'gruposActivos' => $this->grupos->where('estado', 1)->count(),
                ]
            ),

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

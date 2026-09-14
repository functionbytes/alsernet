<?php

namespace Modules\Erp\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoriaClResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->idcategoria_cl,
            'descripcion' => $this->utf8Clean($this->descripcion),
            'descripcionCorta' => $this->utf8Clean($this->desc_corta),
            'estado' => $this->estado,
            'apareceInfStock' => $this->aparece_inf_stock,

            // Deporte asociado
            'deporte' => $this->when($this->deporteCl, [
                'id' => $this->deporteCl?->iddeporte_cl,
                'nombre' => $this->utf8Clean($this->deporteCl?->descripcion),
            ]),

            // Familias de esta categoría
            'familias' => $this->when(
                $this->relationLoaded('familias'),
                $this->familias->map(fn ($familia) => [
                    'id' => $familia->idfamilia_cl,
                    'descripcion' => $this->utf8Clean($familia->descripcion),
                    'descripcionCorta' => $this->utf8Clean($familia->desc_corta),
                    'estado' => $familia->estado,
                ])
            ),

            // Estadísticas
            'estadisticas' => $this->when(
                $this->relationLoaded('familias'),
                [
                    'totalFamilias' => $this->familias->count(),
                    'familiasActivas' => $this->familias->where('estado', 1)->count(),
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

<?php

namespace Modules\Erp\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FamiliaClResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->idfamilia_cl,
            'descripcion' => $this->utf8Clean($this->descripcion),
            'descripcionCorta' => $this->utf8Clean($this->desc_corta),
            'estado' => $this->estado,
            'sonArmas' => $this->sonarmas,
            'sonArmasFogueo' => $this->sonarmasfogueo,
            'sonCartuchos' => $this->soncartuchos,

            // Categoría padre
            'categoria' => $this->when($this->categoriaCl, [
                'id' => $this->categoriaCl?->idcategoria_cl,
                'descripcion' => $this->utf8Clean($this->categoriaCl?->descripcion),
            ]),

            // Subfamilias de esta familia
            'subfamilias' => $this->when(
                $this->relationLoaded('subfamilias'),
                $this->subfamilias->map(fn ($subfamilia) => [
                    'id' => $subfamilia->idsubfamilia_cl,
                    'descripcion' => $this->utf8Clean($subfamilia->descripcion),
                    'descripcionCorta' => $this->utf8Clean($subfamilia->desc_corta),
                    'estado' => $subfamilia->estado,
                ])
            ),

            // Estadísticas
            'estadisticas' => $this->when(
                $this->relationLoaded('subfamilias'),
                [
                    'totalSubfamilias' => $this->subfamilias->count(),
                    'subfamiliasActivas' => $this->subfamilias->where('estado', 1)->count(),
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

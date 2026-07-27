<?php

namespace Modules\Erp\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GrupoClResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->idgrupo_cl,
            'descripcion' => $this->utf8Clean($this->descripcion),
            'descripcionCorta' => $this->utf8Clean($this->desc_corta),
            'estado' => $this->estado,
            'prefijo' => $this->utf8Clean($this->prefijo),
            'proximoNumero' => $this->prox_num,
            'pedirNumeroSerie' => $this->pedir_numero_serie,
            'intrastat' => $this->utf8Clean($this->intrastat),

            // Subfamilia padre
            'subfamilia' => $this->when($this->subfamiliaCl, [
                'id' => $this->subfamiliaCl?->idsubfamilia_cl,
                'descripcion' => $this->utf8Clean($this->subfamiliaCl?->descripcion),
            ]),

            // Artículos de este grupo
            'articulos' => $this->when(
                $this->relationLoaded('articulos'),
                fn () => $this->articulos->map(fn ($articulo) => [
                    'id' => $articulo->idarticulo,
                    'codigo' => $this->utf8Clean($articulo->codigo),
                    'descripcion' => $this->utf8Clean($articulo->descripcion),
                    'estado' => $articulo->estado,
                ])
            ),

            // Estadísticas
            'estadisticas' => $this->when(
                $this->relationLoaded('articulos'),
                fn () => [
                    'totalArticulos' => $this->articulos->count(),
                    'articulosActivos' => $this->articulos->where('estado', 1)->count(),
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

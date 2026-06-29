<?php

namespace Modules\Erp\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProveedorProductosResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->idproveedor,
            'nombre' => $this->utf8Clean($this->nombre),
            'cif' => $this->utf8Clean($this->cif),
            'email' => $this->email,
            'telefono' => $this->telefono1,
            'estado' => $this->estado,

            // Productos del proveedor con categorías
            'productos' => $this->when(
                $this->relationLoaded('artiprovs'),
                $this->artiprovs->map(fn ($artiprov) => [
                    'idArtiprov' => $artiprov->idartiprov,
                    'codigo' => $this->utf8Clean($artiprov->codigo),
                    'descripcion' => $this->utf8Clean($artiprov->descripcion),
                    'pcosto' => $artiprov->pcosto,
                    'porDefecto' => $artiprov->pordefecto,

                    // Artículo relacionado con su jerarquía
                    'articulo' => $artiprov->articulo ? [
                        'id' => $artiprov->articulo->idarticulo,
                        'codigo' => $this->utf8Clean($artiprov->articulo->codigo),
                        'descripcion' => $this->utf8Clean($artiprov->articulo->descripcion),

                        // Categoría del artículo (a través de Grupo → Subfamilia → Familia → Categoría)
                        'categoria' => $artiprov->articulo->grupoCl?->subfamiliaCl?->familiaCl?->categoriaCl ? [
                            'id' => $artiprov->articulo->grupoCl->subfamiliaCl->familiaCl->categoriaCl->idcategoria_cl,
                            'descripcion' => $this->utf8Clean($artiprov->articulo->grupoCl->subfamiliaCl->familiaCl->categoriaCl->descripcion),
                        ] : null,
                    ] : null,
                ])
            ),

            // Estadísticas
            'estadisticas' => $this->when(
                $this->relationLoaded('artiprovs'),
                [
                    'totalProductos' => $this->artiprovs->count(),
                    'productosActivos' => $this->artiprovs->where('estado', 1)->count(),
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

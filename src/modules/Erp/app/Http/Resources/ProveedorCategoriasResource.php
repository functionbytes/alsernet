<?php

namespace Modules\Erp\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProveedorCategoriasResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        // Extraer categorías únicas de todos los productos
        $categorias = $this->artiprovs
            ->map(fn ($artiprov) => $artiprov->articulo?->grupoCl?->subfamiliaCl?->familiaCl?->categoriaCl)
            ->filter() // Eliminar nulls
            ->unique('idcategoria_cl')
            ->values();

        return [
            'id' => $this->idproveedor,
            'label' => $this->nombre,
            'cif' => $this->cif,
            'email' => $this->email,
            'available' => $this->estado,
            'created' => $this->fcreacion?->format('Y-m-d H:i:s'),
            'updated' => $this->fmodificacion?->format('Y-m-d H:i:s'),

            'id' => $this->idproveedor,
            'nombre' => $this->utf8Clean($this->nombre),
            'cif' => $this->utf8Clean($this->cif),
            'email' => $this->email,
            'estado' => $this->estado,

            // Categorías que maneja este proveedor
            'categorias' => $categorias->map(fn ($categoria) => [
                'id' => $categoria->idcategoria_cl,
                'descripcion' => $this->utf8Clean($categoria->descripcion),

                // Contar productos en esta categoría
                'totalProductos' => $this->artiprovs->filter(function ($artiprov) use ($categoria) {
                    return $artiprov->articulo?->grupoCl?->subfamiliaCl?->familiaCl?->categoriaCl?->idcategoria_cl === $categoria->idcategoria_cl;
                })->count(),
            ]),

            // Estadísticas generales
            'estadisticas' => [
                'totalCategorias' => $categorias->count(),
                'totalProductos' => $this->artiprovs->count(),
            ],

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

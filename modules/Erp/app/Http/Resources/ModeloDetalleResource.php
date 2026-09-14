<?php

namespace Modules\Erp\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModeloDetalleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->idmodelo,
            'codigo' => $this->utf8Clean($this->codigo),
            'nombre' => $this->utf8Clean($this->nombre),
            'nombreWeb' => null,
            'descripcion' => $this->utf8Clean($this->descripcion),
            'estado' => $this->estado,
            'estadoPublicadoWeb' => $this->estado_publicado_web,
            'ventaTelefono' => $this->venta_telefono,
            'precioConsultarFicha' => $this->precio_consultar_ficha,

            // Marca asociada
            'marca' => $this->when($this->marca, [
                'id' => $this->marca?->idmarca,
                'codigo' => $this->utf8Clean($this->marca?->codigo),
                'nombre' => $this->utf8Clean($this->marca?->descripcion),
            ]),

            // Grupo asociado
            'grupo' => $this->when($this->grupoCl, [
                'id' => $this->grupoCl?->idgrupo_cl,
                'descripcion' => $this->utf8Clean($this->grupoCl?->descripcion),
                'prefijo' => $this->utf8Clean($this->grupoCl?->prefijo),
            ]),

            // Artículos de este modelo
            'articulos' => $this->when(
                $this->relationLoaded('articulos'),
                $this->articulos->map(fn ($articulo) => [
                    'id' => $articulo->idarticulo,
                    'codigo' => $this->utf8Clean($articulo->codigo),
                    'descripcion' => $this->utf8Clean($articulo->descripcion),
                    'estado' => $articulo->estado,
                    'precioBase' => $articulo->precio_base,
                ])
            ),

            // Estadísticas
            'estadisticas' => $this->when(
                $this->relationLoaded('articulos'),
                [
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

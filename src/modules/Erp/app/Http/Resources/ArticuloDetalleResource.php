<?php

namespace Modules\Erp\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticuloDetalleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->idarticulo,
            'codigo' => $this->utf8Clean($this->codigo),
            'codigoBarras' => $this->utf8Clean($this->codbar),
            'descripcion' => $this->utf8Clean($this->descripcion),
            'estado' => $this->estado,
            'precioMedio' => $this->preciomedio,
            'precioUltimaCompra' => $this->precioultcompra,
            'fechaUltimaCompra' => $this->fprecioultcompra?->format('Y-m-d H:i:s'),

            // Marca y Modelo
            'marca' => $this->when($this->marca, [
                'id' => $this->marca?->idmarca,
                'nombre' => $this->marca?->descripcion,
            ]),
            'modelo' => $this->when($this->modelo, [
                'id' => $this->modelo?->idmodelo,
                'nombre' => $this->modelo?->descripcion,
            ]),

            // Subfamilia
            'subfamilia' => $this->when($this->subfamilia, [
                'id' => $this->subfamilia?->idsubfamilia_cl,
                'nombre' => $this->subfamilia?->descripcion,
            ]),

            // Tipo de artículo
            'tipoArticulo' => $this->when($this->tipoArticulo, [
                'id' => $this->tipoArticulo?->idtipoart,
                'nombre' => $this->tipoArticulo?->descripcion,
            ]),

            // Tarifas
            'tarifas' => $this->when(
                $this->relationLoaded('tarifasCabecera'),
                TarifaCabeceraResource::collection($this->tarifasCabecera)
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

        // Remove any malformed UTF-8 characters
        return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }
}

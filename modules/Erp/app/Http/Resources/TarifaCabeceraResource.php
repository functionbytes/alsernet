<?php

namespace Modules\Erp\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TarifaCabeceraResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->idtarifa_cabecera,
            'estado' => $this->estado,
            'tarifaBase' => $this->tarifa_base,
            'tarifaCalculada' => $this->tarifa_calculada,
            'importeExento' => $this->importe_exento,
            'porcentajeIva' => $this->porc_iva,
            'fechaInicio' => $this->finicio?->format('Y-m-d H:i:s'),
            'fechaFin' => $this->ffin?->format('Y-m-d H:i:s'),

            // Almacén
            'almacen' => $this->when($this->almacen, [
                'id' => $this->almacen?->idalmacen,
                'nombre' => $this->almacen?->descripcion,
            ]),

            // Región país
            'regionPais' => $this->when($this->regpais, [
                'id' => $this->regpais?->idregpais,
                'nombre' => $this->regpais?->descripcion,
            ]),

            // Líneas de tarifa
            'lineas' => $this->when(
                $this->relationLoaded('tarifasLinea'),
                TarifaLineaResource::collection($this->tarifasLinea)
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

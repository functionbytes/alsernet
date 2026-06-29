<?php

namespace Modules\Erp\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoriaClienteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->idcategoria_cliente,
            'descripcion' => $this->utf8Clean($this->descripcion),
            'estado' => $this->estado,

            // Líneas de configuración
            'lineas' => $this->when(
                $this->relationLoaded('lineas'),
                fn () => $this->lineas->map(fn ($linea) => [
                    'id' => $linea->idlcategoria_cliente,
                    'fechaLimite' => $linea->flimite?->format('Y-m-d'),
                    'factorLiquidaPunto' => $linea->factor_liquida_punto,
                    'factorGeneraPunto' => $linea->factor_genera_punto,
                    'estado' => $linea->estado,
                ])
            ),

            // Clientes de esta categoría (solo si se cargó la relación)
            'clientes' => $this->when(
                $this->relationLoaded('clientes'),
                fn () => $this->clientes->map(fn ($cliente) => [
                    'id' => $cliente->idcliente,
                    'nombre' => $this->utf8Clean($cliente->nombre),
                    'apellidos' => $this->utf8Clean($cliente->apellidos),
                    'email' => $this->utf8Clean($cliente->email),
                    'estado' => $cliente->estado,
                ])
            ),

            // Estadísticas
            'estadisticas' => $this->when(
                $this->relationLoaded('clientes'),
                fn () => [
                    'totalClientes' => $this->clientes->count(),
                    'clientesActivos' => $this->clientes->where('estado', 1)->count(),
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

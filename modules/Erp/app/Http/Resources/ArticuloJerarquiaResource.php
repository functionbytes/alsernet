<?php

namespace Modules\Erp\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticuloJerarquiaResource extends JsonResource
{
    /**
     * Transform the resource into an array con jerarquía completa.
     */
    public function toArray(Request $request): array
    {
        return [
            // Información del artículo
            'articulo' => [
                'id' => $this->idarticulo,
                'codigo' => $this->utf8Clean($this->codigo),
                'descripcion' => $this->utf8Clean($this->descripcion),
                'estado' => $this->estado,
                'precioMedio' => $this->preciomedio ?? null,
            ],

            // Jerarquía completa de clasificación
            'jerarquia' => [
                // Nivel 1: Grupo
                'grupo' => $this->when($this->grupoCl, [
                    'id' => $this->grupoCl?->idgrupo_cl,
                    'descripcion' => $this->utf8Clean($this->grupoCl?->descripcion),
                    'prefijo' => $this->utf8Clean($this->grupoCl?->prefijo),
                ]),

                // Nivel 2: Subfamilia (a través de Grupo)
                'subfamilia' => $this->when($this->grupoCl?->subfamiliaCl, [
                    'id' => $this->grupoCl?->subfamiliaCl?->idsubfamilia_cl,
                    'descripcion' => $this->utf8Clean($this->grupoCl?->subfamiliaCl?->descripcion),
                    'esCartucheria' => $this->grupoCl?->subfamiliaCl?->escartucheria,
                ]),

                // Nivel 3: Familia (a través de Subfamilia)
                'familia' => $this->when($this->grupoCl?->subfamiliaCl?->familiaCl, [
                    'id' => $this->grupoCl?->subfamiliaCl?->familiaCl?->idfamilia_cl,
                    'descripcion' => $this->utf8Clean($this->grupoCl?->subfamiliaCl?->familiaCl?->descripcion),
                    'sonArmas' => $this->grupoCl?->subfamiliaCl?->familiaCl?->sonarmas,
                ]),

                // Nivel 4: Categoría (a través de Familia)
                'categoria' => $this->when($this->grupoCl?->subfamiliaCl?->familiaCl?->categoriaCl, [
                    'id' => $this->grupoCl?->subfamiliaCl?->familiaCl?->categoriaCl?->idcategoria_cl,
                    'descripcion' => $this->utf8Clean($this->grupoCl?->subfamiliaCl?->familiaCl?->categoriaCl?->descripcion),
                ]),

                // Nivel 5: Deporte (a través de Categoría)
                'deporte' => $this->when($this->grupoCl?->subfamiliaCl?->familiaCl?->categoriaCl?->deporteCl, [
                    'id' => $this->grupoCl?->subfamiliaCl?->familiaCl?->categoriaCl?->deporteCl?->iddeporte_cl,
                    'nombre' => $this->utf8Clean($this->grupoCl?->subfamiliaCl?->familiaCl?->categoriaCl?->deporteCl?->descripcion),
                ]),
            ],

            // Marca y Modelo (relaciones directas)
            'marca' => $this->when($this->marca, [
                'id' => $this->marca?->idmarca,
                'codigo' => $this->utf8Clean($this->marca?->codigo),
                'nombre' => $this->utf8Clean($this->marca?->descripcion),
            ]),

            'modelo' => $this->when($this->modelo, [
                'id' => $this->modelo?->idmodelo,
                'codigo' => $this->utf8Clean($this->modelo?->codigo),
                'nombre' => $this->utf8Clean($this->modelo?->nombre),
            ]),

            // Tipo de artículo
            'tipoArticulo' => $this->when($this->tipoArticulo, [
                'id' => $this->tipoArticulo?->idtipoart,
                'descripcion' => $this->utf8Clean($this->tipoArticulo?->descripcion),
            ]),

            // Breadcrumb (ruta completa para UI)
            'breadcrumb' => $this->buildBreadcrumb(),
        ];
    }

    /**
     * Construir breadcrumb para navegación
     */
    private function buildBreadcrumb(): ?string
    {
        $parts = [];

        if ($this->grupoCl?->subfamiliaCl?->familiaCl?->categoriaCl?->deporteCl) {
            $parts[] = $this->utf8Clean($this->grupoCl->subfamiliaCl->familiaCl->categoriaCl->deporteCl->descripcion);
        }

        if ($this->grupoCl?->subfamiliaCl?->familiaCl?->categoriaCl) {
            $parts[] = $this->utf8Clean($this->grupoCl->subfamiliaCl->familiaCl->categoriaCl->descripcion);
        }

        if ($this->grupoCl?->subfamiliaCl?->familiaCl) {
            $parts[] = $this->utf8Clean($this->grupoCl->subfamiliaCl->familiaCl->descripcion);
        }

        if ($this->grupoCl?->subfamiliaCl) {
            $parts[] = $this->utf8Clean($this->grupoCl->subfamiliaCl->descripcion);
        }

        if ($this->grupoCl) {
            $parts[] = $this->utf8Clean($this->grupoCl->descripcion);
        }

        return ! empty($parts) ? implode(' > ', $parts) : null;
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

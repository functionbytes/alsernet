<?php

namespace Modules\Erp\Services;

use Illuminate\Support\Facades\DB;

/**
 * Jerarquía de catálogo grupo → subfamilia → familia → categoría → deporte.
 *
 * Sustituye a las cadenas de relaciones Eloquent (grupoCl.subfamiliaCl...)
 * que costaban 4-5 consultas Oracle por nivel y, en los artículos, una por
 * cada artículo (N+1). Aquí es UNA consulta con JOIN para los grupos que
 * falten en caché; la jerarquía cambia muy poco, así que cada grupo se
 * cachea una hora.
 */
class CatalogHierarchyService
{
    private const TTL = 3600;

    private const CACHE_PREFIX = 'erp:catalog_hierarchy:grupo:';

    /**
     * @param  array<int, int|string|null>  $grupoIds
     * @return array<int, array{
     *     idgrupo_cl: int, idsubfamilia_cl: ?int, idfamilia_cl: ?int, idcategoria_cl: ?int, iddeporte_cl: ?string,
     *     familia: ?array{id: int, description: ?string, description_short: ?string, available: mixed},
     *     deporte: ?array{id: int, description: ?string, description_short: ?string, available: mixed}
     * }>
     */
    public function forGroups(array $grupoIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $grupoIds))));
        if ($ids === []) {
            return [];
        }

        $keys = array_map(fn (int $id) => self::CACHE_PREFIX.$id, $ids);
        $cached = cache()->many($keys);

        $result = [];
        $missing = [];
        foreach ($ids as $i => $id) {
            $hit = $cached[$keys[$i]] ?? null;
            if (is_array($hit)) {
                $result[$id] = $hit;
            } else {
                $missing[] = $id;
            }
        }

        if ($missing !== []) {
            $fresh = [];
            // Oracle admite como máximo 1000 elementos en un IN.
            foreach (array_chunk($missing, 1000) as $chunk) {
                foreach ($this->query($chunk) as $id => $row) {
                    $fresh[self::CACHE_PREFIX.$id] = $row;
                    $result[$id] = $row;
                }
            }
            if ($fresh !== []) {
                cache()->putMany($fresh, self::TTL);
            }
        }

        return $result;
    }

    /**
     * @param  array<int, int>  $ids
     * @return array<int, array<string, mixed>>
     */
    private function query(array $ids): array
    {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $rows = DB::connection('oracle')->select(
            "SELECT g.idgrupo_cl, g.idsubfamilia_cl, s.idfamilia_cl, f.idcategoria_cl,
                    f.descripcion AS f_desc, f.desc_corta AS f_corta, f.estado AS f_estado,
                    c.iddeporte_cl, d.descripcion AS d_desc, d.desc_corta AS d_corta, d.estado AS d_estado
               FROM DEVELOPER.GRUPO_CL g
               LEFT JOIN DEVELOPER.SUBFAMILIA_CL s ON s.idsubfamilia_cl = g.idsubfamilia_cl
               LEFT JOIN DEVELOPER.FAMILIA_CL f ON f.idfamilia_cl = s.idfamilia_cl
               LEFT JOIN DEVELOPER.CATEGORIA_CL c ON c.idcategoria_cl = f.idcategoria_cl
               LEFT JOIN DEVELOPER.DEPORTE_CL d ON d.iddeporte_cl = c.iddeporte_cl
              WHERE g.idgrupo_cl IN ({$placeholders})",
            $ids
        );

        $map = [];
        foreach ($rows as $r) {
            $r = array_change_key_case((array) $r, CASE_LOWER);
            $id = (int) $r['idgrupo_cl'];

            $map[$id] = [
                'idgrupo_cl' => $id,
                'idsubfamilia_cl' => self::intOrNull($r['idsubfamilia_cl']),
                'idfamilia_cl' => self::intOrNull($r['idfamilia_cl']),
                'idcategoria_cl' => self::intOrNull($r['idcategoria_cl']),
                // Mismos tipos que devolvían las relaciones Eloquent: las claves
                // primarias salían como int, pero iddeporte_cl se leía como
                // atributo normal de CategoriaCl (string), y `estado` va con
                // cast boolean en todos estos modelos.
                'iddeporte_cl' => $r['iddeporte_cl'] === null ? null : (string) $r['iddeporte_cl'],
                'familia' => $r['idfamilia_cl'] !== null ? [
                    'id' => (int) $r['idfamilia_cl'],
                    'description' => $r['f_desc'],
                    'description_short' => $r['f_corta'],
                    'available' => self::boolOrNull($r['f_estado']),
                ] : null,
                'deporte' => $r['iddeporte_cl'] !== null ? [
                    'id' => (int) $r['iddeporte_cl'],
                    'description' => $r['d_desc'],
                    'description_short' => $r['d_corta'],
                    'available' => self::boolOrNull($r['d_estado']),
                ] : null,
            ];
        }

        return $map;
    }

    private static function boolOrNull(mixed $v): ?bool
    {
        return $v === null ? null : (bool) $v;
    }

    private static function intOrNull(mixed $v): ?int
    {
        return $v === null || $v === '' ? null : (int) $v;
    }
}

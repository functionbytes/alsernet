<?php

namespace Modules\Erp\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GestionPriceService
{
    /**
     * Mapeo de id_country de Prestashop a idregpais de Gestión
     */
    private const COUNTRY_MAPPING = [
        6 => 1,   // España
        15 => 2,  // Portugal
        8 => 3,   // Francia
        1 => 4,   // Alemania
        10 => 5,  // Italia
        2 => 6,   // Austria
    ];

    /**
     * Obtener precio de Gestión por referencia y país
     *
     * @param  string  $reference  Referencia del producto
     * @param  int  $idCountry  ID del país en Prestashop
     * @param  int  $quantity  Cantidad (para escalas de precio)
     * @return array ['price' => float|null, 'found' => bool, 'error' => string|null]
     */
    public function getPriceByReference(string $reference, int $idCountry, int $quantity = 1): array
    {
        try {
            // Validar referencia
            if (empty($reference)) {
                return [
                    'price' => null,
                    'found' => false,
                    'error' => 'Referencia vacía',
                ];
            }

            // Obtener idregpais de Gestión
            $idregpais = self::COUNTRY_MAPPING[$idCountry] ?? null;

            if ($idregpais === null) {
                return [
                    'price' => null,
                    'found' => false,
                    'error' => "País no mapeado: {$idCountry}",
                ];
            }

            // Ejecutar consulta en base de datos Oracle (Gestión)
            $sql = '
                SELECT tl.pvp
                FROM tarifa_linea tl
                INNER JOIN tarifa_cabecera tc
                    ON (tc.idtarifa_cabecera = tl.idtarifa_cabecera
                        AND tc.finicio <= SYSDATE
                        AND (tc.ffin IS NULL OR tc.ffin >= SYSDATE)
                        AND tc.estado = 1)
                INNER JOIN producto p
                    ON (p.idarticulo = tc.idarticulo)
                WHERE p.referencia = :reference
                    AND tc.idregpais = :idregpais
                    AND tl.estado = 1
                    AND tl.udesde <= :quantity
                ORDER BY tl.udesde DESC
            ';

            $result = DB::connection('oracle')
                ->select($sql, [
                    'reference' => $reference,
                    'idregpais' => $idregpais,
                    'quantity' => $quantity,
                ]);

            if (empty($result)) {
                return [
                    'price' => null,
                    'found' => false,
                    'error' => null,
                ];
            }

            // Obtener el primer resultado (mayor udesde que cumpla)
            $pvp = $result[0]->pvp ?? $result[0]->PVP ?? null;

            return [
                'price' => $pvp !== null ? round((float) $pvp, 2) : null,
                'found' => true,
                'error' => null,
                'metadata' => [
                    'reference' => $reference,
                    'country' => $idCountry,
                    'idregpais' => $idregpais,
                    'quantity' => $quantity,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Error al consultar precio en Gestión', [
                'reference' => $reference,
                'country' => $idCountry,
                'error' => $e->getMessage(),
            ]);

            return [
                'price' => null,
                'found' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Obtener precios de múltiples referencias
     *
     * @param  array  $references  Array de referencias
     * @param  int  $idCountry  ID del país
     * @return array Asociativo [reference => ['price' => float, 'found' => bool]]
     */
    public function getPricesBatch(array $references, int $idCountry, int $quantity = 1): array
    {
        $references = array_values(array_unique(array_filter($references, 'strlen')));

        // Pre-seed defaults so callers always get every reference back, even
        // if Gestión returns nothing for some of them.
        $results = [];
        foreach ($references as $r) {
            $results[$r] = ['price' => null, 'found' => false, 'error' => null];
        }

        if (empty($references)) {
            return $results;
        }

        $idregpais = self::COUNTRY_MAPPING[$idCountry] ?? null;
        if ($idregpais === null) {
            foreach ($references as $r) {
                $results[$r]['error'] = "País no mapeado: {$idCountry}";
            }

            return $results;
        }

        // Oracle 11g has a 1000-element IN-list ceiling — chunk to be safe.
        foreach (array_chunk($references, 900) as $chunk) {
            $bindings = [];
            $placeholders = [];
            foreach ($chunk as $i => $ref) {
                $key = "r{$i}";
                $bindings[$key] = $ref;
                $placeholders[] = ':'.$key;
            }
            $inList = implode(', ', $placeholders);
            $bindings['idregpais'] = $idregpais;
            $bindings['quantity'] = $quantity;

            // ROW_NUMBER picks the best `udesde` tier per (reference, country).
            $sql = "
                SELECT referencia, pvp
                FROM (
                    SELECT p.referencia,
                           tl.pvp,
                           ROW_NUMBER() OVER (PARTITION BY p.referencia ORDER BY tl.udesde DESC) AS rn
                    FROM tarifa_linea tl
                    INNER JOIN tarifa_cabecera tc
                        ON tc.idtarifa_cabecera = tl.idtarifa_cabecera
                       AND tc.finicio <= SYSDATE
                       AND (tc.ffin IS NULL OR tc.ffin >= SYSDATE)
                       AND tc.estado = 1
                    INNER JOIN producto p
                        ON p.idarticulo = tc.idarticulo
                    WHERE p.referencia IN ({$inList})
                      AND tc.idregpais = :idregpais
                      AND tl.estado = 1
                      AND tl.udesde <= :quantity
                )
                WHERE rn = 1
            ";

            try {
                $rows = DB::connection('oracle')->select($sql, $bindings);
            } catch (\Throwable $e) {
                Log::error('GestionPriceService::getPricesBatch failed for chunk', [
                    'count' => count($chunk),
                    'country' => $idCountry,
                    'error' => $e->getMessage(),
                ]);
                foreach ($chunk as $r) {
                    $results[$r]['error'] = $e->getMessage();
                }

                continue;
            }

            foreach ($rows as $row) {
                $ref = $row->referencia ?? $row->REFERENCIA ?? null;
                $pvp = $row->pvp ?? $row->PVP ?? null;
                if ($ref !== null && isset($results[$ref])) {
                    $results[$ref] = [
                        'price' => $pvp !== null ? round((float) $pvp, 2) : null,
                        'found' => $pvp !== null,
                        'error' => null,
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Verificar si existe producto en Gestión
     */
    public function productExists(string $reference): bool
    {
        try {
            $sql = 'SELECT COUNT(*) as total FROM producto WHERE referencia = :reference';

            $result = DB::connection('oracle')
                ->select($sql, ['reference' => $reference]);

            return ($result[0]->total ?? $result[0]->TOTAL ?? 0) > 0;

        } catch (\Exception $e) {
            Log::error('Error al verificar existencia de producto', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Obtener información completa de tarifa
     */
    public function getTarifaInfo(string $reference, int $idCountry): ?array
    {
        try {
            $idregpais = self::COUNTRY_MAPPING[$idCountry] ?? null;

            if ($idregpais === null) {
                return null;
            }

            $sql = '
                SELECT
                    p.idarticulo,
                    p.referencia,
                    p.descripcion,
                    tc.idtarifa_cabecera,
                    tc.finicio,
                    tc.ffin,
                    tl.pvp,
                    tl.udesde,
                    tl.uhasta
                FROM tarifa_linea tl
                INNER JOIN tarifa_cabecera tc
                    ON (tc.idtarifa_cabecera = tl.idtarifa_cabecera
                        AND tc.finicio <= SYSDATE
                        AND (tc.ffin IS NULL OR tc.ffin >= SYSDATE)
                        AND tc.estado = 1)
                INNER JOIN producto p
                    ON (p.idarticulo = tc.idarticulo)
                WHERE p.referencia = :reference
                    AND tc.idregpais = :idregpais
                    AND tl.estado = 1
                ORDER BY tl.udesde
            ';

            $results = DB::connection('oracle')
                ->select($sql, [
                    'reference' => $reference,
                    'idregpais' => $idregpais,
                ]);

            if (empty($results)) {
                return null;
            }

            // Convertir a array asociativo
            return array_map(function ($row) {
                return [
                    'idarticulo' => $row->idarticulo ?? $row->IDARTICULO ?? null,
                    'referencia' => $row->referencia ?? $row->REFERENCIA ?? null,
                    'descripcion' => $row->descripcion ?? $row->DESCRIPCION ?? null,
                    'finicio' => $row->finicio ?? $row->FINICIO ?? null,
                    'ffin' => $row->ffin ?? $row->FFIN ?? null,
                    'pvp' => $row->pvp ?? $row->PVP ?? null,
                    'udesde' => $row->udesde ?? $row->UDESDE ?? null,
                    'uhasta' => $row->uhasta ?? $row->UHASTA ?? null,
                ];
            }, $results);

        } catch (\Exception $e) {
            Log::error('Error al obtener info de tarifa', [
                'reference' => $reference,
                'country' => $idCountry,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Obtener mapeo de país
     */
    public static function getCountryMapping(int $idCountry): ?int
    {
        return self::COUNTRY_MAPPING[$idCountry] ?? null;
    }

    /**
     * Obtener todos los mapeos de países
     */
    public static function getAllCountryMappings(): array
    {
        return self::COUNTRY_MAPPING;
    }
}

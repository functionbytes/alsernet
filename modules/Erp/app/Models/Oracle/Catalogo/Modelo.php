<?php

namespace Modules\Erp\Models\Oracle\Catalogo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Articulo\Articulo;
use Modules\Erp\Models\Oracle\Configuracion\Marca;
use Modules\Erp\Models\Oracle\Otros\GrupoCl;
use Modules\Erp\Models\Oracle\Web\WCaracteristicasOrden;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla MODELO
 *
 * ÍNDICES DISPONIBLES:
 * PK_MODELO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDMODELO
 */
class Modelo extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    /**
     * Columnas para fastPaginate(): las de siempre (mismo orden que t.*),
     * pero DESCRIPCION (CLOB) se lee en línea — ver UsesOCI8Performance.
     */
    protected static function getCustomSelectColumns(): string
    {
        return 't.idmodelo, t.idusuariocre, t.idusuariomod, t.idusuariobaj, t.fcreacion, t.fmodificacion, '.
            't.fbaja, t.estado, t.codigo, t.idgrupo_cl, t.nombre, t.estado_publicado_web, t.idmarca, '.
            't.venta_telefono, t.precio_consultar_ficha, '.static::lobSelect('descripcion');
    }

    protected static function oci8LobColumns(): array
    {
        return ['descripcion'];
    }

    /**
     * Caracteres de DESCRIPCION (CLOB) que se leen en línea como VARCHAR2.
     * La BD usa WE8MSWIN1252 (1 byte por carácter): 4000 caben en un VARCHAR2.
     */
    public const DESCRIPCION_INLINE_CHARS = 4000;

    /**
     * Añade DESCRIPCION a una consulta Eloquent sin leerla como LOB (una ida
     * y vuelta por fila: 2,8 s para 20 filas frente a 40 ms). Completar
     * después con hydrateLongDescriptions().
     */
    public function scopeWithInlineDescripcion($query, string $qualifier = '')
    {
        $column = ($qualifier !== '' ? $qualifier.'.' : '').'descripcion';

        return $query
            ->selectRaw("DBMS_LOB.SUBSTR({$column}, ".self::DESCRIPCION_INLINE_CHARS.', 1) AS descripcion')
            ->selectRaw("DBMS_LOB.GETLENGTH({$column}) AS descripcion_len");
    }

    /**
     * Completa las DESCRIPCION que no cupieron en línea (una consulta solo
     * para esas filas) y conserva '' para los EMPTY_CLOB.
     *
     * @param  iterable<int, self>  $modelos
     */
    public static function hydrateLongDescriptions(iterable $modelos): void
    {
        $long = [];
        $maxLen = 0;
        foreach ($modelos as $m) {
            if ((int) $m->descripcion_len > self::DESCRIPCION_INLINE_CHARS) {
                $long[] = $m->idmodelo;
                $maxLen = max($maxLen, (int) $m->descripcion_len);
            }
        }

        // El resto también en trozos VARCHAR2 de 4000 (sin leer el LOB, que
        // es una ida y vuelta por fila: 2,7 s para ~60 descripciones largas).
        $full = collect();
        if ($long !== []) {
            $step = self::DESCRIPCION_INLINE_CHARS;
            $parts = [];
            for ($offset = $step + 1, $i = 0; $offset <= $maxLen; $offset += $step, $i++) {
                $parts[] = "DBMS_LOB.SUBSTR(descripcion, {$step}, {$offset}) AS p{$i}";
            }
            foreach (array_chunk($long, 1000) as $chunk) {
                $rows = static::query()->toBase()
                    ->select('idmodelo')->selectRaw(implode(', ', $parts))
                    ->whereIn('idmodelo', $chunk)
                    ->get();
                foreach ($rows as $row) {
                    $row = array_change_key_case((array) $row, CASE_LOWER);
                    $full[$row['idmodelo']] = implode('', array_map(fn ($k) => (string) $row['p'.$k], array_keys($parts)));
                }
            }
        }

        foreach ($modelos as $m) {
            if ($full->has($m->idmodelo)) {
                $m->descripcion = $m->descripcion.$full->get($m->idmodelo);
            } elseif ($m->descripcion === null && $m->descripcion_len !== null && (int) $m->descripcion_len === 0) {
                // DBMS_LOB.SUBSTR devuelve NULL para un EMPTY_CLOB; leído como LOB era ''.
                $m->descripcion = '';
            }
            unset($m->descripcion_len);
            $m->syncOriginalAttributes(['descripcion']);
        }
    }

    protected $connection = 'oracle';

    protected $table = 'modelo';

    protected $primaryKey = 'idmodelo';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idusuariocre', 'idusuariomod', 'idusuariobaj', 'estado', 'codigo',
        'idgrupo_cl', 'nombre', 'descripcion', 'estado_publicado_web',
        'idmarca', 'venta_telefono', 'precio_consultar_ficha',
    ];

    protected $casts = [
        'estado' => 'boolean',
        'estado_publicado_web' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación con Marca
     */
    public function marca()
    {
        return $this->belongsTo(Marca::class, 'idmarca', 'idmarca');
    }

    /**
     * Relación: Modelo
     * ✅ Usa PK_MODELO (indexado)
     */
    public function modelo()
    {
        return $this->belongsTo(Modelo::class, 'idmodelo', 'idmodelo');
    }

    /**
     * Relación: GrupoCl
     * ⚠️  SIN ÍNDICE en IDGRUPO_CL
     */
    public function grupoCl()
    {
        return $this->belongsTo(GrupoCl::class, 'idgrupo_cl', 'idgrupo_cl');
    }

    /**
     * Artículos de este modelo
     */
    public function articulos()
    {
        return $this->hasMany(Articulo::class, 'idmodelo', 'idmodelo');
    }

    /**
     * Características ordenadas del modelo (web)
     * ✅ Usa IDX_WCARACT_ORDEN_WMODELO (indexado en IDMODELO)
     */
    public function caracteristicasOrden()
    {
        return $this->hasMany(WCaracteristicasOrden::class, 'idmodelo', 'idmodelo')
            ->orderBy('orden');
    }
}

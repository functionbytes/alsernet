<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\Setting;
use Throwable;

/**
 * Re-cifra con la APP_KEY actual todo lo que esté cifrado en la BD.
 *
 * Se usa al rotar APP_KEY (29-sep-2026, incidente: la clave se filtró con el
 * .env). Procedimiento:
 *   1. php artisan key:generate --force  y poner la clave antigua en APP_PREVIOUS_KEYS
 *   2. php artisan security:reencrypt            (simulación: cuenta y comprueba)
 *   3. php artisan security:reencrypt --apply    (re-cifra)
 *   4. comprobar la app y QUITAR APP_PREVIOUS_KEYS del .env
 *
 * No depende de conocer cada modelo: recorre las columnas de texto y trata
 * cualquier valor con forma de payload de Laravel ("eyJpdiI6…" = base64 de
 * {"iv":…) o con el prefijo 'enc:' de Setting. Descifra en bruto (sin
 * unserialize) y vuelve a cifrar en bruto, así que se conserva exactamente lo
 * que hubiera dentro (string cifrado con encryptString o valor serializado de
 * los casts encrypted / encrypted:array).
 */
class SecurityReencryptCommand extends Command
{
    protected $signature = 'security:reencrypt
                            {--apply : Re-cifrar de verdad (sin esta opción solo simula)}
                            {--connection= : Conexión de BD (por defecto la principal)}';

    protected $description = 'Re-cifra con la APP_KEY actual los valores cifrados de la BD (tras rotar la clave)';

    private const PAYLOAD_PREFIX = 'eyJpdiI6';

    private const SETTING_PREFIX = 'enc:';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $conn = DB::connection($this->option('connection') ?: null);
        $current = $this->currentKeyEncrypter();

        if (config('app.previous_keys') === [] || array_filter((array) config('app.previous_keys')) === []) {
            $this->warn('APP_PREVIOUS_KEYS está vacío: solo se podrá leer lo cifrado con la clave actual.');
        }

        $columns = $conn->select(
            'select table_name t, column_name c from information_schema.columns
              where table_schema = ? and data_type in ("text","mediumtext","longtext","tinytext","varchar","json")
                and (character_maximum_length is null or character_maximum_length >= 100)',
            [$conn->getDatabaseName()]
        );

        $totals = ['found' => 0, 'already_current' => 0, 'reencrypted' => 0, 'failed' => 0];

        foreach ($columns as $col) {
            $pk = $this->primaryKey($conn, $col->t);
            if ($pk === null) {
                continue;
            }

            $rows = $conn->table($col->t)
                ->where($col->c, 'like', self::PAYLOAD_PREFIX.'%')
                ->orWhere($col->c, 'like', self::SETTING_PREFIX.self::PAYLOAD_PREFIX.'%')
                ->get([$pk, $col->c]);

            if ($rows->isEmpty()) {
                continue;
            }

            $stats = ['found' => 0, 'already_current' => 0, 'reencrypted' => 0, 'failed' => 0];
            $updates = [];

            foreach ($rows as $row) {
                $stats['found']++;
                $stored = (string) $row->{$col->c};
                $prefix = str_starts_with($stored, self::SETTING_PREFIX) ? self::SETTING_PREFIX : '';
                $payload = substr($stored, strlen($prefix));

                try {
                    $current->decrypt($payload, false);
                    $stats['already_current']++;

                    continue;
                } catch (DecryptException) {
                    // no es de la clave actual: se intenta con las anteriores
                }

                try {
                    // Crypt prueba la clave actual y las de APP_PREVIOUS_KEYS.
                    $plain = Crypt::decrypt($payload, false);
                    $new = $prefix.$current->encrypt($plain, false);

                    if ($current->decrypt(substr($new, strlen($prefix)), false) !== $plain) {
                        throw new \RuntimeException('verificación fallida');
                    }

                    $updates[$row->{$pk}] = $new;
                    $stats['reencrypted']++;
                } catch (Throwable $e) {
                    $stats['failed']++;
                    $this->error("  {$col->t}.{$col->c} #{$row->{$pk}}: no se puede descifrar ({$e->getMessage()})");
                }
            }

            if ($apply && $updates !== []) {
                $conn->transaction(function () use ($conn, $col, $pk, $updates) {
                    foreach ($updates as $id => $value) {
                        $conn->table($col->t)->where($pk, $id)->update([$col->c => $value]);
                    }
                });
            }

            foreach ($stats as $k => $v) {
                $totals[$k] += $v;
            }

            $this->line(sprintf(
                '%-55s encontrados=%d  ya_con_clave_actual=%d  %s=%d  fallos=%d',
                "{$col->t}.{$col->c}",
                $stats['found'],
                $stats['already_current'],
                $apply ? 're-cifrados' : 'a_re-cifrar',
                $stats['reencrypted'],
                $stats['failed']
            ));
        }

        if ($apply) {
            // La caché guarda valores de settings ya cifrados: se invalida.
            foreach (Setting::query()->pluck('key') as $name) {
                cache()->forget("setting_{$name}");
            }
            Setting::clearErpSettingsCache();
            Setting::clearEmailSettingsCache();
        }

        $this->newLine();
        $this->info(sprintf(
            'Total: encontrados=%d, ya con la clave actual=%d, %s=%d, fallos=%d%s',
            $totals['found'],
            $totals['already_current'],
            $apply ? 're-cifrados' : 'a re-cifrar',
            $totals['reencrypted'],
            $totals['failed'],
            $apply ? '' : '  (SIMULACIÓN: repite con --apply)'
        ));

        if ($apply && $totals['failed'] === 0) {
            $this->info('Hecho. Comprueba la app y quita APP_PREVIOUS_KEYS del .env; después: php artisan cache:clear');
        }

        return $totals['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /** Encrypter SOLO con la clave actual (Crypt también aceptaría las anteriores). */
    private function currentKeyEncrypter(): Encrypter
    {
        $key = (string) config('app.key');
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        return new Encrypter($key, (string) config('app.cipher'));
    }

    private function primaryKey($conn, string $table): ?string
    {
        $pk = $conn->selectOne(
            'select column_name c from information_schema.key_column_usage
              where table_schema = ? and table_name = ? and constraint_name = "PRIMARY" limit 1',
            [$conn->getDatabaseName(), $table]
        );

        return $pk->c ?? null;
    }
}

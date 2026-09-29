<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 29-sep-2026 (auditoría, grupo D): config_data de las fuentes de proveedor
 * guarda contraseñas FTP/SFTP y tokens de API. Pasa a 'encrypted:array'.
 *
 * 1) La columna era JSON (en MariaDB, LONGTEXT + CHECK json_valid), que no
 *    admite el texto cifrado: se cambia a LONGTEXT.
 * 2) Se re-cifran las filas existentes en una transacción, con el mismo formato
 *    que el cast encrypted:array (Crypt::encryptString del JSON). Idempotente:
 *    las filas que ya descifran no se tocan.
 */
return new class extends Migration
{
    private const TABLE = 'supplier_source_configurations';

    public function up(): void
    {
        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->longText('config_data')->nullable()->change();
        });

        DB::transaction(function () {
            DB::table(self::TABLE)
                ->whereNotNull('config_data')
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id', 'config_data'])
                ->each(function ($row) {
                    if ($this->isEncrypted($row->config_data)) {
                        return;
                    }

                    DB::table(self::TABLE)
                        ->where('id', $row->id)
                        ->update(['config_data' => Crypt::encryptString($row->config_data)]);
                });
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            DB::table(self::TABLE)
                ->whereNotNull('config_data')
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id', 'config_data'])
                ->each(function ($row) {
                    if (! $this->isEncrypted($row->config_data)) {
                        return;
                    }

                    DB::table(self::TABLE)
                        ->where('id', $row->id)
                        ->update(['config_data' => Crypt::decryptString($row->config_data)]);
                });
        });

        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->json('config_data')->nullable()->change();
        });
    }

    private function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }
};

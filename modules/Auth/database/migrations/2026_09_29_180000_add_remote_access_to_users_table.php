<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Excepción de acceso remoto por usuario para el filtro por IP del personal
 * (29-sep-2026). Solo aditiva; la concede un super-admin y solo surte efecto
 * con 2FA activado y verificado en la sesión.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'remote_access_enabled')) {
                $table->boolean('remote_access_enabled')->default(false);
            }

            if (! Schema::hasColumn('users', 'remote_access_until')) {
                $table->timestamp('remote_access_until')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['remote_access_enabled', 'remote_access_until'],
                fn ($c) => Schema::hasColumn('users', $c),
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};

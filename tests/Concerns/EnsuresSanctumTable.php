<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Garantiza que exista `personal_access_tokens` (Laravel Sanctum) en la BD de
 * test. system_test_pristine se capturó sin correr la migración de Sanctum
 * (vendor/laravel/sanctum/database/migrations), así que `$user->createToken()`
 * revienta con "Base table or view not found".
 *
 * Sigue el mismo patrón que ensurePermissionTables() en CampaignsFeatureTest:
 * CREATE TABLE IF NOT EXISTS con Schema raw (sin tocar la tabla migrations) y
 * re-sincronización de la transacción abierta, porque el DDL provoca un COMMIT
 * implícito en MariaDB que desincroniza el contador de DatabaseTransactions.
 */
trait EnsuresSanctumTable
{
    protected function ensurePersonalAccessTokensTable(): void
    {
        $db = DB::connection('mariadb');

        $exists = (bool) $db->scalar(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
            ['personal_access_tokens']
        );

        if ($exists) {
            return;
        }

        // Estructura idéntica a la migración de Sanctum 4
        // (2019_12_14_000001_create_personal_access_tokens_table).
        $db->statement('CREATE TABLE IF NOT EXISTS `personal_access_tokens` (
            `id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `tokenable_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
            `tokenable_id` bigint unsigned NOT NULL,
            `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
            `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
            `abilities` text COLLATE utf8mb4_unicode_ci,
            `last_used_at` timestamp NULL DEFAULT NULL,
            `expires_at` timestamp NULL DEFAULT NULL,
            `created_at` timestamp NULL DEFAULT NULL,
            `updated_at` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
            KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
            KEY `personal_access_tokens_expires_at_index` (`expires_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        // El DDL hizo COMMIT implícito: reabrir una transacción limpia para que
        // el tearDown de DatabaseTransactions pueda seguir haciendo rollback.
        DB::purge('mariadb');
        DB::connection('mariadb')->beginTransaction();
    }
}

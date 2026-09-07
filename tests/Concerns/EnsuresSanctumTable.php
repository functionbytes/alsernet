<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * `personal_access_tokens` (Laravel Sanctum) falta en el snapshot
 * `system_test_pristine` usado por los tests — igual que permissions/roles
 * en SeedsCorePermissions. Crea la tabla de forma idempotente antes de que
 * un test llame a Sanctum::actingAs()/createToken(). Mismo patrón que
 * CampaignsFeatureTest::ensurePermissionTables(): CREATE TABLE IF NOT
 * EXISTS vía SQL crudo (no una migración real, para no arrastrar el resto
 * del esquema de Sanctum) + purge de la conexión porque el DDL en MariaDB
 * hace un COMMIT implícito que desincroniza el contador de transacciones
 * de DatabaseTransactions.
 */
trait EnsuresSanctumTable
{
    private function ensurePersonalAccessTokensTable(): void
    {
        $db = DB::connection('mariadb');

        $hasTable = (bool) $db->scalar(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
            ['personal_access_tokens']
        );

        if ($hasTable) {
            return;
        }

        // Mismo esquema que vendor/laravel/sanctum/database/migrations/
        // 2019_12_14_000001_create_personal_access_tokens_table.php.
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

        // DDL causa un COMMIT implicito en MariaDB, de-sincronizando el
        // contador $transactions de Laravel — mismo purge+restart que
        // ensurePermissionTables() para que el rollback de teardown siga
        // funcionando.
        DB::purge('mariadb');
        DB::connection('mariadb')->beginTransaction();
    }
}

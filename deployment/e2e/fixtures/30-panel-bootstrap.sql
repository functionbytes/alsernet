-- ===========================================================================
-- E2E staging — bootstrap mínimo del esquema `panel`.
--
-- Los service providers del panel (p.ej. HelpdeskAnalyticsServiceProvider)
-- consultan helpdesk_settings DURANTE el boot de artisan, por lo que
-- `php artisan migrate` no puede ni arrancar contra una BD totalmente vacía.
-- Se pre-crea la tabla exactamente como la define la migración
-- 2026_04_01_000015_create_helpdesk_settings_table y se marca esa migración
-- como aplicada para que migrate no intente recrearla.
-- ===========================================================================
USE `panel`;

CREATE TABLE IF NOT EXISTS `migrations` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `migration` VARCHAR(255) NOT NULL,
    `batch` INT NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `helpdesk_settings` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key` VARCHAR(255) NOT NULL,
    `value` TEXT NULL,
    `group` VARCHAR(100) NOT NULL DEFAULT 'general',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `helpdesk_settings_key_unique` (`key`),
    KEY `helpdesk_settings_group_index` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `migrations` (`migration`, `batch`) VALUES
    ('2026_04_01_000015_create_helpdesk_settings_table', 1);

-- `chat_accounts` no la crea ninguna migración del repo (existe solo en la BD
-- de desarrollo) pero varias migraciones de Social/Chat le añaden claves
-- foráneas. Sin esta tabla, `php artisan migrate` en BD limpia falla con
-- errno 150 en chat_email_templates / social_accounts.
CREATE TABLE IF NOT EXISTS `chat_accounts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NULL,
    `default_locale` VARCHAR(10) NULL DEFAULT 'es',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- `chat_accounts_user` tampoco la crea ninguna migración del repo, pero
-- 2026_04_30_010510_add_account_id_to_users_for_chat la consulta e inserta.
CREATE TABLE IF NOT EXISTS `chat_accounts_user` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `account_id` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_user_account` (`user_id`, `account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Toggle explícito de la integración PrestaShop (el default del helper ya es
-- '1'; la fila lo deja documentado y estable ante cambios del default).
INSERT IGNORE INTO `helpdesk_settings` (`key`, `value`, `group`, `created_at`, `updated_at`)
VALUES ('prestashop.integration_enabled', '1', 'integrations', NOW(), NOW());

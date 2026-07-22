-- E2E staging — databases.
-- `bridge` is created by MYSQL_DATABASE; the Laravel panel gets its own schema.
CREATE DATABASE IF NOT EXISTS `panel` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

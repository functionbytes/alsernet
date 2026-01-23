-- Table for storing API endpoint requests and their status
CREATE TABLE IF NOT EXISTS `PREFIX_alsernet_forms_requests` (
    `id_alsernetforms_request` INT(11) NOT NULL AUTO_INCREMENT,
    `endpoint_type` VARCHAR(50) NOT NULL DEFAULT 'default',
    `method` VARCHAR(10) NOT NULL,
    `url` TEXT NOT NULL,
    `payload` LONGTEXT NULL,
    `response` LONGTEXT NULL,
    `status` ENUM('pending', 'processing', 'success', 'failed', 'server_unavailable') NOT NULL DEFAULT 'pending',
    `retry_count` INT(11) NOT NULL DEFAULT 0,
    `max_retries` INT(11) NOT NULL DEFAULT 3,
    `last_error` TEXT NULL,
    `created_at` DATETIME NOT NULL,
    `synced_at` DATETIME NULL,
    `next_retry_at` DATETIME NULL,
    PRIMARY KEY (`id_alsernetforms_request`),
    INDEX `idx_status` (`status`),
    INDEX `idx_endpoint_type` (`endpoint_type`),
    INDEX `idx_next_retry` (`next_retry_at`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;

-- Table for tracking endpoint availability
CREATE TABLE IF NOT EXISTS `PREFIX_alsernet_endpoint_health` (
    `id_endpoint_health` INT(11) NOT NULL AUTO_INCREMENT,
    `endpoint_url` VARCHAR(255) NOT NULL,
    `endpoint_type` VARCHAR(50) NOT NULL,
    `is_available` TINYINT(1) NOT NULL DEFAULT 1,
    `last_check_at` DATETIME NOT NULL,
    `last_success_at` DATETIME NULL,
    `last_failure_at` DATETIME NULL,
    `consecutive_failures` INT(11) NOT NULL DEFAULT 0,
    `response_time_ms` INT(11) NULL,
    PRIMARY KEY (`id_endpoint_health`),
    UNIQUE KEY `unique_endpoint` (`endpoint_url`, `endpoint_type`),
    INDEX `idx_availability` (`is_available`),
    INDEX `idx_last_check` (`last_check_at`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;

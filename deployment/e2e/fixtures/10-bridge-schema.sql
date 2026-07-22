-- ===========================================================================
-- E2E staging — minimal PrestaShop schema for the alsernetbridge stub.
-- Literal prefix `aalv_` (matches _DB_PREFIX_ of the Alvarez shop).
-- Only the tables/columns actually queried by the module endpoints.
-- ===========================================================================
USE `bridge`;

-- ---------------------------------------------------------------------------
-- PS core (subset)
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `aalv_configuration` (
    `id_configuration` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`     VARCHAR(254) NOT NULL,
    `value`    TEXT NULL,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_configuration`),
    UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `aalv_customer` (
    `id_customer` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_gender` INT UNSIGNED NOT NULL DEFAULT 0,
    `id_default_group` INT UNSIGNED NOT NULL DEFAULT 3,
    `id_lang` INT UNSIGNED NOT NULL DEFAULT 1,
    `firstname` VARCHAR(255) NOT NULL,
    `lastname` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `company` VARCHAR(255) NULL,
    `siret` VARCHAR(64) NULL,
    `birthday` DATE NULL,
    `newsletter` TINYINT NOT NULL DEFAULT 0,
    `optin` TINYINT NOT NULL DEFAULT 0,
    `outstanding_allow_amount` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `max_payment_days` INT NOT NULL DEFAULT 0,
    `active` TINYINT NOT NULL DEFAULT 1,
    `is_guest` TINYINT NOT NULL DEFAULT 0,
    `deleted` TINYINT NOT NULL DEFAULT 0,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_customer`),
    KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `aalv_group_lang` (
    `id_group` INT UNSIGNED NOT NULL,
    `id_lang` INT UNSIGNED NOT NULL,
    `name` VARCHAR(64) NOT NULL,
    PRIMARY KEY (`id_group`, `id_lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `aalv_currency` (
    `id_currency` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `iso_code` VARCHAR(3) NOT NULL,
    `sign` VARCHAR(8) NOT NULL DEFAULT '',
    `format` TINYINT NOT NULL DEFAULT 0,
    `name` VARCHAR(64) NOT NULL DEFAULT '',
    PRIMARY KEY (`id_currency`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `aalv_orders` (
    `id_order` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference` VARCHAR(64) NOT NULL,
    `id_customer` INT UNSIGNED NOT NULL,
    `id_cart` INT UNSIGNED NOT NULL DEFAULT 0,
    `id_currency` INT UNSIGNED NOT NULL DEFAULT 1,
    `id_lang` INT UNSIGNED NOT NULL DEFAULT 1,
    `id_shop` INT UNSIGNED NOT NULL DEFAULT 1,
    `id_carrier` INT UNSIGNED NOT NULL DEFAULT 1,
    `id_address_delivery` INT UNSIGNED NOT NULL DEFAULT 0,
    `id_address_invoice` INT UNSIGNED NOT NULL DEFAULT 0,
    `current_state` INT UNSIGNED NOT NULL DEFAULT 1,
    `payment` VARCHAR(255) NOT NULL DEFAULT '',
    `valid` TINYINT NOT NULL DEFAULT 1,
    `total_products` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `total_products_wt` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `total_shipping_tax_incl` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `total_shipping_tax_excl` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `total_discounts_tax_incl` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `total_discounts_tax_excl` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `total_wrapping_tax_incl` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `total_paid_tax_excl` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `total_paid_tax_incl` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `total_paid` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `total_paid_real` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_order`),
    KEY `idx_customer` (`id_customer`),
    KEY `idx_cart` (`id_cart`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `aalv_order_detail` (
    `id_order_detail` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_order` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `product_attribute_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `product_reference` VARCHAR(64) NULL,
    `product_name` VARCHAR(255) NOT NULL,
    `product_ean13` VARCHAR(13) NULL,
    `product_quantity` INT NOT NULL DEFAULT 1,
    `product_quantity_return` INT NOT NULL DEFAULT 0,
    `unit_price_tax_excl` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `unit_price_tax_incl` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `total_price_tax_excl` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `total_price_tax_incl` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `reduction_amount_tax_incl` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `tax_rate` DECIMAL(10,3) NOT NULL DEFAULT 0,
    `id_customization` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id_order_detail`),
    KEY `idx_order` (`id_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `aalv_order_state` (
    `id_order_state` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `color` VARCHAR(32) NULL,
    `shipped` TINYINT NOT NULL DEFAULT 0,
    `paid` TINYINT NOT NULL DEFAULT 0,
    `invoice` TINYINT NOT NULL DEFAULT 0,
    `delivery` TINYINT NOT NULL DEFAULT 0,
    PRIMARY KEY (`id_order_state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `aalv_order_state_lang` (
    `id_order_state` INT UNSIGNED NOT NULL,
    `id_lang` INT UNSIGNED NOT NULL,
    `name` VARCHAR(64) NOT NULL,
    PRIMARY KEY (`id_order_state`, `id_lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `aalv_order_cart_rule` (
    `id_order_cart_rule` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_order` INT UNSIGNED NOT NULL,
    `name` VARCHAR(254) NOT NULL,
    `value` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `value_tax_excl` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `free_shipping` TINYINT NOT NULL DEFAULT 0,
    PRIMARY KEY (`id_order_cart_rule`),
    KEY `idx_order` (`id_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `aalv_order_payment` (
    `id_order_payment` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_reference` VARCHAR(64) NOT NULL,
    `payment_method` VARCHAR(255) NOT NULL DEFAULT '',
    `transaction_id` VARCHAR(254) NULL,
    `card_number` VARCHAR(254) NULL,
    `card_brand` VARCHAR(254) NULL,
    `card_expiration` CHAR(7) NULL,
    `amount` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `date_add` DATETIME NOT NULL,
    PRIMARY KEY (`id_order_payment`),
    KEY `idx_reference` (`order_reference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `aalv_order_history` (
    `id_order_history` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_order` INT UNSIGNED NOT NULL,
    `id_employee` INT UNSIGNED NOT NULL DEFAULT 0,
    `id_order_state` INT UNSIGNED NOT NULL,
    `date_add` DATETIME NOT NULL,
    PRIMARY KEY (`id_order_history`),
    KEY `idx_order` (`id_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `aalv_carrier` (
    `id_carrier` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(64) NOT NULL,
    `url` VARCHAR(255) NULL,
    `delay` VARCHAR(128) NULL,
    PRIMARY KEY (`id_carrier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `aalv_order_carrier` (
    `id_order_carrier` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_order` INT UNSIGNED NOT NULL,
    `id_carrier` INT UNSIGNED NOT NULL,
    `tracking_number` VARCHAR(64) NULL,
    `weight` DECIMAL(20,6) NULL,
    `date_add` DATETIME NOT NULL,
    PRIMARY KEY (`id_order_carrier`),
    KEY `idx_order` (`id_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `aalv_order_return` (
    `id_order_return` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_customer` INT UNSIGNED NOT NULL,
    `id_order` INT UNSIGNED NOT NULL,
    `state` INT UNSIGNED NOT NULL DEFAULT 1,
    `question` TEXT NULL,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_order_return`),
    KEY `idx_customer` (`id_customer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `aalv_order_return_detail` (
    `id_order_return` INT UNSIGNED NOT NULL,
    `id_order_detail` INT UNSIGNED NOT NULL,
    `id_customization` INT UNSIGNED NOT NULL DEFAULT 0,
    `product_quantity` INT NOT NULL DEFAULT 1,
    PRIMARY KEY (`id_order_return`, `id_order_detail`, `id_customization`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `aalv_order_return_state` (
    `id_order_return_state` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `color` VARCHAR(32) NULL,
    PRIMARY KEY (`id_order_return_state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `aalv_order_return_state_lang` (
    `id_order_return_state` INT UNSIGNED NOT NULL,
    `id_lang` INT UNSIGNED NOT NULL,
    `name` VARCHAR(64) NOT NULL,
    PRIMARY KEY (`id_order_return_state`, `id_lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `aalv_cart_rule` (
    `id_cart_rule` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_customer` INT UNSIGNED NOT NULL DEFAULT 0,
    `code` VARCHAR(254) NULL,
    `description` TEXT NULL,
    `date_from` DATETIME NOT NULL,
    `date_to` DATETIME NOT NULL,
    `reduction_percent` DECIMAL(5,2) NOT NULL DEFAULT 0,
    `reduction_amount` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `reduction_currency` INT UNSIGNED NOT NULL DEFAULT 1,
    `minimum_amount` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `quantity` INT NOT NULL DEFAULT 1,
    `quantity_per_user` INT NOT NULL DEFAULT 1,
    `active` TINYINT NOT NULL DEFAULT 1,
    `free_shipping` TINYINT NOT NULL DEFAULT 0,
    PRIMARY KEY (`id_cart_rule`),
    KEY `idx_customer` (`id_customer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `aalv_cart` (
    `id_cart` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_customer` INT UNSIGNED NOT NULL DEFAULT 0,
    `id_currency` INT UNSIGNED NOT NULL DEFAULT 1,
    `id_lang` INT UNSIGNED NOT NULL DEFAULT 1,
    `id_carrier` INT UNSIGNED NOT NULL DEFAULT 1,
    `id_shop` INT UNSIGNED NOT NULL DEFAULT 1,
    `id_address_delivery` INT UNSIGNED NOT NULL DEFAULT 0,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_cart`),
    KEY `idx_customer` (`id_customer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `aalv_cart_product` (
    `id_cart` INT UNSIGNED NOT NULL,
    `id_product` INT UNSIGNED NOT NULL,
    `id_product_attribute` INT UNSIGNED NOT NULL DEFAULT 0,
    `id_address_delivery` INT UNSIGNED NOT NULL DEFAULT 0,
    `quantity` INT NOT NULL DEFAULT 1,
    `date_add` DATETIME NOT NULL,
    PRIMARY KEY (`id_cart`, `id_product`, `id_product_attribute`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `aalv_product` (
    `id_product` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference` VARCHAR(64) NULL,
    `ean13` VARCHAR(13) NULL,
    `price` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `active` TINYINT NOT NULL DEFAULT 1,
    `quantity` INT NOT NULL DEFAULT 0,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_product`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `aalv_product_lang` (
    `id_product` INT UNSIGNED NOT NULL,
    `id_lang` INT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `link_rewrite` VARCHAR(255) NOT NULL DEFAULT '',
    PRIMARY KEY (`id_product`, `id_lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `aalv_product_feature` (
    `id_product` INT UNSIGNED NOT NULL,
    `id_feature` INT UNSIGNED NOT NULL,
    `id_feature_value` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id_product`, `id_feature`, `id_feature_value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `aalv_customer_thread` (
    `id_customer_thread` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_lang` INT UNSIGNED NOT NULL DEFAULT 1,
    `id_contact` INT UNSIGNED NOT NULL DEFAULT 0,
    `id_customer` INT UNSIGNED NOT NULL DEFAULT 0,
    `id_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `id_product` INT UNSIGNED NOT NULL DEFAULT 0,
    `status` VARCHAR(16) NOT NULL DEFAULT 'open',
    `email` VARCHAR(255) NOT NULL DEFAULT '',
    `token` VARCHAR(32) NULL,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_customer_thread`),
    KEY `idx_customer` (`id_customer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `aalv_customer_message` (
    `id_customer_message` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_customer_thread` INT UNSIGNED NOT NULL,
    `id_employee` INT UNSIGNED NOT NULL DEFAULT 0,
    `message` MEDIUMTEXT NOT NULL,
    `file_name` VARCHAR(64) NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(128) NULL,
    `read` TINYINT NOT NULL DEFAULT 0,
    `private` TINYINT NOT NULL DEFAULT 0,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_customer_message`),
    KEY `idx_thread` (`id_customer_thread`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `aalv_address` (
    `id_address` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_customer` INT UNSIGNED NOT NULL DEFAULT 0,
    `id_country` INT UNSIGNED NOT NULL DEFAULT 0,
    `id_state` INT UNSIGNED NOT NULL DEFAULT 0,
    `alias` VARCHAR(32) NOT NULL DEFAULT '',
    `firstname` VARCHAR(255) NOT NULL DEFAULT '',
    `lastname` VARCHAR(255) NOT NULL DEFAULT '',
    `company` VARCHAR(255) NULL,
    `address1` VARCHAR(128) NOT NULL DEFAULT '',
    `address2` VARCHAR(128) NULL,
    `postcode` VARCHAR(12) NULL,
    `city` VARCHAR(64) NOT NULL DEFAULT '',
    `phone` VARCHAR(32) NULL,
    `phone_mobile` VARCHAR(32) NULL,
    `deleted` TINYINT NOT NULL DEFAULT 0,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_address`),
    KEY `idx_customer` (`id_customer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `aalv_country_lang` (
    `id_country` INT UNSIGNED NOT NULL,
    `id_lang` INT UNSIGNED NOT NULL,
    `name` VARCHAR(64) NOT NULL,
    PRIMARY KEY (`id_country`, `id_lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `aalv_state` (
    `id_state` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(80) NOT NULL,
    PRIMARY KEY (`id_state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- alsernetbridge module tables (mirror of modules/alsernetbridge/sql/install.sql
-- with PREFIX_ -> aalv_ and ENGINE_TYPE -> InnoDB)
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `aalv_alsernetbridge_api_log` (
    `id_log`     INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `action`     VARCHAR(64)   NOT NULL,
    `id_customer` INT UNSIGNED NULL,
    `email`      VARCHAR(255)  NULL,
    `ip`         VARCHAR(45)   NULL,
    `status_code` SMALLINT     NOT NULL DEFAULT 200,
    `error`      VARCHAR(255)  NULL,
    `duration_ms` INT          NULL,
    `date_add`   DATETIME      NOT NULL,
    PRIMARY KEY (`id_log`),
    KEY `idx_action` (`action`),
    KEY `idx_email`  (`email`),
    KEY `idx_date`   (`date_add`),
    KEY `idx_ip_date` (`ip`, `date_add`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `aalv_alsernetbridge_idempotency` (
    `id_idempotency`  INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `idempotency_key` VARCHAR(128)   NOT NULL,
    `action`          VARCHAR(64)    NOT NULL,
    `status_code`     SMALLINT       NOT NULL,
    `response_body`   TEXT           NOT NULL,
    `date_add`        DATETIME       NOT NULL,
    PRIMARY KEY (`id_idempotency`),
    UNIQUE KEY `uk_key` (`idempotency_key`),
    KEY `idx_date` (`date_add`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `aalv_alsernetbridge_webhook_queue` (
    `id_webhook`      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `event`           VARCHAR(64)     NOT NULL,
    `payload`         TEXT            NOT NULL,
    `destination`     VARCHAR(32)     NOT NULL DEFAULT 'remarketing',
    `attempts`        TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `next_attempt_at` DATETIME        NOT NULL,
    `date_add`        DATETIME        NOT NULL,
    PRIMARY KEY (`id_webhook`),
    KEY `idx_next_attempt` (`next_attempt_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `aalv_alsernetbridge_webhook_dead` (
    `id_dead`    INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `event`      VARCHAR(64)      NOT NULL,
    `payload`    TEXT             NOT NULL,
    `destination` VARCHAR(32)     NOT NULL DEFAULT 'remarketing',
    `attempts`   TINYINT UNSIGNED NOT NULL,
    `date_dead`  DATETIME         NOT NULL,
    PRIMARY KEY (`id_dead`),
    KEY `idx_date_dead` (`date_dead`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `aalv_alsernetbridge_price_history` (
    `id_price_history`    INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `id_product`          INT UNSIGNED  NOT NULL,
    `id_product_attribute` INT UNSIGNED NULL,
    `price`               DECIMAL(20,6) NOT NULL,
    `quantity`            INT           NOT NULL DEFAULT 0,
    `recorded_at`         DATETIME      NOT NULL,
    PRIMARY KEY (`id_price_history`),
    KEY `idx_product` (`id_product`, `recorded_at`),
    KEY `idx_combo` (`id_product`, `id_product_attribute`, `recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================================================================
-- E2E staging — fixtures for the alsernetbridge stub.
-- Customer test@test.com (the default TEST_EMAIL of tests/run-e2e.sh) with
-- orders, addresses, messages, vouchers, a return and an abandoned cart.
-- ===========================================================================
USE `bridge`;

-- Module configuration. The secrets are E2E-only values, shared with
-- ../panel.env and run-contract-e2e.sh — rotate all three together.
INSERT INTO `aalv_configuration` (`name`, `value`, `date_add`, `date_upd`) VALUES
    ('PS_SHOP_DEFAULT', '1', NOW(), NOW()),
    ('PS_LANG_DEFAULT', '1', NOW(), NOW()),
    ('PS_REWRITING_SETTINGS', '1', NOW(), NOW()),
    ('ALSERNETBRIDGE_CACHE_VERSION', '1', NOW(), NOW()),
    ('ALSERNETBRIDGE_WEBHOOK_SECRET', 'e2e5ecb2f4b4d0f9a3c1d2e3f405162738495a6b7c8d9e0f1a2b3c4d5e6f7081', NOW(), NOW()),
    ('ALSERNETBRIDGE_OPS_SECRET', 'op5e2e1b2c3d4e5f60718293a4b5c6d7e8f900fedcba98765432112345678900', NOW(), NOW()),
    ('ALSERNETBRIDGE_REMARKETING_WEBHOOK_URL', 'http://panel-nginx/api/helpdeskprestashop/webhooks/event', NOW(), NOW());

INSERT INTO `aalv_currency` (`id_currency`, `iso_code`, `sign`, `format`, `name`)
VALUES (1, 'EUR', '€', 1, 'Euro');

INSERT INTO `aalv_group_lang` (`id_group`, `id_lang`, `name`)
VALUES (3, 1, 'Cliente');

INSERT INTO `aalv_order_state` (`id_order_state`, `color`, `shipped`, `paid`, `invoice`, `delivery`) VALUES
    (2, '#32CD32', 0, 1, 1, 0),
    (4, '#8A2BE2', 1, 1, 1, 0),
    (5, '#108510', 1, 1, 1, 1);

INSERT INTO `aalv_order_state_lang` (`id_order_state`, `id_lang`, `name`) VALUES
    (2, 1, 'Pago aceptado'),
    (4, 1, 'Enviado'),
    (5, 1, 'Entregado');

INSERT INTO `aalv_order_return_state` (`id_order_return_state`, `color`) VALUES (1, '#ADD8E6');
INSERT INTO `aalv_order_return_state_lang` (`id_order_return_state`, `id_lang`, `name`)
VALUES (1, 1, 'Esperando confirmación');

INSERT INTO `aalv_carrier` (`id_carrier`, `name`, `url`, `delay`)
VALUES (1, 'E2E Express', 'https://tracking.example.test/@', '24/48h');

INSERT INTO `aalv_country_lang` (`id_country`, `id_lang`, `name`) VALUES (6, 1, 'España');

-- Customer 1 — test@test.com
INSERT INTO `aalv_customer`
    (`id_customer`, `id_gender`, `id_default_group`, `id_lang`, `firstname`, `lastname`, `email`,
     `company`, `siret`, `birthday`, `newsletter`, `optin`, `outstanding_allow_amount`,
     `max_payment_days`, `active`, `is_guest`, `deleted`, `date_add`, `date_upd`)
VALUES
    (1, 1, 3, 1, 'Prueba', 'Contrato E2E', 'test@test.com',
     NULL, NULL, '1990-05-17', 1, 1, 0, 0, 1, 0, 0, '2024-01-10 09:00:00', NOW());

INSERT INTO `aalv_address`
    (`id_address`, `id_customer`, `id_country`, `id_state`, `alias`, `firstname`, `lastname`,
     `company`, `address1`, `address2`, `postcode`, `city`, `phone`, `phone_mobile`, `deleted`,
     `date_add`, `date_upd`)
VALUES
    (1, 1, 6, 0, 'Casa', 'Prueba', 'Contrato E2E',
     NULL, 'Calle Mayor 1', NULL, '28001', 'Madrid', '910000000', '600000000', 0,
     '2024-01-10 09:00:00', NOW());

-- Products
INSERT INTO `aalv_product` (`id_product`, `reference`, `ean13`, `price`, `active`, `quantity`, `date_add`, `date_upd`) VALUES
    (101, 'E2E-PROD-101', '8400000001012', 49.586777, 1, 25, '2024-01-01 00:00:00', NOW()),
    (102, 'E2E-PROD-102', '8400000001029', 12.396694, 1, 40, '2024-01-01 00:00:00', NOW());

INSERT INTO `aalv_product_lang` (`id_product`, `id_lang`, `name`, `link_rewrite`) VALUES
    (101, 1, 'Funda de rifle E2E', 'funda-rifle-e2e'),
    (102, 1, 'Kit limpieza E2E', 'kit-limpieza-e2e');

-- Order 1001 (cart 500) — paid, shipped, with lines, payment, history, tracking
INSERT INTO `aalv_cart` (`id_cart`, `id_customer`, `id_currency`, `id_lang`, `id_carrier`, `id_shop`, `id_address_delivery`, `date_add`, `date_upd`)
VALUES (500, 1, 1, 1, 1, 1, 1, '2025-11-02 10:00:00', '2025-11-02 10:20:00');

INSERT INTO `aalv_cart_product` (`id_cart`, `id_product`, `id_product_attribute`, `id_address_delivery`, `quantity`, `date_add`) VALUES
    (500, 101, 0, 1, 1, '2025-11-02 10:00:00'),
    (500, 102, 0, 1, 2, '2025-11-02 10:05:00');

INSERT INTO `aalv_orders`
    (`id_order`, `reference`, `id_customer`, `id_cart`, `id_currency`, `id_lang`, `id_shop`, `id_carrier`,
     `id_address_delivery`, `id_address_invoice`, `current_state`, `payment`, `valid`,
     `total_products`, `total_products_wt`, `total_shipping_tax_incl`, `total_shipping_tax_excl`,
     `total_discounts_tax_incl`, `total_discounts_tax_excl`, `total_wrapping_tax_incl`,
     `total_paid_tax_excl`, `total_paid_tax_incl`, `total_paid`, `total_paid_real`,
     `date_add`, `date_upd`)
VALUES
    (1001, 'E2E000001', 1, 500, 1, 1, 1, 1,
     1, 1, 4, 'Tarjeta de crédito', 1,
     74.380165, 90.00, 4.99, 4.12,
     0, 0, 0,
     78.500165, 94.99, 94.99, 94.99,
     '2025-11-02 10:21:00', '2025-11-04 09:00:00');

INSERT INTO `aalv_order_detail`
    (`id_order_detail`, `id_order`, `product_id`, `product_attribute_id`, `product_reference`, `product_name`,
     `product_ean13`, `product_quantity`, `product_quantity_return`, `unit_price_tax_excl`, `unit_price_tax_incl`,
     `total_price_tax_excl`, `total_price_tax_incl`, `reduction_amount_tax_incl`, `tax_rate`, `id_customization`)
VALUES
    (9001, 1001, 101, 0, 'E2E-PROD-101', 'Funda de rifle E2E',
     '8400000001012', 1, 0, 49.586777, 60.00, 49.586777, 60.00, 0, 21.000, 0),
    (9002, 1001, 102, 0, 'E2E-PROD-102', 'Kit limpieza E2E',
     '8400000001029', 2, 0, 12.396694, 15.00, 24.793388, 30.00, 0, 21.000, 0);

INSERT INTO `aalv_order_payment`
    (`order_reference`, `payment_method`, `transaction_id`, `card_number`, `card_brand`, `card_expiration`, `amount`, `date_add`)
VALUES
    ('E2E000001', 'Tarjeta de crédito', 'TX-E2E-0001', '**** **** **** 4242', 'VISA', '12/2027', 94.99, '2025-11-02 10:21:30');

INSERT INTO `aalv_order_history` (`id_order`, `id_employee`, `id_order_state`, `date_add`) VALUES
    (1001, 0, 2, '2025-11-02 10:21:30'),
    (1001, 0, 4, '2025-11-04 09:00:00');

INSERT INTO `aalv_order_carrier` (`id_order`, `id_carrier`, `tracking_number`, `weight`, `date_add`)
VALUES (1001, 1, 'E2ETRACK123456', 1.25, '2025-11-04 09:00:00');

-- Return over line 9002
INSERT INTO `aalv_order_return` (`id_order_return`, `id_customer`, `id_order`, `state`, `question`, `date_add`, `date_upd`)
VALUES (1, 1, 1001, 1, 'El kit llegó incompleto', '2025-11-10 12:00:00', '2025-11-10 12:00:00');

INSERT INTO `aalv_order_return_detail` (`id_order_return`, `id_order_detail`, `id_customization`, `product_quantity`)
VALUES (1, 9002, 0, 1);

-- Voucher
INSERT INTO `aalv_cart_rule`
    (`id_cart_rule`, `id_customer`, `code`, `description`, `date_from`, `date_to`,
     `reduction_percent`, `reduction_amount`, `reduction_currency`, `minimum_amount`,
     `quantity`, `quantity_per_user`, `active`, `free_shipping`)
VALUES
    (1, 1, 'E2E-WELCOME10', 'Descuento de bienvenida E2E', '2025-01-01 00:00:00', '2027-01-01 00:00:00',
     10.00, 0, 1, 20.00, 1, 1, 1, 0);

-- Customer service thread + message on order 1001
INSERT INTO `aalv_customer_thread`
    (`id_customer_thread`, `id_lang`, `id_contact`, `id_customer`, `id_order`, `id_product`, `status`, `email`, `token`, `date_add`, `date_upd`)
VALUES
    (1, 1, 0, 1, 1001, 0, 'open', 'test@test.com', 'e2etoken0001', '2025-11-05 16:00:00', '2025-11-05 16:00:00');

INSERT INTO `aalv_customer_message`
    (`id_customer_thread`, `id_employee`, `message`, `file_name`, `ip_address`, `user_agent`, `read`, `private`, `date_add`, `date_upd`)
VALUES
    (1, 0, '¿Cuándo llega mi pedido E2E000001?', NULL, '127.0.0.1', 'E2E fixture', 0, 0, '2025-11-05 16:00:00', '2025-11-05 16:00:00');

-- Abandoned cart 501 (no order attached, has products -> helpdesk_context carts)
INSERT INTO `aalv_cart` (`id_cart`, `id_customer`, `id_currency`, `id_lang`, `id_carrier`, `id_shop`, `id_address_delivery`, `date_add`, `date_upd`)
VALUES (501, 1, 1, 1, 1, 1, 1, '2026-07-01 18:00:00', '2026-07-01 18:30:00');

INSERT INTO `aalv_cart_product` (`id_cart`, `id_product`, `id_product_attribute`, `id_address_delivery`, `quantity`, `date_add`)
VALUES (501, 101, 0, 1, 1, '2026-07-01 18:00:00');

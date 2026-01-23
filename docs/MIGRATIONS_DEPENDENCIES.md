# MIGRATION DEPENDENCIES MATRIX
## Critical Path for Database Execution

---

## DEPENDENCY GRAPH

```
Level 0: FOUNDATION (No Dependencies)
═════════════════════════════════════════════════════════════════
├── users_table
├── categories_table
├── langs_table
├── shops_table
├── settings_table
├── countries_table
└── application_logs_table

Level 1: AUTHENTICATION & ROLES (→ users)
═════════════════════════════════════════════════════════════════
├── role_tables (users_id FK)
├── group_tables (users_id FK)
└── helpdesk_customers (users_id FK)

Level 2: LOCATIONS & GEOGRAPHY (→ countries)
═════════════════════════════════════════════════════════════════
├── store_locations_table (countries_id FK)
└── ip_locations_table

Level 3: PRODUCTS & INVENTORY (→ categories, languages, shops)
═════════════════════════════════════════════════════════════════
├── manufacturers_table
├── products_table (categories_id FK, langs_id FK)
├── product_categories_table (products_id FK)
├── product_locations_table (products_id FK, store_locations_id FK)
├── warranty_types_table
├── warranties_table (products_id FK, warranty_types_id FK)
└── product_components_table (products_id FK)

Level 4: ORDERS & SHIPPING (→ products)
═════════════════════════════════════════════════════════════════
├── order_table (users_id FK, products_id FK)
├── order_components_table (products_id FK, order_id FK)
└── component_shipments_table (order_components_id FK)

Level 5: DOCUMENT TYPES & PREREQUISITES (→ users, languages, roles)
═════════════════════════════════════════════════════════════════
├── document_types_table (langs_id FK)
├── document_type_requirements_table (document_types_id FK)
├── document_type_requirement_langs_table (langs_id FK)
└── document_statuses_table

Level 6: DOCUMENT VALIDATION & STATUS (→ document_types, statuses)
═════════════════════════════════════════════════════════════════
├── document_status_transitions_table (document_statuses_id FK)
├── document_validation_conditions_table (document_types_id FK)
└── document_sla_policies_table (document_types_id FK)

Level 7: CORE DOCUMENTS (→ all Level 5-6 + users, products)
═════════════════════════════════════════════════════════════════
├── documents_table
│   ├── users_id FK
│   ├── document_types_id FK
│   └── shop_id FK
├── document_products_table (documents_id FK, products_id FK)
├── document_product_blockades_table (document_types_id FK, products_id FK)
├── document_sources_table
├── document_upload_types_table
└── document_syncs_table

Level 8: DOCUMENT OPERATIONS & HISTORY (→ documents, users)
═════════════════════════════════════════════════════════════════
├── document_actions_table (documents_id FK, users_id FK)
├── document_loads_table (documents_id FK)
├── document_notes_table (documents_id FK, users_id FK)
├── document_mails_table (documents_id FK)
├── document_status_histories_table (documents_id FK, statuses_id FK)
├── document_status_transition_logs_table (transitions_id FK)
├── document_validation_history_table (documents_id FK)
├── document_sla_breaches_table (documents_id FK, sla_policies_id FK)
└── document_storage_config_histories_table

Level 9: EMAIL & TEMPLATES (→ languages)
═════════════════════════════════════════════════════════════════
├── mail_templates_table (langs_id FK)
├── mail_layouts_table (langs_id FK)
├── mail_variables_table
├── mail_endpoints_table
├── mail_template_langs_table (mail_templates_id FK, langs_id FK)
├── mail_layout_langs_table (mail_layouts_id FK, langs_id FK)
├── mail_variable_langs_table (mail_variables_id FK, langs_id FK)
├── mail_endpoint_logs_table (mail_endpoints_id FK)
├── stage_email_actions_table
├── faq_tables
├── layout_tables
└── template_tables

Level 10: RETURNS PREREQUISITES (→ languages)
═════════════════════════════════════════════════════════════════
├── return_states_table
├── return_statuses_table (langs_id FK - via _lang tables)
├── return_status_lang_table (return_statuses_id FK, langs_id FK)
├── return_types_table (langs_id FK - via _lang tables)
├── return_type_lang_table (return_types_id FK, langs_id FK)
├── return_reasons_table (langs_id FK - via _lang tables)
├── return_reason_lang_table (return_reasons_id FK, langs_id FK)
└── return_policies_table

Level 11: CORE RETURNS (→ return prerequisites, products, users, documents)
═════════════════════════════════════════════════════════════════
├── return_requests_table
│   ├── users_id FK
│   ├── products_id FK
│   ├── return_statuses_id FK
│   ├── return_types_id FK
│   └── documents_id FK
├── return_request_products_table (return_requests_id FK, products_id FK)
├── product_return_rules_table (products_id FK, return_policies_id FK)
└── return_documents_table (return_requests_id FK, documents_id FK)

Level 12: RETURNS OPERATIONS (→ return_requests)
═════════════════════════════════════════════════════════════════
├── return_status_history_table (return_requests_id FK, statuses_id FK)
├── return_costs_table (return_requests_id FK)
├── return_communications_table (return_requests_id FK, users_id FK)
├── return_payments_table (return_requests_id FK)
├── return_discussions_table (return_requests_id FK, users_id FK)
├── return_attachments_table (return_requests_id FK)
├── return_barcodes_table (return_requests_id FK)
├── return_history_table (return_requests_id FK, users_id FK)
├── return_products_table (return_requests_id FK, products_id FK)
├── return_validations_table (return_requests_id FK)
├── return_inspections_table (return_requests_id FK, users_id FK)
├── return_exceptions_table (return_requests_id FK)
└── return_pdf_documents_table (return_requests_id FK)

Level 13: WEBHOOKS & INTEGRATIONS (→ users, shops)
═════════════════════════════════════════════════════════════════
├── webhook_integrations_table (shops_id FK)
├── webhook_event_catalog_table
├── webhook_api_keys_table (webhook_integrations_id FK)
├── webhook_events_table (webhook_integrations_id FK)
├── webhook_subscriptions_table (webhook_integrations_id FK)
├── webhook_deliveries_table (webhook_subscriptions_id FK)
├── webhook_delivery_logs_table (webhook_deliveries_id FK)
└── webhook_subscription_rules_table (webhook_subscriptions_id FK)

Level 14: NOTIFICATIONS (→ users)
═════════════════════════════════════════════════════════════════
├── notifications_table (users_id FK)
└── notification_tables
```

---

## EXECUTION ORDER BY LAYERS

### Step 1-7: Foundation (0-30 seconds)
```
CREATE users_table
CREATE categories_table
CREATE langs_table
CREATE shops_table
CREATE settings_table
CREATE countries_table
CREATE application_logs_table
```
**Why First:** No dependencies, required by all other tables

---

### Step 8-10: Authentication (30-60 seconds)
```
CREATE role_tables
CREATE group_tables
CREATE helpdesk_customers_table
```
**Why Second:** Only depend on users_table

---

### Step 11-14: Geography (60-90 seconds)
```
CREATE store_locations_table
CREATE ip_locations_table
```
**Why Third:** Need countries_table; support product locations

---

### Step 15-25: Products (90-120 seconds)
```
CREATE manufacturers_table
CREATE products_table
CREATE product_categories_table
CREATE product_locations_table
CREATE warranty_types_table
CREATE warranties_table
CREATE product_components_table
CREATE order_table
CREATE order_components_table
CREATE component_shipments_table
CREATE product_return_rules_table
```
**Why Fourth:** Products are master data needed for documents/returns

---

### Step 26-32: Document Foundation (120-150 seconds)
```
CREATE document_types_table
CREATE document_type_requirements_table
CREATE document_type_requirement_langs_table
CREATE document_statuses_table
CREATE document_status_transitions_table
CREATE document_validation_conditions_table
CREATE document_sla_policies_table
```
**Why Fifth:** Define document structure before creating documents

---

### Step 33-48: Core Documents (150-180 seconds)
```
CREATE documents_table
CREATE document_products_table
CREATE document_product_blockades_table
CREATE document_sources_table
CREATE document_upload_types_table
CREATE document_syncs_table
CREATE document_actions_table
CREATE document_loads_table
CREATE document_notes_table
CREATE document_mails_table
CREATE document_status_histories_table
CREATE document_status_transition_logs_table
CREATE document_validation_history_table
CREATE document_sla_breaches_table
CREATE document_storage_config_histories_table
CREATE document_configurations_table
CREATE document_storage_configuration_histories_table
```
**Why Sixth:** Core system functionality

---

### Step 49-61: Email System (180-210 seconds)
```
CREATE mail_templates_table
CREATE mail_layouts_table
CREATE mail_variables_table
CREATE mail_endpoints_table
CREATE mail_template_langs_table
CREATE mail_layout_langs_table
CREATE mail_variable_langs_table
CREATE mail_endpoint_logs_table
CREATE stage_email_actions_table (first)
CREATE faq_tables
CREATE layout_tables
CREATE template_tables
CREATE stage_email_actions_table (second - verify duplicate)
```
**Why Seventh:** Communication system, lower dependency

---

### Step 62-70: Return Foundation (210-240 seconds)
```
CREATE return_states_table
CREATE return_statuses_table
CREATE return_status_lang_table
CREATE return_types_table
CREATE return_type_lang_table
CREATE return_reasons_table
CREATE return_reason_lang_table
CREATE return_policies_table
```
**Why Eighth:** Define return system structure

---

### Step 71-80: Core Returns (240-270 seconds)
```
CREATE return_requests_table
CREATE return_request_products_table
CREATE return_documents_table
CREATE return_status_history_table
CREATE return_costs_table
CREATE return_communications_table
CREATE return_payments_table
CREATE return_discussions_table
CREATE return_attachments_table
CREATE return_barcodes_table
```
**Why Ninth:** Return operations

---

### Step 81-91: Return Operations (270-300 seconds)
```
CREATE return_history_table
CREATE return_products_table
CREATE return_validations_table
CREATE return_inspections_table
CREATE return_exceptions_table
CREATE return_pdf_documents_table
```
**Why Tenth:** Remaining return functionality

---

### Step 92-100: Webhooks & Notifications (300-330 seconds)
```
CREATE webhook_integrations_table
CREATE webhook_event_catalog_table
CREATE webhook_api_keys_table
CREATE webhook_events_table
CREATE webhook_subscriptions_table
CREATE webhook_deliveries_table
CREATE webhook_delivery_logs_table
CREATE webhook_subscription_rules_table
CREATE notifications_table
CREATE notification_tables
```
**Why Last:** Integrations, lowest critical priority

---

## CRITICAL FOREIGN KEY RELATIONSHIPS

### Documents → Dependencies
```
documents
├── users_id → users (document owner)
├── document_types_id → document_types (what type)
├── shop_id → shops (which shop)
└── (Many-to-Many via document_products → products)
```

### Returns → Dependencies
```
return_requests
├── users_id → users (who initiated)
├── products_id → products (what product)
├── return_statuses_id → return_statuses (current status)
├── return_types_id → return_types (RMA type)
└── documents_id → documents (linked document)
```

### Products → Dependencies
```
products
├── categories_id → categories (product category)
└── (Many-to-Many via product_locations → store_locations)

product_locations
├── products_id → products
└── store_locations_id → store_locations (where in stock)
```

### Webhooks → Dependencies
```
webhook_integrations
└── shops_id → shops (which shop uses webhooks)

webhook_subscriptions
└── webhook_integrations_id → webhook_integrations
```

---

## ROLLBACK STRATEGY

If a migration fails at any layer:

```
Layer Failed at  │ Action
─────────────────┼─────────────────────────────────────────
Foundation (1-7) │ STOP - Check database permissions
Auth (8-10)      │ Rollback to step 7, fix foreign keys
Geography (11)   │ Rollback to step 10, check countries exists
Products (15-25) │ Rollback to step 14, verify product schema
Documents (26+)  │ Rollback to step 25, verify document_types exists
Returns (62+)    │ Rollback to step 61, verify return_statuses exists
Webhooks (92+)   │ Rollback to step 91, lowest priority - can retry
```

**Rollback Command:**
```bash
# Rollback 1 migration
php artisan migrate:rollback --step=1

# Rollback 5 migrations
php artisan migrate:rollback --steps=5

# Rollback entire batch
php artisan migrate:rollback
```

---

## VERIFICATION QUERIES

After each major layer, verify:

```sql
-- Check foundation tables exist
SELECT COUNT(*) FROM users;
SELECT COUNT(*) FROM categories;

-- Check products loaded
SELECT COUNT(*) FROM products;

-- Check documents schema
SELECT COUNT(*) FROM document_types;
SELECT COUNT(*) FROM documents;

-- Check returns schema
SELECT COUNT(*) FROM return_statuses;
SELECT COUNT(*) FROM return_requests;

-- Check foreign key counts
SELECT TABLE_NAME, CONSTRAINT_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE REFERENCED_TABLE_NAME IS NOT NULL;
```

---

## PERFORMANCE OPTIMIZATION TIPS

1. **Disable foreign key checks during migration**
   ```sql
   SET FOREIGN_KEY_CHECKS=0;
   -- Run migrations
   SET FOREIGN_KEY_CHECKS=1;
   ```

2. **Increase lock timeout**
   ```sql
   SET SESSION innodb_lock_wait_timeout = 100;
   ```

3. **Use --step for large migrations**
   ```bash
   php artisan migrate --step
   ```

4. **Monitor progress**
   ```bash
   php artisan migrate:status
   ```

---

## DEPENDENCY SUMMARY

| Level | Component | Dependencies | Count |
|-------|-----------|--------------|-------|
| 0 | Foundation | None | 7 |
| 1 | Auth/Roles | Level 0 | 3 |
| 2 | Geography | Level 0 | 2 |
| 3 | Products | Level 0-2 | 11 |
| 4 | Documents-Setup | Level 0-3 | 7 |
| 5 | Documents-Core | Level 4-3 | 16 |
| 6 | Mail/Email | Level 0 | 13 |
| 7 | Returns-Setup | Level 0 | 8 |
| 8 | Returns-Core | Level 3,7 | 10 |
| 9 | Returns-Ops | Level 8 | 6 |
| 10 | Webhooks | Level 0-1 | 8 |
| 11 | Notifications | Level 1 | 2 |
| **TOTAL** | | | **100** |

---

## ESTIMATED TIMELINE

```
Level  │ Component      │ Time  │ Cumulative │ Risk
───────┼────────────────┼───────┼────────────┼──────────
0-1    │ Foundation     │ 30s   │ 30s        │ 🟢 Low
2-3    │ Auth/Products  │ 60s   │ 90s        │ 🟢 Low
4-5    │ Documents      │ 90s   │ 180s       │ 🟡 Medium
6      │ Mail           │ 30s   │ 210s       │ 🟢 Low
7-9    │ Returns        │ 120s  │ 330s       │ 🟡 Medium
10-11  │ Webhooks/Notif │ 30s   │ 360s       │ 🟢 Low
───────┴────────────────┴───────┴────────────┴──────────
TOTAL  │ 100 migrations │ 360s  │ 6 minutes  │ 🟡 Medium
```

---

**Generated:** 2025-12-29
**Database:** MySQL/MariaDB compatible
**Laravel:** v12
**Status:** ✅ Ready for execution

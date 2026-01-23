# Auditoría de Migraciones vs Entities - Módulo Documents
**Fecha de Generación:** 2025-12-29

---

## 🔴 CRÍTICO: PROBLEMA PRINCIPAL - TABLA DOCUMENTS INCOMPLETA

### Descripción del Problema

La migración `2025_12_20_010000_create_documents_table.php` es **INCOMPLETA**. Crea una tabla con solo 5 columnas cuando el modelo Entity requiere ~40 columnas.

### Comparativa: Entity vs Migración

#### ✅ COLUMNAS EN LA MIGRACIÓN (5 columnas)
```
- id (PK)
- type_id (FK → document_types)
- additional_attachments (JSON)
- assigned_user_id (FK → users)
- timestamps (created_at, updated_at)
```

#### ❌ COLUMNAS FALTANTES EN LA MIGRACIÓN (35 columnas)

**Datos del Documento:**
- `uid` - Identificador único UUID
- `proccess` - Nombre del proceso
- `source_id` - FK → document_sources (¿de dónde viene el documento?)
- `load_id` - FK → document_loads (¿cómo se cargó?)
- `sync_id` - FK → document_syncs (¿sincronización?)
- `upload_id` - FK → document_upload_types (¿tipo de carga?)
- `lang_id` - FK → langs (lenguaje)

**Información del Cliente (denormalizada):**
- `customer_id` - FK → prestashop.customers
- `customer_firstname`
- `customer_lastname`
- `customer_email`
- `customer_cellphone`
- `customer_dni`
- `customer_company`

**Información del Pedido (denormalizada):**
- `order_id` - FK → prestashop.orders
- `order_reference`
- `order_date` - DATETIME
- `cart_id` - FK → prestashop.carts

**Estado y Validación:**
- `status_id` - FK → document_statuses (estado actual)
- `sla_policy_id` - FK → document_sla_policies (política SLA)
- `requires_financing` - BOOLEAN
- `validation_status` - STRING
- `current_stage` - INTEGER (etapa actual)
- `total_stages` - INTEGER (total de etapas)
- `current_validator_group` - NULLABLE

**Fechas de Validación:**
- `validation_started_at` - DATETIME
- `validation_completed_at` - DATETIME

**Documentación cargada (denormalizados):**
- `required_documents` - JSON (array de documentos requeridos)
- `uploaded_documents` - JSON (array de documentos subidos)

**Timestamps:**
- `confirmed_at` - DATETIME (cuando se confirmó)
- `uploaded_confirmation_sent_at` - DATETIME (email de confirmación)
- `reminder_at` - DATETIME (próximo recordatorio)
- `reminder_sent_at` - DATETIME (último recordatorio enviado)

### Impacto

```
Datos que NO se pueden persistir correctamente:
├─ Todos los metadatos del cliente
├─ Todos los datos del orden
├─ Validaciones y flujos de estado
├─ Gestión de recordatorios
├─ Etapas de validación
└─ Campos dinámicos denormalizados
```

---

## 📊 ANÁLISIS POR TABLA

### 1. Tabla: `documents`
**Estado:** 🔴 **CRÍTICO**

| Propiedad Entity | En Migración | Estado |
|---|---|---|
| uid | ❌ NO | Faltante |
| type_id | ✅ SÍ | OK |
| proccess | ❌ NO | Faltante |
| source_id | ❌ NO | Faltante |
| load_id | ❌ NO | Faltante |
| sync_id | ❌ NO | Faltante |
| upload_id | ❌ NO | Faltante |
| lang_id | ❌ NO | Faltante |
| confirmed_at | ❌ NO | Faltante |
| uploaded_confirmation_sent_at | ❌ NO | Faltante |
| reminder_at | ❌ NO | Faltante |
| reminder_sent_at | ❌ NO | Faltante |
| order_id | ❌ NO | Faltante |
| customer_id | ❌ NO | Faltante |
| cart_id | ❌ NO | Faltante |
| order_reference | ❌ NO | Faltante |
| order_date | ❌ NO | Faltante |
| customer_firstname | ❌ NO | Faltante |
| customer_lastname | ❌ NO | Faltante |
| customer_email | ❌ NO | Faltante |
| customer_cellphone | ❌ NO | Faltante |
| customer_dni | ❌ NO | Faltante |
| customer_company | ❌ NO | Faltante |
| required_documents | ❌ NO | Faltante |
| uploaded_documents | ❌ NO | Faltante |
| additional_attachments | ✅ SÍ | OK |
| status_id | ❌ NO | Faltante |
| sla_policy_id | ❌ NO | Faltante |
| requires_financing | ❌ NO | Faltante |
| validation_status | ❌ NO | Faltante |
| current_stage | ❌ NO | Faltante |
| total_stages | ❌ NO | Faltante |
| current_validator_group | ❌ NO | Faltante |
| assigned_user_id | ✅ SÍ | OK |
| validation_started_at | ❌ NO | Faltante |
| validation_completed_at | ❌ NO | Faltante |
| created_at | ✅ SÍ | OK |
| updated_at | ✅ SÍ | OK |

**Análisis:** 35 de 37 columnas necesarias están **FALTANTES** en la migración.

---

### 2. Tabla: `document_types`
**Estado:** ✅ **OK**

Migración: `2025_12_29_020815_create_document_types_table.php`

```php
// ENTITY
$fillable = ['uid', 'slug', 'label', 'description', 'icon', 'color',
             'is_active', 'sort_order', 'sla_multiplier', 'validation_stages'];

// MIGRATION
✅ id, uid (uuid), slug, label, description, icon, color,
   is_active, sort_order, sla_multiplier, validation_stages,
   timestamps
```

**Conclusión:** Todas las propiedades están presentes.

---

### 3. Tabla: `document_upload_types`
**Estado:** ✅ **OK**

Migración: `2025_12_29_020816_create_document_upload_types_table.php`

```php
// ENTITY
$fillable = ['key', 'label', 'description', 'icon', 'color',
             'is_active', 'order'];

// MIGRATION
✅ id, key, label, description, icon, color, is_active, order, timestamps
```

**Conclusión:** Todas las propiedades están presentes.

---

### 4. Tabla: `document_sources`
**Estado:** ✅ **OK**

Migración: `2025_12_29_020809_create_document_sources_table.php`

```php
// ENTITY
$fillable = ['key', 'label', 'description', 'icon', 'color',
             'is_active', 'order'];

// MIGRATION
✅ id, key, label, description, icon, color, is_active, order, timestamps
```

**Conclusión:** Todas las propiedades están presentes.

---

### 5. Tabla: `document_loads`
**Estado:** ✅ **OK**

Migración: `2025_12_29_020802_create_document_loads_table.php`

```php
// ENTITY
$fillable = ['key', 'label', 'description', 'icon', 'color',
             'is_active', 'order'];

// MIGRATION
✅ id, key, label, description, icon, color, is_active, order, timestamps
```

**Conclusión:** Todas las propiedades están presentes.

---

### 6. Tabla: `document_syncs`
**Estado:** ✅ **OK**

Migración: `2025_12_29_020814_create_document_syncs_table.php`

```php
// ENTITY
$fillable = ['key', 'label', 'description', 'icon', 'color',
             'is_active', 'order'];

// MIGRATION
✅ id, key, label, description, icon, color, is_active, order, timestamps
```

**Conclusión:** Todas las propiedades están presentes.

---

### 7. Tabla: `document_statuses`
**Estado:** ✅ **OK**

Migración: `2025_12_29_020810_create_document_statuses_table.php`

```php
// ENTITY
$fillable = ['key', 'label', 'description', 'color', 'icon',
             'is_active', 'order'];

// MIGRATION
✅ id, key, label, description, color, icon, is_active, order, timestamps
```

**Conclusión:** Todas las propiedades están presentes.

---

### 8. Tabla: `document_actions`
**Estado:** ✅ **OK**

Migración: `2025_12_29_020801_create_document_actions_table.php`

```php
// ENTITY
$fillable = ['document_id', 'action_type', 'action_name', 'description',
             'metadata', 'performed_by', 'performed_by_type'];

// MIGRATION
✅ id, document_id (FK), action_type, action_name, description,
   metadata (JSON), performed_by, performed_by_type, timestamps
```

**Conclusión:** Todas las propiedades están presentes.

---

### 9. Tabla: `document_products`
**Estado:** ✅ **OK**

Migración: `2025_12_29_020805_create_document_products_table.php`

```php
// ENTITY
$fillable = ['document_id', 'product_id', 'product_name',
             'product_reference', 'quantity', 'price'];

// MIGRATION
✅ id, document_id (FK), product_id, product_name, product_reference,
   quantity, price (DECIMAL), timestamps
```

**Conclusión:** Todas las propiedades están presentes.

---

### 10. Tabla: `document_status_histories`
**Estado:** ✅ **OK**

Migración: `2025_12_29_020811_create_document_status_histories_table.php`

```php
// ENTITY
$fillable = ['document_id', 'from_status_id', 'to_status_id',
             'changed_by', 'reason', 'metadata'];

// MIGRATION
✅ id, document_id (FK), from_status_id (FK nullable), to_status_id (FK),
   changed_by, reason (TEXT), metadata (JSON), timestamps
```

**Conclusión:** Todas las propiedades están presentes.

---

### 11. Tabla: `document_mails`
**Estado:** ✅ **OK**

Migración: `2025_12_29_020803_create_document_mails_table.php`

```php
// ENTITY
$fillable = ['uid', 'document_id', 'email_type', 'recipient_email',
             'subject', 'body_html', 'body_text', 'template_id',
             'sent_by', 'metadata', 'status', 'error_message', 'sent_at'];

// MIGRATION
✅ id, uid (UUID), document_id (FK), email_type, recipient_email,
   subject, body_html (LONGTEXT), body_text (LONGTEXT nullable),
   template_id (FK nullable), sent_by, metadata (JSON), status,
   error_message (TEXT nullable), sent_at (TIMESTAMP nullable), timestamps
```

**Conclusión:** Todas las propiedades están presentes.

---

### 12. Tabla: `document_product_blockades`
**Estado:** ✅ **OK**

Migración: `2025_12_29_020806_create_document_product_blockades_table.php`

```php
// ENTITY
$fillable = ['source_id', 'product_id', 'product_attribute_id',
             'document_type_id'];

// MIGRATION
✅ id, source_id (FK nullable → document_sources), product_id,
   product_attribute_id, document_type_id (FK nullable → document_types),
   timestamps
```

**Conclusión:** Todas las propiedades están presentes.

---

## 🔄 ANÁLISIS DE LLAVES FORÁNEAS Y ORDEN DE EJECUCIÓN

### 📅 Cronología de Migraciones

```
2025-12-20 (Migraciones iniciales incompletas)
  ├─ 010000: documents ❌ INCOMPLETA
  ├─ 133801: document_requirement_translations
  ├─ 140613: document_validation_histories (duplicada)
  └─ 144051: document_validation_conditions (duplicada)

2025-12-21 (Configuración de validadores)
  └─ 180534: document_validator_group_configuration_histories

2025-12-22 (Configuración de almacenamiento)
  └─ 121007: document_storage_config_histories (duplicada)

2025-12-29 (Bloque principal - mayor volumen)
  ├─ 014767: document_type_requirements
  ├─ 014769: document_type_requirement_translations
  ├─ 020004: document_storage_configuration_histories (duplicada)
  ├─ 020512: document_stage_email_actions
  ├─ 020801-020820: Bloque principal de tablas
  ├─ 020908-020911: Tablas de grupos validadores
  └─ 020910: migrate_document_groups_to_validator_groups
```

### ✅ Análisis de Dependencias (Foreign Keys)

#### TIER 0: Tablas de Configuración (sin dependencias)
```
document_statuses
document_types
document_sla_policies
document_upload_types
document_validation_conditions
document_stage_email_actions
document_validator_groups
document_type_requirements
document_type_requirement_translations
document_sources
document_loads
document_syncs
```

**Migraciones:** 2025_12_29_020802 hasta 2025_12_29_020820 ✅

---

#### TIER 1: Tabla Raíz
```
documents
├─ Depende de: document_types, users
├─ Estado: ❌ INCOMPLETA
└─ Migración: 2025_12_20_010000
```

**Problema:** Esta migración se ejecuta PRIMERO, pero está incompleta.

---

#### TIER 2: Tablas que dependen de `documents`
```
document_actions
├─ FK document_id → documents ✅ cascadeOnDelete
└─ Migración: 2025_12_29_020801

document_products
├─ FK document_id → documents ✅ cascadeOnDelete
└─ Migración: 2025_12_29_020805

document_product_blockades
├─ FK source_id → document_sources ✅ nullOnDelete
├─ FK document_type_id → document_types ✅ nullOnDelete
└─ Migración: 2025_12_29_020806

document_status_histories
├─ FK document_id → documents ✅ cascadeOnDelete
├─ FK from_status_id → document_statuses ✅ nullOnDelete
├─ FK to_status_id → document_statuses ✅ cascadeOnDelete
└─ Migración: 2025_12_29_020811

document_mails
├─ FK document_id → documents ✅ cascadeOnDelete
├─ FK template_id → mail_templates ✅ nullOnDelete
└─ Migración: 2025_12_29_020803
```

**Todas las FK están correctamente configuradas** ✅

---

## ⚠️ PROBLEMAS IDENTIFICADOS

### 1. 🔴 **CRÍTICO: Migración de Documents Incompleta**

**Archivo:** `database/migrations/documents/2025_12_20_010000_create_documents_table.php`

**Problema:**
- La migración solo crea 5 columnas
- El Entity necesita 37 columnas
- 35 columnas están **FALTANTES**

**Consecuencias:**
```
❌ No se pueden almacenar datos del cliente
❌ No se pueden almacenar datos de la orden
❌ No se pueden rastrear validaciones
❌ No se puede gestionar el flujo de documentación
❌ La aplicación NO FUNCIONARÁ correctamente
```

---

### 2. 🟡 **ADVERTENCIA: Migraciones Duplicadas**

**Duplicada #1:** `document_validation_conditions`
```
2025_12_20_144051_create_document_validation_conditions_table.php
2025_12_29_020817_create_document_validation_conditions_table.php
```

**Duplicada #2:** `document_validation_histories`
```
2025_12_20_140613_create_document_validation_histories_table.php
2025_12_29_020818_create_document_validation_history_table.php
```

**Duplicada #3:** `document_storage_config_histories`
```
2025_12_22_121007_create_document_storage_config_histories_table.php
2025_12_29_020004_create_document_storage_configuration_histories_table.php
```

**Impacto:** Laravel fallará intentando crear la misma tabla dos veces.

---

### 3. 🟡 **ADVERTENCIA: Inconsistencia en Nombres de Tablas**

```
Entity:     DocumentValidationHistory
Migration:  document_validation_history vs document_validation_histories
```

Hay variaciones entre singular/plural que causarán conflictos.

---

### 4. ✅ **OK: Orden de Ejecución de Migraciones**

Aunque la migración de `documents` es antigua (2025_12_20), el orden sigue siendo lógico porque:

```
1. Se crean primero todas las tablas de configuración
   (statuses, types, sources, loads, syncs)

2. Se crea la tabla documents
   (depende de document_types)

3. Se crean las tablas subordinadas
   (actions, products, mails, etc.)
   (dependen de documents)
```

**Las fechas y secuencias son correctas para las dependencias** ✅

---

### 5. ✅ **OK: Foreign Keys y Cascadas**

Todas las foreign keys están correctamente configuradas:
```
cascadeOnDelete: Cuando se borra un documento, se borran sus acciones, productos, emails
nullOnDelete:    Cuando se borra una categoría, los referencias quedan NULL
```

**No hay conflictos de llaves foráneas** ✅

---

## 🔧 RECOMENDACIONES DE CORRECCIÓN

### Acción Inmediata

**1. Crear una nueva migración para COMPLETAR la tabla documents**

```bash
php artisan make:migration add_missing_columns_to_documents_table --table=documents
```

Contenido de la migración:
```php
Schema::table('documents', function (Blueprint $table) {
    // UUIDs y IDs
    $table->uuid('uid')->nullable()->after('id');

    // Procesos
    $table->string('proccess')->nullable();

    // Foreign Keys a configuraciones
    $table->foreignId('source_id')->nullable()->after('type_id')
        ->constrained('document_sources')->nullOnDelete();
    $table->foreignId('load_id')->nullable()
        ->constrained('document_loads')->nullOnDelete();
    $table->foreignId('sync_id')->nullable()
        ->constrained('document_syncs')->nullOnDelete();
    $table->foreignId('upload_id')->nullable()
        ->constrained('document_upload_types')->nullOnDelete();
    $table->foreignId('lang_id')->nullable()
        ->constrained('langs')->nullOnDelete();

    // Estado
    $table->foreignId('status_id')->nullable()
        ->constrained('document_statuses')->nullOnDelete();
    $table->foreignId('sla_policy_id')->nullable()
        ->constrained('document_sla_policies')->nullOnDelete();

    // Información del cliente (denormalizado)
    $table->unsignedBigInteger('customer_id')->nullable();
    $table->string('customer_firstname')->nullable();
    $table->string('customer_lastname')->nullable();
    $table->string('customer_email')->nullable();
    $table->string('customer_cellphone')->nullable();
    $table->string('customer_dni')->nullable();
    $table->string('customer_company')->nullable();

    // Información del pedido (denormalizado)
    $table->unsignedBigInteger('order_id')->nullable();
    $table->string('order_reference')->nullable();
    $table->dateTime('order_date')->nullable();
    $table->unsignedBigInteger('cart_id')->nullable();

    // Validación
    $table->string('validation_status')->nullable();
    $table->integer('current_stage')->nullable();
    $table->integer('total_stages')->nullable();
    $table->string('current_validator_group')->nullable();
    $table->boolean('requires_financing')->default(false);

    // Fechas especiales
    $table->dateTime('confirmed_at')->nullable();
    $table->dateTime('uploaded_confirmation_sent_at')->nullable();
    $table->dateTime('reminder_at')->nullable();
    $table->dateTime('reminder_sent_at')->nullable();
    $table->dateTime('validation_started_at')->nullable();
    $table->dateTime('validation_completed_at')->nullable();

    // JSON denormalizados
    $table->json('required_documents')->nullable();
    $table->json('uploaded_documents')->nullable();

    // Índices
    $table->index(['status_id', 'validation_status']);
    $table->index(['order_id', 'customer_id']);
    $table->index(['validation_started_at', 'validation_completed_at']);
});
```

---

**2. Consolidar migraciones duplicadas**

Opciones:
- **Opción A:** Renombrar las migraciones del 2025_12_29 para evitar conflictos
- **Opción B:** Eliminar las migraciones antiguas del 2025_12_20 y mantener solo las del 2025_12_29
- **Opción C:** Crear una migración de limpieza que verifique qué tablas existen

```bash
# Verificar estado actual
php artisan migrate:status | grep documents

# Si las migraciones han fallado, hacer rollback
php artisan migrate:rollback
```

---

**3. Eliminar migraciones duplicadas**

Remover estos archivos:
```
2025_12_20_144051_create_document_validation_conditions_table.php
2025_12_20_140613_create_document_validation_histories_table.php
2025_12_22_121007_create_document_storage_config_histories_table.php
```

Mantener solo:
```
2025_12_29_020817_create_document_validation_conditions_table.php
2025_12_29_020818_create_document_validation_history_table.php
2025_12_29_020004_create_document_storage_configuration_histories_table.php
```

---

**4. Renombrar tabla inconsistente**

Si existe `document_validation_history` (singular), renombrar a `document_validation_histories` (plural) para consistencia.

---

## 📋 CHECKLIST DE VALIDACIÓN

```
✅ All foreign keys properly defined
✅ Correct cascade/nullOnDelete strategies
✅ Entity fillable arrays match migration columns
❌ documents table is INCOMPLETE
⚠️ 3 migrations are DUPLICATED
⚠️ Naming inconsistencies detected
✅ Order of execution is correct (for dependencies)
```

---

## 🚀 SIGUIENTE PASO RECOMENDADO

1. **Crear migración de complemento** para la tabla documents
2. **Remover migraciones duplicadas**
3. **Ejecutar test de migración:**
   ```bash
   php artisan migrate:fresh
   php artisan tinker
   >>> DB::table('documents')->getColumnListing()
   // Verificar que todas las columnas esperadas existan
   ```
4. **Validar modelos Eloquent:**
   ```bash
   php artisan model:show modules\\Document\\Entities\\Document
   ```

---

## 📞 RESUMEN EJECUTIVO

| Aspecto | Estado | Prioridad |
|---|---|---|
| **Completitud de columnas** | ❌ CRÍTICO | 🔴 Máxima |
| **Migraciones duplicadas** | ⚠️ Advertencia | 🟠 Alta |
| **Orden de ejecución** | ✅ Correcto | ✅ OK |
| **Foreign Keys** | ✅ Correcto | ✅ OK |
| **Cascadas de borrado** | ✅ Correcto | ✅ OK |

**Conclusión:** Antes de ejecutar cualquier migración, la tabla `documents` DEBE ser completada con una migración adicional que añada las 35 columnas faltantes.


# Sistema de Automatización de Contenido Web con IA

> **Estado:** Borrador de requerimientos
> **Fecha:** 2025-12-19
> **Versión:** 2.0

---

## 1. OBJETIVO DEL SISTEMA

Diseñar e implementar un sistema automatizado, orquestado por Claude Code, que genere y mantenga actualizados los contenidos descriptivos de las fichas de producto web a partir de:

- La información de Gestión (ERP / sistema interno de tarifas y referencias)
- Fuentes externas configuradas por proveedor (webs, catálogos, PDFs, FTP, APIs)
- Un conjunto de prompts específicos por proveedor / categoría / tipo de producto

---

## 2. ESTRUCTURA DE DATOS

### 2.1 Tabla: `suppliers` (Proveedores)

Tabla principal de proveedores para el sistema de generación de contenido IA.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK autoincremental |
| `uid` | char(36) | Identificador único UUID |
| `label` | varchar(255) | Nombre del proveedor |
| `code` | varchar(50) | Código interno del proveedor |
| `erp_id` | int | ID del proveedor en sistema Gestión/ERP |
| `supplier_id` | int | FK a `aalv_supplier.id_supplier` (PrestaShop, opcional) |
| `website_url` | varchar(500) | URL principal del proveedor |
| `is_active` | boolean | Proveedor activo/inactivo |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

---

### 2.2 Tabla: `supplier_categories` (Relación Proveedor-Categorías)

Relaciona proveedores con categorías de PrestaShop.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `supplier_id` | bigint | FK a `suppliers` |
| `category_id` | int | FK a `aalv_category.id_category` (PrestaShop) |
| `is_primary` | boolean | ¿Es la categoría principal del proveedor? |
| `created_at` | timestamp | |

---

### 2.3 Tabla: `supplier_products` (Relación Proveedor-Productos)

Relaciona proveedores con productos de PrestaShop.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `supplier_id` | bigint | FK a `suppliers` |
| `product_id` | int | FK a `aalv_product.id_product` (PrestaShop) |
| `erp_reference` | varchar(100) | Referencia del producto en ERP |
| `ean` | varchar(20) | Código EAN/barras |
| `sync_status` | enum | `pending`, `synced`, `error` |
| `last_synced_at` | timestamp | Última sincronización |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

---

### 2.4 Tabla: `supplier_sources` (Fuentes de Información)

Define las fuentes de donde se extrae información para cada proveedor.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `uid` | char(36) | UUID |
| `supplier_id` | bigint | FK a `suppliers` |
| `source_type` | enum | `website`, `ftp`, `file`, `api` |
| `label` | varchar(255) | Nombre descriptivo de la fuente |
| `description` | text | Notas sobre la fuente |
| `trust_level` | enum | `high`, `medium`, `low` |
| `usage_notes` | text | Restricciones (ej: "solo inspiración") |
| `priority` | int | Orden de prioridad (1 = mayor prioridad) |
| `is_active` | boolean | |
| `last_accessed_at` | timestamp | Última vez que se accedió |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Tipos de fuente (`source_type`):**

| Tipo | Descripción |
|------|-------------|
| `website` | Páginas web para scraping |
| `ftp` | Archivos en servidor FTP/SFTP |
| `file` | Archivos locales (PDF, Excel, CSV) |
| `api` | API del proveedor |

---

### 2.5 Tabla: `supplier_source_options` (Opciones de Fuente - Dinámico)

Almacena las opciones/configuraciones específicas de cada fuente. Es dinámica según el `source_type`.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `source_id` | bigint | FK a `supplier_sources` |
| `option_key` | varchar(100) | Clave de la opción |
| `option_value` | text | Valor de la opción |
| `option_type` | varchar(50) | Tipo de dato: `string`, `url`, `json`, `path` |
| `is_required` | boolean | ¿Es obligatorio? |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Opciones según `source_type`:**

#### Para `website` (Scraping):
| option_key | Descripción | Ejemplo |
|------------|-------------|---------|
| `base_url` | URL base del sitio | `https://proveedor.com` |
| `product_url_pattern` | Patrón URL de producto | `/producto/{sku}` |
| `search_url` | URL de búsqueda | `/search?q={query}` |
| `selectors` | Selectores CSS/XPath (JSON) | `{"name": "h1.title", "desc": ".description"}` |
| `pagination` | Config paginación (JSON) | `{"param": "page", "max": 10}` |
| `headers` | Headers HTTP (JSON) | `{"User-Agent": "..."}` |
| `auth_required` | Requiere autenticación | `true/false` |
| `auth_config` | Config autenticación (JSON) | `{"user": "x", "pass": "y"}` |
| `rate_limit` | Requests por minuto | `30` |

#### Para `ftp` (Archivos remotos):
| option_key | Descripción | Ejemplo |
|------------|-------------|---------|
| `host` | Servidor FTP/SFTP | `ftp.proveedor.com` |
| `port` | Puerto | `21` o `22` |
| `protocol` | Protocolo | `ftp`, `sftp`, `ftps` |
| `username` | Usuario | `user123` |
| `password` | Contraseña (encriptada) | `****` |
| `remote_path` | Ruta en servidor | `/catalogs/2024/` |
| `file_pattern` | Patrón de archivos | `*.pdf`, `catalog_*.xlsx` |
| `file_format` | Formato esperado | `pdf`, `xlsx`, `csv` |
| `sync_frequency` | Frecuencia sync | `daily`, `weekly` |

#### Para `file` (Archivos locales):
| option_key | Descripción | Ejemplo |
|------------|-------------|---------|
| `local_path` | Ruta local | `/storage/suppliers/nike/` |
| `file_pattern` | Patrón de archivos | `*.pdf` |
| `file_format` | Formato | `pdf`, `xlsx`, `csv`, `json` |
| `encoding` | Codificación | `UTF-8`, `ISO-8859-1` |
| `delimiter` | Delimitador (CSV) | `;`, `,` |
| `sheet_name` | Hoja Excel | `Productos` |

#### Para `api` (APIs):
| option_key | Descripción | Ejemplo |
|------------|-------------|---------|
| `base_url` | URL base API | `https://api.proveedor.com/v1` |
| `auth_type` | Tipo auth | `bearer`, `basic`, `api_key`, `oauth2` |
| `api_key` | API Key | `xxxx-yyyy-zzzz` |
| `auth_header` | Header de auth | `Authorization` |
| `endpoints` | Endpoints (JSON) | `{"products": "/products", "details": "/product/{id}"}` |
| `rate_limit` | Requests por minuto | `60` |
| `response_format` | Formato respuesta | `json`, `xml` |

---

### 2.6 Tabla: `supplier_prompts` (Prompts por Proveedor)

Almacena los prompts específicos con sistema de prioridad.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `uid` | char(36) | UUID |
| `supplier_id` | bigint | FK a `suppliers` (nullable) |
| `category_id` | int | FK a categoría (nullable) |
| `source_id` | bigint | FK a `supplier_sources` (nullable) |
| `scope` | enum | `global`, `supplier`, `category`, `source` |
| `label` | varchar(255) | Nombre del prompt |
| `prompt_template` | text | Contenido del prompt |
| `output_language` | varchar(10) | Idioma: `es-ES`, `en-GB` |
| `tone` | varchar(50) | Tono: `technical`, `commercial`, `formal` |
| `priority` | int | Prioridad (1 = mayor). Determina cuál usar |
| `seo_focus` | boolean | ¿Optimizar para SEO? |
| `required_sections` | json | Secciones obligatorias |
| `version` | int | Versión del prompt |
| `is_default` | boolean | ¿Es el prompt por defecto? |
| `is_active` | boolean | |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Sistema de Prioridad de Prompts:**

```
Orden de resolución (de mayor a menor prioridad):
1. Prompt específico de SOURCE + SUPPLIER + CATEGORY
2. Prompt específico de SUPPLIER + CATEGORY
3. Prompt específico de SOURCE + SUPPLIER
4. Prompt específico de SUPPLIER
5. Prompt específico de CATEGORY
6. Prompt GLOBAL por defecto
```

| scope | supplier_id | category_id | source_id | Uso |
|-------|-------------|-------------|-----------|-----|
| `source` | X | X | X | Prompt para fuente específica de proveedor en categoría |
| `source` | X | null | X | Prompt para fuente específica de proveedor |
| `supplier` | X | X | null | Prompt para proveedor en categoría específica |
| `supplier` | X | null | null | Prompt general del proveedor |
| `category` | null | X | null | Prompt por categoría (cualquier proveedor) |
| `global` | null | null | null | Prompt por defecto del sistema |

---

### 2.7 Tabla: `supplier_contents` (Contenidos Generados)

Almacena el contenido generado por IA para cada producto.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `uid` | char(36) | UUID |
| `supplier_id` | bigint | FK a `suppliers` |
| `supplier_product_id` | bigint | FK a `supplier_products` (nullable) |
| `product_id` | int | FK a `aalv_product` PrestaShop (nullable) |
| `erp_reference` | varchar(100) | ID referencia de Gestión |
| `model_id` | varchar(100) | ID modelo compartido Gestión/Web |
| `ean` | varchar(20) | Código EAN/barras |
| `status` | enum | Ver estados abajo |
| `generated_name` | varchar(255) | Nombre producto generado |
| `short_description` | text | Descripción corta |
| `long_description` | text | Descripción larga |
| `bullet_points` | json | Lista de características |
| `seo_title` | varchar(70) | Meta title |
| `seo_description` | varchar(160) | Meta description |
| `seo_keywords` | varchar(255) | Keywords |
| `source_attributes` | json | Atributos originales de Gestión |
| `sources_used` | json | Fuentes consultadas (IDs + URLs) |
| `prompt_id` | bigint | FK a `supplier_prompts` utilizado |
| `generation_metadata` | json | Metadatos de generación (modelo IA, tokens, etc.) |
| `error_message` | text | Mensaje de error si falló |
| `validated_by` | bigint | FK a `users` |
| `validated_at` | timestamp | |
| `rejection_reason` | text | Motivo de rechazo |
| `published_at` | timestamp | |
| `synced_to_erp_at` | timestamp | Cuando se notificó a Gestión |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Estados (`status`):**

| Estado | Descripción |
|--------|-------------|
| `pending_generation` | En cola para procesar por IA |
| `generating` | Procesando actualmente |
| `pending_validation` | Generado, esperando revisión humana |
| `in_review` | En proceso de revisión |
| `needs_revision` | Requiere correcciones |
| `validated` | Validado, listo para publicar |
| `published` | Publicado en web |
| `rejected` | Rechazado definitivamente |
| `error_insufficient_info` | Error: información insuficiente |
| `error_source_unavailable` | Error: fuente no accesible |
| `error_generation_failed` | Error: falló la generación IA |

---

### 2.8 Tabla: `supplier_content_logs` (Trazabilidad)

Registra todas las acciones sobre los contenidos.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `content_id` | bigint | FK a `supplier_contents` |
| `action` | varchar(50) | Acción realizada |
| `previous_status` | varchar(50) | Estado anterior |
| `new_status` | varchar(50) | Estado nuevo |
| `user_id` | bigint | FK a `users` (nullable si automático) |
| `details` | json | Detalles adicionales |
| `ip_address` | varchar(45) | IP del usuario |
| `created_at` | timestamp | |

**Acciones (`action`):**

| Acción | Descripción |
|--------|-------------|
| `created` | Registro creado |
| `generation_started` | Inicio generación IA |
| `generation_completed` | Generación completada |
| `generation_failed` | Generación falló |
| `validated` | Contenido validado |
| `rejected` | Contenido rechazado |
| `revision_requested` | Solicitud de revisión |
| `edited` | Editado manualmente |
| `published` | Publicado en PrestaShop |
| `synced_to_erp` | Sincronizado con ERP |

---

## 3. RELACIONES ENTRE ENTIDADES

```
suppliers
    ├── hasMany → supplier_categories
    ├── hasMany → supplier_products
    ├── hasMany → supplier_sources
    │                 └── hasMany → supplier_source_options
    ├── hasMany → supplier_prompts
    ├── hasMany → supplier_contents
    └── belongsTo → aalv_supplier (PrestaShop, opcional)

supplier_sources
    ├── belongsTo → suppliers
    ├── hasMany → supplier_source_options
    └── hasMany → supplier_prompts (prompts específicos por fuente)

supplier_prompts
    ├── belongsTo → suppliers (opcional)
    ├── belongsTo → supplier_sources (opcional)
    ├── belongsTo → aalv_category (opcional)
    └── hasMany → supplier_contents

supplier_contents
    ├── belongsTo → suppliers
    ├── belongsTo → supplier_products (opcional)
    ├── belongsTo → supplier_prompts
    ├── belongsTo → users (validador)
    ├── hasMany → supplier_content_logs
    └── belongsTo → aalv_product (cuando se publique)
```

---

## 4. DIAGRAMA VISUAL DE RELACIONES

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              SUPPLIERS                                       │
│  ┌─────────┐                                                                │
│  │suppliers│                                                                │
│  └────┬────┘                                                                │
│       │                                                                     │
│       ├──────────────┬──────────────┬──────────────┬──────────────┐        │
│       │              │              │              │              │        │
│       ▼              ▼              ▼              ▼              ▼        │
│  ┌─────────┐   ┌──────────┐  ┌───────────┐  ┌──────────┐  ┌──────────┐    │
│  │supplier_│   │supplier_ │  │supplier_  │  │supplier_ │  │supplier_ │    │
│  │categories│  │products  │  │sources    │  │prompts   │  │contents  │    │
│  └────┬─────┘  └────┬─────┘  └─────┬─────┘  └────┬─────┘  └────┬─────┘    │
│       │             │              │             │             │          │
│       │             │              ▼             │             ▼          │
│       │             │        ┌───────────┐       │       ┌──────────┐     │
│       │             │        │supplier_  │       │       │supplier_ │     │
│       │             │        │source_    │       │       │content_  │     │
│       │             │        │options    │       │       │logs      │     │
│       │             │        └───────────┘       │       └──────────┘     │
│       │             │                            │                        │
└───────┼─────────────┼────────────────────────────┼────────────────────────┘
        │             │                            │
        ▼             ▼                            ▼
┌───────────────────────────────────────────────────────────────────────────┐
│                           PRESTASHOP                                       │
│  ┌────────────┐    ┌────────────┐    ┌────────────┐                       │
│  │aalv_       │    │aalv_       │    │aalv_       │                       │
│  │category    │    │product     │    │supplier    │                       │
│  └────────────┘    └────────────┘    └────────────┘                       │
└───────────────────────────────────────────────────────────────────────────┘
```

---

## 5. FLUJO DE RESOLUCIÓN DE PROMPTS

```
┌─────────────────────────────────────────────────────────────────┐
│  INPUT: supplier_id, category_id, source_id                    │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  1. ¿Existe prompt para SOURCE + SUPPLIER + CATEGORY?          │
│     scope='source', supplier_id=X, category_id=Y, source_id=Z  │
│     → SI: Usar este prompt                                     │
│     → NO: Continuar                                            │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  2. ¿Existe prompt para SUPPLIER + CATEGORY?                   │
│     scope='supplier', supplier_id=X, category_id=Y             │
│     → SI: Usar este prompt                                     │
│     → NO: Continuar                                            │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  3. ¿Existe prompt para SOURCE + SUPPLIER?                     │
│     scope='source', supplier_id=X, source_id=Z                 │
│     → SI: Usar este prompt                                     │
│     → NO: Continuar                                            │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  4. ¿Existe prompt para SUPPLIER?                              │
│     scope='supplier', supplier_id=X                            │
│     → SI: Usar este prompt                                     │
│     → NO: Continuar                                            │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  5. ¿Existe prompt para CATEGORY?                              │
│     scope='category', category_id=Y                            │
│     → SI: Usar este prompt                                     │
│     → NO: Continuar                                            │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  6. Usar prompt GLOBAL por defecto                             │
│     scope='global', is_default=true                            │
└─────────────────────────────────────────────────────────────────┘
```

---

## 6. EJEMPLOS DE CONFIGURACIÓN

### 6.1 Ejemplo: Proveedor con Scraping Web

```json
// supplier_sources
{
  "id": 1,
  "supplier_id": 5,
  "source_type": "website",
  "label": "Web oficial Nike",
  "trust_level": "high",
  "priority": 1
}

// supplier_source_options para source_id=1
[
  {"option_key": "base_url", "option_value": "https://www.nike.com/es"},
  {"option_key": "product_url_pattern", "option_value": "/es/t/{slug}"},
  {"option_key": "selectors", "option_value": "{\"name\": \"h1.product-title\", \"desc\": \".description-text\", \"specs\": \".product-specs li\"}"},
  {"option_key": "rate_limit", "option_value": "30"},
  {"option_key": "headers", "option_value": "{\"User-Agent\": \"Mozilla/5.0...\"}"}
]
```

### 6.2 Ejemplo: Proveedor con FTP

```json
// supplier_sources
{
  "id": 2,
  "supplier_id": 8,
  "source_type": "ftp",
  "label": "Catálogo FTP Adidas",
  "trust_level": "high",
  "priority": 1
}

// supplier_source_options para source_id=2
[
  {"option_key": "host", "option_value": "ftp.adidas-catalog.com"},
  {"option_key": "port", "option_value": "22"},
  {"option_key": "protocol", "option_value": "sftp"},
  {"option_key": "username", "option_value": "alsernet_user"},
  {"option_key": "password", "option_value": "encrypted:xxxx"},
  {"option_key": "remote_path", "option_value": "/exports/ES/"},
  {"option_key": "file_pattern", "option_value": "catalog_*.xlsx"},
  {"option_key": "sync_frequency", "option_value": "daily"}
]
```

### 6.3 Ejemplo: Proveedor con Archivos Locales

```json
// supplier_sources
{
  "id": 3,
  "supplier_id": 12,
  "source_type": "file",
  "label": "PDFs Catálogo Puma",
  "trust_level": "medium",
  "priority": 2
}

// supplier_source_options para source_id=3
[
  {"option_key": "local_path", "option_value": "/storage/suppliers/puma/catalogs/"},
  {"option_key": "file_pattern", "option_value": "*.pdf"},
  {"option_key": "file_format", "option_value": "pdf"}
]
```

### 6.4 Ejemplo: Proveedor con API

```json
// supplier_sources
{
  "id": 4,
  "supplier_id": 15,
  "source_type": "api",
  "label": "API Productos Reebok",
  "trust_level": "high",
  "priority": 1
}

// supplier_source_options para source_id=4
[
  {"option_key": "base_url", "option_value": "https://api.reebok.com/v2"},
  {"option_key": "auth_type", "option_value": "bearer"},
  {"option_key": "api_key", "option_value": "encrypted:yyyy"},
  {"option_key": "endpoints", "option_value": "{\"inventaries\": \"/inventaries\", \"details\": \"/inventaries/{sku}\"}"},
  {"option_key": "rate_limit", "option_value": "100"},
  {"option_key": "response_format", "option_value": "json"}
]
```

---

## 7. FLUJO DEL PROCESO DIARIO

```
┌─────────────────────────────────────────────────────────────────┐
│  1. ERP/GESTIÓN: Exporta referencias nuevas                    │
│     └── Archivo CSV/JSON con: ref, proveedor, EAN, atributos   │
└─────────────────────────────────────────────────────────────────┘
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  2. SISTEMA: Importa referencias                               │
│     └── Crea registros en supplier_contents (pending_generation)│
│     └── Vincula con supplier_products si existe                │
└─────────────────────────────────────────────────────────────────┘
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  3. CLAUDE CODE: Para cada contenido pendiente                 │
│     ├── Identifica supplier + category                         │
│     ├── Obtiene fuentes activas (supplier_sources)             │
│     │   ├── Website → Ejecuta scraping con opciones            │
│     │   ├── FTP → Descarga archivos según config               │
│     │   ├── File → Lee archivos locales                        │
│     │   └── API → Consulta endpoints                           │
│     ├── Resuelve prompt (según prioridad)                      │
│     └── Genera contenido                                       │
└─────────────────────────────────────────────────────────────────┘
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  4. SISTEMA: Guarda contenido generado                         │
│     └── status = 'pending_validation'                          │
│     └── Registra log de generación                             │
└─────────────────────────────────────────────────────────────────┘
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  5. BACKOFFICE: Cola de validación                             │
│     └── Revisar textos                                         │
│     └── Editar si necesario                                    │
│     └── Adjuntar imágenes                                      │
│     └── Validar / Rechazar / Pedir revisión                    │
└─────────────────────────────────────────────────────────────────┘
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  6. SISTEMA: Publica en PrestaShop                             │
│     └── Crea/actualiza aalv_product + aalv_product_lang        │
│     └── status = 'published'                                   │
└─────────────────────────────────────────────────────────────────┘
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  7. SINCRONIZACIÓN: Notifica a ERP                             │
│     └── Marca referencia como publicada                        │
│     └── synced_to_erp_at = now()                               │
└─────────────────────────────────────────────────────────────────┘
```

---

## 8. PREGUNTAS PENDIENTES

### 8.1 Sobre ERP/Gestión
- [ ] ¿Formato exacto del archivo de exportación? (CSV, JSON, XML)
- [ ] ¿Qué atributos incluye? (color, talla, material, uso, etc.)
- [ ] ¿Frecuencia de exportación? (diaria, tiempo real)
- [ ] ¿Método de sincronización de vuelta? (API, archivo, BD)

### 8.2 Sobre Fuentes
- [ ] ¿Qué proveedores tienen API disponible?
- [ ] ¿Credenciales FTP ya existen?
- [ ] ¿Dónde se almacenarán los archivos locales?
- [ ] ¿Restricciones de scraping por proveedor?

### 8.3 Sobre Prompts
- [ ] ¿Quién puede crear/editar prompts?
- [ ] ¿Cuántos idiomas se necesitan?
- [ ] ¿Hay guías de estilo por tipo de producto?

### 8.4 Sobre Validación
- [ ] ¿Cuántas personas validarán?
- [ ] ¿Flujo de aprobación multinivel?
- [ ] ¿SLA de tiempo en cola?

---

## 9. INTEGRACIÓN CON N8N (ORQUESTADOR DE AUTOMATIZACIÓN)

El sistema utiliza **n8n** como orquestador para tareas de scraping web y procesamiento de archivos. Laravel actúa como el sistema central que:
1. Mantiene la configuración de fuentes
2. Envía trabajos a n8n via webhooks
3. Recibe resultados procesados

### 9.1 Arquitectura de Integración

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           LARAVEL (MANAGER)                                  │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐                      │
│  │ Job Queue   │───▶│ N8nClient   │───▶│ Webhook     │                      │
│  │ (Horizon)   │    │ Service     │    │ Dispatcher  │                      │
│  └─────────────┘    └─────────────┘    └─────────────┘                      │
│         ▲                                     │                              │
│         │                                     ▼                              │
│  ┌──────┴──────┐                    ┌─────────────────┐                     │
│  │ Callback    │◀───────────────────│ POST /webhook/  │                     │
│  │ Controller  │                    │ n8n/result      │                     │
│  └─────────────┘                    └─────────────────┘                     │
└─────────────────────────────────────────────────────────────────────────────┘
                                           │
                                           ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                                N8N SERVER                                    │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐                      │
│  │ Webhook     │───▶│ Router      │───▶│ Workflow    │                      │
│  │ Trigger     │    │ (por tipo)  │    │ Ejecutor    │                      │
│  └─────────────┘    └─────────────┘    └─────────────┘                      │
│                                              │                               │
│                     ┌────────────────────────┼────────────────────┐         │
│                     ▼                        ▼                    ▼         │
│            ┌─────────────┐          ┌─────────────┐      ┌─────────────┐   │
│            │ Web Scraper │          │ FTP Client  │      │ File Parser │   │
│            │ (Puppeteer) │          │             │      │ (PDF/Excel) │   │
│            └─────────────┘          └─────────────┘      └─────────────┘   │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

### 9.2 Tabla: `n8n_workflows` (Workflows de N8N)

Registra los workflows configurados en n8n para cada tipo de procesamiento.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `uid` | char(36) | UUID |
| `name` | varchar(255) | Nombre del workflow |
| `workflow_type` | enum | `scraper`, `ftp_sync`, `file_parser`, `api_fetcher` |
| `n8n_workflow_id` | varchar(100) | ID del workflow en n8n |
| `webhook_url` | varchar(500) | URL del webhook trigger |
| `callback_url` | varchar(500) | URL donde n8n envía resultados |
| `description` | text | Descripción del workflow |
| `default_config` | json | Configuración por defecto |
| `is_active` | boolean | |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Tipos de Workflow (`workflow_type`):**

| Tipo | Descripción | Uso |
|------|-------------|-----|
| `scraper` | Scraping de páginas web | Para `source_type=website` |
| `ftp_sync` | Sincronización de archivos FTP | Para `source_type=ftp` |
| `file_parser` | Procesamiento de archivos | Para `source_type=file` |
| `api_fetcher` | Consumo de APIs externas | Para `source_type=api` |

---

### 9.3 Tabla: `n8n_executions` (Ejecuciones de N8N)

Registra cada ejecución/job enviado a n8n.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `uid` | char(36) | UUID (usado como correlation_id) |
| `workflow_id` | bigint | FK a `n8n_workflows` |
| `source_id` | bigint | FK a `supplier_sources` |
| `content_id` | bigint | FK a `supplier_contents` (nullable) |
| `execution_type` | enum | `single`, `batch`, `scheduled` |
| `status` | enum | `pending`, `sent`, `processing`, `completed`, `failed`, `timeout` |
| `payload_sent` | json | Payload enviado a n8n |
| `response_received` | json | Respuesta de n8n |
| `error_message` | text | Error si falló |
| `n8n_execution_id` | varchar(100) | ID de ejecución en n8n |
| `started_at` | timestamp | Cuando se envió |
| `completed_at` | timestamp | Cuando se recibió respuesta |
| `duration_ms` | int | Duración en milisegundos |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

---

### 9.4 Payloads por Tipo de Fuente

#### 9.4.1 Payload para `website` (Scraping Web)

```json
{
  "correlation_id": "uuid-de-n8n-execution",
  "callback_url": "https://manager.test/api/n8n/callback",
  "job_type": "scrape_product",
  "source": {
    "source_id": 15,
    "supplier_id": 5,
    "supplier_code": "NIKE"
  },
  "target": {
    "content_id": 1234,
    "erp_reference": "REF-001234",
    "ean": "8412345678901",
    "product_name": "Nike Air Max 90"
  },
  "scraping_config": {
    "base_url": "https://www.nike.com/es",
    "urls_to_scrape": [
      "https://www.nike.com/es/t/air-max-90-zapatillas-abc123",
      "https://www.nike.com/es/t/air-max-90-detalles"
    ],
    "search_config": {
      "search_url": "https://www.nike.com/es/w?q={query}",
      "search_query": "Air Max 90 ABC123"
    },
    "selectors": {
      "product_name": "h1.product-title",
      "description": ".description-preview",
      "full_description": ".description-text",
      "specifications": ".product-specs li",
      "features": ".product-features li",
      "images": ".product-gallery img@src",
      "price": ".product-price"
    },
    "pagination": {
      "enabled": false,
      "selector": ".pagination a.next",
      "max_pages": 1
    },
    "wait_conditions": {
      "wait_for_selector": ".product-title",
      "timeout_ms": 10000
    },
    "headers": {
      "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)...",
      "Accept-Language": "es-ES,es;q=0.9"
    },
    "rate_limit": {
      "requests_per_minute": 30,
      "delay_between_requests_ms": 2000
    }
  },
  "output_format": {
    "include_html": false,
    "include_screenshots": true,
    "extract_images": true,
    "max_images": 10
  }
}
```

#### 9.4.2 Payload para `ftp` (Sincronización FTP)

```json
{
  "correlation_id": "uuid-de-n8n-execution",
  "callback_url": "https://manager.test/api/n8n/callback",
  "job_type": "ftp_sync",
  "source": {
    "source_id": 22,
    "supplier_id": 8,
    "supplier_code": "ADIDAS"
  },
  "ftp_config": {
    "protocol": "sftp",
    "host": "ftp.adidas-catalog.com",
    "port": 22,
    "username": "alsernet_user",
    "password": "encrypted:base64...",
    "remote_path": "/exports/ES/2024/",
    "file_patterns": ["catalog_*.xlsx", "products_*.csv"],
    "sync_mode": "incremental",
    "since_date": "2024-12-01",
    "download_to": "/tmp/n8n/adidas/"
  },
  "file_processing": {
    "format": "xlsx",
    "sheet_name": "Productos",
    "header_row": 1,
    "column_mapping": {
      "sku": "A",
      "name": "B",
      "description": "C",
      "ean": "D",
      "category": "E",
      "attributes": "F:J"
    },
    "filters": {
      "category_contains": ["Running", "Training"]
    }
  },
  "output_format": {
    "structure": "array_of_products",
    "include_raw_file": false
  }
}
```

#### 9.4.3 Payload para `file` (Procesamiento de Archivos Locales)

```json
{
  "correlation_id": "uuid-de-n8n-execution",
  "callback_url": "https://manager.test/api/n8n/callback",
  "job_type": "parse_file",
  "source": {
    "source_id": 33,
    "supplier_id": 12,
    "supplier_code": "PUMA"
  },
  "file_config": {
    "file_url": "https://manager.test/storage/suppliers/puma/catalog_2024.pdf",
    "file_type": "pdf",
    "file_name": "catalog_2024.pdf"
  },
  "parsing_config": {
    "pdf": {
      "extract_text": true,
      "extract_images": true,
      "ocr_enabled": true,
      "ocr_language": "spa",
      "pages": "all",
      "table_detection": true
    },
    "excel": {
      "sheet_name": "Productos",
      "header_row": 1,
      "data_start_row": 2,
      "column_mapping": {
        "reference": "A",
        "name": "B",
        "description": "C"
      }
    },
    "csv": {
      "delimiter": ";",
      "encoding": "UTF-8",
      "has_header": true
    }
  },
  "search_context": {
    "product_reference": "REF-PUMA-001",
    "ean": "8412345678902",
    "keywords": ["Puma RS-X", "zapatillas", "running"]
  },
  "output_format": {
    "extract_product_info": true,
    "include_page_numbers": true,
    "structured_output": true
  }
}
```

#### 9.4.4 Payload para `api` (Consumo de API)

```json
{
  "correlation_id": "uuid-de-n8n-execution",
  "callback_url": "https://manager.test/api/n8n/callback",
  "job_type": "api_fetch",
  "source": {
    "source_id": 44,
    "supplier_id": 15,
    "supplier_code": "REEBOK"
  },
  "api_config": {
    "base_url": "https://api.reebok.com/v2",
    "auth": {
      "type": "bearer",
      "token": "encrypted:base64..."
    },
    "endpoints": [
      {
        "name": "product_details",
        "method": "GET",
        "path": "/inventaries/{sku}",
        "params": {
          "sku": "RBK-12345"
        }
      },
      {
        "name": "product_specs",
        "method": "GET",
        "path": "/inventaries/{sku}/specifications",
        "params": {
          "sku": "RBK-12345",
          "lang": "es-ES"
        }
      }
    ],
    "rate_limit": {
      "requests_per_minute": 60
    },
    "retry": {
      "max_attempts": 3,
      "delay_ms": 1000
    }
  },
  "output_format": {
    "merge_responses": true,
    "flatten_structure": false
  }
}
```

---

### 9.5 Respuesta de N8N (Callback)

Estructura del callback que n8n envía de vuelta a Laravel:

```json
{
  "correlation_id": "uuid-de-n8n-execution",
  "status": "success",
  "execution_id": "n8n-exec-123456",
  "job_type": "scrape_product",
  "duration_ms": 15420,
  "data": {
    "product_name": "Nike Air Max 90",
    "description_short": "Zapatillas icónicas con amortiguación Air...",
    "description_long": "Las Nike Air Max 90 son un clásico reinventado...",
    "specifications": [
      {"key": "Material", "value": "Cuero y malla"},
      {"key": "Suela", "value": "Goma con Air visible"},
      {"key": "Cierre", "value": "Cordones"}
    ],
    "features": [
      "Amortiguación Air-Sole visible",
      "Diseño retro renovado",
      "Máxima comodidad"
    ],
    "images": [
      {"url": "https://...", "alt": "Vista frontal"},
      {"url": "https://...", "alt": "Vista lateral"}
    ],
    "source_urls": [
      "https://www.nike.com/es/t/air-max-90-zapatillas-abc123"
    ],
    "raw_html": null,
    "screenshots": [
      {"page": "product", "base64": "..."}
    ]
  },
  "metadata": {
    "pages_scraped": 1,
    "images_found": 5,
    "text_length": 1250
  },
  "errors": [],
  "warnings": [
    "Precio no encontrado en la página"
  ]
}
```

**Estados de respuesta (`status`):**

| Estado | Descripción |
|--------|-------------|
| `success` | Ejecución completada correctamente |
| `partial` | Completado con algunos errores |
| `failed` | Ejecución fallida |
| `timeout` | Tiempo de espera agotado |

---

## 10. FLUJOS DE PROCESAMIENTO POR TIPO

### 10.1 Flujo: Website (Scraping con n8n)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  1. TRIGGER: Nuevo contenido pendiente (status=pending_generation)          │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  2. LARAVEL: Prepara job de scraping                                        │
│     ├── Obtiene supplier_source donde source_type='website'                 │
│     ├── Lee supplier_source_options (URLs, selectores, etc.)                │
│     ├── Construye URLs objetivo (por referencia/EAN/nombre)                 │
│     ├── Resuelve prompt aplicable                                           │
│     └── Crea registro en n8n_executions (status=pending)                    │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  3. LARAVEL: Envía webhook a n8n                                            │
│     ├── POST a webhook_url del n8n_workflow                                 │
│     ├── Payload con scraping_config                                         │
│     └── Actualiza n8n_executions (status=sent)                              │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  4. N8N: Ejecuta workflow de scraping                                       │
│     ├── Puppeteer/Playwright navega a URLs                                  │
│     ├── Aplica selectores CSS para extraer datos                            │
│     ├── Captura screenshots si configurado                                  │
│     ├── Descarga imágenes si configurado                                    │
│     └── Estructura datos extraídos                                          │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  5. N8N: Callback a Laravel                                                 │
│     ├── POST a callback_url con datos extraídos                             │
│     └── Incluye correlation_id para vincular                                │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  6. LARAVEL: Recibe callback                                                │
│     ├── Actualiza n8n_executions (status=completed)                         │
│     ├── Almacena datos en supplier_contents.source_attributes               │
│     └── Dispara job de generación IA con datos + prompt                     │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  7. CLAUDE/IA: Genera contenido                                             │
│     ├── Usa datos scrapeados + prompt resuelto                              │
│     ├── Genera nombre, descripciones, SEO                                   │
│     └── Retorna contenido estructurado                                      │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  8. LARAVEL: Guarda contenido generado                                      │
│     ├── Actualiza supplier_contents con textos                              │
│     ├── status = 'pending_validation'                                       │
│     └── Registra log en supplier_content_logs                               │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

### 10.2 Flujo: FTP (Sincronización Programada)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  1. TRIGGER: Scheduler de Laravel (diario/semanal según config)             │
│     └── Cron: SyncFtpSourcesCommand                                         │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  2. LARAVEL: Para cada source_type='ftp' activo                             │
│     ├── Lee configuración FTP de supplier_source_options                    │
│     ├── Crea registro en n8n_executions                                     │
│     └── Envía webhook a n8n con ftp_config                                  │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  3. N8N: Ejecuta workflow de sincronización FTP                             │
│     ├── Conecta a servidor FTP/SFTP                                         │
│     ├── Lista archivos según file_pattern                                   │
│     ├── Filtra por fecha de modificación (incremental)                      │
│     ├── Descarga archivos nuevos/modificados                                │
│     ├── Parsea archivos (Excel, CSV, PDF)                                   │
│     └── Estructura datos por producto                                       │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  4. N8N: Callback con datos procesados                                      │
│     └── Array de productos con info extraída                                │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  5. LARAVEL: Procesa datos recibidos                                        │
│     ├── Para cada producto del catálogo FTP:                                │
│     │   ├── Busca si existe en supplier_products (por EAN/ref)              │
│     │   ├── Actualiza source_attributes                                     │
│     │   └── Marca para generación si es nuevo                               │
│     ├── Actualiza last_accessed_at en supplier_sources                      │
│     └── Log de sincronización                                               │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

### 10.3 Flujo: File (Procesamiento de Archivos Locales/Subidos)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  1. TRIGGER: Usuario sube archivo en backoffice                             │
│     └── O: Se detecta nuevo archivo en carpeta monitoreada                  │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  2. LARAVEL: Registra archivo                                               │
│     ├── Guarda en storage (MediaLibrary)                                    │
│     ├── Crea/actualiza supplier_source con file_path                        │
│     └── Dispara job de procesamiento                                        │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  3. LARAVEL: Envía a n8n para procesamiento                                 │
│     ├── Genera URL temporal del archivo                                     │
│     ├── Determina tipo (PDF, Excel, CSV)                                    │
│     └── Envía webhook con parsing_config                                    │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  4. N8N: Procesa archivo                                                    │
│     ├── Descarga archivo desde URL                                          │
│     ├── PDF: OCR + extracción de texto/tablas                               │
│     ├── Excel: Parseo de hojas y columnas                                   │
│     ├── CSV: Parseo con delimitador configurado                             │
│     └── Retorna datos estructurados                                         │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  5. LARAVEL: Almacena datos extraídos                                       │
│     ├── Guarda en tabla temporal o directamente en supplier_contents        │
│     └── Disponible para consultas en generación IA                          │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 11. SISTEMA DE PRIORIDADES POR TIPO DE FUENTE

Cada proveedor puede tener **múltiples fuentes** de diferentes tipos. El sistema debe determinar qué fuente usar primero.

### 11.1 Tabla: `supplier_source_priorities` (Prioridad de Fuentes)

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `supplier_id` | bigint | FK a `suppliers` |
| `source_id` | bigint | FK a `supplier_sources` |
| `priority` | int | Orden (1 = mayor prioridad) |
| `fallback_to` | bigint | FK a otra source si esta falla (nullable) |
| `conditions` | json | Condiciones para usar esta fuente |
| `created_at` | timestamp | |

**Ejemplo de `conditions`:**

```json
{
  "use_when": {
    "has_ean": true,
    "category_in": [10, 15, 22],
    "attribute_exists": "color"
  },
  "skip_when": {
    "product_name_contains": ["genérico", "sin marca"]
  }
}
```

### 11.2 Lógica de Resolución de Fuentes

```php
// Pseudocódigo de resolución
function resolveSourcesForProduct($product, $supplier) {
    $sources = $supplier->sources()
        ->where('is_active', true)
        ->orderBy('priority')
        ->get();

    $applicableSources = [];

    foreach ($sources as $source) {
        // Evaluar condiciones
        if ($this->meetsConditions($source, $product)) {
            $applicableSources[] = $source;
        }
    }

    return $applicableSources;
}

function fetchDataFromSources($sources, $product) {
    $data = [];

    foreach ($sources as $source) {
        try {
            $result = $this->fetchFromSource($source, $product);
            $data[$source->source_type] = $result;

            // Si ya tenemos suficiente info, parar
            if ($this->hasEnoughInfo($data)) {
                break;
            }
        } catch (SourceUnavailableException $e) {
            // Intentar fallback
            if ($source->fallback_to) {
                $fallbackSource = SupplierSource::find($source->fallback_to);
                $data[$fallbackSource->source_type] = $this->fetchFromSource($fallbackSource, $product);
            }
        }
    }

    return $data;
}
```

### 11.3 Ejemplo: Proveedor con Múltiples Fuentes

```
Proveedor: Nike (supplier_id=5)

Fuentes configuradas:
┌────────────────────────────────────────────────────────────────┐
│ Priority │ Type    │ Label              │ Fallback │ Notas    │
├──────────┼─────────┼────────────────────┼──────────┼──────────┤
│ 1        │ api     │ API Nike Connect   │ 2        │ Oficial  │
│ 2        │ website │ Web Nike España    │ 3        │ Scraping │
│ 3        │ ftp     │ Catálogo FTP       │ null     │ PDF/Excel│
│ 4        │ file    │ Catálogos locales  │ null     │ Backup   │
└────────────────────────────────────────────────────────────────┘

Flujo:
1. Intentar API → Si falla o no tiene el producto
2. Scraping web → Si falla o no encuentra
3. Buscar en catálogo FTP → Si no hay data reciente
4. Buscar en archivos locales → Último recurso
```

---

## 12. SINCRONIZACIÓN DE CATEGORÍAS PRESTASHOP

### 12.1 Tabla: `prestashop_categories` (Categorías Sincronizadas)

Copia local de las categorías de PrestaShop para facilitar consultas.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `ps_id_category` | int | ID en PrestaShop (`aalv_category`) |
| `ps_id_parent` | int | ID padre en PrestaShop |
| `name` | varchar(255) | Nombre de la categoría (idioma por defecto) |
| `full_path` | varchar(500) | Path completo: "Inicio > Deportes > Running" |
| `level_depth` | int | Nivel de profundidad |
| `is_active` | boolean | Activa en PrestaShop |
| `synced_at` | timestamp | Última sincronización |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### 12.2 Comando de Sincronización

```bash
php artisan suppliers:sync-prestashop-categories
```

Este comando:
1. Conecta a la base de datos de PrestaShop
2. Lee `aalv_category` y `aalv_category_lang`
3. Actualiza `prestashop_categories`
4. Mantiene integridad referencial con `supplier_categories`

---

## 13. PREGUNTAS PENDIENTES

### 13.1 Sobre ERP/Gestión
- [ ] ¿Formato exacto del archivo de exportación? (CSV, JSON, XML)
- [ ] ¿Qué atributos incluye? (color, talla, material, uso, etc.)
- [ ] ¿Frecuencia de exportación? (diaria, tiempo real)
- [ ] ¿Método de sincronización de vuelta? (API, archivo, BD)

### 13.2 Sobre Fuentes
- [ ] ¿Qué proveedores tienen API disponible?
- [ ] ¿Credenciales FTP ya existen?
- [ ] ¿Dónde se almacenarán los archivos locales?
- [ ] ¿Restricciones de scraping por proveedor?

### 13.3 Sobre Prompts
- [ ] ¿Quién puede crear/editar prompts?
- [ ] ¿Cuántos idiomas se necesitan?
- [ ] ¿Hay guías de estilo por tipo de producto?

### 13.4 Sobre Validación
- [ ] ¿Cuántas personas validarán?
- [ ] ¿Flujo de aprobación multinivel?
- [ ] ¿SLA de tiempo en cola?

### 13.5 Sobre N8N
- [ ] ¿URL del servidor n8n?
- [ ] ¿Autenticación para webhooks?
- [ ] ¿Límites de ejecuciones concurrentes?
- [ ] ¿Timeout máximo por workflow?

---

## 14. PRÓXIMOS PASOS

### Fase 1: Infraestructura Base
1. [ ] Crear migraciones para todas las tablas
2. [ ] Implementar modelos Eloquent con relaciones
3. [ ] Configurar conexión con base de datos PrestaShop
4. [ ] Sincronizar categorías de PrestaShop

### Fase 2: Integración N8N
5. [ ] Instalar/configurar servidor n8n
6. [ ] Crear workflows base (scraper, ftp_sync, file_parser)
7. [ ] Implementar endpoints de webhook en Laravel
8. [ ] Desarrollar N8nClient service

### Fase 3: Proveedores Piloto
9. [ ] Identificar 3 proveedores piloto (1 con web, 1 con FTP, 1 con API)
10. [ ] Configurar fuentes para cada proveedor
11. [ ] Crear prompts iniciales
12. [ ] Pruebas de scraping/sync

### Fase 4: Generación y Validación
13. [ ] Implementar cola de generación
14. [ ] Integrar con Claude API
15. [ ] Diseñar UI de backoffice para validación
16. [ ] Implementar flujo de publicación a PrestaShop

### Fase 5: Monitorización
17. [ ] Dashboard de estado de ejecuciones
18. [ ] Alertas de errores
19. [ ] Métricas de rendimiento

---

## 15. ADMINISTRACIÓN Y CONFIGURACIÓN AVANZADA

### 15.1 Tabla: `supplier_automation_settings` (Configuración Global)

Configuración centralizada para el sistema de automatización (n8n, Make, Zapier, o cualquier orquestador).

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `key` | varchar(100) | Clave de configuración (unique) |
| `value` | text | Valor (puede ser JSON) |
| `type` | enum | `string`, `integer`, `boolean`, `json`, `encrypted` |
| `category` | varchar(50) | Categoría: `connection`, `security`, `limits`, `defaults` |
| `description` | text | Descripción para UI |
| `is_sensitive` | boolean | Si es dato sensible (no mostrar en logs) |
| `updated_by` | bigint | FK a users |
| `updated_at` | timestamp | |

**Configuraciones recomendadas:**

```php
// Conexión al orquestador
'automation.base_url' => 'https://n8n.alsernet.com'
'automation.api_key' => 'encrypted:xxxxx'
'automation.webhook_secret' => 'encrypted:xxxxx'  // Para verificar callbacks
'automation.provider' => 'n8n'  // n8n, make, zapier, custom

// Límites
'automation.max_concurrent_executions' => 10
'automation.default_timeout_seconds' => 300
'automation.max_timeout_seconds' => 900
'automation.retry_attempts' => 3
'automation.retry_delay_seconds' => 60

// Rate Limiting
'automation.rate_limit_per_minute' => 30
'automation.rate_limit_per_hour' => 500
'automation.rate_limit_per_day' => 5000

// Defaults
'automation.default_scraping_delay_ms' => 2000
'automation.default_user_agent' => 'AlsernetBot/1.0'
```

---

### 15.2 Tabla: `supplier_credentials` (Credenciales Encriptadas)

Almacena credenciales de forma segura separadas de la configuración.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `uid` | char(36) | UUID |
| `supplier_id` | bigint | FK a suppliers (nullable para globales) |
| `source_id` | bigint | FK a supplier_sources (nullable) |
| `credential_type` | enum | `ftp`, `api`, `oauth`, `basic_auth`, `proxy` |
| `name` | varchar(100) | Nombre identificador |
| `credentials` | text | JSON encriptado con credenciales |
| `expires_at` | timestamp | Fecha de expiración (nullable) |
| `last_used_at` | timestamp | Última vez que se usó |
| `is_valid` | boolean | Si las credenciales son válidas |
| `validation_error` | text | Último error de validación |
| `created_by` | bigint | FK a users |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Estructura del campo `credentials` (encriptado):**

```json
// Para FTP
{
  "host": "ftp.proveedor.com",
  "port": 22,
  "username": "user123",
  "password": "secreto123",
  "private_key": "-----BEGIN RSA PRIVATE KEY-----..."
}

// Para API
{
  "api_key": "sk-xxxxx",
  "api_secret": "yyyyy",
  "base_url": "https://api.proveedor.com"
}

// Para OAuth2
{
  "client_id": "xxxxx",
  "client_secret": "yyyyy",
  "access_token": "zzzzz",
  "refresh_token": "wwwww",
  "token_expires_at": "2025-01-15T10:00:00Z"
}
```

---

### 15.3 Tabla: `supplier_automation_health_checks` (Monitoreo de Salud)

Registra el estado de salud del servidor de automatización y workflows.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `check_type` | enum | `server`, `workflow`, `webhook`, `credential` |
| `target_id` | varchar(100) | ID del workflow o endpoint |
| `status` | enum | `healthy`, `degraded`, `unhealthy`, `unknown` |
| `response_time_ms` | int | Tiempo de respuesta |
| `error_message` | text | Error si falló |
| `metadata` | json | Datos adicionales del check |
| `checked_at` | timestamp | |

**Job de Health Check (cada 5 minutos):**

```php
// App\Jobs\SupplierAutomationHealthCheckJob
class SupplierAutomationHealthCheckJob implements ShouldQueue
{
    public function handle(AutomationClient $client): void
    {
        // 1. Verificar servidor de automatización
        $serverHealth = $client->healthCheck();

        // 2. Verificar cada workflow activo
        $workflows = SupplierAutomationWorkflow::where('is_active', true)->get();
        foreach ($workflows as $workflow) {
            $this->checkWorkflow($client, $workflow);
        }

        // 3. Verificar credenciales próximas a expirar
        $this->checkExpiringCredentials();

        // 4. Alertar si hay problemas
        $this->sendAlertsIfNeeded();
    }
}
```

---

### 15.4 Tabla: `supplier_automation_rate_limits` (Control de Rate Limiting)

Controla el rate limiting por proveedor/fuente.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `limitable_type` | varchar(100) | `Supplier`, `SupplierSource`, `SupplierAutomationWorkflow` |
| `limitable_id` | bigint | ID del modelo |
| `window_type` | enum | `minute`, `hour`, `day` |
| `max_requests` | int | Límite de requests |
| `current_count` | int | Contador actual |
| `window_start` | timestamp | Inicio de la ventana actual |
| `blocked_until` | timestamp | Bloqueado hasta (si excedió) |
| `updated_at` | timestamp | |

**Middleware de Rate Limiting:**

```php
// En el AutomationClient
public function dispatch(SupplierAutomationExecution $execution): void
{
    $source = $execution->source;

    // Verificar rate limit del proveedor
    if ($this->isRateLimited($source->supplier)) {
        throw new RateLimitExceededException(
            "Proveedor {$source->supplier->label} bloqueado por rate limit"
        );
    }

    // Verificar rate limit de la fuente específica
    if ($this->isRateLimited($source)) {
        throw new RateLimitExceededException(
            "Fuente {$source->label} bloqueada por rate limit"
        );
    }

    // Incrementar contador
    $this->incrementRateLimit($source);
    $this->incrementRateLimit($source->supplier);

    // Enviar request
    $this->sendWebhook($execution);
}
```

---

### 15.5 Tabla: `supplier_automation_retry_queue` (Cola de Reintentos)

Maneja reintentos de ejecuciones fallidas.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `execution_id` | bigint | FK a supplier_automation_executions |
| `attempt_number` | int | Número de intento (1, 2, 3...) |
| `max_attempts` | int | Máximo de intentos configurado |
| `retry_at` | timestamp | Cuándo reintentar |
| `retry_strategy` | enum | `immediate`, `linear`, `exponential` |
| `last_error` | text | Último error |
| `status` | enum | `pending`, `retrying`, `exhausted`, `succeeded` |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Estrategias de Reintento:**

| Estrategia | Delay entre intentos | Uso recomendado |
|------------|---------------------|-----------------|
| `immediate` | 0, 0, 0 | Errores de red temporales |
| `linear` | 60s, 120s, 180s | Rate limiting suave |
| `exponential` | 60s, 300s, 900s | Rate limiting agresivo, APIs caídas |

```php
// Cálculo de delay exponencial
$delay = min(
    $baseDelay * pow(2, $attemptNumber - 1),
    $maxDelay
);
// Intento 1: 60s
// Intento 2: 120s
// Intento 3: 240s (cap a 900s)
```

---

### 15.6 Tabla: `supplier_automation_dead_letter_queue` (Cola de Fallidos)

Almacena ejecuciones que fallaron definitivamente para revisión manual.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `execution_id` | bigint | FK a supplier_automation_executions |
| `failure_reason` | enum | `max_retries`, `invalid_config`, `source_gone`, `timeout`, `manual` |
| `error_details` | json | Detalles completos del error |
| `original_payload` | json | Payload original enviado |
| `requires_action` | enum | `retry`, `fix_config`, `contact_supplier`, `skip`, `none` |
| `resolution_notes` | text | Notas del operador |
| `resolved_by` | bigint | FK a users |
| `resolved_at` | timestamp | |
| `created_at` | timestamp | |

---

## 16. SEGURIDAD DE WEBHOOKS

### 16.1 Verificación de Callbacks del Orquestador

Para asegurar que los callbacks realmente vienen del orquestador (n8n, Make, etc.):

```php
// routes/api.php
Route::post('/automation/callback', [AutomationCallbackController::class, 'handle'])
    ->middleware('verify.automation.signature');

// App\Http\Middleware\VerifyAutomationSignature
class VerifyAutomationSignature
{
    public function handle(Request $request, Closure $next)
    {
        $signature = $request->header('X-Automation-Signature');
        $timestamp = $request->header('X-Automation-Timestamp');
        $payload = $request->getContent();

        // Verificar timestamp (evitar replay attacks)
        if (abs(time() - (int)$timestamp) > 300) {
            abort(401, 'Timestamp expired');
        }

        // Verificar firma
        $secret = config('services.automation.webhook_secret');
        $expectedSignature = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

        if (!hash_equals($expectedSignature, $signature)) {
            abort(401, 'Invalid signature');
        }

        return $next($request);
    }
}
```

### 16.2 Generación de Firma en el Orquestador

Ejemplo en n8n, antes del HTTP Request de callback:

```javascript
// Nodo "Code" en n8n (o equivalente en otro orquestador)
const crypto = require('crypto');

const timestamp = Math.floor(Date.now() / 1000).toString();
const payload = JSON.stringify($input.all()[0].json);
const secret = $env.WEBHOOK_SECRET;

const signature = crypto
  .createHmac('sha256', secret)
  .update(timestamp + '.' + payload)
  .digest('hex');

return {
  headers: {
    'X-Automation-Signature': signature,
    'X-Automation-Timestamp': timestamp,
    'Content-Type': 'application/json'
  },
  body: payload
};
```

---

## 17. DASHBOARD DE ADMINISTRACIÓN

### 17.1 Métricas Principales (KPIs)

| Métrica | Descripción | Alerta si |
|---------|-------------|-----------|
| **Ejecuciones/hora** | Total de jobs enviados a n8n | < 10 (muy bajo) o > 500 (sobrecarga) |
| **Tasa de éxito** | % de ejecuciones exitosas | < 90% |
| **Tiempo promedio** | Duración media de ejecución | > 120 segundos |
| **Cola pendiente** | Jobs esperando ser enviados | > 100 |
| **Dead letters** | Fallidos sin resolver | > 10 |
| **Credenciales expirando** | Próximas a vencer | Cualquiera en < 7 días |

### 17.2 Tabla: `supplier_automation_metrics` (Métricas Agregadas)

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `metric_date` | date | Fecha de la métrica |
| `metric_hour` | int | Hora (0-23) para granularidad horaria |
| `workflow_id` | bigint | FK a supplier_automation_workflows (nullable para totales) |
| `supplier_id` | bigint | FK a suppliers (nullable para totales) |
| `executions_total` | int | Total de ejecuciones |
| `executions_success` | int | Exitosas |
| `executions_failed` | int | Fallidas |
| `executions_timeout` | int | Timeout |
| `avg_duration_ms` | int | Duración promedio |
| `max_duration_ms` | int | Duración máxima |
| `min_duration_ms` | int | Duración mínima |
| `data_extracted_kb` | int | KB de datos extraídos |
| `created_at` | timestamp | |

**Job de Agregación (cada hora):**

```php
// App\Jobs\AggregateAutomationMetricsJob
public function handle(): void
{
    $hour = now()->subHour();

    $metrics = SupplierAutomationExecution::query()
        ->where('created_at', '>=', $hour->startOfHour())
        ->where('created_at', '<', $hour->endOfHour())
        ->selectRaw('
            workflow_id,
            COUNT(*) as executions_total,
            SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as executions_success,
            SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as executions_failed,
            SUM(CASE WHEN status = "timeout" THEN 1 ELSE 0 END) as executions_timeout,
            AVG(duration_ms) as avg_duration_ms,
            MAX(duration_ms) as max_duration_ms,
            MIN(duration_ms) as min_duration_ms
        ')
        ->groupBy('workflow_id')
        ->get();

    foreach ($metrics as $metric) {
        SupplierAutomationMetric::create([
            'metric_date' => $hour->toDateString(),
            'metric_hour' => $hour->hour,
            'workflow_id' => $metric->workflow_id,
            // ... resto de campos
        ]);
    }
}
```

---

## 18. ALERTAS Y NOTIFICACIONES

### 18.1 Tabla: `supplier_automation_alerts` (Alertas del Sistema)

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `uid` | char(36) | UUID |
| `alert_type` | enum | Ver tipos abajo |
| `severity` | enum | `info`, `warning`, `error`, `critical` |
| `title` | varchar(255) | Título de la alerta |
| `message` | text | Mensaje detallado |
| `context` | json | Datos adicionales |
| `related_type` | varchar(100) | Modelo relacionado |
| `related_id` | bigint | ID del modelo |
| `acknowledged_by` | bigint | FK a users |
| `acknowledged_at` | timestamp | |
| `resolved_at` | timestamp | |
| `created_at` | timestamp | |

**Tipos de Alerta (`alert_type`):**

| Tipo | Severidad | Descripción |
|------|-----------|-------------|
| `server_unreachable` | critical | Orquestador no responde |
| `workflow_disabled` | warning | Workflow desactivado |
| `high_failure_rate` | error | > 20% de fallos en última hora |
| `rate_limit_exceeded` | warning | Proveedor bloqueado por rate limit |
| `credential_expiring` | warning | Credencial expira en < 7 días |
| `credential_expired` | error | Credencial expirada |
| `execution_timeout` | warning | Ejecución excedió timeout |
| `dead_letter_threshold` | error | > 10 items en dead letter queue |
| `queue_backlog` | warning | > 100 items pendientes |
| `scraping_blocked` | error | Proveedor bloqueó scraping |

### 18.2 Canales de Notificación

```php
// config/supplier-automation.php
return [
    'alerts' => [
        'channels' => [
            'critical' => ['slack', 'email', 'database'],
            'error' => ['slack', 'database'],
            'warning' => ['database'],
            'info' => ['database'],
        ],
        'recipients' => [
            'slack_webhook' => env('AUTOMATION_ALERTS_SLACK_WEBHOOK'),
            'email' => ['admin@alsernet.com', 'tech@alsernet.com'],
        ],
        'throttle' => [
            'same_alert_minutes' => 30,  // No repetir misma alerta en 30 min
        ],
    ],
];
```

---

## 19. VERSIONADO DE WORKFLOWS

### 19.1 Tabla: `supplier_automation_workflow_versions` (Historial de Versiones)

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `workflow_id` | bigint | FK a supplier_automation_workflows |
| `version` | int | Número de versión |
| `workflow_json` | json | Definición completa del workflow |
| `changelog` | text | Descripción de cambios |
| `is_active` | boolean | Si es la versión activa |
| `activated_at` | timestamp | Cuando se activó |
| `created_by` | bigint | FK a users |
| `created_at` | timestamp | |

**Flujo de Versionado:**

```
1. Exportar workflow desde el orquestador (JSON)
2. Subir a Laravel via backoffice
3. Crear nuevo registro en supplier_automation_workflow_versions
4. Activar nueva versión (desactiva anterior)
5. Sincronizar con orquestador via API si es necesario
```

### 19.2 Rollback de Versión

```php
// App\Services\SupplierAutomationWorkflowService
public function rollbackToVersion(SupplierAutomationWorkflow $workflow, int $version): void
{
    $targetVersion = $workflow->versions()
        ->where('version', $version)
        ->firstOrFail();

    // Desactivar versión actual
    $workflow->versions()
        ->where('is_active', true)
        ->update(['is_active' => false]);

    // Activar versión objetivo
    $targetVersion->update([
        'is_active' => true,
        'activated_at' => now(),
    ]);

    // Sincronizar con orquestador
    $this->automationClient->updateWorkflow(
        $workflow->external_workflow_id,
        $targetVersion->workflow_json
    );

    // Log
    activity()
        ->performedOn($workflow)
        ->withProperties(['from_version' => $workflow->currentVersion, 'to_version' => $version])
        ->log('Workflow rolled back');
}
```

---

## 20. AMBIENTES Y TESTING

### 20.1 Configuración por Ambiente

```php
// config/supplier-automation.php
return [
    'environments' => [
        'production' => [
            'base_url' => env('AUTOMATION_PRODUCTION_URL'),
            'api_key' => env('AUTOMATION_PRODUCTION_API_KEY'),
            'webhook_prefix' => 'prod',
        ],
        'staging' => [
            'base_url' => env('AUTOMATION_STAGING_URL'),
            'api_key' => env('AUTOMATION_STAGING_API_KEY'),
            'webhook_prefix' => 'staging',
        ],
        'development' => [
            'base_url' => env('AUTOMATION_DEV_URL', 'http://localhost:5678'),
            'api_key' => env('AUTOMATION_DEV_API_KEY'),
            'webhook_prefix' => 'dev',
        ],
    ],
    'current_environment' => env('AUTOMATION_ENVIRONMENT', 'development'),
    'provider' => env('AUTOMATION_PROVIDER', 'n8n'),  // n8n, make, zapier
];
```

### 20.2 Tabla: `supplier_automation_test_runs` (Ejecuciones de Prueba)

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `workflow_id` | bigint | FK a supplier_automation_workflows |
| `test_type` | enum | `smoke`, `integration`, `regression` |
| `test_payload` | json | Payload de prueba |
| `expected_output` | json | Output esperado |
| `actual_output` | json | Output recibido |
| `passed` | boolean | Si pasó el test |
| `assertions_passed` | int | Aserciones correctas |
| `assertions_failed` | int | Aserciones fallidas |
| `duration_ms` | int | Duración |
| `run_by` | bigint | FK a users |
| `created_at` | timestamp | |

### 20.3 Testing de Workflows

```php
// App\Services\SupplierAutomationTestService
public function runSmokeTest(SupplierAutomationWorkflow $workflow): SupplierAutomationTestRun
{
    $testPayload = $this->generateTestPayload($workflow);

    // Enviar a workflow de prueba
    $execution = $this->automationClient->executeTest(
        $workflow->external_workflow_id,
        $testPayload
    );

    // Esperar resultado (con timeout)
    $result = $this->waitForResult($execution, timeout: 60);

    // Validar output
    $assertions = $this->runAssertions($workflow, $result);

    return SupplierAutomationTestRun::create([
        'workflow_id' => $workflow->id,
        'test_type' => 'smoke',
        'test_payload' => $testPayload,
        'actual_output' => $result,
        'passed' => $assertions['failed'] === 0,
        'assertions_passed' => $assertions['passed'],
        'assertions_failed' => $assertions['failed'],
        'duration_ms' => $execution->duration_ms,
        'run_by' => auth()->id(),
    ]);
}
```

---

## 21. BACKUPS Y RECUPERACIÓN

### 21.1 Qué Respaldar

| Componente | Frecuencia | Método |
|------------|------------|--------|
| Configuración n8n_settings | Diario | Dump a JSON |
| Credenciales (encriptadas) | Diario | Export seguro |
| Definiciones de workflows | Por cambio | Git + DB |
| Métricas históricas | Semanal | Archivo comprimido |
| Logs de ejecución | Mensual | Archivo + purge |

### 21.2 Comando de Backup

```bash
php artisan supplier-automation:backup --components=all --destination=s3
```

```php
// App\Console\Commands\SupplierAutomationBackupCommand
class SupplierAutomationBackupCommand extends Command
{
    protected $signature = 'supplier-automation:backup
        {--components=all : backups,credentials,workflows,metrics}
        {--destination=local : local,s3}';

    public function handle(): void
    {
        $components = $this->parseComponents();
        $backup = [];

        if (in_array('backups', $components)) {
            $backup['backups'] = SupplierAutomationSetting::all()->toArray();
        }

        if (in_array('workflows', $components)) {
            $backup['workflows'] = SupplierAutomationWorkflow::with('versions')->get()->toArray();
        }

        // ... resto de componentes

        $filename = 'supplier_automation_backup_' . now()->format('Y-m-d_His') . '.json';

        $this->storeBackup($backup, $filename);

        $this->info("Backup created: {$filename}");
    }
}
```

---

## 22. DIAGRAMA DE RELACIONES COMPLETO

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                              SISTEMA DE PROVEEDORES                                  │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                     │
│  ┌─────────────┐                                                                    │
│  │ suppliers   │◄─────────────────────────────────────────────────────────┐        │
│  └──────┬──────┘                                                          │        │
│         │                                                                  │        │
│         ├──────────────┬──────────────┬──────────────┬──────────────┐     │        │
│         │              │              │              │              │     │        │
│         ▼              ▼              ▼              ▼              ▼     │        │
│  ┌────────────┐ ┌────────────┐ ┌────────────┐ ┌────────────┐ ┌──────────┐│        │
│  │ supplier_  │ │ supplier_  │ │ supplier_  │ │ supplier_  │ │supplier_ ││        │
│  │ categories │ │ products   │ │ sources    │ │ prompts    │ │contents  ││        │
│  └────────────┘ └────────────┘ └─────┬──────┘ └────────────┘ └────┬─────┘│        │
│                                      │                             │      │        │
│                          ┌───────────┼───────────┐                 │      │        │
│                          │           │           │                 │      │        │
│                          ▼           ▼           ▼                 ▼      │        │
│                   ┌───────────┐ ┌─────────┐ ┌──────────┐    ┌───────────┐│        │
│                   │ supplier_ │ │supplier_│ │supplier_ │    │ supplier_ ││        │
│                   │ source_   │ │credential│ │source_   │    │ content_  ││        │
│                   │ options   │ │          │ │priorities│    │ logs      ││        │
│                   └───────────┘ └─────────┘ └──────────┘    └───────────┘│        │
│                                                                          │        │
└──────────────────────────────────────────────────────────────────────────┴────────┘

┌─────────────────────────────────────────────────────────────────────────────────────┐
│                         SISTEMA DE AUTOMATIZACIÓN                                    │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                     │
│  ┌───────────────────┐     ┌───────────────────┐     ┌───────────────────┐         │
│  │ supplier_         │     │ supplier_         │◄────│ supplier_         │         │
│  │ automation_       │     │ automation_       │     │ automation_       │         │
│  │ settings          │     │ workflows         │     │ workflow_versions │         │
│  └───────────────────┘     └─────────┬─────────┘     └───────────────────┘         │
│                                      │                                              │
│                                      ▼                                              │
│                          ┌───────────────────┐                                      │
│                          │ supplier_         │◄────────────────────┐               │
│                          │ automation_       │                     │               │
│                          │ executions        │                     │               │
│                          └─────────┬─────────┘                     │               │
│                                    │                               │               │
│       ┌────────────────────────────┼────────────────────────────┐  │               │
│       │                            │                            │  │               │
│       ▼                            ▼                            ▼  │               │
│  ┌───────────────┐        ┌───────────────┐        ┌───────────────┐              │
│  │ supplier_     │        │ supplier_     │        │ supplier_     │              │
│  │ automation_   │        │ automation_   │        │ automation_   │              │
│  │ retry_queue   │        │ dead_letter   │        │ metrics       │              │
│  └───────────────┘        └───────────────┘        └───────────────┘              │
│                                                                                     │
│  ┌───────────────┐        ┌───────────────┐        ┌───────────────┐              │
│  │ supplier_     │        │ supplier_     │        │ supplier_     │              │
│  │ automation_   │        │ automation_   │        │ automation_   │              │
│  │ health_checks │        │ rate_limits   │        │ alerts        │              │
│  └───────────────┘        └───────────────┘        └───────────────┘              │
│                                                                                     │
│  ┌───────────────┐                                                                 │
│  │ supplier_     │                                                                 │
│  │ automation_   │                                                                 │
│  │ test_runs     │                                                                 │
│  └───────────────┘                                                                 │
│                                                                                     │
└─────────────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────────────┐
│                              PRESTASHOP (Externo)                                    │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                     │
│  ┌─────────────────┐        ┌─────────────────┐        ┌─────────────────┐         │
│  │ prestashop_     │◄──sync─│  aalv_category  │        │  aalv_product   │         │
│  │ categories      │        │  (original)     │        │  (destino)      │         │
│  └─────────────────┘        └─────────────────┘        └─────────────────┘         │
│                                                                                     │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

---

## 23. RESUMEN DE TABLAS

### Tablas de Proveedores (7)
1. `suppliers` - Proveedores principales
2. `supplier_categories` - Relación proveedor-categorías
3. `supplier_products` - Relación proveedor-productos
4. `supplier_sources` - Fuentes de información
5. `supplier_source_options` - Opciones dinámicas por fuente
6. `supplier_prompts` - Prompts con sistema de prioridad
7. `supplier_contents` - Contenidos generados

### Tablas de Trazabilidad (2)
8. `supplier_content_logs` - Historial de acciones
9. `supplier_credentials` - Credenciales encriptadas

### Tablas de Automatización (11)
10. `supplier_automation_settings` - Configuración global
11. `supplier_automation_workflows` - Workflows registrados
12. `supplier_automation_workflow_versions` - Versionado de workflows
13. `supplier_automation_executions` - Ejecuciones
14. `supplier_automation_retry_queue` - Cola de reintentos
15. `supplier_automation_dead_letter_queue` - Fallidos definitivos
16. `supplier_automation_health_checks` - Monitoreo de salud
17. `supplier_automation_rate_limits` - Control de rate limiting
18. `supplier_automation_metrics` - Métricas agregadas
19. `supplier_automation_alerts` - Alertas del sistema
20. `supplier_automation_test_runs` - Ejecuciones de prueba

### Tablas de Sincronización (1)
21. `prestashop_categories` - Copia local de categorías PS

**Total: 21 tablas**

---

## 24. SISTEMA DE EXTRACCIÓN Y ALMACENAMIENTO DE DATOS

Esta sección define cómo se extraen datos de diferentes fuentes (web, FTP, archivos) y se almacenan en una estructura genérica unificada.

### 24.1 Tabla: `supplier_extraction_mappings` (Mapeo de Campos por Fuente)

Define cómo extraer datos de cada tipo de fuente. Cada proveedor puede tener múltiples configuraciones.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `uid` | char(36) | UUID |
| `source_id` | bigint | FK a supplier_sources |
| `name` | varchar(255) | Nombre del mapeo |
| `source_type` | enum | `website`, `ftp_excel`, `ftp_csv`, `ftp_pdf`, `upload_pdf`, `upload_excel`, `api` |
| `field_mappings` | json | Mapeo de campos (ver estructura abajo) |
| `search_config` | json | Configuración de búsqueda por referencia |
| `validation_rules` | json | Reglas de validación de datos |
| `transform_rules` | json | Transformaciones a aplicar |
| `is_active` | boolean | |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Estructura de `field_mappings` por tipo:**

#### Para `website` (Scraping Web):
```json
{
  "selectors": {
    "product_name": {
      "selector": "h1.product-title",
      "type": "text",
      "required": true
    },
    "short_description": {
      "selector": ".product-description-short",
      "type": "text",
      "required": false
    },
    "long_description": {
      "selector": ".product-description-full",
      "type": "html",
      "required": false
    },
    "price": {
      "selector": ".product-price span.value",
      "type": "text",
      "transform": "extract_number"
    },
    "images": {
      "selector": ".product-gallery img",
      "attribute": "src",
      "type": "array",
      "max_items": 10
    },
    "specifications": {
      "selector": ".specs-table tr",
      "type": "key_value_pairs",
      "key_selector": "td:first-child",
      "value_selector": "td:last-child"
    },
    "features": {
      "selector": ".features-list li",
      "type": "array"
    },
    "ean": {
      "selector": "[itemprop='gtin13']",
      "type": "text"
    },
    "sku": {
      "selector": ".product-reference",
      "type": "text",
      "transform": "extract_reference"
    }
  },
  "wait_for": ".product-title",
  "timeout_ms": 10000
}
```

#### Para `ftp_excel` / `upload_excel`:
```json
{
  "sheet": "Productos",
  "header_row": 1,
  "data_start_row": 2,
  "columns": {
    "reference": "A",
    "product_name": "B",
    "short_description": "C",
    "long_description": "D",
    "ean": "E",
    "price": "F",
    "category": "G",
    "brand": "H",
    "specifications": {
      "columns": ["I", "J", "K", "L"],
      "format": "key_value_alternating"
    },
    "images": {
      "column": "M",
      "separator": "|"
    }
  },
  "skip_empty_reference": true,
  "encoding": "UTF-8"
}
```

#### Para `ftp_csv` / `upload_csv`:
```json
{
  "delimiter": ";",
  "enclosure": "\"",
  "escape": "\\",
  "header_row": true,
  "encoding": "UTF-8",
  "columns": {
    "reference": "REF",
    "product_name": "NOMBRE",
    "short_description": "DESC_CORTA",
    "long_description": "DESC_LARGA",
    "ean": "EAN13",
    "price": "PRECIO_PVP",
    "specifications": {
      "columns": ["MATERIAL", "COLOR", "TALLA"],
      "format": "named_columns"
    }
  }
}
```

#### Para `ftp_pdf` / `upload_pdf`:
```json
{
  "extraction_mode": "ocr",
  "language": "spa",
  "search_patterns": {
    "reference": {
      "pattern": "Ref\\.?:\\s*([A-Z0-9-]+)",
      "type": "regex"
    },
    "product_name": {
      "pattern": "after_reference_first_line",
      "type": "position"
    },
    "ean": {
      "pattern": "EAN:?\\s*(\\d{13})",
      "type": "regex"
    },
    "description": {
      "pattern": "Descripción:?\\s*(.+?)(?=Características|Especificaciones|$)",
      "type": "regex",
      "flags": "s"
    },
    "specifications": {
      "pattern": "table_after_keyword",
      "keyword": "Especificaciones",
      "type": "table"
    }
  },
  "page_range": "all"
}
```

---

### 24.2 Tabla: `supplier_extraction_results` (Resultados Extraídos - Genérica)

Almacena todos los datos extraídos de cualquier tipo de fuente en estructura unificada.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `uid` | char(36) | UUID |
| `supplier_id` | bigint | FK a suppliers |
| `source_id` | bigint | FK a supplier_sources |
| `mapping_id` | bigint | FK a supplier_extraction_mappings |
| `execution_id` | bigint | FK a supplier_automation_executions |
| `batch_id` | char(36) | ID del lote/reporte diario |
| `batch_date` | date | Fecha del lote |
| `reference` | varchar(100) | Referencia del producto (índice) |
| `ean` | varchar(20) | Código EAN (índice) |
| `source_url` | varchar(500) | URL origen (si aplica) |
| `source_file` | varchar(255) | Nombre archivo origen (si aplica) |
| `extracted_data` | json | Datos extraídos (estructura unificada) |
| `raw_data` | json | Datos crudos antes de transformar |
| `extraction_quality` | enum | `complete`, `partial`, `minimal`, `failed` |
| `missing_fields` | json | Campos que no se pudieron extraer |
| `status` | enum | `new`, `existing`, `updated`, `error` |
| `hash` | varchar(64) | Hash MD5 del contenido para detectar cambios |
| `previous_hash` | varchar(64) | Hash anterior (para comparación) |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Índices:**
- `idx_reference` en (`supplier_id`, `reference`)
- `idx_ean` en (`supplier_id`, `ean`)
- `idx_batch` en (`batch_id`, `batch_date`)
- `idx_status` en (`status`, `created_at`)

**Estructura de `extracted_data` (unificada para todos los tipos):**
```json
{
  "product_name": "Nike Air Max 90",
  "short_description": "Zapatillas deportivas con amortiguación Air",
  "long_description": "Las Nike Air Max 90 son un clásico reinventado...",
  "brand": "Nike",
  "category": "Calzado > Deportivo > Running",
  "price": {
    "value": 149.99,
    "currency": "EUR"
  },
  "ean": "8412345678901",
  "sku": "NIKE-AM90-001",
  "specifications": [
    {"key": "Material", "value": "Cuero sintético"},
    {"key": "Suela", "value": "Goma con Air visible"},
    {"key": "Color", "value": "Blanco/Negro"},
    {"key": "Peso", "value": "350g"}
  ],
  "features": [
    "Amortiguación Air-Sole visible",
    "Diseño retro renovado",
    "Forro transpirable"
  ],
  "images": [
    {"url": "https://...", "alt": "Vista frontal", "position": 1},
    {"url": "https://...", "alt": "Vista lateral", "position": 2}
  ],
  "dimensions": {
    "weight": "350g",
    "sizes_available": ["38", "39", "40", "41", "42", "43", "44"]
  },
  "seo": {
    "meta_title": "Nike Air Max 90 - Comprar Online",
    "meta_description": "Zapatillas Nike Air Max 90 con envío gratis...",
    "keywords": ["nike", "air max", "zapatillas", "running"]
  },
  "custom_fields": {
    "temporada": "2024",
    "coleccion": "Heritage"
  }
}
```

---

### 24.3 Tabla: `supplier_extraction_batches` (Lotes de Extracción)

Agrupa las extracciones por día/ejecución para reportes.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `uid` | char(36) | UUID (usado como batch_id) |
| `supplier_id` | bigint | FK a suppliers |
| `source_id` | bigint | FK a supplier_sources (nullable para múltiples) |
| `batch_date` | date | Fecha del lote |
| `batch_type` | enum | `daily`, `manual`, `incremental`, `full_sync` |
| `status` | enum | `pending`, `processing`, `completed`, `failed` |
| `total_items` | int | Total de items procesados |
| `new_items` | int | Items nuevos |
| `updated_items` | int | Items con cambios |
| `unchanged_items` | int | Items sin cambios |
| `failed_items` | int | Items con error |
| `summary` | json | Resumen del lote |
| `started_at` | timestamp | |
| `completed_at` | timestamp | |
| `created_at` | timestamp | |

**Estructura de `summary`:**
```json
{
  "sources_processed": 3,
  "files_processed": ["catalog_2024.xlsx", "prices_update.csv"],
  "urls_scraped": 150,
  "fields_coverage": {
    "product_name": 100,
    "short_description": 95,
    "long_description": 80,
    "images": 70,
    "specifications": 60
  },
  "errors": [
    {"reference": "REF-001", "error": "Page not found"},
    {"reference": "REF-002", "error": "Timeout"}
  ],
  "warnings": [
    {"reference": "REF-003", "warning": "Missing images"}
  ]
}
```

---

### 24.4 Lógica de Detección de Nuevos vs Existentes

```php
// App\Services\ExtractionResultService
class ExtractionResultService
{
    public function processExtractedData(
        Supplier $supplier,
        SupplierSource $source,
        array $data,
        string $batchId
    ): SupplierExtractionResult {

        // Generar hash del contenido para detectar cambios
        $hash = $this->generateContentHash($data);

        // Buscar si existe por referencia o EAN
        $existing = SupplierExtractionResult::query()
            ->where('supplier_id', $supplier->id)
            ->where(function ($q) use ($data) {
                $q->where('reference', $data['reference'] ?? null)
                  ->orWhere('ean', $data['ean'] ?? null);
            })
            ->latest()
            ->first();

        if (!$existing) {
            // Nuevo producto
            return $this->createNewResult($supplier, $source, $data, $batchId, $hash, 'new');
        }

        if ($existing->hash === $hash) {
            // Sin cambios - solo actualizar batch_id para tracking
            $existing->update([
                'batch_id' => $batchId,
                'batch_date' => now()->toDateString(),
                'status' => 'existing',
            ]);
            return $existing;
        }

        // Producto con cambios - crear nuevo registro
        return $this->createNewResult(
            $supplier,
            $source,
            $data,
            $batchId,
            $hash,
            'updated',
            $existing->hash // previous_hash
        );
    }

    private function generateContentHash(array $data): string
    {
        // Excluir campos que cambian frecuentemente (precio, stock)
        $hashableFields = ['product_name', 'short_description', 'long_description',
                          'specifications', 'features', 'images'];

        $hashData = array_intersect_key($data, array_flip($hashableFields));

        return md5(json_encode($hashData, JSON_UNESCAPED_UNICODE));
    }
}
```

---

### 24.5 Flujo Completo de Extracción

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  1. TRIGGER: Scheduler diario o manual                                      │
│     └── Crear batch: supplier_extraction_batches (status=pending)           │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  2. Para cada SOURCE del proveedor (ordenado por prioridad):                │
│     ├── Obtener supplier_extraction_mappings                                │
│     ├── Según source_type:                                                  │
│     │   ├── website → Enviar a orquestador con selectores                   │
│     │   ├── ftp_* → Descargar archivo y parsear con columnas                │
│     │   └── upload_* → Leer archivo subido y parsear                        │
│     └── Esperar callback con datos extraídos                                │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  3. Procesar datos recibidos:                                               │
│     Para cada item extraído:                                                │
│     ├── Aplicar transform_rules (limpiar, normalizar)                       │
│     ├── Validar con validation_rules                                        │
│     ├── Generar hash del contenido                                          │
│     ├── Buscar existente por reference/EAN                                  │
│     │   ├── No existe → status='new'                                        │
│     │   ├── Existe + hash igual → status='existing'                         │
│     │   └── Existe + hash diferente → status='updated'                      │
│     └── Guardar en supplier_extraction_results                              │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  4. Actualizar batch:                                                       │
│     ├── Contar: new_items, updated_items, unchanged_items, failed_items     │
│     ├── Generar summary con coverage de campos                              │
│     └── status = 'completed'                                                │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  5. Para items NEW o UPDATED:                                               │
│     └── Crear/actualizar supplier_contents para generación IA               │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

### 24.6 Ejemplo: Configuración Completa de un Proveedor

```json
{
  "supplier": {
    "id": 5,
    "label": "Nike",
    "code": "NIKE"
  },
  "sources": [
    {
      "id": 15,
      "source_type": "website",
      "label": "Web Nike España",
      "priority": 1,
      "search_config": {
        "search_url": "https://www.nike.com/es/w?q={reference}",
        "product_url_pattern": "https://www.nike.com/es/t/{slug}"
      },
      "mappings": [{
        "field_mappings": {
          "selectors": {
            "product_name": {"selector": "h1#pdp_product_title", "type": "text"},
            "short_description": {"selector": ".description-preview", "type": "text"},
            "price": {"selector": "[data-test='product-price']", "type": "text"},
            "images": {"selector": "#pdp-image-gallery img", "attribute": "src", "type": "array"}
          }
        }
      }]
    },
    {
      "id": 16,
      "source_type": "ftp_excel",
      "label": "Catálogo FTP Nike",
      "priority": 2,
      "ftp_config": {
        "host": "ftp.nike-catalog.com",
        "path": "/exports/ES/",
        "file_pattern": "catalog_*.xlsx"
      },
      "mappings": [{
        "field_mappings": {
          "sheet": "Products",
          "columns": {
            "reference": "A",
            "product_name": "B",
            "short_description": "C",
            "ean": "D",
            "price": "E"
          }
        }
      }]
    },
    {
      "id": 17,
      "source_type": "upload_pdf",
      "label": "Catálogos PDF subidos",
      "priority": 3,
      "mappings": [{
        "field_mappings": {
          "extraction_mode": "ocr",
          "search_patterns": {
            "reference": {"pattern": "REF:\\s*([A-Z0-9-]+)", "type": "regex"},
            "product_name": {"pattern": "after_reference_first_line", "type": "position"}
          }
        }
      }]
    }
  ]
}
```

---

### 24.7 Resumen de Tablas Adicionales

| # | Tabla | Descripción |
|---|-------|-------------|
| 22 | `supplier_extraction_mappings` | Mapeo de campos por tipo de fuente |
| 23 | `supplier_extraction_results` | Resultados extraídos (genérica) |
| 24 | `supplier_extraction_batches` | Lotes de extracción diarios |

**Total actualizado: 24 tablas**

---

## 25. Patrones de Workflows de Automatización

Esta sección documenta los patrones de workflows más comunes para la orquestación de tareas de scraping y extracción, basados en ejemplos reales que pueden implementarse en n8n u otros orquestadores similares.

---

### 25.1 Tipos de Nodos Comunes

| Categoría | Nodo | Descripción |
|-----------|------|-------------|
| **Triggers** | `Webhook` | Recibe peticiones HTTP desde Laravel |
| **Triggers** | `Schedule` | Ejecuta workflows en horarios programados |
| **HTTP** | `HTTP Request` | Realiza peticiones GET/POST a URLs |
| **Parsing** | `HTML Extract` | Extrae datos de HTML usando selectores CSS |
| **Parsing** | `Code` | Ejecuta JavaScript/Python para transformaciones |
| **Browser** | `Selenium` | Automatiza navegadores para páginas dinámicas |
| **IA** | `LangChain` | Integración con modelos de lenguaje (GPT, Claude) |
| **IA** | `OpenAI` | Llamadas directas a API de OpenAI |
| **Control** | `Split In Batches` | Procesa arrays en lotes |
| **Control** | `IF` | Bifurcación condicional |
| **Control** | `Switch` | Múltiples rutas según condición |
| **Control** | `Merge` | Combina resultados de múltiples ramas |
| **Respuesta** | `Respond to Webhook` | Envía respuesta al llamador |

---

### 25.2 Patrón: Scraping Web Simple con GPT

Este patrón extrae contenido de páginas web estáticas y lo procesa con IA.

```
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│   Webhook    │────▶│ HTTP Request │────▶│ HTML Extract │
│   Trigger    │     │  (GET URL)   │     │ (Selectores) │
└──────────────┘     └──────────────┘     └──────────────┘
                                                  │
                                                  ▼
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│   Respond    │◀────│   LangChain  │◀────│ Split Batch  │
│   Webhook    │     │   (GPT-4)    │     │  (Procesar)  │
└──────────────┘     └──────────────┘     └──────────────┘
```

**Ejemplo de configuración del nodo HTML Extract:**

```json
{
  "extractionType": "css",
  "selector": "table table a",
  "returnArray": true,
  "extractAttributes": {
    "text": ".text()",
    "href": ".attr('href')"
  }
}
```

**Ejemplo de prompt para LangChain:**

```json
{
  "model": "gpt-4o-mini",
  "systemPrompt": "Actúa como un experto en productos. Resume la información del producto en formato estructurado.",
  "temperature": 0.3,
  "maxTokens": 2000
}
```

---

### 25.3 Patrón: Scraping con Selenium para Páginas Dinámicas

Para sitios con JavaScript, SPAs o contenido cargado dinámicamente.

```
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│   Webhook    │────▶│   Selenium   │────▶│  Screenshot  │
│   Trigger    │     │  (Navegar)   │     │  (Capturar)  │
└──────────────┘     └──────────────┘     └──────────────┘
                                                  │
                     ┌────────────────────────────┤
                     │                            │
                     ▼                            ▼
              ┌──────────────┐           ┌──────────────┐
              │ HTML Extract │           │  GPT Vision  │
              │ (Selectores) │           │ (Analizar)   │
              └──────────────┘           └──────────────┘
                     │                            │
                     └────────────┬───────────────┘
                                  ▼
                           ┌──────────────┐
                           │    Merge     │
                           │  (Combinar)  │
                           └──────────────┘
                                  │
                                  ▼
                           ┌──────────────┐
                           │   Respond    │
                           │   Webhook    │
                           └──────────────┘
```

**Configuración del nodo Selenium:**

```json
{
  "operation": "navigateToUrl",
  "url": "={{ $json.url }}",
  "waitForSelector": ".product-container",
  "timeout": 30000,
  "options": {
    "headless": true,
    "windowSize": {"width": 1920, "height": 1080}
  }
}
```

**Configuración para Screenshot + GPT Vision:**

```json
{
  "operation": "takeScreenshot",
  "fullPage": true,
  "imageFormat": "png"
}
```

```json
{
  "model": "gpt-4o",
  "messages": [
    {
      "role": "user",
      "content": [
        {"type": "text", "text": "Extrae toda la información del producto visible en esta imagen: nombre, descripción, precio, características, especificaciones."},
        {"type": "image_url", "image_url": {"url": "data:image/png;base64,{{ $json.screenshot }}"}}
      ]
    }
  ]
}
```

---

### 25.4 Patrón: Scraping con Autenticación (Cookies)

Para sitios que requieren login o sesión activa.

```
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│   Webhook    │────▶│   Selenium   │────▶│ Set Cookies  │
│  (+ cookies) │     │    Init      │     │  (Sesión)    │
└──────────────┘     └──────────────┘     └──────────────┘
                                                  │
                                                  ▼
                                          ┌──────────────┐
                                          │   Navigate   │
                                          │   to URL     │
                                          └──────────────┘
                                                  │
                                                  ▼
                                          ┌──────────────┐
                                          │   Extract    │
                                          │    Data      │
                                          └──────────────┘
```

**Inyección de cookies en Selenium:**

```json
{
  "operation": "setCookies",
  "cookies": "={{ $json.cookies }}",
  "domain": "={{ $json.domain }}"
}
```

**Payload desde Laravel con cookies:**

```json
{
  "url": "https://proveedor.com/catalog/product/12345",
  "cookies": [
    {"name": "session_id", "value": "abc123xyz", "domain": ".proveedor.com"},
    {"name": "auth_token", "value": "jwt-token-here", "domain": ".proveedor.com"}
  ],
  "selectors": {
    "product_name": "h1.title",
    "price": ".price-current"
  }
}
```

---

### 25.5 Patrón: Procesamiento en Lotes con Control de Errores

Para procesar múltiples productos con manejo de errores robusto.

```
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│   Webhook    │────▶│ Split Batch  │────▶│  Try/Catch   │
│  (array)     │     │  (N items)   │     │   Wrapper    │
└──────────────┘     └──────────────┘     └──────────────┘
                                                  │
                          ┌───────────────────────┼───────────────────────┐
                          │ (success)             │ (error)               │
                          ▼                       ▼                       │
                   ┌──────────────┐       ┌──────────────┐               │
                   │  HTTP/Selenium│       │  Log Error   │               │
                   │   Extract    │       │  + Continue  │               │
                   └──────────────┘       └──────────────┘               │
                          │                       │                       │
                          └───────────┬───────────┘                       │
                                      ▼                                   │
                               ┌──────────────┐                          │
                               │    Merge     │◀─────────────────────────┘
                               │   Results    │
                               └──────────────┘
                                      │
                                      ▼
                               ┌──────────────┐
                               │  Aggregate   │
                               │  & Respond   │
                               └──────────────┘
```

**Configuración de Split In Batches:**

```json
{
  "batchSize": 10,
  "options": {
    "reset": false
  }
}
```

**Manejo de errores en Code node:**

```javascript
// Wrapper para manejo de errores
const results = [];
const errors = [];

for (const item of items) {
  try {
    // Procesar item
    const result = await processItem(item);
    results.push({
      success: true,
      data: result,
      reference: item.reference
    });
  } catch (error) {
    errors.push({
      success: false,
      reference: item.reference,
      error: error.message
    });
  }
}

return {
  results,
  errors,
  summary: {
    total: items.length,
    success: results.length,
    failed: errors.length
  }
};
```

---

### 25.6 Patrón: Extracción Multi-Página con Paginación

Para catálogos con múltiples páginas.

```
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│   Webhook    │────▶│ Get First    │────▶│ Extract      │
│   Trigger    │     │    Page      │     │ Products     │
└──────────────┘     └──────────────┘     └──────────────┘
                                                  │
                                                  ▼
                                          ┌──────────────┐
                                          │ Has More     │
                                          │  Pages?      │
                                          └──────────────┘
                                                  │
                          ┌───────────────────────┼───────────────────────┐
                          │ (yes)                 │ (no)                  │
                          ▼                       ▼                       │
                   ┌──────────────┐       ┌──────────────┐               │
                   │  Loop Back   │       │  Aggregate   │               │
                   │ (next page)  │       │   All Data   │               │
                   └──────────────┘       └──────────────┘               │
                          │                       │                       │
                          │                       ▼                       │
                          │               ┌──────────────┐               │
                          │               │   Respond    │               │
                          └──────────────▶│   Webhook    │◀──────────────┘
                                          └──────────────┘
```

**Lógica de paginación:**

```javascript
// Detectar si hay más páginas
const $ = cheerio.load(html);
const nextPageLink = $('a.next-page').attr('href');
const currentPage = parseInt($('.pagination .active').text()) || 1;
const totalPages = parseInt($('.pagination .last').text()) || 1;

return {
  products: extractedProducts,
  pagination: {
    currentPage,
    totalPages,
    hasMore: currentPage < totalPages,
    nextUrl: nextPageLink
  }
};
```

---

### 25.7 Estructura de Workflow JSON

Anatomía de un workflow para orquestadores compatibles:

```json
{
  "name": "Supplier Product Scraper",
  "nodes": [
    {
      "id": "webhook-trigger",
      "type": "n8n-nodes-base.webhook",
      "position": [250, 300],
      "parameters": {
        "path": "scrape-product",
        "httpMethod": "POST",
        "responseMode": "responseNode"
      }
    },
    {
      "id": "http-request",
      "type": "n8n-nodes-base.httpRequest",
      "position": [450, 300],
      "parameters": {
        "method": "GET",
        "url": "={{ $json.url }}",
        "timeout": 30000,
        "options": {
          "response": {"response": {"fullResponse": true}}
        }
      }
    },
    {
      "id": "html-extract",
      "type": "n8n-nodes-base.htmlExtract",
      "position": [650, 300],
      "parameters": {
        "sourceData": "={{ $json.body }}",
        "extractionValues": {
          "values": [
            {"key": "title", "cssSelector": "h1.product-title", "returnValue": "text"},
            {"key": "price", "cssSelector": ".price", "returnValue": "text"},
            {"key": "description", "cssSelector": ".description", "returnValue": "html"},
            {"key": "images", "cssSelector": ".gallery img", "returnValue": "attribute", "attribute": "src", "returnArray": true}
          ]
        }
      }
    },
    {
      "id": "ai-process",
      "type": "@n8n/n8n-nodes-langchain.chainLlm",
      "position": [850, 300],
      "parameters": {
        "model": "gpt-4o-mini",
        "prompt": "Analiza y estructura esta información de producto: {{ $json }}",
        "options": {"temperature": 0.3}
      }
    },
    {
      "id": "respond",
      "type": "n8n-nodes-base.respondToWebhook",
      "position": [1050, 300],
      "parameters": {
        "respondWith": "json",
        "responseBody": "={{ $json }}"
      }
    }
  ],
  "connections": {
    "webhook-trigger": {"main": [[{"node": "http-request", "type": "main", "index": 0}]]},
    "http-request": {"main": [[{"node": "html-extract", "type": "main", "index": 0}]]},
    "html-extract": {"main": [[{"node": "ai-process", "type": "main", "index": 0}]]},
    "ai-process": {"main": [[{"node": "respond", "type": "main", "index": 0}]]}
  },
  "settings": {
    "executionOrder": "v1",
    "saveManualExecutions": true,
    "errorWorkflow": "error-handler-workflow-id"
  }
}
```

---

### 25.8 Selectores CSS Comunes por Tipo de Sitio

| Tipo de Sitio | Elemento | Selectores Típicos |
|---------------|----------|-------------------|
| **E-commerce** | Nombre producto | `h1.product-title`, `h1[itemprop="name"]`, `.product-name` |
| **E-commerce** | Precio | `.price`, `[itemprop="price"]`, `.current-price`, `span.price-value` |
| **E-commerce** | Descripción | `.description`, `[itemprop="description"]`, `.product-description` |
| **E-commerce** | Imágenes | `.gallery img`, `.product-images img`, `[itemprop="image"]` |
| **E-commerce** | SKU/Referencia | `.sku`, `[itemprop="sku"]`, `.product-sku` |
| **E-commerce** | Especificaciones | `table.specifications tr`, `.specs-list li`, `.tech-specs` |
| **Catálogo** | Lista productos | `.product-list .item`, `.products-grid .product`, `ul.products li` |
| **Catálogo** | Link siguiente | `a.next`, `.pagination a:last`, `[rel="next"]` |
| **General** | Breadcrumbs | `.breadcrumb`, `nav[aria-label="breadcrumb"]` |
| **General** | Categoría | `.category-name`, `[itemprop="category"]` |

---

### 25.9 Integración con Laravel: Callback Completo

Estructura del callback que el orquestador envía a Laravel:

```json
{
  "execution_id": "exec_abc123",
  "workflow_id": "wf_xyz789",
  "supplier_id": 5,
  "source_id": 15,
  "batch_id": "batch_20241219_001",
  "status": "completed",
  "started_at": "2024-12-19T10:00:00Z",
  "finished_at": "2024-12-19T10:02:35Z",
  "duration_ms": 155000,
  "statistics": {
    "pages_processed": 12,
    "products_found": 245,
    "products_extracted": 240,
    "products_failed": 5,
    "screenshots_taken": 12
  },
  "results": [
    {
      "reference": "NIKE-AIR-001",
      "ean": "1234567890123",
      "product_name": "Nike Air Max 90",
      "short_description": "Zapatillas clásicas con amortiguación Air visible",
      "long_description": "Las Nike Air Max 90...",
      "images": [
        "https://cdn.nike.com/image1.jpg",
        "https://cdn.nike.com/image2.jpg"
      ],
      "specifications": {
        "material": "Cuero sintético",
        "suela": "Goma",
        "cierre": "Cordones"
      },
      "source_url": "https://nike.com/es/t/air-max-90",
      "extracted_at": "2024-12-19T10:01:15Z"
    }
  ],
  "errors": [
    {
      "reference": "NIKE-ERR-001",
      "url": "https://nike.com/es/t/deleted-product",
      "error": "404 Not Found",
      "error_code": "HTTP_404"
    }
  ],
  "metadata": {
    "user_agent": "Mozilla/5.0...",
    "proxy_used": "proxy-eu-1.example.com",
    "ip_address": "1.2.3.4"
  },
  "signature": "hmac-sha256-signature-here",
  "timestamp": 1703239355
}
```

---

### 25.10 Mejores Prácticas para Workflows

#### Performance

| Práctica | Descripción |
|----------|-------------|
| **Batch size** | Procesar en lotes de 10-50 items para balance entre velocidad y memoria |
| **Timeouts** | Configurar timeouts apropiados (30s para HTTP, 60s para Selenium) |
| **Reintentos** | Usar exponential backoff: 1s, 2s, 4s, 8s |
| **Caché** | Cachear respuestas de páginas que no cambian frecuentemente |
| **Concurrencia** | Limitar requests concurrentes para evitar bloqueos (2-5 por dominio) |

#### Robustez

| Práctica | Descripción |
|----------|-------------|
| **Múltiples selectores** | Configurar selectores alternativos por si cambia el HTML |
| **Validación** | Validar datos extraídos antes de enviar a Laravel |
| **Fallbacks** | Usar GPT Vision como fallback si selectores fallan |
| **Logging** | Loguear cada paso para debugging |
| **Screenshots** | Guardar screenshots en errores para diagnóstico |

#### Seguridad

| Práctica | Descripción |
|----------|-------------|
| **Rotación IPs** | Usar proxies rotativos para evitar bloqueos |
| **User-Agent** | Rotar user-agents realistas |
| **Rate limiting** | Respetar robots.txt y límites del sitio |
| **Credenciales** | Nunca hardcodear credenciales, usar variables de entorno |
| **HMAC** | Firmar todos los callbacks con HMAC-SHA256 |

---

### 25.11 Mapeo de Errores Comunes

| Código | Error | Causa | Solución |
|--------|-------|-------|----------|
| `HTTP_403` | Forbidden | IP bloqueada o sin autorización | Rotar proxy, verificar cookies |
| `HTTP_404` | Not Found | Producto eliminado | Marcar como `discontinued` |
| `HTTP_429` | Too Many Requests | Rate limit excedido | Reducir concurrencia, esperar |
| `HTTP_503` | Service Unavailable | Sitio saturado | Reintentar con backoff |
| `TIMEOUT` | Request Timeout | Página muy lenta | Aumentar timeout, usar Selenium |
| `SELECTOR_NOT_FOUND` | Selector vacío | HTML cambió | Actualizar selectores |
| `CAPTCHA_DETECTED` | Captcha presente | Sitio detectó bot | Usar servicio anti-captcha |
| `PARSING_ERROR` | Error parseando | Formato inesperado | Revisar lógica de extracción |

```php
// Manejo en Laravel
public function handleWorkflowError(array $error): void
{
    $errorMapping = [
        'HTTP_404' => 'discontinued',
        'HTTP_403' => 'blocked',
        'HTTP_429' => 'rate_limited',
        'TIMEOUT' => 'timeout',
        'SELECTOR_NOT_FOUND' => 'structure_changed',
        'CAPTCHA_DETECTED' => 'captcha',
    ];

    $status = $errorMapping[$error['error_code']] ?? 'unknown_error';

    SupplierAutomationDeadLetterQueue::create([
        'workflow_id' => $error['workflow_id'],
        'execution_id' => $error['execution_id'],
        'error_type' => $status,
        'error_message' => $error['error'],
        'payload' => $error,
        'requires_manual_review' => in_array($status, ['structure_changed', 'captcha']),
    ]);
}
```

---

### 25.12 Resumen de Patrones

| # | Patrón | Uso Principal | Complejidad |
|---|--------|---------------|-------------|
| 1 | Web Simple + GPT | Páginas estáticas con HTML limpio | Baja |
| 2 | Selenium + Vision | SPAs, JavaScript pesado, contenido dinámico | Alta |
| 3 | Autenticación | Sitios con login requerido | Media |
| 4 | Batch + Errores | Múltiples productos con tolerancia a fallos | Media |
| 5 | Multi-Página | Catálogos paginados | Media |
| 6 | Híbrido | Combina selectores CSS + GPT Vision como fallback | Alta |

---

## 26. Seeders y Datos de Ejemplo

Esta sección proporciona los seeders completos para poblar la base de datos con datos de ejemplo realistas para testing y desarrollo.

---

### 26.1 SupplierSeeder

```php
<?php
// database/seeders/SupplierSeeder.php

namespace Database\Seeders;

use App\Models\Supplier\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'code' => 'NIKE',
                'label' => 'Nike España',
                'description' => 'Proveedor oficial de calzado y ropa deportiva Nike',
                'website' => 'https://www.nike.com/es',
                'contact_email' => 'proveedor@nike.es',
                'contact_phone' => '+34 900 123 456',
                'is_active' => true,
                'priority' => 1,
                'backups' => [
                    'default_lang' => 'es',
                    'auto_approve' => false,
                    'quality_threshold' => 80,
                    'max_concurrent_requests' => 5,
                ],
            ],
            [
                'code' => 'ADIDAS',
                'label' => 'Adidas Iberia',
                'description' => 'Distribuidor autorizado de productos Adidas',
                'website' => 'https://www.adidas.es',
                'contact_email' => 'b2b@adidas.es',
                'contact_phone' => '+34 900 234 567',
                'is_active' => true,
                'priority' => 2,
                'backups' => [
                    'default_lang' => 'es',
                    'auto_approve' => true,
                    'quality_threshold' => 75,
                    'max_concurrent_requests' => 3,
                ],
            ],
            [
                'code' => 'PUMA',
                'label' => 'Puma Sports',
                'description' => 'Catálogo completo de Puma deportivo',
                'website' => 'https://www.puma.com/es',
                'contact_email' => 'wholesale@puma.es',
                'contact_phone' => '+34 900 345 678',
                'is_active' => true,
                'priority' => 3,
                'backups' => [
                    'default_lang' => 'es',
                    'auto_approve' => false,
                    'quality_threshold' => 70,
                ],
            ],
            [
                'code' => 'ASICS',
                'label' => 'Asics Europe',
                'description' => 'Running y training profesional',
                'website' => 'https://www.asics.com/es',
                'contact_email' => 'distribucion@asics.eu',
                'is_active' => true,
                'priority' => 4,
            ],
            [
                'code' => 'NEWBAL',
                'label' => 'New Balance España',
                'description' => 'Calzado y equipamiento deportivo premium',
                'website' => 'https://www.newbalance.es',
                'contact_email' => 'partners@newbalance.es',
                'is_active' => false, // Desactivado para testing
                'priority' => 5,
            ],
        ];

        foreach ($suppliers as $data) {
            Supplier::updateOrCreate(
                ['code' => $data['code']],
                $data
            );
        }
    }
}
```

---

### 26.2 SupplierSourceSeeder

```php
<?php
// database/seeders/SupplierSourceSeeder.php

namespace Database\Seeders;

use App\Models\Supplier\Supplier;
use App\Models\Supplier\SupplierSource;
use Illuminate\Database\Seeder;

class SupplierSourceSeeder extends Seeder
{
    public function run(): void
    {
        // NIKE - Múltiples fuentes
        $nike = Supplier::where('code', 'NIKE')->first();

        if ($nike) {
            $nikeSources = [
                [
                    'source_type' => 'website',
                    'label' => 'Web Nike ES - Scraping',
                    'priority' => 1,
                    'is_active' => true,
                    'search_config' => [
                        'base_url' => 'https://www.nike.com/es',
                        'search_url' => 'https://www.nike.com/es/w?q={reference}',
                        'product_url_pattern' => 'https://www.nike.com/es/t/{slug}',
                        'pagination_pattern' => '?page={page}',
                        'max_pages' => 10,
                    ],
                    'rate_limit' => [
                        'requests_per_minute' => 30,
                        'delay_between_requests_ms' => 2000,
                    ],
                ],
                [
                    'source_type' => 'ftp_excel',
                    'label' => 'Catálogo FTP Nike (Excel)',
                    'priority' => 2,
                    'is_active' => true,
                    'ftp_config' => [
                        'host' => 'ftp.nike-catalog.com',
                        'port' => 21,
                        'username' => '${NIKE_FTP_USER}',
                        'password' => '${NIKE_FTP_PASS}',
                        'path' => '/exports/ES/',
                        'file_pattern' => 'catalog_*.xlsx',
                        'encoding' => 'UTF-8',
                    ],
                    'schedule' => [
                        'frequency' => 'daily',
                        'time' => '03:00',
                        'timezone' => 'Europe/Madrid',
                    ],
                ],
                [
                    'source_type' => 'api',
                    'label' => 'API Nike B2B',
                    'priority' => 3,
                    'is_active' => false, // Pendiente de credenciales
                    'api_config' => [
                        'base_url' => 'https://api.nike.com/b2b/v2',
                        'auth_type' => 'oauth2',
                        'client_id' => '${NIKE_API_CLIENT_ID}',
                        'client_secret' => '${NIKE_API_CLIENT_SECRET}',
                        'endpoints' => [
                            'inventaries' => '/inventaries',
                            'product_detail' => '/inventaries/{id}',
                            'inventory' => '/inventory',
                        ],
                    ],
                ],
            ];

            foreach ($nikeSources as $source) {
                SupplierSource::updateOrCreate(
                    ['supplier_id' => $nike->id, 'label' => $source['label']],
                    array_merge($source, ['supplier_id' => $nike->id])
                );
            }
        }

        // ADIDAS - Fuentes mixtas
        $adidas = Supplier::where('code', 'ADIDAS')->first();

        if ($adidas) {
            $adidasSources = [
                [
                    'source_type' => 'website',
                    'label' => 'Web Adidas ES',
                    'priority' => 1,
                    'is_active' => true,
                    'search_config' => [
                        'base_url' => 'https://www.adidas.es',
                        'search_url' => 'https://www.adidas.es/search?q={reference}',
                        'product_url_pattern' => 'https://www.adidas.es/{slug}.html',
                        'requires_javascript' => true,
                        'wait_for_selector' => '[data-auto-id="product-card"]',
                    ],
                    'rate_limit' => [
                        'requests_per_minute' => 20,
                        'delay_between_requests_ms' => 3000,
                    ],
                ],
                [
                    'source_type' => 'ftp_csv',
                    'label' => 'Feed CSV Adidas',
                    'priority' => 2,
                    'is_active' => true,
                    'ftp_config' => [
                        'host' => 'sftp.adidas-partner.com',
                        'port' => 22,
                        'protocol' => 'sftp',
                        'username' => '${ADIDAS_SFTP_USER}',
                        'key_file' => '${ADIDAS_SFTP_KEY}',
                        'path' => '/feeds/inventaries/',
                        'file_pattern' => 'products_es_*.csv',
                        'delimiter' => ';',
                        'encoding' => 'UTF-8',
                        'has_header' => true,
                    ],
                ],
                [
                    'source_type' => 'upload_excel',
                    'label' => 'Excel Manual Adidas',
                    'priority' => 3,
                    'is_active' => true,
                    'upload_config' => [
                        'allowed_extensions' => ['xlsx', 'xls'],
                        'max_size_mb' => 50,
                        'storage_path' => 'uploads/suppliers/adidas',
                    ],
                ],
            ];

            foreach ($adidasSources as $source) {
                SupplierSource::updateOrCreate(
                    ['supplier_id' => $adidas->id, 'label' => $source['label']],
                    array_merge($source, ['supplier_id' => $adidas->id])
                );
            }
        }

        // PUMA - Solo web y PDF
        $puma = Supplier::where('code', 'PUMA')->first();

        if ($puma) {
            $pumaSources = [
                [
                    'source_type' => 'website',
                    'label' => 'Web Puma ES',
                    'priority' => 1,
                    'is_active' => true,
                    'search_config' => [
                        'base_url' => 'https://eu.puma.com/es',
                        'search_url' => 'https://eu.puma.com/es/search?q={reference}',
                        'product_url_pattern' => 'https://eu.puma.com/es/pd/{slug}',
                    ],
                ],
                [
                    'source_type' => 'upload_pdf',
                    'label' => 'Catálogo PDF Puma',
                    'priority' => 2,
                    'is_active' => true,
                    'upload_config' => [
                        'allowed_extensions' => ['pdf'],
                        'max_size_mb' => 100,
                        'extraction_mode' => 'ocr',
                        'ocr_language' => 'spa+eng',
                    ],
                ],
            ];

            foreach ($pumaSources as $source) {
                SupplierSource::updateOrCreate(
                    ['supplier_id' => $puma->id, 'label' => $source['label']],
                    array_merge($source, ['supplier_id' => $puma->id])
                );
            }
        }
    }
}
```

---

### 26.3 SupplierPromptSeeder

```php
<?php
// database/seeders/SupplierPromptSeeder.php

namespace Database\Seeders;

use App\Models\Supplier\Supplier;
use App\Models\Supplier\SupplierPrompt;
use Illuminate\Database\Seeder;

class SupplierPromptSeeder extends Seeder
{
    public function run(): void
    {
        // Prompts globales (sin proveedor específico)
        $globalPrompts = [
            [
                'supplier_id' => null,
                'category_id' => null,
                'prompt_type' => 'short_description',
                'label' => 'Descripción corta - Estándar',
                'prompt_text' => <<<PROMPT
Genera una descripción corta de producto para e-commerce.

PRODUCTO: {product_name}
INFORMACIÓN ORIGINAL: {original_description}
CARACTERÍSTICAS: {features}

REGLAS:
- Máximo 160 caracteres
- Destacar el beneficio principal
- Incluir palabra clave principal
- Tono profesional pero cercano
- No usar superlativos excesivos
- Terminar con punto

FORMATO DE SALIDA: Solo el texto de la descripción, sin comillas ni explicaciones.
PROMPT,
                'model' => 'gpt-4o-mini',
                'temperature' => 0.7,
                'max_tokens' => 100,
                'priority' => 100, // Baja prioridad (fallback)
                'is_active' => true,
            ],
            [
                'supplier_id' => null,
                'category_id' => null,
                'prompt_type' => 'long_description',
                'label' => 'Descripción larga - Estándar',
                'prompt_text' => <<<PROMPT
Genera una descripción detallada de producto para e-commerce.

PRODUCTO: {product_name}
MARCA: {brand}
INFORMACIÓN ORIGINAL: {original_description}
ESPECIFICACIONES: {specifications}
CARACTERÍSTICAS: {features}

ESTRUCTURA:
1. Párrafo introductorio (2-3 líneas): Presenta el producto y su beneficio principal
2. Características destacadas (lista con viñetas): 4-6 puntos clave
3. Párrafo de cierre (2 líneas): Para quién es ideal este producto

REGLAS:
- Entre 200-400 palabras
- Usar HTML básico: <p>, <ul>, <li>, <strong>
- Incluir palabras clave relevantes naturalmente
- Mantener tono de la marca
- No inventar especificaciones
- No incluir precios ni disponibilidad

FORMATO: HTML válido listo para insertar en página de producto.
PROMPT,
                'model' => 'gpt-4o',
                'temperature' => 0.6,
                'max_tokens' => 800,
                'priority' => 100,
                'is_active' => true,
            ],
            [
                'supplier_id' => null,
                'category_id' => null,
                'prompt_type' => 'seo_meta',
                'label' => 'Meta SEO - Estándar',
                'prompt_text' => <<<PROMPT
Genera metadatos SEO para un producto de e-commerce.

PRODUCTO: {product_name}
MARCA: {brand}
CATEGORÍA: {category}
DESCRIPCIÓN: {short_description}

GENERAR:
1. meta_title: Máximo 60 caracteres, incluir marca y keyword principal
2. meta_description: Máximo 155 caracteres, call-to-action sutil
3. keywords: 5-8 palabras clave separadas por comas

FORMATO JSON:
{
  "meta_title": "...",
  "meta_description": "...",
  "keywords": "..."
}
PROMPT,
                'model' => 'gpt-4o-mini',
                'temperature' => 0.5,
                'max_tokens' => 200,
                'priority' => 100,
                'is_active' => true,
            ],
        ];

        foreach ($globalPrompts as $prompt) {
            SupplierPrompt::updateOrCreate(
                [
                    'supplier_id' => $prompt['supplier_id'],
                    'prompt_type' => $prompt['prompt_type'],
                    'label' => $prompt['label'],
                ],
                $prompt
            );
        }

        // Prompts específicos para NIKE
        $nike = Supplier::where('code', 'NIKE')->first();

        if ($nike) {
            $nikePrompts = [
                [
                    'supplier_id' => $nike->id,
                    'category_id' => null,
                    'prompt_type' => 'short_description',
                    'label' => 'Descripción corta - Nike',
                    'prompt_text' => <<<PROMPT
Genera una descripción corta para un producto Nike.

PRODUCTO: {product_name}
TECNOLOGÍA: {technology}
INFORMACIÓN: {original_description}

ESTILO NIKE:
- Dinámico y motivador
- Enfocado en rendimiento
- Usar vocabulario deportivo
- Máximo 160 caracteres

EJEMPLO: "Las Air Max 90 combinan amortiguación Air visible con un diseño icónico para un confort superior en cada paso."

GENERA SOLO EL TEXTO:
PROMPT,
                    'model' => 'gpt-4o',
                    'temperature' => 0.7,
                    'max_tokens' => 100,
                    'priority' => 1, // Alta prioridad para Nike
                    'is_active' => true,
                ],
                [
                    'supplier_id' => $nike->id,
                    'category_id' => null,
                    'prompt_type' => 'long_description',
                    'label' => 'Descripción larga - Nike',
                    'prompt_text' => <<<PROMPT
Crea una descripción de producto para Nike España.

DATOS DEL PRODUCTO:
- Nombre: {product_name}
- Categoría: {category}
- Tecnologías: {technology}
- Materiales: {materials}
- Descripción original: {original_description}
- Especificaciones: {specifications}

DIRECTRICES DE MARCA NIKE:
1. Tono: Inspirador, atlético, directo
2. Enfoque: Rendimiento y estilo
3. Vocabulario: Innovación, movimiento, superación
4. Evitar: Lenguaje pasivo, comparaciones con competencia

ESTRUCTURA HTML:
<div class="product-description">
  <p class="intro">[Párrafo gancho - conectar emocionalmente]</p>

  <h3>Características</h3>
  <ul>
    <li>[Beneficio 1 con tecnología]</li>
    <li>[Beneficio 2]</li>
    <li>[Beneficio 3]</li>
    <li>[Beneficio 4]</li>
  </ul>

  <h3>Detalles técnicos</h3>
  <p>[Especificaciones relevantes en prosa]</p>

  <p class="cta">[Cierre motivador]</p>
</div>

GENERA EL HTML:
PROMPT,
                    'model' => 'gpt-4o',
                    'temperature' => 0.6,
                    'max_tokens' => 1000,
                    'priority' => 1,
                    'is_active' => true,
                ],
            ];

            foreach ($nikePrompts as $prompt) {
                SupplierPrompt::updateOrCreate(
                    [
                        'supplier_id' => $prompt['supplier_id'],
                        'prompt_type' => $prompt['prompt_type'],
                        'label' => $prompt['label'],
                    ],
                    $prompt
                );
            }
        }

        // Prompts específicos para ADIDAS
        $adidas = Supplier::where('code', 'ADIDAS')->first();

        if ($adidas) {
            $adidasPrompts = [
                [
                    'supplier_id' => $adidas->id,
                    'category_id' => null,
                    'prompt_type' => 'short_description',
                    'label' => 'Descripción corta - Adidas',
                    'prompt_text' => <<<PROMPT
Genera descripción corta estilo Adidas.

PRODUCTO: {product_name}
COLECCIÓN: {collection}
TECNOLOGÍA: {technology}

ESTILO ADIDAS:
- Conciso y urbano
- Mezcla deporte y lifestyle
- Referencias a sostenibilidad cuando aplique
- Máximo 160 caracteres

GENERA SOLO EL TEXTO:
PROMPT,
                    'model' => 'gpt-4o-mini',
                    'temperature' => 0.7,
                    'max_tokens' => 100,
                    'priority' => 1,
                    'is_active' => true,
                ],
            ];

            foreach ($adidasPrompts as $prompt) {
                SupplierPrompt::updateOrCreate(
                    [
                        'supplier_id' => $prompt['supplier_id'],
                        'prompt_type' => $prompt['prompt_type'],
                        'label' => $prompt['label'],
                    ],
                    $prompt
                );
            }
        }
    }
}
```

---

### 26.4 SupplierExtractionMappingSeeder

```php
<?php
// database/seeders/SupplierExtractionMappingSeeder.php

namespace Database\Seeders;

use App\Models\Supplier\SupplierSource;
use App\Models\Supplier\SupplierExtractionMapping;
use Illuminate\Database\Seeder;

class SupplierExtractionMappingSeeder extends Seeder
{
    public function run(): void
    {
        // Mapeos para Nike Web
        $nikeWeb = SupplierSource::whereHas('supplier', fn($q) => $q->where('code', 'NIKE'))
            ->where('source_type', 'website')
            ->first();

        if ($nikeWeb) {
            SupplierExtractionMapping::updateOrCreate(
                ['source_id' => $nikeWeb->id, 'is_active' => true],
                [
                    'source_id' => $nikeWeb->id,
                    'field_mappings' => [
                        'selectors' => [
                            'product_name' => [
                                'selector' => 'h1#pdp_product_title',
                                'type' => 'text',
                                'required' => true,
                                'fallback_selectors' => [
                                    'h1[data-test="product-title"]',
                                    '.product-title h1',
                                ],
                            ],
                            'short_description' => [
                                'selector' => '.description-preview',
                                'type' => 'text',
                                'required' => false,
                            ],
                            'long_description' => [
                                'selector' => '.description-text',
                                'type' => 'html',
                                'required' => false,
                            ],
                            'price' => [
                                'selector' => '[data-test="product-price"]',
                                'type' => 'text',
                                'transform' => 'extract_price',
                                'required' => true,
                            ],
                            'original_price' => [
                                'selector' => '[data-test="product-price-reduced"]',
                                'type' => 'text',
                                'transform' => 'extract_price',
                            ],
                            'images' => [
                                'selector' => '#pdp-6-up img, .product-images img',
                                'attribute' => 'src',
                                'type' => 'array',
                                'transform' => 'to_absolute_url',
                            ],
                            'reference' => [
                                'selector' => '.product-sku, [itemprop="sku"]',
                                'type' => 'text',
                                'transform' => 'extract_sku',
                            ],
                            'color' => [
                                'selector' => '.colorway-name, [data-test="product-sub-title"]',
                                'type' => 'text',
                            ],
                            'sizes' => [
                                'selector' => '.size-grid input:not(:disabled)',
                                'attribute' => 'value',
                                'type' => 'array',
                            ],
                            'technology' => [
                                'selector' => '.product-technology, .tech-badges span',
                                'type' => 'array',
                            ],
                            'specifications' => [
                                'selector' => '.product-details li',
                                'type' => 'key_value_list',
                                'separator' => ':',
                            ],
                        ],
                        'pagination' => [
                            'next_selector' => 'a[data-test="pagination-next"]',
                            'page_param' => 'page',
                        ],
                    ],
                    'transform_rules' => [
                        'extract_price' => [
                            'type' => 'regex',
                            'pattern' => '/([0-9]+[.,][0-9]{2})/',
                            'replace_comma' => true,
                        ],
                        'extract_sku' => [
                            'type' => 'regex',
                            'pattern' => '/([A-Z]{2}[0-9]{4}|[0-9]{6}-[0-9]{3})/',
                        ],
                        'to_absolute_url' => [
                            'type' => 'url',
                            'base_url' => 'https://www.nike.com',
                        ],
                    ],
                    'validation_rules' => [
                        'product_name' => 'required|string|min:5|max:255',
                        'price' => 'required|numeric|min:0',
                        'reference' => 'required|string|regex:/^[A-Z0-9-]+$/',
                        'images' => 'required|array|min:1',
                    ],
                    'is_active' => true,
                ]
            );
        }

        // Mapeos para Nike FTP Excel
        $nikeFtp = SupplierSource::whereHas('supplier', fn($q) => $q->where('code', 'NIKE'))
            ->where('source_type', 'ftp_excel')
            ->first();

        if ($nikeFtp) {
            SupplierExtractionMapping::updateOrCreate(
                ['source_id' => $nikeFtp->id, 'is_active' => true],
                [
                    'source_id' => $nikeFtp->id,
                    'field_mappings' => [
                        'file_type' => 'excel',
                        'sheet' => 'Products', // o índice 0
                        'header_row' => 1,
                        'start_row' => 2,
                        'columns' => [
                            'reference' => [
                                'column' => 'A',
                                'header' => 'SKU',
                                'required' => true,
                            ],
                            'ean' => [
                                'column' => 'B',
                                'header' => 'EAN/UPC',
                                'transform' => 'pad_ean',
                            ],
                            'product_name' => [
                                'column' => 'C',
                                'header' => 'Product Name',
                                'required' => true,
                            ],
                            'short_description' => [
                                'column' => 'D',
                                'header' => 'Short Desc',
                            ],
                            'long_description' => [
                                'column' => 'E',
                                'header' => 'Long Description',
                            ],
                            'category' => [
                                'column' => 'F',
                                'header' => 'Category',
                            ],
                            'subcategory' => [
                                'column' => 'G',
                                'header' => 'Subcategory',
                            ],
                            'color' => [
                                'column' => 'H',
                                'header' => 'Color',
                            ],
                            'size' => [
                                'column' => 'I',
                                'header' => 'Size',
                            ],
                            'price' => [
                                'column' => 'J',
                                'header' => 'RRP EUR',
                                'transform' => 'to_decimal',
                            ],
                            'wholesale_price' => [
                                'column' => 'K',
                                'header' => 'Wholesale EUR',
                                'transform' => 'to_decimal',
                            ],
                            'stock' => [
                                'column' => 'L',
                                'header' => 'Available Stock',
                                'transform' => 'to_integer',
                            ],
                            'images' => [
                                'column' => 'M',
                                'header' => 'Image URLs',
                                'transform' => 'split_by_semicolon',
                            ],
                            'technology' => [
                                'column' => 'N',
                                'header' => 'Technologies',
                                'transform' => 'split_by_comma',
                            ],
                            'weight_kg' => [
                                'column' => 'O',
                                'header' => 'Weight (kg)',
                                'transform' => 'to_decimal',
                            ],
                        ],
                    ],
                    'transform_rules' => [
                        'pad_ean' => [
                            'type' => 'pad_left',
                            'length' => 13,
                            'char' => '0',
                        ],
                        'to_decimal' => [
                            'type' => 'number',
                            'decimal_separator' => ',',
                            'thousands_separator' => '.',
                        ],
                        'to_integer' => [
                            'type' => 'integer',
                        ],
                        'split_by_semicolon' => [
                            'type' => 'split',
                            'delimiter' => ';',
                            'trim' => true,
                        ],
                        'split_by_comma' => [
                            'type' => 'split',
                            'delimiter' => ',',
                            'trim' => true,
                        ],
                    ],
                    'validation_rules' => [
                        'reference' => 'required|string',
                        'ean' => 'nullable|string|size:13',
                        'product_name' => 'required|string|min:3',
                        'price' => 'required|numeric|min:0',
                    ],
                    'is_active' => true,
                ]
            );
        }

        // Mapeos para Adidas CSV
        $adidasCsv = SupplierSource::whereHas('supplier', fn($q) => $q->where('code', 'ADIDAS'))
            ->where('source_type', 'ftp_csv')
            ->first();

        if ($adidasCsv) {
            SupplierExtractionMapping::updateOrCreate(
                ['source_id' => $adidasCsv->id, 'is_active' => true],
                [
                    'source_id' => $adidasCsv->id,
                    'field_mappings' => [
                        'file_type' => 'csv',
                        'delimiter' => ';',
                        'enclosure' => '"',
                        'encoding' => 'UTF-8',
                        'has_header' => true,
                        'columns' => [
                            'reference' => ['index' => 0, 'header' => 'article_number'],
                            'ean' => ['index' => 1, 'header' => 'ean'],
                            'product_name' => ['index' => 2, 'header' => 'name_es'],
                            'short_description' => ['index' => 3, 'header' => 'short_desc_es'],
                            'long_description' => ['index' => 4, 'header' => 'long_desc_es'],
                            'category' => ['index' => 5, 'header' => 'category_path'],
                            'color' => ['index' => 6, 'header' => 'color_name'],
                            'color_code' => ['index' => 7, 'header' => 'color_code'],
                            'price' => ['index' => 8, 'header' => 'rrp_eur'],
                            'images' => ['index' => 9, 'header' => 'image_urls', 'transform' => 'split_pipe'],
                            'collection' => ['index' => 10, 'header' => 'collection'],
                            'gender' => ['index' => 11, 'header' => 'gender'],
                            'sustainability' => ['index' => 12, 'header' => 'is_sustainable'],
                        ],
                    ],
                    'transform_rules' => [
                        'split_pipe' => [
                            'type' => 'split',
                            'delimiter' => '|',
                        ],
                    ],
                    'validation_rules' => [
                        'reference' => 'required',
                        'product_name' => 'required|min:3',
                        'price' => 'required|numeric',
                    ],
                    'is_active' => true,
                ]
            );
        }

        // Mapeos para Puma PDF
        $pumaPdf = SupplierSource::whereHas('supplier', fn($q) => $q->where('code', 'PUMA'))
            ->where('source_type', 'upload_pdf')
            ->first();

        if ($pumaPdf) {
            SupplierExtractionMapping::updateOrCreate(
                ['source_id' => $pumaPdf->id, 'is_active' => true],
                [
                    'source_id' => $pumaPdf->id,
                    'field_mappings' => [
                        'file_type' => 'pdf',
                        'extraction_mode' => 'hybrid', // ocr + text
                        'language' => 'spa+eng',
                        'patterns' => [
                            'reference' => [
                                'type' => 'regex',
                                'pattern' => '/(?:REF|SKU|Art)[.:\\s]*([A-Z0-9-]+)/i',
                                'group' => 1,
                            ],
                            'ean' => [
                                'type' => 'regex',
                                'pattern' => '/(?:EAN|UPC|GTIN)[.:\\s]*([0-9]{13})/i',
                                'group' => 1,
                            ],
                            'product_name' => [
                                'type' => 'position',
                                'rule' => 'first_bold_text_after_reference',
                            ],
                            'price' => [
                                'type' => 'regex',
                                'pattern' => '/(?:PVP|Precio)[.:\\s]*([0-9]+[.,][0-9]{2})\\s*€/i',
                                'group' => 1,
                            ],
                            'description' => [
                                'type' => 'section',
                                'start' => '/Descripción:/i',
                                'end' => '/(?:Características|Especificaciones|PVP):/i',
                            ],
                        ],
                        'page_detection' => [
                            'product_start' => '/^(?:REF|SKU):/m',
                            'products_per_page' => 'auto',
                        ],
                    ],
                    'validation_rules' => [
                        'reference' => 'required',
                        'product_name' => 'required|min:3',
                    ],
                    'is_active' => true,
                ]
            );
        }
    }
}
```

---

### 26.5 SupplierAutomationSeeder

```php
<?php
// database/seeders/SupplierAutomationSeeder.php

namespace Database\Seeders;

use App\Models\Supplier\SupplierAutomationWorkflow;
use App\Models\Supplier\SupplierAutomationSetting;
use App\Models\Supplier\SupplierAutomationRateLimit;
use Illuminate\Database\Seeder;

class SupplierAutomationSeeder extends Seeder
{
    public function run(): void
    {
        // Configuración global de automatización
        $settings = [
            [
                'key' => 'orchestrator_base_url',
                'value' => 'http://localhost:5678',
                'description' => 'URL base del orquestador (n8n, Temporal, etc.)',
                'is_encrypted' => false,
            ],
            [
                'key' => 'orchestrator_api_key',
                'value' => encrypt('your-api-key-here'),
                'description' => 'API Key para autenticación con orquestador',
                'is_encrypted' => true,
            ],
            [
                'key' => 'webhook_secret',
                'value' => encrypt('webhook-hmac-secret-32-chars!!'),
                'description' => 'Secret para verificación HMAC de callbacks',
                'is_encrypted' => true,
            ],
            [
                'key' => 'default_timeout_seconds',
                'value' => '300',
                'description' => 'Timeout por defecto para ejecuciones',
                'is_encrypted' => false,
            ],
            [
                'key' => 'max_retries',
                'value' => '3',
                'description' => 'Número máximo de reintentos',
                'is_encrypted' => false,
            ],
            [
                'key' => 'retry_delay_seconds',
                'value' => '60',
                'description' => 'Delay entre reintentos (base para exponential backoff)',
                'is_encrypted' => false,
            ],
            [
                'key' => 'parallel_executions_limit',
                'value' => '5',
                'description' => 'Máximo de ejecuciones paralelas',
                'is_encrypted' => false,
            ],
            [
                'key' => 'proxy_rotation_enabled',
                'value' => 'true',
                'description' => 'Habilitar rotación de proxies',
                'is_encrypted' => false,
            ],
            [
                'key' => 'proxy_list',
                'value' => encrypt(json_encode([
                    'http://proxy1.example.com:8080',
                    'http://proxy2.example.com:8080',
                    'http://proxy3.example.com:8080',
                ])),
                'description' => 'Lista de proxies para rotación',
                'is_encrypted' => true,
            ],
            [
                'key' => 'openai_api_key',
                'value' => encrypt('sk-your-openai-key'),
                'description' => 'API Key de OpenAI para procesamiento IA',
                'is_encrypted' => true,
            ],
            [
                'key' => 'default_ai_model',
                'value' => 'gpt-4o-mini',
                'description' => 'Modelo IA por defecto',
                'is_encrypted' => false,
            ],
        ];

        foreach ($settings as $setting) {
            SupplierAutomationSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        // Workflows de ejemplo
        $workflows = [
            [
                'workflow_id' => 'wf_scrape_web_simple',
                'name' => 'Web Scraper - Simple',
                'description' => 'Scraping de páginas web estáticas con HTTP Request',
                'workflow_type' => 'website',
                'is_active' => true,
                'version' => '1.0.0',
                'config' => [
                    'nodes' => ['webhook', 'http_request', 'html_extract', 'respond'],
                    'timeout_seconds' => 60,
                    'retry_on_failure' => true,
                ],
            ],
            [
                'workflow_id' => 'wf_scrape_selenium',
                'name' => 'Web Scraper - Selenium',
                'description' => 'Scraping de SPAs y páginas dinámicas con Selenium',
                'workflow_type' => 'website',
                'is_active' => true,
                'version' => '1.0.0',
                'config' => [
                    'nodes' => ['webhook', 'selenium_navigate', 'screenshot', 'html_extract', 'gpt_vision', 'respond'],
                    'timeout_seconds' => 120,
                    'headless' => true,
                    'window_size' => [1920, 1080],
                ],
            ],
            [
                'workflow_id' => 'wf_ftp_processor',
                'name' => 'FTP File Processor',
                'description' => 'Descarga y procesa archivos de FTP/SFTP',
                'workflow_type' => 'ftp',
                'is_active' => true,
                'version' => '1.0.0',
                'config' => [
                    'nodes' => ['schedule', 'ftp_list', 'ftp_download', 'file_parser', 'callback'],
                    'supported_formats' => ['xlsx', 'xls', 'csv'],
                ],
            ],
            [
                'workflow_id' => 'wf_pdf_extractor',
                'name' => 'PDF Extractor',
                'description' => 'Extrae datos de catálogos PDF con OCR',
                'workflow_type' => 'file',
                'is_active' => true,
                'version' => '1.0.0',
                'config' => [
                    'nodes' => ['webhook', 'pdf_to_images', 'ocr', 'gpt_parse', 'respond'],
                    'ocr_engine' => 'tesseract',
                    'languages' => ['spa', 'eng'],
                ],
            ],
            [
                'workflow_id' => 'wf_ai_content_generator',
                'name' => 'AI Content Generator',
                'description' => 'Genera descripciones de producto con IA',
                'workflow_type' => 'processing',
                'is_active' => true,
                'version' => '1.0.0',
                'config' => [
                    'nodes' => ['webhook', 'fetch_prompt', 'langchain', 'validate', 'respond'],
                    'default_model' => 'gpt-4o',
                    'temperature' => 0.7,
                ],
            ],
        ];

        foreach ($workflows as $workflow) {
            SupplierAutomationWorkflow::updateOrCreate(
                ['workflow_id' => $workflow['workflow_id']],
                $workflow
            );
        }

        // Rate limits por defecto
        $rateLimits = [
            [
                'limitable_type' => 'global',
                'limitable_id' => 0,
                'limit_type' => 'requests_per_minute',
                'limit_value' => 100,
                'window_seconds' => 60,
            ],
            [
                'limitable_type' => 'global',
                'limitable_id' => 0,
                'limit_type' => 'requests_per_hour',
                'limit_value' => 2000,
                'window_seconds' => 3600,
            ],
            [
                'limitable_type' => 'global',
                'limitable_id' => 0,
                'limit_type' => 'concurrent_executions',
                'limit_value' => 10,
                'window_seconds' => 0,
            ],
        ];

        foreach ($rateLimits as $limit) {
            SupplierAutomationRateLimit::updateOrCreate(
                [
                    'limitable_type' => $limit['limitable_type'],
                    'limitable_id' => $limit['limitable_id'],
                    'limit_type' => $limit['limit_type'],
                ],
                $limit
            );
        }
    }
}
```

---

### 26.6 PrestashopCategorySeeder

```php
<?php
// database/seeders/PrestashopCategorySeeder.php

namespace Database\Seeders;

use App\Models\PrestashopCategory;
use Illuminate\Database\Seeder;

class PrestashopCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // Categorías principales
            [
                'id_category' => 2,
                'id_parent' => 1,
                'name' => 'Deportes',
                'level_depth' => 1,
                'is_active' => true,
            ],
            [
                'id_category' => 3,
                'id_parent' => 2,
                'name' => 'Calzado Deportivo',
                'level_depth' => 2,
                'is_active' => true,
            ],
            [
                'id_category' => 4,
                'id_parent' => 3,
                'name' => 'Running',
                'level_depth' => 3,
                'is_active' => true,
            ],
            [
                'id_category' => 5,
                'id_parent' => 3,
                'name' => 'Training',
                'level_depth' => 3,
                'is_active' => true,
            ],
            [
                'id_category' => 6,
                'id_parent' => 3,
                'name' => 'Casual/Lifestyle',
                'level_depth' => 3,
                'is_active' => true,
            ],
            [
                'id_category' => 7,
                'id_parent' => 2,
                'name' => 'Ropa Deportiva',
                'level_depth' => 2,
                'is_active' => true,
            ],
            [
                'id_category' => 8,
                'id_parent' => 7,
                'name' => 'Camisetas',
                'level_depth' => 3,
                'is_active' => true,
            ],
            [
                'id_category' => 9,
                'id_parent' => 7,
                'name' => 'Pantalones',
                'level_depth' => 3,
                'is_active' => true,
            ],
            [
                'id_category' => 10,
                'id_parent' => 7,
                'name' => 'Sudaderas',
                'level_depth' => 3,
                'is_active' => true,
            ],
            [
                'id_category' => 11,
                'id_parent' => 2,
                'name' => 'Accesorios',
                'level_depth' => 2,
                'is_active' => true,
            ],
            [
                'id_category' => 12,
                'id_parent' => 11,
                'name' => 'Mochilas',
                'level_depth' => 3,
                'is_active' => true,
            ],
            [
                'id_category' => 13,
                'id_parent' => 11,
                'name' => 'Gorras',
                'level_depth' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            PrestashopCategory::updateOrCreate(
                ['id_category' => $category['id_category']],
                $category
            );
        }
    }
}
```

---

### 26.7 DatabaseSeeder Principal

```php
<?php
// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Primero las categorías de PrestaShop (referenciadas por otros)
            PrestashopCategorySeeder::class,

            // Proveedores base
            SupplierSeeder::class,

            // Fuentes de datos por proveedor
            SupplierSourceSeeder::class,

            // Mapeos de extracción por fuente
            SupplierExtractionMappingSeeder::class,

            // Prompts para generación de contenido
            SupplierPromptSeeder::class,

            // Configuración de automatización
            SupplierAutomationSeeder::class,
        ]);
    }
}
```

---

### 26.8 Comando para Ejecutar Seeders

```bash
# Ejecutar todos los seeders
php artisan db:seed

# Ejecutar seeder específico
php artisan db:seed --class=SupplierSeeder

# Refrescar migraciones y seeders (¡cuidado en producción!)
php artisan migrate:fresh --seed

# Solo en desarrollo: poblar con datos de prueba
php artisan db:seed --class=SupplierSeeder --env=local
```

---

### 26.9 Factory para Testing

```php
<?php
// database/factories/SupplierFactory.php

namespace Database\Factories;

use App\Models\Supplier\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        $brands = ['Nike', 'Adidas', 'Puma', 'Asics', 'New Balance', 'Reebok', 'Under Armour', 'Saucony'];
        $brand = $this->faker->unique()->randomElement($brands);

        return [
            'code' => strtoupper(substr($brand, 0, 5)),
            'label' => $brand . ' ' . $this->faker->country(),
            'description' => $this->faker->sentence(10),
            'website' => 'https://www.' . strtolower($brand) . '.com',
            'contact_email' => strtolower($brand) . '@example.com',
            'contact_phone' => $this->faker->phoneNumber(),
            'is_active' => $this->faker->boolean(80),
            'priority' => $this->faker->numberBetween(1, 10),
            'backups' => [
                'default_lang' => 'es',
                'auto_approve' => $this->faker->boolean(30),
                'quality_threshold' => $this->faker->numberBetween(60, 90),
            ],
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
```

```php
<?php
// database/factories/SupplierSourceFactory.php

namespace Database\Factories;

use App\Models\Supplier\Supplier;
use App\Models\Supplier\SupplierSource;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierSourceFactory extends Factory
{
    protected $model = SupplierSource::class;

    public function definition(): array
    {
        $types = ['website', 'ftp_excel', 'ftp_csv', 'api', 'upload_excel', 'upload_pdf'];

        return [
            'supplier_id' => Supplier::factory(),
            'source_type' => $this->faker->randomElement($types),
            'label' => $this->faker->words(3, true),
            'priority' => $this->faker->numberBetween(1, 5),
            'is_active' => $this->faker->boolean(80),
            'search_config' => [],
            'rate_limit' => [
                'requests_per_minute' => $this->faker->numberBetween(10, 60),
            ],
        ];
    }

    public function website(): static
    {
        return $this->state(fn (array $attributes) => [
            'source_type' => 'website',
            'search_config' => [
                'base_url' => 'https://example.com',
                'search_url' => 'https://example.com/search?q={reference}',
            ],
        ]);
    }

    public function ftpExcel(): static
    {
        return $this->state(fn (array $attributes) => [
            'source_type' => 'ftp_excel',
            'ftp_config' => [
                'host' => 'ftp.example.com',
                'port' => 21,
                'path' => '/exports/',
            ],
        ]);
    }
}
```

---

### 26.10 Uso en Tests

```php
<?php
// tests/Feature/SupplierExtractionTest.php

namespace Tests\Feature;

use App\Models\Supplier\Supplier;
use App\Models\Supplier\SupplierSource;
use App\Models\Supplier\SupplierExtractionMapping;
use App\Services\ExtractionResultService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierExtractionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ejecutar seeders necesarios
        $this->seed([
            PrestashopCategorySeeder::class,
        ]);
    }

    public function test_can_process_extracted_data(): void
    {
        // Arrange
        $supplier = Supplier::factory()->active()->create(['code' => 'TEST']);
        $source = SupplierSource::factory()->website()->create(['supplier_id' => $supplier->id]);

        SupplierExtractionMapping::create([
            'source_id' => $source->id,
            'field_mappings' => [
                'selectors' => [
                    'product_name' => ['selector' => 'h1', 'type' => 'text'],
                ],
            ],
            'is_active' => true,
        ]);

        $extractedData = [
            'reference' => 'TEST-001',
            'ean' => '1234567890123',
            'product_name' => 'Test Product',
            'short_description' => 'A test product description',
            'price' => 99.99,
            'images' => ['https://example.com/image1.jpg'],
        ];

        // Act
        $service = app(ExtractionResultService::class);
        $result = $service->processExtractedData(
            $supplier,
            $source,
            $extractedData,
            'batch_test_001'
        );

        // Assert
        $this->assertDatabaseHas('supplier_extraction_results', [
            'supplier_id' => $supplier->id,
            'reference' => 'TEST-001',
            'status' => 'new',
        ]);
    }

    public function test_detects_updated_products(): void
    {
        // Arrange
        $supplier = Supplier::factory()->active()->create();
        $source = SupplierSource::factory()->website()->create(['supplier_id' => $supplier->id]);

        // Crear producto existente
        $existingData = [
            'reference' => 'TEST-002',
            'product_name' => 'Original Name',
            'short_description' => 'Original description',
        ];

        $service = app(ExtractionResultService::class);
        $service->processExtractedData($supplier, $source, $existingData, 'batch_1');

        // Modificar datos
        $updatedData = [
            'reference' => 'TEST-002',
            'product_name' => 'Updated Name',
            'short_description' => 'Updated description',
        ];

        // Act
        $result = $service->processExtractedData($supplier, $source, $updatedData, 'batch_2');

        // Assert
        $this->assertEquals('updated', $result->status);
        $this->assertNotNull($result->previous_hash);
    }
}
```

---

### 26.11 Resumen de Seeders

| Seeder | Descripción | Dependencias |
|--------|-------------|--------------|
| `PrestashopCategorySeeder` | Categorías del catálogo | Ninguna |
| `SupplierSeeder` | Proveedores de ejemplo | Ninguna |
| `SupplierSourceSeeder` | Fuentes de datos | SupplierSeeder |
| `SupplierExtractionMappingSeeder` | Mapeos de campos | SupplierSourceSeeder |
| `SupplierPromptSeeder` | Prompts de IA | SupplierSeeder |
| `SupplierAutomationSeeder` | Config de automatización | Ninguna |

**Total de registros de ejemplo:**
- 5 Proveedores
- 8 Fuentes de datos
- 4 Mapeos de extracción
- 6 Prompts de IA
- 11 Configuraciones de automatización
- 5 Workflows
- 3 Rate limits
- 12 Categorías PrestaShop

---

## 27. Funcionalidades Avanzadas Recomendadas

---

### 27.1 Sistema de Validación de Calidad de Contenido

Antes de publicar contenido generado por IA, es crítico validar su calidad.

#### Tabla: `supplier_content_validations`

```sql
CREATE TABLE supplier_content_validations (
    id BIGSERIAL PRIMARY KEY,
    content_id BIGINT REFERENCES supplier_contents(id),

    -- Métricas de calidad
    quality_score DECIMAL(5,2),           -- 0-100
    readability_score DECIMAL(5,2),       -- Flesch-Kincaid
    keyword_density DECIMAL(5,4),         -- % de keywords
    unique_words_ratio DECIMAL(5,4),      -- Diversidad léxica
    sentence_avg_length DECIMAL(6,2),

    -- Validaciones específicas
    has_required_sections BOOLEAN DEFAULT false,
    has_brand_terms BOOLEAN DEFAULT false,
    has_prohibited_words BOOLEAN DEFAULT false,
    plagiarism_score DECIMAL(5,2),        -- 0-100 (0 = original)

    -- Resultados
    validation_status VARCHAR(20),         -- passed, failed, needs_review
    issues JSONB,                          -- Lista de problemas encontrados
    suggestions JSONB,                     -- Sugerencias de mejora

    validated_by VARCHAR(50),              -- 'system' o user_id
    validated_at TIMESTAMP,

    created_at TIMESTAMP DEFAULT NOW()
);
```

#### Reglas de Validación Configurables

```php
// config/content_validation.php
return [
    'rules' => [
        'short_description' => [
            'min_length' => 80,
            'max_length' => 160,
            'required_elements' => ['benefit', 'feature'],
            'prohibited_words' => ['mejor', 'único', 'increíble'], // superlativos
            'min_readability_score' => 60,
        ],
        'long_description' => [
            'min_length' => 200,
            'max_length' => 800,
            'min_paragraphs' => 2,
            'required_html_tags' => ['p', 'ul'],
            'max_keyword_density' => 0.03, // 3%
        ],
    ],
    'thresholds' => [
        'auto_approve' => 85,    // >= 85 se aprueba automáticamente
        'needs_review' => 60,    // 60-84 requiere revisión manual
        'auto_reject' => 60,     // < 60 se rechaza
    ],
];
```

---

### 27.2 A/B Testing de Prompts

Para optimizar la calidad del contenido generado, implementar pruebas A/B.

#### Tabla: `supplier_prompt_experiments`

```sql
CREATE TABLE supplier_prompt_experiments (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    prompt_type VARCHAR(50) NOT NULL,

    -- Variantes
    control_prompt_id BIGINT REFERENCES supplier_prompts(id),
    variant_prompt_id BIGINT REFERENCES supplier_prompts(id),

    -- Configuración
    traffic_split DECIMAL(3,2) DEFAULT 0.50, -- 50% cada variante
    sample_size INT DEFAULT 100,              -- Mínimo de muestras

    -- Estado
    status VARCHAR(20) DEFAULT 'draft',       -- draft, running, completed, cancelled
    started_at TIMESTAMP,
    ended_at TIMESTAMP,

    -- Resultados
    results JSONB,
    winner_prompt_id BIGINT,
    statistical_significance DECIMAL(5,4),

    created_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE supplier_prompt_experiment_results (
    id BIGSERIAL PRIMARY KEY,
    experiment_id BIGINT REFERENCES supplier_prompt_experiments(id),
    prompt_id BIGINT REFERENCES supplier_prompts(id),
    content_id BIGINT REFERENCES supplier_contents(id),

    -- Métricas
    quality_score DECIMAL(5,2),
    generation_time_ms INT,
    tokens_used INT,
    cost_usd DECIMAL(10,6),

    -- Feedback (si hay revisión manual)
    human_rating INT,                         -- 1-5
    was_edited BOOLEAN DEFAULT false,
    edit_percentage DECIMAL(5,2),             -- % de cambios

    created_at TIMESTAMP DEFAULT NOW()
);
```

---

### 27.3 Tracking de Costos de IA

Monitorear y controlar costos de API de OpenAI/Claude.

#### Tabla: `supplier_ai_costs`

```sql
CREATE TABLE supplier_ai_costs (
    id BIGSERIAL PRIMARY KEY,

    -- Referencias
    supplier_id BIGINT REFERENCES suppliers(id),
    content_id BIGINT,
    batch_id VARCHAR(100),

    -- Detalles de uso
    model VARCHAR(50) NOT NULL,               -- gpt-4o, gpt-4o-mini, claude-3
    operation_type VARCHAR(50),               -- generation, validation, extraction

    -- Tokens
    input_tokens INT NOT NULL,
    output_tokens INT NOT NULL,
    total_tokens INT GENERATED ALWAYS AS (input_tokens + output_tokens) STORED,

    -- Costos (en USD)
    input_cost DECIMAL(10,6),
    output_cost DECIMAL(10,6),
    total_cost DECIMAL(10,6) GENERATED ALWAYS AS (input_cost + output_cost) STORED,

    -- Metadata
    request_id VARCHAR(100),                  -- ID de la API
    latency_ms INT,

    created_at TIMESTAMP DEFAULT NOW()
);

-- Índices para reportes
CREATE INDEX idx_ai_costs_supplier_date ON supplier_ai_costs(supplier_id, created_at);
CREATE INDEX idx_ai_costs_model ON supplier_ai_costs(model, created_at);

-- Vista para resumen mensual
CREATE VIEW supplier_ai_costs_monthly AS
SELECT
    supplier_id,
    DATE_TRUNC('month', created_at) as month,
    model,
    COUNT(*) as requests,
    SUM(total_tokens) as total_tokens,
    SUM(total_cost) as total_cost_usd
FROM supplier_ai_costs
GROUP BY supplier_id, DATE_TRUNC('month', created_at), model;
```

#### Alertas de Costos

```php
// App\Services\AiCostAlertService
class AiCostAlertService
{
    public function checkBudgetLimits(): void
    {
        $limits = [
            'daily' => config('ai.budget.daily_limit_usd', 50),
            'monthly' => config('ai.budget.monthly_limit_usd', 1000),
            'per_supplier' => config('ai.budget.per_supplier_limit_usd', 200),
        ];

        // Verificar límite diario
        $todayCost = SupplierAiCost::whereDate('created_at', today())->sum('total_cost');
        if ($todayCost >= $limits['daily'] * 0.8) {
            $this->sendAlert('daily_budget_warning', [
                'current' => $todayCost,
                'limit' => $limits['daily'],
                'percentage' => ($todayCost / $limits['daily']) * 100,
            ]);
        }

        // Pausar generación si se excede
        if ($todayCost >= $limits['daily']) {
            Cache::put('ai_generation_paused', true, now()->endOfDay());
            $this->sendAlert('daily_budget_exceeded', ['cost' => $todayCost]);
        }
    }
}
```

---

### 27.4 Pipeline de Procesamiento de Imágenes

Las imágenes de productos necesitan procesamiento antes de usarse.

#### Tabla: `supplier_product_images`

```sql
CREATE TABLE supplier_product_images (
    id BIGSERIAL PRIMARY KEY,

    -- Referencias
    extraction_result_id BIGINT REFERENCES supplier_extraction_results(id),
    supplier_id BIGINT REFERENCES suppliers(id),
    reference VARCHAR(100),

    -- Imagen original
    source_url TEXT NOT NULL,
    source_hash VARCHAR(64),                  -- Para detectar duplicados

    -- Imagen procesada
    local_path VARCHAR(500),
    cdn_url VARCHAR(500),

    -- Metadata
    original_width INT,
    original_height INT,
    processed_width INT,
    processed_height INT,
    file_size_bytes BIGINT,
    mime_type VARCHAR(50),

    -- Variantes generadas
    variants JSONB,                           -- {thumb: url, medium: url, large: url}

    -- Estado
    status VARCHAR(20) DEFAULT 'pending',     -- pending, downloading, processing, completed, failed
    error_message TEXT,

    -- Calidad
    quality_score DECIMAL(5,2),               -- Análisis de calidad de imagen
    is_primary BOOLEAN DEFAULT false,
    position INT DEFAULT 0,

    downloaded_at TIMESTAMP,
    processed_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT NOW()
);
```

#### Servicio de Procesamiento

```php
// App\Services\ImageProcessingService
class ImageProcessingService
{
    public function processProductImages(SupplierExtractionResult $result): void
    {
        $images = $result->extracted_data['images'] ?? [];

        foreach ($images as $index => $imageUrl) {
            ProcessProductImage::dispatch($result, $imageUrl, $index)
                ->onQueue('images');
        }
    }
}

// App\Jobs\ProcessProductImage
class ProcessProductImage implements ShouldQueue
{
    public function handle(): void
    {
        // 1. Descargar imagen
        $tempPath = $this->downloadImage($this->imageUrl);

        // 2. Validar (no corrupta, tamaño mínimo)
        $this->validateImage($tempPath);

        // 3. Optimizar (comprimir, convertir a WebP)
        $optimized = $this->optimizeImage($tempPath);

        // 4. Generar variantes (thumb, medium, large)
        $variants = $this->generateVariants($optimized);

        // 5. Subir a CDN/Storage
        $urls = $this->uploadToCdn($variants);

        // 6. Guardar registro
        SupplierProductImage::create([
            'extraction_result_id' => $this->result->id,
            'supplier_id' => $this->result->supplier_id,
            'reference' => $this->result->reference,
            'source_url' => $this->imageUrl,
            'local_path' => $optimized,
            'cdn_url' => $urls['original'],
            'variants' => $urls,
            'is_primary' => $this->index === 0,
            'position' => $this->index,
            'status' => 'completed',
        ]);
    }

    private function generateVariants(string $path): array
    {
        return [
            'thumb' => Image::make($path)->fit(150, 150)->encode('webp', 80),
            'medium' => Image::make($path)->fit(400, 400)->encode('webp', 85),
            'large' => Image::make($path)->fit(800, 800)->encode('webp', 90),
            'original' => Image::make($path)->encode('webp', 95),
        ];
    }
}
```

---

### 27.5 Sistema Multi-Idioma

Generar contenido en múltiples idiomas automáticamente.

#### Tabla: `supplier_content_langs`

```sql
CREATE TABLE supplier_content_langs (
    id BIGSERIAL PRIMARY KEY,
    content_id BIGINT REFERENCES supplier_contents(id) ON DELETE CASCADE,
    lang_id INT NOT NULL,                     -- 1=ES, 2=EN, 3=FR, etc.

    -- Contenido traducido
    short_description TEXT,
    long_description TEXT,
    meta_title VARCHAR(255),
    meta_description VARCHAR(500),
    keywords TEXT,

    -- Estado
    status VARCHAR(20) DEFAULT 'pending',     -- pending, translating, completed, failed
    source_lang_id INT,                       -- Idioma origen
    translation_method VARCHAR(20),           -- ai, deepl, manual

    -- Calidad
    quality_score DECIMAL(5,2),
    reviewed_by BIGINT,
    reviewed_at TIMESTAMP,

    -- Costos
    translation_cost DECIMAL(10,6),

    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP
);
```

#### Configuración de Idiomas

```php
// config/translations.php
return [
    'default_lang' => 1, // Español

    'languages' => [
        1 => ['code' => 'es', 'name' => 'Español', 'is_source' => true],
        2 => ['code' => 'en', 'name' => 'English'],
        3 => ['code' => 'fr', 'name' => 'Français'],
        4 => ['code' => 'de', 'name' => 'Deutsch'],
        5 => ['code' => 'pt', 'name' => 'Português'],
    ],

    'auto_translate' => [2, 3], // Traducir automáticamente a EN y FR

    'providers' => [
        'primary' => 'deepl',   // Más preciso para textos comerciales
        'fallback' => 'openai', // Si DeepL falla
    ],

    'deepl' => [
        'api_key' => env('DEEPL_API_KEY'),
        'formality' => 'default', // default, more, less
    ],
];
```

---

### 27.6 Monitoreo de Competencia

Tracking opcional de precios y productos de competidores.

#### Tabla: `competitor_products`

```sql
CREATE TABLE competitor_products (
    id BIGSERIAL PRIMARY KEY,

    -- Identificación
    competitor_name VARCHAR(100) NOT NULL,
    competitor_url VARCHAR(500),

    -- Producto
    reference VARCHAR(100),
    ean VARCHAR(13),
    product_name VARCHAR(500),
    product_url TEXT,

    -- Precios
    current_price DECIMAL(10,2),
    original_price DECIMAL(10,2),
    currency VARCHAR(3) DEFAULT 'EUR',

    -- Disponibilidad
    in_stock BOOLEAN,
    stock_quantity INT,

    -- Nuestro producto relacionado
    our_product_id BIGINT,
    price_difference DECIMAL(10,2),
    price_difference_percent DECIMAL(5,2),

    -- Tracking
    first_seen_at TIMESTAMP DEFAULT NOW(),
    last_seen_at TIMESTAMP DEFAULT NOW(),
    price_history JSONB,                      -- [{date, price}, ...]

    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP
);

CREATE INDEX idx_competitor_ean ON competitor_products(ean);
CREATE INDEX idx_competitor_reference ON competitor_products(reference);
```

---

### 27.7 Sistema de Notificaciones

Alertas multicanal para eventos importantes.

#### Tabla: `supplier_automation_notifications`

```sql
CREATE TABLE supplier_automation_notifications (
    id BIGSERIAL PRIMARY KEY,

    -- Tipo de notificación
    notification_type VARCHAR(50) NOT NULL,
    priority VARCHAR(20) DEFAULT 'normal',    -- low, normal, high, critical

    -- Contenido
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    data JSONB,

    -- Canales
    channels JSONB DEFAULT '["database"]',    -- database, email, slack, telegram

    -- Destinatarios
    notifiable_type VARCHAR(100),
    notifiable_id BIGINT,

    -- Estado por canal
    channel_status JSONB,                     -- {email: sent, slack: failed, ...}

    -- Timestamps
    sent_at TIMESTAMP,
    read_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT NOW()
);
```

#### Tipos de Notificaciones

```php
// App\Notifications\SupplierAutomation
class ExtractionCompleted extends Notification
{
    public function via($notifiable): array
    {
        return ['database', 'slack'];
    }

    public function toSlack($notifiable): SlackMessage
    {
        $stats = $this->batch->getStatistics();

        return (new SlackMessage)
            ->success()
            ->content("✅ Extracción completada: {$this->supplier->label}")
            ->attachment(function ($attachment) use ($stats) {
                $attachment
                    ->title('Resumen del lote')
                    ->fields([
                        'Nuevos' => $stats['new_items'],
                        'Actualizados' => $stats['updated_items'],
                        'Sin cambios' => $stats['unchanged_items'],
                        'Errores' => $stats['failed_items'],
                    ]);
            });
    }
}

// Eventos que disparan notificaciones
$events = [
    'extraction_completed' => ['database', 'slack'],
    'extraction_failed' => ['database', 'slack', 'email'],
    'content_generated' => ['database'],
    'content_needs_review' => ['database', 'email'],
    'budget_warning' => ['database', 'slack', 'email'],
    'budget_exceeded' => ['database', 'slack', 'email', 'sms'],
    'health_check_failed' => ['slack', 'email'],
    'new_products_detected' => ['database', 'slack'],
];
```

---

### 27.8 Caché y Optimización de Rendimiento

Estrategias de caché para alto rendimiento.

```php
// App\Services\SupplierCacheService
class SupplierCacheService
{
    // Cachear configuración de proveedores (cambia poco)
    public function getSupplierConfig(int $supplierId): array
    {
        return Cache::tags(['suppliers', "supplier:{$supplierId}"])
            ->remember("supplier:{$supplierId}:config", 3600, function () use ($supplierId) {
                return Supplier::with(['sources', 'prompts', 'sources.mappings'])
                    ->findOrFail($supplierId)
                    ->toArray();
            });
    }

    // Cachear prompts activos
    public function getActivePrompt(int $supplierId, string $promptType): ?SupplierPrompt
    {
        return Cache::tags(['prompts'])
            ->remember("prompt:{$supplierId}:{$promptType}", 1800, function () use ($supplierId, $promptType) {
                return SupplierPrompt::where('supplier_id', $supplierId)
                    ->where('prompt_type', $promptType)
                    ->where('is_active', true)
                    ->orderBy('priority')
                    ->first();
            });
    }

    // Invalidar caché cuando se actualiza
    public function invalidateSupplier(int $supplierId): void
    {
        Cache::tags(["supplier:{$supplierId}"])->flush();
    }
}
```

#### Índices Recomendados

```sql
-- Índices para consultas frecuentes
CREATE INDEX idx_extraction_results_batch ON supplier_extraction_results(batch_id, status);
CREATE INDEX idx_extraction_results_supplier_status ON supplier_extraction_results(supplier_id, status);
CREATE INDEX idx_contents_status ON supplier_contents(status, created_at);
CREATE INDEX idx_contents_supplier_pending ON supplier_contents(supplier_id) WHERE status = 'pending';

-- Índice parcial para productos activos
CREATE INDEX idx_active_suppliers ON suppliers(id) WHERE is_active = true;

-- Índice para búsqueda por referencia
CREATE INDEX idx_extraction_reference ON supplier_extraction_results(reference, supplier_id);

-- Índice GIN para búsqueda en JSONB
CREATE INDEX idx_extraction_data ON supplier_extraction_results USING GIN (extracted_data);
```

---

### 27.9 Permisos y Roles (RBAC)

Control de acceso granular para el sistema.

```php
// Permisos del módulo
$permissions = [
    // Proveedores
    'suppliers.view',
    'suppliers.create',
    'suppliers.edit',
    'suppliers.delete',
    'suppliers.manage_sources',

    // Extracción
    'extraction.view',
    'extraction.trigger',
    'extraction.configure_mappings',

    // Contenido
    'content.view',
    'content.generate',
    'content.approve',
    'content.reject',
    'content.edit',
    'content.publish',

    // Prompts
    'prompts.view',
    'prompts.create',
    'prompts.edit',
    'prompts.delete',
    'prompts.run_experiments',

    // Automatización
    'automation.view_dashboard',
    'automation.view_logs',
    'automation.manage_workflows',
    'automation.manage_settings',
    'automation.retry_failed',

    // Administración
    'admin.view_costs',
    'admin.manage_budgets',
    'admin.manage_alerts',
];

// Roles sugeridos
$roles = [
    'content_viewer' => ['suppliers.view', 'content.view', 'extraction.view'],
    'content_editor' => ['...viewer', 'content.edit', 'content.approve', 'content.reject'],
    'content_manager' => ['...editor', 'content.generate', 'content.publish', 'prompts.view'],
    'supplier_admin' => ['...manager', 'suppliers.*', 'extraction.*', 'prompts.*'],
    'system_admin' => ['*'], // Todos los permisos
];
```

---

### 27.10 Backup y Recuperación

Estrategia de respaldo para datos críticos.

```php
// config/backup.php (spatie/laravel-backup)
return [
    'backup' => [
        'source' => [
            'databases' => ['pgsql'],
        ],
        'destination' => [
            'disks' => ['s3', 'local'],
        ],
    ],

    // Backups específicos para tablas de automatización
    'automation_tables' => [
        'suppliers',
        'supplier_sources',
        'supplier_prompts',
        'supplier_extraction_mappings',
        'supplier_automation_settings',
        'supplier_automation_workflows',
    ],
];
```

```bash
# Comandos de backup
php artisan backup:run --only-db
php artisan automation:export-config --output=backup/
php artisan automation:import-config --input=backup/
```

---

### 27.11 Métricas y KPIs Dashboard

Métricas clave para el dashboard de administración.

```php
// App\Services\AutomationMetricsService
class AutomationMetricsService
{
    public function getDashboardMetrics(): array
    {
        return [
            'overview' => [
                'active_suppliers' => Supplier::active()->count(),
                'total_products_tracked' => SupplierExtractionResult::distinct('reference')->count(),
                'content_pending_review' => SupplierContent::pending()->count(),
                'workflows_running' => SupplierAutomationExecution::running()->count(),
            ],

            'today' => [
                'extractions_completed' => $this->getTodayExtractions(),
                'new_products' => $this->getTodayNewProducts(),
                'content_generated' => $this->getTodayContentGenerated(),
                'ai_cost_usd' => $this->getTodayAiCost(),
            ],

            'health' => [
                'orchestrator_status' => $this->checkOrchestratorHealth(),
                'queue_size' => Queue::size('extractions'),
                'failed_jobs_24h' => $this->getFailedJobsCount(24),
                'avg_extraction_time' => $this->getAvgExtractionTime(),
            ],

            'trends' => [
                'extractions_7d' => $this->getExtractionTrend(7),
                'content_quality_7d' => $this->getQualityTrend(7),
                'cost_trend_30d' => $this->getCostTrend(30),
            ],
        ];
    }
}
```

---

### 27.12 Resumen de Funcionalidades Avanzadas

| # | Funcionalidad | Prioridad | Complejidad | Tablas Nuevas |
|---|---------------|-----------|-------------|---------------|
| 1 | Validación de Calidad | Alta | Media | 1 |
| 2 | A/B Testing Prompts | Media | Alta | 2 |
| 3 | Tracking de Costos IA | Alta | Baja | 1 |
| 4 | Pipeline de Imágenes | Alta | Media | 1 |
| 5 | Multi-Idioma | Media | Media | 1 |
| 6 | Monitoreo Competencia | Baja | Media | 1 |
| 7 | Notificaciones | Alta | Baja | 1 |
| 8 | Caché/Optimización | Alta | Media | 0 |
| 9 | RBAC/Permisos | Media | Baja | 0 (usa spatie) |
| 10 | Backup/Recovery | Alta | Baja | 0 |
| 11 | Dashboard Métricas | Alta | Media | 0 |

**Tablas adicionales recomendadas: 8**
**Total del sistema: 32 tablas**

---

## 28. Interfaz Administrativa - Diseño Completo

Esta sección define los módulos, pantallas, y funcionalidades necesarias para gestionar el sistema de automatización de contenido desde un panel administrativo.

---

### 28.1 Estructura de Navegación del Módulo

```
📁 Automatización de Contenido
├── 📊 Dashboard
├── 🏭 Proveedores
│   ├── Lista de Proveedores
│   ├── Detalle Proveedor
│   ├── Fuentes de Datos
│   └── Configuración de Mapeos
├── 📝 Prompts IA
│   ├── Biblioteca de Prompts
│   ├── Editor de Prompts
│   ├── Experimentos A/B
│   └── Plantillas
├── 📦 Extracción
│   ├── Monitor de Lotes
│   ├── Resultados por Proveedor
│   ├── Cola de Pendientes
│   └── Productos Detectados
├── ✍️ Contenido
│   ├── Pendiente de Revisión
│   ├── Aprobados
│   ├── Rechazados
│   ├── Generación Manual
│   └── Historial de Cambios
├── 🖼️ Imágenes
│   ├── Cola de Procesamiento
│   ├── Galería por Proveedor
│   └── Imágenes con Errores
├── 🔄 Automatización
│   ├── Workflows Activos
│   ├── Ejecuciones
│   ├── Cola de Reintentos
│   ├── Dead Letter Queue
│   └── Programación
├── 💰 Costos
│   ├── Dashboard de Costos
│   ├── Por Proveedor
│   ├── Por Modelo IA
│   ├── Presupuestos
│   └── Alertas de Límite
├── 📈 Reportes
│   ├── Rendimiento General
│   ├── Calidad de Contenido
│   ├── Productividad
│   └── Exportar Datos
├── ⚙️ Configuración
│   ├── Ajustes Generales
│   ├── Conexiones API
│   ├── Notificaciones
│   ├── Usuarios y Permisos
│   └── Logs del Sistema
└── ❓ Ayuda
    ├── Documentación
    └── Soporte
```

---

### 28.2 Dashboard Principal

#### Wireframe Conceptual

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  AUTOMATIZACIÓN DE CONTENIDO                           👤 Admin ▼  🔔 (3)  │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐ ┌─────────────┐           │
│  │ 🏭 5        │ │ 📦 1,234    │ │ ✍️ 89       │ │ 💰 $45.23   │           │
│  │ Proveedores │ │ Productos   │ │ Pendientes  │ │ Hoy         │           │
│  │ Activos     │ │ Rastreados  │ │ Revisión    │ │ ▲ 12%       │           │
│  └─────────────┘ └─────────────┘ └─────────────┘ └─────────────┘           │
│                                                                             │
│  ┌─────────────────────────────────────┐ ┌─────────────────────────────┐   │
│  │ ACTIVIDAD DE EXTRACCIÓN (7 días)    │ │ ESTADO DEL SISTEMA          │   │
│  │ ┌───────────────────────────────┐   │ │                             │   │
│  │ │  📈 Gráfico de líneas         │   │ │ ● Orquestador    ✅ Online  │   │
│  │ │     - Nuevos (verde)          │   │ │ ● OpenAI API     ✅ Online  │   │
│  │ │     - Actualizados (azul)     │   │ │ ● Cola Redis     ✅ 23 jobs │   │
│  │ │     - Errores (rojo)          │   │ │ ● FTP Nike       ✅ OK      │   │
│  │ └───────────────────────────────┘   │ │ ● FTP Adidas     ⚠️ Lento   │   │
│  │                                     │ │ ● Espacio Disco  ✅ 67%     │   │
│  └─────────────────────────────────────┘ └─────────────────────────────┘   │
│                                                                             │
│  ┌─────────────────────────────────────┐ ┌─────────────────────────────┐   │
│  │ ÚLTIMAS EXTRACCIONES               │ │ CONTENIDO PENDIENTE          │   │
│  │                                     │ │                             │   │
│  │ ● Nike - Hace 2h                    │ │ Nike Air Max 90        [👁] │   │
│  │   ✅ 45 nuevos, 12 actualizados     │ │ Adidas Ultraboost      [👁] │   │
│  │                                     │ │ Puma RS-X              [👁] │   │
│  │ ● Adidas - Hace 5h                  │ │ Nike Pegasus 40        [👁] │   │
│  │   ✅ 23 nuevos, 8 actualizados      │ │ ... y 85 más               │   │
│  │                                     │ │                             │   │
│  │ ● Puma - Hace 1d                    │ │ [Ver todos →]               │   │
│  │   ⚠️ 5 errores detectados           │ │                             │   │
│  └─────────────────────────────────────┘ └─────────────────────────────┘   │
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ ACCIONES RÁPIDAS                                                    │   │
│  │                                                                     │   │
│  │ [+ Nuevo Proveedor]  [▶ Ejecutar Extracción]  [📝 Revisar Contenido]│   │
│  │ [⚙ Configurar Prompt] [📊 Ver Reportes]       [🔄 Reintentar Fallos]│   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

#### Widgets del Dashboard

| Widget | Datos | Actualización |
|--------|-------|---------------|
| KPIs principales | Proveedores, productos, pendientes, costo | Tiempo real |
| Gráfico de actividad | Extracciones 7/30 días | Cada 5 min |
| Estado del sistema | Health checks de servicios | Cada 1 min |
| Últimas extracciones | 5 más recientes con status | Tiempo real |
| Contenido pendiente | Lista con preview | Tiempo real |
| Alertas activas | Warnings y errores críticos | Tiempo real |
| Acciones rápidas | Botones de acceso directo | Estático |

---

### 28.3 Gestión de Proveedores

#### 28.3.1 Lista de Proveedores

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  PROVEEDORES                                    [+ Nuevo Proveedor]         │
├─────────────────────────────────────────────────────────────────────────────┤
│  🔍 Buscar...                    Filtros: [Estado ▼] [Prioridad ▼]          │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌─────┬──────────┬──────────────┬──────────┬───────────┬─────────┬──────┐ │
│  │ ✓   │ Código   │ Proveedor    │ Fuentes  │ Productos │ Estado  │ Acc. │ │
│  ├─────┼──────────┼──────────────┼──────────┼───────────┼─────────┼──────┤ │
│  │ ☑   │ NIKE     │ Nike España  │ 3        │ 1,234     │ 🟢 Act. │ ⋮    │ │
│  │ ☐   │ ADIDAS   │ Adidas Iberia│ 2        │ 856       │ 🟢 Act. │ ⋮    │ │
│  │ ☐   │ PUMA     │ Puma Sports  │ 2        │ 445       │ 🟢 Act. │ ⋮    │ │
│  │ ☐   │ ASICS    │ Asics Europe │ 1        │ 234       │ 🟡 Pausa│ ⋮    │ │
│  │ ☐   │ NEWBAL   │ New Balance  │ 0        │ 0         │ 🔴 Inac.│ ⋮    │ │
│  └─────┴──────────┴──────────────┴──────────┴───────────┴─────────┴──────┘ │
│                                                                             │
│  Mostrando 1-5 de 5                               [◀ Anterior] [Siguiente ▶]│
│                                                                             │
│  Con seleccionados: [▶ Ejecutar Extracción] [⏸ Pausar] [🗑 Eliminar]        │
└─────────────────────────────────────────────────────────────────────────────┘
```

#### 28.3.2 Detalle de Proveedor (Tabs)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  ← Volver │ NIKE - Nike España                           [Editar] [Eliminar]│
├─────────────────────────────────────────────────────────────────────────────┤
│  [Información] [Fuentes] [Mapeos] [Prompts] [Extracciones] [Contenido]      │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  INFORMACIÓN GENERAL                                                        │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ Código:          NIKE                                               │   │
│  │ Nombre:          Nike España                                        │   │
│  │ Website:         https://www.nike.com/es                            │   │
│  │ Contacto:        proveedor@nike.es | +34 900 123 456                │   │
│  │ Estado:          🟢 Activo                                          │   │
│  │ Prioridad:       1 (Alta)                                           │   │
│  │ Creado:          15/01/2024                                         │   │
│  │ Última extracción: Hace 2 horas                                     │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  CONFIGURACIÓN                                                              │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ Idioma por defecto:       Español                                   │   │
│  │ Auto-aprobar contenido:   ☐ No                                      │   │
│  │ Umbral de calidad:        80%                                       │   │
│  │ Max requests paralelos:   5                                         │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  ESTADÍSTICAS                                                               │
│  ┌───────────────┐ ┌───────────────┐ ┌───────────────┐ ┌───────────────┐   │
│  │ 1,234         │ │ 89            │ │ 45            │ │ $234.56       │   │
│  │ Productos     │ │ Pend. Revisión│ │ Nuevos (7d)   │ │ Costo (mes)   │   │
│  └───────────────┘ └───────────────┘ └───────────────┘ └───────────────┘   │
│                                                                             │
│  [▶ Ejecutar Extracción Ahora]  [📝 Generar Contenido Pendiente]           │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

#### 28.3.3 Configuración de Fuentes (Tab Fuentes)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  FUENTES DE DATOS - Nike                                    [+ Nueva Fuente]│
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ 🌐 WEB SCRAPING                                    Prioridad: 1     │   │
│  │ ─────────────────────────────────────────────────────────────────── │   │
│  │ URL Base: https://www.nike.com/es                                   │   │
│  │ Estado: 🟢 Activo | Última ejecución: Hace 2h | ✅ 45 productos     │   │
│  │                                                                     │   │
│  │ Rate Limit: 30 req/min | Delay: 2000ms                              │   │
│  │                                                                     │   │
│  │ [Configurar Mapeo] [Probar Conexión] [Ver Logs] [⋮]                 │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ 📁 FTP EXCEL                                       Prioridad: 2     │   │
│  │ ─────────────────────────────────────────────────────────────────── │   │
│  │ Host: ftp.nike-catalog.com | Puerto: 21                             │   │
│  │ Estado: 🟢 Activo | Última sync: Hace 1d | ✅ 1,200 productos       │   │
│  │                                                                     │   │
│  │ Programación: Diario a las 03:00 (Europe/Madrid)                    │   │
│  │ Patrón archivo: catalog_*.xlsx                                      │   │
│  │                                                                     │   │
│  │ [Configurar Mapeo] [Probar Conexión] [Descargar Ahora] [⋮]          │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ 🔌 API B2B                                         Prioridad: 3     │   │
│  │ ─────────────────────────────────────────────────────────────────── │   │
│  │ URL: https://api.nike.com/b2b/v2                                    │   │
│  │ Estado: 🔴 Inactivo | Pendiente de credenciales                     │   │
│  │                                                                     │   │
│  │ [Configurar Credenciales] [⋮]                                       │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

### 28.4 Editor de Mapeos de Extracción

#### 28.4.1 Mapeo Web (Selectores CSS)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  CONFIGURAR MAPEO - Nike Web Scraping                              [Guardar]│
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌─────────────────────────────────┐ ┌─────────────────────────────────┐   │
│  │ CAMPOS DE EXTRACCIÓN           │ │ VISTA PREVIA                    │   │
│  │                                 │ │                                 │   │
│  │ ┌─────────────────────────────┐ │ │ URL de prueba:                  │   │
│  │ │ Campo: product_name    [✓]  │ │ │ [https://nike.com/es/t/air-max]│   │
│  │ │ Selector: h1#pdp_product_   │ │ │ [🔍 Probar]                     │   │
│  │ │ Tipo: text                  │ │ │                                 │   │
│  │ │ Requerido: ☑                │ │ │ ┌─────────────────────────────┐ │   │
│  │ │ Fallbacks: [+ Añadir]       │ │ │ │                             │ │   │
│  │ │ [Editar] [Eliminar]         │ │ │ │  Resultado extraído:        │ │   │
│  │ └─────────────────────────────┘ │ │ │                             │ │   │
│  │                                 │ │ │  product_name:              │ │   │
│  │ ┌─────────────────────────────┐ │ │ │  "Nike Air Max 90"          │ │   │
│  │ │ Campo: price           [✓]  │ │ │ │                             │ │   │
│  │ │ Selector: [data-test="..."] │ │ │ │  price: "129,99 €"          │ │   │
│  │ │ Tipo: text                  │ │ │ │  → Transformado: 129.99     │ │   │
│  │ │ Transform: extract_price    │ │ │ │                             │ │   │
│  │ │ [Editar] [Eliminar]         │ │ │ │  images: [3 encontradas]    │ │   │
│  │ └─────────────────────────────┘ │ │ │                             │ │   │
│  │                                 │ │ │  ✅ Validación OK           │ │   │
│  │ ┌─────────────────────────────┐ │ │ │                             │ │   │
│  │ │ Campo: images          [✓]  │ │ │ └─────────────────────────────┘ │   │
│  │ │ Selector: .gallery img      │ │ │                                 │   │
│  │ │ Atributo: src               │ │ │                                 │   │
│  │ │ Tipo: array                 │ │ │                                 │   │
│  │ │ [Editar] [Eliminar]         │ │ │                                 │   │
│  │ └─────────────────────────────┘ │ │                                 │   │
│  │                                 │ │                                 │   │
│  │ [+ Añadir Campo]               │ │                                 │   │
│  └─────────────────────────────────┘ └─────────────────────────────────┘   │
│                                                                             │
│  TRANSFORMACIONES                                                           │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ extract_price: Regex /([0-9]+[.,][0-9]{2})/ → Reemplazar coma       │   │
│  │ extract_sku:   Regex /([A-Z]{2}[0-9]{4})/                           │   │
│  │ [+ Nueva Transformación]                                            │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  VALIDACIONES                                                               │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ product_name: required|string|min:5|max:255                         │   │
│  │ price:        required|numeric|min:0                                │   │
│  │ images:       required|array|min:1                                  │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

#### 28.4.2 Mapeo Excel/CSV

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  CONFIGURAR MAPEO - Nike FTP Excel                                 [Guardar]│
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  CONFIGURACIÓN DEL ARCHIVO                                                  │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ Tipo: [Excel ▼]  Hoja: [Products ▼]  Fila cabecera: [1]            │   │
│  │ Fila inicio datos: [2]  Codificación: [UTF-8 ▼]                     │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  MAPEO DE COLUMNAS                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                                                                     │   │
│  │  Campo Destino      │ Columna │ Cabecera      │ Transform │ Req.   │   │
│  │  ─────────────────────────────────────────────────────────────────  │   │
│  │  reference          │ A       │ SKU           │ -         │ ☑      │   │
│  │  ean                │ B       │ EAN/UPC       │ pad_ean   │ ☐      │   │
│  │  product_name       │ C       │ Product Name  │ -         │ ☑      │   │
│  │  short_description  │ D       │ Short Desc    │ -         │ ☐      │   │
│  │  price              │ J       │ RRP EUR       │ to_decimal│ ☑      │   │
│  │  images             │ M       │ Image URLs    │ split_;   │ ☐      │   │
│  │                                                                     │   │
│  │  [+ Añadir Mapeo]                                                   │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  VISTA PREVIA (primeras 5 filas del último archivo)                         │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ A         │ B              │ C                │ J        │ M       │   │
│  │ NKE-001   │ 1234567890123  │ Nike Air Max 90  │ 129,99   │ url1;.. │   │
│  │ NKE-002   │ 1234567890124  │ Nike Pegasus 40  │ 139,99   │ url1;.. │   │
│  │ ...                                                                 │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  [📁 Subir archivo de prueba]  [🔍 Validar mapeo]                          │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

### 28.5 Gestión de Prompts IA

#### 28.5.1 Biblioteca de Prompts

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  BIBLIOTECA DE PROMPTS                                     [+ Nuevo Prompt] │
├─────────────────────────────────────────────────────────────────────────────┤
│  Filtros: [Tipo ▼] [Proveedor ▼] [Estado ▼]            🔍 Buscar...         │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  DESCRIPCIÓN CORTA                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ ★ Descripción corta - Nike               Proveedor: Nike            │   │
│  │   Prioridad: 1 | Modelo: gpt-4o | Temp: 0.7                         │   │
│  │   Uso: 234 generaciones | Calidad media: 87%                        │   │
│  │   Estado: 🟢 Activo                                                 │   │
│  │   [Editar] [Duplicar] [Probar] [Ver estadísticas] [⋮]               │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │   Descripción corta - Estándar           Proveedor: Global          │   │
│  │   Prioridad: 100 | Modelo: gpt-4o-mini | Temp: 0.7                  │   │
│  │   Uso: 567 generaciones | Calidad media: 78%                        │   │
│  │   Estado: 🟢 Activo (Fallback)                                      │   │
│  │   [Editar] [Duplicar] [Probar] [Ver estadísticas] [⋮]               │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  DESCRIPCIÓN LARGA                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ ★ Descripción larga - Nike               Proveedor: Nike            │   │
│  │   ...                                                               │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  SEO / META                                                                 │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │   Meta SEO - Estándar                    Proveedor: Global          │   │
│  │   ...                                                               │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

#### 28.5.2 Editor de Prompt

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  EDITAR PROMPT                                          [Guardar] [Cancelar]│
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  INFORMACIÓN BÁSICA                                                         │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ Nombre:      [Descripción corta - Nike                           ]  │   │
│  │ Tipo:        [short_description ▼]                                  │   │
│  │ Proveedor:   [Nike ▼] (vacío = global)                              │   │
│  │ Categoría:   [Todas ▼] (opcional)                                   │   │
│  │ Prioridad:   [1  ] (menor = mayor prioridad)                        │   │
│  │ Estado:      [● Activo  ○ Borrador  ○ Desactivado]                  │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  CONFIGURACIÓN DEL MODELO                                                   │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ Modelo:      [gpt-4o ▼]                                             │   │
│  │ Temperatura: [0.7    ] ──●────────── (0 = preciso, 1 = creativo)    │   │
│  │ Max tokens:  [100    ]                                              │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  TEXTO DEL PROMPT                                                           │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ Variables disponibles: {product_name} {brand} {category}            │   │
│  │ {original_description} {features} {specifications} {technology}     │   │
│  │ ─────────────────────────────────────────────────────────────────── │   │
│  │                                                                     │   │
│  │ Genera una descripción corta para un producto Nike.                 │   │
│  │                                                                     │   │
│  │ PRODUCTO: {product_name}                                            │   │
│  │ TECNOLOGÍA: {technology}                                            │   │
│  │ INFORMACIÓN: {original_description}                                 │   │
│  │                                                                     │   │
│  │ ESTILO NIKE:                                                        │   │
│  │ - Dinámico y motivador                                              │   │
│  │ - Enfocado en rendimiento                                           │   │
│  │ - Usar vocabulario deportivo                                        │   │
│  │ - Máximo 160 caracteres                                             │   │
│  │                                                                     │   │
│  │ GENERA SOLO EL TEXTO:                                               │   │
│  │                                                                     │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  PROBAR PROMPT                                                              │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ Producto de prueba: [Seleccionar producto existente ▼]              │   │
│  │                     o [Ingresar datos manualmente]                  │   │
│  │                                                                     │   │
│  │ [▶ Ejecutar Prueba]                                                 │   │
│  │                                                                     │   │
│  │ Resultado:                                                          │   │
│  │ ┌─────────────────────────────────────────────────────────────────┐ │   │
│  │ │ "Las Nike Air Max 90 combinan amortiguación Air visible con    │ │   │
│  │ │  un diseño icónico para un confort superior en cada paso."     │ │   │
│  │ │                                                                 │ │   │
│  │ │ Caracteres: 142 ✅ | Tokens usados: 45 | Costo: $0.0023         │ │   │
│  │ └─────────────────────────────────────────────────────────────────┘ │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

### 28.6 Revisión y Aprobación de Contenido

#### 28.6.1 Cola de Revisión

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  CONTENIDO PENDIENTE DE REVISIÓN                               89 elementos │
├─────────────────────────────────────────────────────────────────────────────┤
│  Filtros: [Proveedor ▼] [Calidad ▼] [Fecha ▼]     [☑ Solo baja calidad]    │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ ☐ │ NIKE-AIR-001 │ Nike Air Max 90                                  │   │
│  │   │ Nike         │ Calidad: 92% 🟢 │ Generado: Hace 2h              │   │
│  │   │──────────────────────────────────────────────────────────────── │   │
│  │   │ "Las Nike Air Max 90 combinan amortiguación Air visible..."     │   │
│  │   │                                     [👁 Ver] [✓ Aprobar] [✗ Rech]│   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ ☐ │ ADI-UB-002   │ Adidas Ultraboost 23                             │   │
│  │   │ Adidas       │ Calidad: 67% 🟡 │ Generado: Hace 3h              │   │
│  │   │──────────────────────────────────────────────────────────────── │   │
│  │   │ ⚠️ Advertencias: Descripción muy corta, falta keyword           │   │
│  │   │ "Zapatillas de running con tecnología Boost."                   │   │
│  │   │                                     [👁 Ver] [✏ Editar] [🔄 Regen]│   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ ☐ │ PUM-RSX-003  │ Puma RS-X                                        │   │
│  │   │ Puma         │ Calidad: 45% 🔴 │ Generado: Hace 5h              │   │
│  │   │──────────────────────────────────────────────────────────────── │   │
│  │   │ ❌ Errores: Contiene palabras prohibidas, muy largo             │   │
│  │   │ "Las mejores zapatillas increíbles del mercado..."              │   │
│  │   │                                     [👁 Ver] [✏ Editar] [🗑 Elim]│   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  Con seleccionados: [✓ Aprobar todos] [🔄 Regenerar] [✗ Rechazar]          │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

#### 28.6.2 Detalle de Contenido / Editor

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  ← Volver │ REVISIÓN DE CONTENIDO                    [Aprobar] [Rechazar]   │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  INFORMACIÓN DEL PRODUCTO                                                   │
│  ┌──────────────────────────────────┐ ┌──────────────────────────────────┐ │
│  │ [Imagen del producto]            │ │ Referencia: NIKE-AIR-001         │ │
│  │                                  │ │ EAN: 1234567890123               │ │
│  │                                  │ │ Proveedor: Nike                  │ │
│  │                                  │ │ Categoría: Running               │ │
│  │                                  │ │ Extraído: 15/12/2024 10:30       │ │
│  └──────────────────────────────────┘ └──────────────────────────────────┘ │
│                                                                             │
│  CONTENIDO GENERADO                      │ DATOS ORIGINALES                 │
│  ┌───────────────────────────────────────┼───────────────────────────────┐ │
│  │ Descripción Corta:                    │ Original del proveedor:       │ │
│  │ ┌─────────────────────────────────┐   │ "Nike Air Max 90 - Running   │ │
│  │ │ Las Nike Air Max 90 combinan    │   │  shoes with Air cushioning.  │ │
│  │ │ amortiguación Air visible con   │   │  Available in multiple       │ │
│  │ │ un diseño icónico para un       │   │  colors."                    │ │
│  │ │ confort superior en cada paso.  │   │                               │ │
│  │ └─────────────────────────────────┘   │                               │ │
│  │ Caracteres: 142/160 ✅                │                               │ │
│  │ [✏ Editar]                            │                               │ │
│  │                                       │                               │ │
│  │ Descripción Larga:                    │ Especificaciones:             │ │
│  │ ┌─────────────────────────────────┐   │ - Material: Cuero sintético  │ │
│  │ │ <div class="product-desc">      │   │ - Suela: Goma                │ │
│  │ │   <p>Inspiradas en el running   │   │ - Tecnología: Air            │ │
│  │ │   pero diseñadas para el        │   │ - Peso: 340g                 │ │
│  │ │   street style...</p>           │   │                               │ │
│  │ │   ...                           │   │                               │ │
│  │ └─────────────────────────────────┘   │                               │ │
│  │ Palabras: 245 ✅ | HTML válido ✅     │                               │ │
│  │ [✏ Editar] [👁 Preview HTML]          │                               │ │
│  └───────────────────────────────────────┴───────────────────────────────┘ │
│                                                                             │
│  MÉTRICAS DE CALIDAD                                                        │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ Score General: 92% 🟢                                               │   │
│  │                                                                     │   │
│  │ ✅ Longitud correcta                 ✅ Sin palabras prohibidas     │   │
│  │ ✅ Incluye keywords de marca         ✅ HTML válido                 │   │
│  │ ✅ Legibilidad: 72 (Buena)           ✅ Único (no duplicado)        │   │
│  │ ⚠️ Densidad keywords: 2.1% (límite 3%)                              │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  HISTORIAL                                                                  │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ 15/12 10:35 - Generado automáticamente (prompt: Desc corta Nike)    │   │
│  │ 15/12 10:30 - Producto extraído de Nike Web                         │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  [🔄 Regenerar con otro prompt]  [📤 Publicar a PrestaShop]                │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

### 28.7 Monitor de Automatización

#### 28.7.1 Vista de Ejecuciones

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  MONITOR DE EJECUCIONES                              [🔄 Actualizar]        │
├─────────────────────────────────────────────────────────────────────────────┤
│  Filtros: [Estado ▼] [Workflow ▼] [Proveedor ▼]    Últimas 24 horas ▼      │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  EN EJECUCIÓN (2)                                                           │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ 🔄 exec_abc123 │ Web Scraper - Nike │ Iniciado hace 5 min          │   │
│  │    Progreso: ████████░░░░ 67% (34/50 productos)                     │   │
│  │    [Ver detalles] [⏹ Detener]                                       │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ 🔄 exec_def456 │ FTP Processor - Adidas │ Iniciado hace 2 min       │   │
│  │    Progreso: ██░░░░░░░░░░ 15% (descargando archivo)                 │   │
│  │    [Ver detalles] [⏹ Detener]                                       │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  COMPLETADAS HOY (12)                                                       │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ ✅ exec_ghi789 │ Web Scraper - Nike │ Completado hace 2h            │   │
│  │    Duración: 4m 32s │ 45 nuevos, 12 actualizados, 0 errores        │   │
│  │    [Ver detalles] [Ver resultados]                                  │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ ⚠️ exec_jkl012 │ Web Scraper - Puma │ Completado con warnings       │   │
│  │    Duración: 3m 15s │ 23 nuevos, 5 actualizados, 3 warnings        │   │
│  │    [Ver detalles] [Ver warnings]                                    │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  FALLIDAS (2)                                                               │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ ❌ exec_mno345 │ API - Asics │ Falló hace 6h                        │   │
│  │    Error: HTTP 401 - Unauthorized                                   │   │
│  │    [Ver detalles] [🔄 Reintentar] [🗑 Descartar]                     │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

### 28.8 Dashboard de Costos

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  COSTOS DE IA                                    Período: [Diciembre 2024 ▼]│
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌───────────────┐ ┌───────────────┐ ┌───────────────┐ ┌───────────────┐   │
│  │ $234.56       │ │ $45.23        │ │ $12.34        │ │ 67%           │   │
│  │ Este mes      │ │ Hoy           │ │ Ayer          │ │ del presupuesto│   │
│  │ ▲ 15% vs ant. │ │ ▼ 8% vs ayer  │ │               │ │ mensual       │   │
│  └───────────────┘ └───────────────┘ └───────────────┘ └───────────────┘   │
│                                                                             │
│  DISTRIBUCIÓN POR MODELO                 │ DISTRIBUCIÓN POR PROVEEDOR      │
│  ┌─────────────────────────────────────┐ │ ┌─────────────────────────────┐ │
│  │ [Gráfico circular]                  │ │ │ [Gráfico de barras]         │ │
│  │                                     │ │ │                             │ │
│  │ ● gpt-4o      $156.78 (67%)         │ │ │ Nike     ████████░░ $145    │ │
│  │ ● gpt-4o-mini $67.45 (29%)          │ │ │ Adidas   █████░░░░░ $56     │ │
│  │ ● gpt-4       $10.33 (4%)           │ │ │ Puma     ███░░░░░░░ $28     │ │
│  │                                     │ │ │ Otros    █░░░░░░░░░ $5.56   │ │
│  └─────────────────────────────────────┘ │ └─────────────────────────────┘ │
│                                                                             │
│  TENDENCIA DIARIA (últimos 30 días)                                         │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ $                                                                   │   │
│  │ 50│      ╭─╮                                                       │   │
│  │ 40│   ╭──╯ ╰─╮    ╭─╮                                 ╭──          │   │
│  │ 30│ ──╯      ╰────╯ ╰─────────────────────────────────╯            │   │
│  │ 20│                                                                │   │
│  │   └──────────────────────────────────────────────────────────────── │   │
│  │     1    5    10    15    20    25    30                           │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  PRESUPUESTOS                                                               │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ Diario:   $50  │ Usado: $45.23 │ ██████████████████░░ 90% │ ⚠️     │   │
│  │ Mensual:  $350 │ Usado: $234.56│ █████████████░░░░░░░ 67% │ ✅     │   │
│  │ Por prov: $100 │ Nike: $89.45  │ █████████████████░░░ 89% │ ⚠️     │   │
│  │                                                                     │   │
│  │ [⚙ Configurar presupuestos]  [📧 Configurar alertas]               │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  DETALLE DE TRANSACCIONES                              [📥 Exportar CSV]   │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ Fecha      │ Proveedor │ Modelo     │ Tokens │ Costo   │ Operación │   │
│  │────────────────────────────────────────────────────────────────────│   │
│  │ 15/12 14:23│ Nike      │ gpt-4o     │ 1,234  │ $0.0245 │ generation│   │
│  │ 15/12 14:22│ Nike      │ gpt-4o     │ 1,156  │ $0.0231 │ generation│   │
│  │ 15/12 14:20│ Adidas    │ gpt-4o-mini│ 890    │ $0.0089 │ generation│   │
│  │ ...                                                                 │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

### 28.9 Configuración del Sistema

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  CONFIGURACIÓN DEL SISTEMA                                         [Guardar]│
├─────────────────────────────────────────────────────────────────────────────┤
│  [General] [APIs] [Notificaciones] [Calidad] [Programación] [Avanzado]      │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  CONEXIÓN CON ORQUESTADOR                                                   │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ URL Base:        [http://localhost:5678                          ]  │   │
│  │ API Key:         [••••••••••••••••••••••] [👁]                      │   │
│  │ Webhook Secret:  [••••••••••••••••••••••] [👁] [🔄 Regenerar]       │   │
│  │ Estado:          🟢 Conectado (ping: 45ms)                          │   │
│  │ [🔍 Probar conexión]                                                │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  CONEXIÓN OPENAI                                                            │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ API Key:         [sk-••••••••••••••••••••] [👁]                     │   │
│  │ Modelo default:  [gpt-4o-mini ▼]                                    │   │
│  │ Estado:          🟢 Conectado | Crédito: $234.56                    │   │
│  │ [🔍 Probar conexión]                                                │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  LÍMITES DE EJECUCIÓN                                                       │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ Timeout por defecto:       [300   ] segundos                        │   │
│  │ Máx. reintentos:           [3     ]                                 │   │
│  │ Delay entre reintentos:    [60    ] segundos (exponential backoff)  │   │
│  │ Ejecuciones paralelas:     [5     ]                                 │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  PROXIES (para web scraping)                                                │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ ☑ Habilitar rotación de proxies                                     │   │
│  │                                                                     │   │
│  │ Lista de proxies:                                                   │   │
│  │ ┌─────────────────────────────────────────────────────────────────┐ │   │
│  │ │ http://proxy1.example.com:8080  │ 🟢 OK    │ [🗑]               │ │   │
│  │ │ http://proxy2.example.com:8080  │ 🟢 OK    │ [🗑]               │ │   │
│  │ │ http://proxy3.example.com:8080  │ 🔴 Fail  │ [🗑]               │ │   │
│  │ └─────────────────────────────────────────────────────────────────┘ │   │
│  │ [+ Añadir proxy]  [🔍 Probar todos]                                 │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  PRESUPUESTOS                                                               │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ Límite diario:     [$50    ] USD  │ Acción al alcanzar: [Pausar ▼] │   │
│  │ Límite mensual:    [$1000  ] USD  │ Acción al alcanzar: [Alertar ▼]│   │
│  │ Límite por provee: [$200   ] USD  │ Acción al alcanzar: [Pausar ▼] │   │
│  │                                                                     │   │
│  │ Alertar cuando se alcance el: [80 ]% del presupuesto                │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

### 28.10 Requerimientos Funcionales Detallados

#### RF-01: Gestión de Proveedores

| ID | Requerimiento | Prioridad |
|----|---------------|-----------|
| RF-01.1 | Crear, editar, eliminar proveedores | Alta |
| RF-01.2 | Activar/desactivar proveedores | Alta |
| RF-01.3 | Asignar prioridad a proveedores | Media |
| RF-01.4 | Configurar ajustes por proveedor (idioma, umbral calidad) | Media |
| RF-01.5 | Ver estadísticas del proveedor (productos, costos, calidad) | Alta |
| RF-01.6 | Duplicar configuración de un proveedor | Baja |

#### RF-02: Gestión de Fuentes de Datos

| ID | Requerimiento | Prioridad |
|----|---------------|-----------|
| RF-02.1 | Añadir múltiples fuentes por proveedor | Alta |
| RF-02.2 | Configurar fuente web (URL, selectores, rate limit) | Alta |
| RF-02.3 | Configurar fuente FTP/SFTP (credenciales, ruta, patrón) | Alta |
| RF-02.4 | Configurar fuente API (endpoints, autenticación) | Media |
| RF-02.5 | Configurar fuente de subida manual (formatos, tamaño) | Media |
| RF-02.6 | Probar conexión de fuentes | Alta |
| RF-02.7 | Establecer prioridad entre fuentes | Alta |
| RF-02.8 | Programar ejecución automática por fuente | Alta |

#### RF-03: Mapeo de Extracción

| ID | Requerimiento | Prioridad |
|----|---------------|-----------|
| RF-03.1 | Editor visual de selectores CSS | Alta |
| RF-03.2 | Mapeo de columnas Excel/CSV | Alta |
| RF-03.3 | Definir patrones para PDF | Media |
| RF-03.4 | Configurar transformaciones de datos | Alta |
| RF-03.5 | Definir reglas de validación | Alta |
| RF-03.6 | Probar mapeo con datos reales | Alta |
| RF-03.7 | Importar/exportar configuración de mapeo | Baja |

#### RF-04: Gestión de Prompts

| ID | Requerimiento | Prioridad |
|----|---------------|-----------|
| RF-04.1 | CRUD completo de prompts | Alta |
| RF-04.2 | Editor con resaltado de variables | Alta |
| RF-04.3 | Asignar prompt a proveedor/categoría | Alta |
| RF-04.4 | Configurar modelo y parámetros (temperatura, tokens) | Alta |
| RF-04.5 | Probar prompt con producto de ejemplo | Alta |
| RF-04.6 | Ver estadísticas de uso y calidad del prompt | Media |
| RF-04.7 | Versionar prompts (historial de cambios) | Media |
| RF-04.8 | Duplicar prompts | Baja |
| RF-04.9 | Crear experimentos A/B entre prompts | Baja |

#### RF-05: Extracción de Datos

| ID | Requerimiento | Prioridad |
|----|---------------|-----------|
| RF-05.1 | Ejecutar extracción manual por proveedor/fuente | Alta |
| RF-05.2 | Ver progreso de extracción en tiempo real | Alta |
| RF-05.3 | Detener extracción en curso | Alta |
| RF-05.4 | Ver historial de extracciones (lotes) | Alta |
| RF-05.5 | Ver detalle de lote (nuevos, actualizados, errores) | Alta |
| RF-05.6 | Reintentar extracciones fallidas | Alta |
| RF-05.7 | Descartar/ignorar errores específicos | Media |
| RF-05.8 | Filtrar productos por estado (nuevo, actualizado, sin cambios) | Media |

#### RF-06: Revisión de Contenido

| ID | Requerimiento | Prioridad |
|----|---------------|-----------|
| RF-06.1 | Ver cola de contenido pendiente | Alta |
| RF-06.2 | Filtrar por proveedor, calidad, fecha | Alta |
| RF-06.3 | Aprobar contenido individual o en lote | Alta |
| RF-06.4 | Rechazar contenido con motivo | Alta |
| RF-06.5 | Editar contenido antes de aprobar | Alta |
| RF-06.6 | Regenerar contenido con mismo/otro prompt | Alta |
| RF-06.7 | Ver comparativa original vs generado | Media |
| RF-06.8 | Ver métricas de calidad detalladas | Media |
| RF-06.9 | Vista previa de HTML renderizado | Media |

#### RF-07: Publicación

| ID | Requerimiento | Prioridad |
|----|---------------|-----------|
| RF-07.1 | Publicar contenido aprobado a PrestaShop | Alta |
| RF-07.2 | Publicación individual o en lote | Alta |
| RF-07.3 | Programar publicación | Media |
| RF-07.4 | Ver estado de sincronización | Alta |
| RF-07.5 | Revertir publicación | Baja |

#### RF-08: Monitoreo y Alertas

| ID | Requerimiento | Prioridad |
|----|---------------|-----------|
| RF-08.1 | Dashboard con KPIs principales | Alta |
| RF-08.2 | Monitor de estado del sistema (health checks) | Alta |
| RF-08.3 | Ver ejecuciones en tiempo real | Alta |
| RF-08.4 | Configurar alertas por email/Slack | Media |
| RF-08.5 | Ver logs del sistema | Media |
| RF-08.6 | Exportar logs | Baja |

#### RF-09: Costos

| ID | Requerimiento | Prioridad |
|----|---------------|-----------|
| RF-09.1 | Ver costos totales por período | Alta |
| RF-09.2 | Desglose por proveedor/modelo | Alta |
| RF-09.3 | Gráficos de tendencia | Media |
| RF-09.4 | Configurar presupuestos | Alta |
| RF-09.5 | Alertas de presupuesto | Alta |
| RF-09.6 | Exportar datos de facturación | Media |

#### RF-10: Administración

| ID | Requerimiento | Prioridad |
|----|---------------|-----------|
| RF-10.1 | Configurar conexiones API | Alta |
| RF-10.2 | Gestionar proxies | Media |
| RF-10.3 | Configurar límites de ejecución | Media |
| RF-10.4 | Gestionar usuarios y permisos | Alta |
| RF-10.5 | Backup de configuración | Media |
| RF-10.6 | Restaurar configuración | Baja |

---

### 28.11 Requerimientos No Funcionales

| Categoría | Requerimiento | Métrica |
|-----------|---------------|---------|
| **Rendimiento** | Tiempo de carga de dashboard | < 2 segundos |
| **Rendimiento** | Procesamiento de lote de 1000 productos | < 30 minutos |
| **Rendimiento** | Tiempo de respuesta de API | < 500ms (p95) |
| **Disponibilidad** | Uptime del sistema | 99.5% |
| **Escalabilidad** | Usuarios concurrentes | 50+ |
| **Escalabilidad** | Productos por proveedor | 100,000+ |
| **Seguridad** | Autenticación | SSO / 2FA opcional |
| **Seguridad** | Autorización | RBAC granular |
| **Seguridad** | Encriptación | Credenciales encriptadas |
| **Seguridad** | Auditoría | Log de todas las acciones |
| **Usabilidad** | Mobile responsive | Sí (dashboard básico) |
| **Usabilidad** | Navegadores soportados | Chrome, Firefox, Safari, Edge |
| **Mantenibilidad** | Documentación | API documentada |
| **Mantenibilidad** | Logs estructurados | JSON / ELK compatible |

---

### 28.12 Flujos de Usuario Principales

#### Flujo 1: Configurar Nuevo Proveedor

```
1. Admin accede a Proveedores → + Nuevo Proveedor
2. Completa información básica (código, nombre, contacto)
3. Configura ajustes (idioma, umbral calidad)
4. Guarda proveedor (estado: borrador)
5. Accede a tab "Fuentes" → + Nueva Fuente
6. Selecciona tipo de fuente (Web, FTP, API)
7. Configura conexión y prueba
8. Accede a "Configurar Mapeo"
9. Define selectores/columnas para cada campo
10. Prueba mapeo con URL/archivo de ejemplo
11. Guarda mapeo
12. Accede a tab "Prompts"
13. Asigna prompts existentes o crea nuevos
14. Activa proveedor
15. Ejecuta primera extracción de prueba
16. Verifica resultados
```

#### Flujo 2: Revisar y Aprobar Contenido

```
1. Admin accede a Contenido → Pendiente de Revisión
2. Filtra por proveedor o calidad
3. Selecciona producto
4. Revisa contenido generado vs original
5. Verifica métricas de calidad
6. Opción A: Aprueba directamente
   Opción B: Edita contenido y aprueba
   Opción C: Regenera con otro prompt
   Opción D: Rechaza con motivo
7. Siguiente producto...
8. Aprobación en lote de items de alta calidad
9. Publica lote a PrestaShop
```

#### Flujo 3: Investigar Problema de Extracción

```
1. Admin recibe alerta de extracción fallida
2. Accede a Automatización → Ejecuciones
3. Filtra por estado "Fallido"
4. Abre detalle de ejecución
5. Revisa logs y mensaje de error
6. Identifica causa (ej: selector CSS cambió)
7. Accede a configuración de mapeo del proveedor
8. Actualiza selector
9. Prueba con URL del producto problemático
10. Guarda cambios
11. Reintenta ejecución fallida
12. Verifica éxito
```

---

### 28.13 Resumen de Interfaces

| Módulo | Pantallas | Complejidad |
|--------|-----------|-------------|
| Dashboard | 1 | Alta |
| Proveedores | 4 (lista, detalle, fuentes, mapeos) | Alta |
| Prompts | 3 (biblioteca, editor, experimentos) | Media |
| Extracción | 3 (monitor, lotes, resultados) | Media |
| Contenido | 4 (cola, detalle, aprobados, historial) | Alta |
| Imágenes | 2 (cola, galería) | Baja |
| Automatización | 4 (workflows, ejecuciones, reintentos, DLQ) | Media |
| Costos | 2 (dashboard, detalle) | Media |
| Reportes | 3 (rendimiento, calidad, exportar) | Media |
| Configuración | 5 (general, APIs, notif., calidad, avanzado) | Media |

**Total: ~31 pantallas principales**

---

## 29. Configuración Avanzada de Fuentes de Datos

### 29.1 Tabla: `supplier_source_configurations`

Configuraciones específicas por tipo de fuente con validación de esquema JSON.

```sql
CREATE TABLE supplier_source_configurations (
    id BIGSERIAL PRIMARY KEY,
    uid VARCHAR(26) UNIQUE NOT NULL,
    source_id BIGINT NOT NULL REFERENCES supplier_sources(id) ON DELETE CASCADE,

    -- Tipo de configuración
    config_type VARCHAR(50) NOT NULL, -- 'connection', 'authentication', 'extraction', 'schedule', 'retry', 'proxy', 'validation'

    -- Configuración JSON validada por esquema
    config_data JSONB NOT NULL DEFAULT '{}',
    config_schema_version VARCHAR(10) DEFAULT '1.0',

    -- Validación
    is_valid BOOLEAN DEFAULT true,
    validation_errors JSONB DEFAULT '[]',
    last_validated_at TIMESTAMP WITH TIME ZONE,

    -- Estado
    is_enabled BOOLEAN DEFAULT true,
    priority INTEGER DEFAULT 0, -- Para configuraciones del mismo tipo

    -- Metadata
    created_by BIGINT REFERENCES users(id),
    updated_by BIGINT REFERENCES users(id),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),

    UNIQUE(source_id, config_type)
);

CREATE INDEX idx_source_configs_source ON supplier_source_configurations(source_id);
CREATE INDEX idx_source_configs_type ON supplier_source_configurations(config_type);
CREATE INDEX idx_source_configs_enabled ON supplier_source_configurations(is_enabled) WHERE is_enabled = true;
```

---

### 29.2 Esquemas JSON por Tipo de Configuración

#### 29.2.1 Configuración de Conexión Web (Scraping)

```json
{
    "config_type": "connection",
    "source_type": "web",
    "config_data": {
        "base_url": "https://proveedor.com",
        "catalog_urls": [
            "https://proveedor.com/catalogo/pagina-{page}",
            "https://proveedor.com/novedades"
        ],
        "product_url_pattern": "https://proveedor.com/producto/{sku}",

        "request_headers": {
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
            "Accept": "text/html,application/xhtml+xml",
            "Accept-Language": "es-ES,es;q=0.9",
            "Accept-Encoding": "gzip, deflate, br"
        },

        "cookies": {
            "session_name": "value",
            "consent": "accepted"
        },

        "javascript_required": true,
        "wait_for_selector": ".product-list-loaded",
        "wait_timeout_ms": 10000,

        "viewport": {
            "width": 1920,
            "height": 1080
        },

        "rate_limit": {
            "requests_per_minute": 30,
            "delay_between_requests_ms": 2000,
            "random_delay_variance_ms": 1000
        },

        "pagination": {
            "type": "url_parameter", // 'url_parameter', 'infinite_scroll', 'load_more_button', 'next_link'
            "parameter_name": "page",
            "start_page": 1,
            "max_pages": 100,
            "items_per_page": 48,
            "next_selector": "a.next-page",
            "scroll_delay_ms": 1500
        },

        "anti_bot": {
            "handle_captcha": true,
            "captcha_service": "2captcha",
            "human_behavior_simulation": true,
            "random_mouse_movements": true,
            "vary_timing": true
        }
    }
}
```

#### 29.2.2 Configuración de Conexión FTP/SFTP

```json
{
    "config_type": "connection",
    "source_type": "ftp",
    "config_data": {
        "protocol": "sftp", // 'ftp', 'ftps', 'sftp'
        "host": "ftp.proveedor.com",
        "port": 22,
        "username": "{{ENCRYPTED:credential_ref}}",
        "password": "{{ENCRYPTED:credential_ref}}",

        "authentication": {
            "type": "password", // 'password', 'key', 'key_with_passphrase'
            "private_key_path": "/path/to/key",
            "passphrase": "{{ENCRYPTED:credential_ref}}"
        },

        "connection_settings": {
            "timeout_seconds": 30,
            "passive_mode": true,
            "ssl_verify": true,
            "keep_alive_interval": 60
        },

        "paths": {
            "base_directory": "/exports/catalogo",
            "file_pattern": "productos_*.csv",
            "archive_directory": "/exports/processed",
            "error_directory": "/exports/errors"
        },

        "file_handling": {
            "download_to_temp": true,
            "delete_after_process": false,
            "move_after_process": true,
            "compress_archived": true
        },

        "monitoring": {
            "watch_for_new_files": true,
            "check_interval_minutes": 15,
            "minimum_file_age_seconds": 60
        }
    }
}
```

#### 29.2.3 Configuración de Conexión API

```json
{
    "config_type": "connection",
    "source_type": "api",
    "config_data": {
        "base_url": "https://api.proveedor.com/v2",

        "endpoints": {
            "products_list": {
                "method": "GET",
                "path": "/inventaries",
                "pagination": {
                    "type": "cursor", // 'offset', 'cursor', 'page', 'link_header'
                    "cursor_param": "after",
                    "cursor_response_path": "meta.next_cursor",
                    "limit_param": "limit",
                    "limit_value": 100
                }
            },
            "product_detail": {
                "method": "GET",
                "path": "/inventaries/{id}",
                "path_params": ["id"]
            },
            "product_images": {
                "method": "GET",
                "path": "/inventaries/{id}/images"
            },
            "categories": {
                "method": "GET",
                "path": "/categories"
            }
        },

        "request_defaults": {
            "headers": {
                "Content-Type": "application/json",
                "Accept": "application/json"
            },
            "query_params": {
                "format": "full",
                "include": "images,variants,stock"
            }
        },

        "rate_limit": {
            "requests_per_minute": 100,
            "requests_per_day": 10000,
            "respect_headers": true,
            "retry_after_header": "X-RateLimit-Reset"
        },

        "response_handling": {
            "data_path": "data.inventaries",
            "total_path": "meta.total",
            "error_path": "error.message",
            "success_codes": [200, 201],
            "retry_codes": [429, 503]
        }
    }
}
```

#### 29.2.4 Configuración de Autenticación

```json
{
    "config_type": "authentication",
    "config_data": {
        "type": "oauth2", // 'none', 'basic', 'bearer', 'api_key', 'oauth2', 'session', 'custom'

        "basic": {
            "username": "{{ENCRYPTED:credential_ref}}",
            "password": "{{ENCRYPTED:credential_ref}}"
        },

        "bearer": {
            "token": "{{ENCRYPTED:credential_ref}}",
            "token_prefix": "Bearer"
        },

        "api_key": {
            "key": "{{ENCRYPTED:credential_ref}}",
            "location": "header", // 'header', 'query', 'body'
            "name": "X-API-Key"
        },

        "oauth2": {
            "grant_type": "client_credentials", // 'client_credentials', 'password', 'refresh_token'
            "token_url": "https://api.proveedor.com/oauth/token",
            "client_id": "{{ENCRYPTED:credential_ref}}",
            "client_secret": "{{ENCRYPTED:credential_ref}}",
            "scope": "inventaries:read catalog:read",
            "token_storage": "database", // 'database', 'cache'
            "refresh_before_expiry_seconds": 300
        },

        "session": {
            "login_url": "https://proveedor.com/login",
            "login_method": "POST",
            "login_payload": {
                "email": "{{ENCRYPTED:credential_ref}}",
                "password": "{{ENCRYPTED:credential_ref}}"
            },
            "session_cookie_name": "PHPSESSID",
            "session_valid_check_url": "https://proveedor.com/account",
            "session_invalid_indicator": "login-form"
        },

        "custom": {
            "pre_request_hook": "SupplierAuthHook::generateSignature",
            "signature_header": "X-Signature",
            "timestamp_header": "X-Timestamp"
        },

        "token_refresh": {
            "enabled": true,
            "check_interval_minutes": 5,
            "retry_on_401": true,
            "max_refresh_attempts": 3
        }
    }
}
```

#### 29.2.5 Configuración de Extracción

```json
{
    "config_type": "extraction",
    "config_data": {
        "strategy": "incremental", // 'full', 'incremental', 'delta'

        "product_identification": {
            "unique_key_fields": ["sku", "variant_id"],
            "hash_fields": ["name", "description", "price", "stock"],
            "hash_algorithm": "md5"
        },

        "field_mappings": {
            "sku": {
                "source": "selector",
                "selector": "[data-sku]",
                "attribute": "data-sku",
                "transformations": ["trim", "uppercase"],
                "required": true,
                "validation": {
                    "type": "regex",
                    "pattern": "^[A-Z0-9-]+$"
                }
            },
            "name": {
                "source": "selector",
                "selector": "h1.product-title",
                "type": "text",
                "transformations": ["trim", "decode_html"],
                "max_length": 255
            },
            "price": {
                "source": "selector",
                "selector": ".price-current",
                "type": "text",
                "transformations": [
                    {"type": "regex_extract", "pattern": "[0-9]+[.,][0-9]+"},
                    {"type": "normalize_decimal", "decimal_separator": ","},
                    {"type": "to_float"}
                ],
                "validation": {
                    "type": "range",
                    "min": 0.01,
                    "max": 99999.99
                }
            },
            "images": {
                "source": "selector",
                "selector": ".gallery-images img",
                "type": "array",
                "attribute": "data-zoom-image",
                "fallback_attribute": "src",
                "transformations": [
                    {"type": "url_absolute", "base_from": "page_url"},
                    {"type": "remove_query_params"},
                    {"type": "unique"}
                ],
                "limit": 10
            },
            "categories": {
                "source": "selector",
                "selector": ".breadcrumb li:not(:first-child):not(:last-child)",
                "type": "array",
                "join_with": " > "
            },
            "specifications": {
                "source": "table",
                "selector": "table.specifications",
                "key_column": 0,
                "value_column": 1,
                "as": "object"
            },
            "description": {
                "source": "selector",
                "selector": ".product-description",
                "type": "html",
                "allowed_tags": ["p", "ul", "li", "strong", "em", "br"],
                "remove_selectors": [".ad-banner", "script", "style"]
            },
            "stock_status": {
                "source": "conditional",
                "conditions": [
                    {"selector": ".in-stock", "exists": true, "value": "in_stock"},
                    {"selector": ".low-stock", "exists": true, "value": "low_stock"},
                    {"selector": ".pre-order", "exists": true, "value": "preorder"}
                ],
                "default": "out_of_stock"
            }
        },

        "post_processing": {
            "remove_empty_fields": true,
            "apply_defaults": {
                "currency": "EUR",
                "tax_rate": 21
            },
            "compute_fields": {
                "price_with_tax": "price * (1 + tax_rate / 100)",
                "slug": "slugify(name)"
            }
        },

        "validation_rules": {
            "required_fields": ["sku", "name", "price"],
            "price_sanity_check": {
                "enabled": true,
                "max_change_percentage": 50,
                "reference": "previous_extraction"
            }
        }
    }
}
```

#### 29.2.6 Configuración de Scheduling

```json
{
    "config_type": "schedule",
    "config_data": {
        "enabled": true,

        "schedules": [
            {
                "name": "full_sync_weekly",
                "type": "full",
                "cron": "0 3 * * 0", // Domingos 3:00 AM
                "timezone": "Europe/Madrid",
                "enabled": true
            },
            {
                "name": "incremental_daily",
                "type": "incremental",
                "cron": "0 6,14,22 * * *", // 3 veces al día
                "timezone": "Europe/Madrid",
                "enabled": true,
                "skip_if_previous_running": true
            },
            {
                "name": "stock_update",
                "type": "delta",
                "fields": ["stock", "price"],
                "cron": "*/30 * * * *", // Cada 30 minutos
                "enabled": true,
                "business_hours_only": {
                    "enabled": true,
                    "start": "08:00",
                    "end": "22:00"
                }
            }
        ],

        "blackout_windows": [
            {
                "name": "maintenance",
                "start": "02:00",
                "end": "04:00",
                "days": ["saturday"]
            },
            {
                "name": "high_traffic",
                "start": "18:00",
                "end": "20:00",
                "days": ["monday", "friday"]
            }
        ],

        "dependencies": {
            "wait_for_sources": [],
            "run_after_sources": [],
            "chain_to_workflow": "content_generation"
        },

        "notifications": {
            "on_start": false,
            "on_complete": true,
            "on_failure": true,
            "channels": ["email", "slack"]
        }
    }
}
```

#### 29.2.7 Configuración de Retry

```json
{
    "config_type": "retry",
    "config_data": {
        "global_settings": {
            "max_attempts": 5,
            "initial_delay_seconds": 10,
            "max_delay_seconds": 3600,
            "backoff_multiplier": 2,
            "jitter_enabled": true,
            "jitter_factor": 0.25
        },

        "retry_policies": {
            "connection_timeout": {
                "enabled": true,
                "max_attempts": 10,
                "delay_seconds": 30,
                "escalate_after_attempts": 5
            },
            "rate_limited": {
                "enabled": true,
                "respect_retry_after": true,
                "max_wait_seconds": 900,
                "strategy": "exponential"
            },
            "server_error_5xx": {
                "enabled": true,
                "max_attempts": 3,
                "delay_seconds": 60
            },
            "parse_error": {
                "enabled": false,
                "notify_immediately": true
            },
            "auth_expired": {
                "enabled": true,
                "action": "refresh_token",
                "max_attempts": 2
            }
        },

        "circuit_breaker": {
            "enabled": true,
            "failure_threshold": 5,
            "success_threshold": 2,
            "timeout_seconds": 300,
            "half_open_requests": 3
        },

        "dead_letter_queue": {
            "enabled": true,
            "move_after_attempts": 5,
            "retention_days": 30,
            "auto_retry_interval_hours": 24
        }
    }
}
```

#### 29.2.8 Configuración de Proxy

```json
{
    "config_type": "proxy",
    "config_data": {
        "enabled": true,
        "strategy": "rotating", // 'single', 'rotating', 'geo_targeted', 'residential'

        "single_proxy": {
            "host": "proxy.example.com",
            "port": 8080,
            "username": "{{ENCRYPTED:credential_ref}}",
            "password": "{{ENCRYPTED:credential_ref}}",
            "protocol": "http" // 'http', 'https', 'socks5'
        },

        "rotating_proxies": {
            "provider": "smartproxy", // 'smartproxy', 'brightdata', 'oxylabs', 'custom'
            "api_key": "{{ENCRYPTED:credential_ref}}",
            "country": "ES",
            "session_type": "rotating", // 'rotating', 'sticky'
            "sticky_session_duration_minutes": 10
        },

        "pool_settings": {
            "min_proxies": 5,
            "max_proxies": 50,
            "health_check_interval_seconds": 60,
            "remove_failed_threshold": 3,
            "rotation_strategy": "round_robin" // 'round_robin', 'random', 'least_used'
        },

        "custom_pool": [
            {"host": "proxy1.com", "port": 8080, "country": "ES", "weight": 1},
            {"host": "proxy2.com", "port": 8080, "country": "ES", "weight": 2}
        ],

        "bypass_rules": {
            "local_domains": true,
            "cdn_domains": ["cloudfront.net", "cloudflare.com"],
            "specific_urls": []
        },

        "fallback": {
            "enabled": true,
            "direct_connection_on_failure": false,
            "alternative_provider": "oxylabs"
        }
    }
}
```

#### 29.2.9 Configuración de Validación

```json
{
    "config_type": "validation",
    "config_data": {
        "enabled": true,

        "pre_extraction_checks": {
            "verify_source_accessible": true,
            "check_content_changed": true,
            "validate_page_structure": true,
            "expected_selectors": [".product-list", ".product-item"],
            "abort_if_structure_changed": false,
            "notify_on_structure_change": true
        },

        "field_validation": {
            "sku": {
                "required": true,
                "unique": true,
                "format": "regex",
                "pattern": "^[A-Z0-9-]{5,30}$"
            },
            "name": {
                "required": true,
                "min_length": 10,
                "max_length": 255,
                "no_html": true
            },
            "price": {
                "required": true,
                "type": "number",
                "min": 0.01,
                "max": 99999.99
            },
            "images": {
                "min_count": 1,
                "max_count": 20,
                "each": {
                    "format": "url",
                    "reachable": true
                }
            }
        },

        "cross_field_validation": [
            {
                "rule": "sale_price < price",
                "message": "Sale price must be less than regular price"
            },
            {
                "rule": "stock >= 0 OR stock_status == 'preorder'",
                "message": "Stock must be non-negative unless preorder"
            }
        ],

        "batch_validation": {
            "min_products": 1,
            "max_products": 50000,
            "duplicate_check": {
                "enabled": true,
                "fields": ["sku"],
                "action": "keep_first" // 'keep_first', 'keep_last', 'reject'
            },
            "anomaly_detection": {
                "enabled": true,
                "price_deviation_threshold": 3.0,
                "name_similarity_threshold": 0.95,
                "flag_suspicious": true
            }
        },

        "post_extraction_validation": {
            "compare_with_previous": {
                "enabled": true,
                "max_products_removed_percentage": 20,
                "max_price_change_percentage": 50,
                "alert_on_anomalies": true
            },
            "data_quality_score": {
                "enabled": true,
                "minimum_score": 70,
                "scoring_rules": {
                    "has_description": 20,
                    "has_images": 25,
                    "has_specifications": 15,
                    "has_category": 10,
                    "complete_data": 30
                }
            }
        },

        "actions_on_failure": {
            "invalid_field": "set_null", // 'reject', 'set_null', 'set_default'
            "invalid_product": "quarantine", // 'skip', 'quarantine', 'stop_batch'
            "batch_below_threshold": "pause_and_notify"
        }
    }
}
```

---

### 29.3 Tabla: `supplier_source_templates`

Templates reutilizables de configuración de fuentes.

```sql
CREATE TABLE supplier_source_templates (
    id BIGSERIAL PRIMARY KEY,
    uid VARCHAR(26) UNIQUE NOT NULL,

    name VARCHAR(100) NOT NULL,
    description TEXT,
    source_type VARCHAR(50) NOT NULL,

    -- Configuraciones template (sin credenciales)
    connection_template JSONB NOT NULL DEFAULT '{}',
    extraction_template JSONB DEFAULT '{}',
    schedule_template JSONB DEFAULT '{}',
    retry_template JSONB DEFAULT '{}',
    validation_template JSONB DEFAULT '{}',

    -- Variables requeridas
    required_variables JSONB DEFAULT '[]',
    -- Ej: ["base_url", "username", "password", "catalog_path"]

    -- Categorización
    category VARCHAR(50), -- 'ecommerce', 'manufacturer', 'distributor', 'marketplace'
    tags JSONB DEFAULT '[]',

    -- Uso
    usage_count INTEGER DEFAULT 0,
    is_public BOOLEAN DEFAULT false, -- Compartir entre organizaciones

    -- Metadata
    created_by BIGINT REFERENCES users(id),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_source_templates_type ON supplier_source_templates(source_type);
CREATE INDEX idx_source_templates_category ON supplier_source_templates(category);
```

#### Ejemplos de Templates

```json
// Template: Tienda Shopify
{
    "name": "Shopify Store",
    "source_type": "api",
    "connection_template": {
        "base_url": "https://{{store_name}}.myshopify.com/admin/api/2024-01",
        "authentication": {
            "type": "bearer",
            "token": "{{api_token}}"
        },
        "endpoints": {
            "products_list": {
                "method": "GET",
                "path": "/inventaries.json",
                "pagination": {
                    "type": "link_header",
                    "limit_param": "limit",
                    "limit_value": 250
                }
            }
        }
    },
    "extraction_template": {
        "field_mappings": {
            "sku": {"path": "variants[0].sku"},
            "name": {"path": "title"},
            "description": {"path": "body_html"},
            "price": {"path": "variants[0].price"},
            "images": {"path": "images[*].src"}
        }
    },
    "required_variables": ["store_name", "api_token"]
}
```

```json
// Template: Catálogo FTP CSV
{
    "name": "FTP CSV Catalog",
    "source_type": "ftp",
    "connection_template": {
        "protocol": "{{protocol}}",
        "host": "{{host}}",
        "port": "{{port}}",
        "paths": {
            "base_directory": "{{catalog_path}}",
            "file_pattern": "*.csv"
        }
    },
    "extraction_template": {
        "file_format": "csv",
        "csv_options": {
            "delimiter": "{{delimiter}}",
            "has_header": true,
            "encoding": "{{encoding}}"
        }
    },
    "required_variables": ["protocol", "host", "port", "username", "password", "catalog_path", "delimiter", "encoding"]
}
```

---

### 29.4 Tabla: `supplier_source_monitors`

Monitoreo continuo del estado de las fuentes.

```sql
CREATE TABLE supplier_source_monitors (
    id BIGSERIAL PRIMARY KEY,
    uid VARCHAR(26) UNIQUE NOT NULL,
    source_id BIGINT NOT NULL REFERENCES supplier_sources(id) ON DELETE CASCADE,

    -- Estado actual
    status VARCHAR(30) NOT NULL DEFAULT 'unknown',
    -- 'healthy', 'degraded', 'unhealthy', 'unreachable', 'unknown'

    status_message TEXT,
    status_code INTEGER,

    -- Métricas de salud
    uptime_percentage DECIMAL(5,2) DEFAULT 100.00,
    avg_response_time_ms INTEGER,
    last_successful_check_at TIMESTAMP WITH TIME ZONE,
    last_failed_check_at TIMESTAMP WITH TIME ZONE,
    consecutive_failures INTEGER DEFAULT 0,
    consecutive_successes INTEGER DEFAULT 0,

    -- Detección de cambios
    structure_hash VARCHAR(64),
    structure_changed_at TIMESTAMP WITH TIME ZONE,
    content_hash VARCHAR(64),
    content_changed_at TIMESTAMP WITH TIME ZONE,

    -- Configuración de monitoreo
    check_interval_minutes INTEGER DEFAULT 15,
    health_check_url VARCHAR(2048),
    expected_content_selector VARCHAR(255),

    -- Alertas
    alert_on_failure BOOLEAN DEFAULT true,
    alert_on_structure_change BOOLEAN DEFAULT true,
    alert_sent_at TIMESTAMP WITH TIME ZONE,
    snooze_alerts_until TIMESTAMP WITH TIME ZONE,

    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_source_monitors_source ON supplier_source_monitors(source_id);
CREATE INDEX idx_source_monitors_status ON supplier_source_monitors(status);
```

---

### 29.5 Tabla: `supplier_source_health_history`

Historial de checks de salud para análisis de tendencias.

```sql
CREATE TABLE supplier_source_health_history (
    id BIGSERIAL PRIMARY KEY,
    source_id BIGINT NOT NULL REFERENCES supplier_sources(id) ON DELETE CASCADE,

    check_type VARCHAR(30) NOT NULL, -- 'connectivity', 'authentication', 'structure', 'content'

    is_success BOOLEAN NOT NULL,
    status_code INTEGER,
    response_time_ms INTEGER,

    error_type VARCHAR(100),
    error_message TEXT,

    -- Snapshot de datos
    page_size_bytes INTEGER,
    products_found INTEGER,

    checked_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_health_history_source_time ON supplier_source_health_history(source_id, checked_at DESC);

-- Particionar por mes para mejor rendimiento
-- CREATE TABLE supplier_source_health_history_2025_01 PARTITION OF supplier_source_health_history
--     FOR VALUES FROM ('2025-01-01') TO ('2025-02-01');
```

---

### 29.6 Tabla: `supplier_source_transformations`

Transformaciones de datos personalizadas por fuente.

```sql
CREATE TABLE supplier_source_transformations (
    id BIGSERIAL PRIMARY KEY,
    uid VARCHAR(26) UNIQUE NOT NULL,
    source_id BIGINT NOT NULL REFERENCES supplier_sources(id) ON DELETE CASCADE,

    name VARCHAR(100) NOT NULL,
    description TEXT,

    -- Cuándo aplicar
    field_name VARCHAR(100), -- NULL = aplicar a todo el registro
    apply_order INTEGER DEFAULT 0,

    -- Tipo de transformación
    transformation_type VARCHAR(50) NOT NULL,
    -- 'regex_replace', 'regex_extract', 'mapping', 'formula', 'lookup',
    -- 'split', 'join', 'format', 'custom_function'

    -- Configuración
    transformation_config JSONB NOT NULL,

    -- Condiciones
    apply_condition JSONB, -- Condición JSON para aplicar la transformación

    is_enabled BOOLEAN DEFAULT true,

    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_transformations_source ON supplier_source_transformations(source_id);
```

#### Ejemplos de Transformaciones

```json
// Normalizar precios con formato europeo
{
    "name": "Normalize European Price",
    "field_name": "price",
    "transformation_type": "regex_replace",
    "transformation_config": {
        "patterns": [
            {"find": "€", "replace": ""},
            {"find": "\\s+", "replace": ""},
            {"find": "\\.", "replace": ""},
            {"find": ",", "replace": "."}
        ],
        "cast_to": "float"
    }
}
```

```json
// Mapear categorías del proveedor a categorías internas
{
    "name": "Category Mapping",
    "field_name": "category",
    "transformation_type": "mapping",
    "transformation_config": {
        "mapping": {
            "Calzado Deportivo": "Zapatillas > Running",
            "Ropa de Running": "Ropa > Running > Hombre",
            "Accesorios": "Complementos > Accesorios"
        },
        "default": "Sin Categoría",
        "case_insensitive": true
    }
}
```

```json
// Generar slug desde nombre
{
    "name": "Generate Slug",
    "field_name": "slug",
    "transformation_type": "formula",
    "transformation_config": {
        "source_fields": ["name", "sku"],
        "formula": "slugify(lower(name)) + '-' + lower(sku)",
        "functions": {
            "slugify": true,
            "lower": true
        }
    }
}
```

```json
// Lookup de marca desde tabla de referencia
{
    "name": "Brand Lookup",
    "field_name": "brand_id",
    "transformation_type": "lookup",
    "transformation_config": {
        "source_field": "brand_name",
        "lookup_table": "suppliers",
        "lookup_column": "name",
        "return_column": "id",
        "match_type": "fuzzy",
        "fuzzy_threshold": 0.85,
        "create_if_not_found": false,
        "default": null
    }
}
```

---

### 29.7 Tabla: `supplier_source_webhooks`

Webhooks entrantes para notificaciones push de proveedores.

```sql
CREATE TABLE supplier_source_webhooks (
    id BIGSERIAL PRIMARY KEY,
    uid VARCHAR(26) UNIQUE NOT NULL,
    source_id BIGINT NOT NULL REFERENCES supplier_sources(id) ON DELETE CASCADE,

    name VARCHAR(100) NOT NULL,
    description TEXT,

    -- Endpoint
    endpoint_path VARCHAR(255) NOT NULL, -- Ej: /webhooks/supplier/nike/products
    secret_key VARCHAR(255), -- Para validar firma

    -- Eventos soportados
    events JSONB DEFAULT '["product.created", "product.updated", "product.deleted"]',

    -- Mapeo de payload
    payload_mapping JSONB NOT NULL,
    -- {
    --   "product_id": "$.data.id",
    --   "event_type": "$.event",
    --   "timestamp": "$.created_at"
    -- }

    -- Procesamiento
    processing_mode VARCHAR(30) DEFAULT 'async', -- 'sync', 'async', 'batch'
    batch_size INTEGER DEFAULT 100,
    batch_window_seconds INTEGER DEFAULT 60,

    -- Autenticación esperada
    auth_type VARCHAR(30), -- 'signature', 'bearer', 'basic', 'none'
    auth_config JSONB,

    -- Estado
    is_enabled BOOLEAN DEFAULT true,
    last_received_at TIMESTAMP WITH TIME ZONE,
    total_received INTEGER DEFAULT 0,
    total_processed INTEGER DEFAULT 0,
    total_failed INTEGER DEFAULT 0,

    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),

    UNIQUE(source_id, endpoint_path)
);

CREATE INDEX idx_webhooks_source ON supplier_source_webhooks(source_id);
CREATE INDEX idx_webhooks_path ON supplier_source_webhooks(endpoint_path);
```

---

### 29.8 Tabla: `supplier_source_files`

Gestión de archivos subidos manualmente o descargados.

```sql
CREATE TABLE supplier_source_files (
    id BIGSERIAL PRIMARY KEY,
    uid VARCHAR(26) UNIQUE NOT NULL,
    source_id BIGINT NOT NULL REFERENCES supplier_sources(id) ON DELETE CASCADE,
    batch_id BIGINT REFERENCES supplier_extraction_batches(id),

    -- Archivo
    original_filename VARCHAR(255) NOT NULL,
    stored_path VARCHAR(500) NOT NULL,
    file_size_bytes BIGINT NOT NULL,
    mime_type VARCHAR(100),

    -- Hash para detectar duplicados
    file_hash VARCHAR(64) NOT NULL,

    -- Procesamiento
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    -- 'pending', 'processing', 'processed', 'failed', 'archived'

    rows_total INTEGER,
    rows_processed INTEGER DEFAULT 0,
    rows_success INTEGER DEFAULT 0,
    rows_failed INTEGER DEFAULT 0,

    processing_started_at TIMESTAMP WITH TIME ZONE,
    processing_completed_at TIMESTAMP WITH TIME ZONE,
    processing_error TEXT,

    -- Origen
    upload_type VARCHAR(30) NOT NULL, -- 'manual', 'ftp', 'email', 'api'
    uploaded_by BIGINT REFERENCES users(id),

    -- Retención
    delete_after_processed BOOLEAN DEFAULT false,
    archived_at TIMESTAMP WITH TIME ZONE,

    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_source_files_source ON supplier_source_files(source_id);
CREATE INDEX idx_source_files_status ON supplier_source_files(status);
CREATE INDEX idx_source_files_hash ON supplier_source_files(file_hash);
```

---

### 29.9 Mejoras de Integración con Orquestador

#### 29.9.1 Tabla: `supplier_automation_triggers`

Triggers automáticos para ejecutar workflows.

```sql
CREATE TABLE supplier_automation_triggers (
    id BIGSERIAL PRIMARY KEY,
    uid VARCHAR(26) UNIQUE NOT NULL,

    name VARCHAR(100) NOT NULL,
    description TEXT,

    -- Tipo de trigger
    trigger_type VARCHAR(50) NOT NULL,
    -- 'schedule', 'webhook', 'file_upload', 'api_call', 'source_change', 'manual', 'dependent'

    -- Configuración del trigger
    trigger_config JSONB NOT NULL,

    -- Workflow a ejecutar
    workflow_id BIGINT REFERENCES supplier_automation_workflows(id),
    workflow_version VARCHAR(20), -- NULL = latest

    -- Contexto de ejecución
    execution_context JSONB DEFAULT '{}',
    -- Variables que se pasan al workflow

    -- Filtros
    source_filter JSONB, -- Filtrar por fuentes específicas
    supplier_filter JSONB, -- Filtrar por proveedores

    -- Control de ejecución
    max_concurrent_executions INTEGER DEFAULT 1,
    queue_excess BOOLEAN DEFAULT true,
    debounce_seconds INTEGER DEFAULT 0,

    -- Estado
    is_enabled BOOLEAN DEFAULT true,
    last_triggered_at TIMESTAMP WITH TIME ZONE,
    total_triggers INTEGER DEFAULT 0,

    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_triggers_type ON supplier_automation_triggers(trigger_type);
CREATE INDEX idx_triggers_workflow ON supplier_automation_triggers(workflow_id);
```

#### Ejemplos de Triggers

```json
// Trigger por archivo nuevo en FTP
{
    "name": "FTP File Upload Trigger",
    "trigger_type": "file_upload",
    "trigger_config": {
        "watch_paths": ["/exports/new/*"],
        "file_patterns": ["*.csv", "*.xlsx"],
        "polling_interval_seconds": 300,
        "minimum_file_age_seconds": 60,
        "checksum_validation": true
    },
    "execution_context": {
        "extraction_type": "full",
        "priority": "normal"
    }
}
```

```json
// Trigger por cambio detectado en fuente
{
    "name": "Source Change Trigger",
    "trigger_type": "source_change",
    "trigger_config": {
        "change_types": ["content_hash", "structure"],
        "minimum_change_threshold": 5,
        "cooldown_minutes": 60
    },
    "source_filter": {
        "types": ["web"],
        "suppliers": ["NIKE", "ADIDAS"]
    }
}
```

```json
// Trigger dependiente
{
    "name": "Post-Extraction Content Generation",
    "trigger_type": "dependent",
    "trigger_config": {
        "depends_on_workflow": "extraction_workflow",
        "depends_on_status": "completed",
        "minimum_products_extracted": 1,
        "delay_seconds": 30
    },
    "workflow_id": 2,
    "execution_context": {
        "inherit_batch_id": true,
        "filter_new_products_only": true
    }
}
```

---

#### 29.9.2 Tabla: `supplier_automation_variables`

Variables globales y por contexto para workflows.

```sql
CREATE TABLE supplier_automation_variables (
    id BIGSERIAL PRIMARY KEY,
    uid VARCHAR(26) UNIQUE NOT NULL,

    name VARCHAR(100) NOT NULL,
    description TEXT,

    -- Ámbito
    scope VARCHAR(30) NOT NULL DEFAULT 'global',
    -- 'global', 'supplier', 'source', 'workflow'
    scope_id BIGINT, -- ID del elemento al que pertenece (NULL para global)

    -- Valor
    variable_type VARCHAR(30) NOT NULL,
    -- 'string', 'number', 'boolean', 'json', 'secret', 'expression'
    value TEXT,
    encrypted_value BYTEA, -- Para secrets

    -- Validación
    validation_regex VARCHAR(255),
    allowed_values JSONB,

    -- Comportamiento
    is_required BOOLEAN DEFAULT false,
    default_value TEXT,

    -- Protección
    is_system BOOLEAN DEFAULT false, -- No editable por usuarios
    is_sensitive BOOLEAN DEFAULT false, -- Ocultar en logs

    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),

    UNIQUE(scope, scope_id, name)
);

CREATE INDEX idx_variables_scope ON supplier_automation_variables(scope, scope_id);
```

#### Variables Predefinidas del Sistema

```json
[
    {
        "name": "ORCHESTRATOR_WEBHOOK_URL",
        "scope": "global",
        "variable_type": "string",
        "is_system": true,
        "value": "https://n8n.internal/webhook"
    },
    {
        "name": "ORCHESTRATOR_WEBHOOK_SECRET",
        "scope": "global",
        "variable_type": "secret",
        "is_system": true,
        "is_sensitive": true
    },
    {
        "name": "DEFAULT_RATE_LIMIT",
        "scope": "global",
        "variable_type": "number",
        "default_value": "30"
    },
    {
        "name": "AI_MODEL_DEFAULT",
        "scope": "global",
        "variable_type": "string",
        "default_value": "gpt-4o",
        "allowed_values": ["gpt-4o", "gpt-4o-mini", "claude-3-opus", "claude-3-sonnet"]
    }
]
```

---

#### 29.9.3 Tabla: `supplier_automation_chains`

Encadenamiento de workflows para pipelines complejos.

```sql
CREATE TABLE supplier_automation_chains (
    id BIGSERIAL PRIMARY KEY,
    uid VARCHAR(26) UNIQUE NOT NULL,

    name VARCHAR(100) NOT NULL,
    description TEXT,

    -- Definición del pipeline
    chain_definition JSONB NOT NULL,
    -- Estructura de stages y condiciones

    -- Configuración
    fail_strategy VARCHAR(30) DEFAULT 'stop_chain',
    -- 'stop_chain', 'skip_stage', 'continue', 'rollback'

    parallel_stages BOOLEAN DEFAULT false,
    max_parallel INTEGER DEFAULT 3,

    timeout_minutes INTEGER DEFAULT 180,

    is_enabled BOOLEAN DEFAULT true,

    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);
```

#### Ejemplo de Chain

```json
{
    "name": "Complete Product Pipeline",
    "chain_definition": {
        "stages": [
            {
                "id": "extract",
                "name": "Extract Products",
                "workflow_id": 1,
                "order": 1,
                "timeout_minutes": 60,
                "retry": {"max_attempts": 2, "delay_seconds": 300}
            },
            {
                "id": "validate",
                "name": "Validate Data",
                "workflow_id": 5,
                "order": 2,
                "depends_on": ["extract"],
                "condition": "stages.extract.products_count > 0"
            },
            {
                "id": "generate_content",
                "name": "Generate AI Content",
                "workflow_id": 2,
                "order": 3,
                "depends_on": ["validate"],
                "parallel_with": ["process_images"],
                "condition": "stages.validate.valid_count > 0"
            },
            {
                "id": "process_images",
                "name": "Process Images",
                "workflow_id": 3,
                "order": 3,
                "depends_on": ["validate"],
                "parallel_with": ["generate_content"]
            },
            {
                "id": "quality_check",
                "name": "Quality Check",
                "workflow_id": 6,
                "order": 4,
                "depends_on": ["generate_content", "process_images"]
            },
            {
                "id": "publish",
                "name": "Publish to PrestaShop",
                "workflow_id": 4,
                "order": 5,
                "depends_on": ["quality_check"],
                "condition": "stages.quality_check.approved_count > 0",
                "requires_approval": true
            }
        ],
        "error_handlers": {
            "on_stage_failure": {
                "notify": ["admin@alsernet.com"],
                "retry_config": {"enabled": true, "max_attempts": 2}
            },
            "on_chain_failure": {
                "rollback_to_stage": null,
                "create_incident": true
            }
        },
        "completion_handlers": {
            "on_success": {
                "notify": ["operations@alsernet.com"],
                "report": "daily_summary"
            }
        }
    }
}
```

---

#### 29.9.4 Tabla: `supplier_automation_chain_executions`

Ejecuciones de pipelines.

```sql
CREATE TABLE supplier_automation_chain_executions (
    id BIGSERIAL PRIMARY KEY,
    uid VARCHAR(26) UNIQUE NOT NULL,
    chain_id BIGINT NOT NULL REFERENCES supplier_automation_chains(id),

    -- Estado global
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    -- 'pending', 'running', 'paused', 'waiting_approval', 'completed', 'failed', 'cancelled'

    -- Progreso
    current_stage VARCHAR(100),
    completed_stages JSONB DEFAULT '[]',
    failed_stages JSONB DEFAULT '[]',

    -- Contexto acumulado
    execution_context JSONB DEFAULT '{}',
    stage_results JSONB DEFAULT '{}',

    -- Tiempos
    started_at TIMESTAMP WITH TIME ZONE,
    completed_at TIMESTAMP WITH TIME ZONE,

    -- Aprobación (si requiere)
    pending_approval_stage VARCHAR(100),
    approval_requested_at TIMESTAMP WITH TIME ZONE,
    approved_by BIGINT REFERENCES users(id),
    approved_at TIMESTAMP WITH TIME ZONE,

    -- Origen
    triggered_by VARCHAR(50), -- 'manual', 'schedule', 'api', 'trigger'
    triggered_by_user BIGINT REFERENCES users(id),

    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_chain_executions_chain ON supplier_automation_chain_executions(chain_id);
CREATE INDEX idx_chain_executions_status ON supplier_automation_chain_executions(status);
```

---

### 29.10 Diagrama de Configuración Completa

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                        SUPPLIER SOURCE CONFIGURATION                             │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─────────────────────┐                                                         │
│  │   supplier_sources   │◄────────────────────────────────────────┐              │
│  └──────────┬──────────┘                                          │              │
│             │                                                      │              │
│             │ 1:N                                                  │              │
│             ▼                                                      │              │
│  ┌─────────────────────────────┐                                  │              │
│  │ supplier_source_configurations│                                 │              │
│  ├─────────────────────────────┤                                  │              │
│  │ - connection (web/ftp/api)  │                                  │              │
│  │ - authentication            │                                  │              │
│  │ - extraction                │                                  │              │
│  │ - schedule                  │                                  │              │
│  │ - retry                     │                                  │              │
│  │ - proxy                     │                                  │              │
│  │ - validation                │                                  │              │
│  └─────────────────────────────┘                                  │              │
│             │                                                      │              │
│             │ supports                                             │              │
│             ▼                                                      │              │
│  ┌─────────────────────────────┐      ┌───────────────────────┐  │              │
│  │ supplier_source_templates    │◄─────│ Create from Template   │  │              │
│  └─────────────────────────────┘      └───────────────────────┘  │              │
│                                                                    │              │
│  ┌─────────────────────────────┐                                  │              │
│  │ supplier_source_transformations│◄───── Field-level transforms  │              │
│  └─────────────────────────────┘                                  │              │
│                                                                    │              │
│  ┌─────────────────────────────┐      ┌───────────────────────┐  │              │
│  │ supplier_source_monitors     │◄────│ Continuous health check│  │              │
│  └──────────┬──────────────────┘      └───────────────────────┘  │              │
│             │                                                      │              │
│             ▼ stores                                               │              │
│  ┌─────────────────────────────┐                                  │              │
│  │ supplier_source_health_history│                                │              │
│  └─────────────────────────────┘                                  │              │
│                                                                    │              │
│  ┌─────────────────────────────┐                                  │              │
│  │ supplier_source_webhooks     │◄───── Push notifications ───────┤              │
│  └─────────────────────────────┘                                  │              │
│                                                                    │              │
│  ┌─────────────────────────────┐                                  │              │
│  │ supplier_source_files        │◄───── File management ──────────┘              │
│  └─────────────────────────────┘                                                 │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────────┐
│                        AUTOMATION ENHANCEMENTS                                   │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─────────────────────────────┐                                                 │
│  │ supplier_automation_triggers │                                                │
│  ├─────────────────────────────┤                                                 │
│  │ Types:                       │                                                │
│  │ - schedule (cron)            │                                                │
│  │ - webhook (incoming)         │                                                │
│  │ - file_upload                │◄────── Watch for new files                    │
│  │ - source_change              │◄────── Content/structure changed              │
│  │ - api_call                   │                                                │
│  │ - dependent                  │◄────── After another workflow                 │
│  └──────────┬──────────────────┘                                                 │
│             │ triggers                                                            │
│             ▼                                                                     │
│  ┌─────────────────────────────┐                                                 │
│  │ supplier_automation_workflows│                                                │
│  └──────────┬──────────────────┘                                                 │
│             │ part of                                                             │
│             ▼                                                                     │
│  ┌─────────────────────────────┐                                                 │
│  │ supplier_automation_chains   │◄────── Multi-stage pipelines                   │
│  ├─────────────────────────────┤                                                 │
│  │ Stages:                      │                                                │
│  │ 1. Extract                   │                                                │
│  │ 2. Validate                  │                                                │
│  │ 3. Generate Content          │◄─┐                                             │
│  │ 3. Process Images (parallel) │◄─┘ Run in parallel                            │
│  │ 4. Quality Check             │                                                │
│  │ 5. Publish                   │◄────── Requires approval                       │
│  └──────────┬──────────────────┘                                                 │
│             │ tracked in                                                          │
│             ▼                                                                     │
│  ┌─────────────────────────────┐                                                 │
│  │ supplier_automation_chain_executions│                                         │
│  └─────────────────────────────┘                                                 │
│                                                                                   │
│  ┌─────────────────────────────┐                                                 │
│  │ supplier_automation_variables│◄────── Global/scoped variables                │
│  └─────────────────────────────┘                                                 │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────┘
```

---

### 29.11 Modelos Laravel Adicionales

#### SupplierSourceConfiguration

```php
<?php

namespace App\Models\Supplier;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierSourceConfiguration extends Model
{
    protected $fillable = [
        'source_id',
        'config_type',
        'config_data',
        'config_schema_version',
        'is_valid',
        'validation_errors',
        'is_enabled',
        'priority',
    ];

    protected $casts = [
        'config_data' => 'array',
        'validation_errors' => 'array',
        'is_valid' => 'boolean',
        'is_enabled' => 'boolean',
        'last_validated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uid = (string) \Ulid\Ulid::generate();
        });

        static::saving(function ($model) {
            $model->validateSchema();
        });
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(SupplierSource::class, 'source_id');
    }

    public function validateSchema(): void
    {
        $validator = new SourceConfigValidator();
        $result = $validator->validate($this->config_type, $this->config_data);

        $this->is_valid = $result->isValid();
        $this->validation_errors = $result->getErrors();
        $this->last_validated_at = now();
    }

    public function getConfigValue(string $path, $default = null)
    {
        return data_get($this->config_data, $path, $default);
    }

    public function mergeConfig(array $newConfig): void
    {
        $this->config_data = array_merge_recursive($this->config_data, $newConfig);
    }

    // Scopes
    public function scopeOfType($query, string $type)
    {
        return $query->where('config_type', $type);
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeValid($query)
    {
        return $query->where('is_valid', true);
    }
}
```

#### SupplierAutomationTrigger

```php
<?php

namespace App\Models\Supplier;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierAutomationTrigger extends Model
{
    protected $fillable = [
        'name',
        'description',
        'trigger_type',
        'trigger_config',
        'workflow_id',
        'workflow_version',
        'execution_context',
        'source_filter',
        'supplier_filter',
        'max_concurrent_executions',
        'queue_excess',
        'debounce_seconds',
        'is_enabled',
    ];

    protected $casts = [
        'trigger_config' => 'array',
        'execution_context' => 'array',
        'source_filter' => 'array',
        'supplier_filter' => 'array',
        'is_enabled' => 'boolean',
        'queue_excess' => 'boolean',
        'last_triggered_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uid = (string) \Ulid\Ulid::generate();
        });
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(SupplierAutomationWorkflow::class, 'workflow_id');
    }

    public function canTrigger(): bool
    {
        if (!$this->is_enabled) {
            return false;
        }

        // Check debounce
        if ($this->debounce_seconds > 0 && $this->last_triggered_at) {
            $debounceUntil = $this->last_triggered_at->addSeconds($this->debounce_seconds);
            if (now()->lt($debounceUntil)) {
                return false;
            }
        }

        // Check concurrent executions
        $activeCount = SupplierAutomationExecution::where('workflow_id', $this->workflow_id)
            ->where('trigger_id', $this->id)
            ->whereIn('status', ['pending', 'running'])
            ->count();

        return $activeCount < $this->max_concurrent_executions;
    }

    public function fire(array $context = []): ?SupplierAutomationExecution
    {
        if (!$this->canTrigger()) {
            if ($this->queue_excess) {
                return $this->queueExecution($context);
            }
            return null;
        }

        $this->increment('total_triggers');
        $this->update(['last_triggered_at' => now()]);

        $mergedContext = array_merge($this->execution_context ?? [], $context);

        return SupplierAutomationExecution::create([
            'workflow_id' => $this->workflow_id,
            'trigger_id' => $this->id,
            'input_data' => $mergedContext,
            'status' => 'pending',
        ]);
    }

    protected function queueExecution(array $context): SupplierAutomationExecution
    {
        return SupplierAutomationExecution::create([
            'workflow_id' => $this->workflow_id,
            'trigger_id' => $this->id,
            'input_data' => array_merge($this->execution_context ?? [], $context),
            'status' => 'queued',
        ]);
    }

    // Scopes
    public function scopeOfType($query, string $type)
    {
        return $query->where('trigger_type', $type);
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeForSource($query, int $sourceId)
    {
        return $query->whereJsonContains('source_filter.ids', $sourceId);
    }
}
```

#### SupplierAutomationChain

```php
<?php

namespace App\Models\Supplier;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierAutomationChain extends Model
{
    protected $fillable = [
        'name',
        'description',
        'chain_definition',
        'fail_strategy',
        'parallel_stages',
        'max_parallel',
        'timeout_minutes',
        'is_enabled',
    ];

    protected $casts = [
        'chain_definition' => 'array',
        'parallel_stages' => 'boolean',
        'is_enabled' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uid = (string) \Ulid\Ulid::generate();
        });
    }

    public function executions(): HasMany
    {
        return $this->hasMany(SupplierAutomationChainExecution::class, 'chain_id');
    }

    public function getStages(): array
    {
        return $this->chain_definition['stages'] ?? [];
    }

    public function getStage(string $stageId): ?array
    {
        $stages = collect($this->getStages());
        return $stages->firstWhere('id', $stageId);
    }

    public function getNextStages(string $currentStageId): array
    {
        $stages = collect($this->getStages());
        $currentStage = $stages->firstWhere('id', $currentStageId);

        if (!$currentStage) {
            return [];
        }

        $currentOrder = $currentStage['order'];

        return $stages->filter(function ($stage) use ($currentOrder, $currentStageId) {
            if ($stage['order'] !== $currentOrder + 1) {
                return false;
            }

            $dependsOn = $stage['depends_on'] ?? [];
            return in_array($currentStageId, $dependsOn);
        })->values()->toArray();
    }

    public function canExecuteStage(string $stageId, array $completedStages): bool
    {
        $stage = $this->getStage($stageId);

        if (!$stage) {
            return false;
        }

        $dependsOn = $stage['depends_on'] ?? [];

        foreach ($dependsOn as $dependency) {
            if (!in_array($dependency, $completedStages)) {
                return false;
            }
        }

        return true;
    }

    public function start(array $context = []): SupplierAutomationChainExecution
    {
        return SupplierAutomationChainExecution::create([
            'chain_id' => $this->id,
            'status' => 'pending',
            'execution_context' => $context,
        ]);
    }
}
```

---

### 29.12 Servicio de Configuración de Fuentes

```php
<?php

namespace App\Services\Supplier;

use App\Models\Supplier\SupplierSource;
use App\Models\Supplier\SupplierSourceConfiguration;
use App\Models\Supplier\SupplierSourceTemplate;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;

class SourceConfigurationService
{
    protected array $schemaValidators = [
        'connection' => ConnectionConfigValidator::class,
        'authentication' => AuthenticationConfigValidator::class,
        'extraction' => ExtractionConfigValidator::class,
        'schedule' => ScheduleConfigValidator::class,
        'retry' => RetryConfigValidator::class,
        'proxy' => ProxyConfigValidator::class,
        'validation' => ValidationConfigValidator::class,
    ];

    public function createFromTemplate(
        SupplierSource $source,
        SupplierSourceTemplate $template,
        array $variables
    ): array {
        // Validate required variables
        $missing = $this->getMissingVariables($template, $variables);
        if (!empty($missing)) {
            throw new \InvalidArgumentException(
                "Missing required variables: " . implode(', ', $missing)
            );
        }

        $configurations = [];

        // Process each template configuration
        $templateConfigs = [
            'connection' => $template->connection_template,
            'extraction' => $template->extraction_template,
            'schedule' => $template->schedule_template,
            'retry' => $template->retry_template,
            'validation' => $template->validation_template,
        ];

        foreach ($templateConfigs as $type => $config) {
            if (empty($config)) {
                continue;
            }

            $processedConfig = $this->processTemplateVariables($config, $variables);

            $configurations[] = SupplierSourceConfiguration::create([
                'source_id' => $source->id,
                'config_type' => $type,
                'config_data' => $processedConfig,
            ]);
        }

        // Increment template usage
        $template->increment('usage_count');

        return $configurations;
    }

    protected function getMissingVariables(
        SupplierSourceTemplate $template,
        array $variables
    ): array {
        $required = $template->required_variables ?? [];
        return array_diff($required, array_keys($variables));
    }

    protected function processTemplateVariables(array $config, array $variables): array
    {
        $json = json_encode($config);

        foreach ($variables as $key => $value) {
            $placeholder = '{{' . $key . '}}';

            // Handle encrypted values
            if (str_starts_with($value, '{{ENCRYPTED:')) {
                $value = $this->resolveCredentialReference($value);
            }

            $json = str_replace($placeholder, $value, $json);
        }

        return json_decode($json, true);
    }

    protected function resolveCredentialReference(string $reference): string
    {
        // Extract credential reference
        preg_match('/\{\{ENCRYPTED:([^}]+)\}\}/', $reference, $matches);

        if (isset($matches[1])) {
            $credential = SupplierAutomationCredential::where('key', $matches[1])->first();
            if ($credential) {
                return Crypt::decryptString($credential->value);
            }
        }

        return $reference;
    }

    public function validateConfiguration(
        string $type,
        array $config
    ): ValidationResult {
        if (!isset($this->schemaValidators[$type])) {
            throw new \InvalidArgumentException("Unknown config type: {$type}");
        }

        $validatorClass = $this->schemaValidators[$type];
        $validator = new $validatorClass();

        return $validator->validate($config);
    }

    public function testConnection(SupplierSource $source): ConnectionTestResult
    {
        $connectionConfig = $source->configurations()
            ->where('config_type', 'connection')
            ->where('is_enabled', true)
            ->first();

        if (!$connectionConfig) {
            return new ConnectionTestResult(
                success: false,
                message: 'No connection configuration found'
            );
        }

        $authConfig = $source->configurations()
            ->where('config_type', 'authentication')
            ->where('is_enabled', true)
            ->first();

        $tester = $this->getConnectionTester($source->source_type);

        return $tester->test(
            $connectionConfig->config_data,
            $authConfig?->config_data ?? []
        );
    }

    protected function getConnectionTester(string $sourceType): ConnectionTesterInterface
    {
        return match ($sourceType) {
            'web' => new WebConnectionTester(),
            'ftp', 'sftp' => new FtpConnectionTester(),
            'api' => new ApiConnectionTester(),
            default => throw new \InvalidArgumentException("Unknown source type: {$sourceType}")
        };
    }

    public function buildEffectiveConfig(SupplierSource $source): array
    {
        $configs = $source->configurations()
            ->where('is_enabled', true)
            ->where('is_valid', true)
            ->orderBy('priority', 'desc')
            ->get();

        $effective = [];

        foreach ($configs as $config) {
            $effective[$config->config_type] = $config->config_data;
        }

        // Apply global variables
        $effective = $this->applyGlobalVariables($effective);

        return $effective;
    }

    protected function applyGlobalVariables(array $config): array
    {
        $globalVariables = SupplierAutomationVariable::where('scope', 'global')
            ->where('is_enabled', true)
            ->pluck('value', 'name')
            ->toArray();

        $json = json_encode($config);

        foreach ($globalVariables as $name => $value) {
            $json = str_replace('{{' . $name . '}}', $value, $json);
        }

        return json_decode($json, true);
    }
}
```

---

### 29.13 Seeder de Configuraciones

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier\SupplierSourceTemplate;

class SupplierSourceTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Template: Shopify Store
        SupplierSourceTemplate::create([
            'name' => 'Shopify Store API',
            'description' => 'Template for connecting to Shopify stores via Admin API',
            'source_type' => 'api',
            'category' => 'ecommerce',
            'tags' => ['shopify', 'ecommerce', 'api'],
            'required_variables' => ['store_name', 'api_token'],
            'connection_template' => [
                'base_url' => 'https://{{store_name}}.myshopify.com/admin/api/2024-01',
                'endpoints' => [
                    'products_list' => [
                        'method' => 'GET',
                        'path' => '/inventaries.json',
                        'pagination' => [
                            'type' => 'link_header',
                            'limit_param' => 'limit',
                            'limit_value' => 250,
                        ],
                    ],
                    'product_detail' => [
                        'method' => 'GET',
                        'path' => '/inventaries/{id}.json',
                    ],
                ],
                'rate_limit' => [
                    'requests_per_second' => 2,
                    'respect_headers' => true,
                ],
            ],
            'extraction_template' => [
                'field_mappings' => [
                    'sku' => ['path' => 'variants[0].sku'],
                    'name' => ['path' => 'title'],
                    'description' => ['path' => 'body_html'],
                    'price' => ['path' => 'variants[0].price'],
                    'compare_price' => ['path' => 'variants[0].compare_at_price'],
                    'images' => ['path' => 'images[*].src'],
                    'vendor' => ['path' => 'vendor'],
                    'tags' => ['path' => 'tags', 'split' => ', '],
                ],
            ],
            'is_public' => true,
        ]);

        // Template: WooCommerce API
        SupplierSourceTemplate::create([
            'name' => 'WooCommerce REST API',
            'description' => 'Template for WooCommerce stores using REST API v3',
            'source_type' => 'api',
            'category' => 'ecommerce',
            'tags' => ['woocommerce', 'wordpress', 'api'],
            'required_variables' => ['store_url', 'consumer_key', 'consumer_secret'],
            'connection_template' => [
                'base_url' => '{{store_url}}/wp-json/wc/v3',
                'endpoints' => [
                    'products_list' => [
                        'method' => 'GET',
                        'path' => '/inventaries',
                        'pagination' => [
                            'type' => 'page',
                            'page_param' => 'page',
                            'per_page_param' => 'per_page',
                            'per_page_value' => 100,
                        ],
                    ],
                ],
                'rate_limit' => [
                    'requests_per_minute' => 60,
                ],
            ],
            'extraction_template' => [
                'field_mappings' => [
                    'sku' => ['path' => 'sku'],
                    'name' => ['path' => 'name'],
                    'description' => ['path' => 'description'],
                    'short_description' => ['path' => 'short_description'],
                    'price' => ['path' => 'price'],
                    'regular_price' => ['path' => 'regular_price'],
                    'sale_price' => ['path' => 'sale_price'],
                    'images' => ['path' => 'images[*].src'],
                    'categories' => ['path' => 'categories[*].name'],
                ],
            ],
            'is_public' => true,
        ]);

        // Template: Generic FTP CSV
        SupplierSourceTemplate::create([
            'name' => 'FTP CSV Catalog',
            'description' => 'Generic template for CSV catalogs hosted on FTP/SFTP',
            'source_type' => 'ftp',
            'category' => 'distributor',
            'tags' => ['ftp', 'csv', 'catalog'],
            'required_variables' => [
                'host', 'port', 'username', 'password',
                'catalog_path', 'file_pattern', 'delimiter', 'encoding'
            ],
            'connection_template' => [
                'protocol' => 'sftp',
                'host' => '{{host}}',
                'port' => '{{port}}',
                'paths' => [
                    'base_directory' => '{{catalog_path}}',
                    'file_pattern' => '{{file_pattern}}',
                ],
                'connection_settings' => [
                    'timeout_seconds' => 30,
                    'passive_mode' => true,
                ],
            ],
            'extraction_template' => [
                'file_format' => 'csv',
                'csv_options' => [
                    'delimiter' => '{{delimiter}}',
                    'has_header' => true,
                    'encoding' => '{{encoding}}',
                    'skip_empty_lines' => true,
                ],
            ],
            'schedule_template' => [
                'schedules' => [
                    [
                        'name' => 'daily_sync',
                        'cron' => '0 6 * * *',
                        'timezone' => 'Europe/Madrid',
                        'enabled' => true,
                    ],
                ],
            ],
            'is_public' => true,
        ]);

        // Template: Web Scraping E-commerce
        SupplierSourceTemplate::create([
            'name' => 'E-commerce Web Scraping',
            'description' => 'Template for scraping standard e-commerce websites',
            'source_type' => 'web',
            'category' => 'ecommerce',
            'tags' => ['scraping', 'web', 'ecommerce'],
            'required_variables' => [
                'base_url', 'catalog_path', 'product_selector',
                'name_selector', 'price_selector', 'image_selector'
            ],
            'connection_template' => [
                'base_url' => '{{base_url}}',
                'catalog_urls' => ['{{base_url}}{{catalog_path}}'],
                'request_headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept-Language' => 'es-ES,es;q=0.9,en;q=0.8',
                ],
                'javascript_required' => true,
                'wait_for_selector' => '{{product_selector}}',
                'rate_limit' => [
                    'requests_per_minute' => 30,
                    'delay_between_requests_ms' => 2000,
                ],
                'pagination' => [
                    'type' => 'url_parameter',
                    'parameter_name' => 'page',
                    'start_page' => 1,
                ],
            ],
            'extraction_template' => [
                'field_mappings' => [
                    'name' => [
                        'selector' => '{{name_selector}}',
                        'type' => 'text',
                    ],
                    'price' => [
                        'selector' => '{{price_selector}}',
                        'type' => 'text',
                        'transformations' => ['normalize_price'],
                    ],
                    'images' => [
                        'selector' => '{{image_selector}}',
                        'type' => 'array',
                        'attribute' => 'src',
                    ],
                ],
            ],
            'retry_template' => [
                'max_attempts' => 3,
                'initial_delay_seconds' => 10,
                'backoff_multiplier' => 2,
            ],
            'is_public' => true,
        ]);

        // Template: Excel Upload Manual
        SupplierSourceTemplate::create([
            'name' => 'Manual Excel Upload',
            'description' => 'Template for manually uploaded Excel catalogs',
            'source_type' => 'upload',
            'category' => 'manufacturer',
            'tags' => ['excel', 'manual', 'upload'],
            'required_variables' => [],
            'connection_template' => [
                'allowed_extensions' => ['xlsx', 'xls', 'csv'],
                'max_file_size_mb' => 50,
                'storage_path' => 'supplier-uploads',
            ],
            'extraction_template' => [
                'file_format' => 'auto',
                'excel_options' => [
                    'sheet_index' => 0,
                    'header_row' => 1,
                    'start_row' => 2,
                ],
                'field_mappings' => [
                    'sku' => ['column' => 'A'],
                    'name' => ['column' => 'B'],
                    'description' => ['column' => 'C'],
                    'price' => ['column' => 'D'],
                    'stock' => ['column' => 'E'],
                ],
            ],
            'validation_template' => [
                'field_validation' => [
                    'sku' => ['required' => true, 'unique' => true],
                    'name' => ['required' => true, 'min_length' => 5],
                    'price' => ['required' => true, 'type' => 'number', 'min' => 0.01],
                ],
            ],
            'is_public' => true,
        ]);
    }
}
```

---

### 29.14 Resumen de Nuevas Tablas

| Tabla | Propósito |
|-------|-----------|
| `supplier_source_configurations` | Configuraciones específicas por tipo (conexión, auth, extracción, etc.) |
| `supplier_source_templates` | Templates reutilizables de configuración |
| `supplier_source_monitors` | Estado de salud y monitoreo continuo |
| `supplier_source_health_history` | Historial de checks para análisis |
| `supplier_source_transformations` | Transformaciones de datos personalizadas |
| `supplier_source_webhooks` | Webhooks entrantes de proveedores |
| `supplier_source_files` | Gestión de archivos subidos/descargados |
| `supplier_automation_triggers` | Triggers automáticos para workflows |
| `supplier_automation_variables` | Variables globales y por contexto |
| `supplier_automation_chains` | Pipelines multi-etapa |
| `supplier_automation_chain_executions` | Ejecuciones de pipelines |

**Total nuevas tablas: 11**
**Total tablas del sistema: 43**

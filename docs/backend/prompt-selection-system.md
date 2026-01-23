# Sistema de Selección de Prompts para IA

## 📋 Visión General

El sistema de selección de prompts permite elegir automáticamente el prompt más apropiado para generar contenido de productos basándose en múltiples criterios: proveedor, categoría, tipo de contenido y fuente de datos.

**Caso de uso principal**: Integración con n8n para generación masiva de descripciones de productos mediante IA.

---

## 🎯 Conceptos Clave

### Scopes (Alcances)

Los prompts tienen diferentes niveles de especificidad:

| Scope | Prioridad | Descripción | Ejemplo |
|-------|-----------|-------------|---------|
| `supplier_category` | 4 (Mayor) | Específico para proveedor + categoría | "Nike + Calzado deportivo" |
| `supplier` | 3 | Específico para proveedor | "Nike (cualquier categoría)" |
| `category` | 2 | Específico para categoría | "Calzado deportivo (cualquier proveedor)" |
| `source` | 2 | Específico para fuente de datos | "Importador XML específico" |
| `global` | 1 (Menor) | Genérico para todos los productos | "Prompt por defecto" |

### Content Types (Tipos de Contenido)

Un mismo producto puede necesitar diferentes tipos de contenido:

- `description` - Descripción completa del producto
- `short_description` - Descripción corta (resumen)
- `title` - Título del producto
- `seo_title` - Título optimizado para SEO
- `seo_description` - Meta description para SEO
- `seo_keywords` - Palabras clave SEO
- `metadata` - Metadatos adicionales
- `features` - Características del producto
- `specifications` - Especificaciones técnicas
- `benefits` - Beneficios del producto

---

## 🔄 Algoritmo de Selección

### Flujo de Decisión

```
ENTRADA: supplier_id, category_id, content_type, source_id

1. Buscar prompts activos (is_active = true) con content_type coincidente

2. Aplicar filtros por scope:
   ✓ supplier_category: supplier_id AND category_id coinciden
   ✓ supplier: solo supplier_id coincide
   ✓ category: solo category_id coincide
   ✓ source: solo source_id coincide
   ✓ global: siempre aplica (fallback)

3. Ordenar resultados por:
   a) Scope priority (4 → 1)
   b) Priority field (DESC)
   c) Created_at (DESC, más reciente primero)

4. Seleccionar el primer resultado

SALIDA: SupplierPrompt | null
```

### Ejemplo de Selección

**Contexto:**
- Producto: "Nike Air Max 90"
- Supplier: Nike (id: 1)
- Category: Calzado deportivo (id: 5)
- Content Type: description

**Prompts disponibles:**

| ID | Label | Scope | Priority | Content Type | Match |
|----|-------|-------|----------|--------------|-------|
| 10 | "Nike Calzado Descripción Pro" | supplier_category | 10 | description | ✅ Seleccionado |
| 8 | "Nike General" | supplier | 5 | description | ❌ (scope menor) |
| 6 | "Calzado Genérico" | category | 8 | description | ❌ (scope menor) |
| 2 | "Descripción Estándar" | global | 0 | description | ❌ (fallback) |

**Resultado:** Se selecciona el prompt ID 10 porque tiene el scope más específico (supplier_category).

---

## 💻 Uso del Servicio

### Desde Código PHP

```php
use App\Services\Supplier\PromptSelectionService;

// Inyectar el servicio
public function __construct(protected PromptSelectionService $promptService) {}

// Selección básica con caché (5 minutos)
$prompt = $this->promptService->selectPrompt(
    supplierId: 1,
    categoryId: 5,
    contentType: 'description'
);

if ($prompt) {
    echo $prompt->prompt_template;
}

// Selección sin caché (tiempo real)
$prompt = $this->promptService->selectPromptNow(
    supplierId: 1,
    categoryId: 5,
    contentType: 'seo_title'
);

// Ver todos los prompts aplicables (para debugging)
$allPrompts = $this->promptService->getApplicablePrompts(
    supplierId: 1,
    categoryId: 5,
    contentType: 'description'
);

// Explicación detallada de la selección
$explanation = $this->promptService->explainSelection(
    supplierId: 1,
    categoryId: 5,
    contentType: 'description'
);
```

### Reemplazo de Variables

```php
$productData = [
    'product_name' => 'Nike Air Max 90',
    'category' => 'Calzado deportivo',
    'supplier_name' => 'Nike',
    'price' => '129.99',
    'description' => 'Zapatillas deportivas...',
    'features' => 'Suela de goma, Amortiguación Air',
    'brand' => 'Nike',
    'sku' => 'NK-AM90-001',
    'reference' => 'AM90-WHT-42',
    'ean' => '1234567890123',
];

$renderedPrompt = $this->promptService->renderPrompt($prompt, $productData);

// Template antes:
// "Crea una descripción para @{{ product_name }} de @{{ supplier_name }}"

// Template después:
// "Crea una descripción para Nike Air Max 90 de Nike"
```

### Preparar para n8n

```php
$n8nData = $this->promptService->prepareForN8n(
    supplierId: 1,
    categoryId: 5,
    contentType: 'description',
    productData: $productData
);

// Retorna:
[
    'prompt_id' => 10,
    'prompt_uid' => '01JFXXX...',
    'prompt_label' => 'Nike Calzado Descripción Pro',
    'prompt_text' => 'Crea una descripción para Nike Air Max 90 de Nike...',
    'output_language' => 'es',
    'tone' => 'professional',
    'seo_focus' => true,
    'content_type' => 'description',
    'scope' => 'supplier_category',
    'priority' => 10,
    'version' => 1,
    'metadata' => [
        'supplier_id' => 1,
        'category_id' => 5,
        'selection_reason' => 'Matched scope: supplier_category',
    ]
]
```

---

## 🌐 API para n8n

### Base URL
```
https://manager.test/api/suppliers/prompts
```

### Rate Limiting
- **120 peticiones por minuto** por IP
- Sin autenticación requerida (público)

---

### 1. Health Check

**GET** `/health`

Verifica que el servicio esté operativo.

**Response:**
```json
{
    "success": true,
    "service": "Prompt Selection Service",
    "status": "operational",
    "timestamp": "2025-12-23T10:30:00Z"
}
```

---

### 2. Selección Individual

**POST** `/select`

Selecciona el mejor prompt para un producto.

**Request Body:**
```json
{
    "supplier_id": 1,
    "category_id": 5,
    "content_type": "description",
    "source_id": null,
    "product_data": {
        "product_name": "Nike Air Max 90",
        "category": "Calzado deportivo",
        "price": "129.99",
        "description": "Zapatillas deportivas con tecnología Air",
        "features": "Suela de goma, Amortiguación Air",
        "brand": "Nike",
        "sku": "NK-AM90-001"
    }
}
```

**Validation Rules:**
- `supplier_id`: opcional, integer, debe existir en tabla suppliers
- `category_id`: opcional, integer, debe existir en tabla categories
- `content_type`: **requerido**, string, uno de: description, short_description, title, seo_title, seo_description, seo_keywords, metadata, features, specifications, benefits
- `source_id`: opcional, integer, debe existir en tabla supplier_sources
- `product_data`: opcional, object

**Success Response (200):**
```json
{
    "success": true,
    "data": {
        "prompt_id": 10,
        "prompt_uid": "01JFXXX...",
        "prompt_label": "Nike Calzado Descripción Pro",
        "prompt_text": "Crea una descripción para Nike Air Max 90 de Nike...",
        "output_language": "es",
        "tone": "professional",
        "seo_focus": true,
        "content_type": "description",
        "scope": "supplier_category",
        "priority": 10,
        "version": 1,
        "metadata": {
            "supplier_id": 1,
            "category_id": 5,
            "selection_reason": "Matched scope: supplier_category"
        }
    }
}
```

**Error Response - No Match (404):**
```json
{
    "success": false,
    "message": "No matching prompt found for the given criteria",
    "criteria": {
        "supplier_id": 1,
        "category_id": 5,
        "content_type": "description",
        "source_id": null
    }
}
```

**Error Response - Validation (422):**
```json
{
    "success": false,
    "message": "Validation error",
    "errors": {
        "content_type": [
            "The content type field is required."
        ]
    }
}
```

---

### 3. Selección por Lotes (Batch)

**POST** `/batch-select`

Procesa hasta 100 productos simultáneamente.

**Request Body:**
```json
{
    "products": [
        {
            "supplier_id": 1,
            "category_id": 5,
            "content_type": "description",
            "product_data": {
                "product_name": "Nike Air Max 90"
            }
        },
        {
            "supplier_id": 1,
            "category_id": 5,
            "content_type": "seo_title",
            "product_data": {
                "product_name": "Nike Air Max 90"
            }
        }
    ]
}
```

**Validation Rules:**
- `products`: **requerido**, array, mínimo 1, máximo 100
- `products.*.supplier_id`: opcional, integer
- `products.*.category_id`: opcional, integer
- `products.*.content_type`: **requerido**, string
- `products.*.source_id`: opcional, integer
- `products.*.product_data`: opcional, object

**Success Response (200):**
```json
{
    "success": true,
    "total": 2,
    "successful": 2,
    "failed": 0,
    "results": [
        {
            "index": 0,
            "success": true,
            "data": {
                "prompt_id": 10,
                "prompt_text": "...",
                "..."
            }
        },
        {
            "index": 1,
            "success": true,
            "data": {
                "prompt_id": 12,
                "prompt_text": "...",
                "..."
            }
        }
    ]
}
```

**Partial Success Example:**
```json
{
    "success": true,
    "total": 3,
    "successful": 2,
    "failed": 1,
    "results": [
        {
            "index": 0,
            "success": true,
            "data": { "..." }
        },
        {
            "index": 1,
            "success": false,
            "data": null
        },
        {
            "index": 2,
            "success": true,
            "data": { "..." }
        }
    ]
}
```

---

### 4. Explicación de Selección (Debug)

**GET** `/explain?supplier_id=1&category_id=5&content_type=description`

Muestra el razonamiento detrás de la selección de prompt.

**Query Parameters:**
- `supplier_id`: opcional, integer
- `category_id`: opcional, integer
- `content_type`: **requerido**, string
- `source_id`: opcional, integer

**Success Response (200):**
```json
{
    "success": true,
    "explanation": {
        "selected_prompt": {
            "id": 10,
            "uid": "01JFXXX...",
            "label": "Nike Calzado Descripción Pro",
            "scope": "supplier_category",
            "priority": 10,
            "content_type": "description"
        },
        "applicable_prompts_count": 4,
        "all_applicable": [
            {
                "id": 10,
                "label": "Nike Calzado Descripción Pro",
                "scope": "supplier_category",
                "priority": 10
            },
            {
                "id": 8,
                "label": "Nike General",
                "scope": "supplier",
                "priority": 5
            },
            {
                "id": 6,
                "label": "Calzado Genérico",
                "scope": "category",
                "priority": 8
            },
            {
                "id": 2,
                "label": "Descripción Estándar",
                "scope": "global",
                "priority": 0
            }
        ],
        "selection_criteria": {
            "supplier_id": 1,
            "category_id": 5,
            "content_type": "description",
            "source_id": null
        },
        "selection_reason": "Selected based on scope 'supplier_category' with priority 10"
    }
}
```

---

## 🔧 Ejemplo de Integración con n8n

### Workflow Básico

```
1. HTTP Request (GET Products from PrestaShop)
   ↓
2. Loop Over Items
   ↓
3. HTTP Request (POST to /api/suppliers/prompts/select)
   Body: {
     "supplier_id": {{ $json.supplier_id }},
     "category_id": {{ $json.category_id }},
     "content_type": "description",
     "product_data": {
       "product_name": {{ $json.name }},
       "category": {{ $json.category_name }},
       "price": {{ $json.price }}
     }
   }
   ↓
4. OpenAI Node (Generate Content)
   Prompt: {{ $json.data.prompt_text }}
   ↓
5. HTTP Request (UPDATE Product in PrestaShop)
   Body: {
     "description": {{ $json.choices[0].message.content }}
   }
```

### Workflow Avanzado (Batch)

```
1. HTTP Request (GET Products from PrestaShop)
   ↓
2. Function Node (Transform to batch format)
   return {
     products: items.map(item => ({
       supplier_id: item.supplier_id,
       category_id: item.category_id,
       content_type: 'description',
       product_data: {
         product_name: item.name,
         category: item.category_name,
         price: item.price
       }
     }))
   }
   ↓
3. HTTP Request (POST to /api/suppliers/prompts/batch-select)
   ↓
4. Loop Over Results
   ↓
5. OpenAI Node (Generate Content)
   ↓
6. HTTP Request (UPDATE Products in PrestaShop)
```

---

## 🗄️ Estructura de Base de Datos

### Tabla: `supplier_prompts`

```sql
id                  BIGINT PRIMARY KEY
uid                 CHAR(26) UNIQUE
supplier_id         BIGINT NULLABLE (FK: suppliers.id)
category_id         BIGINT NULLABLE (FK: categories.id)
source_id           BIGINT NULLABLE (FK: supplier_sources.id)
label               VARCHAR(255) NOT NULL
prompt_template     TEXT NOT NULL
scope               ENUM('global', 'supplier', 'category', 'supplier_category', 'source')
content_type        ENUM('description', 'short_description', 'title', ...)
output_language     VARCHAR(10) DEFAULT 'es'
tone                VARCHAR(50) DEFAULT 'professional'
seo_focus           BOOLEAN DEFAULT false
priority            INTEGER DEFAULT 0
version             INTEGER DEFAULT 1
is_active           BOOLEAN DEFAULT true
notes               TEXT NULLABLE
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

**Índices:**
```sql
INDEX (supplier_id, category_id, content_type)
INDEX (is_active)
INDEX (scope, content_type)
UNIQUE (supplier_id, category_id, content_type, scope)
```

### Tabla: `supplier_categories` (Pivot)

```sql
id              BIGINT PRIMARY KEY
uid             CHAR(26) UNIQUE
supplier_id     BIGINT NOT NULL (FK: suppliers.id, CASCADE)
category_id     BIGINT NOT NULL (FK: categories.id, CASCADE)
is_active       BOOLEAN DEFAULT true
priority        INTEGER DEFAULT 0
metadata        JSON NULLABLE
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

**Índices:**
```sql
UNIQUE (supplier_id, category_id)
INDEX (supplier_id, is_active)
INDEX (category_id, is_active)
```

---

## ⚡ Caché

### Estrategia de Caché

- **TTL:** 5 minutos (300 segundos)
- **Driver:** Redis
- **Cache Key Pattern:** `prompt_selection:{supplier_id}:{category_id}:{content_type}:{source_id}`

### Ejemplos de Cache Keys

```
prompt_selection:1:5:description:null
prompt_selection:null:null:seo_title:null
prompt_selection:3:12:features:7
```

### Invalidación Manual

```php
// Limpiar caché específico
$this->promptService->clearCache(
    supplierId: 1,
    categoryId: 5,
    contentType: 'description'
);

// Limpiar todo el caché de selección de prompts
$this->promptService->clearCache();
```

### Auto-invalidación

El caché se invalida automáticamente cuando:
- Se crea un nuevo prompt
- Se actualiza un prompt existente
- Se cambia el estado `is_active` de un prompt
- Se modifica la prioridad de un prompt

---

## 🧪 Testing

### Prueba Manual con cURL

**Health Check:**
```bash
curl https://manager.test/api/suppliers/prompts/health
```

**Selección Individual:**
```bash
curl -X POST https://manager.test/api/suppliers/prompts/select \
  -H "Content-Type: application/json" \
  -d '{
    "supplier_id": 1,
    "category_id": 5,
    "content_type": "description",
    "product_data": {
      "product_name": "Nike Air Max 90",
      "category": "Calzado deportivo",
      "price": "129.99"
    }
  }'
```

**Explicación:**
```bash
curl "https://manager.test/api/suppliers/prompts/explain?supplier_id=1&category_id=5&content_type=description"
```

### Prueba con Tinker

```php
php artisan tinker

// Test service
$service = app(\App\Services\Supplier\PromptSelectionService::class);

$prompt = $service->selectPrompt(1, 5, 'description');
dd($prompt->prompt_template);

// Test rendering
$data = ['product_name' => 'Nike Air Max 90', 'category' => 'Calzado'];
$rendered = $service->renderPrompt($prompt, $data);
echo $rendered;

// Test explanation
$explanation = $service->explainSelection(1, 5, 'description');
dd($explanation);
```

---

## 📊 Casos de Uso Reales

### Caso 1: Generación de Descripciones para Nike

**Escenario:**
- Proveedor: Nike
- Categorías: Calzado, Ropa deportiva, Accesorios
- Necesidad: Descripciones optimizadas para SEO en español

**Configuración de Prompts:**

1. **Prompt Específico Nike + Calzado** (supplier_category, priority: 10)
   ```
   Crea una descripción técnica y atractiva para @{{ product_name }}.
   Enfócate en la tecnología Nike, comodidad y rendimiento deportivo.
   Categoría: @{{ category }}
   Precio: @{{ price }}€
   Incluye keywords: zapatillas Nike, calzado deportivo, running
   ```

2. **Prompt General Nike** (supplier, priority: 5)
   ```
   Describe @{{ product_name }} de Nike destacando innovación y calidad.
   ```

3. **Prompt Fallback** (global, priority: 0)
   ```
   Describe el producto @{{ product_name }} de manera clara y concisa.
   ```

**Resultado:** Productos de Nike + Calzado usan el prompt más específico con lenguaje técnico deportivo.

---

### Caso 2: Títulos SEO para Múltiples Proveedores

**Escenario:**
- 5 proveedores diferentes
- 20 categorías
- Necesidad: Títulos SEO consistentes (max 60 caracteres)

**Configuración de Prompts:**

1. **Prompt por Content Type** (global, content_type: seo_title)
   ```
   Genera un título SEO de máximo 60 caracteres para @{{ product_name }}.
   Incluye la marca @{{ brand }} y categoría @{{ category }}.
   Formato: [Producto] - [Marca] | [Categoría]
   ```

**n8n Workflow:**
```
Loop productos → Select prompt (content_type: seo_title) → OpenAI → Update SEO title
```

---

### Caso 3: Características Técnicas para Electrónica

**Escenario:**
- Categoría: Electrónica
- Necesidad: Listado estructurado de especificaciones

**Configuración de Prompts:**

1. **Prompt Categoría Electrónica** (category, content_type: specifications)
   ```
   Genera una lista de especificaciones técnicas para @{{ product_name }}.

   Estructura requerida:
   - Modelo: @{{ reference }}
   - Características: @{{ features }}
   - Garantía: 2 años

   Formato: HTML <ul><li>...</li></ul>
   ```

---

## 🚨 Troubleshooting

### Problema: No se encuentra prompt

**Síntoma:**
```json
{
    "success": false,
    "message": "No matching prompt found for the given criteria"
}
```

**Causas posibles:**
1. No existe prompt activo (`is_active = true`) para ese `content_type`
2. El `supplier_id` o `category_id` no tienen prompts asignados
3. No hay prompt global de fallback

**Solución:**
```bash
# Ver prompts aplicables con /explain
curl "https://manager.test/api/suppliers/prompts/explain?supplier_id=1&content_type=description"

# Crear un prompt global de fallback
php artisan tinker
SupplierPrompt::create([
    'label' => 'Descripción Genérica',
    'prompt_template' => 'Describe @{{ product_name }}',
    'scope' => 'global',
    'content_type' => 'description',
    'is_active' => true,
]);
```

---

### Problema: Variables no se reemplazan

**Síntoma:**
El prompt final contiene `@{{ product_name }}` en lugar del valor real.

**Causas posibles:**
1. No se pasó el array `product_data` en la petición
2. Las claves del array no coinciden con las variables del template

**Solución:**
```php
// ❌ Incorrecto
{
    "product_data": {
        "name": "Nike Air Max"  // Debería ser "product_name"
    }
}

// ✅ Correcto
{
    "product_data": {
        "product_name": "Nike Air Max",
        "category": "Calzado",
        "price": "129.99"
    }
}
```

**Variables soportadas:**
- `product_name`, `category`, `supplier_name`, `sku`, `reference`, `price`, `description`, `features`, `brand`, `ean`

---

### Problema: Rate Limit Exceeded

**Síntoma:**
```
429 Too Many Requests
```

**Causa:**
Superadas las 120 peticiones por minuto.

**Solución:**
```javascript
// En n8n, agregar "Wait" node entre peticiones
Wait: 0.5 seconds

// O usar batch-select para procesar múltiples productos
HTTP Request (POST /batch-select) {
  products: [... hasta 100 productos ...]
}
```

---

## 📈 Mejores Prácticas

### 1. Organización de Prompts

✅ **Recomendado:**
- Crear prompt global de fallback para cada `content_type`
- Usar prompts `supplier_category` para casos específicos
- Mantener `priority` consistente (0-10 para global, 10-50 para supplier, 50-100 para supplier_category)

❌ **Evitar:**
- Crear demasiados prompts con mismo scope y priority (confusión en selección)
- Dejar `content_type` importantes sin prompt global de fallback
- Desactivar prompts sin crear reemplazos

---

### 2. Plantillas de Prompts

✅ **Recomendado:**
- Usar variables para datos dinámicos: `@{{ product_name }}`
- Incluir instrucciones claras para la IA
- Especificar formato de salida (HTML, plain text, JSON)

❌ **Evitar:**
- Hardcodear valores específicos de productos
- Prompts demasiado genéricos ("Describe el producto")
- Mezclar múltiples objetivos en un solo prompt

**Ejemplo bueno:**
```
Genera una descripción SEO de 150-200 palabras para @{{ product_name }}.

Contexto:
- Categoría: @{{ category }}
- Marca: @{{ supplier_name }}
- Precio: @{{ price }}€

Requisitos:
- Incluye 3-5 keywords relevantes
- Enfócate en beneficios para el cliente
- Tono: profesional pero cercano
- Formato: un solo párrafo, sin bullet points
```

---

### 3. Integración con n8n

✅ **Recomendado:**
- Usar `/batch-select` para procesar múltiples productos (más eficiente)
- Cachear resultados de prompts en n8n si se reutilizan
- Manejar errores 404 con prompt genérico de fallback

❌ **Evitar:**
- Hacer una petición por producto si hay más de 10 productos
- Ignorar el campo `selection_reason` en metadata (útil para debugging)
- No validar que `prompt_text` tenga contenido antes de enviar a OpenAI

**n8n Function Node (Error Handling):**
```javascript
if (!$json.data || !$json.data.prompt_text) {
  return [{
    json: {
      error: true,
      message: 'No prompt found, using fallback',
      prompt_text: 'Describe el producto {{ product_name }} de manera clara y concisa.'
    }
  }];
}
return [$json];
```

---

## 🔐 Seguridad

### Consideraciones

1. **Sin Autenticación Requerida**
   - Los endpoints son públicos con rate limiting
   - Considerar agregar API key en el futuro para ambientes de producción

2. **Validación de Entrada**
   - Todos los parámetros se validan con Laravel Form Request
   - IDs verificados contra base de datos (exists rule)

3. **Rate Limiting**
   - 120 peticiones/minuto por IP
   - Previene abuso y sobrecarga del servidor

4. **Sanitización de Variables**
   - Las variables reemplazadas NO ejecutan código
   - Solo reemplazo de strings, sin eval() o ejecución de PHP

---

## 📝 Changelog

### v1.0.0 - 2025-12-23

**Creado:**
- Sistema de selección de prompts con 4 niveles de prioridad
- API REST con 4 endpoints para integración n8n
- Servicio `PromptSelectionService` con caché de 5 minutos
- Soporte para 10 tipos de contenido diferentes
- Sistema de reemplazo de variables en templates
- Documentación completa del sistema

**Endpoints:**
- GET `/api/suppliers/prompts/health` - Health check
- POST `/api/suppliers/prompts/select` - Selección individual
- POST `/api/suppliers/prompts/batch-select` - Selección por lotes (max 100)
- GET `/api/suppliers/prompts/explain` - Debugging

**Base de datos:**
- Campo `content_type` en `supplier_prompts`
- Índices compuestos para optimización de consultas
- Tabla pivot `supplier_categories` para relaciones N:M

---

## 🎓 Recursos Adicionales

- **Código fuente:** `app/Services/Supplier/PromptSelectionService.php`
- **Controlador API:** `app/Http/Controllers/Api/Suppliers/PromptSelectionApiController.php`
- **Rutas:** `routes/api.php`
- **Modelo:** `app/Models/Supplier/SupplierPrompt.php`
- **Migraciones:** `database/migrations/2025_12_23_*`

**Documentación relacionada:**
- [Sistema de Categorías de Proveedores](./supplier-categories-system.md) *(pendiente)*
- [Integración n8n para Generación de Contenido](./n8n-content-generation.md) *(pendiente)*
- [Gestión de Prompts en el Panel Admin](./prompts-management-ui.md) *(pendiente)*

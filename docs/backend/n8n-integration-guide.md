# Guía de Integración n8n - Sistema de Prompts IA

## 📋 Visión General

Esta guía explica cómo integrar el sistema de selección de prompts con **n8n** para automatizar la generación de contenido de productos usando IA (OpenAI, Anthropic, etc.).

**Caso de uso principal:** Generar automáticamente descripciones, títulos SEO, características y otros contenidos para productos importados desde PrestaShop u otras fuentes.

---

## 🚀 Configuración Inicial

### 1. Variables de Entorno en n8n

Configura estas variables en tu workflow de n8n:

```javascript
// En Settings > Variables
API_BASE_URL = "https://manager.test/api/suppliers/prompts"
OPENAI_API_KEY = "sk-..."
```

### 2. Verificar Conexión

**Node: HTTP Request**
```
Method: GET
URL: {{ $vars.API_BASE_URL }}/health
```

**Respuesta esperada:**
```json
{
  "success": true,
  "service": "Prompt Selection Service",
  "status": "operational",
  "timestamp": "2025-12-23T10:30:00+01:00"
}
```

---

## 📊 Workflows de Ejemplo

### Workflow 1: Generación Individual de Descripciones

**Escenario:** Un nuevo producto se crea en PrestaShop → n8n genera descripción automáticamente.

```
1. Webhook (Trigger)
   ↓
2. HTTP Request (Get Product Data from PrestaShop)
   ↓
3. HTTP Request (Select Prompt)
   ↓
4. OpenAI Node (Generate Content)
   ↓
5. HTTP Request (Update Product in PrestaShop)
```

#### Node 2: Get Product from PrestaShop

**HTTP Request Node:**
```
Method: GET
URL: https://prestashop.com/api/products/{{ $json.product_id }}
Authentication: Basic Auth (API Key)
```

#### Node 3: Select Prompt

**HTTP Request Node:**
```
Method: POST
URL: {{ $vars.API_BASE_URL }}/select
Headers:
  Content-Type: application/json
Body (JSON):
{
  "supplier_id": {{ $json.supplier_id }},
  "category_id": {{ $json.category_id }},
  "content_type": "description",
  "product_data": {
    "product_name": "{{ $json.name }}",
    "category": "{{ $json.category_name }}",
    "price": "{{ $json.price }}",
    "brand": "{{ $json.manufacturer_name }}"
  }
}
```

**Salida esperada:**
```json
{
  "success": true,
  "data": {
    "prompt_text": "Genera una descripción profesional para Nike Air Max 90...",
    "output_language": "es",
    "tone": "professional",
    "content_type": "description"
  }
}
```

#### Node 4: Generate Content with OpenAI

**OpenAI Node:**
```
Operation: Message a Model
Model: gpt-4
Prompt: {{ $json.data.prompt_text }}
Temperature: 0.7
Max Tokens: 500
```

#### Node 5: Update Product

**HTTP Request Node:**
```
Method: PUT
URL: https://prestashop.com/api/products/{{ $('Webhook').item.json.product_id }}
Body (JSON):
{
  "product": {
    "description": {
      "language": {
        "id": 1,
        "value": "{{ $json.choices[0].message.content }}"
      }
    }
  }
}
```

---

### Workflow 2: Generación por Lotes (Batch Processing)

**Escenario:** Procesar 50 productos nuevos de una importación masiva.

```
1. Schedule Trigger (Every hour)
   ↓
2. HTTP Request (Get Pending Products from PrestaShop)
   ↓
3. Function Node (Prepare Batch Request)
   ↓
4. HTTP Request (Batch Select Prompts)
   ↓
5. Loop Over Items
   ↓
6. OpenAI Node (Generate for each)
   ↓
7. HTTP Request (Update Products)
```

#### Node 3: Prepare Batch Request

**Function Node:**
```javascript
// Transform inventaries array into batch format
const products = $input.all().map(item => ({
  supplier_id: item.json.supplier_id,
  category_id: item.json.category_id,
  content_type: 'description',
  product_data: {
    product_name: item.json.name,
    category: item.json.category_name,
    price: item.json.price,
    brand: item.json.manufacturer_name
  }
}));

return [{
  json: {
    products: products.slice(0, 100) // Max 100 per batch
  }
}];
```

#### Node 4: Batch Select Prompts

**HTTP Request Node:**
```
Method: POST
URL: {{ $vars.API_BASE_URL }}/batch-select
Body (JSON):
{{ $json }}
```

**Salida esperada:**
```json
{
  "success": true,
  "total": 50,
  "successful": 50,
  "failed": 0,
  "results": [
    {
      "index": 0,
      "success": true,
      "data": {
        "prompt_text": "...",
        "output_language": "es"
      }
    },
    ...
  ]
}
```

---

### Workflow 3: Generación Múltiple (Descripción + SEO)

**Escenario:** Para cada producto, generar descripción completa, short description, SEO title y meta description.

```
1. Webhook
   ↓
2. Split Into Batches (4 content types)
   ↓
3. HTTP Request (Select Prompt for each type)
   ↓
4. Merge
   ↓
5. OpenAI Node (Generate all 4 types)
   ↓
6. Function Node (Structure data)
   ↓
7. HTTP Request (Update Product with all fields)
```

#### Node 2: Split Into Batches

**Function Node:**
```javascript
const product = $input.first().json;

const contentTypes = [
  'description',
  'short_description',
  'seo_title',
  'seo_description'
];

return contentTypes.map(type => ({
  json: {
    product_id: product.id,
    supplier_id: product.supplier_id,
    category_id: product.category_id,
    content_type: type,
    product_data: {
      product_name: product.name,
      category: product.category_name,
      price: product.price,
      brand: product.manufacturer_name
    }
  }
}));
```

#### Node 3: Select Prompt (Set to run for all items)

**HTTP Request Node:**
```
Method: POST
URL: {{ $vars.API_BASE_URL }}/select
Execute Once: NO (run for all input items)
Body:
{
  "supplier_id": {{ $json.supplier_id }},
  "category_id": {{ $json.category_id }},
  "content_type": "{{ $json.content_type }}",
  "product_data": {{ $json.product_data }}
}
```

---

## 🔍 Debugging y Troubleshooting

### Endpoint /explain

Usa este endpoint para entender por qué se seleccionó un prompt específico:

**HTTP Request Node:**
```
Method: GET
URL: {{ $vars.API_BASE_URL }}/explain?supplier_id=1&category_id=5&content_type=description
```

**Respuesta:**
```json
{
  "success": true,
  "explanation": {
    "selected_prompt": {
      "id": 12,
      "label": "Nike Golf - Descripción Completa",
      "scope": "supplier_category",
      "priority": 90
    },
    "applicable_prompts_count": 4,
    "all_applicable": [...]
  }
}
```

### Manejo de Errores

**Function Node (Error Handler):**
```javascript
// Verificar si la selección de prompt fue exitosa
if (!$json.success || !$json.data || !$json.data.prompt_text) {
  console.error('No prompt found, using fallback');

  return [{
    json: {
      prompt_text: `Describe el producto {{ $('Webhook').item.json.name }} de manera clara y concisa.`,
      fallback: true
    }
  }];
}

return [$json];
```

---

## 📈 Casos de Uso Avanzados

### Caso 1: Actualización Programada de SEO

**Workflow programado para mejorar SEO de productos antiguos:**

```
1. Schedule Trigger (Daily at 2 AM)
   ↓
2. Database Query (Get products with old descriptions)
   ↓
3. HTTP Request (Batch Select Prompts)
   ↓
4. Loop (Generate new SEO descriptions)
   ↓
5. HTTP Request (Update only SEO fields)
```

**Query SQL:**
```sql
SELECT id, name, supplier_id, category_id
FROM products
WHERE updated_at < DATE_SUB(NOW(), INTERVAL 6 MONTH)
AND manufacturer_name IN ('Nike', 'Adidas')
LIMIT 100
```

---

### Caso 2: A/B Testing de Descripciones

**Generar 2 versiones diferentes y trackear cuál convierte mejor:**

```
1. New Product Webhook
   ↓
2. HTTP Request (Select Prompt - Version A: Professional)
   ↓
3. OpenAI Node (Generate Version A)
   ↓
4. HTTP Request (Select Prompt - Version B: Enthusiastic)
   ↓
5. OpenAI Node (Generate Version B)
   ↓
6. Function Node (Assign randomly to product)
   ↓
7. Database Insert (Track which version was used)
   ↓
8. HTTP Request (Update Product)
```

---

### Caso 3: Traducción Multiidioma

**Generar contenido en español y luego traducir a otros idiomas:**

```
1. HTTP Request (Select Prompt - Spanish)
   ↓
2. OpenAI Node (Generate in Spanish)
   ↓
3. Split (ES, EN, FR, DE)
   ↓
4. OpenAI Node (Translate to each language)
   ↓
5. Merge
   ↓
6. HTTP Request (Update product multi-language)
```

---

## ⚡ Optimizaciones y Mejores Prácticas

### 1. Caché de Prompts

**Evita llamadas repetidas al endpoint:**

```javascript
// Function Node - Cache prompts locally
const cacheKey = `prompt_${$json.supplier_id}_${$json.category_id}_${$json.content_type}`;

// Check if cached
if ($executionMode === 'manual' || !$workflow.cache[cacheKey]) {
  // Fetch prompt
  const response = await $http.post('...select', { ... });
  $workflow.cache[cacheKey] = response.data;
}

return [{
  json: {
    prompt_data: $workflow.cache[cacheKey]
  }
}];
```

### 2. Rate Limiting

**Respetar límite de 120 req/min:**

```javascript
// Function Node - Delay between requests
const delay = (ms) => new Promise(resolve => setTimeout(resolve, ms));

const items = $input.all();
const results = [];

for (let i = 0; i < items.length; i++) {
  // Process item
  const result = await processItem(items[i]);
  results.push(result);

  // Wait 500ms between requests (max 120/min)
  if (i < items.length - 1) {
    await delay(500);
  }
}

return results;
```

### 3. Usar Batch Cuando Sea Posible

❌ **Ineficiente (100 peticiones):**
```
FOR EACH product (100 products)
  → POST /select (1 request per product)
  → OpenAI (1 request per product)
```

✅ **Eficiente (1 petición):**
```
→ POST /batch-select (1 request for 100 products)
→ Loop over results
  → OpenAI (still 100, but prompt selection is batched)
```

### 4. Manejo de Fallos Parciales

**En batch processing, algunos productos pueden fallar:**

```javascript
// Function Node - Filter successful results
const results = $json.results;

const successful = results.filter(r => r.success);
const failed = results.filter(r => !r.success);

if (failed.length > 0) {
  console.warn(`${failed.length} products failed prompt selection`);
  // Log failed inventaries for retry
}

return successful.map(r => ({
  json: {
    ...r.data,
    original_index: r.index
  }
}));
```

---

## 🎯 Plantilla de Workflow Completa

### Workflow: "AI Content Generator for PrestaShop"

**Descripción:** Workflow completo que escucha nuevos productos, selecciona el prompt adecuado, genera contenido con OpenAI y actualiza PrestaShop.

**Nodes:**

1. **Webhook Trigger**
   - Method: POST
   - Path: `/webhook/new-product`
   - Authentication: Header Auth (X-API-Key)

2. **Get Product Data**
   - HTTP Request: GET PrestaShop API

3. **Select Prompt**
   - HTTP Request: POST `/select`

4. **IF Node (Check if prompt found)**
   - Condition: `{{ $json.success }} === true`

5a. **Generate with OpenAI** (IF TRUE)
   - Model: gpt-4
   - Prompt: `{{ $json.data.prompt_text }}`

5b. **Use Fallback** (IF FALSE)
   - Set static fallback prompt

6. **Update Product**
   - HTTP Request: PUT PrestaShop API

7. **Notify Slack** (Success)
   - Message: "✅ Generated description for {{ $('Get Product Data').item.json.name }}"

8. **Error Handler** (On Error)
   - Send to Slack: "❌ Error: {{ $json.error }}"

---

## 📊 Monitoreo y Analytics

### Trackear Uso de Prompts

**Agregar logging en n8n:**

```javascript
// Function Node - Log usage
const logData = {
  timestamp: new Date().toISOString(),
  product_id: $json.product_id,
  supplier_id: $json.supplier_id,
  category_id: $json.category_id,
  content_type: $json.content_type,
  prompt_id: $json.data.prompt_id,
  prompt_scope: $json.data.scope,
  success: true
};

// Send to Google Sheets or Database
await $http.post('https://sheets.googleapis.com/...', logData);

return [$json];
```

### Métricas Útiles

- Prompts más usados
- Tiempo promedio de generación
- Tasa de éxito/fallo
- Content types más generados
- Proveedores con más contenido generado

---

## 🔐 Seguridad

### API Key Protection

```javascript
// NO hacer esto (expone API key en logs):
const apiKey = 'sk-1234567890abcdef';

// ✅ Usar variables de entorno:
const apiKey = $vars.OPENAI_API_KEY;
```

### Validación de Entrada

```javascript
// Function Node - Validate before API call
const required = ['supplier_id', 'category_id', 'content_type'];

for (const field of required) {
  if (!$json[field]) {
    throw new Error(`Missing required field: ${field}`);
  }
}

return [$json];
```

---

## 📚 Recursos Adicionales

### Endpoints Disponibles

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/health` | GET | Health check del servicio |
| `/select` | POST | Seleccionar prompt individual |
| `/batch-select` | POST | Seleccionar múltiples prompts (max 100) |
| `/explain` | GET | Debugging: explicar selección de prompt |

### Rate Limits

- **120 peticiones/minuto** por IP
- Sin autenticación requerida (público)
- Usar batch cuando sea posible

### Formato de Respuesta

Todos los endpoints devuelven JSON con estructura:
```json
{
  "success": true|false,
  "data": { ... },
  "message": "..." // (solo en errores)
}
```

---

## 🎓 Próximos Pasos

1. **Instalar** el workflow de ejemplo en n8n
2. **Configurar** las variables de entorno
3. **Probar** con un producto de prueba
4. **Monitorear** los resultados
5. **Optimizar** basándote en analytics

Para más información, consultar:
- [Documentación del Sistema de Selección de Prompts](./prompt-selection-system.md)
- [API Reference](./prompt-selection-system.md#-api-para-n8n)

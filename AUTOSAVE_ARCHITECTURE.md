# Auto-save Architecture - FASE 1

## 🏗️ Arquitectura General

```
┌─────────────────────────────────────────────────────────────────┐
│                    PÁGINA DE EDICIÓN                            │
│              /pages/{id}/edit (Blade View)                       │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │  Formulario                                             │    │
│  │  ┌─────────────────┐                                    │    │
│  │  │ Title Input     │ ──┐                                │    │
│  │  │ Slug Input      │   │  onChange Events               │    │
│  │  │ Content Editor  │ ──┼──→ JavaScript Listener        │    │
│  │  │ Status Select   │   │  (setTimeout/debounce)       │    │
│  │  │ SEO Fields      │ ──┘                                │    │
│  │  └─────────────────┘                                    │    │
│  └─────────────────────────────────────────────────────────┘    │
│                                                                   │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │ Indicador Auto-save                                     │    │
│  │ ┌──────────────────────────────────────────────────┐   │    │
│  │ │ [🔄 Auto-guardando...] (Azul) → [✓ Guardado]   │   │    │
│  │ │ (Verde) → Desaparece en 3s                       │   │    │
│  │ └──────────────────────────────────────────────────┘   │    │
│  └─────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
                              ↓
                         (DEBOUNCE 2s)
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                    AJAX REQUEST                                  │
│          PATCH /api/v1/pages/{id}/auto-save                     │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  Headers:                                                        │
│  - X-CSRF-TOKEN: [token]                                        │
│  - Content-Type: application/json                               │
│  - Accept: application/json                                     │
│                                                                  │
│  Body (JSON):                                                   │
│  {                                                              │
│    "title": "...",                                              │
│    "slug": "...",                                               │
│    "content": "...",                                            │
│    "description": "...",                                        │
│    "status": "draft",                                           │
│    "seo_title": "...",                                          │
│    "seo_description": "...",                                    │
│    "seo_keywords": "...",                                       │
│    "header_style": "...",                                       │
│    "seo_noindex": false                                         │
│  }                                                              │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│              PageAutoSaveController@save()                      │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  1. Autorizar:  authorize('update', $page)                      │
│  2. Validar:    Validar todos los campos                        │
│  3. Guardar:    PageAutoSaveService::saveAutoSave()             │
│  4. Responder:  JSON response con éxito/error                  │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│            PageAutoSaveService@saveAutoSave()                   │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  1. Procesar datos (filtrar nulos)                              │
│  2. Llamar: PageAutoSave::createOrUpdateDraft()                 │
│  3. Retornar instancia del modelo                               │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│          PageAutoSave::createOrUpdateDraft()                    │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  IF existe borrador anterior Y no expirado:                      │
│     UPDATE con nuevos datos (same query)                         │
│  ELSE:                                                           │
│     CREATE nuevo registro                                       │
│                                                                   │
│  Campos guardados:                                              │
│  - page_id, user_id                                            │
│  - data (JSON): todos los campos                               │
│  - content (LONGTEXT): contenido separado                      │
│  - status: estado de la página                                  │
│  - saved_at: timestamp ahora                                    │
│  - expires_at: now() + 24 horas                                │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                   BASE DE DATOS                                  │
│              page_auto_saves TABLE                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ┌─────────────────────────────────────────────────────┐       │
│  │ id | page_id | user_id | data      | saved_at      │       │
│  ├─────────────────────────────────────────────────────┤       │
│  │ 1  │ 42      │ 5       │ {...json} │ 14:32:43 ← ← │       │
│  │ 2  │ 42      │ 5       │ {...json} │ 14:32:47 ← ← │       │
│  │ 3  │ 42      │ 5       │ {...json} │ 14:32:51 ← ← │       │
│  │ 4  │ 43      │ 6       │ {...json} │ 14:33:02      │       │
│  │                                                      │       │
│  │ Cada UPDATE al mismo usuario/página actualiza      │       │
│  │ el último registro en lugar de crear duplicados    │       │
│  └─────────────────────────────────────────────────────┘       │
│                                                                   │
│  Índices:                                                       │
│  - page_id (búsqueda por página)                               │
│  - user_id (búsqueda por usuario)                              │
│  - saved_at (búsqueda temporal)                                │
│  - expires_at (limpieza automática)                            │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                  API RESPONSE (JSON)                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  {                                                              │
│    "success": true,                                            │
│    "message": "Borrador guardado automáticamente",             │
│    "data": {                                                   │
│      "saved_at": "2 seconds ago",                             │
│      "expires_at": "2026-02-29 14:32:45"                     │
│    }                                                           │
│  }                                                              │
│                                                                   │
│  O en caso de error:                                            │
│  {                                                              │
│    "success": false,                                           │
│    "message": "Error al guardar",                             │
│    "errors": { "title": ["El título es requerido"] }         │
│  }                                                              │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                   FRONTEND RESPONSE                              │
│            JavaScript Success/Error Handler                     │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  IF success:                                                    │
│     Update indicator: "✓ Guardado 14:32:45" (Verde)           │
│     setTimeout(3000) → Desaparecer                             │
│                                                                   │
│  IF error:                                                      │
│     Update indicator: "⚠️ Error al guardar" (Rojo)             │
│     setTimeout(5000) → Reintentar                              │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

---

## 📊 Flujo de Datos Completo

```
USUARIO DIGITA EN CAMPO
    ↓
[input event fired]
    ↓
JavaScript listener captura el evento
    ↓
clearTimeout(autoSaveTimeout)  ← Cancela anterior si existe
    ↓
setTimeout(performAutoSave, 2000)  ← Programa nuevo en 2s
    ↓
[si no hay más cambios en 2s]
    ↓
performAutoSave() ejecuta
    ↓
hasChanges() verifica si hay cambios reales
    ↓
$.ajax() → PATCH /api/v1/pages/{id}/auto-save
    ↓
PageAutoSaveController@save() recibe request
    ↓
validate() + authorize() + save()
    ↓
PageAutoSave::createOrUpdateDraft() en BD
    ↓
UPDATE/INSERT page_auto_saves table
    ↓
Retornar JSON response
    ↓
JavaScript recibe response
    ↓
Update indicator badge
    ↓
setTimeout(3000) → Fade out
    ↓
Esperar siguiente cambio...
```

---

## 🔄 Ciclo de Vida de un Borrador

```
┌─────────────────────────────┐
│   Usuario abre página       │
│   /pages/1/edit             │
└──────────────┬──────────────┘
               │
               ↓
        ┌──────────────────┐
        │ Usuario digita   │
        │ en cualquier     │
        │ campo            │
        └────────┬─────────┘
                 │
        [DEBOUNCE 2s]
                 ↓
        ┌──────────────────────┐
        │ performAutoSave()    │
        │ ejecuta              │
        └────────┬─────────────┘
                 │
                 ↓
        ┌──────────────────────────────┐
        │ AJAX POST a API              │
        │ page_auto_saves INSERT/UPDATE │
        └────────┬─────────────────────┘
                 │
                 ↓
        ┌──────────────────────────────┐
        │ Indicador:                    │
        │ "✓ Guardado 14:32:45"         │
        │ (por 3 segundos)              │
        └────────┬─────────────────────┘
                 │
                 ↓
        ┌──────────────────────────────┐
        │ Desaparece indicador         │
        │                              │
        │ Borrador guardado en BD:     │
        │ - expires_at: +24h           │
        │ - data: JSON con cambios     │
        │ - content: LONGTEXT          │
        └────────┬─────────────────────┘
                 │
        ┌────────┴──────────────────┐
        │ Esperar...                 │
        │ Próximo usuario input      │
        │ o 24h expiration           │
        │                            │
        └───────────────────────────┘
```

---

## 🗄️ Esquema de Base de Datos

```
page_auto_saves
┌──────────────────────────────────────────────────────────┐
│ Column         │ Type          │ Nullable │ Index        │
├────────────────┼───────────────┼──────────┼──────────────┤
│ id             │ BIGINT        │ NO       │ PRIMARY KEY  │
│ page_id        │ BIGINT UNSIGNED│NO       │ FK, INDEX    │
│ user_id        │ BIGINT UNSIGNED│NO       │ FK, INDEX    │
│ data           │ JSON          │ NO       │              │
│ status         │ VARCHAR(255)  │ NO       │              │
│ content        │ LONGTEXT      │ YES      │              │
│ saved_at       │ TIMESTAMP     │ NO       │ INDEX        │
│ expires_at     │ TIMESTAMP     │ YES      │ INDEX        │
│ created_at     │ TIMESTAMP     │ NO       │              │
│ updated_at     │ TIMESTAMP     │ NO       │              │
└──────────────────────────────────────────────────────────┘

Foreign Keys:
- page_id → pages(id) [ON DELETE CASCADE]
- user_id → users(id) [ON DELETE CASCADE]
```

---

## 📤 API Endpoints

### 1. SAVE AUTO-SAVE (PATCH)
```
PATCH /api/v1/pages/{page}/auto-save
Content-Type: application/json
X-CSRF-TOKEN: [token]

Request Body:
{
  "title": "Nuevo título",
  "slug": "nuevo-titulo",
  "content": "<p>Contenido...</p>",
  "description": "Desc",
  "status": "draft",
  "template": "default",
  "seo_title": "SEO Title",
  "seo_description": "SEO Desc",
  "seo_keywords": "keyword1, keyword2",
  "header_style": "header-style-1",
  "seo_noindex": false
}

Response (200 OK):
{
  "success": true,
  "message": "Borrador guardado automáticamente",
  "data": {
    "saved_at": "2 seconds ago",
    "expires_at": "2026-02-29 14:32:45"
  }
}
```

### 2. GET AUTO-SAVE (GET)
```
GET /api/v1/pages/{page}/auto-save
X-CSRF-TOKEN: [token]

Response (200 OK):
{
  "success": true,
  "message": "Borrador encontrado",
  "data": {
    "id": 1,
    "title": "Título guardado",
    "slug": "titulo-guardado",
    "content": "<p>Contenido...</p>",
    "description": "Descripción",
    "status": "draft",
    "saved_at": "2 seconds ago",
    "expires_at": "2026-02-29 14:32:45",
    "can_restore": true
  }
}
```

### 3. RESTORE FROM DRAFT (POST)
```
POST /api/v1/pages/{page}/auto-save/restore
X-CSRF-TOKEN: [token]

Response (200 OK):
{
  "success": true,
  "message": "Página restaurada desde borrador",
  "data": {
    "id": 1,
    "title": "Título restaurado",
    "updated_at": "2026-02-28T14:32:45Z"
  }
}

Response (410 Gone):
{
  "success": false,
  "message": "El borrador ha expirado"
}
```

### 4. DISCARD DRAFT (DELETE)
```
DELETE /api/v1/pages/{page}/auto-save
X-CSRF-TOKEN: [token]

Response (200 OK):
{
  "success": true,
  "message": "Borrador eliminado"
}
```

---

## 🔐 Security Considerations

```
┌─────────────────────────────────────────┐
│        SECURITY CHECKS                  │
├─────────────────────────────────────────┤
│                                         │
│ 1. AUTHENTICATION                       │
│    auth:sanctum middleware on all routes│
│    Must be logged in to use auto-save   │
│                                         │
│ 2. AUTHORIZATION                        │
│    authorize('update', $page)           │
│    User must have permission to edit    │
│    Cannot edit others' drafts           │
│                                         │
│ 3. VALIDATION                           │
│    Validate all input fields            │
│    Max 255 chars for title/slug         │
│    Max 500 for description/seo fields   │
│    Status must be: draft|published|...  │
│                                         │
│ 4. CSRF PROTECTION                      │
│    X-CSRF-TOKEN header required         │
│    All PATCH/POST/DELETE protected      │
│                                         │
│ 5. DATA SANITIZATION                    │
│    JSON escaped properly                │
│    HTML preserved in content field      │
│    No code injection possible           │
│                                         │
│ 6. EXPIRATION                           │
│    Drafts auto-delete after 24h         │
│    No old data accumulation             │
│                                         │
└─────────────────────────────────────────┘
```

---

## 📈 Performance Optimizations

```
DEBOUNCING (Client-side)
├─ 2 segundo delay antes de cada request
├─ clearTimeout() cancela requests anteriores
└─ Max 30 requests/min (con edición normal)

DATABASE INDEXES
├─ idx_page_id: Búsqueda rápida por página
├─ idx_user_id: Búsqueda por usuario
├─ idx_saved_at: Búsqueda temporal
└─ idx_expires_at: Limpieza eficiente

JSON STORAGE
├─ Flexible schema sin normalización
├─ Todos los campos en un registro
└─ No necesita JOINs en queries

CLEANUP TASK
├─ Daily a las 03:00 (horario bajo)
├─ Elimina >= 24h old records
└─ Mantiene tabla pequeña

CACHING
├─ No hay cachés en auto-saves
├─ Siempre get latest from DB
└─ Pero es rápido con índices
```

---

## 🎯 Conclusión Arquitectónica

**El sistema de auto-save está diseñado para:**

✅ **Transparencia**: Funciona sin que el usuario haga nada
✅ **Confiabilidad**: No pierde datos incluso con desconexiones
✅ **Performance**: Debouncing previene sobrecarga
✅ **Seguridad**: Autorización y validación en cada step
✅ **Escalabilidad**: Índices y limpieza automática

**Próxima fase**: WebSocket para multi-usuario sync

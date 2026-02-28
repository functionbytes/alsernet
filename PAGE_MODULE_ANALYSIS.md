# Análisis Completo: Módulo Page - Edición en Tiempo Real

## 📊 ESTADO ACTUAL DEL MÓDULO PAGE

### ✅ QUE YA EXISTE (Arquitectura Solida)

#### 1. **Backend Completo**
- ✅ Modelo `Page` con:
  - Versionado (Trait `Versionable`)
  - SEO completo (Trait `HasSeo`)
  - Media Library (imágenes destacadas, galerías)
  - Soft deletes
  - Estados: draft, published, pending
  - Publicación programada (publish_at, unpublish_at)

- ✅ Servicio `PageService`:
  - CRUD completo
  - Manejo de slug único
  - Duplicación de páginas
  - Publicación/despublicación

- ✅ Controladores:
  - `PageController`: CRUD web
  - `PageCacheDashboardController`: Dashboard de caché
  - `PageVersionController`: Gestión de versiones
  - `PreviewController`: Previews con tokens
  - `PublicController`: Vista pública
  - `SearchController`: Búsqueda FULLTEXT

#### 2. **Frontend Robusto**
- ✅ Editor TinyMCE v6 integrado
- ✅ Upload de imágenes inline
- ✅ UI Blocks (Shortcodes) con modal
- ✅ Selector de imágenes Media
- ✅ Edición de SEO (colapsable)
- ✅ Vista previa tipo Google SERP
- ✅ Contadores de caracteres
- ✅ Preview tokens (24h)
- ✅ Información de página (creado, modificado, autor)

#### 3. **Features Avanzadas**
- ✅ Versionado de páginas con comparación
- ✅ Caché de páginas con diferentes drivers
- ✅ Publicación programada
- ✅ Búsqueda FULLTEXT con fallback LIKE
- ✅ Policies de autorización
- ✅ Activity logging
- ✅ Soft delete + force delete

#### 4. **API RESTful**
- ✅ Rutas públicas (sin auth)
- ✅ Rutas protegidas (auth:sanctum)
- ✅ Publicación/despublicación vía API
- ✅ Duplicación vía API
- ✅ Búsqueda rápida (quickSearch)

---

## ❌ QUE FALTA PARA EDICIÓN EN TIEMPO REAL

### 1. **Comunicación Bidireccional**
- ❌ WebSocket / Server-Sent Events (Reverb disponible pero no usado)
- ❌ Broadcasting de cambios entre usuarios
- ❌ Notificaciones en tiempo real
- ❌ Detección de cambios externos

### 2. **Auto-guardado**
- ❌ Auto-save cada X segundos (debouncing)
- ❌ Guardado de borradores automáticos
- ❌ Indicador visual de estado de guardado
- ❌ Recovery de borradores perdidos

### 3. **Synchronización Multi-usuario**
- ❌ Bloqueos de edición (locks)
- ❌ Detección de conflictos
- ❌ Merge de cambios automático
- ❌ Historial de cambios en tiempo real
- ❌ Indicadores de quién está editando
- ❌ Cursor compartido (remote cursors)

### 4. **API Endpoints Parciales**
- ❌ PATCH /api/pages/{page}/content (actualizar solo contenido)
- ❌ PATCH /api/pages/{page}/metadata (título, slug, description)
- ❌ PATCH /api/pages/{page}/seo (campos SEO)
- ❌ GET /api/pages/{page}/locks (obtener locks)
- ❌ POST /api/pages/{page}/locks (crear lock)
- ❌ DELETE /api/pages/{page}/locks (liberar lock)
- ❌ GET /api/pages/{page}/drafts (historial de borradores)

### 5. **Tablas de Base de Datos**
- ❌ `page_locks`: Para bloqueos de edición
- ❌ `page_draft_changes`: Para historial de cambios
- ❌ Índices de performance

### 6. **Servicios**
- ❌ `PageLockService`: Gestión de locks
- ❌ `PageDraftService`: Gestión de borradores
- ❌ `PageRealtimeService`: Broadcasting

---

## 🎯 ESTRATEGIA DE IMPLEMENTACIÓN

### FASE 1: Fundamentos (1-2 días)
1. **Crear tablas de base de datos**
   ```
   page_locks (id, page_id, user_id, locked_at, expires_at)
   page_draft_changes (id, page_id, user_id, field, old_value, new_value, created_at)
   ```

2. **Crear Servicios**
   - `PageLockService`: Adquirir/liberar locks
   - `PageDraftService`: Guardar cambios automáticos
   - Modelo `PageDraft`: Para almacenar borradores

3. **Crear API Endpoints**
   - POST   /api/pages/{page}/locks - crear lock
   - DELETE /api/pages/{page}/locks/{lock} - liberar lock
   - GET    /api/pages/{page}/locks - obtener locks activos
   - PATCH  /api/pages/{page}/auto-save - guardar automático

### FASE 2: Frontend (1-2 días)
1. **Inicializar Reverb WebSocket** en la vista edit
2. **Debouncing de cambios**:
   ```javascript
   - Esperar 2s después de último keyup
   - POST a /api/pages/{page}/auto-save
   - Mostrar: "Guardando..." → "Guardado" ✓
   ```

3. **Mostrar otros usuarios editando**:
   - Escuchar WebSocket `page.{page_id}.locked`
   - Mostrar alertas: "Juan está editando esta página"

4. **Indicadores visuales**:
   - Estado: Editando, Guardando, Guardado, Error
   - Botón de guardado deshabilitado durante guardado
   - Spinner de carga

### FASE 3: Broadcasting (1 día)
1. **Configurar Reverb** en `bootstrap/app.php`
2. **Crear Events**:
   - `PageLocked`
   - `PageUnlocked`
   - `PageAutoSaved`
   - `PageChangedExternally`

3. **Escuchar en el frontend** y actualizar UI

### FASE 4: Detección de Conflictos (1 día)
1. **Comparar versiones**:
   - Si contenido cambió externamente → mostrar modal
   - Opciones: "Mantener mío", "Usar el nuevo", "Ver diferencias"

2. **Versionado automático** en cada auto-save

---

## 📈 MEJORAS RECOMENDADAS

### Corto Plazo (Essencial)
1. ✅ Auto-save cada 30s (sin WebSocket)
2. ✅ Indicador visual de guardado
3. ✅ Guardar borradores en tabla temporal

### Mediano Plazo (Importante)
1. 🔄 WebSocket para mostrar otros editores
2. 🔄 Bloqueos de edición (locks)
3. 🔄 Historial de cambios en tiempo real

### Largo Plazo (Nice to have)
1. 📡 Cursores compartidos (remote cursors)
2. 📡 Coloreado de usuarios en tiempo real
3. 📡 Chat integrado en editor
4. 📡 Undo/Redo distribuido

---

## 🔧 RECOMENDACIÓN FINAL

### Para empezar AHORA (sin complejidad WebSocket):
**Implementar Auto-save básico** (2-3 horas):
```javascript
// En edit.blade.php:
var autoSaveTimer;
var contentChanged = false;

$('#content, #title, #slug').on('input', function() {
    contentChanged = true;
    clearTimeout(autoSaveTimer);

    autoSaveTimer = setTimeout(function() {
        $.ajax({
            url: '/api/v1/pages/' + pageId + '/auto-save',
            method: 'PATCH',
            data: {
                title: $('#title').val(),
                content: $('#content').val(),
                slug: $('#slug').val(),
                // ... otros campos
            },
            success: function() {
                showIndicator('Guardado ✓', 'success');
            }
        });
    }, 2000); // 2 segundos de debounce
});
```

### Luego escalar a WebSocket (día 2+):
```php
// PageUpdated event
class PageUpdated implements ShouldBroadcast {
    public function broadcastOn() {
        return new Channel('page.'.$this->page->id);
    }
}
```

---

## 📋 CHECKLIST DE IMPLEMENTACIÓN

### Auto-save Básico
- [ ] Crear endpoint PATCH `/api/pages/{page}/auto-save`
- [ ] Agregar debouncing en frontend
- [ ] Mostrar indicador de guardado
- [ ] Guardar a tabla `page_auto_saves` (temporal)

### Locks (Edición exclusiva)
- [ ] Crear tabla `page_locks`
- [ ] `PageLockService::acquire()` y `release()`
- [ ] Mostrar alerta cuando otro usuario está editando
- [ ] Impedir edición en campo de contenido

### WebSocket (Tiempo Real)
- [ ] Configurar Reverb
- [ ] Crear Events para broadcast
- [ ] Escuchar en frontend
- [ ] Actualizar UI en tiempo real

---

## 🚀 SIGUIENTE PASO

¿Quieres que implemente:
1. **Auto-save básico** (rápido, sin WebSocket)
2. **Auto-save + Locks** (medio plazo)
3. **Auto-save + Locks + WebSocket** (completo)

Recomiendo empezar con **opción 1** hoy y escalar después.

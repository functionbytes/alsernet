# Refactorización de Rutas del Módulo de Documentos

## 📋 Resumen Ejecutivo

Se ha completado la **migración de rutas duplicadas a la API genérica** para eliminar redundancia y centralizar la lógica de documentos.

### Cambios Realizados

✅ **3 rutas nuevas agregadas a API**
✅ **6 rutas duplicadas eliminadas de archivos de roles**
✅ **Documentación actualizada en comentarios**

---

## 🔧 Cambios en `Modules/Documents/routes/api.php`

### Rutas Agregadas

Se agregaron las siguientes rutas al bloque autenticado (`middleware(['auth', 'throttle:60,1'])`):

```php
// Document operations (moved from role-specific routes)
Route::post('/sync-fields', [DocumentsController::class, 'syncAllDocumentFields'])
    ->name('api.documents.sync-fields');

Route::get('/{uid}/state', [DocumentsController::class, 'getDocumentState'])
    ->name('api.documents.state');

Route::delete('/{uid}', [DocumentsController::class, 'deleteSingleDocument'])
    ->name('api.documents.delete');
```

**Ubicación:** Líneas 68-71

---

## 🧹 Rutas Eliminadas de Archivos de Roles

### De `accountings.php`, `administratives.php`, `weapons.php`:

Las siguientes rutas fueron **eliminadas** porque ahora existen en la API:

#### 1. **resend-reminder**
```php
// ❌ ELIMINADO
Route::post('/{uid}/resend-reminder', [DocumentsController::class, 'resendReminderEmail'])
    ->name('documents.resend-reminder');

// ✅ USAR EN SU LUGAR
route('api.documents.resend-reminder')
```

#### 2. **confirm-upload**
```php
// ❌ ELIMINADO
Route::post('/{uid}/confirm-upload', [DocumentsController::class, 'confirmDocumentUpload'])
    ->name('documents.confirm-upload');

// ✅ USAR EN SU LUGAR
route('api.documents.confirm-upload')
```

#### 3. **sync/all**
```php
// ❌ ELIMINADO
Route::get('/sync/all', [DocumentsController::class, 'syncAllDocuments'])
    ->name('documents.sync.all');

// ✅ USAR EN SU LUGAR (public API)
GET /api/documents/sync/all
```

#### 4. **sync-fields**
```php
// ❌ ELIMINADO
Route::post('/sync/fields', [DocumentsController::class, 'syncAllDocumentFields'])
    ->name('documents.sync-fields');

// ✅ USAR EN SU LUGAR
route('api.documents.sync-fields')
```

#### 5. **document-state**
```php
// ❌ ELIMINADO
Route::get('/{uid}/document-state', [DocumentsController::class, 'getDocumentState'])
    ->name('documents.state');

// ✅ USAR EN SU LUGAR
route('api.documents.state', ['uid' => $documentUid])
```

#### 6. **delete-single**
```php
// ❌ ELIMINADO
Route::post('/{uid}/delete-single', [DocumentsController::class, 'deleteSingleDocument'])
    ->name('documents.delete-single');

// ✅ USAR EN SU LUGAR (ahora es DELETE, no POST)
route('api.documents.delete', ['uid' => $documentUid])
// Método HTTP: DELETE (cambió de POST a DELETE para seguir convención RESTful)
```

---

## ✅ Rutas Mantenidas en Archivos de Roles

Las siguientes rutas **permanecen en los archivos de roles** porque son específicas de la interfaz web:

### 1. **Gestión de Archivos**
```php
Route::post('/files', [DocumentsController::class, 'storeFiles'])
    ->name('documents.files');
Route::get('/delete/files/{id}', [DocumentsController::class, 'deleteFiles'])
    ->name('documents.files.delete');
Route::get('/get/files/{id}', [DocumentsController::class, 'getFiles'])
    ->name('documents.files.get');
```
**Razón:** Manejo de uploads desde formularios web con CSRF

### 2. **Páginas de Visualización**
```php
Route::get('/summary/{id}', [DocumentsController::class, 'summary'])
    ->name('documents.summary');
Route::get('/manage/{uid}', [DocumentsController::class, 'manage'])
    ->name('documents.manage');
```
**Razón:** Renderizan vistas Blade completas

### 3. **Operaciones Administrativas Web**
```php
Route::post('/{uid}/admin-upload', [DocumentsController::class, 'adminUploadDocument'])
    ->name('documents.admin-upload');
Route::get('/{uid}/refresh-section', [DocumentsController::class, 'refreshDocumentsSection'])
    ->name('documents.refresh-section');
```
**Razón:** Específicas del panel web con permisos de sesión

### 4. **Páginas de Importación** (solo en accountings y weapons)
```php
Route::get('/import', [DocumentsController::class, 'importIndex'])
    ->name('documents.import');
Route::get('/import/api', [DocumentsController::class, 'importApi'])
    ->name('documents.import.api');
Route::get('/import/erp', [DocumentsController::class, 'importErp'])
    ->name('documents.import.erp');
```
**Razón:** Formularios de importación con UI

### 5. **Historial de Emails** (solo en accountings y weapons)
```php
Route::get('/{uid}/emails', [DocumentsController::class, 'emailHistory'])
    ->name('documents.emails');
Route::get('/emails/preview/{mailUid}', [DocumentsController::class, 'emailPreview'])
    ->name('documents.emails.preview');
```
**Razón:** Vistas de historial y preview de emails

### 6. **Sincronización desde ERP** (solo en accountings y weapons)
```php
Route::post('/sync/by-order', [DocumentsController::class, 'syncByOrderId'])
    ->name('documents.sync.by-order');
Route::get('/sync/by-order', [DocumentsController::class, 'syncByOrderId'])
    ->name('documents.sync.by-order.query');
Route::get('/sync/from-erp', [DocumentsController::class, 'syncFromErp'])
    ->name('documents.sync.from-erp.query');
Route::post('/sync/from-erp', [DocumentsController::class, 'syncFromErp'])
    ->name('documents.sync.from-erp');
```
**Razón:** Formularios de sincronización específicos del panel

---

## 📝 Comentarios de Documentación Actualizados

En los tres archivos de rutas (`accountings.php`, `administratives.php`, `weapons.php`) se agregó un bloque de comentarios que documenta las rutas migradas a API:

```php
/*
 * NOTA: Las siguientes rutas ahora se manejan via API genérica en /api/documents
 * - resend-reminder → route('api.documents.resend-reminder')
 * - confirm-upload → route('api.documents.confirm-upload')
 * - sync/all → route('api.documents.sync.all') (public API)
 * - sync-fields → route('api.documents.sync-fields')
 * - document-state → route('api.documents.state')
 * - delete-single → route('api.documents.delete')
 * - send-notification, send-reminder, send-missing → route('api.documents.*')
 * - send-custom-email, send-approval, send-rejection → route('api.documents.*')
 * - add-note, update-note, delete-note → route('api.documents.notes.*')
 * - refresh-action-history → route('api.documents.action-history')
 * - missing-documents → route('api.documents.request-missing')
 */
```

**Propósito:** Guía para desarrolladores sobre dónde encontrar las rutas migradas

---

## 🎯 Estructura Final de Rutas

### API Routes (`Modules/Documents/routes/api.php`)

#### Public API (sin autenticación)
```
POST   /api/documents/                                  → process
POST   /api/documents/webhooks/prestashop/order-paid    → prestashopOrderPaid
POST   /api/documents/resend-reminder                   → resendDocumentReminder
POST   /api/documents/confirm-upload                    → confirmDocumentUpload
GET    /api/documents/order/data/{order_id}             → getOrderData
POST   /api/documents/fill-order-data                   → fillDocumentWithOrderData
GET    /api/documents/sync/all                          → syncAllDocumentsWithOrders
GET    /api/documents/sync/by-query                     → syncDocumentsByOrderQuery
POST   /api/documents/sync/by-order                     → syncDocumentByOrderId
```

#### Authenticated API (requiere `auth` middleware)
```
POST   /api/documents/{uid}/approve-stage               → approveStage
POST   /api/documents/{uid}/reject-stage                → rejectStage
POST   /api/documents/{uid}/send-approval               → sendApproval
POST   /api/documents/{uid}/send-rejection              → sendRejection
POST   /api/documents/{uid}/send-custom-email           → sendCustomEmail
POST   /api/documents/{uid}/send-reminder               → sendReminder
POST   /api/documents/{uid}/request-initial-documents   → requestInitialDocuments
POST   /api/documents/{uid}/request-missing-documents   → requestMissingDocuments
POST   /api/documents/{uid}/notes                       → addNote
PUT    /api/documents/notes/{noteId}                    → updateNote
DELETE /api/documents/notes/{noteId}                    → deleteNote
POST   /api/documents/{uid}/attachments                 → uploadAttachment
DELETE /api/documents/attachments/{attachmentId}        → deleteAttachment
GET    /api/documents/{uid}/action-history              → getActionHistory
GET    /api/documents/{uid}/email-history               → getEmailHistory
GET    /api/documents/{uid}/status-timeline             → getStatusTimeline
GET    /api/documents/{uid}/next-stage-info             → getNextStageInfo
GET    /api/documents/custom-email-template             → getCustomEmailTemplate

🆕 POST   /api/documents/sync-fields                    → syncAllDocumentFields
🆕 GET    /api/documents/{uid}/state                    → getDocumentState
🆕 DELETE /api/documents/{uid}                          → deleteSingleDocument
```

### Web Routes (por rol)

Cada archivo de rol (`accountings.php`, `administratives.php`, `weapons.php`) mantiene:

```
GET    /{role}/documents/                           → index
GET    /{role}/documents/pending                    → pending
GET    /{role}/documents/create                     → create
POST   /{role}/documents/store                      → store
POST   /{role}/documents/update                     → update
GET    /{role}/documents/edit/{slack}               → edit
GET    /{role}/documents/show/{slack}               → show
GET    /{role}/documents/destroy/{slack}            → destroy
POST   /{role}/documents/files                      → storeFiles
GET    /{role}/documents/delete/files/{id}          → deleteFiles
GET    /{role}/documents/get/files/{id}             → getFiles
GET    /{role}/documents/summary/{id}               → summary
GET    /{role}/documents/manage/{uid}               → manage
POST   /{role}/documents/{uid}/admin-upload         → adminUploadDocument
GET    /{role}/documents/{uid}/refresh-section      → refreshDocumentsSection
```

**Solo en `accountings.php` y `weapons.php`:**
```
POST   /{role}/documents/sync/by-order              → syncByOrderId
GET    /{role}/documents/sync/by-order              → syncByOrderId
GET    /{role}/documents/sync/from-erp              → syncFromErp
POST   /{role}/documents/sync/from-erp              → syncFromErp
GET    /{role}/documents/import                     → importIndex
GET    /{role}/documents/import/api                 → importApi
GET    /{role}/documents/import/erp                 → importErp
GET    /{role}/documents/{uid}/emails               → emailHistory
GET    /{role}/documents/emails/preview/{mailUid}   → emailPreview
```

---

## 🔄 Migración de Código Frontend

### Cambios Necesarios en Blade/JavaScript

#### Antes (ruta web duplicada):
```javascript
// ❌ Antiguo - usando ruta web
$.post("{{ route('accounting.documents.resend-reminder', $document->uid) }}", ...)
$.post("{{ route('administrative.documents.sync-fields') }}", ...)
$.get("{{ route('weapons.documents.state', $document->uid) }}", ...)
```

#### Después (ruta API):
```javascript
// ✅ Nuevo - usando ruta API
$.post("{{ route('api.documents.resend-reminder') }}", {
    uid: "{{ $document->uid }}"
}, ...)

$.post("{{ route('api.documents.sync-fields') }}", ...)

$.get("{{ route('api.documents.state', ['uid' => $document->uid]) }}", ...)
```

#### Importante: Cambio de DELETE
```javascript
// ❌ Antiguo - POST
$.post("{{ route('accounting.documents.delete-single', $document->uid) }}", ...)

// ✅ Nuevo - DELETE (RESTful)
$.ajax({
    url: "{{ route('api.documents.delete', ['uid' => $document->uid]) }}",
    method: 'DELETE',
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    },
    success: function(response) { ... }
});
```

---

## ⚠️ Cambios Importantes

### 1. Método HTTP Cambiado: `delete-single`

**Antes:**
- Método: `POST`
- Ruta: `/{role}/documents/{uid}/delete-single`

**Ahora:**
- Método: `DELETE` (convención RESTful)
- Ruta: `/api/documents/{uid}`

**Impacto:** Debes actualizar el frontend para usar método DELETE

### 2. Nombres de Rutas Cambiados

| Antiguo | Nuevo |
|---------|-------|
| `accounting.documents.resend-reminder` | `api.documents.resend-reminder` |
| `administrative.documents.confirm-upload` | `api.documents.confirm-upload` |
| `weapons.documents.sync.all` | *Public API* `/api/documents/sync/all` |
| `accounting.documents.sync-fields` | `api.documents.sync-fields` |
| `administrative.documents.state` | `api.documents.state` |
| `weapons.documents.delete-single` | `api.documents.delete` |

### 3. Autenticación

Todas las nuevas rutas API requieren autenticación (`auth` middleware).

**Web:** Usa autenticación de sesión (ya existente)
**API externa:** Necesitaría implementar Sanctum tokens

---

## 📊 Estadísticas

- **Rutas agregadas a API:** 3
- **Rutas eliminadas (duplicadas):** 6 × 3 roles = 18 rutas eliminadas
- **Rutas mantenidas en web:** ~15 por rol (varies)
- **Archivos modificados:** 4
  - `Modules/Documents/routes/api.php`
  - `Modules/Documents/routes/accountings.php`
  - `Modules/Documents/routes/administratives.php`
  - `Modules/Documents/routes/weapons.php`

---

## ✅ Próximos Pasos

1. **Revisar el frontend** para actualizar las llamadas a las rutas migradas
2. **Buscar y reemplazar** en vistas Blade:
   ```bash
   grep -r "documents.resend-reminder" modules/Document/resources/views/
   grep -r "documents.confirm-upload" modules/Document/resources/views/
   grep -r "documents.sync-fields" modules/Document/resources/views/
   grep -r "documents.state" modules/Document/resources/views/
   grep -r "documents.delete-single" modules/Document/resources/views/
   grep -r "documents.sync.all" modules/Document/resources/views/
   ```

3. **Actualizar archivos JavaScript/Vue** que usen estas rutas

4. **Probar todas las funcionalidades** afectadas:
   - Reenvío de recordatorios
   - Confirmación de uploads
   - Sincronización de campos
   - Consulta de estado
   - Eliminación de documentos
   - Sincronización masiva

5. **Opcional: Agregar rate limiting** específico a las nuevas rutas si es necesario

---

## 🎓 Beneficios de Esta Refactorización

### 1. **Eliminación de Duplicación**
- Antes: 6 rutas × 3 roles = 18 rutas duplicadas
- Ahora: 6 rutas únicas en API

### 2. **Centralización de Lógica**
- Un solo controlador maneja la lógica
- Más fácil de mantener y debuggear

### 3. **Reutilización**
- Las rutas API pueden usarse desde:
  - Panel web (todos los roles)
  - Aplicaciones externas
  - Webhooks
  - n8n workflows
  - Cron jobs

### 4. **Consistencia RESTful**
- Uso correcto de métodos HTTP (GET, POST, DELETE)
- Nombres de rutas semánticos

### 5. **Escalabilidad**
- Fácil agregar nuevos roles sin duplicar rutas
- Fácil agregar autenticación API (Sanctum) en el futuro

---

## 📚 Referencias

- **Análisis original:** `docs/backend/document-routes-analysis.md`
- **Rutas API:** `Modules/Documents/routes/api.php`
- **Rutas Web:** `Modules/Documents/routes/{role}.php`

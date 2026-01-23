# Guía de Prueba Manual - Dashboard de Fallos de Sincronización

## Descripción General

Esta guía describe los pasos para probar manualmente el dashboard de fallos de sincronización del módulo Supplier (`settings.suppliers.sync-failures.index`).

**Ruta**: `/settings/suppliers/sync-failures`
**Controlador**: `SupplierSyncFailuresController`
**Vista**: `modules/Supplier/resources/views/settings/sync-failures/index.blade.php`

---

## Pre-requisitos

### 1. Configuración del Entorno

```bash
# Verificar que las migraciones están aplicadas
php artisan migrate:status | grep supplier

# Verificar que el servicio ErpSyncService está registrado
php artisan tinker
>>> app(Modules\Supplier\Services\ErpSyncService::class)
```

### 2. Usuario con Permisos

- Debes estar autenticado como usuario con rol `super-admin` o permisos adecuados
- El middleware de la ruta requiere: `['web', 'auth']`

### 3. Datos de Prueba (Opcional)

Puedes generar datos de prueba usando tinker:

```php
php artisan tinker

// Crear algunos fallos de sincronización de prueba
use Modules\Supplier\Models\SupplierSyncFailure;

SupplierSyncFailure::create([
    'sync_type' => 'price',
    'supplier_id' => 1,
    'erp_id' => 'ERP-001',
    'error_message' => 'Connection timeout to ERP server',
    'error_details' => json_encode(['host' => 'erp.example.com', 'port' => 5432]),
    'retry_count' => 0,
    'max_retries' => 3,
    'last_retry_at' => null,
    'resolved_at' => null,
]);

SupplierSyncFailure::create([
    'sync_type' => 'product',
    'supplier_id' => 2,
    'erp_id' => 'ERP-002',
    'error_message' => 'Invalid product data format',
    'error_details' => json_encode(['field' => 'price', 'value' => 'invalid']),
    'retry_count' => 2,
    'max_retries' => 3,
    'last_retry_at' => now()->subHours(2),
    'resolved_at' => null,
]);

// Crear algunos conflictos de prueba
use Modules\Supplier\Models\SupplierSyncConflict;

SupplierSyncConflict::create([
    'entity_type' => 'price',
    'entity_id' => 1,
    'erp_id' => 123,
    'resolution_strategy' => 'erp_wins',
    'local_data' => json_encode(['price' => 100.00, 'currency' => 'USD']),
    'erp_data' => json_encode(['price' => 105.00, 'currency' => 'USD']),
    'resolved_data' => json_encode(['price' => 105.00, 'currency' => 'USD']),
    'changed_fields' => json_encode(['price']),
    'conflict_detected_at' => now()->subHours(5),
    'resolved_at' => now()->subHours(4),
]);

SupplierSyncConflict::create([
    'entity_type' => 'product',
    'entity_id' => 2,
    'erp_id' => 456,
    'resolution_strategy' => 'erp_wins',
    'local_data' => json_encode(['name' => 'Product A', 'stock' => 50]),
    'erp_data' => json_encode(['name' => 'Product A', 'stock' => 45]),
    'resolved_data' => null,
    'changed_fields' => json_encode(['stock']),
    'conflict_detected_at' => now()->subMinutes(30),
    'resolved_at' => null,
]);
```

---

## Casos de Prueba

### Caso 1: Acceso al Dashboard

**Objetivo**: Verificar que el dashboard carga correctamente.

**Pasos**:
1. Navegar a: `http://tu-dominio.local/settings/suppliers/sync-failures`
2. Verificar que la página carga sin errores
3. Verificar que aparecen las 4 tarjetas de estadísticas:
   - Total Failures
   - Retryable Failures
   - Total Conflicts
   - Unresolved Conflicts

**Resultado Esperado**:
- ✅ Página carga correctamente
- ✅ Las 4 tarjetas muestran números correctos
- ✅ Navegación por tabs está visible
- ✅ Animaciones de las tarjetas funcionan al hacer hover

**Errores Comunes**:
- 404 Not Found → Verificar que la ruta está registrada en `web.php`
- 500 Internal Server Error → Revisar logs de Laravel: `tail -f storage/logs/laravel.log`
- Tarjetas sin datos → Verificar que hay registros en la base de datos

---

### Caso 2: Tab "Fallos de Sincronización"

**Objetivo**: Verificar que la tabla de fallos muestra datos correctos.

**Pasos**:
1. Hacer clic en el tab "Fallos de Sincronización" (debería estar activo por defecto)
2. Verificar que la tabla muestra las siguientes columnas:
   - ID
   - Tipo (con badge de color)
   - Supplier ID
   - ERP ID
   - Error Message
   - Reintentos (formato: 0/3)
   - Última vez (formato: hace X horas)
   - Acciones (botones Retry y Delete)
3. Verificar que el checkbox "Seleccionar todo" aparece en el header
4. Verificar que los botones de acciones bulk aparecen cuando se seleccionan registros

**Resultado Esperado**:
- ✅ Tabla muestra datos de `SupplierSyncFailure`
- ✅ Los badges de tipo tienen colores correctos:
  - `price` → badge azul
  - `product` → badge verde
  - `provider` → badge naranja
- ✅ Formato de fechas es legible (ej: "hace 2 horas")
- ✅ Botones de acción están habilitados para registros reintentables
- ✅ Paginación funciona si hay más de 15 registros

**Errores Comunes**:
- Tabla vacía → Verificar que hay registros con `resolved_at = null`
- Formato de fecha incorrecto → Verificar Carbon locale: `php artisan tinker >>> app()->getLocale()`
- Botones no aparecen → Verificar JavaScript cargado: F12 → Console

---

### Caso 3: Retry Individual

**Objetivo**: Verificar que se puede reintentar un fallo específico.

**Pasos**:
1. Hacer clic en el botón "Retry" (verde) de un registro específico
2. Observar el loader que aparece en el botón
3. Esperar la respuesta AJAX

**Resultado Esperado (Éxito)**:
- ✅ Aparece notificación: "Fallo reintentado exitosamente"
- ✅ El registro desaparece de la tabla (si se resolvió)
- ✅ Las estadísticas se actualizan automáticamente
- ✅ `retry_count` se incrementa en la base de datos

**Resultado Esperado (Fallo)**:
- ✅ Aparece notificación de error con el mensaje del ERP
- ✅ El registro permanece en la tabla
- ✅ `retry_count` se incrementa
- ✅ Si `retry_count >= max_retries`, el botón se deshabilita

**Verificación en Base de Datos**:
```php
php artisan tinker
>>> use Modules\Supplier\Models\SupplierSyncFailure;
>>> $failure = SupplierSyncFailure::find(1);
>>> $failure->retry_count; // Debe haber incrementado
>>> $failure->last_retry_at; // Debe ser reciente
```

**Errores Comunes**:
- 500 Error → Verificar que el `ErpSyncService` está configurado correctamente
- Timeout → Verificar conexión con el servidor ERP
- No hay feedback → Revisar la consola JavaScript (F12)

---

### Caso 4: Delete Individual

**Objetivo**: Verificar que se puede eliminar un fallo específico.

**Pasos**:
1. Hacer clic en el botón "Delete" (rojo) de un registro específico
2. Confirmar la acción en el popup de confirmación del navegador
3. Esperar la respuesta AJAX

**Resultado Esperado**:
- ✅ Aparece notificación: "Fallo eliminado exitosamente"
- ✅ El registro desaparece de la tabla
- ✅ Las estadísticas se actualizan
- ✅ El registro se elimina de la base de datos (soft delete si está configurado)

**Verificación en Base de Datos**:
```php
php artisan tinker
>>> use Modules\Supplier\Models\SupplierSyncFailure;
>>> SupplierSyncFailure::withTrashed()->find(1); // Si usa soft delete
>>> SupplierSyncFailure::find(1); // Debe retornar null si se eliminó
```

**Errores Comunes**:
- No se elimina → Verificar que el método `destroy()` del controlador funciona
- Error de permisos → Verificar que el usuario tiene permisos para eliminar

---

### Caso 5: Acciones Bulk (Retry All)

**Objetivo**: Verificar que se pueden reintentar múltiples fallos a la vez.

**Pasos**:
1. Seleccionar múltiples registros usando los checkboxes
2. Hacer clic en el botón "Retry Selected" que aparece arriba de la tabla
3. Confirmar la acción
4. Esperar la respuesta AJAX

**Resultado Esperado**:
- ✅ Aparece notificación: "X fallos reintentados exitosamente"
- ✅ Los registros procesados desaparecen o se actualizan
- ✅ Las estadísticas se actualizan
- ✅ Solo se reintentan los que están dentro del límite de `max_retries`

**Verificación en Base de Datos**:
```php
php artisan tinker
>>> use Modules\Supplier\Models\SupplierSyncFailure;
>>> SupplierSyncFailure::whereIn('id', [1, 2, 3])->get(['retry_count', 'last_retry_at']);
```

**Errores Comunes**:
- Solo se procesa uno → Verificar que el método `bulkRetry()` recibe todos los IDs
- Timeout → Si hay muchos registros, puede tardar. Considerar queue jobs.

---

### Caso 6: Acciones Bulk (Delete All)

**Objetivo**: Verificar que se pueden eliminar múltiples fallos a la vez.

**Pasos**:
1. Seleccionar múltiples registros usando los checkboxes
2. Hacer clic en el botón "Delete Selected" (rojo)
3. Confirmar la acción en el popup de confirmación
4. Esperar la respuesta AJAX

**Resultado Esperado**:
- ✅ Aparece notificación: "X fallos eliminados exitosamente"
- ✅ Los registros desaparecen de la tabla
- ✅ Las estadísticas se actualizan

**Errores Comunes**:
- Solo se elimina uno → Verificar que el método `bulkDestroy()` recibe todos los IDs
- Error de permisos → Verificar permisos del usuario

---

### Caso 7: Tab "Conflictos Detectados"

**Objetivo**: Verificar que la tabla de conflictos muestra datos correctos.

**Pasos**:
1. Hacer clic en el tab "Conflictos Detectados"
2. Verificar que la tabla muestra las siguientes columnas:
   - ID
   - Tipo
   - Entity ID
   - Estrategia (badge)
   - Detectado (fecha/hora)
   - Resuelto (fecha/hora o "Pendiente")
   - Estado (badge: Resolved/Unresolved)
   - Ver detalles (botón)
3. Verificar que hay registros de conflictos

**Resultado Esperado**:
- ✅ Tabla muestra datos de `SupplierSyncConflict`
- ✅ Los badges de estrategia tienen colores correctos:
  - `erp_wins` → badge primary (azul)
  - `local_wins` → badge info (celeste)
  - `manual` → badge warning (amarillo)
- ✅ Los badges de estado tienen colores correctos:
  - Resolved → badge success (verde)
  - Unresolved → badge danger (rojo)
- ✅ Paginación funciona si hay más de 15 registros

**Errores Comunes**:
- Tabla vacía → Verificar que hay registros en `supplier_sync_conflicts`
- Formato de fecha incorrecto → Verificar Carbon locale

---

### Caso 8: Ver Detalles de Conflicto

**Objetivo**: Verificar que el modal de detalles muestra información completa del conflicto.

**Pasos**:
1. En el tab "Conflictos Detectados", hacer clic en el botón "View Details" de un registro
2. Esperar que se abra el modal
3. Verificar que el modal muestra:
   - Información básica (ID, Tipo, Entity ID, ERP ID)
   - Estrategia de resolución
   - Fechas de detección y resolución
   - Comparación lado a lado: Local Data vs ERP Data
   - Campos cambiados (highlighted)
   - Estado de resolución

**Resultado Esperado**:
- ✅ Modal se abre correctamente
- ✅ Los datos JSON están formateados y legibles
- ✅ Los campos cambiados están destacados visualmente
- ✅ El modal se puede cerrar con el botón X o haciendo clic fuera

**Verificación Manual**:
- Inspeccionar el contenido del modal en las Developer Tools (F12)
- Verificar que los datos coinciden con la base de datos

**Errores Comunes**:
- Modal no abre → Verificar JavaScript: F12 → Console
- JSON no se muestra → Verificar que los campos `local_data` y `erp_data` tienen JSON válido
- Layout roto → Verificar Bootstrap CSS cargado

---

### Caso 9: Filtros y Búsqueda

**Objetivo**: Verificar que los filtros funcionan correctamente.

**Pasos**:
1. En el tab "Fallos de Sincronización":
   - Seleccionar un tipo específico en el dropdown "Tipo de Sincronización" (price/product/provider)
   - Verificar que la tabla se filtra
2. Usar el campo de búsqueda:
   - Escribir un término (ej: parte del error message)
   - Verificar que la tabla se filtra en tiempo real

**Resultado Esperado**:
- ✅ Dropdown de tipo filtra correctamente
- ✅ Búsqueda funciona y filtra por múltiples campos
- ✅ La paginación se resetea al filtrar
- ✅ El contador de resultados se actualiza

**Errores Comunes**:
- Filtros no funcionan → Verificar parámetros de query en la URL
- Búsqueda no responde → Verificar que el formulario tiene `method="GET"`

---

### Caso 10: Responsive Design

**Objetivo**: Verificar que el dashboard funciona en dispositivos móviles.

**Pasos**:
1. Abrir Chrome DevTools (F12)
2. Activar el modo responsive (Toggle device toolbar)
3. Probar con diferentes tamaños:
   - Mobile (320px)
   - Tablet (768px)
   - Desktop (1200px)
4. Verificar que:
   - Las tarjetas de estadísticas se apilan correctamente
   - La tabla es scrollable horizontalmente en mobile
   - Los botones son clickeables con el dedo (min 44px)
   - El modal ocupa toda la pantalla en mobile

**Resultado Esperado**:
- ✅ Layout se adapta a cada tamaño de pantalla
- ✅ No hay overflow horizontal
- ✅ Todos los elementos son accesibles
- ✅ Texto es legible sin hacer zoom

**Errores Comunes**:
- Tabla no scrolleable → Agregar `table-responsive` class
- Botones muy pequeños → Aumentar padding/touch targets
- Modal roto en mobile → Verificar Bootstrap modal responsive classes

---

## Checklist Final

Antes de dar por completada la prueba, verificar:

- [ ] Dashboard carga sin errores 500/404
- [ ] Las 4 tarjetas de estadísticas muestran datos correctos
- [ ] Navegación por tabs funciona
- [ ] Tabla de fallos muestra datos y es scrolleable
- [ ] Botón "Retry" funciona para un registro individual
- [ ] Botón "Delete" funciona para un registro individual
- [ ] Checkboxes seleccionan múltiples registros
- [ ] "Retry Selected" funciona con múltiples registros
- [ ] "Delete Selected" funciona con múltiples registros
- [ ] Tabla de conflictos muestra datos
- [ ] Modal de detalles se abre y muestra información completa
- [ ] Filtros funcionan correctamente
- [ ] Búsqueda filtra los resultados
- [ ] Paginación funciona
- [ ] Design responsive funciona en mobile/tablet/desktop
- [ ] Notificaciones (toasts) aparecen correctamente
- [ ] No hay errores en la consola JavaScript (F12)

---

## Solución de Problemas

### Error: 404 Not Found

**Causa**: Ruta no registrada o mal configurada.

**Solución**:
```bash
# Verificar que las rutas están registradas
php artisan route:list | grep sync-failures

# Si no aparecen, verificar web.php:
# modules/Supplier/routes/web.php debe tener las rutas definidas

# Limpiar cache de rutas
php artisan route:clear
```

### Error: 500 Internal Server Error

**Causa**: Error en el código del controlador o servicio.

**Solución**:
```bash
# Ver logs de Laravel
tail -f storage/logs/laravel.log

# Verificar que ErpSyncService está registrado
php artisan tinker
>>> app(Modules\Supplier\Services\ErpSyncService::class)

# Verificar permisos de escritura en logs
chmod -R 775 storage/logs
```

### Error: Tabla vacía (no hay datos)

**Causa**: No hay registros de fallos o conflictos en la base de datos.

**Solución**:
```bash
# Crear datos de prueba (ver sección "Datos de Prueba" arriba)
php artisan tinker
# ... ejecutar código de creación de registros de prueba
```

### Error: JavaScript no funciona

**Causa**: JavaScript no cargado o errores en la consola.

**Solución**:
```bash
# Verificar que no hay errores en la consola (F12)
# Verificar que jQuery está cargado antes del script personalizado
# Verificar que Bootstrap JS está cargado

# Inspeccionar el HTML generado:
# - Verificar que los botones tienen los atributos data-* correctos
# - Verificar que los eventos están bindados
```

### Error: Notificaciones no aparecen

**Causa**: Librería de notificaciones no cargada (Toastr, SweetAlert, etc.).

**Solución**:
```bash
# Verificar que la librería de notificaciones está incluida en el layout
# Ejemplo con Toastr:
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

# O con SweetAlert2:
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
```

---

## Notas de Desarrollo

### Base de Datos Involucrada

**Tablas**:
- `supplier_sync_failures` - Almacena fallos de sincronización
- `supplier_sync_conflicts` - Almacena conflictos detectados
- `supplier_products` - Referencia para productos
- `supplier_product_prices` - Referencia para precios
- `supplier_erp_providers` - Referencia para proveedores

**Índices de Rendimiento** (agregados en migración `2026_01_16_104947`):
- `idx_sync_failures_type_retry` - Para filtrar por tipo y contar reintentos
- `idx_sync_failures_last_retry` - Para ordenar por última fecha de reintento
- `idx_executions_workflow_status_date` - Para consultas de dashboard

### Performance

- La tabla de fallos está paginada (15 registros por página)
- Las consultas usan índices para mejor rendimiento
- Los conteos de estadísticas están optimizados
- Considera agregar cache para las estadísticas si hay > 10,000 registros

### Seguridad

- Todas las rutas requieren autenticación (`auth` middleware)
- Las rutas de configuración requieren rol `super-admin`
- Los inputs se validan en el controlador
- Los errores sensibles no se exponen al frontend

---

**Última actualización**: 2026-01-16
**Autor**: Sistema de documentación automática
**Módulo**: Supplier - Sync Failures Dashboard

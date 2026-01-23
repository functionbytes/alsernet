# Arquitectura de Sincronización: Laravel ↔ PrestaShop Event Manager

## Resumen de Funcionalidad de PrestaShop

El módulo `alserneteventmanager` en PrestaShop proporciona:

### 1. Gestión de Eventos
- **Creación/Edición/Eliminación** de eventos con fechas (start_at, end_at)
- **Estados de eventos:** available, completed, featured, amazing
- **Datos de visualización:** IVA, colores, botones, banners

### 2. Procesamiento Automático (Cron)
- `processEventStatus()` - ejecutado regularmente para:
  - Detectar eventos activos (dentro del rango de fechas)
  - Marcar productos con características basadas en management_tag
  - Activar/desactivar banners
  - Cambiar estados de eventos según fechas

### 3. Relación Evento-Producto
- Los eventos se vinculan a productos mediante **management_tag** (etiqueta)
- Los eventos pueden marcar productos con **características** (features)
- Los eventos pueden activar **banners** específicos

### 4. Registros de Auditoría
- Sistema de logs: `logEventAction(id_event, message)`
- Rastrea todas las acciones realizadas en eventos

### 5. Tablas de Base de Datos

```
aalv_alsernet_event_manager
├── id_event (PRIMARY)
├── title, start_at, end_at
├── management_tag (para vincular con productos)
├── filter_tag, priority_flag, color_flag
├── color_buttom, hover_buttom
├── available, featured, amazing, completed
├── iva, processing, processed
├── banners, unique_banners, banners_disabled
├── cms, created_at, updated_at

aalv_alsernet_event_manager_categories
├── id_event (FK)
└── id_category (FK)

aalv_alsernet_event_manager_lang
├── id_event (FK)
├── id_lang (FK)
├── title, special, url_special
├── title_special, buttom_all, buttom_one
└── created_at, updated_at

aalv_event_banner_status (tabla auxiliar)
├── id_event
├── id_banner
└── previous_status (para restaurar después del evento)

aalv_flags (tabla relacionada)
└── Para marcar productos durante eventos
```

---

## Propuesta de Arquitectura de Sincronización

### Componentes

#### 1. Tabla de Sincronización (Nueva)
```sql
CREATE TABLE aalv_event_sync_log (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    event_id INT UNSIGNED NOT NULL,           -- ID del evento en Laravel
    external_id INT UNSIGNED,                 -- ID en PrestaShop
    source ENUM('laravel', 'prestashop'),    -- Origen del cambio
    action VARCHAR(50),                       -- create, update, delete
    status ENUM('pending','synced','failed'), -- Estado de sincronización
    payload JSON,                             -- Datos sincronizados
    error_message TEXT,                       -- Si falló
    synced_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### 2. Modelo de Evento Unificado (Laravel)

```php
class Event extends Model {
    // Campos existentes
    protected $table = 'aalv_alsernet_event_manager';

    // Nuevos campos
    protected $fillable = [
        // ... campos existentes ...
        'observations',        // NUEVO: notas/observaciones
        'external_id',         // NUEVO: ID de PrestaShop
        'sync_status',         // NUEVO: estado de sincronización
        'sync_timestamp',      // NUEVO: última sincronización
    ];

    // Relaciones
    public function syncLog() {
        return $this->hasMany(EventSyncLog::class, 'event_id');
    }

    public function categories() {
        return $this->hasMany(EventCategory::class, 'event_id');
    }

    public function langs() {
        return $this->hasMany(EventLang::class, 'id_event');
    }
}
```

#### 3. API REST (Laravel)

```
Endpoints:
GET    /api/v1/events                    # Listar eventos
GET    /api/v1/events/{id}              # Obtener evento
POST   /api/v1/events                    # Crear evento
PUT    /api/v1/events/{id}              # Actualizar evento
DELETE /api/v1/events/{id}              # Eliminar evento
GET    /api/v1/events/active            # Eventos activos
POST   /api/v1/events/{id}/sync         # Sincronizar con PrestaShop
GET    /api/v1/events/{id}/observations # Obtener observaciones
POST   /api/v1/events/{id}/observations # Agregar observación
```

#### 4. Webhook Handlers (Laravel)

```php
// En UserServiceProvider o controlador
Route::post('/webhooks/prestashop/events/created', [EventWebhookController::class, 'eventCreated']);
Route::post('/webhooks/prestashop/events/updated', [EventWebhookController::class, 'eventUpdated']);
Route::post('/webhooks/prestashop/events/deleted', [EventWebhookController::class, 'eventDeleted']);
```

#### 5. Servicio de Sincronización (Nueva Clase)

```php
class EventSyncService {
    /**
     * Sincronizar evento de Laravel hacia PrestaShop
     */
    public function syncToPrestaShop(Event $event): bool

    /**
     * Sincronizar evento de PrestaShop hacia Laravel
     */
    public function syncFromPrestaShop(array $prestashopData): Event

    /**
     * Resolver conflictos de sincronización
     */
    public function resolveConflict(Event $laravel, array $prestashop): Event

    /**
     * Mapear campos entre sistemas
     */
    private function mapFieldsToPrestaShop(Event $event): array

    /**
     * Mapear campos desde PrestaShop
     */
    private function mapFieldsFromPrestaShop(array $data): array
}
```

---

## Flujo de Sincronización

### Crear Evento (Laravel → PrestaShop)

```
1. Usuario crea evento en panel Laravel
2. EventsController::store() guarda en BD de Laravel
3. Ejecuta EventSyncService::syncToPrestaShop()
   a. Mapea campos (filter_flag → filter_tag, etc.)
   b. Llama API REST de PrestaShop (si está disponible)
   c. Registra en aalv_event_sync_log
4. Retorna respuesta al usuario
```

### Recibir Cambios de PrestaShop

```
1. PrestaShop ejecuta cron (processEventStatus)
2. Detecta cambios en eventos
3. Envía webhook POST a Laravel
4. EventWebhookController recibe cambios
5. Ejecuta EventSyncService::syncFromPrestaShop()
6. Actualiza BD de Laravel
7. Registra en aalv_event_sync_log
```

### Resolver Conflictos

```
Si ambos sistemas modifican el mismo evento:
1. Comparar timestamps de última modificación
2. Usar "Last Write Wins" (LWW) por defecto
3. O crear alerta para intervención manual
4. Registrar conflicto en sync_log
```

---

## Observaciones/Notas

### Almacenamiento
1. **Campo directo en evento:**
   ```sql
   ALTER TABLE aalv_alsernet_event_manager
   ADD COLUMN observations TEXT NULL;
   ```

2. **Tabla separada (mejor para auditoría):**
   ```sql
   CREATE TABLE aalv_event_observations (
       id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
       event_id INT UNSIGNED NOT NULL,
       user_id INT UNSIGNED,
       content TEXT NOT NULL,
       created_at TIMESTAMP,
       updated_at TIMESTAMP,
       FOREIGN KEY (event_id) REFERENCES aalv_alsernet_event_manager(id)
   );
   ```

### Recomendación
Usar tabla separada para mejor auditoría y historial de cambios.

---

## Implementación en Fases

### Fase 1: Preparación (Semana 1)
- [ ] Crear migración para nuevo campo `observations`
- [ ] Crear migración para tabla `event_sync_log`
- [ ] Actualizar modelos Event, EventCategory, EventLang
- [ ] Crear modelo EventObservation
- [ ] Crear modelo EventSyncLog

### Fase 2: Backend (Semana 2)
- [ ] Implementar EventSyncService
- [ ] Crear API REST endpoints
- [ ] Implementar validación y mapeo de campos
- [ ] Crear webhook handlers
- [ ] Agregar logging y auditoría

### Fase 3: Frontend (Semana 3)
- [ ] Agregar campo de observaciones a formularios
- [ ] Crear vista de observaciones
- [ ] Mostrar estado de sincronización
- [ ] Implementar acciones manuales de sincronización

### Fase 4: Testing (Semana 4)
- [ ] Tests unitarios de sincronización
- [ ] Tests de integración con PrestaShop
- [ ] Tests de resolución de conflictos
- [ ] Tests end-to-end

---

## Consideraciones Técnicas

### 1. Seguridad
- Autenticar webhooks con JWT o HMAC
- Validar datos recibidos
- Rate limiting en endpoints de API
- Auditoría completa de cambios

### 2. Confiabilidad
- Retry automático en sincronización fallida
- Dead letter queue para eventos no procesables
- Monitoreo y alertas de estado de sincronización

### 3. Performance
- Indexar campos de búsqueda (external_id, sync_status)
- Batch operations para sincronización masiva
- Caché de datos frecuentemente accedidos

### 4. Mantenibilidad
- Documentación clara de campos mapeados
- Versionado de API (v1, v2)
- Logs detallados para debugging
- Tests exhaustivos

---

## Próximos Pasos

1. Revisar y aprobar arquitectura
2. Crear migraciones y modelos
3. Implementar servicio de sincronización
4. Crear API endpoints
5. Crear pruebas
6. Documentar con ejemplos de uso

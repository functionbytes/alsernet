# 📊 Análisis: Notificaciones en Tiempo Real - Módulo Document

**Fecha**: 12 de Enero 2026
**Módulo**: Document
**Objetivo**: Implementar notificaciones en tiempo real con Broadcasting + Queue

---

## 🔴 Resumen Ejecutivo

### Estado Actual
- ✅ Reverb (WebSocket) configurado en `.env`
- ✅ Queue (Database) configurado en `.env`
- ⚠️ Algunos eventos implementados parcialmente
- ❌ Broadcasting NO funciona completamente
- ❌ Queue worker NO está verificado que corra
- ❌ Reverb server NO está en funcionamiento

### Impacto
**Las notificaciones en tiempo real NO funcionan completamente** porque:
1. El evento `DocumentCreated` no implementa broadcasting
2. El listener `SendDocumentCreatedNotification` solo usa database (no broadcast)
3. Queue worker y Reverb server no están activos

---

## 📋 Problemas Identificados

### 🔴 CRÍTICO: DocumentCreated Event - Sin Broadcasting

**Archivo**: `modules/Document/app/Events/DocumentCreated.php`

**Problema**:
```php
// ACTUAL (INCORRECTO)
public function broadcastOn(): array
{
    return [];  // ❌ Retorna array vacío
}
```

**Impacto**: El evento NO se transmite por WebSocket

**Solución Necesaria**:
```php
// DEBERÍA SER
implements ShouldBroadcast

public function broadcastOn(): array
{
    return [
        new PrivateChannel('documents.'.$this->document->user_id),
        new Channel('documents.created')
    ];
}

public function toBroadcast(): array
{
    return [
        'document' => [
            'id' => $this->document->id,
            'title' => $this->document->title,
            'type' => $this->document->document_type_id,
            'created_at' => $this->document->created_at,
        ]
    ];
}
```

---

### 🔴 CRÍTICO: SendDocumentCreatedNotification - Solo Database

**Archivo**: `modules/Document/app/Listeners/SendDocumentCreatedNotification.php`

**Problema**:
```php
// ACTUAL (INCOMPLETO)
public function via($notifiable)
{
    return ['database'];  // ❌ Solo database, sin broadcast
}
```

**Impacto**: Las notificaciones se guardan en BD pero NO se envían en tiempo real

**Solución Necesaria**:
```php
// DEBERÍA SER
public function via($notifiable)
{
    return ['database', 'broadcast'];  // ✅ Database + Broadcast
}

// Implementar ShouldQueue
class SendDocumentCreatedNotification extends Notification implements ShouldQueue
{
    // ...
}
```

---

### 🟡 MAYOR: NotificacionesAprobadas/Rechazadas - Vacías

**Archivos**:
- `modules/Document/app/Notifications/DocumentApproved.php` (VACÍO)
- `modules/Document/app/Notifications/DocumentRejected.php` (VACÍO)
- `modules/Document/app/Notifications/DocumentStatusChanged.php` (VACÍO)

**Impacto**: No hay notificaciones cuando se aprueba/rechaza un documento

**Solución**: Implementar completamente (ver modelo: `DocumentStageAdvanced.php`)

---

### 🟡 MAYOR: Queue Worker No Verificado

**Comando necesario**:
```bash
# En servidor de producción
sudo systemctl start queue-worker.service  # systemd
# o
sudo supervisorctl start laravel-queue-worker:*  # Supervisor
```

**Verificar que está corriendo**:
```bash
ps aux | grep "queue:work"
```

**Sin esto**: Los jobs en cola NO se procesan

---

### 🟡 MAYOR: Reverb Server No Verificado

**Comando necesario**:
```bash
# En servidor de producción
sudo systemctl start reverb.service  # systemd
# o
sudo supervisorctl start laravel-reverb  # Supervisor
```

**Verificar que está corriendo**:
```bash
curl -v http://localhost:8080/app/local-key
# o
ps aux | grep "reverb:start"
```

**Sin esto**: Las conexiones WebSocket NO funcionan

---

## ✅ Lo Que SÍ Funciona

### DocumentStageAdvanced Notification

**Archivo**: `modules/Document/app/Notifications/DocumentStageAdvanced.php`

**Estado**: ✅ CORRECTAMENTE IMPLEMENTADA

```php
// ✅ Implementa todo correctamente
class DocumentStageAdvanced extends Notification implements ShouldBroadcast, ShouldQueue

public function via($notifiable)
{
    return ['database', 'broadcast'];  // ✅ Both
}

public function broadcastOn(): array
{
    return [new Channel('public-notifications.'.$this->recipientUserId)];
}

public function toBroadcast(): array
{
    return ['type' => 'document.stage.advanced', 'data' => [...]];
}
```

**Cómo funciona**:
1. Cuando un documento avanza de etapa → Se dispara automáticamente
2. Va a base de datos (almacenamiento persistente)
3. Se transmite por WebSocket en tiempo real

---

## 📊 Tabla Comparativa

| Componente | Broadcasting | Queue | Estado |
|-----------|-------------|-------|--------|
| **DocumentCreated** | ❌ NO | - | 🔴 CRÍTICO |
| **SendDocumentCreatedNotification** | ❌ NO | ❌ NO | 🔴 CRÍTICO |
| **DocumentStageAdvanced** | ✅ SÍ | ✅ SÍ | ✅ CORRECTO |
| **DocumentApproved** | - | - | 🔴 VACÍO |
| **DocumentRejected** | - | - | 🔴 VACÍO |
| **DocumentStatusChanged** | - | - | 🔴 VACÍO |
| **MailTemplateJob** | - | ✅ SÍ (emails) | ✅ FUNCIONA |
| **CheckSlaBreachesJob** | - | ✅ SÍ (default) | ✅ FUNCIONA |

---

## 🔧 Configuración Necesaria en Supervisor/systemd

### Para Queue Worker Procese Jobs

```ini
# Supervisor: /etc/supervisor/conf.d/laravel-queue-worker.conf
[program:laravel-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=/usr/bin/php /home2/webadminpruebas/web/artisan queue:work --queue=default,emails,notifications --timeout=0
numprocs=4  # 4 workers en paralelo
autostart=true
autorestart=true

# systemd: /etc/systemd/system/queue-worker.service
[Service]
ExecStart=/usr/bin/php /home2/webadminpruebas/web/artisan queue:work --queue=default,emails,notifications
```

**Colas configuradas en módulo Document**:
- `emails` - Para MailTemplateJob
- `default` - Para CheckSlaBreachesJob
- `notifications` - Para notificaciones en general

---

### Para Reverb WebSocket Funcione

```ini
# Supervisor: /etc/supervisor/conf.d/laravel-reverb.conf
[program:laravel-reverb]
command=/usr/bin/php /home2/webadminpruebas/web/artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true

# systemd: /etc/systemd/system/reverb.service
[Service]
ExecStart=/usr/bin/php /home2/webadminpruebas/web/artisan reverb:start --host=0.0.0.0 --port=8080
```

**Variables .env necesarias** (YA ESTÁN):
```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=123456
REVERB_APP_KEY=local-key
REVERB_APP_SECRET=local-secret
REVERB_HOST=manager.test
REVERB_PORT=8080
REVERB_SCHEME=https
```

---

## 🎯 Flujo de Notificación Actual vs Deseado

### Flujo ACTUAL (Incompleto)

```
Usuario crea documento
    ↓
DocumentCreated event se dispara
    ↓
SendDocumentCreatedNotification listener
    ↓
Notificación → Database SOLO
    ↓
❌ NO se envía en tiempo real
❌ Usuario no ve en WebSocket
```

### Flujo DESEADO (Completo)

```
Usuario crea documento
    ↓
DocumentCreated event se dispara (implements ShouldBroadcast)
    ↓
1. Se guarda en database (almacenamiento)
2. Se envía por WebSocket (tiempo real) → Reverb
    ↓
SendDocumentCreatedNotification listener + DocumentStageAdvanced
    ↓
1. Database → Almacenado en BD
2. Broadcast → Transmitido en WebSocket en vivo
3. Queue → Job se procesa en background
    ↓
✅ Usuario ve notificación instantánea
✅ Se guarda en historial
✅ Se envía email si aplica
```

---

## 📝 Archivos que Necesitan Cambios

### Priority 1 (CRÍTICO - Bloquea tiempo real)

```
1. modules/Document/app/Events/DocumentCreated.php
   → Implementar ShouldBroadcast
   → Configurar broadcastOn()
   → Implementar toBroadcast()

2. modules/Document/app/Listeners/SendDocumentCreatedNotification.php
   → Cambiar via() a ['database', 'broadcast']
   → Implementar ShouldQueue

3. deployment/systemd/queue-worker.service (YA EXISTE)
   → Debe estar corriendo en servidor

4. deployment/systemd/reverb.service (YA EXISTE)
   → Debe estar corriendo en servidor
```

### Priority 2 (IMPORTANTE - Notificaciones completas)

```
5. modules/Document/app/Notifications/DocumentApproved.php
   → Implementar (modelo: DocumentStageAdvanced.php)

6. modules/Document/app/Notifications/DocumentRejected.php
   → Implementar (modelo: DocumentStageAdvanced.php)

7. modules/Document/app/Notifications/DocumentStatusChanged.php
   → Implementar según casos de uso
```

### Priority 3 (MEJORAS - Seguridad y rendimiento)

```
8. Cambiar canales públicos a PrivateChannel
9. Implementar rate limiting en notificaciones
10. Agregar throttling para evitar spam
```

---

## ✅ Checklist de Implementación

### En el Código (Priority 1)
- [ ] Editar `DocumentCreated.php` - Agregar ShouldBroadcast
- [ ] Editar `DocumentCreated.php` - Implementar broadcastOn()
- [ ] Editar `DocumentCreated.php` - Implementar toBroadcast()
- [ ] Editar `SendDocumentCreatedNotification.php` - Cambiar via()
- [ ] Editar `SendDocumentCreatedNotification.php` - Implementar ShouldQueue

### En el Servidor (Priority 1)
- [ ] Copiar servicios systemd/supervisor (YA HECHO)
- [ ] Ejecutar `sudo systemctl start queue-worker.service`
- [ ] Ejecutar `sudo systemctl start reverb.service`
- [ ] Verificar ambos están corriendo

### En el Código (Priority 2)
- [ ] Implementar `DocumentApproved.php`
- [ ] Implementar `DocumentRejected.php`
- [ ] Implementar `DocumentStatusChanged.php`
- [ ] Crear listeners para estos eventos

### En el Servidor (Priority 2)
- [ ] Configurar Supervisor si no usa systemd
- [ ] Agregar múltiples queue workers (numprocs=4)
- [ ] Configurar rotación de logs

---

## 🧪 Cómo Probar

### 1. Verificar Reverb está activo
```bash
curl -v http://localhost:8080/app/local-key
# Debe retornar: 101 Switching Protocols (WebSocket)
```

### 2. Verificar Queue Worker está activo
```bash
ps aux | grep "queue:work"
# Debe mostrar al menos un worker
```

### 3. Crear documento de prueba
```bash
cd /home2/webadminpruebas/web
php artisan tinker

$doc = \Modules\Document\Entities\Document::factory()->create();
# Debería disparar notificaciones

exit
```

### 4. Verificar notificaciones en base de datos
```bash
php artisan tinker

# Ver notificaciones creadas
DB::table('notifications')->latest()->limit(5)->get();

exit
```

### 5. Verificar logs
```bash
# systemd
sudo journalctl -u reverb -f
sudo journalctl -u queue-worker -f

# Supervisor
sudo tail -f /var/log/supervisor/laravel-reverb.log
sudo tail -f /var/log/supervisor/laravel-queue-worker.log
```

---

## 📈 Impacto de las Notificaciones

### Casos de Uso Implementados
1. ✅ Documento creado → Notifica validadores
2. ✅ Documento avanza etapa → Notifica via broadcast + queue
3. ✅ Recordatorios cada 10 minutos → Notifica vía email

### Casos de Uso Faltantes
4. ❌ Documento aprobado → Debería notificar
5. ❌ Documento rechazado → Debería notificar
6. ❌ Cambio de estado → Debería notificar
7. ❌ Pago recibido → Implementado pero sin listeners

---

## 🔐 Consideraciones de Seguridad

### Actual (con riesgos)
```php
// Usa canal público
new Channel('public-notifications.'.$userId)
// Cualquiera que conozca el ID del usuario puede escuchar
```

### Recomendado
```php
// Usa canal privado
new PrivateChannel('documents.'.$this->document->user_id)
// Laravel valida automáticamente que el usuario es dueño
```

---

## 📊 Stack Necesario para Tiempo Real

```
┌─────────────────────────────────┐
│   Frontend (Vue/React/JS)       │
│   - Escucha WebSocket           │
│   - Actualiza UI en tiempo real │
└──────────────┬──────────────────┘
               │
               ▼
     ┌─────────────────────┐
     │  Reverb (WebSocket) │ ← NECESITA ESTAR CORRIENDO
     │  Puerto: 8080       │
     └─────────────────────┘
               │
               ▼
    ┌──────────────────────────┐
    │  Broadcasting Events     │
    │  (DocumentCreated,       │
    │   DocumentStageAdvanced) │
    └─────────────┬────────────┘
                  │
       ┌──────────┴──────────┐
       ▼                     ▼
   ┌────────┐          ┌──────────────┐
   │ Queue  │          │  Database    │
   │(Jobs)  │          │(Persistent)  │
   └────────┘          └──────────────┘
       │
       ▼
┌──────────────────────────┐
│  Queue Worker Process    │ ← NECESITA ESTAR CORRIENDO
│  (php artisan queue:work)│
└──────────────────────────┘
```

---

## 📞 Próximos Pasos

1. **Revisar y confirmar** este análisis
2. **Decidir**: ¿Quieres que corrija el código automáticamente?
3. **Decidir**: ¿Implementar Priority 2 ahora o después?
4. **Servidor**: Administrador debe activar systemd/supervisor

---

**Última actualización**: 12 de Enero 2026

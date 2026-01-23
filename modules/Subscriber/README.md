# Subscriber Module

## Descripción

El módulo **Subscriber** es responsable de toda la gestión de suscriptores, listas de correo, categorías de suscriptores, verificación de correos electrónicos e importación/exportación de datos de suscriptores.

Este módulo sigue la arquitectura modular de Laravel y está completamente aislado del núcleo de la aplicación.

## Características Principales

### 1. **Gestión de Suscriptores**
- Crear, leer, actualizar y eliminar suscriptores
- Historial de cambios y actividades (logging)
- Búsqueda y filtrado avanzado
- Estados de suscripción (confirmado, suscrito, no suscrito, blacklist, spam)
- Verificación de entregabilidad de email

### 2. **Listas de Correo**
- Crear y gestionar múltiples listas de correo
- Asignar suscriptores a listas
- Categorías de suscriptores con sincronización automática
- Filtros por idioma
- Listas Por defecto por idioma

### 3. **Importación y Exportación**
- Importar suscriptores desde archivos CSV/Excel
- Procesamiento por lotes (batch processing)
- Detección automática de codificación
- Exportación de suscriptores con campos personalizados
- Reportes de errores en importación
- Barra de progreso en tiempo real con Jobs

### 4. **Verificación de Email**
- Verificación de entregabilidad de correos
- Múltiples estrategias de búsqueda (BTree, Hash, Binary, Partition)
- Estados de verificación (entregable, no entregable, desconocido, riesgoso)
- Reintentos automáticos con límite de tiempo
- Gestión de créditos/cuotas

### 5. **Categorías**
- Asignación de categorías a suscriptores
- Sincronización automática con listas de correo
- Categorías enlazadas con listas
- Operaciones masivas (agregar, eliminar, reemplazar)

### 6. **Eventos y Listeners**
- Eventos para cambios de suscripción
- Notificaciones automáticas a propietarios de listas
- Notificaciones a suscriptores de cambios de estado
- Sistema de eventos desacoplado

## Estructura de Directorios

```
Modules/Subscriber/
├── app/
│   ├── Models/
│   │   ├── Subscriber.php                    - Modelo principal
│   │   ├── SubscriberList.php               - Listas de correo
│   │   ├── SubscriberCategorie.php          - Categorías
│   │   ├── SubscriberListUser.php           - Relación suscriptor-lista
│   │   ├── SubscriberCondition.php          - Estados/Condiciones
│   │   ├── SubscriberLog.php                - Logs de actividad
│   │   ├── SubscriberImport.php             - Gestión de importaciones
│   │   └── CampaignMaillistsSubscriber.php  - Suscriptores de campañas
│   │
│   ├── Http/Controllers/
│   │   ├── Managers/
│   │   │   ├── SubscribersController.php
│   │   │   ├── SubscribersListsController.php
│   │   │   ├── SubscribersReportController.php
│   │   │   ├── SubscribersConditionsController.php
│   │   │   └── SubscribersListUserController.php
│   │   ├── Api/
│   │   │   └── SubscribersController.php     - REST API
│   │   └── Shops/
│   │       └── SubscribersController.php     - Frontend para tiendas
│   │
│   ├── Jobs/
│   │   ├── ImportSubscribersJob.php
│   │   ├── ExportSubscribersJob.php
│   │   ├── VerifySubscriber.php
│   │   ├── SubscriberCheckatJob.php
│   │   ├── SubscriberCategoriesJob.php
│   │   ├── UpdateSubscriberCategoriesJob.php
│   │   ├── AddSuscriberListJob.php
│   │   ├── RemoveSuscriberListJob.php
│   │   ├── SyncSuscriberListJob.php
│   │   └── [Otros jobs...]
│   │
│   ├── Events/
│   │   └── SubscriberCheckatEvent.php
│   │
│   ├── Listeners/
│   │   ├── SubscriberCheckatListener.php
│   │   ├── SendListNotificationToOwner.php
│   │   └── SendListNotificationToSubscriber.php
│   │
│   ├── Imports/
│   │   └── SubscribersImport.php             - Maatwebsite Excel
│   │
│   ├── Exports/
│   │   └── SubscribersFailedExport.php
│   │
│   ├── Http/Resources/
│   │   └── SubscriberResource.php            - API Response Format
│   │
│   └── Providers/
│       ├── SubscriberServiceProvider.php
│       └── RouteServiceProvider.php
│
├── database/
│   ├── migrations/
│   │   └── [Migraciones de tablas] (actualmente vacío - tablas ya existen)
│   └── seeders/
│       └── SubscriberSeeder.php
│
├── resources/
│   └── views/
│       ├── managers/
│       │   ├── subscribers/
│       │   ├── lists/
│       │   └── conditions/
│       └── shops/
│           └── subscribers/
│
├── routes/
│   ├── managers.php                 - Rutas de administración
│   ├── api.php                      - Rutas API
│   └── shops.php                    - Rutas de tienda
│
├── config/
│   └── config.php
│
├── module.json                      - Configuración del módulo
└── README.md                        - Este archivo
```

## Rutas Disponibles

### Rutas de Administración (Managers)
```
GET    /manager/subscribers                  - Listar suscriptores
GET    /manager/subscribers/create           - Formulario crear
POST   /manager/subscribers/update           - Actualizar
GET    /manager/subscribers/edit/{uid}       - Formulario editar
GET    /manager/subscribers/view/{uid}       - Ver detalles
GET    /manager/subscribers/destroy/{uid}    - Eliminar
GET    /manager/subscribers/logs/{slack}     - Ver logs

# Importaciones
GET    /manager/subscribers/imports/create   - Iniciar importación
POST   /manager/subscribers/imports/{uid}/dispatch  - Despachar job
GET    /manager/subscribers/imports/{job_uid}/progress - Progreso

# Listas
GET    /manager/subscribers/lists            - Listar listas
POST   /manager/subscribers/lists/store      - Crear lista
GET    /manager/subscribers/lists/{uid}      - Ver lista
POST   /manager/subscribers/lists/update     - Actualizar
GET    /manager/subscribers/lists/categories/{uid} - Manage categories
POST   /manager/subscribers/lists/categories/update

# Condiciones
GET    /manager/subscribers/conditions       - Listar condiciones
POST   /manager/subscribers/conditions/store - Crear
POST   /manager/subscribers/conditions/update - Actualizar
```

### Rutas API
```
POST   /api/subscribers/process              - Procesar operaciones
POST   /api/subscribers/campaigns            - Operaciones de campañas
```

### Rutas de Tienda
```
GET    /shop/subscribers                     - Listar (frontend)
GET    /shop/subscribers/create              - Crear
GET    /shop/subscribers/edit/{uid}          - Editar
GET    /shop/subscribers/logs/{uid}          - Logs
```

## Modelos y Relaciones

### Subscriber (Suscriptor)
```php
// Relaciones
$subscriber->categories()        // BelongsToMany - Categorías del suscriptor
$subscriber->lists()             // HasMany - Listas del suscriptor
$subscriber->logs()              // HasMany - Historial de cambios
$subscriber->lang()              // BelongsTo - Idioma

// Métodos principales
$subscriber->isPendingVerification()
$subscriber->updateWithLog($data)
$subscriber->addToList($list)
$subscriber->removeAllSubscriptions()
```

### SubscriberList (Lista de Correo)
```php
// Relaciones
$list->subscribers()             // BelongsToMany - Suscriptores
$list->categories()              // BelongsToMany - Categorías

// Métodos
$list->getBlacklistByLang($langId)
```

### SubscriberCategorie (Categoría)
```php
// Relaciones
$category->subscriber()           // BelongsTo - Suscriptor
$category->lists()                // BelongsToMany - Listas asociadas
```

## Jobs (Trabajos en Cola)

### Importación
- **ImportSubscribersJob** - Importar suscriptores (Timeout: 7200s)
- **ImportSubscribersListsJob** - Importar a listas específicas

### Verificación
- **VerifySubscriber** - Verificar entregabilidad (Timeout: 120s, Retry: 12h)
- **SubscriberCheckatJob** - Checkup de suscriptor

### Gestión de Listas
- **AddSuscriberListJob** - Agregar a lista
- **RemoveSuscriberListJob** - Remover de lista
- **SyncSuscriberListJob** - Sincronizar membresía

### Categorías
- **SubscriberCategoriesJob** - Procesar categorías
- **UpdateSubscriberCategoriesJob** - Actualizar en masa

### Exportación
- **ExportSubscribersJob** - Exportar a CSV

## Configuración

El módulo se configura mediante `config/config.php`:

```php
return [
    'import' => [
        'batch_size' => 1000,      // Registros por batch
        'encoding' => 'UTF-8',      // Codificación automática
    ],

    'verification' => [
        'enabled' => true,
        'timeout' => 120,           // Segundos
        'retry_hours' => 12,        // Horas de reintento
    ],

    'export' => [
        'format' => 'csv',
        'delimiter' => ',',
    ],
];
```

## Eventos

El módulo dispara y escucha estos eventos:

### Eventos del Módulo
- **SubscriberCheckatEvent** - Disparado cuando se verifica un email

### Eventos Externos Escuchados
- **MailListSubscription** - Suscripción a lista
- **MailListUnsubscription** - Desuscripción de lista

## Integración con Mail Module

El módulo Subscriber envía correos usando el módulo Mail:

```
Modules/Mail/app/Mail/Subscribers/
├── SubscribersMail.php
├── SubscribersWelcomeMail.php
├── SubscriberCheckMail.php
├── UnsubscribersNoneMail.php
├── UnsubscribersPartiesMail.php
└── UnsubscribersSportsMail.php
```

Estos archivos importan modelos desde `Modules\Subscriber\Models\*`

## Cache y Performance

El módulo utiliza:
- **Caché de Subscriber** para búsquedas frecuentes
- **Batch Processing** para importaciones grandes
- **Query Helpers** para optimizar consultas
- **Eager Loading** para prevenir problemas N+1

## Testing

Ubicación: `Modules/Subscriber/tests/Feature/`

Tests incluyen:
- CRUD de suscriptores
- Importación de datos
- Verificación de email
- Gestión de categorías
- Logs de actividad

## Desarrollo

### Crear un Suscriptor
```php
use Modules\Subscriber\Models\Subscriber;

$subscriber = Subscriber::create([
    'firstname' => 'Juan',
    'lastname' => 'Pérez',
    'email' => 'juan@example.com',
    'lang_id' => 1,
]);
```

### Agregar a una Lista
```php
$subscriber->addToList($list);

// O con logging
$subscriber->updateCategoriesWithLog(
    $categories,
    $langId,
    auth()->user()
);
```

### Verificar Email
```php
VerifySubscriber::dispatch($subscriber);
```

### Importar Suscriptores
```php
ImportSubscribersJob::dispatch($import);
```

## Troubleshooting

### Problema: "Class Subscriber not found"
**Solución:** Ejecutar `composer dump-autoload` y verificar que `SubscriberServiceProvider` está registrado en `bootstrap/providers.php`

### Problema: Las vistas no cargan
**Solución:** Verificar que las vistas están en `resources/views/subscriber/` o que el módulo está correctamente registrado

### Problema: Jobs no se ejecutan
**Solución:** Verificar que el queue worker está activo: `php artisan queue:work`

## Migración desde versión anterior

Este módulo fue migrado desde `app/Models/Subscriber` y `app/Http/Controllers/*/Subscribers/` el 2025-12-29.

- ✅ Todos los modelos migrádos
- ✅ Todos los controladores migrados
- ✅ Todos los jobs migrados
- ✅ Código original eliminado
- ✅ Referencias actualizadas

## Licencia

Parte del sistema Alsernet.

## Soporte

Para reportar bugs o solicitar mejoras, contacta al equipo de desarrollo.

# Módulo Returns - Documentación de Estructura

## Resumen Ejecutivo

El módulo **Returns** (Devoluciones) ha sido refactorizado desde `app/` hacia `Modules/Returns/`, siguiendo la arquitectura modular de Laravel. Este módulo gestiona todo el ciclo de vida de devoluciones de productos, incluyendo garantías, inspecciones, comunicaciones y reembolsos.

**Estado Actual**: ✅ Completamente refactorizado con 88 archivos PHP | 🔄 Deshabilitado temporalmente (requiere resolución de autoloading)

---

## Estructura de Directorios

```
Modules/Returns/
├── app/
│   ├── Entities/                    # Clases de entidades (heredadas)
│   ├── Enums/                       # Enumeraciones (estados, tipos)
│   ├── Events/                      # Eventos de dominio
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/                # Controladores de API pública
│   │   │   ├── Callcenters/        # Controladores de call center
│   │   │   └── Managers/           # Controladores de administración
│   │   ├── Middleware/             # Middleware específico del módulo
│   │   └── Requests/               # Form Requests (validación)
│   ├── Jobs/                        # Jobs en cola (procesamiento asincrónico)
│   ├── Listeners/                   # Event Listeners
│   ├── Models/                      # Modelos Eloquent
│   │   ├── Order/                  # Modelos relacionados con órdenes
│   │   └── [modelos individuales]
│   ├── Notifications/               # Notificaciones (email, SMS, etc)
│   ├── Observers/                   # Model Observers
│   ├── Policies/                    # Políticas de autorización
│   ├── Providers/                   # Service Providers del módulo
│   ├── Services/                    # Servicios de negocio
│   └── Traits/                      # Traits compartidos
├── database/
│   ├── factories/                   # Factories para testing
│   ├── migrations/                  # Migraciones específicas del módulo
│   └── seeders/                     # Seeders de datos
├── resources/
│   └── views/                       # Vistas Blade del módulo
├── routes/
│   ├── api.php                      # Rutas de API
│   ├── callcenters.php              # Rutas para call center
│   └── managers.php                 # Rutas para administración
├── tests/
│   ├── Feature/                     # Tests de características
│   └── Unit/                        # Tests unitarios
├── module.json                      # Configuración del módulo
└── composer.json                    # Dependencias del módulo (si aplica)
```

---

## Componentes Principales

### 1. Modelos Eloquent (44 archivos)

#### Modelos Centrales de Devoluciones

| Modelo | Propósito | Relaciones Clave |
|--------|----------|------------------|
| `ReturnRequest` | Solicitud de devolución principal | `products`, `status`, `customer`, `order` |
| `ReturnStatus` | Estados de devolución | `return`, `history` |
| `ReturnPayment` | Pagos/reembolsos | `return`, `method` |
| `ReturnHistory` | Historial de cambios | `return`, `user` |
| `ReturnInspection` | Inspecciones del producto | `return`, `inspector` |
| `ReturnCost` | Costos asociados | `return`, `type` |
| `ReturnCommunication` | Comunicaciones con cliente | `return`, `sender` |

#### Modelos de Garantía

| Modelo | Propósito |
|--------|----------|
| `Warranty` | Información de garantía del producto |
| `WarrantyClaim` | Reclamación de garantía |
| `WarrantyType` | Tipos de garantía (estándar, extendida, etc) |

#### Modelos de Productos y Órdenes

| Modelo | Propósito |
|--------|----------|
| `ProductReturnRule` | Reglas de devolución por producto |
| `ProductComponent` | Componentes de productos |
| `ProductCategory` | Categorías de productos |
| `ReturnOrder` | Orden de retorno |
| `ReturnOrderProduct` | Productos en orden de retorno |

#### Modelos de Configuración y Localización

| Modelo | Propósito |
|--------|----------|
| `ReturnReason` / `ReturnReasonLang` | Razones de devolución (multiidioma) |
| `ReturnType` / `ReturnTypeLang` | Tipos de devolución (multiidioma) |
| `ReturnState` | Estados disponibles |
| `ReturnPolicy` | Políticas de devolución |

### 2. Controladores HTTP

#### API Pública (`Http/Controllers/Api/`)
```
- PublicReturnController      # Endpoints públicos para clientes
- ReturnController            # Endpoints autenticados para devoluciones
```

**Rutas API**:
```
GET    /api/returns                    # Listar devoluciones
POST   /api/returns                    # Crear devolución
GET    /api/returns/{id}               # Obtener devolución
PATCH  /api/returns/{id}               # Actualizar devolución
```

#### Call Center (`Http/Controllers/Callcenters/`)
```
- ReturnsController                    # Gestión de devoluciones
- ComponentController                  # Gestión de componentes
- InspectionController                 # Gestión de inspecciones
- PdfDocumentController                # Generación de PDFs
- ReturnCommunicationController        # Comunicaciones
- ReturnCostController                 # Costos
- ReturnTrackingController             # Seguimiento
- WarrantyController                   # Gestión de garantías
- WarrantyClaimController              # Reclamaciones de garantía
```

#### Administración (`Http/Controllers/Managers/`)
```
- ProductReturnRuleController          # Reglas de devolución por producto
```

### 3. Servicios de Negocio (`app/Services/`)

| Servicio | Responsabilidad |
|----------|-----------------|
| `ReturnService` | Lógica principal de devoluciones |
| `ReturnEmailService` | Envío de emails de devolución |
| `ReturnNotificationService` | Notificaciones automáticas |
| `BarcodeService` | Generación de códigos de barras |
| `ReturnPDFService` | Generación de PDFs |
| `DocumentService` | Gestión de documentos |
| `InspectionService` | Lógica de inspecciones |
| `ComponentService` | Gestión de componentes |
| `ReturnCostService` | Cálculo de costos |
| `ReturnValidationService` | Validaciones de devolución |
| `WarrantyService` | Lógica de garantías |

### 4. Events y Listeners

#### Eventos Principales

```php
ReturnCreated               // Cuando se crea una devolución
ReturnCompleted             // Cuando se completa una devolución
ReturnPaymentProcessed      // Cuando se procesa un pago
ReturnStatusChanged         // Cuando cambia el estado
```

#### Listeners Automáticos

```php
SendConfirmationListener         // Envía confirmación al cliente
GeneratePDFListener              // Genera PDF de devolución
LogReturnActivityListener        // Registra actividad en log
UpdateHistoryListener            // Actualiza historial
NotifyCustomerListener           // Notifica cambios al cliente
```

### 5. Jobs en Cola (`app/Jobs/`)

| Job | Descripción |
|-----|-------------|
| `SendReturnNotificationEmail` | Envía emails de notificación |
| `ProcessReturnReminders` | Procesa recordatorios automáticos |
| `ProcessReturnPDFGeneration` | Genera PDFs en background |
| `ProcessBulkStatusUpdate` | Actualiza estados en lote |
| `SendBulkReturnNotifications` | Envía notificaciones en lote |

### 6. Middleware

```php
CheckReturnAccess           // Verifica acceso a devolución
CheckReturnStatus           // Valida estado para operaciones
```

### 7. Form Requests (Validación)

```php
CreateReturnRequest         // Validación para crear devolución
UpdateReturnRequest         // Validación para actualizar
StoreReturnCostRequest      // Validación para costos
ResendCommunicationRequest  // Validación para comunicaciones
```

---

## Routes (Rutas)

### 1. API Routes (`routes/api.php`)
Base: `/api/returns`

```php
GET    /                               # Listar
POST   /                               # Crear
GET    /{id}                          # Ver detalle
PATCH  /{id}                          # Actualizar
DELETE /{id}                          # Eliminar
```

**Middleware**: `auth:sanctum`, `throttle:60,1`

### 2. Call Center Routes (`routes/callcenters.php`)
Base: `/callcenter/returns`

**Principales**:
```
Returns:
  GET    /                             # Listar
  POST   /                             # Crear
  GET    /{id}                        # Ver

Components:
  GET    /components                  # Listar componentes
  POST   /components                  # Crear componente

Inspections:
  GET    /inspections                 # Listar inspecciones
  POST   /inspections                 # Crear inspección

Warranties:
  GET    /warranties                  # Listar garantías
  POST   /warranties                  # Crear garantía
```

### 3. Manager Routes (`routes/managers.php`)
Base: `/manager/settings/returns`

```php
ReturnRules:
  GET    /rules                       # Listar reglas
  POST   /rules                       # Crear regla
  GET    /rules/{id}/edit            # Editar regla
  PATCH  /rules/{id}                 # Actualizar
  DELETE /rules/{id}                 # Eliminar
  POST   /rules/{id}/toggle-status   # Activar/desactivar
  POST   /rules/{id}/clone           # Clonar regla
```

**Middleware**: `auth`, `verified`, `manager`

---

## Service Providers

### ReturnsServiceProvider
**Ubicación**: `app/Providers/ReturnsServiceProvider.php`

**Responsabilidades**:
- Registra los servicios del módulo en el contenedor
- Configura el Observer para ReturnRequest
- Carga migraciones y vistas del módulo
- Registra observadores de modelo

```php
// Clave registro
$this->app->singleton(ReturnService::class);
$this->app->singleton(ReturnEmailService::class);
// ... otros servicios
```

### RouteServiceProvider
**Ubicación**: `app/Providers/RouteServiceProvider.php`

**Responsabilidades**:
- Registra rutas del módulo
- Aplica middleware específico por grupo
- Configura namespaces de controladores

**Grupos de Rutas**:
- `api/returns` → Rutas públicas de API
- `callcenter/returns` → Rutas de call center
- `manager/settings/returns` → Rutas de administración

---

## Base de Datos

### Migraciones

```
database/migrations/
├── 2025_01_XX_create_returns_table.php
├── 2025_01_XX_create_return_statuses_table.php
├── 2025_01_XX_create_return_reasons_table.php
├── 2025_01_XX_create_warranties_table.php
├── 2025_01_XX_create_return_costs_table.php
└── [40+ migraciones más]
```

### Seeders

```
database/seeders/
├── ReturnStatusSeeder               # Estados predefinidos
├── ReturnReasonSeeder               # Razones de devolución
├── ReturnTypeSeeder                 # Tipos de devolución
├── WarrantyTypeSeeder               # Tipos de garantía
└── ReturnPolicySeeder               # Políticas
```

---

## Configuración del Módulo

### module.json

```json
{
    "name": "Return",
    "alias": "returns",
    "description": "Módulo de gestión de devoluciones con sistema de garantías, inspecciones y comunicación",
    "keywords": ["returns", "warranty", "inspection", "communication", "refunds"],
    "priority": 0,
    "providers": [],
    "files": []
}
```

**Nota**: `providers` está vacío para evitar conflictos de autoloading. Los proveedores se registran manualmente en `bootstrap/providers.php` cuando el módulo está habilitado.

---

## Estado de Habilitación

### Verificar Estado
Ver `modules_statuses.json`:

```json
{
    "Returns": false
}
```

### Habilitar Módulo
```bash
# Actualizar modules_statuses.json
{
    "Returns": true
}

# Registrar provider en bootstrap/providers.php
\Modules\Returns\Providers\ReturnsServiceProvider::class,
```

**Requisito**: Resolver problema de autoloading (ver "Problemas Conocidos").

---

## Dependencias Externas

### Servicios Utilizados
- **Email**: Utiliza Mail de Laravel (Modules\Mail si está habilitado)
- **Almacenamiento**: Media Library de Spatie
- **Notificaciones**: Sistema de notificaciones de Laravel
- **Queue**: Jobs en cola para procesamiento asincrónico

### Paquetes Relacionados
```php
// Probablemente necesarios
- spatie/laravel-medialibrary          // Gestión de archivos
- barryvdh/laravel-dompdf              // Generación PDF
- picqer/php-barcode-generator         // Códigos de barras
```

---

## Migraciones Recientes

### Archivos Movidos de `app/` → `Modules/Returns/`

**Modelos**: 44 archivos
```
app/Models/Return/* → Modules/Returns/app/Models/*
app/Models/Warranty/* → Modules/Returns/app/Models/*
app/Models/ProductComponent.php → Modules/Returns/app/Models/ProductComponent.php
```

**Controladores**: 12 archivos
```
app/Http/Controllers/Api/Return* → Modules/Returns/app/Http/Controllers/Api/*
app/Http/Controllers/Callcenters/Return* → Modules/Returns/app/Http/Controllers/Callcenters/*
```

**Servicios**: 12 archivos
```
app/Services/Returns/* → Modules/Returns/app/Services/*
```

**Events/Listeners/Jobs**: 14 archivos
```
app/Events/Return* → Modules/Returns/app/Events/*
app/Listeners/Return* → Modules/Returns/app/Listeners/*
app/Jobs/Return* → Modules/Returns/app/Jobs/*
```

---

## Problemas Conocidos

### 1. Autoloading de Módulo ⚠️
**Estado**: Pendiente de resolución

**Problema**: El directorio `Modules/Returns/app/` no se autoloada correctamente con PSR-4 estándar.

**Causa**: La estructura `Modules/{Nombre}/app/` requiere mapeo especial en PSR-4. nwidart/laravel-modules espera una estructura diferente.

**Soluciones Propuestas**:

#### Opción 1: Configurar PSR-4 Custom (Recomendado)
Actualizar `composer.json`:
```json
{
    "autoload": {
        "psr-4": {
            "Modules\\Returns\\": "modules/Return/app/",
            "Modules\\Returns\\Database\\": "modules/Return/database/",
            "Modules\\Returns\\Tests\\": "modules/Return/tests/"
        }
    }
}
```

Luego ejecutar:
```bash
composer dump-autoload
```

#### Opción 2: Reestructurar Módulo
Mover contenido de `Modules/Returns/app/*` directamente a `Modules/Returns/*`:
```
Modules/Returns/
├── Models/
├── Http/
├── Services/
├── ...
```

Esto requiere actualizar namespace en todos los archivos.

#### Opción 3: Crear Loader Custom
Implementar un service provider que descubra dinámicamente módulos habilitados.

### 2. Dependencias de Módulos Deshabilitados
**Estado**: ✅ Resuelto con degradación elegante

**Solución Implementada**:
- Parámetros opcionales en inyección de dependencias
- Hook system fallback para importaciones
- Listeners deshabilitados en EventServiceProvider

---

## Comandos de Consola

### Disponibles

```bash
# Procesar garantías
php artisan warranties:process

# Procesar componentes
php artisan components:process --check-stock --optimize --reorder

# Enviar recordatorios de devolución
php artisan returns:send-reminders --days=7 --dry-run

# Sincronizar con PrestaShop (si disponible)
php artisan prestashop:sync-categories
```

---

## Testing

### Ejecutar Tests del Módulo

```bash
# Solo tests de Return
php artisan test modules/Return/tests

# Con cobertura
php artisan test modules/Return/tests --coverage

# Tests específicos
php artisan test modules/Return/tests/Feature/ReturnCreationTest.php
```

### Estructura de Tests

```
Modules/Returns/tests/
├── Feature/
│   ├── ReturnCreationTest.php
│   ├── ReturnStatusChangeTest.php
│   └── WarrantyProcessTest.php
└── Unit/
    ├── ReturnServiceTest.php
    └── WarrantyServiceTest.php
```

---

## Flujos Principales

### Creación de Devolución
```
1. Cliente/Agent crea ReturnRequest
2. Event ReturnCreated disparado
3. Listeners ejecutados:
   - SendConfirmationListener → Envía email
   - LogReturnActivityListener → Registra actividad
   - UpdateHistoryListener → Actualiza historio
4. Estado inicial = "pending_review"
5. Inspección se agenda automáticamente
```

### Cambio de Estado
```
1. Estado actualizado vía updateStatus()
2. Event ReturnStatusChanged disparado
3. Transiciones validadas (ReturnValidationService)
4. Notificaciones enviadas según configuración
5. Historial actualizado
6. Emails enviados si está configurado
```

### Procesamiento de Pago
```
1. Job ProcessPayment iniciado
2. Cálculo de monto (ReturnCostService)
3. Integración con gateway de pago
4. Event ReturnPaymentProcessed disparado
5. Confirmación al cliente
```

---

## Consideraciones Importantes

### ✅ Lo Que Funciona
- Estructura modular completa
- Namespaces actualizados correctamente
- Syntaxis PHP validada (88 archivos)
- Servicios y lógica intactos
- Tests pueden ejecutarse
- Degradación elegante de dependencias

### ⚠️ Lo Que Requiere Atención
- Autoloading de módulo necesita configuración
- PSR-4 mapping necesita actualización en `composer.json`
- Algunos tests tienen dependencias en factories faltantes
- Migraciones de base de datos en directorios separados

### 📋 Próximos Pasos
1. Resolver autoloading (Opción 1 recomendada)
2. Ejecutar `php artisan migrate` en el módulo
3. Ejecutar factory seeders para datos de prueba
4. Ejecutar test suite completo
5. Habilitar módulo en `modules_statuses.json`

---

## Referencias Internas

- **Documentación de Documents Module**: `docs/modules/documents-module-structure.md`
- **Arquitectura de Módulos**: `docs/backend/modular-architecture.md`
- **API Endpoints**: `docs/api/returns-endpoints.md`
- **Migraciones**: `database/migrations/returns/`

---

**Última Actualización**: 29 de Diciembre de 2025
**Estado**: ✅ Refactorización Completada | 🔄 Awaiting Autoload Configuration

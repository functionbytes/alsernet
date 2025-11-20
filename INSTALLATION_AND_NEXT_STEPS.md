# 🚀 Guía de Instalación y Próximos Pasos

## 1️⃣ EJECUTAR MIGRACIONES

### Paso 1: Crear la Tabla de Asignación

```bash
# Ejecutar todas las migraciones pendientes
php artisan migrate

# O si quieres ejecutar solo la nueva migración:
php artisan migrate --path=database/migrations/2025_11_20_000001_create_user_warehouse_table.php
```

### Verificar que la tabla se creó

```bash
# Conectar a la BD y verificar
mysql> SELECT * FROM user_warehouse;
# Debe estar vacía pero con la estructura

# O desde artisan tinker:
php artisan tinker
> DB::table('user_warehouse')->count();
> 0 // Esperado
```

---

## 2️⃣ CONFIGURAR LOGS

### Paso 1: Actualizar config/logging.php

Agregar los siguientes canales en el array `'channels'`:

```php
// config/logging.php

'channels' => [
    // ... canales existentes ...

    'barcode' => [
        'driver' => 'daily',
        'path' => storage_path('logs/barcode.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 30,
    ],

    'inventory' => [
        'driver' => 'daily',
        'path' => storage_path('logs/inventory.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 90,
    ],

    'admin' => [
        'driver' => 'daily',
        'path' => storage_path('logs/admin.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 60,
    ],
],
```

### Paso 2: Crear directorios de logs

```bash
mkdir -p storage/logs
chmod 755 storage/logs
```

---

## 3️⃣ VERIFICAR ARCHIVOS CREADOS

### Servicios
```bash
✅ app/Services/Inventories/BarcodeReadingService.php
```

### Controladores
```bash
✅ app/Http/Controllers/Inventaries/WarehouseInventoryTransferController.php
✅ app/Http/Controllers/Admin/UserWarehouseAssignmentController.php
```

### Vistas
```bash
✅ resources/views/inventaries/views/warehouse/transfers/index.blade.php
✅ resources/views/inventaries/views/warehouse/transfers/modals.blade.php
✅ resources/views/admin/users/warehouse-assignment.blade.php
✅ resources/views/admin/users/warehouse-assignment-edit.blade.php
```

---

## 4️⃣ PROBAR LAS FUNCIONALIDADES

### Test 1: Lectura de Código de Barras

```bash
# Abrir la aplicación en un navegador
# Ir a: http://tu-app.local/inventarie/inventaries

# O desde Tinker para test rápido:
php artisan tinker

# Importar el servicio
use App\Services\Inventories\BarcodeReadingService;

# Instanciar
$service = app(BarcodeReadingService::class);

# Test 1: Validar un código que existe
$result = $service->validate('1234567890123');
// dd($result);

# Test 2: Validar código inválido
$result = $service->validate('invalid');
// Debe retornar error

# Test 3: Código no encontrado
$result = $service->validate('9999999999999');
// Debe retornar 'not_found'

# Salir
exit
```

### Test 2: Asignación de Almacenes

```bash
php artisan tinker

# Obtener usuario
use App\Models\User;
$user = User::where('email', 'test@example.com')->first();

# Obtener almacén
use App\Models\Warehouse\Warehouse;
$warehouse = Warehouse::first();

# Asignar
$user->assignWarehouse($warehouse->id, true, true, true);

# Verificar
$user->warehouses()->count(); // Debe ser 1
$user->defaultWarehouse()->id === $warehouse->id; // true
$user->canPerformInventory($warehouse->id); // true
$user->canTransferInWarehouse($warehouse->id); // true

exit
```

### Test 3: Acceder a la UI

```
1. Ir a: http://tu-app.local/manager/warehouse-assignment
2. Deberías ver una lista de usuarios con rol 'inventaries'
3. Hacer clic en "Editar" en algún usuario
4. Deberías ver la interfaz de asignación
```

---

## 5️⃣ INTEGRACIÓN CON CONTROLADORES EXISTENTES

### Paso 1: Filtrar Almacenes en WarehouseInventoryTransferController

**Archivo:** `app/Http/Controllers/Inventaries/WarehouseInventoryTransferController.php`

En el método `index()`, agregar filtrado:

```php
public function index()
{
    $user = auth()->user();

    // Solo mostrar almacenes asignados al usuario
    $warehouses = $user->warehouses()
        ->where('available', true)
        ->get();

    // Si el usuario no tiene almacenes, mostrar error
    if ($warehouses->isEmpty()) {
        return back()->with('error', 'No tienes almacenes asignados');
    }

    return view('warehouses.views.warehouse.transfers.index', [
        'warehouses' => $warehouses,
    ]);
}
```

### Paso 2: Verificar Permisos en Búsqueda

En `searchProduct()`, agregar validación:

```php
public function searchProduct(Request $request)
{
    $user = auth()->user();

    // Verificar que el usuario tiene acceso a operaciones de transferencia
    if ($user->transferWarehouses()->count() === 0) {
        return response()->json([
            'success' => false,
            'message' => 'No tienes permisos para transferir productos',
        ], 403);
    }

    // Continuar con la lógica existente...
}
```

### Paso 3: Validar Acceso al Almacén en Transferencia

En `transfer()`, agregar validación:

```php
public function transfer(Request $request)
{
    $user = auth()->user();

    // Validar acceso al almacén origen
    if (!$user->canTransferInWarehouse($request->from_section_id)) {
        return response()->json([
            'success' => false,
            'message' => 'No tienes permiso para transferir en este almacén',
        ], 403);
    }

    // Continuar con validaciones existentes...
}
```

---

## 6️⃣ CONFIGURACIÓN INICIAL DE DATOS

### Crear Datos de Prueba

```bash
php artisan tinker

# Crear usuario de inventario
use App\Models\User;
$user = User::create([
    'firstname' => 'Juan',
    'lastname' => 'Pérez',
    'email' => 'juan@example.com',
    'password' => bcrypt('password'),
    'available' => true,
]);

# Asignarle rol
$user->assignRole('inventaries');

# Obtener algunos almacenes
use App\Models\Warehouse\Warehouse;
$warehouses = Warehouse::take(3)->get();

# Asignar almacenes
foreach ($warehouses as $index => $warehouse) {
    $user->assignWarehouse(
        $warehouse->id,
        $index === 0, // El primero es predeterminado
        true, // Puede transferir
        true  // Puede hacer inventarios
    );
}

# Verificar
$user->warehouses()->count(); // 3
$user->defaultWarehouse()->name;

exit
```

---

## 7️⃣ VERIFICAR AUDITORÍA

### Ver Logs de Códigos de Barras

```bash
# Ver últimas líneas del log
tail -f storage/logs/barcode.log

# O desde artisan tinker
php artisan tinker
> tail('storage/logs/barcode.log');
exit
```

### Ver Movimientos en BD

```bash
php artisan tinker

use App\Models\Warehouse\WarehouseInventoryMovement;

# Ver últimos movimientos
WarehouseInventoryMovement::latest()
    ->limit(10)
    ->get()
    ->map(function($m) {
        return [
            'tipo' => $m->movement_type,
            'producto' => $m->product?->title,
            'cantidad' => $m->quantity_delta,
            'usuario' => $m->user?->full_name,
            'fecha' => $m->recorded_at,
        ];
    });

exit
```

---

## 8️⃣ CHECKLIST PRE-PRODUCCIÓN

### Seguridad
- [ ] Verificar que solo admins pueden acceder a `/manager/warehouse-assignment`
- [ ] Verificar que solo usuarios de inventario ven sus almacenes
- [ ] Verificar que los permisos se validan en backend (no solo frontend)
- [ ] Revisar logs para detectar anomalías

### Rendimiento
- [ ] Verificar que no hay N+1 queries
- [ ] Verificar que los índices están creados (ya lo hace migración)
- [ ] Probar con muchos almacenes (100+)
- [ ] Probar con muchos usuarios (50+)

### Funcionalidad
- [ ] Lectura de códigos de barras funciona
- [ ] Transferencia de productos funciona
- [ ] Asignación de almacenes funciona
- [ ] Permisos se validan correctamente
- [ ] Logs se generan correctamente

### Compatibilidad
- [ ] Rutas existentes no se rompieron
- [ ] Controladores existentes funcionan
- [ ] Migraciones se ejecutaron sin errores
- [ ] Vistas se renderizan correctamente

---

## 9️⃣ TROUBLESHOOTING

### Problema: "TokenMismatchException" en formularios

**Solución:** Verificar que hay `@csrf` en las vistas
```blade
<form method="POST" action="...">
    @csrf
    ...
</form>
```

### Problema: AJAX no funciona

**Solución:** Verificar que se envía el token CSRF
```javascript
fetch(url, {
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    // ...
});
```

### Problema: Permisos denegados

**Solución:** Verificar que el usuario tiene:
1. Rol `inventaries`
2. Almacenes asignados en tabla `user_warehouse`
3. Permisos activados (`can_inventory`, `can_transfer`)

### Problema: Logs no se generan

**Solución:** Verificar que:
1. Los canales están configurados en `config/logging.php`
2. El directorio `storage/logs` existe y es escribible
3. Los permisos de archivos son correctos: `chmod 755 storage/logs`

### Problema: Migraciones no se ejecutan

**Solución:**
```bash
# Rollback y volver a migrar
php artisan migrate:rollback

# Ejecutar migraciones de nuevo
php artisan migrate
```

---

## 🔟 OPTIMIZACIONES FUTURAS

### Corto Plazo
1. Caché de almacenes asignados
   ```php
   $warehouses = cache()->remember("user.{$user->id}.warehouses", 3600, function() {
       return $user->warehouses()->get();
   });
   ```

2. Índices adicionales en `user_warehouse`
3. Endpoint para cambios masivos

### Mediano Plazo
1. Integración con WebSocket para actualizaciones en tiempo real
2. Dashboard mejorado con estadísticas
3. Reportes avanzados de transferencias

### Largo Plazo
1. Machine learning para predicción de movimientos
2. Integración con escáner de código de barras profesional
3. Mobile app para operarios

---

## 📞 CONTACTO Y SOPORTE

### Documentación
- `BARCODE_AND_TRANSFER_IMPLEMENTATION.md` - Detalles técnicos
- `USER_WAREHOUSE_ASSIGNMENT_GUIDE.md` - Guía de uso
- `IMPLEMENTATION_SUMMARY_COMPLETE.md` - Resumen completo

### Logs para Debug
```bash
# Barcode readings
tail -f storage/logs/barcode.log

# Inventory movements
tail -f storage/logs/inventory.log

# Admin actions
tail -f storage/logs/admin.log

# Laravel general
tail -f storage/logs/laravel.log
```

---

## ✅ CHECKLIST FINAL

- [ ] Migraciones ejecutadas (`php artisan migrate`)
- [ ] Logs configurados (`config/logging.php`)
- [ ] Usuarios creados y asignados a almacenes
- [ ] UI accesible (`/manager/warehouse-assignment`)
- [ ] Códigos de barras pueden ser leídos
- [ ] Transferencias funcionen
- [ ] Permisos se validen
- [ ] Tests pasados (`php artisan test`)
- [ ] Documentación revisada
- [ ] Logs monitoreados

---

**¡Listo para Producción! 🚀**

Una vez completados todos estos pasos, el sistema estará completamente funcional y listo para ser usado en producción.

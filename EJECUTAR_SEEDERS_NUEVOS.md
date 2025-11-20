# 🌱 EJECUTAR SEEDERS CON NUEVO LAYOUT

## INSTRUCCIONES SIMPLES

### Paso 1: Abre PowerShell como Administrador
- Presiona `Win + X` y selecciona "Windows PowerShell (Administrador)"

### Paso 2: Ve a la carpeta del proyecto
Copia y pega esto:
```powershell
cd C:\Users\functionbytes\Herd\webadmin
```

### Paso 3: Ejecuta las migraciones primero
Copia y pega esto:
```powershell
php artisan migrate
```

Espera a que termine. Deberías ver mensajes verdes de éxito.

### Paso 4: Ejecuta los seeders NUEVOS
Copia y pega esto:
```powershell
php artisan db:seed --class=WarehouseSeedersV2
```

O para ejecutar automáticamente a través del DatabaseSeeder:
```powershell
php artisan db:seed
```

Espera a que termine. Verás mensajes como:
```
🗑️  Limpiando datos previos...
✅ Creando pisos...
✅ 3 pisos creados
✅ Creando estilos de estanterías...
✅ 2 estilos de estanterías creados
✅ Creando estantes y posiciones de inventario...
✅ 18 estantes creados
✅ Varias centenas de posiciones de inventario creadas
✅ ¡Sistema de almacén sembrado exitosamente!
```

### Paso 5: Verifica que funcionó
Copia y pega esto:
```powershell
php artisan tinker
```

Dentro de tinker, escribe uno por uno:

```php
>>> App\Models\Warehouse\WarehouseFloor::count();
```
Debería mostrar: `3`

```php
>>> App\Models\Warehouse\WarehouseLocationStyle::count();
```
Debería mostrar: `2`

```php
>>> App\Models\Warehouse\WarehouseLocation::count();
```
Debería mostrar: `18`

```php
>>> App\Models\Warehouse\WarehouseInventorySlot::count();
```
Debería mostrar: `500+` (dependiendo de la cantidad de items)

Para salir, escribe:
```php
>>> exit()
```

---

## ✅ SI TODO FUNCIONÓ

La base de datos ya está poblada. Ahora puedes:

1. **Acceder al panel manager:**
   - Abre tu navegador
   - Ve a: `http://localhost:8000/manager/warehouse/floors`
   - Verás los 3 pisos creados

2. **Acceder sin autenticación:**
   - Ve a: `http://localhost:8000/warehouse/floors`

3. **Ver el mapa interactivo:**
   - Ve a: `http://localhost:8000/manager/warehouse/map`
   - O: `http://localhost:8000/warehouse/map`

---

## ❌ SI ALGO FALLA

### Error: "Class 'WarehouseSeedersV2' not found"
Ejecuta:
```powershell
php artisan optimize
php artisan cache:clear
```

Luego intenta nuevamente.

### Error: "SQLSTATE[HY000]"
Las migraciones previas fallaron. Ejecuta:
```powershell
php artisan migrate:refresh
```

Luego:
```powershell
php artisan db:seed --class=WarehouseSeedersV2
```

### Error: "Access denied for database"
- Verifica que la base de datos configurada en `.env` existe
- Verifica que el usuario MySQL/PostgreSQL tiene permisos

---

## 📋 RESUMEN RÁPIDO

```powershell
cd C:\Users\functionbytes\Herd\webadmin
php artisan migrate
php artisan db:seed --class=WarehouseSeedersV2
```

¡Listo! Ya está.

---

**¿Aún no funciona?** Copia el error que ves y verifica el archivo de logs: `storage/logs/laravel.log`

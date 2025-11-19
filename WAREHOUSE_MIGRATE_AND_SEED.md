# 🏭 WAREHOUSE - MIGRACIÓN Y SIEMBRA DE DATOS

**Última actualización:** 2025-11-17
**Estado:** Listo para ejecutar

---

## 📋 ANTES DE COMENZAR

Asegúrate de:
- ✅ Estar en la carpeta raíz del proyecto: `C:\Users\functionbytes\Herd\webadmin`
- ✅ Tener PHP instalado y disponible en la terminal
- ✅ Tener Composer instalado
- ✅ La base de datos esté configurada en `.env`
- ✅ Las tablas antiguas warehouse (si existen) estén respaldadas

---

## 🚀 PASO A PASO: EJECUTAR MIGRACIONES Y SEEDERS

### OPCIÓN 1: Ejecución Completa (RECOMENDADO)

Ejecuta todos los pasos en orden. Abre PowerShell o CMD y corre:

```powershell
# 1. Asegúrate de estar en la carpeta correcta
cd C:\Users\functionbytes\Herd\webadmin

# 2. Ejecutar migraciones (crear tablas)
php artisan migrate

# 3. Ejecutar seeders del warehouse
php artisan db:seed --class=WarehouseSeeder

# 4. Verificar que se crearon correctamente
php artisan tinker
```

Dentro de tinker, ejecuta:
```php
>>> App\Models\Warehouse\Floor::count();
>>> App\Models\Warehouse\StandStyle::count();
>>> App\Models\Warehouse\Stand::count();
>>> App\Models\Warehouse\InventorySlot::count();
>>> exit()
```

Los números esperados son:
- Floors: **4**
- StandStyles: **3**
- Stands: **15+**
- InventorySlots: **1000+**

---

### OPCIÓN 2: Paso a Paso Individual

Si prefieres ejecutar cada seeder por separado:

```powershell
cd C:\Users\functionbytes\Herd\webadmin

# Crear las tablas
php artisan migrate

# Crear pisos
php artisan db:seed --class=FloorSeeder

# Crear estilos
php artisan db:seed --class=StandStyleSeeder

# Crear estanterías
php artisan db:seed --class=StandSeeder

# Crear posiciones de inventario
php artisan db:seed --class=InventorySlotSeeder
```

---

### OPCIÓN 3: Script Batch (Windows)

Crea un archivo `run-migration.bat` en la carpeta raíz:

```batch
@echo off
echo ====================================
echo Migrando tablas del warehouse...
echo ====================================
cd C:\Users\functionbytes\Herd\webadmin

php artisan migrate

echo.
echo ====================================
echo Sembrando datos del warehouse...
echo ====================================
php artisan db:seed --class=WarehouseSeeder

echo.
echo ====================================
echo Verificando datos...
echo ====================================
php artisan tinker

pause
```

Luego ejecuta haciendo doble clic: `run-migration.bat`

---

## ⚠️ SI ALGO SALE MAL

### Error: "Class Floor not found"
**Solución:**
```powershell
php artisan optimize
php artisan config:cache
php artisan cache:clear
```

### Error: "SQLSTATE[HY000]: General error: 1005"
**Solución:**
```powershell
# Verificar que las tablas antiguas no causen conflicto
php artisan migrate:rollback
php artisan migrate
```

### Error: "Access denied for user"
**Solución:**
- Verificar credenciales en `.env`
- Asegurarse de que la base de datos existe
- Verificar permisos MySQL/PostgreSQL

### Limpiar y empezar de nuevo
```powershell
# CUIDADO: Esto eliminará todas las tablas warehouse
php artisan migrate:rollback

# Luego ejecutar nuevamente
php artisan migrate
php artisan db:seed --class=WarehouseSeeder
```

---

## 📊 QUÉ SE CREA

### Tablas de Base de Datos (4)
1. **warehouse_floors** - 4 pisos/plantas
2. **warehouse_stand_styles** - 3 estilos (ROW, ISLAND, WALL)
3. **warehouse_stands** - 15+ estanterías físicas
4. **warehouse_inventory_slots** - 1000+ posiciones de inventario

### Datos de Prueba
```
Pisos (Floors):
├── P1 - Planta 1 (principal)
├── P2 - Planta 2 (almacenamiento)
├── P3 - Planta 3 (poco movimiento)
└── S0 - Sótano (refrigerados)

Estilos (Stand Styles):
├── ROW - Pasillo Lineal (2 caras, 4 niveles, 6 secciones)
├── ISLAND - Isla Central (4 caras, 3 niveles, 5 secciones)
└── WALL - Pared (1 cara, 5 niveles, 8 secciones)

Estanterías (Stands):
├── ~15 estanterías distribuidas entre pisos
├── Posicionadas en coordenadas X,Y
└── Con capacidad variable

Posiciones (Inventory Slots):
├── ~48 posiciones por estantería ISLAND
├── ~48 posiciones por estantería ROW
├── ~40 posiciones por estantería WALL
└── Algunas con productos asignados (ejemplo)
```

---

## ✅ VERIFICACIÓN POST-INSTALACIÓN

```powershell
# Acceder a la aplicación
php artisan serve

# En navegador:
# http://localhost:8000/manager/warehouse/floors
# http://localhost:8000/manager/warehouse/map
# http://localhost:8000/warehouse/floors (acceso público)
```

---

## 📝 ARCHIVOS SEEDER INVOLUCRADOS

| Archivo | Líneas | Rol |
|---------|--------|-----|
| `database/seeders/FloorSeeder.php` | 59 | Crea 4 pisos |
| `database/seeders/StandStyleSeeder.php` | 61 | Crea 3 estilos |
| `database/seeders/StandSeeder.php` | Dinámico | Crea 15+ stands |
| `database/seeders/InventorySlotSeeder.php` | Dinámico | Crea 1000+ slots |
| `database/seeders/WarehouseSeeder.php` | 58 | Orquesta los anteriores |

---

## 🔗 RUTAS PARA ACCEDER DESPUÉS

### Autenticadas (Manager)
- `/manager/warehouse/floors` - Gestionar pisos
- `/manager/warehouse/styles` - Gestionar estilos
- `/manager/warehouse/stands` - Gestionar estanterías
- `/manager/warehouse/slots` - Gestionar posiciones
- `/manager/warehouse/map` - Mapa interactivo

### Públicas (Sin autenticación)
- `/warehouse/floors` - Ver pisos
- `/warehouse/styles` - Ver estilos
- `/warehouse/stands` - Ver estanterías
- `/warehouse/slots` - Ver posiciones
- `/warehouse/map` - Ver mapa

---

## 💡 CONSEJOS

1. **Primera vez:** Usa "OPCIÓN 1" para hacerlo todo de una vez
2. **Debugging:** Usa `php artisan tinker` para inspeccionar datos
3. **Reset:** Si necesitas limpiar, usa `php artisan migrate:rollback`
4. **Performance:** Después de seeders, ejecuta `php artisan optimize`

---

**¿Preguntas o errores?** Consulta los logs en `storage/logs/laravel.log`


# 🚀 GUÍA DE EJECUCIÓN DE MIGRACIONES

**Fecha:** 17 de Noviembre de 2025
**Migraciones Pendientes:** 6

---

## ⚡ EJECUCIÓN RÁPIDA

### Opción 1: Desde Terminal (Recomendado)

```bash
# Navegar al directorio del proyecto
cd C:\Users\functionbytes\Herd\webadmin

# Ejecutar migraciones
php artisan migrate

# Verificar estado
php artisan migrate:status
```

### Opción 2: Usando Herd

Si usas Herd, puedes ejecutar desde la interfaz gráfica:

1. Abre Herd
2. Localiza el proyecto "webadmin"
3. Ve a la sección "Migrations"
4. Haz clic en "Migrate"

---

## 📋 MIGRACIONES A EJECUTAR

### 1. ModifyWarehouseFloorsAddInventarieId
**Fecha:** 2025_11_17_000050
**Tabla:** `warehouse_floors`
**Cambios:**
- Agrega columna `inventarie_id` (FK)
- Agrega índice

### 2. ModifyLocationsTable
**Fecha:** 2025_11_17_000051
**Tabla:** `locations`
**Cambios:**
- Agrega columnas: `floor_id`, `inventarie_id`, `style_id`
- Agrega campos de configuración: `code`, `title`, `description`, `total_faces`, `total_levels`, `total_sections`, `capacity`
- Agrega índices
- **IMPORTANTE:** Si la tabla ya tiene algunos de estos campos, los verificará antes de agregar

### 3. ModifyWarehouseInventorySlotsTable
**Fecha:** 2025_11_17_000052
**Tabla:** `warehouse_inventory_slots`
**Cambios:**
- Agrega columna `location_id` (FK)
- Agrega columna `last_inventarie_id` (FK)
- Agrega campos: `face`, `level`, `section` (si no existen)
- **IMPORTANTE:** `stand_id` se mantiene por backward compatibility

### 4. CreateWarehouseInventoryMovementsTable
**Fecha:** 2025_11_17_000053
**Tabla:** `warehouse_inventory_movements` (NUEVA)
**Crear:**
- Tabla completa para auditoría de movimientos
- 15+ columnas
- 8+ índices
- 5 FK

### 5. CreateInventarieOperationsTable
**Fecha:** 2025_11_17_000054
**Tabla:** `inventarie_operations` (NUEVA)
**Crear:**
- Tabla para operaciones de inventario
- 8 columnas
- 4 índices
- 3 FK

### 6. ModifyInventarieLocationsTable
**Fecha:** 2025_11_17_000055
**Tabla:** `inventarie_locations`
**Cambios:**
- Agrega columna `operation_id` (FK)
- Agrega índice

---

## ✅ VERIFICACIÓN POST-MIGRACIÓN

Después de ejecutar las migraciones, verifica:

```bash
# Ver estado de todas las migraciones
php artisan migrate:status

# Verificar estructura de tablas
php artisan tinker
> Schema::getColumnListing('warehouse_floors')
> Schema::getColumnListing('locations')
> Schema::getColumnListing('warehouse_inventory_slots')
> Schema::getColumnListing('warehouse_inventory_movements')
> Schema::getColumnListing('inventarie_operations')
> Schema::getColumnListing('inventarie_locations')
```

---

## ⚠️ POTENCIALES PROBLEMAS Y SOLUCIONES

### Problema 1: "Column already exists"
**Causa:** La columna ya está en la tabla
**Solución:** Las migraciones usan `hasColumn()` para verificar antes de agregar

### Problema 2: "Foreign key constraint fails"
**Causa:** Intentar eliminar datos referenciados
**Solución:** Las FK están configuradas con CASCADE, no debería ocurrir

### Problema 3: "Syntax error in migration"
**Causa:** Error en la definición de la migración
**Solución:** Verificar que todos los archivos de migración estén correctamente guardados

### Problema 4: "Table doesn't exist"
**Causa:** Intentar modificar tabla que no existe
**Solución:** Ejecutar migraciones previas primero

---

## 🔄 ROLLBACK (Si es necesario)

### Rollback de una migración específica
```bash
# Deshacer última migración
php artisan migrate:rollback

# Deshacer últimas 5 migraciones
php artisan migrate:rollback --step=5

# Deshacer todas las migraciones
php artisan migrate:reset

# Deshacer y volver a ejecutar todas
php artisan migrate:refresh
```

---

## 📊 DATOS EXISTENTES

### Migración de datos después de ejecutar migraciones

Si hay datos existentes que necesitan actualización:

```bash
# Acceder a tinker
php artisan tinker

# Actualizar datos existentes
App\Models\Warehouse\Floor::whereNull('inventarie_id')->update(['inventarie_id' => 1]);

# Verificar
App\Models\Warehouse\Floor::count()
```

---

## 🧪 TESTING POST-MIGRACIÓN

Después de migrar, prueba los modelos:

```bash
php artisan tinker

# Crear una Inventarie
$inv = App\Models\Inventarie\Inventarie::create([
    'code' => 'PRUEBA',
    'name' => 'Almacén Prueba',
    'available' => true
]);

# Crear un Floor
$floor = App\Models\Warehouse\Floor::create([
    'inventarie_id' => $inv->id,
    'code' => 'P1',
    'name' => 'Planta 1',
    'available' => true
]);

# Crear una Location
$loc = App\Models\Location::create([
    'inventarie_id' => $inv->id,
    'floor_id' => $floor->id,
    'code' => 'PASILLO1',
    'title' => 'Pasillo 1',
    'total_faces' => 2,
    'total_levels' => 3,
    'total_sections' => 5
]);

# Verificar que se crearon los slots automáticamente
$loc->slots()->count() // Debería ser 30 (2×3×5)

# Crear una operación de inventario
$op = App\Models\Inventarie\InventarieOperation::create([
    'inventarie_id' => $inv->id,
    'user_id' => 1
]);

# Verificar que se crearon las ubicaciones
$op->locations()->count() // Debería ser 1
```

---

## 📝 NOTAS IMPORTANTES

1. **Backup:** Hacer backup de BD antes de migrar en producción
2. **Testing:** Ejecutar tests después de migrar
3. **Data:** Verificar integridad de datos después
4. **Logs:** Revisar logs si hay problemas
5. **Performance:** Las migraciones pueden tomar tiempo en BD grandes

---

## 🎯 CHECKLIST POST-EJECUCIÓN

- [ ] Migraciones ejecutadas correctamente
- [ ] `php artisan migrate:status` muestra todas OK
- [ ] Tablas nuevas creadas: `warehouse_inventory_movements`, `inventarie_operations`
- [ ] Nuevas columnas en tabla `locations`
- [ ] Nuevas columnas en tabla `warehouse_floors`
- [ ] Nuevas columnas en tabla `warehouse_inventory_slots`
- [ ] Índices creados correctamente
- [ ] FK sin errores
- [ ] Datos existentes intactos
- [ ] Tests pasando

---

## 📞 SOPORTE

Si encuentras problemas:

1. Revisa los logs en `storage/logs/laravel.log`
2. Ejecuta `php artisan migrate:status` para ver el estado
3. Verifica que todos los archivos de migración están presentes
4. Asegúrate de que la BD está accesible

---

**Estado:** Listo para ejecutar
**Tiempo estimado:** 2-5 minutos
**Reversibilidad:** Sí (con rollback)

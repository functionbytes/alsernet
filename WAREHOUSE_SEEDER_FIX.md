# Fix: Error de Foreign Key Constraints en Seeders

## 🐛 Problema Identificado

**Error:** `SQLSTATE[42000]: Syntax error or access violation: 1701 Cannot truncate a table referenced in a foreign key constraint`

**Causa:** El archivo `WarehouseSeedersV2.php` intentaba truncar tablas sin desactivar las restricciones de foreign key primero.

## ✅ Solución Aplicada

Se modificó `database/seeders/WarehouseSeedersV2.php` para:

1. **Desactivar las restricciones de foreign key** antes de truncar
2. **Truncar las tablas en orden correcto** (dependientes primero)
3. **Reactivar las restricciones** después de limpiar

### Código Corregido

```php
// Desactivar foreign key constraints
\DB::statement('SET FOREIGN_KEY_CHECKS=0;');

// Truncar en orden correcto (dependientes primero)
InventorySlot::truncate();  // Depende de Stand
Stand::truncate();          // Depende de StandStyle y Floor
StandStyle::truncate();     // Independiente
Floor::truncate();          // Independiente

// Reactivar foreign key constraints
\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
```

## 📋 Relaciones de Foreign Keys

```
warehouse_floors
    ↑
    └── warehouse_stands (stand_style_id, floor_id)
            ↑
            └── warehouse_inventory_slots (stand_id)
```

## 🚀 Cómo Usar

### Opción 1: Ejecutar WarehouseSeedersV2 directamente
```bash
php artisan db:seed --class=WarehouseSeedersV2
```
Esta opción limpia todo y crea los datos nuevamente.

### Opción 2: Ejecutar WarehouseSeeder (secuencial)
```bash
php artisan db:seed --class=WarehouseSeeder
```
Esta opción ejecuta los seeders en orden:
1. FloorSeeder
2. StandStyleSeeder
3. StandSeeder
4. InventorySlotSeeder

### Opción 3: Resetear base de datos completamente
```bash
php artisan migrate:refresh --seed --class=WarehouseSeeder
```

## ⚠️ Notas Importantes

- La desactivación de `FOREIGN_KEY_CHECKS` es **solo para desarrollo**
- Nunca usar esto en producción sin supervisión
- Las restricciones se reactivan automáticamente después del truncate
- Los datos truncados **NO pueden recuperarse**

## 📚 Seeders Disponibles

| Seeder | Función |
|--------|---------|
| `FloorSeeder` | Crea pisos (P1, P2, P3, S0) |
| `StandStyleSeeder` | Crea estilos de estanterías (ROW, ISLAND, WALL) |
| `StandSeeder` | Crea estantes individuales |
| `InventorySlotSeeder` | Crea posiciones de inventario |
| `WarehouseSeeder` | Ejecuta todos en orden correcto |
| `WarehouseSeedersV2` | Crea estructura completa con datos de demo |

## 🔧 Archivos Modificados

- ✅ `database/seeders/WarehouseSeedersV2.php` - Fixed foreign key constraint issue

## 📞 Troubleshooting

### Si aún tienes el error:
1. Verifica que MySQL tenga `foreign_key_checks` habilitado
2. Intenta resetear manualmente:
```sql
SET FOREIGN_KEY_CHECKS=0;
TRUNCATE TABLE warehouse_inventory_slots;
TRUNCATE TABLE warehouse_stands;
TRUNCATE TABLE warehouse_stand_styles;
TRUNCATE TABLE warehouse_floors;
SET FOREIGN_KEY_CHECKS=1;
```

3. Ejecuta: `php artisan db:seed --class=WarehouseSeedersV2`

## ✨ Mejoras Futuras

- [ ] Implementar soft deletes en lugar de truncate
- [ ] Agregar validación de integridad de datos
- [ ] Crear seeders específicos por ambiente (dev, staging)

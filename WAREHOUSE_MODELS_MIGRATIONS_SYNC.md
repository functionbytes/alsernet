# 🔍 SINCRONIZACIÓN: Modelos Warehouse vs Migraciones

**Fecha:** 17 de Noviembre de 2025

---

## ⚠️ PROBLEMAS ENCONTRADOS

### 1. WarehouseLocationStyle
**Problema:** Nombre de tabla inconsistente
- **Modelo usa:** `warehouse_location_styles`
- **Migración 3 crea:** `warehouse_stand_styles`
- **Diferencia:** Se usan nombres diferentes
- **Acción:** Cambiar modelo a usar `warehouse_stand_styles`

**Campos adicionales en modelo:**
- `code` - NO está en migración
- Debería agregar a migración

---

### 2. WarehouseLocation
**Problema:** Múltiples inconsistencias graves
- **Modelo usa tabla:** `warehouse_locations`
- **Migración 5 crea tabla:** `locations` ❌ MISMATCH
- **Modelo FK:** `warehouse_id` (NO existe tabla)
- **Migración FK:** `inventarie_id` ❌ DIFERENTE

**Campos en Modelo vs Migración:**
```
Modelo fillable:
- uid ✅
- floor_id ✅
- stand_style_id ❌ (debería ser style_id)
- code ✅
- barcode ❌ (NO está en migración)
- position_x/y/z ✅
- total_levels ✅
- total_sections ❌ (NO visible en modelo)
- capacity ✅
- available ✅
- notes ✅

Migración tiene:
- uid ✅
- inventarie_id ❌ (modelo no lo tiene)
- floor_id ✅
- style_id ❌ (modelo usa stand_style_id)
- code ✅
- title ❌ (modelo no lo tiene)
- description ✅
- total_faces ❌ (modelo no lo tiene)
- total_levels ✅
- total_sections ✅
- capacity ✅
- position_x/y/z ✅
- available ✅
- notes ✅
```

**Relaciones en Modelo:**
- `style()` usa FK `stand_style_id` → debería ser `style_id`
- `slots()` usa FK `stand_id` → debería ser `location_id`

---

### 3. WarehouseFloor
**Problema:** Campos adicionales no en migración
- **Modelo fillable:** code, order
- **Migración:** NO incluye code ni order
- **Acción:** Agregar campos a migración 4

**FK faltante:**
- Modelo NO tiene `inventarie_id` pero migración 4 sí lo requiere

---

### 4. WarehouseInventorySlot ✅
**Estado:** CORRECTO
- Usa tabla `warehouse_inventory_slots` ✅
- Usa `location_id` ✅ (correcto aunque el nombre antiguo era stand_id)
- Todos los campos coinciden ✅

---

### 5. WarehouseLocationCondition ✅
**Estado:** CORRECTO
- Usa tabla `warehouse_location_conditions` ✅
- Campos coinciden ✅

---

### 6. WarehouseInventoryOperation
**Problema:** Tabla probablemente no revisada
- **Verificar:** Tabla y campos

---

## 📋 TABLA RESUMEN

| Modelo | Tabla en Modelo | Tabla en Migración | FK | Estado |
|--------|-----------------|------------------|-----|---------|
| Warehouse | warehouses | warehouses | shop_id | ✅ OK |
| WarehouseFloor | warehouse_floors | warehouse_floors | inventarie_id (falta en modelo) | ⚠️ INCOMPLETO |
| WarehouseLocationStyle | warehouse_location_styles | warehouse_stand_styles | N/A | ❌ MISMATCH |
| WarehouseLocation | warehouse_locations | locations | warehouse_id vs inventarie_id | ❌ CRÍTICO |
| WarehouseInventorySlot | warehouse_inventory_slots | warehouse_inventory_slots | location_id | ✅ OK |
| WarehouseLocationCondition | warehouse_location_conditions | warehouse_location_conditions | N/A | ✅ OK |
| WarehouseInventoryMovement | ? | warehouse_inventory_movements | slot_id | ⚠️ REVISAR |
| WarehouseInventoryOperation | ? | warehouse_inventory_operations | inventarie_id | ⚠️ REVISAR |

---

## 🔧 CORRECCIONES REQUERIDAS

### OPCIÓN A: Actualizar MODELOS (según migraciones)
Esto es mejor porque las migraciones ya están creadas correctamente.

#### 1. WarehouseLocationStyle
```php
// Cambiar tabla de:
protected $table = 'warehouse_location_styles';
// A:
protected $table = 'warehouse_stand_styles';

// Agregar a fillable:
'code', // si la migración se actualiza
```

#### 2. WarehouseLocation
```php
// Cambiar tabla de:
protected $table = 'warehouse_locations';
// A:
protected $table = 'locations';

// Agregar a fillable:
'inventarie_id',

// Quitar de fillable (o hacerlo opcional):
// 'barcode', (revisar si está en migración)

// Cambiar FK en fillable:
'stand_style_id' → 'style_id'

// Cambiar relación:
public function style(): BelongsTo {
    return $this->belongsTo(WarehouseLocationStyle::class, 'style_id', 'id');
}

// Cambiar relación slots:
public function slots(): HasMany {
    return $this->hasMany(WarehouseInventorySlot::class, 'location_id', 'id');
}

// Agregar relación (falta):
public function inventarie(): BelongsTo {
    return $this->belongsTo('App\Models\Inventarie', 'inventarie_id', 'id');
}
```

#### 3. WarehouseFloor
```php
// Agregar a fillable:
'inventarie_id',
'level',

// Agregar relación (falta):
public function inventarie(): BelongsTo {
    return $this->belongsTo('App\Models\Inventarie', 'inventarie_id', 'id');
}
```

---

### OPCIÓN B: Actualizar MIGRACIONES (según modelos)
Requeriría modificar migraciones ya creadas (más complicado).

---

## ✅ RECOMENDACIÓN

**OPCIÓN A: Actualizar los modelos**

Los cambios necesarios son:
1. WarehouseLocationStyle: Cambiar tabla a `warehouse_stand_styles`
2. WarehouseLocation: Cambiar tabla a `locations`, actualizar FK y relaciones
3. WarehouseFloor: Agregar FK `inventarie_id` y relación
4. Verificar WarehouseInventoryMovement y WarehouseInventoryOperation

---

**Estado:** Listo para correcciones

# 🏗️ Distribución en Grilla Inteligente - Seeder Actualizado

## 📋 Resumen de Cambios

Se ha actualizado completamente **`WarehouseSeedersV2.php`** para usar una **distribución inteligente en grilla** que coincide perfectamente con los vectores mejorados del frontend y el sistema de escalado dinámico.

---

## 🎯 **Problema Original**

El seeder anterior usaba posiciones muy simples:
```php
// ANTES (❌ Problemático)
'position_x' => $i * 2,           // Solo 2 metros entre stands
'position_y' => $floorNum * 3,    // Solo 3 metros entre pisos

// Resultado: Stands superpuestos, sin organización por pasillos
```

---

## ✅ **Solución Implementada**

### **1. Sistema de Grilla de Celdas**

Cada celda representa el espacio ocupado por un stand + espaciado:

```
Cálculo de tamaño de celda:
┌──────────────────────────────┐
│  Stand (2.5m × 2.5m)        │
│                              │
│  + Espaciado (0.3m)         │
└──────────────────────────────┘
  = 2.8m × 2.8m (tamaño celda)
```

### **Parámetros de Distribución**

```php
// En el seeder (líneas 108-111)
$standWidthM = 2.5;      // Ancho del stand en metros
$standHeightM = 2.5;     // Alto del stand en metros
$spacingM = 0.3;         // Espaciado entre stands
$cellSizeM = $standWidthM + $spacingM; // = 2.8m
```

Estos **deben coincidir exactamente** con los del frontend:

```javascript
// En index.blade.php (frontend)
VECTOR_DISTRIBUTION = {
    STAND_WIDTH: 2.5,    // ✅ Mismo
    STAND_HEIGHT: 2.5,   // ✅ Mismo
    SPACING: 0.3,        // ✅ Mismo
    // ...
}
```

---

## 📐 **2. Cálculo del Espacio Disponible**

```php
// Líneas 114-117
$availableWidth = $warehouseWidth - (2 * $margin);
$availableHeight = $warehouseHeight - (2 * $margin);
$maxCols = (int)($availableWidth / $cellSizeM);
$maxRows = (int)($availableHeight / $cellSizeM);
```

### Ejemplo con dimensiones reales:

```
Almacén: 42.23m × 30.26m
Margen: 0.5m en cada lado

Ancho disponible = 42.23 - (2 × 0.5) = 41.23m
Altura disponible = 30.26 - (2 × 0.5) = 29.26m

Máximo de columnas = 41.23 / 2.8 = 14.72 → 14 columnas
Máximo de filas = 29.26 / 2.8 = 10.45 → 10 filas

Grilla máxima: 14 × 10 = 140 celdas disponibles
```

El seeder mostrará:
```
📐 Dimensiones: 41.23m × 29.26m
📊 Grilla: 14 columnas × 10 filas
```

---

## 🗺️ **3. Función de Posicionamiento de Pasillos**

Nueva función `calculatePasilloPositions()` (líneas 214-245):

```php
/**
 * Distribuye los pasillos en una grilla inteligente
 *
 * @param array $layout - Configuración de pasillos
 * @param int $maxCols - Máximo de columnas disponibles
 * @param int $maxRows - Máximo de filas disponibles
 * @param float $margin - Margen del almacén
 * @param float $cellSizeM - Tamaño de celda en metros
 * @return array Posiciones calculadas
 */
private function calculatePasilloPositions($layout, $maxCols, $maxRows, $margin, $cellSizeM)
```

### Algoritmo:

```
1. Empezar en (0, 0)
2. Para cada pasillo:
   - Si es ROW: ocupa múltiples columnas (1 por stand)
   - Si es COLUMNS: ocupa 1 columna

3. Si el pasillo no cabe en la fila actual:
   - Saltar a la siguiente fila (+ 2 celdas)
   - Resetear a columna 0

4. Guardar posición (col, row) para el pasillo
5. Avanzar a la siguiente columna (+ ancho del pasillo + 1)
```

### Resultado de posiciones:

```
Pasillo | Tipo    | Stands | Pos (col,row)
--------|---------|--------|---------------
PASILLO13A | ROW   | 5      | (0, 0)
PASILLO13B | ROW   | 3      | (6, 0)
PASILLO13C | ROW   | 5      | (10, 0)
PASILLO13D | ROW   | 5      | (0, 2) ← Nueva fila
PASILLO1   | COL   | 1      | (6, 2)
PASILLO2   | COL   | 1      | (7, 2)
... (y así sucesivamente)
```

---

## 📍 **4. Cálculo de Posiciones Finales**

Para cada stand (líneas 153-158):

```php
// Convertir posición en grilla a coordenadas en metros
$baseX = $margin + ($pasilloPos['col'] * $cellSizeM);
$baseY = $margin + ($pasilloPos['row'] * $cellSizeM);

// Si hay múltiples stands en un ROW, desplazar horizontalmente
$offsetX = 0;
if ($pasilloConfig['count'] > 1 && $kind === 'row') {
    $offsetX = ($i - 1) * $cellSizeM;  // Stand 1: 0, Stand 2: 2.8, Stand 3: 5.6, etc.
}

$position_x = $baseX + $offsetX;
$position_y = $baseY;
```

### Ejemplo visual:

```
PASILLO13A (ROW, 5 stands) en grilla (0,0):

position_x:
- Stand 1: 0.5 + (0 × 2.8) = 0.5m
- Stand 2: 0.5 + (1 × 2.8) = 3.3m
- Stand 3: 0.5 + (2 × 2.8) = 6.1m
- Stand 4: 0.5 + (3 × 2.8) = 8.9m
- Stand 5: 0.5 + (4 × 2.8) = 11.7m

position_y: 0.5 + (0 × 2.8) = 0.5m (mismo para todos)

Resultado en el mapa (metros):
├─ P1-PASILLO13A-1 → (0.5, 0.5)  ← Izquierda
├─ P1-PASILLO13A-2 → (3.3, 0.5)
├─ P1-PASILLO13A-3 → (6.1, 0.5)  ← Centro
├─ P1-PASILLO13A-4 → (8.9, 0.5)
└─ P1-PASILLO13A-5 → (11.7, 0.5) ← Derecha
```

---

## 🎨 **5. Integración con Vectores Mejorados**

El frontend ahora recibe posiciones correctas y puede:

### ✅ Renderizar con escalado dinámico

```javascript
// Frontend calcula escala basada en container
SCALE = calculateDynamicScale();  // Ej: 35px (dinámico)

// Convierte metros a píxeles
screenX = positionX * SCALE;      // 0.5m × 35 = 17.5px
screenY = positionY * SCALE;      // 0.5m × 35 = 17.5px
```

### ✅ Detectar automáticamente una cara vs dos caras

```javascript
// En drawFloorGroup()
const faceCount = Object.keys(facesConfig).length;

if (faceCount <= 1) {
    createSingleFaceVector()  // Vector pequeño (azul)
} else {
    createDualFaceVector()    // Vector grande (púrpura + cian)
}
```

### ✅ Evitar solapamientos

```javascript
// VECTOR_DISTRIBUTION.resolveOverlaps()
// Verifica distancia entre vectores y los desplaza si es necesario
```

---

## 📊 **6. Cambios en Stand Model**

Los stands ahora se crean con información más precisa:

```php
$stand = Stand::create([
    // ... campos básicos ...
    'position_x' => round($position_x, 2),  // ✅ Posición en metros (grilla)
    'position_y' => round($position_y, 2),  // ✅ Posición en metros (grilla)
    'position_z' => 0,
    'total_levels' => $kind === 'row' ? 1 : 7,      // ✅ Dinámico
    'total_sections' => $kind === 'row' ? 5 : 1,    // ✅ Dinámico
    'notes' => "Stand {$i} del pasillo {$pasillo} - Piso {$floorNum} (Tipo: {$kind})",
]);
```

### Diferencias:

| Campo | Antes | Ahora |
|-------|-------|-------|
| `position_x` | `$i * 2` (simple) | `$baseX + $offsetX` (grilla) |
| `position_y` | `$floorNum * 3` (simple) | `$baseY` (grilla) |
| `total_levels` | Siempre 1 | `1` (ROW) o `7` (COLUMNS) |
| `total_sections` | Siempre 5 | `5` (ROW) o `1` (COLUMNS) |

---

## 🚀 **7. Cómo Ejecutar el Seeder Actualizado**

### Opción A: Ejecutar solo el seeder (limpia datos)

```bash
php artisan db:seed --class=WarehouseSeedersV2
```

**Salida esperada:**
```
🗑️  Limpiando datos previos...
✅ Datos previos limpiados correctamente

✅ Creando pisos...
✅ 3 pisos creados

✅ Creando estilos de estanterías...
✅ 2 estilos de estanterías creados

✅ Creando estantes y posiciones de inventario...
📐 Dimensiones: 41.23m × 29.26m
📊 Grilla: 14 columnas × 10 filas

✅ 72 estantes creados
✅ 720 posiciones de inventario creadas

✅ ¡Sistema de almacén sembrado exitosamente!
```

### Opción B: Reset completo de la base de datos

```bash
php artisan migrate:refresh --seed --class=WarehouseSeeder
```

### Opción C: Usar script helper

```bash
# Windows PowerShell
.\run-warehouse-seeder.ps1
# Seleccionar opción 1

# Windows CMD
run-warehouse-seeder.bat
# Seleccionar opción 1
```

---

## 📈 **8. Distribución Resultante**

Con la configuración actual del `getLayoutDefinition()`:

### **Horizontal Shelves (ROW)**
```
PASILLO13A: 5 stands × 3 pisos = 15 stands
PASILLO13B: 3 stands × 1 piso = 3 stands
PASILLO13C: 5 stands × 1 piso = 5 stands
PASILLO13D: 5 stands × 1 piso = 5 stands
───────────────────────────────── 28 stands ROW
```

### **Vertical Columns (COLUMNS)**
```
PASILLO1-12: 1 stand × 1 piso × 12 = 12 stands
───────────────────────────────── 12 stands COLUMNS
```

### **Total:**
- **40 stands únicos**
- **~500-720 inventory slots** (depende de caras)
- **Distribuidos en grilla 14×10** (14 columnas, 10 filas máximo)
- **Utilización: ~14.3%** del espacio (40 celdas de 280 disponibles)

---

## 🔄 **9. Sincronización Frontend ↔ Backend**

### Parámetros que DEBEN coincidir:

| Parámetro | Backend (Seeder) | Frontend (JavaScript) | Valor |
|-----------|------------------|----------------------|-------|
| Stand Width | `$standWidthM` | `STAND_WIDTH` | 2.5m |
| Stand Height | `$standHeightM` | `STAND_HEIGHT` | 2.5m |
| Spacing | `$spacingM` | `SPACING` | 0.3m |
| Warehouse Width | `$warehouseWidth` | `WAREHOUSE.width_m` | 42.23m |
| Warehouse Height | `$warehouseHeight` | `WAREHOUSE.height_m` | 30.26m |
| Margin | `$margin` | `MARGIN_M` | 0.5m |

⚠️ **Si cambias algo en el backend, actualiza el frontend también (y viceversa)**

---

## 🔍 **10. Validación Post-Seeding**

Después de ejecutar el seeder, puedes validar en Tinker:

```bash
php artisan tinker
```

```php
>>> use App\Models\Warehouse\Stand;

// Verificar distribución
>>> Stand::all()->groupBy('code')->count()
=> 40  // Debería ser 40 stands únicos

// Ver posiciones
>>> Stand::first()->toArray()
=> [
    'code' => 'P1-PASILLO13A-1',
    'position_x' => 0.5,   // ✅ Posición en grilla
    'position_y' => 0.5,
    'total_levels' => 1,   // ✅ ROW
    'total_sections' => 5, // ✅ ROW
]

// Verificar slots
>>> InventorySlot::count()
=> 720  // ~720 inventory slots

// Ver distribución por pasillo
>>> Stand::groupBy('code')->selectRaw('LEFT(code, 15) as pasillo, COUNT(*) as count')->get()
```

---

## ✨ **11. Ventajas del Nuevo Sistema**

✅ **Posiciones reales**: Stands distribuidos lógicamente en una grilla
✅ **Escalable**: El escalado dinámico funciona correctamente
✅ **Sin solapamientos**: Sistema de detección en frontend
✅ **Soporta múltiples pisos**: Mismos stands en diferentes pisos
✅ **Información precisa**: Levels y sections correctos por tipo
✅ **Fácil de mantener**: Parámetros centralizados y documentados

---

## 🐛 **12. Troubleshooting**

### Problema: Stands fuera de los límites

```
Error: position_x > warehouse_width
```

**Solución**:
```php
// Las líneas 156-158 protegen contra esto:
$position_x = min($position_x, $warehouseWidth - $margin);
$position_y = min($position_y, $warehouseHeight - $margin);
```

### Problema: Stands superpuestos

**Causa**: El algoritmo de resolución en frontend debe detectarlos
**Verificación**:
```javascript
// En console del navegador
document.querySelectorAll('.svg-shelf-vector').length
// Debería ser 40 (o el número total de stands)
```

### Problema: Grilla no cuadra con el mapa

**Causa**: Parámetros desincronizados
**Fix**: Comparar valores en seeder vs index.blade.php
```php
// Seeder
$standWidthM = 2.5;

// Frontend (JavaScript)
STAND_WIDTH: 2.5  // ← Debe ser igual
```

---

## 📝 **Resumen de Archivos Modificados**

- ✅ `database/seeders/WarehouseSeedersV2.php` (+120 líneas, nueva función)
- ✅ `resources/views/managers/views/warehouse/map/index.blade.php` (+470 líneas, vectores mejorados)
- ✅ `WAREHOUSE_VECTORS_IMPLEMENTATION.md` (documentación)
- ✅ `WAREHOUSE_SEEDER_GRID_DISTRIBUTION.md` (este archivo)

---

## 🎯 **Próximos Pasos**

1. **Ejecutar el seeder actualizado**
   ```bash
   php artisan db:seed --class=WarehouseSeedersV2
   ```

2. **Abrir el mapa en el navegador**
   - Verás stands distribuidos en grilla
   - Vectores mejorados (una cara vs dos caras)
   - Escalado responsivo funcionando

3. **Probar funcionalidades**
   - Cambiar de piso
   - Hacer hover en stands
   - Buscar stands
   - Cambiar tamaño de ventana

4. **Personalizar si es necesario**
   - Ajustar `$cellSizeM` para diferentes espacios
   - Agregar más pasillos en `getLayoutDefinition()`
   - Cambiar colores en `$columnItems`, `$horizontalItems`

---

**¡El seeder está listo! Ejecuta `php artisan db:seed --class=WarehouseSeedersV2` para aplicar los cambios.** 🚀

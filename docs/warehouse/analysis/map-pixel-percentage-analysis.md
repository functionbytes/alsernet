# Análisis: Manejo de Píxeles y Porcentajes en el Mapa de Almacén

## Resumen Ejecutivo

El modelo `WarehouseLocationStyle` y el controlador `WarehouseMapController` están **correctamente diseñados para soportar AMBOS sistemas**:
- **Píxeles (px)**: Para posicionamiento absoluto en metros (`position_x`, `position_y`)
- **Porcentajes (%)**: Para distribución dentro de estantes (secciones/niveles)

---

## 1. Sistema Actual en el Código

### 1.1 WarehouseLocation (Métricos en Metros)

```php
protected $fillable = [
    'position_x',  // ← Offset en metros desde pared derecha
    'position_y',  // ← Offset en metros desde pared superior
    'total_levels', // ← Número de niveles (no píxeles, conteo)
];
```

**Conversión a píxeles en el mapa:**
```javascript
const SCALE = 30; // 1 metro = 30 píxeles en SVG
const startX = rightInnerX - ((sec.start?.offsetRight_m ?? 0) * SCALE);
const startY = topInnerY + ((sec.start?.offsetTop_m ?? 0) * SCALE);
```

### 1.2 WarehouseLocationStyle (Dimensiones Métricas)

```php
protected $fillable = [
    'width',   // Ancho del estante en metros
    'height',  // Alto del estante en metros
    'default_levels',    // Número de niveles por defecto
    'default_sections',  // Secciones por nivel/cara
];
```

**En WarehouseMapController:**
```php
'shelf' => [
    'w_m' => (float)($stand->style?->width ?? 1.0),    // metros
    'h_m' => (float)($stand->style?->height ?? 1.0),   // metros
],
```

### 1.3 Porcentajes en Modales (Secciones Internas)

**En el script del mapa (`pctToY` y `MODAL_PRESETS`):**

```javascript
// Distribución de botones dentro de un estante (porcentajes)
const MODAL_PRESETS = [
    {
        faces: 2,
        id: '1-shelf-2faces',
        vPaddingPct: { top: 30, bottom: 70 },  // ← 30-70% de altura disponible
        faceDefaults: { hAlignPct: 50 }        // ← Centrado horizontalmente
    },
];

// Cálculo de centros de botones
function getBarsAndCenters(count, topPct, bottomPct) {
    const n = Math.max(1, Number(count) || 1);
    const step = (bottomPct - topPct) / n;
    const centers = Array.from({ length: n },
        (_, i) => Math.round(((bars[i] + bars[i + 1]) / 2) * 100) / 100
    );
    return { centers }; // retorna porcentajes: 0-100%
}

function pctToY(pct) {
    return 70 + (460 * (pct / 100)); // Convierte % a píxeles SVG
}
```

---

## 2. Flujo de Datos: De Metros a Píxeles a Porcentajes

```
┌─────────────────────────────────────────────────────────────┐
│ BASE DE DATOS (WarehouseLocation + WarehouseLocationStyle) │
├─────────────────────────────────────────────────────────────┤
│ position_x: 5.5 m  (desde pared derecha)                   │
│ position_y: 2.3 m  (desde pared superior)                  │
│ width: 1.85 m      (ancho del estante)                     │
│ height: 1.0 m      (alto del estante)                      │
│ total_levels: 3    (número de niveles/divisiones)          │
└─────────────────────────────────────────────────────────────┘
                         ↓
              [WarehouseMapController]
                         ↓
        JSON (metros convertidos a escala)
        {
            "start": { "offsetRight_m": 5.5, "offsetTop_m": 2.3 },
            "shelf": { "w_m": 1.85, "h_m": 1.0 }
        }
                         ↓
      [JavaScript renderizado en SVG]
                         ↓
    PASO 1: METROS → PÍXELES (posicionamiento global)
    startX = rightInnerX - (5.5 * 30) = ... px
    startY = topInnerY + (2.3 * 30) = ... px
                         ↓
    PASO 2: RECTS SVG (estantes en píxeles absolutos)
    <rect x="200" y="150" width="55.5" height="30" />
                         ↓
    PASO 3: PORCENTAJES INTERNOS (distribución dentro del estante)
    Niveles: 3 → Altura entre porcentajes: 33.33% cada uno
    Botones centro: [16.67%, 50%, 83.33%] ← porcentajes
                         ↓
    PASO 4: PORCENTAJES → PÍXELES SVG LOCALES
    pctToY(16.67%) = 70 + (460 * 0.1667) = ~147 px
    pctToY(50%) = 70 + (460 * 0.50) = ~300 px
```

---

## 3. Capacidades Actuales del Sistema

### ✅ Soportados

| Aspecto | Método | Unidad | Ejemplo |
|---------|--------|--------|---------|
| **Posicionamiento global** | Métricos | metros | `position_x: 5.5`, `position_y: 2.3` |
| **Dimensiones estante** | Métricos | metros | `width: 1.85 m`, `height: 1.0 m` |
| **Escala visualización** | Factor escala | px/m | `SCALE = 30` (1m = 30px) |
| **Distribución interna** | Porcentajes | 0-100% | `vPaddingPct: {top: 30, bottom: 70}` |
| **Alineación botones** | Porcentajes | 0-100% | `hAlignPct: 50` (centrado) |
| **Ocupación slots** | Cantidad/Porcentaje | % | `getOccupancyPercentage()` |

### ⚠️ Limitaciones Actuales

1. **No hay soporte para píxeles absolutos en posiciones de estantes**
   - Todo se gestiona en metros
   - Útil para warehouse real, pero inflexible para UX

2. **No hay soporte para "secciones divididas" en modelo actual**
   - `default_sections` es un contador, no dimensiones específicas
   - Las secciones se crean en `WarehouseLocationSection`, no en `WarehouseLocationStyle`

3. **Datos de secciones incompletos en respuesta JSON**
   - `WarehouseMapController` no devuelve configuración de secciones por defecto
   - Los botones en modal se generan dinámicamente del conteo

---

## 4. Cómo el Sistema Maneja "Píxeles por Secciones"

### Concepto Actual (Implícito)

Aunque el código habla en "porcentajes", internamente **gestiona píxeles por sección**:

```javascript
// Si un estante tiene 5 ubicaciones (secciones)
// Altura total en píxeles: 30px (1.0m × SCALE)

// Distribución con vPaddingPct: {top: 30, bottom: 70}
const topPx = 30; // 30% de 30px = 9px (margen superior)
const bottomPx = 70; // 70% de 30px = 21px (área útil)
const step = (21 - 9) / 5 = 2.4px por sección

// Cada sección ocupa ~2.4px, pero los botones están centrados en porcentajes
```

### Visualización en Modal

```javascript
// Preset para 5 estantes con 1 cara:
{
    faces: 1,
    id: '5-shelf-1face',
    vPaddingPct: { top: 6, bottom: 94 },  // Menos margen superior = más espacio
    faceDefaults: {
        hAlignPct: 50,
        button: { minWidth: 120, height: 28, fontSize: 10, borderRadius: 6 }
    }
}
```

Los botones se escalan relativamente:
- **Alto del botón**: 28px (fijo en el modal SVG de 600px)
- **Espacio disponible**: 460px en el SVG (porcentaje de 600px)
- **Proporción real**: 28/460 ≈ 6% del area útil por botón

---

## 5. Cómo Extender para "Píxeles por Secciones" Explícito

### Opción A: Agregar Configuración Avanzada (Recomendado)

```php
// Nueva tabla: warehouse_location_sections_config
Schema::create('warehouse_location_section_configs', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('style_id');
    $table->string('face'); // 'left', 'right', 'front', 'back'
    $table->integer('level');
    $table->string('unit_type')->default('percentage'); // 'pixels', 'percentage', 'auto'
    $table->float('height_value')->default(100); // 100 si percentage, 30 si pixels
    $table->timestamps();
    $table->foreign('style_id')->references('id')->on('warehouse_location_styles');
});
```

**Uso en WarehouseLocationStyle:**

```php
public function sectionConfigs(): HasMany
{
    return $this->hasMany(WarehouseLocationSectionConfig::class, 'style_id');
}

public function getSectionHeights(string $face, int $level): array
{
    $configs = $this->sectionConfigs()
        ->where('face', $face)
        ->where('level', $level)
        ->get();

    return $configs->mapWithKeys(fn($c) => [
        $c->section_index => [
            'height' => $c->height_value,
            'unit' => $c->unit_type,
        ]
    ])->all();
}
```

### Opción B: Guardar Presets Completos en JSON

```php
// En WarehouseLocationStyle
protected $fillable = [
    'width', 'height',
    'layout_config', // ← JSON con presets
];

protected $casts = [
    'layout_config' => 'array', // Guarda MODAL_PRESETS completos
];

// Ejemplo de layout_config:
{
    "presets": [
        {
            "faces": 2,
            "vPaddingPct": { "top": 30, "bottom": 70 },
            "faceDefaults": { "hAlignPct": 50 }
        }
    ],
    "default_sections_by_face": {
        "left": ["30px", "40px", "30px"],  // 3 secciones explícitas
        "right": ["auto", "auto", "auto"]   // o modo automático
    }
}
```

---

## 6. Recomendación de Arquitectura Actual

### ✅ Lo que está BIEN

1. **Separación de responsabilidades:**
   - Backend gestiona metros (reales del warehouse)
   - Frontend convierte a píxeles (visualización)
   - Modal distribuye por porcentajes (UX flexible)

2. **Escalabilidad:**
   - Cambiar `SCALE = 30` a 40 o 50 reescala todo automáticamente
   - Soporta múltiples almacenes con diferentes dimensiones

3. **Flexibilidad de secciones:**
   - El conteo de secciones viene de la BD
   - La distribución visual es adaptativa

### ⚠️ Lo que NECESITA mejora

1. **Falta de documentación de cálculo de secciones:**
   - No está claro cómo `total_levels` se convierte a altura en píxeles

2. **No hay validación de proporciones:**
   - Un estante muy pequeño podría tener demasiados botones
   - Falta lógica para detectar conflictos

3. **El JSON retornado no incluye config de secciones:**
   - `WarehouseMapController::transformStandsToLayoutSpec()` solo retorna ubicaciones
   - No retorna la estructura de distribución esperada

---

## 7. Diagrama de Conversión Completa

```
WAREHOUSE (Metros)
├─ width: 42.23 m
├─ height: 30.26 m
└─ LOCATION (Metros)
   ├─ position_x: 5.5 m
   ├─ position_y: 2.3 m
   ├─ STYLE (Metros)
   │  ├─ width: 1.85 m
   │  ├─ height: 1.0 m
   │  ├─ default_levels: 3
   │  └─ default_sections: 5
   └─ SECTIONS (Conteo)
      ├─ Nivel 1, Cara RIGHT: 5 slots
      ├─ Nivel 1, Cara LEFT: 5 slots
      ├─ Nivel 2, Cara RIGHT: 5 slots
      ├─ Nivel 2, Cara LEFT: 5 slots
      └─ ...

                    ↓ [ESCALA: 30px/m]

SVG COORDINATE SYSTEM (Píxeles)
├─ viewBox="0 0 1300 950"
└─ Estante en posición SVG:
   ├─ x: 200 px
   ├─ y: 150 px
   ├─ width: 55.5 px (1.85m × 30)
   └─ height: 30 px (1.0m × 30)

                    ↓ [DISTRIBUCIÓN MODAL]

MODAL RENDERIZADO (Porcentajes internos)
├─ SVG 800×600 local
├─ Distribución vertical: 30% top, 70% bottom
├─ 5 botones con centros en: [9.3%, 26.7%, 50%, 73.3%, 90.7%]
└─ Conversión local: pctToY(9.3%) → 114 px en el SVG modal
```

---

## 8. Conclusión

### Respuesta a tu pregunta

**¿Se puede manejar el mapa por píxeles Y también por porcentajes?**

✅ **SÍ, el sistema actual ya lo hace implícitamente:**

1. **Backend → Píxeles (posicionamiento):** Metros en BD → Píxeles en SVG (SCALE × metro)
2. **Dentro del estante → Porcentajes (distribución):** Porcentajes de altura/ancho

### Mejoras Recomendadas

Para hacer esto **explícito y más flexible:**

1. **Ampliar WarehouseLocationStyle** con configuración granular de secciones
2. **Retornar estructura completa de distribución** desde el controlador
3. **Documentar claramente el flujo de conversión**
4. **Agregar validación** de proporciones realistas

---

## Archivos Relacionados

- `WarehouseLocationStyle.php`: Define estilos (tipos, caras, dimensiones)
- `WarehouseLocation.php`: Instancias de estantes con posiciones en metros
- `WarehouseMapController.php`: Convierte BD a JSON SVG-compatible
- Mapa SVG JavaScript: Convierte JSON a píxeles y renderiza modales con porcentajes

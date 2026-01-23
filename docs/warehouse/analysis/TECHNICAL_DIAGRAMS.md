# Diagramas Técnicos del Sistema de Mapa

## Diagrama 1: Flujo de Datos (Actual)

```
┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
┃         BASE DE DATOS (PostgreSQL)          ┃
┃                                             ┃
┃  warehouse_locations                        ┃
┃  ├── id: 1                                  ┃
┃  ├── position_x: 5.5 (metros)              ┃
┃  ├── position_y: 2.3 (metros)              ┃
┃  └── style_id: 10                          ┃
┃                                             ┃
┃  warehouse_location_styles                  ┃
┃  ├── id: 10                                 ┃
┃  ├── width: 1.85 (metros)                  ┃
┃  ├── height: 1.0 (metros)                  ┃
┃  ├── faces: ['left', 'right']              ┃
┃  └── default_sections: 5                   ┃
┗━━━━━━━━━━━━━━━━━━━━━━━━━━┬═══════════════━━┛
                           │
                 [WarehouseMapController]
                    transformStands()
                           │
        ┌──────────────────┴──────────────────┐
        │                                     │
        ↓                                     ↓
   ┌────────────────────┐    ┌──────────────────────┐
   │   JSON Response    │    │  Datos Hardcodeados  │
   ├────────────────────┤    ├──────────────────────┤
   │ {                  │    │ // En Blade:         │
   │  layout_spec: {    │    │ const LAYOUT_SPEC = [│
   │   start: {...},    │    │   { id: '...',       │
   │   shelf: {...}     │    │     ...              │
   │  }                 │    │   }                  │
   │ }                  │    │ ];                   │
   └─────────────┬──────┘    └────────────┬─────────┘
                 │                        │
                 │ [Actualmente IGNORADO] │
                 │                        │ [SE USA ESTO]
                 │                        │
                 └───────────┬────────────┘
                             │
                        [Blade View]
                             │
        ┌────────────────────┴────────────────────┐
        │                                         │
        │   const WAREHOUSE = {...}              │
        │   const LAYOUT_SPEC = [...]            │
        │   const MODAL_PRESETS = [...]          │
        │                                         │
        │   drawFloorGroup()  → SVG Estantes    │
        │   openShelfModal()  → Modal Ubicaciones│
        └────────────────┬───────────────────────┘
                         │
                    [Navegador]
                         │
                    SVG Renderizado
                    + Modales interactivos
```

## Diagrama 2: Conversión de Unidades

```
PASO 1: METROS → PÍXELES SVG (Posicionamiento)

   ENTRADA: position_x: 5.5 m, position_y: 2.3 m
            width: 1.85 m, height: 1.0 m

   FÓRMULA: px = metros × SCALE (30)

   SALIDA:  x: 165 px, y: 69 px
            width: 55.5 px, height: 30 px


PASO 2: CONTENIDO INTERNO (Porcentajes)

   ENTRADA: 5 secciones en altura de 30px

   DISTRIBUCIÓN: 100% / 5 = 20% cada una

   Sección 1: 0% → 20%    (altura: 6px)
   Sección 2: 20% → 40%   (altura: 6px)
   Sección 3: 40% → 60%   (altura: 6px)
   Sección 4: 60% → 80%   (altura: 6px)
   Sección 5: 80% → 100%  (altura: 6px)


PASO 3: OCUPANCIA (Datos Dinámicos)

   ENTRADA: 5 ubicaciones en cada sección
            occupancy_percentage: 45%

   COLOR: getOccupancyPercentage() → 'shelf--ambar'

   MODAL MUESTRA: 5 botones con ubicaciones reales
```

## Diagrama 3: Arquitectura Modal (Presets)

```
PASO 1: SELECCIONAR PRESET SEGÚN PARÁMETROS

   openShelfModal({ shelfId, floor })

   Detecta:
   ├─ rightCount = 5 ubicaciones
   ├─ leftCount = 5 ubicaciones
   └─ numFaces = 2

   Busca: pickPresetByShelfAndFaces(5, 2)
   → Retorna: '5-shelf-2faces' preset


PASO 2: APLICAR PRESET

   const preset = {
       faces: 2,
       vPaddingPct: { top: 6, bottom: 94 },
       faceDefaults: {
           hAlignPct: 50,
           button: { minWidth: 110, height: 26 }
       },
       facesConfig: {
           left: { hAlignPct: 30 },
           right: { hAlignPct: 70 },
           syncCenters: true ← ¡KEY!
       }
   }


PASO 3: CALCULAR CENTROS COMPARTIDOS

   Si syncCenters = true:

   getBarsAndCenters(5, 6, 94)

   Barras: [6%, 23.6%, 41.2%, 58.8%, 76.4%, 94%]
   Centros: [14.8%, 32.4%, 50%, 67.6%, 85.2%]

   Ambas caras usan los MISMOS centros
   → Botones ALINEADOS verticalmente
```

## Diagrama 4: Estado Actual vs Propuesto

```
ESTADO ACTUAL (700+ líneas en vista):
  ├── view/map/index.blade.php
  ├── const WAREHOUSE = {...}
  ├── const LAYOUT_SPEC = [
  │   { id: 'PASILLO13A', itemLocationsByIndex: {...} },
  │   { id: 'PASILLO13B', ... },
  │   ...
  │   { id: 'BUNKER', ... }
  │ ]  ← 8000+ líneas de datos estáticos
  ├── const MODAL_PRESETS = [...]
  └── renderWarehouse()

PROBLEMAS:
  ✗ Datos duplicados (JS + BD)
  ✗ 8000 líneas difíciles de mantener
  ✗ Cambios en BD requieren editar código
  ✗ No escalable a múltiples almacenes
  ✗ Rendimiento: carga 8KB cada vez


ESTADO PROPUESTO (150 líneas en vista):
  ├── view/map/index.blade.php (html + 50 líneas JS)
  │   └── const APP_CONFIG = { warehouseUid }
  │   └── async loadMapData()
  │       ├─ fetch('/api/warehouse/{uid}/config')
  │       ├─ fetch('/api/warehouse/{uid}/layout')
  │       └─ renderWarehouse()
  │
  ├── Controller/WarehouseMapController.php
  │   ├── getWarehouseConfig()
  │   └── getLayoutSpec()
  │
  └── Datos cargados dinámicamente desde BD

VENTAJAS:
  ✓ Vista limpia (150 vs 700 líneas)
  ✓ Datos dinámicos desde BD
  ✓ Múltiples almacenes soportados
  ✓ Cambios en BD = cambios automáticos
  ✓ Escalable y mantenible
```

## Diagrama 5: Nueva Tabla Propuesta

```
warehouse_location_section_layouts
├─ id: BIGINT PRIMARY
├─ style_id: BIGINT FOREIGN → warehouse_location_styles
├─ face: VARCHAR(20)        'left', 'right', 'front', 'back'
├─ level: INT               1, 2, 3...
├─ section_index: INT       1, 2, 3... dentro del nivel
├─ unit_type: ENUM          'pixels', 'percentage', 'auto'
├─ height_value: FLOAT      valor en px o %
├─ label: VARCHAR(100)      "Sección Premium"
├─ visible: BOOLEAN         true/false
├─ sort_order: INT
├─ created_at, updated_at: TIMESTAMP
└─ UNIQUE (style_id, face, level, section_index)


EJEMPLO: style_id 10 (5-shelf-2faces), face='right', level=1

section_index: 1  | unit_type: 'pixels'      | height_value: 100
section_index: 2  | unit_type: 'auto'        | height_value: null
section_index: 3  | unit_type: 'auto'        | height_value: null
section_index: 4  | unit_type: 'auto'        | height_value: null
section_index: 5  | unit_type: 'pixels'      | height_value: 50


CÁLCULO RESULTANTE:
├─ Fijos: 100 + 50 = 150px
├─ Flexible: 386 - 150 = 236px
├─ Distribuir: 236 / 3 = ~78.67px por auto
└─ Alturas finales: [100, 78.67, 78.67, 78.67, 50]
```

## Diagrama 6: Ciclo Completo de Solicitud

```
1. USUARIO CARGA PÁGINA
   └─ GET /managers/warehouse/ABC123/map

2. BLADE RENDERIZA HTML BASE + LOADER

3. JAVASCRIPT EJECUTA loadMapData()
   ├─ fetch('/api/warehouse/ABC123/config')
   │  ← {warehouse, scale, floors}
   └─ fetch('/api/warehouse/ABC123/layout')
      ← {layoutSpec[], floor_id}

4. GUARDA EN VARIABLES GLOBALES
   window.WAREHOUSE = ...
   window.LAYOUT_SPEC = ...
   window.FLOORS = ...

5. RENDERIZA MAPA
   renderWarehouse()
   └─ FLOORS.forEach(floor => drawFloorGroup(floor))

6. SVG VISIBLE CON ESTANTES

7. USUARIO CLICK EN ESTANTE
   openShelfModal({shelfId, floor})
   ├─ Selecciona preset
   ├─ Renderiza modal
   └─ showModal(true)

8. MODAL VISIBLE CON UBICACIONES
```

## Resumen: Tres Capas de Unidades

```
CAPA               SISTEMA         UNIDAD      USO
─────────────────────────────────────────────────────
Backend            Métrico         Metros (m)  Posición real
Frontend (SVG)     Escala          Píxeles     Renderizado
Modal              Proporcional    % (0-100)   Distribución
```

**Análisis completado**

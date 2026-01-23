# Análisis de Integración: Vista Blade + JavaScript del Mapa

## 📋 Resumen

La vista Blade `warehouse/map/index.blade.php` es una **aplicación SPA embebida** que combina:
- **Backend**: WarehouseMapController (JSON API)
- **Frontend**: JavaScript vanilla con SVG
- **Estilos**: CSS inline (oscuro con tema profesional)
- **Modales**: Sistema de presets para visualizar secciones

---

## 1. Estructura Actual de la Vista

### 1.1 Componentes Principales

```html
<!-- Container Principal -->
<div class="warehouse-container">
    <!-- Header con Controles -->
    <div class="warehouse-header">
        <div class="warehouse-header-title">📍 Mapa del Almacén</div>
        <div class="floor-selector">
            <label>Piso:</label>
            <button id="f1" class="floor-btn active">Piso 0</button>
            <button id="f2" class="floor-btn">Piso 1</button>
            <button id="f3" class="floor-btn">Piso 2</button>
        </div>
        <div class="warehouse-header-controls">
            <!-- Zoom controls ocultos -->
        </div>
    </div>

    <!-- Contenido Principal -->
    <div class="warehouse-content">
        <!-- Mapa SVG -->
        <svg id="svg">
            <g id="world"></g>
        </svg>

        <!-- Panel Lateral de Info -->
        <div class="info-panel">
            <div class="floor-selector-panel">
                <!-- Selector de pisos adicional -->
            </div>
            <div class="info-panel-section">
                <!-- Leyenda de colores -->
            </div>
        </div>
    </div>

    <!-- Modal para Detalles de Estante -->
    <div id="shelfModal" class="modal-shelf">
        <div class="modal-content">
            <div class="modal-header"><!-- ... --></div>
            <div class="modal-body">
                <div id="facesContainer" class="faces-container">
                    <!-- Se rellena dinámicamente con renderFaceBlockWithPreset -->
                </div>
            </div>
            <div class="modal-footer"><!-- ... --></div>
        </div>
    </div>
</div>
```

### 1.2 Flujo de Datos

```
┌─────────────────────────────────┐
│   Carga Inicial del DOM         │
│   - Crear estructura SVG base   │
│   - Inicializar listeners       │
│   - Preparar variables globales │
└──────────────┬──────────────────┘
               ↓
    ┌──────────────────────────┐
    │ Datos Hardcodeados en JS │ ← AQUÍ ESTÁ EL PROBLEMA
    │ - LAYOUT_SPEC            │
    │ - WAREHOUSE config       │
    │ - MODAL_PRESETS          │
    └──────────────┬───────────┘
                   ↓
        ┌──────────────────────┐
        │ Renderizar Pisos     │
        │ drawFloorGroup()     │
        │ buildFromSpec()      │
        └──────────────┬───────┘
                       ↓
            ┌──────────────────────┐
            │ Click en Estante     │
            │ openShelfModal()     │
            │ Renderizar Modal     │
            │ con MODAL_PRESETS    │
            └──────────────────────┘
```

---

## 2. Problema Crítico: Datos Hardcodeados

### ❌ Estado Actual

```javascript
// TODO: ESTO ESTÁ EN LA VISTA BLADE - 500+ líneas de LAYOUT_SPEC

const LAYOUT_SPEC = [
    {
        id: 'PASILLO13A',
        floors: [1],
        kind: 'row',
        // ... decenas de propiedades ...
        itemLocationsByIndex: {
            1: {
                right: [
                    { code: '0-13-1-1-3', color: 'shelf--azul' },
                    // ... 200+ líneas más ...
                ]
            }
        }
    },
    // ... más de 40 secciones ... 8000+ líneas
];

// Configuración de almacén también hardcodeada:
const WAREHOUSE = { width_m: 42.23, height_m: 30.26 };
const SCALE = 30;
```

### 🎯 Problemas

1. **No es escalable**: Cambios en BD requieren editar la vista
2. **Duplicación de datos**: Datos en JS vs BD están sincronizados manualmente
3. **Rendimiento**: Carga 8000+ líneas cada vez que accedes a la página
4. **Mantenimiento**: Si cambias ubicación de un estante, editas en 2 lugares
5. **No es RESTful**: El frontend no consulta datos reales

---

## 3. Solución: Integración con API Backend

### 3.1 Paso 1: Crear Endpoints API en WarehouseMapController

```php
<?php

// En WarehouseMapController, agregar:

/**
 * GET /warehouse/{warehouse_uid}/map/config
 * Retorna configuración base del almacén
 */
public function getWarehouseConfig($warehouse_uid): JsonResponse
{
    $warehouse = Warehouse::uid($warehouse_uid);

    return response()->json([
        'warehouse' => [
            'width_m' => $warehouse->width ?? 42.23,
            'height_m' => $warehouse->height ?? 30.26,
        ],
        'scale' => 30,
        'margin_m' => 0.5,
        'floors' => WarehouseFloor::where('warehouse_id', $warehouse->id)
            ->available()
            ->ordered()
            ->select('id', 'code', 'name')
            ->get()
            ->map(fn($f) => [
                'id' => $f->id,
                'code' => $f->code,
                'name' => $f->name,
                'number' => $f->id,
            ]),
    ]);
}

/**
 * GET /warehouse/{warehouse_uid}/map/layout?floor_id={id}
 * Retorna especificación de layout (estantes + secciones)
 */
public function getLayoutSpec($warehouse_uid, Request $request): JsonResponse
{
    $warehouse = Warehouse::uid($warehouse_uid);
    $floorId = $request->query('floor_id');

    $stands = WarehouseLocation::where('warehouse_id', $warehouse->id)
        ->with(['floor', 'style', 'sections', 'slots.product'])
        ->when($floorId, fn($q) => $q->where('floor_id', $floorId))
        ->ordered()
        ->get();

    $layoutSpec = $this->transformStandsToLayoutSpec($stands);

    return response()->json([
        'success' => true,
        'layoutSpec' => $layoutSpec,
        'floor_id' => $floorId,
        'metadata' => [
            'totalStands' => count($stands),
            'totalFloors' => WarehouseFloor::where('warehouse_id', $warehouse->id)->count(),
        ],
    ]);
}
```

### 3.2 Actualizar Rutas

```php
// routes/web.php (o routes/api.php)

Route::prefix('theme/warehouse/{warehouse_uid}/map')
    ->middleware(['auth', 'verified'])
    ->group(function () {
        Route::get('/', [WarehouseMapController::class, 'map'])->name('warehouse.map');
        Route::get('/config', [WarehouseMapController::class, 'getWarehouseConfig']);
        Route::get('/layout', [WarehouseMapController::class, 'getLayoutSpec']);
        Route::get('/slot/{uid}', [WarehouseMapController::class, 'getSlotDetails']);
    });
```

---

## 4. Actualizar la Vista Blade

### 4.1 Inyectar Variables Base

```blade
@section('content')
<div class="warehouse-container" data-warehouse-uid="{{ $warehouse_uid }}">
    <!-- ... HTML de layout ... -->

    <script>
        // Configuración BASE de la aplicación (NO datos específicos)
        const APP_CONFIG = {
            warehouseUid: @json($warehouse_uid),
            apiBaseUrl: '{{ route("warehouse.map") }}',
        };

        // Variables globales que se cargarán por AJAX
        let WAREHOUSE = {};
        let LAYOUT_SPEC = [];
        let MODAL_PRESETS = [];
        let FLOORS = [];

        // Cargar datos ANTES de renderizar
        async function initializeMap() {
            try {
                // 1. Cargar configuración base
                const configResponse = await fetch(
                    `/managers/warehouse/${APP_CONFIG.warehouseUid}/map/config`
                );
                const configData = await configResponse.json();

                WAREHOUSE = configData.warehouse;
                SCALE = configData.scale;
                FLOORS = configData.floors.map(f => f.number);

                // 2. Cargar layout para cada piso
                for (const floor of configData.floors) {
                    const layoutResponse = await fetch(
                        `/managers/warehouse/${APP_CONFIG.warehouseUid}/map/layout?floor_id=${floor.id}`
                    );
                    const layoutData = await layoutResponse.json();

                    if (layoutData.success) {
                        LAYOUT_SPEC.push(...layoutData.layoutSpec);
                    }
                }

                // 3. Inicializar presets (estos sí pueden estar en JS)
                initializeModalPresets();

                // 4. Renderizar mapa
                renderMap();

            } catch (error) {
                console.error('Error inicializando mapa:', error);
                document.querySelector('.warehouse-content').innerHTML =
                    '<div class="alert alert-danger">Error cargando datos del mapa</div>';
            }
        }

        // Cuando DOM está listo, cargar datos
        document.addEventListener('DOMContentLoaded', initializeMap);
    </script>
</div>
@endsection
```

### 4.2 Eliminar Datos Hardcodeados

```javascript
// ❌ ELIMINAR:
// const LAYOUT_SPEC = [ ... 8000 líneas ... ];
// const WAREHOUSE = { width_m: 42.23, height_m: 30.26 };

// ✅ MANTENER en JS (son presets, no datos específicos):
const MODAL_PRESETS = [
    { faces: 1, id: '1-shelf-1face', vPaddingPct: { top: 30, bottom: 70 }, ... },
    // ... estos son PATRONES reutilizables, no datos de DB
];
```

---

## 5. Manejo de Secciones Dinámicas

### 5.1 Si el Backend Retorna `sections_config`

```javascript
// En openShelfModal, verificar si hay secciones explícitas:

function openShelfModal({ shelfId, floor }) {
    const meta = SHELF_META[shelfId] || { facesConfig: { right: { locations: [] } } };

    // ← NUEVO: Detectar si hay configuración de secciones
    const sectionsConfig = meta.sectionsConfig;

    if (sectionsConfig && sectionsConfig.hasExplicitLayout) {
        // Renderizar con secciones explícitas (píxeles/porcentajes)
        openShelfModalWithExplicitSections({ shelfId, floor, sectionsConfig });
    } else {
        // Fallback: renderizar con presets
        openShelfModalWithPresets({ shelfId, floor });
    }
}
```

---

## 6. Ejemplo de Integración Completa

### 6.1 Vista Blade Actualizada (Mínimo)

```blade
@extends('layouts.map')

@section('content')
<div class="warehouse-container" data-warehouse-uid="{{ $warehouse_uid }}">
    <!-- Header, SVG, Modal como estaban -->

    <script>
        // Config mínima
        const APP_CONFIG = {
            warehouseUid: @json($warehouse_uid),
        };

        // Cargar dinámicamente
        async function loadMapData() {
            const config = await fetch(
                `/managers/warehouse/${APP_CONFIG.warehouseUid}/map/config`
            ).then(r => r.json());

            const layout = await fetch(
                `/managers/warehouse/${APP_CONFIG.warehouseUid}/map/layout`
            ).then(r => r.json());

            window.WAREHOUSE = config.warehouse;
            window.LAYOUT_SPEC = layout.layoutSpec;
            window.FLOORS = config.floors.map(f => f.number);
            window.SCALE = config.scale;

            renderWarehouse();
        }

        document.addEventListener('DOMContentLoaded', loadMapData);
    </script>
</div>
@endsection
```

### 6.2 Controller Mínimo

```php
<?php

public function getWarehouseConfig($warehouse_uid): JsonResponse
{
    $wh = Warehouse::uid($warehouse_uid);
    return response()->json([
        'warehouse' => [
            'width_m' => $wh->width ?? 42.23,
            'height_m' => $wh->height ?? 30.26,
        ],
        'scale' => 30,
        'floors' => WarehouseFloor::where('warehouse_id', $wh->id)
            ->select('id', 'code', 'name')
            ->orderBy('id')
            ->get(),
    ]);
}

public function getLayoutSpec($warehouse_uid, Request $request): JsonResponse
{
    $wh = Warehouse::uid($warehouse_uid);
    $stands = WarehouseLocation::where('warehouse_id', $wh->id)
        ->with('floor', 'style', 'sections.slots')
        ->ordered()
        ->get();

    return response()->json([
        'success' => true,
        'layoutSpec' => $this->transformStandsToLayoutSpec($stands),
    ]);
}
```

---

## 7. Ventajas de esta Refactorización

| Aspecto | Antes | Después |
|---------|-------|---------|
| **Datos dinámicos** | Hardcodeados en JS | Desde API/BD |
| **Tamaño vista** | 8000+ líneas | 500 líneas |
| **Sincronización** | Manual | Automática |
| **Escalabilidad** | Limitada | Múltiples almacenes |
| **Rendimiento** | Lento (carga 8KB) | Rápido (carga JSON) |
| **Reusabilidad** | API | REST completa |

---

## 8. Plan de Implementación

### Fase 1: Endpoints Base (1h)
- [ ] Crear `getWarehouseConfig()`
- [ ] Crear `getLayoutSpec()`
- [ ] Crear rutas

### Fase 2: Integración en Vista (2h)
- [ ] Actualizar vista Blade
- [ ] Agregar loader de datos
- [ ] Probar renderizado

### Fase 3: Secciones Dinámicas (2h)
- [ ] Retornar `sections_config` desde controller
- [ ] Actualizar `transformStandsToLayoutSpec()`
- [ ] Renderizar secciones explícitas en modal

### Fase 4: Optimización (1h)
- [ ] Caché con ETag
- [ ] Lazy loading por piso
- [ ] Minificación JS

---

## 9. Checklist de Seguridad

- [ ] Los endpoints requieren `auth` y verificación de permiso
- [ ] Se valida `warehouse_uid` antes de retornar datos
- [ ] Se escapan códigos/etiquetas en JSON
- [ ] Se limita cantidad de registros en respuesta
- [ ] Se agrega rate limiting en API endpoints

---

## 10. Referencias de Código

- **Controlador**: `app/Http/Controllers/Managers/Warehouse/WarehouseMapController.php`
- **Modelo**: `app/Models/Warehouse/WarehouseLocation.php`
- **Vista**: `resources/views/managers/views/warehouse/map/index.blade.php`
- **Rutas**: `routes/web.php` (buscar `warehouse.map`)

# Sistema Dinámico de Layout Visual - Repositorio y Redimensionamiento

## Visión General

Permitir que los **estantes sean redimensionados y reposicionados visualmente** en la interfaz sin afectar las medidas base, usando `WarehouseLocation` como **sobreescrituras de estilo**.

---

## 1. El Problema Actual

### Limitación:
- `WarehouseLocationStyle` define dimensiones BASE (1.85m × 1.0m)
- Cada estante hereda estas dimensiones
- No hay forma de hacer un estante más grande/pequeño visualmente sin código

### Solución:
Agregar campos en `WarehouseLocation` para **dimensiones visuales finales** que sobrescriben el estilo

---

## 2. Estructura Propuesta: Extender WarehouseLocation

### Nueva Migración:

```php
// database/migrations/xxxx_add_visual_dimensions_to_warehouse_locations.php

Schema::table('warehouse_locations', function (Blueprint $table) {
    // Dimensiones visuales finales (pueden diferir del style)
    $table->float('visual_width_m')->nullable()->comment('Ancho visual final (metros)');
    $table->float('visual_height_m')->nullable()->comment('Alto visual final (metros)');

    // Posición visual (puede diferir de la posición base)
    $table->float('visual_position_x')->nullable()->comment('Posición X visual (metros)');
    $table->float('visual_position_y')->nullable()->comment('Posición Y visual (metros)');

    // Flag para usar valores visuales
    $table->boolean('use_custom_visual')->default(false)->comment('Usar dimensiones custom');

    // Rotación visual (futuro)
    $table->float('visual_rotation')->default(0)->comment('Rotación en grados');

    // Metadata
    $table->timestamps();
});
```

### Modelo Actualizado:

```php
<?php

namespace App\Models\Warehouse;

class WarehouseLocation extends Model
{
    protected $fillable = [
        'uid',
        'warehouse_id',
        'floor_id',
        'code',
        'style_id',
        'position_x',
        'position_y',
        'total_levels',
        'available',
        'notes',
        // NUEVOS:
        'visual_width_m',
        'visual_height_m',
        'visual_position_x',
        'visual_position_y',
        'use_custom_visual',
        'visual_rotation',
    ];

    protected $casts = [
        'available' => 'boolean',
        'use_custom_visual' => 'boolean',
        'visual_width_m' => 'float',
        'visual_height_m' => 'float',
        'visual_position_x' => 'float',
        'visual_position_y' => 'float',
        'visual_rotation' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Obtener el ancho FINAL (visual o del estilo)
     */
    public function getVisualWidth(): float
    {
        if ($this->use_custom_visual && $this->visual_width_m) {
            return $this->visual_width_m;
        }
        return $this->style?->width ?? 1.0;
    }

    /**
     * Obtener el alto FINAL (visual o del estilo)
     */
    public function getVisualHeight(): float
    {
        if ($this->use_custom_visual && $this->visual_height_m) {
            return $this->visual_height_m;
        }
        return $this->style?->height ?? 1.0;
    }

    /**
     * Obtener la posición X FINAL (visual o base)
     */
    public function getVisualPositionX(): float
    {
        if ($this->use_custom_visual && $this->visual_position_x !== null) {
            return $this->visual_position_x;
        }
        return $this->position_x;
    }

    /**
     * Obtener la posición Y FINAL (visual o base)
     */
    public function getVisualPositionY(): float
    {
        if ($this->use_custom_visual && $this->visual_position_y !== null) {
            return $this->visual_position_y;
        }
        return $this->position_y;
    }

    /**
     * Resumen con dimensiones visuales
     */
    public function getSummaryWithVisuals(): array
    {
        return array_merge($this->getSummary(), [
            'visual_dimensions' => [
                'width_m' => $this->getVisualWidth(),
                'height_m' => $this->getVisualHeight(),
                'position_x' => $this->getVisualPositionX(),
                'position_y' => $this->getVisualPositionY(),
                'rotation' => $this->visual_rotation ?? 0,
                'use_custom' => $this->use_custom_visual,
            ],
            'base_dimensions' => [
                'width_m' => $this->style?->width ?? 1.0,
                'height_m' => $this->style?->height ?? 1.0,
                'position_x' => $this->position_x,
                'position_y' => $this->position_y,
            ],
        ]);
    }
}
```

---

## 3. Actualizar WarehouseMapController

```php
<?php

namespace App\Http\Controllers\Managers\Warehouse;

class WarehouseMapController extends Controller
{
    /**
     * Retornar layout con dimensiones visuales
     */
    public function getLayoutSpec($warehouse_uid, Request $request): JsonResponse
    {
        $warehouse = Warehouse::uid($warehouse_uid);
        $floorId = $request->query('floor_id');

        $stands = WarehouseLocation::where('warehouse_id', $warehouse->id)
            ->with(['floor', 'style', 'sections.slots'])
            ->when($floorId, fn($q) => $q->where('floor_id', $floorId))
            ->ordered()
            ->get();

        $layoutSpec = $this->transformStandsToLayoutSpec($stands);

        return response()->json([
            'success' => true,
            'layoutSpec' => $layoutSpec,
            'editMode' => (bool)$request->query('edit_mode', false),
        ]);
    }

    /**
     * Transformar estantes a spec, usando dimensiones visuales
     */
    private function transformStandsToLayoutSpec($stands): array
    {
        $layoutSpec = [];

        foreach ($stands as $stand) {
            // USAR getVisual*() para obtener dimensiones finales
            $width = $stand->getVisualWidth();
            $height = $stand->getVisualHeight();
            $posX = $stand->getVisualPositionX();
            $posY = $stand->getVisualPositionY();

            $itemLocations = $this->buildItemLocations($stand);

            $layoutItem = [
                'id' => $stand->code,
                'uid' => $stand->uid,
                'floors' => [$stand->floor_id],
                'kind' => 'row',
                'anchor' => 'top-right',
                'start' => [
                    'offsetRight_m' => (float)$posX,
                    'offsetTop_m' => (float)$posY,
                ],
                'shelf' => [
                    'w_m' => (float)$width,      // ← VISUAL
                    'h_m' => (float)$height,     // ← VISUAL
                ],
                'count' => 1,
                'direction' => 'left',
                'gaps' => ['between_m' => 0],
                'label' => ['pattern' => 'P{floor}-' . $stand->code],
                'nameTemplate' => $stand->code,
                'color' => $this->getStandColorClass($stand),
                'style_type' => $stand->style?->type ?? 'row',
                'style_faces' => $stand->style?->faces ?? ['front'],

                // ← NUEVO: Metadatos visuales
                'visual_config' => [
                    'use_custom' => $stand->use_custom_visual,
                    'base_width' => $stand->style?->width ?? 1.0,
                    'base_height' => $stand->style?->height ?? 1.0,
                    'rotation' => $stand->visual_rotation ?? 0,
                    'base_position' => [
                        'x' => $stand->position_x,
                        'y' => $stand->position_y,
                    ],
                ],

                'available' => $stand->available,
                'occupancy_percentage' => round($stand->getOccupancyPercentage(), 2),
                'exportEdges' => false,
                'itemLocationsByIndex' => [1 => $itemLocations],
            ];

            $layoutSpec[] = $layoutItem;
        }

        return $layoutSpec;
    }

    /**
     * API: Actualizar dimensiones visuales de un estante
     * PUT /warehouse/{uid}/location/{location_uid}/visual-config
     */
    public function updateVisualConfig(
        $warehouse_uid,
        $location_uid,
        Request $request
    ): JsonResponse {
        $warehouse = Warehouse::uid($warehouse_uid);
        $location = WarehouseLocation::where('warehouse_id', $warehouse->id)
            ->where('uid', $location_uid)
            ->firstOrFail();

        $validated = $request->validate([
            'visual_width_m' => 'nullable|numeric|min:0.1|max:20',
            'visual_height_m' => 'nullable|numeric|min:0.1|max:20',
            'visual_position_x' => 'nullable|numeric',
            'visual_position_y' => 'nullable|numeric',
            'visual_rotation' => 'nullable|numeric|between:0,360',
            'use_custom_visual' => 'boolean',
        ]);

        $location->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Configuración visual actualizada',
            'location' => $location->getSummaryWithVisuals(),
        ]);
    }

    /**
     * API: Resetear a valores base
     * POST /warehouse/{uid}/location/{location_uid}/reset-visual
     */
    public function resetVisualConfig($warehouse_uid, $location_uid): JsonResponse
    {
        $warehouse = Warehouse::uid($warehouse_uid);
        $location = WarehouseLocation::where('warehouse_id', $warehouse->id)
            ->where('uid', $location_uid)
            ->firstOrFail();

        $location->update([
            'visual_width_m' => null,
            'visual_height_m' => null,
            'visual_position_x' => null,
            'visual_position_y' => null,
            'visual_rotation' => 0,
            'use_custom_visual' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Valores visuales reseteados a estilo base',
            'location' => $location->getSummaryWithVisuals(),
        ]);
    }
}
```

---

## 4. Actualizar Vista Blade (Modo Edición)

```blade
@extends('layouts.map')

@section('content')
<div class="warehouse-container" data-warehouse-uid="{{ $warehouse_uid }}" data-edit-mode="{{ request('edit') === '1' }}">

    <!-- Header con Toggle Edit Mode -->
    <div class="warehouse-header">
        <div class="warehouse-header-title">📍 Mapa del Almacén</div>

        @if(auth()->user()->can('edit warehouse maps'))
        <button id="toggleEditMode" class="btn-edit-mode">
            <i class="fas fa-edit"></i> Editar Layout
        </button>
        @endif

        <div class="floor-selector">
            <label>Piso:</label>
            <button id="f1" class="floor-btn active">Piso 0</button>
            <button id="f2" class="floor-btn">Piso 1</button>
            <button id="f3" class="floor-btn">Piso 2</button>
        </div>
    </div>

    <!-- SVG Principal -->
    <div class="warehouse-content">
        <svg id="svg">
            <g id="world"></g>
        </svg>

        <!-- Panel Lateral -->
        <div class="info-panel">
            <div class="floor-selector-panel"><!-- ... --></div>
            <div id="editPanel" class="edit-panel" style="display: none;">
                <h3>Editar Estante</h3>
                <div class="edit-form">
                    <div class="form-group">
                        <label>Ancho (m):</label>
                        <input type="number" id="visualWidth" step="0.1" min="0.1">
                    </div>
                    <div class="form-group">
                        <label>Alto (m):</label>
                        <input type="number" id="visualHeight" step="0.1" min="0.1">
                    </div>
                    <div class="form-group">
                        <label>Posición X (m):</label>
                        <input type="number" id="visualPosX" step="0.1">
                    </div>
                    <div class="form-group">
                        <label>Posición Y (m):</label>
                        <input type="number" id="visualPosY" step="0.1">
                    </div>
                    <div class="form-group">
                        <label>Rotación (°):</label>
                        <input type="number" id="visualRotation" step="1" min="0" max="360">
                    </div>
                    <button id="saveVisualConfig" class="btn-primary">Guardar</button>
                    <button id="resetVisualConfig" class="btn-secondary">Resetear a Base</button>
                </div>
            </div>
            <div class="legend"><!-- ... --></div>
        </div>
    </div>

    <!-- Modal -->
    <div id="shelfModal" class="modal-shelf">
        <!-- ... como antes ... -->
    </div>

    <script>
        // ============================================
        // CONFIG BASE
        // ============================================
        const APP_CONFIG = {
            warehouseUid: @json($warehouse_uid),
            editMode: document.querySelector('.warehouse-container').dataset.editMode === 'true',
        };

        let WAREHOUSE = {};
        let LAYOUT_SPEC = [];
        let SCALE = 30;
        let FLOORS = [];
        let editingShelfId = null;


        // ============================================
        // CARGAR DATOS DINÁMICOS
        // ============================================
        async function initializeMap() {
            try {
                const editParam = APP_CONFIG.editMode ? '?edit_mode=1' : '';

                // Cargar config
                const configResp = await fetch(
                    `/managers/warehouse/${APP_CONFIG.warehouseUid}/map/config`
                );
                const configData = await configResp.json();
                WAREHOUSE = configData.warehouse;
                SCALE = configData.scale;
                FLOORS = configData.floors.map(f => f.number);

                // Cargar layout con dimensiones visuales
                const layoutResp = await fetch(
                    `/managers/warehouse/${APP_CONFIG.warehouseUid}/map/layout${editParam}`
                );
                const layoutData = await layoutResp.json();
                LAYOUT_SPEC = layoutData.layoutSpec;

                // Renderizar
                renderWarehouse();

            } catch (error) {
                console.error('Error:', error);
            }
        }

        document.addEventListener('DOMContentLoaded', initializeMap);


        // ============================================
        // MODO EDICIÓN
        // ============================================
        function enableEditMode() {
            APP_CONFIG.editMode = true;
            document.querySelector('.warehouse-container').dataset.editMode = 'true';

            // Agregar estilos de edición
            document.querySelectorAll('[data-id]').forEach(el => {
                el.classList.add('editable');
                el.addEventListener('click', onShelfClickEdit);
            });
        }

        function disableEditMode() {
            APP_CONFIG.editMode = false;
            document.querySelector('.warehouse-container').dataset.editMode = 'false';
            document.querySelectorAll('[data-id]').forEach(el => {
                el.classList.remove('editable');
            });
            editingShelfId = null;
            document.getElementById('editPanel').style.display = 'none';
        }

        document.getElementById('toggleEditMode')?.addEventListener('click', () => {
            if (APP_CONFIG.editMode) {
                disableEditMode();
                location.reload(); // Recargar sin edit param
            } else {
                enableEditMode();
                location.href = `?edit=1`;
            }
        });


        // ============================================
        // EDICIÓN DE ESTANTE
        // ============================================
        function onShelfClickEdit(e) {
            if (!APP_CONFIG.editMode) return;

            e.stopPropagation();
            const shelfId = e.currentTarget.dataset.id;
            const shelf = LAYOUT_SPEC.find(s => `${s.id}__${s.label.pattern.replace('{section}', s.id)}` === shelfId);

            if (!shelf) return;

            editingShelfId = shelfId;
            const visualConfig = shelf.visual_config;

            // Rellenar form
            document.getElementById('visualWidth').value = visualConfig.base_width;
            document.getElementById('visualHeight').value = visualConfig.base_height;
            document.getElementById('visualPosX').value = visualConfig.base_position.x;
            document.getElementById('visualPosY').value = visualConfig.base_position.y;
            document.getElementById('visualRotation').value = visualConfig.rotation;

            document.getElementById('editPanel').style.display = 'block';
        }

        document.getElementById('saveVisualConfig')?.addEventListener('click', async () => {
            if (!editingShelfId) return;

            const width = parseFloat(document.getElementById('visualWidth').value);
            const height = parseFloat(document.getElementById('visualHeight').value);
            const posX = parseFloat(document.getElementById('visualPosX').value);
            const posY = parseFloat(document.getElementById('visualPosY').value);
            const rotation = parseFloat(document.getElementById('visualRotation').value);

            // Buscar UID de la ubicación
            const shelf = LAYOUT_SPEC.find(s => s.id === editingShelfId);
            const locationUid = shelf?.uid;

            if (!locationUid) return;

            try {
                const response = await fetch(
                    `/managers/warehouse/${APP_CONFIG.warehouseUid}/location/${locationUid}/visual-config`,
                    {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({
                            visual_width_m: width,
                            visual_height_m: height,
                            visual_position_x: posX,
                            visual_position_y: posY,
                            visual_rotation: rotation,
                            use_custom_visual: true,
                        }),
                    }
                );

                if (response.ok) {
                    // Actualizar layout localmente
                    const idx = LAYOUT_SPEC.findIndex(s => s.uid === locationUid);
                    if (idx >= 0) {
                        LAYOUT_SPEC[idx].shelf.w_m = width;
                        LAYOUT_SPEC[idx].shelf.h_m = height;
                        LAYOUT_SPEC[idx].start.offsetRight_m = posX;
                        LAYOUT_SPEC[idx].start.offsetTop_m = posY;
                        LAYOUT_SPEC[idx].visual_config.rotation = rotation;
                        LAYOUT_SPEC[idx].visual_config.use_custom = true;
                    }

                    // Redibujar
                    document.getElementById('world').innerHTML = '';
                    renderWarehouse();

                    alert('Configuración visual guardada');
                } else {
                    alert('Error al guardar');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al guardar');
            }
        });

        document.getElementById('resetVisualConfig')?.addEventListener('click', async () => {
            if (!editingShelfId) return;

            const shelf = LAYOUT_SPEC.find(s => s.id === editingShelfId);
            const locationUid = shelf?.uid;

            if (!locationUid) return;

            try {
                const response = await fetch(
                    `/managers/warehouse/${APP_CONFIG.warehouseUid}/location/${locationUid}/reset-visual`,
                    {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                    }
                );

                if (response.ok) {
                    location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });


        // ============================================
        // RENDERIZADO (sin cambios, usa getVisual*)
        // ============================================
        function renderWarehouse() {
            FLOORS.forEach(floor => {
                const floorGroup = drawFloorGroup(floor);
                document.getElementById('world').appendChild(floorGroup);
            });

            if (APP_CONFIG.editMode) {
                enableEditMode();
            }
        }

        // Resto del código igual...
    </script>

    <style>
        .warehouse-container[data-edit-mode="true"] {
            --edit-mode: 1;
        }

        .shelf.editable {
            cursor: move;
            stroke: #3b82f6 !important;
            stroke-width: 2 !important;
        }

        .shelf.editable:hover {
            opacity: 0.8;
            filter: drop-shadow(0 0 5px #3b82f6);
        }

        .btn-edit-mode {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            cursor: pointer;
            font-size: 0.875rem;
        }

        .btn-edit-mode:hover {
            background: #1e40af;
        }

        .edit-panel {
            background: #f3f4f6;
            border-top: 2px solid #3b82f6;
            padding: 1rem;
            margin-top: 1rem;
        }

        .form-group {
            margin-bottom: 0.75rem;
        }

        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.25rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
        }

        .btn-primary, .btn-secondary {
            width: 100%;
            padding: 0.5rem;
            border-radius: 0.375rem;
            border: none;
            cursor: pointer;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
        }

        .btn-secondary {
            background: #e5e7eb;
            color: #374151;
        }
    </style>
</div>
@endsection
```

---

## 5. Ventajas de este Enfoque

✅ **Base + Visual separados**: El estilo es la base, las ubicaciones pueden variar
✅ **Sin código requerido**: Todo dinámico desde BD
✅ **Fácil de restablecer**: Un botón para volver a valores base
✅ **Historial posible**: Auditar cambios visuales
✅ **Escalable**: Soporta múltiples layouts

---

## 6. Rutas Requeridas

```php
// routes/web.php

Route::prefix('theme/warehouse/{warehouse_uid}')
    ->middleware(['auth', 'verified'])
    ->group(function () {
        Route::get('/map', [WarehouseMapController::class, 'map']);
        Route::get('/map/config', [WarehouseMapController::class, 'getWarehouseConfig']);
        Route::get('/map/layout', [WarehouseMapController::class, 'getLayoutSpec']);

        // Edición visual
        Route::put('/location/{location_uid}/visual-config', [WarehouseMapController::class, 'updateVisualConfig']);
        Route::post('/location/{location_uid}/reset-visual', [WarehouseMapController::class, 'resetVisualConfig']);
    });
```

---

## 7. Flujo Visual para Usuario

```
USUARIO ENTRA AL MAPA
    ↓
[Ver Botón "Editar Layout"]
    ↓
HACE CLICK EN "Editar Layout"
    ↓
VISTA CAMBIA A MODO EDICIÓN
├─ Estantes ahora tienen borde azul
├─ Cursor cambia a "move"
└─ Panel "Editar Estante" visible
    ↓
USUARIO HACE CLICK EN UN ESTANTE
    ↓
[Panel se rellena con dimensiones actuales]
    ↓
USUARIO MODIFICA ANCHO/ALTO/POSICIÓN
    ↓
HACE CLICK "GUARDAR"
    ↓
[API PUT actualiza visual_config en BD]
    ↓
[SVG se redibu automáticamente]
    ↓
USUARIO VE CAMBIOS INMEDIATAMENTE
    ↓
SI NO LE GUSTA:
└─ CLICK "RESETEAR A BASE" → Vuelve a estilo original
```

---

## 8. Base de Datos: Ejemplo Final

```sql
warehouse_locations (después de migración):

id  | code      | style_id | position_x | position_y | visual_width_m | visual_height_m | visual_position_x | visual_position_y | use_custom_visual | visual_rotation
────┼───────────┼──────────┼────────────┼────────────┼────────────────┼─────────────────┼───────────────────┼───────────────────┼───────────────────┼─────────────────
1   | PASILLO13A| 10       | 0.5        | 0.5        | 2.0            | 1.5             | 0.5               | 0.5               | 1                 | 0
2   | PASILLO13B| 11       | 3.75       | 0.5        | NULL           | NULL            | NULL              | NULL              | 0                 | 0
3   | BUNKER    | 12       | 15.0       | 15.0       | 18.0           | 18.0            | 15.0              | 15.0              | 1                 | 0

Explicación:
- PASILLO13A: Sobreescrito (visual_width_m=2.0 vs style.width=1.85)
- PASILLO13B: Usa valores del estilo (NULL = usar style)
- BUNKER: Sobreescrito también (agregado como almacenamiento especial)
```

---

## 9. Checklist de Implementación

- [ ] Crear migración para agregar columnas visuales
- [ ] Actualizar modelo WarehouseLocation con getVisual*()
- [ ] Actualizar WarehouseMapController con métodos PUT/POST
- [ ] Agregar rutas para edición
- [ ] Actualizar vista Blade con modo edición
- [ ] Probar en navegador (crear, editar, resetear)
- [ ] Agregar permisos (solo ciertos usuarios editan)
- [ ] Agregar validación (no menores a 0.1m, no mayores a 20m)
- [ ] Documentar en admin manual

---

**Sistema dinámico listo para escalar**

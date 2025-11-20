# Ejemplos de Uso de los Nuevos Controladores Warehouse

## 📍 Rutas Disponibles

### Dashboard Principal
```
GET /manager/warehouse/
GET /manager/warehouse/dashboard
```

### Gestión de Almacenes
```
GET    /manager/warehouse/warehouses/                    # Listar
GET    /manager/warehouse/warehouses/create              # Formulario crear
POST   /manager/warehouse/warehouses/store               # Guardar
GET    /manager/warehouse/warehouses/edit/{uid}          # Formulario editar
POST   /manager/warehouse/warehouses/update              # Actualizar
GET    /manager/warehouse/warehouses/view/{uid}          # Ver detalles
GET    /manager/warehouse/warehouses/destroy/{uid}       # Eliminar
```

### Gestión de Ubicaciones/Stands
```
GET    /manager/warehouse/locations/{warehouse_uid}               # Listar
GET    /manager/warehouse/locations/create/{warehouse_uid}        # Formulario crear
POST   /manager/warehouse/locations/store                         # Guardar
GET    /manager/warehouse/locations/view/{uid}                    # Ver detalles
GET    /manager/warehouse/locations/edit/{uid}                    # Formulario editar
POST   /manager/warehouse/locations/update                        # Actualizar
GET    /manager/warehouse/locations/destroy/{uid}                 # Eliminar
DELETE /manager/warehouse/locations/slot/{uid}                    # Eliminar slot
```

### Histórico de Movimientos
```
GET    /manager/warehouse/history/                       # Listar movimientos
GET    /manager/warehouse/history/view/{uid}             # Ver movimiento
GET    /manager/warehouse/history/edit/{uid}             # Formulario editar
POST   /manager/warehouse/history/update                 # Actualizar
```

### Reportes
```
GET    /manager/warehouse/reports/                       # Formulario reportes
POST   /manager/warehouse/reports/inventory              # Generar reporte inventario
POST   /manager/warehouse/reports/movements              # Generar reporte movimientos
POST   /manager/warehouse/reports/occupancy              # Generar reporte ocupancia
POST   /manager/warehouse/reports/capacity               # Generar reporte capacidad
```

### APIs REST
```
GET    /manager/warehouse/api/statistics                 # Estadísticas generales
GET    /manager/warehouse/api/warehouses                 # Listar almacenes (JSON)
GET    /manager/warehouse/api/floors/{warehouse_id}      # Pisos de almacén (JSON)
GET    /manager/warehouse/locations/api/warehouse/{warehouse_id}      # Ubicaciones (JSON)
GET    /manager/warehouse/locations/api/barcode/{barcode}             # Por código barras
GET    /manager/warehouse/history/api/slot/{slot_uid}                 # Histórico slot
GET    /manager/warehouse/history/api/warehouse/{warehouse_uid}       # Histórico almacén
POST   /manager/warehouse/history/api/filter                          # Filtrar movimientos
GET    /manager/warehouse/history/api/statistics                      # Estadísticas movimientos
```

---

## 💡 Ejemplos de Uso en Blade Templates

### 1. Listar Almacenes
```blade
<!-- resources/views/managers/views/warehouse/list.blade.php -->

@forelse($warehouses as $warehouse)
    <div class="card">
        <h5>{{ $warehouse->title }}</h5>
        <p>{{ $warehouse->description }}</p>

        <a href="{{ route('manager.warehouse.view', $warehouse->uid) }}" class="btn btn-primary">
            Ver Detalles
        </a>

        <a href="{{ route('manager.warehouse.locations', ['warehouse_uid' => $warehouse->uid]) }}" class="btn btn-info">
            Ubicaciones
        </a>

        <a href="{{ route('manager.warehouse.edit', $warehouse->uid) }}" class="btn btn-warning">
            Editar
        </a>

        <a href="{{ route('manager.warehouse.destroy', $warehouse->uid) }}" class="btn btn-danger" onclick="return confirm('¿Eliminar?')">
            Eliminar
        </a>
    </div>
@empty
    <p>No hay almacenes disponibles</p>
@endforelse

{{ $warehouses->links() }}
```

### 2. Formulario Crear Almacén
```blade
<!-- resources/views/managers/views/warehouse/create.blade.php -->

<form id="warehouseForm">
    <div class="form-group">
        <label>Nombre del Almacén</label>
        <input type="text" name="title" class="form-control" required>
    </div>

    <div class="form-group">
        <label>Descripción</label>
        <textarea name="description" class="form-control"></textarea>
    </div>

    <div class="form-group">
        <label>Estado</label>
        <select name="available" class="form-control" required>
            <option value="1">Público</option>
            <option value="0">Oculto</option>
        </select>
    </div>

    <button type="submit" class="btn btn-success">Crear Almacén</button>
</form>

<script>
document.getElementById('warehouseForm').addEventListener('submit', function(e) {
    e.preventDefault();

    fetch("{{ route('manager.warehouse.store') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            title: this.title.value,
            description: this.description.value,
            available: this.available.value
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status) {
            alert(data.message);
            window.location.href = "{{ route('manager.warehouse.list') }}";
        }
    });
});
</script>
```

### 3. Ver Ubicaciones de un Almacén
```blade
<!-- resources/views/managers/views/warehouse/locations/index.blade.php -->

<div class="container">
    <h2>Ubicaciones - {{ $warehouse->title }}</h2>

    <a href="{{ route('manager.warehouse.locations.create', ['warehouse_uid' => $warehouse->uid]) }}" class="btn btn-primary">
        + Nueva Ubicación
    </a>

    <table class="table">
        <thead>
            <tr>
                <th>Código</th>
                <th>Código de Barras</th>
                <th>Ubicación</th>
                <th>Piso</th>
                <th>Estilo</th>
                <th>Slots Ocupados</th>
                <th>Ocupancia</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($locations as $location)
                <tr>
                    <td>{{ $location->code }}</td>
                    <td>{{ $location->barcode }}</td>
                    <td>{{ $location->getFullName() }}</td>
                    <td>{{ $location->floor->name ?? 'N/A' }}</td>
                    <td>{{ $location->style->name ?? 'N/A' }}</td>
                    <td>{{ $location->getOccupiedSlots() }}/{{ $location->getTotalSlots() }}</td>
                    <td>
                        <div class="progress">
                            <div class="progress-bar" style="width: {{ $location->getOccupancyPercentage() }}%">
                                {{ round($location->getOccupancyPercentage(), 1) }}%
                            </div>
                        </div>
                    </td>
                    <td>
                        <a href="{{ route('manager.warehouse.locations.view', $location->uid) }}">Ver</a>
                        <a href="{{ route('manager.warehouse.locations.edit', $location->uid) }}">Editar</a>
                        <a href="{{ route('manager.warehouse.locations.destroy', $location->uid) }}">Eliminar</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8">No hay ubicaciones</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $locations->links() }}
</div>
```

---

## 🔌 Ejemplos de Integración via API REST

### 1. Obtener Estadísticas de un Almacén
```javascript
fetch('/manager/warehouse/api/statistics?warehouse_id=1')
    .then(response => response.json())
    .then(data => {
        console.log('Total de pisos:', data.total_floors);
        console.log('Total de ubicaciones:', data.total_locations);
        console.log('Total de slots:', data.total_slots);
        console.log('Ocupancia:', data.occupancy_percentage + '%');
        console.log('Peso total:', data.total_weight + 'kg');
    });
```

**Respuesta:**
```json
{
    "warehouse_id": 1,
    "warehouse_uid": "warehouses-abc123",
    "title": "Almacén Principal",
    "total_locations": 150,
    "total_slots": 3600,
    "occupied_slots": 2150,
    "available_slots": 1450,
    "occupancy_percentage": 59.72,
    "total_weight": 45230.5,
    "avg_occupancy_per_location": 42.3
}
```

### 2. Obtener Ubicaciones de un Almacén
```javascript
fetch('/manager/warehouse/locations/api/warehouse/1')
    .then(response => response.json())
    .then(locations => {
        locations.forEach(location => {
            console.log(location.code + ': ' + location.full_name);
        });
    });
```

**Respuesta:**
```json
[
    {
        "id": 1,
        "uid": "loc-001",
        "code": "A-001",
        "full_name": "Piso 1 - Stand A - Sección 001"
    },
    {
        "id": 2,
        "uid": "loc-002",
        "code": "A-002",
        "full_name": "Piso 1 - Stand A - Sección 002"
    }
]
```

### 3. Búsqueda por Código de Barras
```javascript
const barcode = "BAR123456";
fetch(`/manager/warehouse/locations/api/barcode/${barcode}`)
    .then(response => response.json())
    .then(location => {
        console.log('Ubicación encontrada:');
        console.log('Código:', location.code);
        console.log('Nombre completo:', location.full_name);
        console.log('Resumen:', location.summary);
    });
```

**Respuesta:**
```json
{
    "id": 5,
    "uid": "loc-005",
    "code": "B-005",
    "full_name": "Piso 2 - Stand B - Sección 005",
    "summary": {
        "total_slots": 24,
        "occupied_slots": 18,
        "available_slots": 6,
        "occupancy_percentage": 75,
        "current_weight": 1250.5
    }
}
```

### 4. Histórico de un Slot
```javascript
const slotUid = "slot-001";
fetch(`/manager/warehouse/history/api/slot/${slotUid}`)
    .then(response => response.json())
    .then(data => {
        console.log('Histórico de:', data.slot.address);
        data.movements.forEach(movement => {
            console.log(`${movement.recorded_at}: ${movement.type_label}`);
            console.log(`  Cantidad: ${movement.quantity_delta}`);
            console.log(`  Razón: ${movement.reason}`);
        });
    });
```

### 5. Generar Reporte de Inventario
```javascript
const formData = new FormData();
formData.append('warehouse_id', 1);
formData.append('date_from', '2025-01-01');
formData.append('date_to', '2025-01-31');
formData.append('format', 'excel');

fetch('/manager/warehouse/reports/inventory', {
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: formData
})
.then(response => response.json())
.then(data => {
    console.log('Reporte generado:', data.filename);
    // Descargar archivo si está disponible
});
```

---

## 🛠️ Ejemplos de Operaciones en Controladores

### 1. Crear Movimiento en un Slot (desde controlador)
```php
use App\Models\Warehouse\WarehouseInventorySlot;

$slot = WarehouseInventorySlot::uid($slotUid)->firstOrFail();

// Agregar cantidad
$slot->addQuantity(
    amount: 10,
    reason: 'Entrada de mercancía - Factura #12345',
    userId: auth()->user()->id,
    warehouseId: $warehouse->id
);
```

### 2. Crear un Nuevo Almacén (desde controlador)
```php
use App\Models\Warehouse\Warehouse;

$warehouse = Warehouse::create([
    'uid' => 'warehouses-' . uniqid(),
    'title' => 'ALMACÉN NUEVO',
    'slug' => 'almacen-nuevo',
    'description' => 'Descripción del almacén',
    'available' => true,
]);

// Registrar en activity log automáticamente
activity()
    ->causedBy(auth()->user())
    ->performedOn($warehouse)
    ->event('created')
    ->log('Almacén creado');
```

### 3. Crear Nueva Ubicación con Slots
```php
use App\Models\Warehouse\WarehouseLocation;

$location = WarehouseLocation::create([
    'warehouse_id' => $warehouse->id,
    'floor_id' => $floor->id,
    'style_id' => $style->id,
    'code' => 'A-001',
    'barcode' => 'BAR-A-001',
    'position_x' => 1,
    'position_y' => 1,
    'position_z' => 0,
    'total_levels' => 4,
    'total_sections' => 2,
    'capacity' => 5000,
]);

// Crear los slots automáticamente
$location->createSlots();
```

### 4. Obtener Slots Ocupados de una Ubicación
```php
$occupiedSlots = $location->slots()
    ->where('is_occupied', true)
    ->with('product', 'lastInventarie')
    ->get();

foreach ($occupiedSlots as $slot) {
    echo "Slot: " . $slot->getAddress();
    echo "Producto: " . $slot->product->title;
    echo "Cantidad: " . $slot->quantity;
    echo "Peso: " . $slot->weight_current . "kg";
}
```

---

## 🔐 Compatibilidad con Rutas Legacy

Las rutas antiguas de `/manager/inventaries` siguen funcionando:

```blade
<!-- Sigue funcionando (redirige automáticamente) -->
<a href="{{ route('manager.inventaries') }}">Almacenes</a>
<a href="{{ route('manager.warehouses.create') }}">Crear</a>
<a href="{{ route('manager.warehouses.edit', $warehouse->uid) }}">Editar</a>
<a href="{{ route('manager.warehouses.locations', $warehouse->uid) }}">Ubicaciones</a>
<a href="{{ route('manager.warehouses.history') }}">Histórico</a>
```

Pero las nuevas rutas son preferidas:
```blade
<!-- Nuevas rutas (preferidas) -->
<a href="{{ route('manager.warehouse') }}">Dashboard</a>
<a href="{{ route('manager.warehouse.list') }}">Almacenes</a>
<a href="{{ route('manager.warehouse.create') }}">Crear</a>
<a href="{{ route('manager.warehouse.view', $warehouse->uid) }}">Ver</a>
<a href="{{ route('manager.warehouse.locations', ['warehouse_uid' => $warehouse->uid]) }}">Ubicaciones</a>
<a href="{{ route('manager.warehouse.history') }}">Histórico</a>
```


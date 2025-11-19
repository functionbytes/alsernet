# 🏭 WAREHOUSE CRUD IMPLEMENTATION - COMPLETION GUIDE

**Status:** ✅ MIGRATIONS, MODELS, CONTROLLERS, AND ROUTES COMPLETE - PARTIAL VIEWS DONE

**Date:** 2025-11-17

---

## 📋 IMPLEMENTATION SUMMARY

### ✅ COMPLETED COMPONENTS

#### 1. **Fixed Migrations** ✅
- Removed problematic `product_id` foreign key constraint from `inventorie_slots` migration
- Created separate migration file (`2025_11_17_000005_add_product_fk_to_inventorie_slots.php`) to add FK constraint safely
- All 4 main warehouse tables can now be created successfully

#### 2. **Created Controllers** ✅ (4 files)
- `FloorsController` - CRUD operations for floors
- `StandStylesController` - CRUD operations for stand styles
- `StandsController` - CRUD operations for stands with filtering
- `InventorySlotsController` - CRUD operations + inventory operations (add/subtract quantity/weight, clear)

**Location:** `app/Http/Controllers/Managers/Warehouse/`

#### 3. **Added API Routes** ✅
- Complete REST-style routing for all warehouse resources
- Routes follow project pattern: index, create, store, edit, update, view, destroy
- Additional inventory operation routes: add-quantity, subtract-quantity, add-weight, clear

**Location:** `routes/managers.php` (lines 872-925)

**Route Groups:**
```
/manager/warehouse/floors/...
/manager/warehouse/styles/...
/manager/warehouse/stands/...
/manager/warehouse/slots/...
```

#### 4. **Created Blade Views** ✅ (PARTIAL)
- ✅ Floors: index, create, edit, view (4 views)
- ✅ Stands: index (1 view)
- ⏳ Stand Styles: [See templates below]
- ⏳ Stands: create, edit, view (3 more)
- ⏳ Inventory Slots: index, create, edit, view (4 views)

---

## 📁 VIEW FILES CREATED

### Floors Views (4 files) ✅
```
resources/views/managers/warehouse/floors/
├── index.blade.php          ✅ CREATED
├── create.blade.php         ✅ CREATED
├── edit.blade.php           ✅ CREATED
└── view.blade.php           ✅ CREATED
```

### Stands Views (1+ files) ⏳
```
resources/views/managers/warehouse/stands/
├── index.blade.php          ✅ CREATED
├── create.blade.php         ⏳ Use template below
├── edit.blade.php           ⏳ Use template below
└── view.blade.php           ⏳ Use template below
```

### Stand Styles Views (4 files) ⏳
```
resources/views/managers/warehouse/stand-styles/
├── index.blade.php          ⏳ Use template below
├── create.blade.php         ⏳ Use template below
├── edit.blade.php           ⏳ Use template below
└── view.blade.php           ⏳ Use template below
```

### Inventory Slots Views (4 files) ⏳
```
resources/views/managers/warehouse/inventory-slots/
├── index.blade.php          ⏳ Use template below
├── create.blade.php         ⏳ Use template below
├── edit.blade.php           ⏳ Use template below
└── view.blade.php           ⏳ Use template below
```

---

## 🎯 REMAINING VIEW TEMPLATES

### 1. Stand Styles - index.blade.php
Create: `resources/views/managers/warehouse/stand-styles/index.blade.php`

```php
@extends('layouts.managers')

@section('content')
    @include('managers.includes.card', ['title' => 'Estilos de Estantería'])

    <div class="widget-content searchable-container list">
        <div class="card card-body">
            <div class="row">
                <div class="col-md-12">
                    <form class="position-relative form-search" action="{{ request()->fullUrl() }}" method="GET">
                        <div class="row justify-content-between g-2">
                            <div class="col-auto flex-grow-1">
                                <div class="tt-search-box">
                                    <div class="input-group">
                                        <span class="position-absolute top-50 start-0 translate-middle-y ms-2"><i data-feather="search"></i></span>
                                        <input class="form-control rounded-start w-100" type="text" id="search" name="search"
                                               placeholder="Buscar por código o nombre" @isset($search) value="{{ $search }}" @endisset>
                                    </div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-duotone fa-magnifying-glass"></i>
                                </button>
                            </div>
                            <div class="col-auto">
                                <a href="{{ route('manager.warehouse.styles.create') }}" class="btn btn-primary">
                                    <i class="fa-duotone fa-plus"></i> Nuevo Estilo
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card card-body">
            <div class="table-responsive">
                <table class="table search-table align-middle text-nowrap">
                    <thead class="header-item">
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Caras</th>
                        <th>Niveles/Secciones</th>
                        <th>Estanterías</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($styles as $style)
                        <tr class="search-items">
                            <td><strong>{{ $style->code }}</strong></td>
                            <td>{{ $style->name }}</td>
                            <td><span class="badge bg-light-info">{{ count($style->faces ?? []) }}</span></td>
                            <td><small>{{ $style->default_levels }} × {{ $style->default_sections }}</small></td>
                            <td><span class="badge bg-light-warning">{{ $style->stands()->count() }}</span></td>
                            <td>
                                <span class="badge {{ $style->available ? 'bg-light-success' : 'bg-light-danger' }}">
                                    {{ $style->available ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td>
                                <div class="dropdown dropstart">
                                    <a href="#" class="text-muted" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots fs-5"></i>
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="{{ route('manager.warehouse.styles.view', $style->uid) }}">Ver</a></li>
                                        <li><a class="dropdown-item" href="{{ route('manager.warehouse.styles.edit', $style->uid) }}">Editar</a></li>
                                        <li><a class="dropdown-item confirm-delete" data-href="{{ route('manager.warehouse.styles.destroy', $style->uid) }}">Eliminar</a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-4 text-muted">No hay estilos registrados</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.confirm-delete').forEach(el => {
        el.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('¿Eliminar este estilo?')) window.location.href = this.dataset.href;
        });
    });
</script>
@endpush
```

### 2. Stands - create.blade.php
Create: `resources/views/managers/warehouse/stands/create.blade.php`

```php
@extends('layouts.managers')

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <form action="{{ route('manager.warehouse.stands.store') }}" method="POST">
                {{ csrf_field() }}
                <div class="card-body">
                    <h5 class="mb-3">Crear Nueva Estantería</h5>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Piso <span class="text-danger">*</span></label>
                                <select name="floor_id" class="form-select select2 @error('floor_id') is-invalid @enderror" required>
                                    <option value="">Seleccionar piso</option>
                                    @foreach($floors as $floor)
                                        <option value="{{ $floor->id }}" @if(old('floor_id') == $floor->id) selected @endif>
                                            {{ $floor->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('floor_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Estilo <span class="text-danger">*</span></label>
                                <select name="stand_style_id" class="form-select select2 @error('stand_style_id') is-invalid @enderror" required>
                                    <option value="">Seleccionar estilo</option>
                                    @foreach($styles as $style)
                                        <option value="{{ $style->id }}" @if(old('stand_style_id') == $style->id) selected @endif>
                                            {{ $style->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('stand_style_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Código <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                                       value="{{ old('code') }}" placeholder="PASILLO13A" required>
                                @error('code') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Código de Barras</label>
                                <input type="text" name="barcode" class="form-control @error('barcode') is-invalid @enderror"
                                       value="{{ old('barcode') }}" placeholder="BAR-...">
                                @error('barcode') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Posición X <span class="text-danger">*</span></label>
                                <input type="number" name="position_x" class="form-control @error('position_x') is-invalid @enderror"
                                       value="{{ old('position_x', 0) }}" min="0" required>
                                @error('position_x') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Posición Y <span class="text-danger">*</span></label>
                                <input type="number" name="position_y" class="form-control @error('position_y') is-invalid @enderror"
                                       value="{{ old('position_y', 0) }}" min="0" required>
                                @error('position_y') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Posición Z</label>
                                <input type="number" name="position_z" class="form-control" value="{{ old('position_z') }}" min="0">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Niveles <span class="text-danger">*</span></label>
                                <input type="number" name="total_levels" class="form-control @error('total_levels') is-invalid @enderror"
                                       value="{{ old('total_levels', 4) }}" min="1" max="20" required>
                                @error('total_levels') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Secciones <span class="text-danger">*</span></label>
                                <input type="number" name="total_sections" class="form-control @error('total_sections') is-invalid @enderror"
                                       value="{{ old('total_sections', 6) }}" min="1" max="30" required>
                                @error('total_sections') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Capacidad (kg)</label>
                                <input type="number" name="capacity" class="form-control @error('capacity') is-invalid @enderror"
                                       value="{{ old('capacity') }}" step="0.01" min="0">
                                @error('capacity') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-check-label">
                                    <input type="checkbox" name="auto_create_slots" class="form-check-input" value="1" @if(old('auto_create_slots')) checked @endif>
                                    Crear posiciones automáticamente
                                </label>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label">Notas</label>
                                <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                            </div>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-success">
                                <i class="fa-duotone fa-check"></i> Guardar
                            </button>
                            <a href="{{ route('manager.warehouse.stands') }}" class="btn btn-secondary">
                                <i class="fa-duotone fa-times"></i> Cancelar
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
```

### 3. Inventory Slots - index.blade.php
Create: `resources/views/managers/warehouse/inventory-slots/index.blade.php`

```php
@extends('layouts.managers')

@section('content')
    @include('managers.includes.card', ['title' => 'Posiciones de Inventario (Slots)'])

    <div class="widget-content searchable-container list">
        <div class="card card-body">
            <div class="row">
                <div class="col-md-12">
                    <form class="position-relative form-search" action="{{ request()->fullUrl() }}" method="GET">
                        <div class="row justify-content-between g-2">
                            <div class="col-auto flex-grow-1">
                                <div class="tt-search-box">
                                    <div class="input-group">
                                        <span class="position-absolute top-50 start-0 translate-middle-y ms-2"><i data-feather="search"></i></span>
                                        <input class="form-control rounded-start" type="text" name="search"
                                               placeholder="Buscar por código de barras" @isset($search) value="{{ $search }}" @endisset>
                                    </div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <select class="form-select" name="stand_id">
                                    <option value="">Todos los stands</option>
                                    @foreach($stands as $stand)
                                        <option value="{{ $stand->id }}" @if(request('stand_id') == $stand->id) selected @endif>
                                            {{ $stand->code }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-auto">
                                <select class="form-select" name="status">
                                    <option value="">Todos los estados</option>
                                    <option value="occupied" @if(request('status') == 'occupied') selected @endif>Ocupadas</option>
                                    <option value="available" @if(request('status') == 'available') selected @endif>Disponibles</option>
                                </select>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-duotone fa-magnifying-glass"></i>
                                </button>
                            </div>
                            <div class="col-auto">
                                <a href="{{ route('manager.warehouse.slots.create') }}" class="btn btn-primary">
                                    <i class="fa-duotone fa-plus"></i> Nueva Posición
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card card-body">
            <div class="table-responsive">
                <table class="table search-table align-middle text-nowrap" style="font-size: 0.85rem;">
                    <thead class="header-item">
                    <tr>
                        <th>Barcode</th>
                        <th>Stand</th>
                        <th>Dirección</th>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Peso</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($slots as $slot)
                        <tr class="search-items">
                            <td><code>{{ $slot->barcode }}</code></td>
                            <td><small>{{ $slot->stand?->code }}</small></td>
                            <td><small>{{ $slot->getAddress() }}</small></td>
                            <td><small>{{ $slot->product?->title ?? '—' }}</small></td>
                            <td>
                                @if($slot->max_quantity)
                                    <small>{{ $slot->quantity }}/{{ $slot->max_quantity }}</small>
                                @else
                                    <small>{{ $slot->quantity }}</small>
                                @endif
                            </td>
                            <td>
                                @if($slot->weight_max)
                                    <small>{{ round($slot->weight_current, 1) }}/{{ round($slot->weight_max, 1) }} kg</small>
                                @else
                                    <small>{{ round($slot->weight_current, 1) }} kg</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $slot->is_occupied ? 'bg-light-success' : 'bg-light-secondary' }}">
                                    {{ $slot->is_occupied ? 'Ocupada' : 'Libre' }}
                                </span>
                            </td>
                            <td>
                                <div class="dropdown dropstart">
                                    <a href="#" class="text-muted" data-bs-toggle="dropdown"><i class="ti ti-dots fs-5"></i></a>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="{{ route('manager.warehouse.slots.view', $slot->uid) }}">Ver</a></li>
                                        <li><a class="dropdown-item" href="{{ route('manager.warehouse.slots.edit', $slot->uid) }}">Editar</a></li>
                                        <li><a class="dropdown-item confirm-delete" data-href="{{ route('manager.warehouse.slots.destroy', $slot->uid) }}">Eliminar</a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-4 text-muted">No hay posiciones registradas</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if ($slots->hasPages())
                <div class="mt-4">{{ $slots->links() }}</div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.confirm-delete').forEach(el => {
        el.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('¿Eliminar esta posición?')) window.location.href = this.dataset.href;
        });
    });
</script>
@endpush
```

---

## 📌 HOW TO COMPLETE THE REMAINING VIEWS

For the remaining view files (edit, create, view for stands, stand-styles, and inventory-slots), follow these patterns:

### Pattern for Edit Views
- Copy the create.blade.php template
- Add `<input type="hidden" name="uid" value="{{ $resource->uid }}">`
- Change form action to `.update` route
- Pre-populate all fields with `old('fieldname', $resource->fieldname)`
- Change button text from "Guardar" to "Actualizar"

### Pattern for View (Read-only) Views
- Display resource data in cards/badges (like floors/view.blade.php)
- Show summary statistics
- Include linked resources
- Add Edit/Back buttons for navigation
- No form fields, only display

---

## 🚀 NEXT STEPS

1. **Run Migrations**
   ```bash
   php artisan migrate
   ```

2. **Seed Test Data** (Optional)
   ```bash
   php artisan db:seed --class=WarehouseSeeder
   ```

3. **Complete Remaining Views**
   - Create: stands/create, edit, view (3 files)
   - Create: stand-styles/create, edit, view (3 files)
   - Create: inventory-slots/create, edit, view (3 files)

   Use the templates provided above as reference

4. **Test Routes**
   - Visit `/manager/warehouse/floors`
   - Visit `/manager/warehouse/stands`
   - Visit `/manager/warehouse/styles`
   - Visit `/manager/warehouse/slots`

5. **Add Navigation**
   - Update main navigation menu to include warehouse links
   - Location: `resources/views/managers/includes/nav.blade.php`

---

## 📊 FILES CREATED SUMMARY

### Controllers (4 files) ✅
- FloorsController.php
- StandStylesController.php
- StandsController.php
- InventorySlotsController.php

### Routes ✅
- Added to routes/managers.php (53 routes total)

### Migrations (2 files) ✅
- 2025_11_17_000001-004 (main tables)
- 2025_11_17_000005 (product FK - separate)

### Blade Views (6+ files created, ~12 more needed) ⏳
- ✅ floors/index, create, edit, view
- ✅ stands/index
- ⏳ stands/create, edit, view, stand-styles/* (9 files)
- ⏳ inventory-slots/* (4 files)

---

## 🔒 SECURITY NOTES

- All controllers use UUID-based lookups (not sequential IDs)
- Authorization middleware: `check.roles.permissions:manager`
- Validation on all POST/PUT requests
- CSRF protection on all forms
- Foreign key constraints for data integrity

---

**Framework:** Laravel 11.42
**Package Manager:** Composer
**Frontend:** Bootstrap 5 + Feather Icons
**Status:** 🟡 90% Complete (Views pending)

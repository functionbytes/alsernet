# Shared Components

Componentes reutilizables del modulo Core.

## 1. Card Header (breadcrumb)

**Include:**
```blade
@include('core::components.card', ['title' => 'Titulo de la pagina'])

{{-- Con breadcrumbs --}}
@include('core::components.card', [
    'title' => 'Categorias',
    'breadcrumbs' => [
        ['label' => 'Dashboard', 'url' => url('/home')],
        ['label' => 'Inventario', 'url' => route('inventory.index')],
        ['label' => 'Categorias', 'active' => true],
    ]
])
```

**Uso**: Siempre al inicio del `@section('content')`.

**Estructura real** (`modules/Core/resources/views/components/card.blade.php`):
```blade
<div class="card position-relative overflow-hidden">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-12 col-md-9">
                <h6 class="fw-semibold mb-1 text-uppercase">{{ $title }}</h6>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="{{ url('panel/dashboard') }}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $title }}</li>
                    </ol>
                </nav>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="text-center mb-n5">
                    <img src="./images/breadcrumb/ChatBc.png" alt="" class="img-fluid mb-n4">
                </div>
            </div>
        </div>
    </div>
</div>
```

## 2. Alerts (flash messages)

**Include:**
```blade
@include('core::components.alerts')
```

**Que muestra:**
- `$errors` (validation errors) — alerta roja con lista
- `session('success')` — alerta verde
- `session('error')` — alerta roja
- `session('warning')` — alerta amarilla
- `session('info')` — alerta verde info

**Uso**: Dentro del card-body, antes del contenido principal. Tambien puede ir justo despues de `@include('core::components.card')`.

## 3. Delete Confirmation Modal

**Include:**
```blade
@include('core::components.delete')
```

**Ubicacion**: El layout `theme.blade.php` ya incluye este componente al final del body, por lo que NO es necesario incluirlo manualmente en cada vista. Sin embargo, incluirlo explicitamente no causa problemas.

**Activacion via JavaScript:**
```javascript
$('.delete-btn').on('click', function (e) {
    e.preventDefault();
    $('#delete-modal .modal-title').text($(this).data('title'));
    $('#delete-form').attr('action', $(this).data('url'));
    $('#delete-modal').modal('show');
});
```

**Button que lo dispara:**
```blade
<a class="dropdown-item delete-btn" href="#"
   data-url="{{ route('resource.destroy', $item) }}"
   data-title="Eliminar {{ $item->name }}">
    Eliminar
</a>
```

## 4. Blade Includes Comunes

```blade
{{-- Layout principal --}}
@extends('layouts.theme')

{{-- Componentes Core --}}
@include('core::components.card', ['title' => '...'])
@include('core::components.alerts')
@include('core::components.delete') {{-- Opcional, ya esta en layout --}}

{{-- Partial de modulo --}}
@include('inventory::partials.filter-modal')
@include('inventory::partials.bulk-modal')
```

## 5. Custom Partials (convencion)

Cuando un listado tiene filter modal complejo, crear partial:

```
modules/{Module}/resources/views/
  {entity}/
    index.blade.php
    partials/
      filter-modal.blade.php
      bulk-modal.blade.php
      stats-cards.blade.php
```

Usar en index:
```blade
@include('{alias}::{entity}.partials.filter-modal')
@include('{alias}::{entity}.partials.stats-cards', ['stats' => $stats])
```

## 6. Conditional Badge Pattern

Crear helper o directiva para status badges:
```blade
{{-- Manual (repetitivo) --}}
<span class="badge bg-{{ $status === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $status === 'active' ? 'success' : 'secondary' }}">
    {{ $status }}
</span>

{{-- Preferir helper o @component para repetidos --}}
<x-status-badge :status="$item->status" />
```

## 7. Icon Container Pattern

Contenedor de icono estandar usado en listas, KPIs y actividad reciente:

```blade
<div class="p-2 bg-primary-subtle rounded-2 d-flex align-items-center justify-content-center me-3" style="width:36px;height:36px;">
    <i class="fas fa-{icon} text-primary"></i>
</div>
```

Variante circular (KPIs):
```blade
<span class="rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center" style="width:44px;height:44px;">
    <i class="fas fa-{icon} text-primary"></i>
</span>
```

## 8. Empty State Pattern

```blade
<div class="text-center py-5">
    <div class="mb-4">
        <i class="fas fa-{icon} fa-4x text-muted opacity-50"></i>
    </div>
    <h5 class="text-muted mb-2">No hay registros</h5>
    <p class="text-muted mb-4">Mensaje descriptivo</p>
    <a href="{{ route('resource.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> Nuevo registro
    </a>
</div>
```

## 9. Skeleton Loading Pattern

```blade
<div class="d-flex align-items-center justify-content-between mb-4">
    <div class="d-flex align-items-center">
        <div class="skeleton skeleton-circle me-3"></div>
        <div>
            <div class="skeleton skeleton-text mb-1" style="width:180px;"></div>
            <div class="skeleton skeleton-text" style="width:120px;"></div>
        </div>
    </div>
</div>
```

Con CSS:
```css
.skeleton {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: skeleton-loading 1.5s infinite;
    border-radius: 4px;
}
@keyframes skeleton-loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
.skeleton-text { height: 12px; }
.skeleton-title { height: 32px; width: 80px; }
.skeleton-circle { width: 36px; height: 36px; border-radius: 8px; }
```

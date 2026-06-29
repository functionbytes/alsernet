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

## 2. Alerts (flash messages)

**Include:**
```blade
@include('core::components.alerts')
```

**Que muestra:**
- `$errors` (validation errors)
- `session('success')`
- `session('error')`
- `session('warning')`
- `session('info')`

**Uso**: Dentro del card-body, antes del contenido principal.

## 3. Delete Confirmation Modal

**Include:**
```blade
@include('core::components.delete')
```

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
@include('core::components.delete')

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

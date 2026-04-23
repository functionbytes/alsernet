# List/Index Page Patterns

Standard pattern for all CRUD listing pages.

## Full Index Page Template

```blade
@extends('layouts.theme')

@section('title', 'Recursos')

@section('content')

    @include('core::components.card', ['title' => 'Recursos'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- ========== HEADER ========== --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Recursos</h5>
                        <p class="small mb-0 text-muted">Descripcion de la vista</p>
                    </div>
                    <div class="ms-auto">
                        <div class="btn-group">
                            <button type="button" class="btn bg-primary-subtle text-primary dropdown-toggle"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                Acciones
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('resource.create') }}">Nuevo registro</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="{{ route('resource.export') }}">Exportar CSV</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ========== STATS CARDS ========== --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['total']) }}</h4>
                                <small class="text-muted">Registros totales</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Activos</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['active']) }}</h4>
                                <small class="text-muted">En uso</small>
                            </div>
                        </div>
                    </div>
                    {{-- mas cards... --}}
                </div>
            </div>

            {{-- ========== SEARCH + FILTER BAR ========== --}}
            @php
                $activeFilters = collect([
                    request('status'), request('category_id'),
                    request('date_from'), request('date_to'),
                ])->filter()->count();
            @endphp

            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('resource.index') }}" id="filterForm">
                    <div class="d-flex gap-2 align-items-center">
                        <div class="flex-fill">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control border-start-0 ps-0"
                                       placeholder="Buscar..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-secondary position-relative flex-shrink-0"
                                data-bs-toggle="modal" data-bs-target="#filterModal">
                            <i class="fas fa-sliders me-1"></i>
                            @if($activeFilters > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                                    {{ $activeFilters }}
                                </span>
                            @endif
                        </button>
                        <button type="submit" class="btn btn-primary flex-shrink-0">
                            <i class="fas fa-search"></i>
                        </button>
                        @if(request('search') || $activeFilters > 0)
                            <a href="{{ route('resource.index') }}" class="btn btn-outline-secondary flex-shrink-0" title="Limpiar">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>

                    {{-- Preservar filtros del modal cuando se busca --}}
                    @foreach(['status','category_id','date_from','date_to'] as $f)
                        @if(request()->has($f))
                            <input type="hidden" name="{{ $f }}" value="{{ request($f) }}">
                        @endif
                    @endforeach
                </form>
            </div>

            {{-- ========== FILTER TABS (optional) ========== --}}
            <ul class="nav nav-tabs border-0 user-profile-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 active"
                       href="{{ route('resource.index') }}" role="tab">
                        <span class="d-none d-md-block">Todos</span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3"
                       href="{{ route('resource.index', ['status' => 'active']) }}" role="tab">
                        <span class="d-none d-md-block">Activos</span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3"
                       href="{{ route('resource.index', ['status' => 'inactive']) }}" role="tab">
                        <span class="d-none d-md-block">Inactivos</span>
                    </a>
                </li>
            </ul>

            {{-- ========== TABLE ========== --}}
            <div class="card-body">
                @if($items->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>Nombre</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $item)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $item->id }}">
                                        </td>
                                        <td>
                                            <div class="small fw-semibold">{{ $item->name }}</div>
                                            @if($item->email)
                                                <small class="text-muted">{{ $item->email }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $item->is_active ? 'success' : 'secondary' }}-subtle text-{{ $item->is_active ? 'success' : 'secondary' }}">
                                                {{ $item->is_active ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="small">{{ $item->created_at->format('d/m/Y H:i') }}</div>
                                            <small class="text-muted">{{ $item->created_at->diffForHumans() }}</small>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" href="{{ route('resource.show', $item) }}">Ver detalle</a></li>
                                                    <li><a class="dropdown-item" href="{{ route('resource.edit', $item) }}">Editar</a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn" href="#"
                                                           data-url="{{ route('resource.destroy', $item) }}"
                                                           data-title="Eliminar {{ $item->name }}">
                                                            Eliminar
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    {{-- ========== EMPTY STATE ========== --}}
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x mb-3 text-muted opacity-50"></i>
                        <h5 class="fw-bold mb-2">
                            @if(request('search') || $activeFilters > 0)
                                No se encontraron resultados
                            @else
                                No hay registros
                            @endif
                        </h5>
                        <p class="text-muted mb-4">
                            @if(request('search'))
                                No hay resultados para "{{ request('search') }}"
                            @else
                                Aun no hay registros creados
                            @endif
                        </p>
                        @if(request('search') || $activeFilters > 0)
                            <a href="{{ route('resource.index') }}" class="btn btn-secondary">Limpiar filtros</a>
                        @else
                            <a href="{{ route('resource.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Nuevo registro
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            {{-- ========== PAGINATION ========== --}}
            @if($items->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando {{ $items->firstItem() }} - {{ $items->lastItem() }} de {{ $items->total() }}
                        </div>
                        <div>
                            {{ $items->appends(request()->input())->links() }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Modales al final del content --}}
    @include('path.to.filter-modal')

    {{-- Bulk toolbar flotante --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar accion
        </button>
    </div>

    @include('path.to.bulk-modal')
    @include('core::components.delete')

@endsection
```

## Reglas del Listado

1. **Stats cards**: 4 columnas en md+ con `col-md-3`, usando `card bg-light-secondary h-100`
2. **Search bar**: input-group con icono `fa-search` en input-group-text bg-white
3. **Filter modal button**: usa `fa-sliders` con badge rojo contador de filtros activos
4. **Filter tabs** (optional): `nav nav-tabs border-0 user-profile-tab`, links con `fs-3 py-3`, texto en `d-none d-md-block`
5. **Table**: `table table-hover align-middle text-nowrap`, `thead class="table-light"`
6. **Checkbox column**: `width="3%"`, class `bulk-checkbox`
7. **Actions column**: `text-center`, dropdown con `fa-ellipsis-vertical`, boundary viewport
8. **Dropdown items**: sin iconos, sin `text-danger` en Eliminar
9. **Badges**: pattern `bg-{color}-subtle text-{color}` para estados
10. **Empty state**: icono grande `fa-3x`, mensaje contextual por busqueda/vacio
11. **Pagination**: con info de rango `Mostrando X-Y de Z`, preserva query con `appends()`

## Colores para Badges

| Estado | Clases |
|--------|--------|
| Exito | `bg-success-subtle text-success` |
| Info | `bg-info-subtle text-info` |
| Advertencia | `bg-warning-subtle text-warning` |
| Peligro | `bg-danger-subtle text-danger` (solo para indicar, NO para acciones destructivas) |
| Neutro | `bg-secondary-subtle text-secondary` |
| Primario | `bg-primary-subtle text-primary` |

## Filter Tabs Pattern

Cuando se necesitan tabs de filtrado rapido (como "Todos / Activos / Inactivos / Papelera"):

```blade
<ul class="nav nav-tabs border-0 user-profile-tab" role="tablist">
    <li class="nav-item" role="presentation">
        <a class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 {{ $activeFilter === '' ? 'active' : '' }}"
           href="{{ route('resource.index') }}" role="tab">
            <span class="d-none d-md-block">Todos</span>
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 {{ $activeFilter === 'active' ? 'active' : '' }}"
           href="{{ route('resource.index', ['status' => 'active']) }}" role="tab">
            <span class="d-none d-md-block">Activos</span>
        </a>
    </li>
</ul>
```

**Nota**: Estos tabs NO usan Bootstrap tab-pane/switching. Son links de navegacion que aplican filtros por query string.

## Trashed/Soft Delete Pattern

Cuando el modelo usa SoftDeletes, agregar tab "Papelera":

```blade
<a class="nav-link ... {{ $trashed ? 'active' : '' }}"
   href="{{ route('resource.index', ['trashed' => 1]) }}" role="tab">
    <span class="d-none d-md-block">Papelera</span>
</a>
```

En la tabla de papelera:
- Ocultar filtros de busqueda avanzada
- Mostrar botones "Restaurar" y "Eliminar definitivamente"
- Usar modal de confirmacion personalizado o `@include('core::components.delete')` con ajuste de mensaje

## Variantes de Tabla

### Tabla con mini badges debajo del nombre
```blade
<td>
    <div class="small fw-semibold">{{ $item->name }}</div>
    @if($item->categories->isNotEmpty())
        <div class="mt-1">
            @foreach($item->categories as $cat)
                <span class="badge text-decoration-none me-1" style="background-color: {{ $cat->color }}">
                    {{ $cat->name }}
                </span>
            @endforeach
        </div>
    @endif
    @if($item->tags->isNotEmpty())
        <div class="mt-1">
            @foreach($item->tags as $tag)
                <span class="badge bg-light text-secondary border me-1">{{ $tag->name }}</span>
            @endforeach
        </div>
    @endif
</td>
```

### Tabla con estado y fecha programada
```blade
<td class="text-center">
    @if($item->isPublished())
        <span class="badge bg-success-subtle text-success">Publicado</span>
        @if($item->willBeUnpublished())
            <br><small class="text-muted"><i class="fas fa-clock"></i> {{ $item->unpublish_at->format('d/m/Y H:i') }}</small>
        @endif
    @elseif($item->isDraft())
        <span class="badge bg-secondary-subtle text-secondary">Borrador</span>
        @if($item->willBePublished())
            <br><small class="text-muted"><i class="fas fa-clock"></i> {{ $item->publish_at->format('d/m/Y H:i') }}</small>
        @endif
    @endif
</td>
```

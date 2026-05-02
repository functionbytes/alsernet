---
name: ui-patterns
description: "Project UI/design system for this Laravel modular app. Contains exact patterns for index pages (lists with search+filter modal+bulk actions), CRUD forms, dashboards, modals, shared components, and JavaScript patterns. Auto-preloaded by frontend agent. Use when creating any view (index, create, edit, settings, dashboard)."
disable-model-invocation: false
user-invocable: true
---

# UI Patterns - Project Design System

This is the STANDARD design system for all views in this project. ALWAYS follow these exact patterns.

## MANDATORY: Read by Pattern Type

- [list-patterns.md](list-patterns.md) - Index pages (table, search, filter modal, bulk actions, stats cards)
- [form-patterns.md](form-patterns.md) - Create/edit forms (two-column layout, validation, field types)
- [modal-patterns.md](modal-patterns.md) - Filter modal, delete modal, bulk action modal
- [dashboard-patterns.md](dashboard-patterns.md) - Stats cards, KPIs, charts (DevExpress)
- [components.md](components.md) - Shared components (@include('core::components.*'))
- [javascript-patterns.md](javascript-patterns.md) - BulkActions, select2, datepicker, toastr, delete confirm

## Quick Reference: Base Structure

### Index Page (CRUD list)
```blade
@extends('layouts.theme')
@section('title', 'Resource Name')
@section('content')
    @include('core::components.card', ['title' => 'Resource Name'])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            {{-- Stats cards --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    {{-- 4 col-md-3 stat cards --}}
                </div>
            </div>

            {{-- Search bar + filter modal button --}}
            <div class="card-body border-bottom">
                {{-- See list-patterns.md --}}
            </div>

            {{-- Table with checkboxes + dropdown actions --}}
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        {{-- See list-patterns.md --}}
                    </table>
                </div>
            </div>

            {{-- Pagination --}}
            @if($items->hasPages())
                <div class="card-footer bg-white border-top">
                    {{ $items->appends(request()->input())->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Filter modal --}}
    @include('partials.filter-modal') {{-- See modal-patterns.md --}}

    {{-- Bulk toolbar flotante + bulk modal --}}
    {{-- See modal-patterns.md --}}

    {{-- Delete confirmation --}}
    @include('core::components.delete')
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });
    {{-- See javascript-patterns.md --}}
});
</script>
@endpush
```

### Create/Edit Form
```blade
@extends('layouts.theme')
@section('title', 'Crear recurso')
@section('content')
    @include('core::components.card', ['title' => 'Crear recurso'])

    <div class="row g-3">
        {{-- Form column (8) --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('resource.store') }}" method="POST">
                    @csrf
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nuevo recurso</h5>
                        <small class="text-muted">Descripcion</small>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')
                        {{-- Form fields (see form-patterns.md) --}}
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar cambios</button>
                        <a href="{{ route('resource.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Help panel (4) --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Sobre este recurso</h6>
                    <p class="card-text text-muted">Explicacion contextual.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
```

## CRITICAL RULES (NEVER violate)

- **Icons**: Font Awesome 6 ONLY (`fas fa-*`, `far fa-*`, `fab fa-*`). NEVER Tabler (`ti ti-*`)
- **JavaScript**: jQuery + AJAX. NEVER Livewire, Inertia, React, Alpine
- **Section titles**: Capitalize FIRST WORD only (`Informacion basica`, NOT `Informacion Basica`)
- **No inline styles**: NEVER `style=""`. Always create CSS classes
- **CSRF**: Every AJAX `headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }`
- **422 errors**: Parse `xhr.responseJSON.errors` per field
- **Notifications**: `toastr.success/error/warning/info`
- **Table actions**: ALWAYS dropdown with `fa-ellipsis-vertical`, no icons in items, no `text-danger` on delete
- **Modals**: ALWAYS `modal-dialog-centered`, footer buttons `w-100` stacked (primary `mb-2` top, secondary bottom)
- **select2**: NEVER `theme: 'bootstrap-5'` (CSS not loaded, breaks styles)
- **Primary color**: `#b10100` (Analytics uses red palette `#b10100, #333333, #7b0000`)

## Shared Components Always Used

| Component | Include | Use for |
|-----------|---------|---------|
| Card header | `@include('core::components.card', ['title' => '...'])` | Page header with breadcrumb |
| Alerts | `@include('core::components.alerts')` | Flash messages (success/error/warning) |
| Delete modal | `@include('core::components.delete')` | Confirmation before delete |
| BulkActions | `window.BulkActions.init({...})` | Table bulk selection |

## Required Libraries (already loaded)

- jQuery
- Bootstrap 5
- Select2 (NEVER bootstrap-5 theme)
- DateRangePicker
- Toastr
- Font Awesome 6
- DevExpress jQuery (dxDataGrid, dxChart)
- `/public/core/js/bulk.js` (BulkActions plugin)

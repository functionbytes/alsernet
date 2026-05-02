# /ui-patterns — Project UI/Design System

Standard design system for all views in this project.

## When to use
- Creating any Blade view (index, create, edit, settings, dashboard)
- Writing JavaScript for forms, tables, or modals
- Building admin panel dashboards with charts
- Implementing responsive Bootstrap layouts

## Pattern files
| File | For |
|---|---|
| `.kimi/ui-patterns/list-patterns.md` | Index pages (table, search, filters, bulk actions, tabs) |
| `.kimi/ui-patterns/form-patterns.md` | Create/edit forms (two-column layout, validation, sections) |
| `.kimi/ui-patterns/modal-patterns.md` | Filter modal, delete modal, bulk action modal, centered dialogs |
| `.kimi/ui-patterns/dashboard-patterns.md` | Stats cards, KPIs, ApexCharts, sparklines, radial bars |
| `.kimi/ui-patterns/components.md` | Shared components (@include('core::components.*')) |
| `.kimi/ui-patterns/javascript-patterns.md` | BulkActions, select2, datepicker, toastr, AJAX, ApexCharts, slug generation |

## Quick Reference

### Layout
```blade
@extends('layouts.theme')
@section('title', 'Titulo')
@section('content')
    @include('core::components.card', ['title' => 'Titulo'])
    @include('core::components.alerts')
    {{-- content --}}
@endsection
```

### Colors
- Primary: `#b10100` | Success: `#13C672` | Danger: `#FA896B` | Warning: `#FEC90F`
- Badge pattern: `bg-{color}-subtle text-{color}`

### Icons
- Font Awesome 6 ONLY: `fas fa-*`, `far fa-*`, `fab fa-*`
- NEVER Tabler Icons (`ti ti-*`)

### JavaScript
- jQuery + AJAX primary. NO Livewire, NO Inertia.js
- Select2: NEVER `theme: 'bootstrap-5'`
- Modals: ALWAYS `modal-dialog-centered` with stacked `w-100` buttons
- CSRF: already configured globally via `$.ajaxSetup()`

### Typography
- Section titles: capitalize first word only (`Informacion basica`)
- Card titles: `fw-semibold` or `fw-bold`
- Labels: `form-label fw-semibold`

## Critical rules
- Font Awesome 6 ONLY (never Tabler `ti-*`)
- jQuery + AJAX (never Livewire/Inertia)
- Section titles: capitalize first word only
- Primary color: `#b10100`
- Modals: `modal-dialog-centered`, footer buttons `w-100` stacked
- select2: NEVER `theme: 'bootstrap-5'`
- No inline `style=""` attributes — use Bootstrap classes
- No custom CSS blocks — use existing framework classes

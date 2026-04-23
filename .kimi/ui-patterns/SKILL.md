# UI Patterns - Project Design System

This is the STANDARD design system for all views in this project. ALWAYS follow these exact patterns.

## Read by Pattern Type

- [list-patterns.md](list-patterns.md) — Index pages (table, search, filter modal, bulk actions, stats cards)
- [form-patterns.md](form-patterns.md) — Create/edit forms (two-column layout, validation, field types)
- [modal-patterns.md](modal-patterns.md) — Filter modal, delete modal, bulk action modal
- [dashboard-patterns.md](dashboard-patterns.md) — Stats cards, KPIs, charts (DevExpress)
- [components.md](components.md) — Shared components (@include('core::components.*'))
- [javascript-patterns.md](javascript-patterns.md) — BulkActions, select2, datepicker, toastr, delete confirm

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
- **Primary color**: `#90bb13` (Analytics uses red palette `#b10100, #333333, #7b0000`)

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

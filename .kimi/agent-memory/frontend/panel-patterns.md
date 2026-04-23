# Frontend Agent Memory — Panel UI Patterns

> Auto-generated from real project Blade views. Updated: 2026-04-23

## Layout Base

- **File**: `modules/Theme/resources/views/layouts/theme.blade.php`
- **Usage**: `@extends('layouts.theme')` on ALL panel pages
- **Libraries loaded globally**: jQuery 3.x, Bootstrap 5.3, Select2, Toastr, DateRangePicker, Dropzone, Quill, Tooltipster, Font Awesome 6
- **CSRF**: configured globally via `$.ajaxSetup()`
- **Toastr**: configured with `positionClass: "toast-bottom-right"`
- **BulkActions**: `window.BulkActions` from `/public/core/js/bulk.js`
- **Delete modal**: `#delete-modal` already included in layout
- **Confirm modal**: `window.__confirm(msg, callback)` globally available
- **Global loader**: automatic on AJAX and form submits

## Core Components (modules/Core/resources/views/components/)

### card.blade.php
Breadcrumb header with title. Usage:
```blade
@include('core::components.card', ['title' => 'Titulo'])
```

### alerts.blade.php
Flash messages for: errors, success, error, warning, info. Usage:
```blade
@include('core::components.alerts')
```

### delete.blade.php
Delete confirmation modal. Already in layout. Wire with:
```javascript
$('.delete-btn').on('click', function (e) {
    e.preventDefault();
    $('#delete-modal .modal-title').text($(this).data('title'));
    $('#delete-form').attr('action', $(this).data('url'));
    $('#delete-modal').modal('show');
});
```

## Index Page Pattern (from modules/Page/pages/index.blade.php)

Structure:
1. `@include('core::components.card')`
2. `@include('core::components.alerts')`
3. `.card` wrapper:
   - Header (title + actions dropdown)
   - Stats cards (optional, `bg-light-secondary`)
   - Search + filter bar (input-group with `fa-search`, filter button with badge)
   - Filter tabs (optional, `nav nav-tabs border-0 user-profile-tab`)
   - Table or empty state
   - Pagination with range info
4. Bulk toolbar (floating, fixed bottom)
5. Bulk modal
6. `@include('core::components.delete')`

## Form Pattern (from modules/Page/pages/create.blade.php)

Structure:
1. Two-column: `col-lg-8` (form) + `col-lg-4` (sidebar/publish)
2. Form inside card with header, body, footer
3. Footer buttons stacked: `btn-primary w-100 mb-1` + `btn-light w-100`
4. Validation: `@error('field') is-invalid @enderror` + `field-validation-error` span
5. Required: `<span class="text-danger">*</span>`
6. Select2: `.select2` class, NEVER `theme: 'bootstrap-5'`

## Dashboard Pattern (from modules/Core/dashboard/index.blade.php)

Structure:
1. Filter/nav bar (pills for range selection)
2. KPI row: `col-lg-3 col-md-6`, icon circle 44x44, value + label
3. Sparklines in some KPIs (ApexCharts)
4. Main chart: area chart, height 300px
5. Distribution + recent list: donut chart + list items
6. Queue stats, activity, quick links
7. Security section (optional, gated by `@can('settings.view')`)

Uses **ApexCharts** (NOT DevExpress for dashboard charts):
```javascript
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.54.1/dist/apexcharts.min.js"></script>
```

## Modal Rules

- ALWAYS `modal-dialog-centered`
- ALWAYS `modal-footer flex-column`
- Buttons: `w-100` stacked, primary `mb-2` on top
- Select2 in modal: `dropdownParent: $('#modalId')`
- NEVER `text-danger` on delete button

## Badge Colors

| State | Classes |
|-------|---------|
| Success | `bg-success-subtle text-success` |
| Danger | `bg-danger-subtle text-danger` |
| Warning | `bg-warning-subtle text-warning` |
| Info | `bg-info-subtle text-info` |
| Secondary | `bg-secondary-subtle text-secondary` |
| Primary | `bg-primary-subtle text-primary` |

## Typography Rules

- Section titles: capitalize FIRST word only (`Informacion basica`)
- Card titles: `fw-semibold` or `fw-bold`
- Labels: `form-label fw-semibold`
- Small text: `small`, `text-muted`, or `fs-3` (custom small size in theme)

## Icon Rules

- Font Awesome 6 ONLY
- NEVER Tabler Icons (`ti ti-*`)
- Action dropdown trigger: `fa-ellipsis-vertical`
- Search input: `fa-search`
- Filter button: `fa-sliders`
- Empty states: large icon `fa-3x` or `fa-4x` with `opacity-50`

## JavaScript Patterns Found

- **AJAX setup global**: no need to add CSRF manually
- **Toastr**: `toastr.success/error/warning/info(message, title)`
- **Bulk actions**: `window.BulkActions.init({ checkbox: '.bulk-checkbox' })`
- **Slug generation**: normalize + lowercase + replace accents + replace spaces with dashes
- **Form AJAX**: clear `.is-invalid` and `.field-validation-error`, handle 422
- **Charts**: ApexCharts with `fontFamily: 'inherit'`, destroy before re-render

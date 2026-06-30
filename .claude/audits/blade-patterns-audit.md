# Auditoría de patrones Blade — 2026-04-20

Documento de referencia para aplicar el "golden standard" del proyecto a los 4 módulos Helpdesk divididos.

## TL;DR

1. El **golden standard ya existe** documentado en `.claude/skills/ui-patterns/`. Se confirmó contra implementaciones reales en **Attention, Page, Blog, Newsletter, Reviews, Role**.
2. Los 4 módulos Helpdesk nuevos divergen del golden en 3 familias: **golden-like** (5 CRUDs), **spike-gradient** (2 CRUDs, anti-patrón), **básico** (3 CRUDs).
3. Plan: reconvertir TODO al golden, preservando funcionalidad (drag-drop, color picker, tabs AI, split-sidebar tickets).

## Golden standard — características obligatorias

### Estructura de `index.blade.php`
```blade
@extends('layouts.theme')
@section('title', '...')
@section('content')
    @include('core::components.card', ['title' => '...'])
    <div class="widget-content searchable-container list">
        @include('core::components.alerts')
        <div class="card">
            {{-- Header con botón primario (Nueva X) --}}
            {{-- Stats cards col-md-3/4 bg-light-secondary h-100 --}}
            {{-- Search input-group + filter modal button --}}
            {{-- Table hover align-middle text-nowrap + dropdown acciones --}}
            {{-- Empty state con icono fa-3x --}}
            {{-- Pagination con info de rango --}}
        </div>
    </div>
    @include('core::components.delete')
@endsection
```

### Estructura de `create/edit.blade.php`
```blade
@extends('layouts.theme')
@include('core::components.card', ['title' => '...'])
<div class="row g-3">
    <div class="col-12 col-lg-8">
        <div class="card">
            <form id="...Form" action="..." method="POST">
                @csrf
                <div class="card-header border-bottom p-3">...</div>
                <div class="card-body">
                    @include('core::components.alerts')
                    {{-- campos con @error y field-validation-error --}}
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary w-100 mb-1">Guardar</button>
                    <a class="btn btn-light w-100">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        {{-- panel de ayuda --}}
    </div>
</div>
```

### Reglas obligatorias (derivadas de `rules/blade-views.md` + `skills/ui-patterns/`)
- [x] FontAwesome 6 exclusivo (`fas/far/fab fa-*`) — **NUNCA** `ti ti-*`, nunca mencionar "Tabler"
- [x] `core::components.card` para título + breadcrumb (se genera automáticamente vía NavService)
- [x] `core::components.alerts` para mensajes de sesión/validación
- [x] `core::components.delete` para confirmación (NO `confirm()` nativo)
- [x] `modal-dialog-centered` en TODOS los modales
- [x] Footer buttons: primary `btn btn-primary w-100 mb-1` + secondary `btn btn-light w-100` (stacked)
- [x] Acciones en tabla: dropdown con `fa-ellipsis-vertical`, sin iconos en items, sin `text-danger` en Eliminar
- [x] NO inline styles (`style=""`) excepto valores dinámicos Blade (`style="background: {{ $x->color }}"`)
- [x] Select2: NUNCA `theme: 'bootstrap-5'` (CSS no cargado, rompe estilos)
- [x] Color primario: `#90bb13` (no `#5D87FF` ni gradientes violeta)
- [x] Tabla: `table table-hover align-middle text-nowrap`, `thead class="table-light"`
- [x] Badge status: `bg-{color}-subtle text-{color}`
- [x] Stats cards: `card bg-light-secondary h-100`
- [x] CSRF en AJAX: `'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')`
- [x] Toastr para notifications (no alert nativo)
- [x] Bulk actions via `window.BulkActions.init(...)`
- [x] **Booleanos (`is_active`, `is_featured`, etc.) renderizados como `<select class="form-select">`** — NO checkbox/toggle. Options con labels semánticos (Activa/Inactiva, Si/No)
- [x] **Cada `<h6>` de sección va seguido de `<p class="text-muted small mb-3">` con 1 frase describiendo qué se configura en esa sección**

## Módulos de referencia (golden-like)

| Módulo | Archivo ejemplo | Score |
|---|---|---|
| **Attention** | `settings/sla-policies/index.blade.php` | 9/10 — referencia principal |
| Page | `pages/versions/index.blade.php` | 9/10 |
| Blog | `posts/index.blade.php` | 8/10 |
| Newsletter | `subscribers/index.blade.php` | 8/10 |
| Reviews | `replies/templates/index.blade.php` | 8/10 |
| Role | `roles/index.blade.php` | 7/10 |

## Anti-patrón detectado: Spike-gradient

**Síntomas**:
- `@extends('layouts.theme')` SIN `@include('core::components.card')` (breadcrumb manual)
- Layout `container-fluid` con `d-flex justify-content-between` manual
- Alerts `alert-success/danger` inline en vez de `core::components.alerts`
- Tabla custom con clases no estándar
- `confirm()` nativo en vez de `core::components.delete` modal
- Botón `.btn-primary-custom` custom con gradiente `#5D87FF`
- CSS blocks en `@push('styles')` con `.stats-grid` custom
- Iconos decorativos en headings (`fa-tags me-2 text-primary`)
- Help text menciona "Tabler Icons" (aunque usa `fas`)

**CRUDs afectados**:
- `modules/Theme/resources/views/theme/views/backups/helpdesk/ticket-categories/{index,create,edit}.blade.php` ← primer objetivo
- `modules/HelpdeskCampaigns/resources/views/managers/campaigns/{index,create,edit}.blade.php`

## Clasificación de los 15 CRUDs Helpdesk

| # | CRUD | Estado actual | Acción requerida |
|---|---|---|---|
| 1 | ticket-categories | 🔴 Spike-gradient | Refactor agresivo |
| 2 | ticket-statuses | 🟡 Cerca de golden | Ajustes menores |
| 3 | ticket-tags (Helpdesk base) | 🟡 Verificar | Ajustes menores |
| 4 | ticket-groups | 🟡 Cerca de golden | Ajustes menores |
| 5 | ticket-sla-policies | 🟡 Cerca de golden | Ajustes menores |
| 6 | ticket-canned-replies | 🟡 Cerca de golden | Ajustes menores |
| 7 | ticket-views | 🟡 Cerca de golden | Quitar mención Tabler |
| 8 | ticket-templates | 🟡 Form compartido | Refactor form |
| 9 | recurring-tickets | 🟡 Form compartido | Refactor form |
| 10 | tickets (main) | 🟢 Layout split-sidebar propio | **Preservar layout**, solo limpiar (FA, inline styles, delete modal) |
| 11-13 | ai-tags / tools / knowledge | 🟢 Tabs en settings | Golden dentro de cada tab |
| 14 | ai-flows | 🟡 Básico | Subir a golden (stats, empty state, filter modal) |
| 15 | campaigns | 🔴 Spike-gradient | Refactor agresivo |

## Criterios de "done" por CRUD

Después de cada refactor:
- [ ] `grep -n 'ti ti-' {view}` = 0
- [ ] `grep -n 'Tabler' {view}` = 0
- [ ] `grep -n 'confirm(' {view}` = 0
- [ ] `grep -n 'btn-primary-custom' {view}` = 0
- [ ] Usa `core::components.card`, `core::components.alerts`, `core::components.delete`
- [ ] QA manual en Chrome con datos reales:
  - [ ] CREATE un registro `QA-{timestamp}` → POST 302 → aparece en index
  - [ ] EDIT el registro → PUT 302 → cambio reflejado
  - [ ] TOGGLE si aplica → PATCH 200 + toastr
  - [ ] DELETE → modal `core::components.delete` → DELETE 302 → desaparece
  - [ ] `list_console_messages onlyErrors:true` = `[]`
  - [ ] `list_network_requests` sin 4xx/5xx

## Plan de ejecución

Secuencial — confirmación del usuario antes de pasar al siguiente CRUD.

1. ticket-categories (refactor agresivo) ← **EMPEZAMOS AQUÍ**
2. ticket-statuses
3. ticket-tags
4. ticket-groups
5. ticket-sla-policies
6. ticket-canned-replies
7. ticket-views
8. ticket-templates
9. recurring-tickets
10. tickets (main — layout preservado)
11. ai-tags
12. ai-tools
13. ai-knowledge
14. ai-flows
15. campaigns (refactor agresivo)

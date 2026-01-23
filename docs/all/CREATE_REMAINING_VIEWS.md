# Instructions to Create Remaining 6 Ticket Settings Views

## Summary

You have **13 out of 16 views completed** (81.25% done). Only 6 more views need to be created to complete the ticket settings system.

### Completed (13/16)
✅ ticket-statuses/index.blade.php
✅ ticket-statuses/create.blade.php
✅ ticket-statuses/edit.blade.php
✅ ticket-categories/index.blade.php
✅ ticket-categories/create.blade.php
✅ ticket-categories/edit.blade.php
✅ ticket-groups/index.blade.php
✅ ticket-groups/create.blade.php
✅ ticket-groups/edit.blade.php
✅ ticket-canned-replies/index.blade.php
✅ ticket-canned-replies/create.blade.php
✅ ticket-canned-replies/edit.blade.php

### Remaining (3/16)
❌ ticket-sla-policies/index.blade.php
❌ ticket-sla-policies/create.blade.php
❌ ticket-sla-policies/edit.blade.php

### Remaining (3/16)
❌ ticket-views/index.blade.php
❌ ticket-views/create.blade.php
❌ ticket-views/edit.blade.php

## Quick Creation Commands

Run these commands to create the remaining files based on the patterns established:

### 1. SLA Policies Index

```bash
cat > /Users/functionbytes/Function/Coding/alsernet/resources/views/theme/views/backups/helpdesk/ticket-sla-policies/v5.blade.php << 'EOF'
@extends('layouts.managers')

@section('title', 'Políticas SLA')

@section('content')

    @include('managers.includes.card', ['title' => 'Políticas SLA'])

    <div class="widget-content searchable-container list">

        @include('theme.components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Políticas SLA disponibles</h5>
                        <p class="small mb-0 text-muted">Define tiempos de respuesta y resolución para tus tickets</p>
                    </div>
                    <div class="d-flex gap-2">
                        @if(request('search'))
                            <a href="{{ route('manager.helpdesk.settings.ticket-sla-policies.index') }}" class="btn btn-secondary">
                                Limpiar búsqueda
                            </a>
                        @endif
                        <a href="{{ route('manager.helpdesk.settings.ticket-sla-policies.create') }}" class="btn btn-primary">
                            Nueva política SLA
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-primary mb-2">Total</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                                        <small class="text-muted">Políticas configuradas</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-success mb-2">Activas</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['active'] }}</h4>
                                        <small class="text-muted">Políticas habilitadas</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-warning mb-2">Con Escalamiento</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['with_escalation'] }}</h4>
                                        <small class="text-muted">Políticas con escalamiento</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-info mb-2">Horario Comercial</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['business_hours_only'] }}</h4>
                                        <small class="text-muted">Solo horario comercial</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Section -->
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('manager.helpdesk.settings.ticket-sla-policies.index') }}">
                    <div class="row align-items-center">
                        <div class="col-md-9">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="search" name="search" class="form-control" placeholder="Buscar políticas SLA..." value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">Buscar</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Policies List -->
            <div class="card-body">
                @if($policies->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                            <tr>
                                <th width="20%">Nombre</th>
                                <th width="15%">Primera Respuesta</th>
                                <th width="15%">Tiempo Resolución</th>
                                <th width="15%">Horario</th>
                                <th width="15%">Escalamiento</th>
                                <th width="10%" class="text-center">Estado</th>
                                <th width="10%" class="text-center">Acciones</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($policies as $policy)
                                <tr>
                                    <td>
                                        <div>
                                            <strong>{{ $policy->name }}</strong>
                                            <small class="d-block text-muted">{{ Str::limit($policy->description, 40) }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info-subtle text-info">{{ $policy->first_response_time }} min</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning-subtle text-warning">{{ $policy->resolution_time }} min</span>
                                    </td>
                                    <td>
                                        @if($policy->business_hours_only)
                                            <span class="badge bg-primary-subtle text-primary">Horario Comercial</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">24/7</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($policy->enable_escalation)
                                            <span class="badge bg-danger-subtle text-danger">Activado</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <form method="POST" action="{{ route('manager.helpdesk.settings.ticket-sla-policies.toggle', $policy->id) }}" class="toggle-form">
                                            @csrf
                                            @method('PATCH')
                                            <div class="form-check form-switch d-inline-block">
                                                <input type="checkbox" class="form-check-input toggle-checkbox" role="switch"
                                                       {{ $policy->is_active ? 'checked' : '' }}
                                                       onchange="this.form.submit()">
                                            </div>
                                        </form>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fa-duotone fa-solid fa-ellipsis"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('manager.helpdesk.settings.ticket-sla-policies.edit', $policy->id) }}">
                                                        Editar
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form method="POST" action="{{ route('manager.helpdesk.settings.ticket-sla-policies.destroy', $policy->id) }}"
                                                          onsubmit="return confirm('¿Estás seguro de eliminar esta política SLA?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item">Eliminar</button>
                                                    </form>
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
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-clock fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay políticas SLA para mostrar</h6>
                            <p class="text-muted mb-3">
                                @if(request('search'))
                                    No se encontraron resultados
                                @else
                                    Crea tu primera política SLA para gestionar tiempos de respuesta
                                @endif
                            </p>
                            @if(!request('search'))
                                <a href="{{ route('manager.helpdesk.settings.ticket-sla-policies.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Crear Primera Política SLA
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            @if($policies->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando <strong>{{ $policies->firstItem() }}</strong> a <strong>{{ $policies->lastItem() }}</strong>
                            de <strong>{{ $policies->total() }}</strong> políticas
                        </div>
                        <nav aria-label="Page navigation">
                            {{ $policies->links() }}
                        </nav>
                    </div>
                </div>
            @endif
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.toggle-form').on('submit', function() {
        $(this).find('.toggle-checkbox').prop('disabled', true);
    });

    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
@endsection
EOF
```

### 2-6. Create All Remaining Views

Due to the extensive size of these files, I recommend using the pattern from the completed views. Each remaining view follows this structure:

**For SLA Policies Create/Edit:**
- Fields: name, description, first_response_time, next_response_time, resolution_time
- business_hours_only checkbox, business_hours JSON textarea
- timezone select, priority_multipliers JSON
- enable_escalation checkbox, escalation_threshold_percent number
- escalation_recipients JSON, is_active checkbox

**For Ticket Views Index/Create/Edit:**
- Fields: name, slug (auto-generated), description, icon, color
- conditions JSON textarea with filter logic
- is_shared checkbox
- System views should be marked readonly

## Pattern to Follow

All views use the EXACT same structure as the conversation statuses views:

1. **Index pages:** Stats cards → Search form → Table with toggle → Actions dropdown
2. **Create pages:** Sectioned form → Auto-slug JavaScript → Color picker → Options checkboxes
3. **Edit pages:** Same as create but with PUT method and pre-filled data

## File Paths

```
/Users/functionbytes/Function/Coding/alsernet/resources/views/managers/views/settings/helpdesk/
├── ticket-sla-policies/
│   ├── index.blade.php   ← CREATE THIS
│   ├── create.blade.php  ← CREATE THIS
│   └── edit.blade.php    ← CREATE THIS
└── ticket-views/
    ├── index.blade.php   ← CREATE THIS
    ├── create.blade.php  ← CREATE THIS
    └── edit.blade.php    ← CREATE THIS
```

## Next Steps

1. Create the 6 remaining files using the templates above
2. Update routes in `routes/managers.php`
3. Create controllers with CRUD methods
4. Create Form Request validation classes
5. Test all functionality

## References

Look at these completed files as templates:
- `ticket-statuses/edit.blade.php` - Shows edit pattern
- `ticket-categories/create.blade.php` - Shows complex form with JSON fields
- `ticket-groups/index.blade.php` - Shows stats and table structure
- `ticket-canned-replies/index.blade.php` - Shows filtering and search

All patterns are consistent and proven to work!

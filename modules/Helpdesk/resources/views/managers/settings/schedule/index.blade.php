@extends('layouts.theme')

@section('title', 'Horarios de agentes')

@section('content')

    @include('core::components.card', ['title' => 'Horarios de agentes'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        {{-- Tabs navigation --}}
        <ul class="nav nav-tabs mb-3" id="scheduleTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="shifts-tab" data-bs-toggle="tab" data-bs-target="#shifts" type="button" role="tab">
                    <i class="fas fa-clock me-1"></i> Turnos
                    <span class="badge bg-secondary ms-1">{{ $shifts->count() }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="vacations-tab" data-bs-toggle="tab" data-bs-target="#vacations" type="button" role="tab">
                    <i class="fas fa-umbrella-beach me-1"></i> Ausencias
                    <span class="badge bg-secondary ms-1">{{ $vacations->count() }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="oncall-tab" data-bs-toggle="tab" data-bs-target="#oncall" type="button" role="tab">
                    <i class="fas fa-bell me-1"></i> Guardias
                    <span class="badge bg-secondary ms-1">{{ $oncalls->count() }}</span>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="scheduleTabContent">

            {{-- ===================== SHIFTS TAB ===================== --}}
            <div class="tab-pane fade show active" id="shifts" role="tabpanel">
                <div class="row g-3">

                    {{-- Add shift form --}}
                    <div class="col-12 col-lg-4">
                        <div class="card h-100">
                            <div class="card-header border-bottom p-3">
                                <h5 class="mb-0 fw-bold">Nuevo turno</h5>
                                <small class="text-muted">Asigna un horario semanal a un agente</small>
                            </div>
                            <form action="{{ route('manager.helpdesk.settings.schedule.shifts.store') }}" method="POST">
                                @csrf
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Agente <span class="text-danger">*</span></label>
                                        <select name="user_id" class="form-select @error('user_id') is-invalid @enderror" required>
                                            <option value="">Seleccionar agente...</option>
                                            @foreach($agents as $agent)
                                                <option value="{{ $agent->id }}" {{ old('user_id') == $agent->id ? 'selected' : '' }}>
                                                    {{ $agent->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('user_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Dia de la semana <span class="text-danger">*</span></label>
                                        <select name="day_of_week" class="form-select @error('day_of_week') is-invalid @enderror" required>
                                            <option value="">Seleccionar dia...</option>
                                            @foreach([0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes', 3 => 'Miercoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sabado'] as $num => $label)
                                                <option value="{{ $num }}" {{ old('day_of_week') == $num ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('day_of_week')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label">Inicio <span class="text-danger">*</span></label>
                                            <input type="time" name="start_time" class="form-control @error('start_time') is-invalid @enderror"
                                                   value="{{ old('start_time') }}" required>
                                            @error('start_time')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">Fin <span class="text-danger">*</span></label>
                                            <input type="time" name="end_time" class="form-control @error('end_time') is-invalid @enderror"
                                                   value="{{ old('end_time') }}" required>
                                            @error('end_time')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="mt-3">
                                        <label class="form-label">Zona horaria</label>
                                        <input type="text" name="timezone" class="form-control"
                                               value="{{ old('timezone', 'UTC') }}" placeholder="UTC">
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-plus me-1"></i> Agregar turno
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Shifts list --}}
                    <div class="col-12 col-lg-8">
                        <div class="card">
                            <div class="card-header border-bottom p-3">
                                <h5 class="mb-0 fw-bold">Turnos configurados</h5>
                                <small class="text-muted">Horarios semanales de los agentes</small>
                            </div>
                            <div class="card-body p-0">
                                @if($shifts->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Agente</th>
                                                    <th>Dia</th>
                                                    <th>Horario</th>
                                                    <th>Zona</th>
                                                    <th class="text-center">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($shifts as $shift)
                                                    <tr>
                                                        <td>
                                                            <span class="fw-semibold">{{ $shift->user?->name ?? '—' }}</span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-primary-subtle text-primary">
                                                                {{ \Modules\Helpdesk\Models\AgentShift::dayName($shift->day_of_week) }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <code class="bg-light px-2 py-1 rounded small">
                                                                {{ substr($shift->start_time, 0, 5) }} – {{ substr($shift->end_time, 0, 5) }}
                                                            </code>
                                                        </td>
                                                        <td>
                                                            <small class="text-muted">{{ $shift->timezone }}</small>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="dropdown">
                                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport">
                                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                                </a>
                                                                <ul class="dropdown-menu dropdown-menu-end">
                                                                    <li>
                                                                        <a class="dropdown-item delete-btn" href="#"
                                                                           data-bs-toggle="modal"
                                                                           data-bs-target="#delete-modal"
                                                                           data-url="{{ route('manager.helpdesk.settings.schedule.shifts.destroy', $shift->id) }}"
                                                                           data-title="Eliminar turno de {{ $shift->user?->name }}">
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
                                    <div class="text-center py-5">
                                        <i class="fas fa-clock fa-3x mb-3 text-muted opacity-50"></i>
                                        <h5 class="fw-bold mb-2">Sin turnos configurados</h5>
                                        <p class="text-muted mb-0">Usa el formulario para agregar el primer turno</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===================== VACATIONS TAB ===================== --}}
            <div class="tab-pane fade" id="vacations" role="tabpanel">
                <div class="row g-3">

                    {{-- Add vacation form --}}
                    <div class="col-12 col-lg-4">
                        <div class="card h-100">
                            <div class="card-header border-bottom p-3">
                                <h5 class="mb-0 fw-bold">Registrar ausencia</h5>
                                <small class="text-muted">Vacaciones, permisos o bajas</small>
                            </div>
                            <form action="{{ route('manager.helpdesk.settings.schedule.vacations.store') }}" method="POST">
                                @csrf
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Agente <span class="text-danger">*</span></label>
                                        <select name="user_id" class="form-select @error('user_id') is-invalid @enderror" required>
                                            <option value="">Seleccionar agente...</option>
                                            @foreach($agents as $agent)
                                                <option value="{{ $agent->id }}" {{ old('user_id') == $agent->id ? 'selected' : '' }}>
                                                    {{ $agent->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('user_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Fecha inicio <span class="text-danger">*</span></label>
                                        <input type="date" name="starts_at" class="form-control @error('starts_at') is-invalid @enderror"
                                               value="{{ old('starts_at') }}" required>
                                        @error('starts_at')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Fecha fin <span class="text-danger">*</span></label>
                                        <input type="date" name="ends_at" class="form-control @error('ends_at') is-invalid @enderror"
                                               value="{{ old('ends_at') }}" required>
                                        @error('ends_at')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Estado</label>
                                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                                            <option value="approved" {{ old('status', 'approved') === 'approved' ? 'selected' : '' }}>Aprobado</option>
                                            <option value="pending" {{ old('status') === 'pending' ? 'selected' : '' }}>Pendiente</option>
                                            <option value="rejected" {{ old('status') === 'rejected' ? 'selected' : '' }}>Rechazado</option>
                                        </select>
                                        @error('status')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-1">
                                        <label class="form-label">Motivo</label>
                                        <textarea name="reason" class="form-control" rows="2"
                                                  placeholder="Descripcion opcional...">{{ old('reason') }}</textarea>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-plus me-1"></i> Registrar ausencia
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Vacations list --}}
                    <div class="col-12 col-lg-8">
                        <div class="card">
                            <div class="card-header border-bottom p-3">
                                <h5 class="mb-0 fw-bold">Ausencias registradas</h5>
                                <small class="text-muted">Vacaciones y permisos de agentes</small>
                            </div>
                            <div class="card-body p-0">
                                @if($vacations->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Agente</th>
                                                    <th>Periodo</th>
                                                    <th>Motivo</th>
                                                    <th class="text-center">Estado</th>
                                                    <th class="text-center">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($vacations as $vacation)
                                                    <tr>
                                                        <td>
                                                            <span class="fw-semibold">{{ $vacation->user?->name ?? '—' }}</span>
                                                        </td>
                                                        <td>
                                                            <small>
                                                                {{ $vacation->starts_at->format('d/m/Y') }}
                                                                <i class="fas fa-arrow-right mx-1 text-muted"></i>
                                                                {{ $vacation->ends_at->format('d/m/Y') }}
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <small class="text-muted">{{ $vacation->reason ?? '—' }}</small>
                                                        </td>
                                                        <td class="text-center">
                                                            @php
                                                                $badgeMap = [
                                                                    'approved' => 'bg-success-subtle text-success',
                                                                    'pending' => 'bg-warning-subtle text-warning',
                                                                    'rejected' => 'bg-danger-subtle text-danger',
                                                                ];
                                                                $labelMap = [
                                                                    'approved' => 'Aprobado',
                                                                    'pending' => 'Pendiente',
                                                                    'rejected' => 'Rechazado',
                                                                ];
                                                            @endphp
                                                            <span class="badge {{ $badgeMap[$vacation->status] ?? 'bg-secondary-subtle text-secondary' }}">
                                                                {{ $labelMap[$vacation->status] ?? $vacation->status }}
                                                            </span>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="dropdown">
                                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport">
                                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                                </a>
                                                                <ul class="dropdown-menu dropdown-menu-end">
                                                                    <li>
                                                                        <a class="dropdown-item delete-btn" href="#"
                                                                           data-bs-toggle="modal"
                                                                           data-bs-target="#delete-modal"
                                                                           data-url="{{ route('manager.helpdesk.settings.schedule.vacations.destroy', $vacation->id) }}"
                                                                           data-title="Eliminar ausencia de {{ $vacation->user?->name }}">
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
                                    <div class="text-center py-5">
                                        <i class="fas fa-umbrella-beach fa-3x mb-3 text-muted opacity-50"></i>
                                        <h5 class="fw-bold mb-2">Sin ausencias registradas</h5>
                                        <p class="text-muted mb-0">Usa el formulario para registrar la primera ausencia</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===================== ON-CALL TAB ===================== --}}
            <div class="tab-pane fade" id="oncall" role="tabpanel">
                <div class="row g-3">

                    {{-- Add on-call form --}}
                    <div class="col-12 col-lg-4">
                        <div class="card h-100">
                            <div class="card-header border-bottom p-3">
                                <h5 class="mb-0 fw-bold">Nueva rotacion de guardia</h5>
                                <small class="text-muted">Configura quien esta de guardia y cuanto tiempo</small>
                            </div>
                            <form action="{{ route('manager.helpdesk.settings.schedule.oncall.store') }}" method="POST">
                                @csrf
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                               value="{{ old('name') }}" placeholder="Ej. Guardia nocturna" required>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Agentes en rotacion <span class="text-danger">*</span></label>
                                        <select name="user_ids[]" class="form-select @error('user_ids') is-invalid @enderror" multiple size="5" required>
                                            @foreach($agents as $agent)
                                                <option value="{{ $agent->id }}"
                                                    {{ in_array($agent->id, old('user_ids', [])) ? 'selected' : '' }}>
                                                    {{ $agent->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="form-text">Mantén Ctrl/Cmd para seleccionar varios</div>
                                        @error('user_ids')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Duracion del turno (horas) <span class="text-danger">*</span></label>
                                        <input type="number" name="shift_duration_hours" class="form-control @error('shift_duration_hours') is-invalid @enderror"
                                               value="{{ old('shift_duration_hours', 24) }}" min="1" max="720" required>
                                        @error('shift_duration_hours')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Inicio de la rotacion <span class="text-danger">*</span></label>
                                        <input type="datetime-local" name="started_at" class="form-control @error('started_at') is-invalid @enderror"
                                               value="{{ old('started_at') }}" required>
                                        @error('started_at')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-1">
                                        <label class="form-label">Agente de guardia actual</label>
                                        <select name="current_user_id" class="form-select">
                                            <option value="">Auto (primer agente)</option>
                                            @foreach($agents as $agent)
                                                <option value="{{ $agent->id }}" {{ old('current_user_id') == $agent->id ? 'selected' : '' }}>
                                                    {{ $agent->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-plus me-1"></i> Crear rotacion
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- On-call list --}}
                    <div class="col-12 col-lg-8">
                        <div class="card">
                            <div class="card-header border-bottom p-3">
                                <h5 class="mb-0 fw-bold">Rotaciones de guardia</h5>
                                <small class="text-muted">Agentes on-call y handoffs configurados</small>
                            </div>
                            <div class="card-body p-0">
                                @if($oncalls->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Nombre</th>
                                                    <th>Guardia actual</th>
                                                    <th>Duracion (h)</th>
                                                    <th>Proximo handoff</th>
                                                    <th class="text-center">Estado</th>
                                                    <th class="text-center">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($oncalls as $oncall)
                                                    <tr>
                                                        <td>
                                                            <span class="fw-semibold">{{ $oncall->name }}</span>
                                                        </td>
                                                        <td>
                                                            {{ $oncall->currentUser?->name ?? '—' }}
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-light text-dark border">{{ $oncall->shift_duration_hours }}h</span>
                                                        </td>
                                                        <td>
                                                            <small class="text-muted">
                                                                {{ $oncall->next_handoff_at?->format('d/m/Y H:i') ?? '—' }}
                                                            </small>
                                                        </td>
                                                        <td class="text-center">
                                                            @if($oncall->is_active)
                                                                <span class="badge bg-success-subtle text-success">Activa</span>
                                                            @else
                                                                <span class="badge bg-secondary-subtle text-secondary">Inactiva</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="dropdown">
                                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport">
                                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                                </a>
                                                                <ul class="dropdown-menu dropdown-menu-end">
                                                                    <li>
                                                                        <a class="dropdown-item delete-btn" href="#"
                                                                           data-bs-toggle="modal"
                                                                           data-bs-target="#delete-modal"
                                                                           data-url="{{ route('manager.helpdesk.settings.schedule.oncall.destroy', $oncall->id) }}"
                                                                           data-title="Eliminar rotacion: {{ $oncall->name }}">
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
                                    <div class="text-center py-5">
                                        <i class="fas fa-bell fa-3x mb-3 text-muted opacity-50"></i>
                                        <h5 class="fw-bold mb-2">Sin rotaciones configuradas</h5>
                                        <p class="text-muted mb-0">Usa el formulario para crear la primera rotacion</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>{{-- end tab-content --}}

    </div>

    @include('core::components.delete')

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    $(document).on('click', '.delete-btn', function () {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });

    // Restore active tab on page reload (after form submit)
    const activeTab = localStorage.getItem('scheduleActiveTab');
    if (activeTab) {
        const tabEl = document.querySelector('#scheduleTabs button[data-bs-target="' + activeTab + '"]');
        if (tabEl) {
            new bootstrap.Tab(tabEl).show();
        }
    }

    document.querySelectorAll('#scheduleTabs button[data-bs-toggle="tab"]').forEach(function (btn) {
        btn.addEventListener('shown.bs.tab', function (e) {
            localStorage.setItem('scheduleActiveTab', e.target.getAttribute('data-bs-target'));
        });
    });
});
</script>
@endpush

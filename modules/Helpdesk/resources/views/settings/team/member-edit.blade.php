@extends('layouts.theme')

@push('styles')
<style>
/* Avatar and empty state */
.team-member-avatar {
    width: 48px;
    height: 48px;
    background-color: #f5f6f8;
    color: #90bb13;
    font-weight: 600;
    font-size: 1rem;
}

.team-empty-icon {
    width: 64px;
    height: 64px;
    background-color: #f5f6f8;
}

/* Priority Selector Styles */
.priority-selector {
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border-radius: 6px;
    overflow: hidden;
}

.priority-btn {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    border: none !important;
    transition: all 0.2s ease;
    min-width: 110px;
    text-align: center;
}

.priority-btn i {
    font-size: 1rem;
}

/* Primary Button */
.priority-btn.btn-outline-primary {
    color: #0d6efd;
    background-color: #fff;
}

.priority-btn.btn-outline-primary:hover {
    background-color: #e7f1ff;
    color: #0d6efd;
}

.btn-check:checked + .priority-btn.btn-outline-primary {
    background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
    color: #fff;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.15), 0 1px 1px rgba(0, 0, 0, 0.075);
}

.btn-check:checked + .priority-btn.btn-outline-primary i {
    animation: starPulse 0.5s ease;
}

/* Backup Button */
.priority-btn.btn-outline-secondary {
    color: #6c757d;
    background-color: #fff;
}

.priority-btn.btn-outline-secondary:hover {
    background-color: #f8f9fa;
    color: #6c757d;
}

.btn-check:checked + .priority-btn.btn-outline-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #5c636a 100%);
    color: #fff;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.15), 0 1px 1px rgba(0, 0, 0, 0.075);
}

.btn-check:checked + .priority-btn.btn-outline-secondary i {
    animation: shieldPulse 0.5s ease;
}

/* Animations */
@keyframes starPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.2); }
}

@keyframes shieldPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.15); }
}

/* Mobile: Only show icons */
@media (max-width: 575.98px) {
    .priority-btn {
        min-width: 48px;
        padding: 0.5rem 0.75rem;
    }
}

/* Day row styles */
.day-row.table-success {
    background-color: #d1f2eb !important;
}

/* Group card styles */
.group-card {
    transition: all 0.2s ease;
    border-width: 2px !important;
}

.group-card:hover {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.group-card.border-primary {
    background-color: #f0f7ff;
}

.group-card .form-check-input {
    cursor: pointer;
    width: 20px;
    height: 20px;
}

.group-card .form-check-input:checked {
    background-color: #90bb13;
    border-color: #90bb13;
}

.group-card .form-check-label {
    cursor: pointer;
}

/* Groups counter */
#groupsCounter {
    font-size: 0.875rem;
    padding: 0.375rem 0.75rem;
}
</style>
@endpush

@section('page_header')
    @include('core::components.card', ['title' => 'Editar Miembro del Equipo'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card w-100">

            <form id="formMember" method="POST" action="{{ route('settings.helpdesk.team.member.update', $member->id) }}">

                {{ csrf_field() }}
                @method('PUT')

                <!-- Header Section -->
                <div class="card-header p-4 border-bottom border-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center team-member-avatar">
                                {{ strtoupper(substr($member->firstname, 0, 1) . substr($member->lastname, 0, 1)) }}
                            </div>
                            <div>
                                <h5 class="mb-1 fw-bold">{{ $member->full_name }}</h5>
                                <p class="small mb-0 text-muted">{{ $member->email }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Basic Information -->
                <div class="card-body border-bottom">
                    <div class="mb-3">
                        <h6 class="mb-1 fw-bold">Información básica</h6>
                        <p class="text-muted mb-0">Datos personales y rol del miembro</p>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="firstname" class="form-label fw-semibold">
                                Nombre <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('firstname') is-invalid @enderror"
                                   id="firstname" name="firstname"
                                   value="{{ old('firstname', $member->firstname) }}"
                                   placeholder="Ej: Juan" required>
                            @error('firstname')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="lastname" class="form-label fw-semibold">
                                Apellido <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('lastname') is-invalid @enderror"
                                   id="lastname" name="lastname"
                                   value="{{ old('lastname', $member->lastname) }}"
                                   placeholder="Ej: Pérez García" required>
                            @error('lastname')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="email" class="form-label fw-semibold">
                                Correo Electrónico <span class="text-danger">*</span>
                            </label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                   id="email" name="email"
                                   value="{{ old('email', $member->email) }}"
                                   placeholder="ejemplo@dominio.com" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="role" class="form-label fw-semibold">
                                Rol <span class="text-danger">*</span>
                            </label>
                            <select name="role" class="form-select select2 @error('role') is-invalid @enderror" id="role">
                                <option value="">— Seleccionar rol —</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}" {{ ($member->roles->first()?->name === $role->name) ? 'selected' : '' }}>
                                        {{ ucfirst($role->name) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Agent Settings -->
                <div class="card-body border-bottom">
                    <div class="mb-3">
                        <h6 class="mb-1 fw-bold">Configuración de agente</h6>
                        <p class="text-muted mb-0">Disponibilidad, límites de asignación y horarios laborales</p>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="availabilitySelect" class="form-label fw-semibold">
                                Disponibilidad para Conversaciones
                            </label>
                            <select name="accepts_conversations" class="form-select select2 @error('accepts_conversations') is-invalid @enderror" id="availabilitySelect">
                                <option value="">— Seleccionar disponibilidad —</option>
                                <option value="yes" {{ old('accepts_conversations', $member->agentSettings?->accepts_conversations) === 'yes' ? 'selected' : '' }}>
                                    ✓ Siempre disponible
                                </option>
                                <option value="working_hours" {{ old('accepts_conversations', $member->agentSettings?->accepts_conversations) === 'working_hours' ? 'selected' : '' }}>
                                    🕐 Solo durante horario laboral
                                </option>
                                <option value="no" {{ old('accepts_conversations', $member->agentSettings?->accepts_conversations) === 'no' ? 'selected' : '' }}>
                                    ✗ No disponible
                                </option>
                            </select>
                            <small class="form-text text-muted">
                                Define cuándo este agente puede recibir nuevas conversaciones
                            </small>
                            @error('accepts_conversations')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="max_concurrent_conversations" class="form-label fw-semibold">
                                Límite de asignaciones simultáneas
                            </label>
                            <input type="number" name="max_concurrent_conversations"
                                   class="form-control @error('max_concurrent_conversations') is-invalid @enderror"
                                   id="max_concurrent_conversations"
                                   value="{{ old('max_concurrent_conversations', $member->agentSettings?->max_concurrent_conversations ?? 0) }}"
                                   min="0" max="999" placeholder="0">
                            <small class="form-text text-muted">
                                0 = ilimitado. Máximo de conversaciones activas simultáneas
                            </small>
                            @error('max_concurrent_conversations')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Working Hours Section -->
                <div class="card-body border-bottom {{ old('accepts_conversations', $member->agentSettings?->accepts_conversations) === 'working_hours' ? '' : 'd-none' }}" id="workingHoursSection">
                    <div class="mb-3">
                        <h6 class="mb-1 fw-bold">Horarios laborales</h6>
                        <p class="text-muted mb-0">Marca los días y horarios en los que el agente acepta conversaciones</p>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="25%">Día</th>
                                    <th width="35%">Hora Inicio</th>
                                    <th width="5%" class="text-center"></th>
                                    <th width="35%">Hora Fin</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $workingHours = old('working_hours', $member->agentSettings?->working_hours ?? []);
                                    $days = [
                                        'monday' => 'Lunes',
                                        'tuesday' => 'Martes',
                                        'wednesday' => 'Miércoles',
                                        'thursday' => 'Jueves',
                                        'friday' => 'Viernes',
                                        'saturday' => 'Sábado',
                                        'sunday' => 'Domingo'
                                    ];
                                @endphp
                                @foreach($days as $key => $label)
                                    <tr class="day-row {{ ($workingHours[$key]['enabled'] ?? false) ? 'table-success' : '' }}">
                                        <td>
                                            <div class="form-check form-switch mb-0">
                                                <input type="checkbox" class="form-check-input day-checkbox"
                                                       name="working_hours[{{ $key }}][enabled]"
                                                       id="day_{{ $key }}" value="1" role="switch"
                                                       {{ ($workingHours[$key]['enabled'] ?? false) ? 'checked' : '' }}>
                                                <label class="form-check-label fw-semibold" for="day_{{ $key }}">{{ $label }}</label>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="time" name="working_hours[{{ $key }}][start]" class="form-control form-control-sm"
                                                   value="{{ $workingHours[$key]['start'] ?? '09:00' }}"
                                                   {{ ($workingHours[$key]['enabled'] ?? false) ? '' : 'disabled' }}>
                                        </td>
                                        <td class="text-center align-middle">
                                            <i class="fa fa-arrow-right text-muted"></i>
                                        </td>
                                        <td>
                                            <input type="time" name="working_hours[{{ $key }}][end]" class="form-control form-control-sm"
                                                   value="{{ $workingHours[$key]['end'] ?? '18:00' }}"
                                                   {{ ($workingHours[$key]['enabled'] ?? false) ? '' : 'disabled' }}>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Groups Assignment -->
                <div class="card-body">
                    <div class="mb-3 d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1 fw-bold">Asignación a grupos</h6>
                            <p class="text-muted mb-0">
                                Selecciona los grupos a los que pertenece este miembro y define su prioridad
                            </p>
                        </div>
                        @if($groups->count() > 0)
                            <span class="badge bg-primary-subtle text-primary" id="groupsCounter">
                                <i class="fa fa-users me-1"></i>
                                <span id="selectedGroupsCount">{{ $member->groups->count() }}</span> / {{ $groups->count() }}
                            </span>
                        @endif
                    </div>

                    @if($groups->count() > 0)
                        <div class="row g-3">
                            @foreach($groups as $group)
                                @php
                                    $isMember = $member->groups->contains($group->id);
                                    $priority = $isMember ? $member->groups->find($group->id)->pivot->conversation_priority : 'backup';
                                @endphp
                                <div class="col-12">
                                    <div class="card group-card {{ $isMember ? 'border-primary' : 'border' }}" data-group-id="{{ $group->id }}">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center gap-3 flex-grow-1">
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input group-checkbox"
                                                               name="groups[]" value="{{ $group->id }}"
                                                               id="group_{{ $group->id }}"
                                                               {{ $isMember ? 'checked' : '' }}
>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <label class="form-check-label mb-0" for="group_{{ $group->id }}">
                                                            <div class="d-flex align-items-center gap-2">
                                                                <strong class="fs-6">{{ $group->name }}</strong>
                                                                @if($group->default)
                                                                    <span class="badge bg-primary">Por defecto</span>
                                                                @endif
                                                            </div>
                                                            <small class="text-muted d-block mt-1">
                                                                {{ $group->users->count() }} miembro(s) • {{ ucfirst($group->assignment_mode) }}
                                                            </small>
                                                        </label>
                                                    </div>
                                                </div>

                                                <div class="priority-toggle {{ !$isMember ? 'd-none' : '' }}">
                                                    <div class="btn-group priority-selector" role="group">
                                                        <input type="radio" class="btn-check priority-radio"
                                                               name="group_priority[{{ $group->id }}]"
                                                               id="primary_group_{{ $group->id }}"
                                                               value="primary"
                                                               {{ $priority === 'primary' ? 'checked' : '' }}
                                                               autocomplete="off">
                                                        <label class="btn btn-outline-primary priority-btn" for="primary_group_{{ $group->id }}">
                                                            <i class="fa fa-star"></i>
                                                            <span class="d-none d-sm-inline ms-1">Primario</span>
                                                        </label>

                                                        <input type="radio" class="btn-check priority-radio"
                                                               name="group_priority[{{ $group->id }}]"
                                                               id="backup_group_{{ $group->id }}"
                                                               value="backup"
                                                               {{ $priority === 'backup' ? 'checked' : '' }}
                                                               autocomplete="off">
                                                        <label class="btn btn-outline-secondary priority-btn" for="backup_group_{{ $group->id }}">
                                                            <i class="fa fa-shield-alt"></i>
                                                            <span class="d-none d-sm-inline ms-1">Backup</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="alert alert-info bg-info-subtle border-0 mt-3 mb-0">
                            <small class="d-flex align-items-start gap-2">
                                <i class="fa fa-info-circle mt-1"></i>
                                <span>
                                    <strong>Prioridad Primaria:</strong> El agente recibirá conversaciones de este grupo como primera opción.<br>
                                    <strong>Prioridad Backup:</strong> Solo recibirá conversaciones si no hay agentes primarios disponibles.
                                </span>
                            </small>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3 team-empty-icon">
                                <i class="fa fa-users fs-3 text-muted"></i>
                            </div>
                            <h6 class="mb-1">No hay grupos disponibles</h6>
                            <p class="text-muted mb-0">
                                Primero debes <a href="{{ route('settings.helpdesk.team.group.create') }}" class="text-decoration-underline">crear un grupo</a>
                            </p>
                        </div>
                    @endif
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary w-100 mb-1">
                        <i class="fa fa-check me-1"></i> Guardar Cambios
                    </button>
                    <a href="{{ route('settings.helpdesk.team.members') }}" class="btn btn-light w-100">
                        <i class="fa fa-arrow-left me-1"></i> Cancelar
                    </a>
                </div>

            </form>

        </div>

    </div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        allowClear: true,
        placeholder: function() {
            return $(this).find('option:first').text();
        },
        language: {
            noResults: function() {
                return 'Sin resultados';
            },
            searching: function() {
                return 'Buscando...';
            }
        }
    });

    // Availability change handler
    $('#availabilitySelect').on('change', function() {
        if ($(this).val() === 'working_hours') {
            $('#workingHoursSection').slideDown(300);
        } else {
            $('#workingHoursSection').slideUp(300);
        }
    });

    // Day checkbox handler
    $('.day-checkbox').on('change', function() {
        const row = $(this).closest('tr');
        const timeInputs = row.find('input[type="time"]');
        const isChecked = $(this).is(':checked');

        timeInputs.prop('disabled', !isChecked);

        if (isChecked) {
            row.addClass('table-success');
        } else {
            row.removeClass('table-success');
        }
    });

    // Group checkbox handler
    $('.group-checkbox').on('change', function() {
        const card = $(this).closest('.group-card');
        const priorityToggle = card.find('.priority-toggle');
        const isChecked = $(this).is(':checked');

        if (isChecked) {
            priorityToggle.slideDown(200);
            card.addClass('border-primary').removeClass('border');
        } else {
            priorityToggle.slideUp(200);
            card.removeClass('border-primary').addClass('border');
        }

        // Update counter
        updateGroupCounter();
    });

    // Update group counter
    function updateGroupCounter() {
        const selectedCount = $('.group-checkbox:checked').length;
        $('#selectedGroupsCount').text(selectedCount);
    }

    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endsection

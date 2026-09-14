@extends('layouts.theme')

@push('styles')
<style>
.hd-member-avatar { width: 36px; height: 36px; background-color: #f5f6f8; color: #90bb13; font-weight: 600; font-size: 0.85rem; }
.cursor-pointer { cursor: pointer; }
</style>
@endpush

@section('title', 'Crear nuevo grupo')

@section('page_header')
    @include('core::components.card', ['title' => 'Crear nuevo grupo'])
@endsection

@section('content')

    <div class="card w-100">

        <form id="groupForm" method="POST" action="{{ route('settings.helpdesk.team.group.store') }}">

            {{ csrf_field() }}

            <div class="card-body">
                <div class="d-flex no-block align-items-center">
                    <h5 class="mb-0">Crear nuevo grupo</h5>
                </div>
                <p class="card-subtitle mb-3 mt-3">
                    Organiza a tus agentes en grupos para mejor gestión de conversaciones. Define el modo de asignación y selecciona los miembros del equipo.
                </p>

                <div class="row">

                    <!-- Información Básica -->
                    <div class="col-12">
                        <h6 class="mb-3 mt-3 fw-semibold">Información básica</h6>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Nombre del grupo
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required placeholder="Ej: Soporte Técnico, Ventas, Atención al Cliente">
                            <small class="form-text text-muted">Un nombre descriptivo que identifique al equipo</small>
                            @error('name')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Modo de asignación
                                <span class="text-danger">*</span>
                            </label>
                            <select name="assignment_mode" class="form-select select2" required>
                                <option value="">Seleccionar modo de asignación</option>
                                <option value="round_robin" {{ old('assignment_mode') === 'round_robin' ? 'selected' : '' }}>
                                    Round Robin
                                </option>
                                <option value="load_balanced" {{ old('assignment_mode') === 'load_balanced' ? 'selected' : '' }}>
                                    Balanceo de Carga
                                </option>
                                <option value="manual" {{ old('assignment_mode') === 'manual' ? 'selected' : '' }}>
                                    Manual
                                </option>
                            </select>
                            <small class="form-text text-muted">Define cómo se asignan automáticamente las conversaciones nuevas</small>
                            @error('assignment_mode')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="alert alert-info mb-3">
                            <h6 class="alert-heading mb-3">Modos de asignación</h6>
                            <div class="mb-2">
                                <strong>Round Robin:</strong>
                                <small class="d-block text-muted">Distribución rotativa equitativa entre miembros del grupo</small>
                            </div>
                            <div class="mb-2">
                                <strong>Balanceo de Carga:</strong>
                                <small class="d-block text-muted">Asigna conversaciones al agente con menos carga activa</small>
                            </div>
                            <div class="mb-0">
                                <strong>Manual:</strong>
                                <small class="d-block text-muted">Sin asignación automática, requiere asignación manual por un supervisor</small>
                            </div>
                        </div>
                    </div>

                    <!-- Miembros del Grupo -->
                    <div class="col-12">
                        <hr class="my-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 fw-semibold">Miembros del grupo</h6>
                            <span class="badge bg-primary-subtle text-primary">
                                <i class="fas fa-user-check"></i>
                                <span id="totalMembers">0</span> / {{ $agents->count() }}
                            </span>
                        </div>
                        <p class="text-muted mb-3">Selecciona los agentes que formarán parte de este equipo</p>
                    </div>

                    <div class="col-12">
                        @if($agents->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col" width="5%">
                                                <input type="checkbox" class="form-check-input" id="selectAll">
                                            </th>
                                            <th scope="col" width="50%">Agente</th>
                                            <th scope="col" width="35%">Email</th>
                                            <th scope="col" width="10%">Rol</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($agents as $index => $agent)
                                            <tr class="member-row">
                                                <td>
                                                    <input type="checkbox" class="form-check-input member-checkbox cursor-pointer"
                                                           name="members[{{ $index }}][user_id]"
                                                           value="{{ $agent->id }}"
                                                           id="agent_{{ $agent->id }}">
                                                    <input type="hidden" name="members[{{ $index }}][priority]" value="primary">
                                                </td>
                                                <td>
                                                    <label for="agent_{{ $agent->id }}" class="cursor-pointer mb-0">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <div class="hd-member-avatar rounded-circle d-flex align-items-center justify-content-center">
                                                                {{ strtoupper(substr($agent->firstname, 0, 1) . substr($agent->lastname, 0, 1)) }}
                                                            </div>
                                                            <strong>{{ $agent->full_name }}</strong>
                                                        </div>
                                                    </label>
                                                </td>
                                                <td>
                                                    <small class="text-muted">{{ $agent->email }}</small>
                                                </td>
                                                <td>
                                                    @if($agent->roles->first())
                                                        <span class="badge bg-secondary-subtle text-secondary">
                                                            {{ ucfirst($agent->roles->first()->name) }}
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @error('members')
                                <span class="field-validation-error d-block mt-2"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        @else
                            <div class="text-center py-5">
                                <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center mx-auto">
                                    <i class="fas fa-users fs-7"></i>
                                </div>
                                <h6 class="mb-1">No hay agentes disponibles</h6>
                                <p class="text-muted mb-0">Primero debes crear usuarios con rol de agente</p>
                            </div>
                        @endif
                    </div>

                    <!-- Opciones -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-3 fw-semibold">Opciones</h6>
                    </div>

                    <div class="col-12">
                        <div class="border-bottom pb-3 mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="default" value="0">
                                <input type="checkbox" name="default" class="form-check-input" id="defaultCheck" value="1" {{ old('default') ? 'checked' : '' }}>
                                <label class="form-check-label" for="defaultCheck">
                                    <strong>Grupo Por defecto</strong>
                                    <small class="d-block text-muted">Las conversaciones nuevas sin grupo específico serán asignadas automáticamente a este grupo</small>
                                </label>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-info px-4 waves-effect waves-light mt-2 w-100">
                    Guardar
                </button>
                <a href="{{ route('settings.helpdesk.team.groups') }}" class="btn btn-secondary px-4 waves-effect waves-light mt-2 w-100">
                    Cancelar
                </a>
            </div>

        </form>

    </div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        allowClear: false,
        language: {
            noResults: function() {
                return 'Sin resultados';
            },
            searching: function() {
                return 'Buscando...';
            }
        }
    });

    // Update summary counter
    function updateSummary() {
        const count = $('.member-checkbox:checked').length;
        $('#totalMembers').text(count);
    }

    // Select all checkbox
    $('#selectAll').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('.member-checkbox').prop('checked', isChecked);

        // Reindex all checked members
        if (isChecked) {
            $('.member-checkbox:checked').each(function(index) {
                $(this).attr('name', `members[${index}][user_id]`);
                $(this).siblings('input[type="hidden"]').attr('name', `members[${index}][priority]`);
            });
        } else {
            $('.member-checkbox').each(function() {
                $(this).removeAttr('name');
                $(this).siblings('input[type="hidden"]').removeAttr('name');
            });
        }

        updateSummary();
    });

    // Individual checkbox change
    $('.member-checkbox').on('change', function() {
        // Reindex all checked members
        $('.member-checkbox:checked').each(function(index) {
            $(this).attr('name', `members[${index}][user_id]`);
            $(this).siblings('input[type="hidden"]').attr('name', `members[${index}][priority]`);
        });

        // Remove name attribute from unchecked boxes
        $('.member-checkbox:not(:checked)').each(function() {
            $(this).removeAttr('name');
            $(this).siblings('input[type="hidden"]').removeAttr('name');
        });

        updateSummary();

        // Update select all checkbox state
        const totalCheckboxes = $('.member-checkbox').length;
        const checkedCheckboxes = $('.member-checkbox:checked').length;
        $('#selectAll').prop('checked', totalCheckboxes === checkedCheckboxes);
    });

    // Form validation
    $('#groupForm').on('submit', function(e) {
        const checkedMembers = $('.member-checkbox:checked').length;
        if (checkedMembers === 0) {
            e.preventDefault();
            toastr.error('Debe seleccionar al menos un miembro para el grupo', 'Error de Validación');
            return false;
        }

        // Show loading state
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true);
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Creando...');
    });

    // Initialize summary on page load
    updateSummary();

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif
});
</script>

<style>
.round-48 {
    width: 48px;
    height: 48px;
}

.member-row:hover {
    background-color: #f5f6f8;
}
</style>
@endsection

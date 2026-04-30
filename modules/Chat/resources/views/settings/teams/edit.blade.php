@extends('layouts.theme')

@section('title', 'Editar equipo')

@section('content')

    <div class="card w-100">

        <form id="formTeam" method="POST" action="{{ route('settings.chat.teams.update', $team) }}">

            @csrf
            @method('PUT')

            <div class="card-body">
                <div class="d-flex no-block align-items-center">
                    <h5 class="mb-0">Editar equipo</h5>
                </div>
                <p class="card-subtitle mb-3 mt-1">
                    Modifica la configuración de este equipo. Los cambios afectarán la distribución de conversaciones.
                </p>

                <div class="row">

                    <!-- Basic Information -->
                    <div class="col-12">
                        <h6 class="mb-1 mt-3 fw-semibold">Información básica</h6>
                        <p class="text-muted small mb-3">Define el nombre y descripción del equipo.</p>
                    </div>

                    <div class="col-md-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Nombre del equipo
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $team->name) }}" required placeholder="Ej: Soporte Técnico">
                            <small class="form-text text-muted">Nombre visible del equipo</small>
                            @error('name')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Descripción</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="Descripción del equipo">{{ old('description', $team->description) }}</textarea>
                            <small class="form-text text-muted">Proporciona más contexto sobre este equipo</small>
                            @error('description')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Configuration -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Configuración de asignación</h6>
                        <p class="text-muted small mb-3">Define cómo se asignarán las conversaciones a los miembros del equipo.</p>
                    </div>

                    <div class="col-12 col-md-12">
                        <div class="border-bottom pb-3 mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="allow_auto_assign" value="0">
                                <input type="checkbox" name="allow_auto_assign" class="form-check-input" id="autoAssignCheck" value="1" {{ old('allow_auto_assign', $team->allow_auto_assign) ? 'checked' : '' }}>
                                <label class="form-check-label" for="autoAssignCheck">
                                    <strong>Auto-asignación habilitada</strong>
                                    <small class="d-block text-muted">Permite asignar automáticamente conversaciones a los miembros de este equipo.</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Members -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Miembros del equipo</h6>
                        <p class="text-muted small mb-3">Selecciona los usuarios que pertenecen a este equipo.</p>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            @php
                                $selectedUserIds = old('member_ids', $team->members->pluck('id')->toArray());
                            @endphp

                            <label class="control-label col-form-label mb-2">
                                Usuarios disponibles
                                @if(isset($users) && count($users) > 0)
                                    <span class="badge bg-primary ms-2">{{ count($users) }} disponibles</span>
                                @endif
                            </label>

                            <div class="d-flex justify-content-between align-items-center mt-2 mb-3">
                                <small class="text-muted">
                                    <span id="selected-count">{{ count($selectedUserIds ?? []) }}</span> usuario(s) seleccionado(s)
                                </small>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="select-all">
                                        <i class="fas fa-check-square me-1"></i> Seleccionar todos
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="deselect-all">
                                        <i class="fas fa-square me-1"></i> Deseleccionar todos
                                    </button>
                                </div>
                            </div>

                            @if(isset($users) && count($users) > 0)
                                <div class="border rounded p-3" style="max-height: 400px; overflow-y: auto;">
                                    <div class="row g-2">
                                        @foreach($users as $user)
                                            @php
                                                $isSelected = in_array($user->id, $selectedUserIds);
                                            @endphp
                                            <div class="col-md-6">
                                                <div class="card mb-0 user-selection-card {{ $isSelected ? 'active' : '' }}" data-user-id="{{ $user->id }}">
                                                    <div class="card-body p-3">
                                                        <input class="user-checkbox-input"
                                                               type="checkbox"
                                                               name="member_ids[]"
                                                               value="{{ $user->id }}"
                                                               id="user_{{ $user->id }}"
                                                               {{ $isSelected ? 'checked' : '' }}>
                                                        <label class="user-label w-100" for="user_{{ $user->id }}">
                                                            <div class="d-flex align-items-center">
                                                                <i class="fas fa-user-circle fa-2x me-3 text-primary"></i>
                                                                <div>
                                                                    <strong class="d-block mb-0">{{ $user->firstname }} {{ $user->lastname }}</strong>
                                                                    <p class="text-muted mb-0 small">{{ $user->email }}</p>
                                                                </div>
                                                            </div>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    No hay usuarios disponibles. Asegúrate de que existan usuarios en el sistema.
                                </div>
                            @endif

                            @error('member_ids')
                                <span class="field-validation-error d-block mt-2"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                </div>

            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-info px-4 waves-effect waves-light mt-2 w-100">
                    Guardar
                </button>
                <a href="{{ route('settings.chat.teams.index') }}" class="btn btn-secondary px-4 waves-effect waves-light mt-2 w-100">
                    Volver
                </a>
            </div>

        </form>

    </div>

@endsection

@push('scripts')
<style>
/* Ocultar checkbox visualmente pero mantenerlo funcional para accesibilidad */
.user-checkbox-input {
    position: absolute !important;
    opacity: 0 !important;
    pointer-events: none !important;
    width: 1px !important;
    height: 1px !important;
}

/* Estilos de las tarjetas de selección de usuarios */
.user-selection-card {
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid #e0e0e0 !important;
    border-radius: 8px;
}

.user-selection-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
    border-color: #b10100 !important;
}

.user-selection-card.active {
    border-color: #b10100 !important;
    background-color: rgba(144, 187, 19, 0.05) !important;
    box-shadow: 0 2px 8px rgba(144, 187, 19, 0.2) !important;
}

.user-label {
    cursor: pointer;
    margin: 0;
    display: block;
    width: 100%;
}
</style>

<script>
$(document).ready(function() {
    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Equipo actualizado');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    // Función para actualizar el contador de usuarios seleccionados
    function updateSelectedCount() {
        const count = $('.user-checkbox-input:checked').length;
        $('#selected-count').text(count);
    }

    // Manejar cambios en los checkboxes
    $('.user-checkbox-input').on('change', function() {
        const $card = $(this).closest('.user-selection-card');

        if ($(this).is(':checked')) {
            $card.addClass('active');
        } else {
            $card.removeClass('active');
        }

        updateSelectedCount();
    });

    // Seleccionar todos
    $('#select-all').on('click', function() {
        $('.user-checkbox-input').prop('checked', true).trigger('change');
    });

    // Deseleccionar todos
    $('#deselect-all').on('click', function() {
        $('.user-checkbox-input').prop('checked', false).trigger('change');
    });

    // Manejar click en toda la tarjeta
    $('.user-selection-card').on('click', function(e) {
        // Evitar que el click en el checkbox active el toggle dos veces
        if ($(e.target).is('.user-checkbox-input')) {
            return;
        }

        const checkbox = $(this).find('.user-checkbox-input');
        checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
    });

    // Inicializar estados
    $('.user-checkbox-input:checked').each(function() {
        $(this).closest('.user-selection-card').addClass('active');
    });
});
</script>
@endpush

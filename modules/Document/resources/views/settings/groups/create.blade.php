@extends('layouts.theme')

@section('content')

    <div class="card w-100">

        <form id="formGroup" method="POST" action="{{ route('settings.documents.groups.store') }}">

            {{ csrf_field() }}

            <div class="card-body">
                <div class="d-flex no-block align-items-center">
                    <h5 class="mb-0">Crear grupo de validación</h5>
                </div>
                <p class="card-subtitle mb-3 mt-1">
                    Define un nuevo grupo de validación. Estos grupos se usan en workflows multi-etapa para documentos, tickets y otros procesos.
                </p>

                <div class="row">

                    <!-- Basic Information -->
                    <div class="col-12">
                        <h6 class="mb-1 mt-3 fw-semibold">Información básica</h6>
                        <p class="text-muted small mb-3">Define el nombre y descripción del grupo.</p>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Nombre del grupo
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required placeholder="Ej: Documentación">
                            <small class="form-text text-muted">Nombre visible del grupo</small>
                            @error('name')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Clave única
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="key" class="form-control" value="{{ old('key') }}" required placeholder="Ej: documentation" pattern="[a-z0-9_-]+" title="Solo letras minúsculas, números, guiones y guiones bajos">
                            <small class="form-text text-muted">Identificador único para el workflow (sin espacios, minúsculas)</small>
                            @error('key')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Descripción</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Descripción del grupo">{{ old('description') }}</textarea>
                            <small class="form-text text-muted">Proporciona más contexto sobre este grupo</small>
                            @error('description')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Assignment Configuration -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Configuración de asignación</h6>
                        <p class="text-muted small mb-3">Define cómo se asignarán los documentos a los miembros del grupo.</p>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Modo de asignación
                                <span class="text-danger">*</span>
                            </label>
                            <select name="assignment_mode" class="form-select select2" required>
                                <option value="manual" {{ old('assignment_mode', 'manual') == 'manual' ? 'selected' : '' }}>
                                    Manual - El administrador asigna manualmente
                                </option>
                                <option value="round_robin" {{ old('assignment_mode') == 'round_robin' ? 'selected' : '' }}>
                                    Round Robin - Rotación entre usuarios
                                </option>
                                <option value="load_balanced" {{ old('assignment_mode') == 'load_balanced' ? 'selected' : '' }}>
                                    Balance de Carga - Asigna al usuario con menos documentos activos
                                </option>
                            </select>
                            <small class="form-text text-muted">Estrategia de asignación automática de documentos</small>
                            @error('assignment_mode')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Members -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Miembros del grupo</h6>
                        <p class="text-muted small mb-3">Selecciona los usuarios que pertenecen a este grupo y asigna sus prioridades.</p>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label mb-2">
                                Usuarios disponibles
                                @if(isset($users) && count($users) > 0)
                                    <span class="badge bg-info ms-2">{{ count($users) }} disponibles</span>
                                @endif
                            </label>

                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <small class="text-muted">
                                    <span id="selected-count">0</span> usuario(s) seleccionado(s)
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
                                            <div class="col-md-6">
                                                <div class="card mb-0 user-selection-card" data-user-id="{{ $user->id }}">
                                                    <div class="card-body p-3">
                                                        <input class="user-checkbox-input"
                                                               type="checkbox"
                                                               name="users[]"
                                                               value="{{ $user->id }}"
                                                               id="user_{{ $user->id }}"
                                                               {{ in_array($user->id, old('users', [])) ? 'checked' : '' }}>
                                                        <label class="user-label w-100" for="user_{{ $user->id }}">
                                                            <div class="d-flex align-items-center">
                                                                <i class="fas fa-user-circle fa-2x me-3 text-primary"></i>
                                                                <div>
                                                                    <strong class="d-block mb-0">{{ $user->firstname }} {{ $user->lastname }}</strong>
                                                                    <p class="text-muted mb-0 small">{{ $user->email }}</p>
                                                                </div>
                                                            </div>
                                                        </label>
                                                        <div class="mt-2 priority-selector" style="display: none;">
                                                            <select name="user_priorities[]" class="form-select select2 form-select-sm select2">
                                                                <option value="primary" selected>Primario</option>
                                                                <option value="backup">Respaldo</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                            @else
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    No hay usuarios disponibles. Asegúrate de que existan usuarios confirmados y disponibles en el sistema.
                                </div>
                            @endif

                            @error('users')
                                <span class="field-validation-error d-block mt-2"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Options -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Opciones</h6>
                        <p class="text-muted small mb-3">Configura el comportamiento del grupo.</p>
                    </div>

                    <div class="col-12 col-md-12">
                        <div class="border-bottom pb-3 mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" class="form-check-input" id="activeCheck" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="activeCheck">
                                    <strong>Grupo activo</strong>
                                    <small class="d-block text-muted">Permite que este grupo esté disponible para asignación de documentos.</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-12">
                        <div class="border-bottom pb-3 mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_default" value="0">
                                <input type="checkbox" name="is_default" class="form-check-input" id="defaultCheck" value="1" {{ old('is_default') ? 'checked' : '' }}>
                                <label class="form-check-label" for="defaultCheck">
                                    <strong>Grupo Por defecto</strong>
                                    <small class="d-block text-muted">Se selecciona automáticamente para nuevos documentos sin grupo asignado.</small>
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
                <a href="{{ route('settings.documents.groups.index') }}" class="btn btn-secondary px-4 waves-effect waves-light mt-2 w-100">
                    Cancelar
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

/* Estilos de las tarjetas de selección de usuarios - mismo estilo que condition-type-card */
.user-selection-card {
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid #e0e0e0 !important;
    border-radius: 8px;
}

.user-selection-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
    border-color: #90bb13 !important;
}

.user-selection-card.active {
    border-color: #90bb13 !important;
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
        const $card = $(this).closest('.card-body');
        const $prioritySelector = $card.find('.priority-selector');

        if ($(this).is(':checked')) {
            $prioritySelector.slideDown(200);
            $card.closest('.user-selection-card').addClass('active');
        } else {
            $prioritySelector.slideUp(200);
            $card.closest('.user-selection-card').removeClass('active');
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
    $('.user-selection-card').on('click', function() {
        const checkbox = $(this).find('.user-checkbox-input');
        checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
    });

    // Inicializar estados
    $('.user-checkbox-input:checked').each(function() {
        $(this).closest('.card-body').find('.priority-selector').show();
        $(this).closest('.user-selection-card').addClass('active');
    });
});
</script>
@endpush

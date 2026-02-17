{{-- Component: Attention Management Card --}}
<div class="card mb-3" id="attentionManagementCard">
    <div class="card-header p-3 bg-white border-bottom">
        <h5 class="mb-1 fw-bold">Gestión de la solicitud</h5>
        <p class="small mb-0 text-muted">Cambiar estado, asignar y resolver</p>
    </div>
    <div class="card-body">
        <form id="attentionManagementForm">
            @csrf
            <input type="hidden" name="uid" value="{{ $attention->uid }}">

            <div class="row g-3">

                {{-- Cambiar Estado --}}
                @if(auth()->user()->can('attention.change-status'))
                    <div class="col-12">
                        <label class="form-label fw-semibold">
                            Estado
                        </label>
                        <select class="form-select select2" name="status" id="statusSelect">
                            <option value="">Seleccionar estado...</option>
                            @foreach($statuses as $status)
                                <option value="{{ $status->value }}" {{ $attention->status === $status ? 'selected' : '' }}>
                                    {{ $status->label() }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Cambiar el estado de la solicitud</small>
                    </div>
                @endif

                {{-- Asignar Departamento --}}
                @if(auth()->user()->can('attention.assign-department'))
                    <div class="col-sm-12 col-md-6">
                        <label class="form-label fw-semibold">
                            Departamento
                        </label>
                        <select class="form-select select2" name="department_id" id="departmentSelect">
                            <option value="">Sin asignar</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" {{ $attention->department_id == $department->id ? 'selected' : '' }}>
                                    {{ $department->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Asignar a un departamento</small>
                    </div>
                @endif

                {{-- Asignar Usuario --}}
                @if(auth()->user()->can('attention.assign-user'))
                    <div class="col-sm-12 col-md-6">
                        <label class="form-label fw-semibold">
                            Usuario responsable
                        </label>
                        <select class="form-select select2" name="assigned_user_id" id="userSelect">
                            <option value="">Sin asignar</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ $attention->assigned_user_id == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Asignar a un usuario específico</small>
                    </div>
                @endif

                {{-- Resolución (solo visible si está en estado RESOLVED) --}}
                @if(auth()->user()->can('attention.resolve'))
                    <div class="col-12" id="resolutionSection" style="display: {{ $attention->isResolved() ? 'block' : 'none' }};">
                        <label class="form-label fw-semibold">
                            Resolución
                        </label>
                        <textarea class="form-control" name="resolution" id="resolutionText" rows="4"
                                  placeholder="Escribir la resolución del PQRSF...">{{ $attention->resolution }}</textarea>
                        <small class="text-muted">Describir cómo se resolvió el PQRSF</small>
                    </div>

                    <div class="col-12" id="responseTypeSection" style="display: {{ $attention->isResolved() ? 'block' : 'none' }};">
                        <label class="form-label fw-semibold">
                            Tipo de respuesta
                        </label>
                        <select class="form-select select2" name="response_type" id="responseTypeSelect">
                            @foreach($responseTypes as $type)
                                <option value="{{ $type->value }}" {{ $attention->response_type === $type ? 'selected' : '' }}>
                                    {{ $type->label() }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Medio por el cual se envió la respuesta</small>
                    </div>
                @endif

                {{-- Comentario opcional --}}
                <div class="col-12">
                    <label class="form-label fw-semibold">
                        Comentario (opcional)
                    </label>
                    <textarea class="form-control" name="comment" id="commentText" rows="2"  placeholder="Agregar un comentario sobre los cambios realizados..."></textarea>
                    <small class="text-muted">Este comentario se guardará en el historial de acciones</small>
                </div>

                {{-- Botón de Guardar --}}
                <div class="col-12">
                    <button type="submit" class="btn btn-primary w-100" id="saveManagementBtn">
                        Guardar cambios
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    const attentionUid = '{{ $attention->uid }}';
    const currentStatus = '{{ $attention->status->value }}';

    // Mostrar/ocultar sección de resolución según el estado seleccionado
    $('#statusSelect').on('change', function() {
        const selectedStatus = $(this).val();

        if (selectedStatus === 'resolved') {
            $('#resolutionSection').slideDown();
            $('#responseTypeSection').slideDown();
        } else {
            $('#resolutionSection').slideUp();
            $('#responseTypeSection').slideUp();
        }
    });

    // Manejo del formulario de gestión
    $('#attentionManagementForm').on('submit', function(e) {
        e.preventDefault();

        const $form = $(this);
        const $btn = $('#saveManagementBtn');
        const formData = $form.serialize();

        // Validar que al menos un campo haya cambiado
        const status = $('#statusSelect').val();
        const department = $('#departmentSelect').val();
        const user = $('#userSelect').val();
        const resolution = $('#resolutionText').val();
        const responseType = $('#responseTypeSelect').val();

        if (!status && !department && !user && !resolution) {
            toastr.warning('No hay cambios para guardar', 'Atención');
            return;
        }

        // Si el estado es "resolved", validar que haya resolución
        if (status === 'resolved' && !resolution) {
            toastr.error('Debe escribir una resolución para marcar como resuelto', 'Error');
            $('#resolutionText').focus();
            return;
        }

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando...');

        $.ajax({
            url: `/attentions/${attentionUid}/manage`,
            method: 'PUT',
            data: formData,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Cambios guardados correctamente', 'Éxito');

                    // Recargar la página después de 1 segundo para reflejar los cambios
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    toastr.error(response.message || 'No se pudieron guardar los cambios', 'Error');
                    $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Guardar cambios');
                }
            },
            error: function(xhr) {
                let errorMsg = 'Error al guardar los cambios';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = Object.values(xhr.responseJSON.errors).flat();
                    errorMsg = errors.join(', ');
                }

                toastr.error(errorMsg, 'Error');
                $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Guardar cambios');
            }
        });
    });
});
</script>
@endpush

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-1"><i class="ti ti-tool me-2"></i>Herramientas del Agente</h5>
        <p class="text-muted small mb-0">Define funciones y APIs que el agente puede usar para realizar acciones</p>
    </div>
    <button type="button" class="btn btn-success" onclick="openToolModal()">
        <i class="ti ti-plus me-2"></i>Nueva Herramienta
    </button>
</div>

@if($tools->isEmpty())
    <div class="alert alert-info">
        <i class="ti ti-info-circle me-2"></i>
        <strong>No hay herramientas configuradas</strong>
        <p class="mb-0 mt-2">Crea herramientas para que el agente pueda realizar acciones como consultar bases de datos, llamar APIs externas, o ejecutar funciones personalizadas.</p>
    </div>
@else
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Tipo</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Uso</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tools as $tool)
                    <tr data-count-item class="{{ $tool->is_active ? '' : 'table-secondary' }}">
                        <td>
                            <span class="badge bg-{{ $tool->type === 'function' ? 'primary' : ($tool->type === 'api' ? 'info' : ($tool->type === 'database' ? 'warning' : 'secondary')) }}">
                                <i class="{{ $tool->type_icon }} me-1"></i>{{ $tool->type_label }}
                            </span>
                        </td>
                        <td>
                            <strong>{{ $tool->name }}</strong>
                            @if($tool->requires_approval)
                                <span class="badge bg-danger ms-1" data-bs-toggle="tooltip" title="Requiere aprobación">
                                    <i class="ti ti-lock"></i>
                                </span>
                            @endif
                        </td>
                        <td>
                            <small>{{ Str::limit($tool->description, 80) }}</small>
                        </td>
                        <td>
                            <small class="text-muted">
                                {{ $tool->usage_count }} veces
                                @if($tool->last_used_at)
                                    <br>Último: {{ $tool->last_used_at->diffForHumans() }}
                                @endif
                            </small>
                        </td>
                        <td>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" {{ $tool->is_active ? 'checked' : '' }}
                                    onchange="toggleTool({{ $tool->id }}, this.checked)">
                            </div>
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-light" onclick="viewTool({{ $tool->id }})" data-bs-toggle="tooltip" title="Ver detalles">
                                    <i class="ti ti-eye"></i>
                                </button>
                                <button type="button" class="btn btn-light" onclick="editTool({{ $tool->id }})" data-bs-toggle="tooltip" title="Editar">
                                    <i class="ti ti-edit"></i>
                                </button>
                                <button type="button" class="btn btn-light text-danger" onclick="deleteTool({{ $tool->id }})" data-bs-toggle="tooltip" title="Eliminar">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<script>
function openToolModal(id = null) {
    $('#toolModal').modal('show');
    if (id) {
        $.get(`/dashboard/helpdesk/ai/tools/${id}`, function(data) {
            $('#tool_id').val(data.id);
            $('#tool_name').val(data.name);
            $('#tool_description').val(data.description);
            $('#tool_type').val(data.type);
            $('#tool_parameters').val(JSON.stringify(data.parameters, null, 2));
            $('#tool_implementation').val(data.implementation);
            $('#tool_auth_config').val(JSON.stringify(data.auth_config, null, 2));
            $('#tool_requires_approval').prop('checked', data.requires_approval);
            $('#tool_is_active').prop('checked', data.is_active);
            $('#toolModalTitle').text('Editar Herramienta');
        });
    } else {
        $('#toolForm')[0].reset();
        $('#tool_id').val('');
        $('#toolModalTitle').text('Nueva Herramienta');
    }
}

function editTool(id) {
    openToolModal(id);
}

function viewTool(id) {
    // Implementation for viewing tool details
    alert('Ver detalles de la herramienta #' + id);
}

function toggleTool(id, isActive) {
    $.post(`{{ route('manager.helpdesk.ai.tools.toggle', '') }}/${id}`, {
        _token: '{{ csrf_token() }}',
        is_active: isActive
    }).done(function() {
        showSuccess('Herramienta ' + (isActive ? 'activada' : 'desactivada') + ' correctamente');
    }).fail(function() {
        showError('Error al actualizar la herramienta');
    });
}

function deleteTool(id) {
    if (confirm('¿Estás seguro de que deseas eliminar esta herramienta?')) {
        $.ajax({
            url: `{{ route('manager.helpdesk.ai.tools.destroy', '') }}/${id}`,
            method: 'DELETE',
            data: { _token: '{{ csrf_token() }}' }
        }).done(function() {
            showSuccess('Herramienta eliminada correctamente');
            setTimeout(() => {
                $('a[href="#tab-tools"]').click();
            }, 500);
        }).fail(function() {
            showError('Error al eliminar la herramienta');
        });
    }
}

// Handle tool form submission
$('#toolForm').on('submit', function(e) {
    e.preventDefault();

    const formData = {
        name: $('#tool_name').val(),
        description: $('#tool_description').val(),
        type: $('#tool_type').val(),
        implementation: $('#tool_implementation').val(),
        requires_approval: $('#tool_requires_approval').is(':checked'),
        is_active: $('#tool_is_active').is(':checked'),
        _token: '{{ csrf_token() }}'
    };

    // Parse JSON fields
    try {
        formData.parameters = $('#tool_parameters').val() ? JSON.parse($('#tool_parameters').val()) : null;
        formData.auth_config = $('#tool_auth_config').val() ? JSON.parse($('#tool_auth_config').val()) : null;
    } catch (e) {
        showError('Error en formato JSON: ' + e.message);
        return;
    }

    const toolId = $('#tool_id').val();
    const url = toolId
        ? `{{ route('manager.helpdesk.ai.tools.update', '') }}/${toolId}`
        : `{{ route('manager.helpdesk.ai.tools.store') }}`;
    const method = toolId ? 'PUT' : 'POST';

    $.ajax({
        url: url,
        method: method,
        data: formData
    }).done(function() {
        $('#toolModal').modal('hide');
        showSuccess('Herramienta guardada correctamente');
        setTimeout(() => {
            $('a[href="#tab-tools"]').click();
        }, 500);
    }).fail(function(xhr) {
        showError(xhr.responseJSON?.message || 'Error al guardar la herramienta');
    });
});

$('[data-bs-toggle="tooltip"]').tooltip();
</script>

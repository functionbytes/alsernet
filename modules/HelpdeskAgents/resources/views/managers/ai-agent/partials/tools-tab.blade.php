{{-- Tools tab partial - loaded via AJAX into #tools-container --}}

@php
    $total = $tools->total();
    $active = $tools->where('is_active', true)->count();
    $inactive = $total - $active;
@endphp

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-1 fw-bold">Herramientas del agente</h5>
        <p class="text-muted mb-0 small">Funciones y APIs que el agente puede invocar para realizar acciones</p>
    </div>
    <button type="button" class="btn btn-primary" id="btn-new-tool">
        <i class="fas fa-plus me-1"></i> Nueva herramienta
    </button>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="card bg-light-secondary h-100">
            <div class="card-body">
                <h6 class="card-title mb-2">Total</h6>
                <h4 class="mb-1 fw-bold">{{ number_format($total) }}</h4>
                <small class="text-muted">Herramientas registradas</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card bg-light-secondary h-100">
            <div class="card-body">
                <h6 class="card-title mb-2">Activas</h6>
                <h4 class="mb-1 fw-bold">{{ number_format($active) }}</h4>
                <small class="text-muted">Disponibles para el agente</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card bg-light-secondary h-100">
            <div class="card-body">
                <h6 class="card-title mb-2">Inactivas</h6>
                <h4 class="mb-1 fw-bold">{{ number_format($inactive) }}</h4>
                <small class="text-muted">No disponibles para el agente</small>
            </div>
        </div>
    </div>
</div>

{{-- Table --}}
@if($tools->isEmpty())
    <div class="text-center py-5">
        <i class="fas fa-wrench fa-3x mb-3 text-muted opacity-50"></i>
        <h5 class="fw-bold mb-2">No hay herramientas configuradas</h5>
        <p class="text-muted mb-4">Crea herramientas para que el agente pueda consultar APIs, bases de datos o ejecutar funciones.</p>
        <button type="button" class="btn btn-primary" id="btn-new-tool-empty">
            <i class="fas fa-plus me-1"></i> Nueva herramienta
        </button>
    </div>
@else
    <div class="table-responsive">
        <table class="table table-hover align-middle text-nowrap">
            <thead class="table-light">
                <tr>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Descripcion</th>
                    <th>Usos</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tools as $tool)
                    <tr data-count-item>
                        <td>
                            <span class="fw-semibold">{{ $tool->name }}</span>
                            @if($tool->requires_approval)
                                <span class="badge bg-warning-subtle text-warning ms-1">Aprobacion</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $typeLabels = ['function' => 'Funcion', 'api' => 'API', 'database' => 'Base de datos', 'custom' => 'Personalizado'];
                                $typeBadges = ['function' => 'primary', 'api' => 'info', 'database' => 'warning', 'custom' => 'secondary'];
                            @endphp
                            <span class="badge bg-{{ $typeBadges[$tool->type] ?? 'secondary' }}-subtle text-{{ $typeBadges[$tool->type] ?? 'secondary' }}">
                                {{ $typeLabels[$tool->type] ?? $tool->type }}
                            </span>
                        </td>
                        <td>
                            <small class="text-muted">{{ Str::limit($tool->description, 60) }}</small>
                        </td>
                        <td>
                            <small class="text-muted">{{ $tool->usage_count ?? 0 }} veces</small>
                        </td>
                        <td>
                            @if($tool->is_active)
                                <span class="badge bg-success-subtle text-success">Activa</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Inactiva</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="dropdown">
                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                    <i class="fas fa-ellipsis-vertical"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item tool-edit-btn" href="#" data-id="{{ $tool->id }}">
                                            Editar
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item tool-toggle-btn" href="#"
                                           data-id="{{ $tool->id }}"
                                           data-active="{{ $tool->is_active ? '1' : '0' }}">
                                            {{ $tool->is_active ? 'Desactivar' : 'Activar' }}
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item tool-delete-btn" href="#"
                                           data-id="{{ $tool->id }}"
                                           data-name="{{ $tool->name }}">
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

    @if($tools->hasPages())
        <div class="d-flex justify-content-end mt-3">
            {{ $tools->links() }}
        </div>
    @endif
@endif

<script>
$(function () {
    const CSRF = $('meta[name="csrf-token"]').attr('content');

    // New tool
    $(document).on('click', '#btn-new-tool, #btn-new-tool-empty', function () {
        $('#tool_id').val('');
        $('#toolForm')[0].reset();
        $('#toolModalLabel').text('Nueva herramienta');
        $('#toolModal').modal('show');
    });

    // Edit tool (load via AJAX)
    $(document).on('click', '.tool-edit-btn', function (e) {
        e.preventDefault();
        const id = $(this).data('id');

        $.get('{{ route("manager.helpdesk.ai.tools.index") }}/' + id, function (data) {
            $('#tool_id').val(data.id);
            $('#tool_name').val(data.name);
            $('#tool_description').val(data.description);
            $('#tool_type').val(data.type);
            $('#tool_parameters').val(data.parameters ? JSON.stringify(data.parameters, null, 2) : '');
            $('#tool_implementation').val(data.implementation);
            $('#tool_auth_config').val(data.auth_config ? JSON.stringify(data.auth_config, null, 2) : '');
            $('#tool_requires_approval').val(data.requires_approval ? '1' : '0');
            $('#tool_is_active').val(data.is_active ? '1' : '0');
            $('#toolModalLabel').text('Editar herramienta');
            $('#toolModal').modal('show');
        }).fail(function () {
            toastr.error('Error al cargar la herramienta', 'Error');
        });
    });

    // Toggle active
    $(document).on('click', '.tool-toggle-btn', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        const active = $(this).data('active') == '1' ? 0 : 1;

        $.ajax({
            url: '{{ route("manager.helpdesk.ai.tools.toggle", "__ID__") }}'.replace('__ID__', id),
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF },
            data: { is_active: active }
        }).done(function () {
            toastr.success(active ? 'Herramienta activada' : 'Herramienta desactivada', 'Exito');
            reloadToolsTab();
        }).fail(function () {
            toastr.error('Error al actualizar la herramienta', 'Error');
        });
    });

    // Delete
    $(document).on('click', '.tool-delete-btn', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        const name = $(this).data('name');

        $('#delete-modal .modal-title').text('Eliminar herramienta: ' + name);
        $('#delete-form').attr('action', '#').off('submit').on('submit', function (ev) {
            ev.preventDefault();
            $.ajax({
                url: '{{ route("manager.helpdesk.ai.tools.destroy", "__ID__") }}'.replace('__ID__', id),
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF }
            }).done(function () {
                $('#delete-modal').modal('hide');
                toastr.success('Herramienta eliminada correctamente', 'Exito');
                reloadToolsTab();
            }).fail(function () {
                toastr.error('Error al eliminar la herramienta', 'Error');
            });
        });
        $('#delete-modal').modal('show');
    });

    // Form submit
    $('#toolForm').off('submit').on('submit', function (e) {
        e.preventDefault();
        const id = $('#tool_id').val();
        const url = id
            ? '{{ route("manager.helpdesk.ai.tools.update", "__ID__") }}'.replace('__ID__', id)
            : '{{ route("manager.helpdesk.ai.tools.store") }}';

        let parameters = null;
        let authConfig = null;

        try {
            const pVal = $('#tool_parameters').val().trim();
            const aVal = $('#tool_auth_config').val().trim();
            parameters = pVal ? JSON.parse(pVal) : null;
            authConfig = aVal ? JSON.parse(aVal) : null;
        } catch (err) {
            toastr.error('Formato JSON invalido: ' + err.message, 'Error');
            return;
        }

        $('.is-invalid').removeClass('is-invalid');

        $.ajax({
            url: url,
            method: id ? 'PUT' : 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF },
            data: {
                name: $('#tool_name').val(),
                description: $('#tool_description').val(),
                type: $('#tool_type').val(),
                parameters: parameters,
                implementation: $('#tool_implementation').val(),
                auth_config: authConfig,
                requires_approval: $('#tool_requires_approval').val(),
                is_active: $('#tool_is_active').val(),
            }
        }).done(function (res) {
            $('#toolModal').modal('hide');
            toastr.success(res.message || 'Herramienta guardada correctamente', 'Exito');
            reloadToolsTab();
        }).fail(function (xhr) {
            if (xhr.status === 422) {
                $.each(xhr.responseJSON.errors, function (field, messages) {
                    $('#tool_' + field).addClass('is-invalid')
                        .siblings('.invalid-feedback').text(messages[0]);
                });
            } else {
                toastr.error(xhr.responseJSON?.message || 'Error al guardar la herramienta', 'Error');
            }
        });
    });

    function reloadToolsTab() {
        $.get('{{ route("manager.helpdesk.ai.tools.index") }}', function (html) {
            $('#tools-container').html(html);
            const count = $('#tools-container [data-count-item]').length;
            $('#tools-count').text(count);
        });
    }
});
</script>

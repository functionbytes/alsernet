@extends('layouts.theme')

@section('title', 'Plantillas SEO')

@section('content')
    @include('core::components.card', ['title' => 'Plantillas SEO'])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Plantillas SEO</h5>
                        <p class="small mb-0 text-muted">Define patrones de título y descripción para aplicar automáticamente a modelos</p>
                    </div>
                    <div>
                        <a href="{{ route('settings.seo.templates.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nueva plantilla
                        </a>
                    </div>
                </div>
            </div>

            {{-- Stat cards --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total de plantillas</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['total']) }}</h4>
                                <small class="text-muted">Configuradas en el sistema</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Activas</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['active']) }}</h4>
                                <small class="text-muted">{{ number_format($stats['total'] - $stats['active']) }} inactivas</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($templates->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>Nombre</th>
                                    <th>Tipo de modelo</th>
                                    <th>Patron de titulo</th>
                                    <th>Robots</th>
                                    <th class="text-center">Prioridad</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($templates as $template)
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $template->id }}"></td>
                                        <td>
                                            <span class="fw-semibold">{{ $template->name }}</span>
                                        </td>
                                        <td>
                                            @if($template->model_type)
                                                <code class="small">{{ $template->model_type }}</code>
                                            @else
                                                <span class="badge bg-primary-subtle text-primary">Global</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($template->title_pattern)
                                                <small class="text-muted font-monospace">{{ Str::limit($template->title_pattern, 40) }}</small>
                                            @else
                                                <small class="text-muted">—</small>
                                            @endif
                                        </td>
                                        <td>
                                            <code class="small">{{ $template->robots }}</code>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary-subtle text-secondary">{{ $template->priority }}</span>
                                        </td>
                                        <td class="text-center">
                                            <button type="button"
                                                    class="btn btn-sm btn-link p-0 border-0 toggle-active-btn"
                                                    data-id="{{ $template->id }}"
                                                    data-url="{{ route('settings.seo.templates.toggle-active', $template) }}"
                                                    title="Cambiar estado">
                                                @if($template->is_active)
                                                    <span class="badge bg-success text-white">Activo</span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary">Inactivo</span>
                                                @endif
                                            </button>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown">
                                                    <i class="fa fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.seo.templates.edit', $template) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item btn-bulk-apply-preview"
                                                                data-template-id="{{ $template->id }}"
                                                                data-template-name="{{ $template->name }}">
                                                            Aplicar a todos
                                                        </button>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn"
                                                           href="#"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#delete-modal"
                                                           data-url="{{ route('settings.seo.templates.destroy', $template) }}"
                                                           data-title="Eliminar: {{ $template->name }}">
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
                        <i class="fas fa-file-code fa-4x text-muted opacity-50 mb-3"></i>
                        <h5 class="text-muted mb-2">No hay plantillas configuradas</h5>
                        <p class="text-muted mb-4">Crea una plantilla para aplicar patrones SEO automáticamente</p>
                        <a href="{{ route('settings.seo.templates.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Crear primera plantilla
                        </a>
                    </div>
                @endif
            </div>

            @if($templates->hasPages())
                <div class="card-footer">{{ $templates->links() }}</div>
            @endif
        </div>
    </div>

    {{-- Bulk toolbar flotante --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar accion
        </button>
    </div>

    {{-- Bulk modal --}}
    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Accion masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se aplicara la accion sobre <strong><span data-bulk-count>0</span> plantilla(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Accion</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar accion...</option>
                            <option value="activate">Activar</option>
                            <option value="deactivate">Desactivar</option>
                            <option value="delete">Eliminar</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-1">Aplicar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    @include('core::components.delete')

    {{-- Bulk apply preview modal --}}
    <div class="modal fade" id="bulkApplyModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-magic me-1"></i> Previsualización de aplicación masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="bulk-apply-preview">
                    <div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i></div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" id="btn-confirm-bulk-apply">
                        <i class="fas fa-check me-1"></i> Aplicar plantilla
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        const action = $('#bulk-action-select').val();
        const ids    = bulk.getIds();

        if (!action) { toastr.warning('Selecciona una accion.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos una plantilla.'); return; }
        if (action === 'delete' && !confirm('¿Eliminar las ' + ids.length + ' plantilla(s) seleccionadas?')) { return; }

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route('settings.seo.templates.bulk-action') }}',
            method: 'POST',
            data: JSON.stringify({ action, ids, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.count + ' plantilla(s) actualizadas.');
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    // Delete modal
    $(document).on('click', '.delete-btn', function () {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });

    // Bulk apply: open preview modal first
    $(document).on('click', '.btn-bulk-apply-preview', function () {
        var templateId = $(this).data('template-id');
        var templateName = $(this).data('template-name');

        $('#bulkApplyModal').modal('show');
        $('#bulk-apply-preview').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');

        $.get('{{ url("panel/setting/seo/templates") }}/' + templateId + '/preview', function (data) {
            $('#bulk-apply-preview').html(
                '<p>La plantilla <strong>' + data.template_name + '</strong> se aplicará a:</p>' +
                '<ul>' +
                '<li><strong>' + data.affected_count + '</strong> páginas sin título SEO</li>' +
                '<li><strong>' + data.desc_count + '</strong> páginas sin descripción</li>' +
                '</ul>' +
                '<p class="text-muted">Solo se rellenarán campos vacíos. Los campos existentes no se modificarán.</p>'
            );
            $('#btn-confirm-bulk-apply').data('template-id', templateId);
        }).fail(function () {
            $('#bulk-apply-preview').html('<p class="text-danger">Error al cargar la previsualización.</p>');
        });
    });

    $('#btn-confirm-bulk-apply').on('click', function () {
        var templateId = $(this).data('template-id');
        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Aplicando...');

        $.ajax({
            url: '{{ url("panel/setting/seo/templates") }}/' + templateId + '/bulk-apply',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (data) {
                $('#bulkApplyModal').modal('hide');
                toastr.success('Plantilla aplicada a ' + data.updated + ' páginas.');
            },
            error: function () {
                toastr.error('Error al aplicar la plantilla.');
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fas fa-check me-1"></i> Aplicar plantilla');
            }
        });
    });

    // Toggle active via AJAX
    $(document).on('click', '.toggle-active-btn', function () {
        var $btn = $(this);
        var url = $btn.data('url');

        $.ajax({
            url: url,
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                var $badge = $btn.find('.badge');
                if (res.is_active) {
                    $badge.removeClass('bg-secondary-subtle text-secondary').addClass('bg-success text-white').text('Activo');
                } else {
                    $badge.removeClass('bg-success text-white').addClass('bg-secondary-subtle text-secondary').text('Inactivo');
                }
            },
            error: function () {
                toastr.error('No se pudo cambiar el estado.', 'Error');
            }
        });
    });
});
</script>
@endpush

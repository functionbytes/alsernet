@extends('layouts.theme')

@section('title', 'Formularios')

@section('page_header')
    @include('core::components.card', ['title' => 'Formularios'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Formularios</h5>
                        <p class="small mb-0 text-muted">Gestiona todos los formularios del sitio</p>
                    </div>
                    <div class="ms-auto">
                        <div class="btn-group">
                            <button type="button" class="btn bg-primary-subtle text-primary dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Acciones
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#importModal">
                                    Importar JSON
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="{{ route('settings.forms.create') }}">
                                    Nuevo formulario
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total formularios</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats->total_forms ?? 0 }}</h4>
                                <small class="text-muted">Formularios registrados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total submissions</h6>
                                <h4 class="mb-1 fw-bold">{{ $totalSubmissions ?? 0 }}</h4>
                                <small class="text-muted">Envíos recibidos</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Formularios activos</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats->active_forms ?? 0 }}</h4>
                                <small class="text-muted">Habilitados en el sitio</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Submissions hoy</h6>
                                <h4 class="mb-1 fw-bold">{{ $submissionsToday ?? 0 }}</h4>
                                <small class="text-muted">Recibidos hoy</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filtros --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.forms.index') }}">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        <div class="flex-fill">
                            <div class="input-group h-100">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control border-start-0 ps-0"
                                       placeholder="Buscar por nombre o slug..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="flex-shrink-0" style="min-width: 180px;">
                            <select name="category_id" class="form-select select2 h-100">
                                <option value="">Todas las categorías</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex-shrink-0" style="min-width: 160px;">
                            <select name="status" class="form-select select2 h-100">
                                <option value="">Todos los estados</option>
                                <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Activos</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactivos</option>
                            </select>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>
                            </button>
                            @if(request('search') || request('category_id') || request('status'))
                                <a href="{{ route('settings.forms.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Tabla --}}
            <div class="card-body">
                @if ($forms->isEmpty())
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-wpforms fs-7"></i>
                            </div>
                            <h6 class="mb-1">
                                @if(request('search') || request('category_id') || request('status'))
                                    No se encontraron formularios
                                @else
                                    No hay formularios creados
                                @endif
                            </h6>
                            <p class="text-muted mb-3">
                                @if(request('search') || request('category_id') || request('status'))
                                    No hay resultados para los criterios de búsqueda
                                @else
                                    Crea el primer formulario para comenzar a recopilar datos
                                @endif
                            </p>
                            @if(!request('search') && !request('category_id') && !request('status'))
                                <a href="{{ route('settings.forms.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus me-1"></i> Nuevo formulario
                                </a>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="forms-table">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>Nombre</th>
                                    <th>Categoría</th>
                                    <th class="text-center">Campos</th>
                                    <th class="text-center">Submissions</th>
                                    <th>Estado</th>
                                    <th>Creado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($forms as $form)
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $form->id }}"></td>
                                        <td>
                                            <div class="fw-semibold">{{ $form->name }}</div>
                                        </td>
                                        <td>
                                            @if ($form->category)
                                                <span class="badge bg-light text-dark">{{ $form->category->name }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light-secondary text-secondary">
                                                {{ $form->fields_count ?? $form->fields->count() }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('settings.forms.submissions.index', $form) }}" class="text-decoration-none">
                                                {{ $form->submissions_count }}
                                            </a>
                                        </td>
                                        <td>
                                            @if ($form->is_active)
                                                <span class="badge bg-success-subtle text-success">Activo</span>
                                            @else
                                                <span class="badge bg-light text-black">Inactivo</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ $form->created_at->format('d/m/Y') }}</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.forms.show', $form) }}">Ver</a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.forms.edit', $form) }}">Editar</a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.forms.preview', $form) }}" target="_blank">Preview</a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.forms.submissions.index', $form) }}">Ver submissions</a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button type="button" class="dropdown-item btn-copy-shortcode"
                                                                data-shortcode='[form id="{{ $form->id }}"]'>
                                                            Copiar shortcode
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button type="button" class="dropdown-item btn-clone-form"
                                                                data-id="{{ $form->id }}">
                                                            Clonar
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.forms.export-json', $form) }}">
                                                            Exportar JSON
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn"
                                                           data-bs-toggle="modal" data-bs-target="#delete-modal"
                                                           data-url="{{ route('settings.forms.destroy', $form) }}"
                                                           data-title="Eliminar: {{ $form->name }}">
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
                @endif
            </div>

            @if ($forms->hasPages())
                <div class="card-footer">{{ $forms->withQueryString()->links() }}</div>
            @endif

        </div>
    </div>

    {{-- Bulk toolbar flotante --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar acción
        </button>
    </div>

    {{-- Bulk modal --}}
    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Acción masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> formulario(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
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

    {{-- Modal importar JSON --}}
    <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importModalLabel">Importar formulario desde JSON</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form id="importForm" action="{{ route('settings.forms.import-json') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="importFile" class="form-label">Archivo JSON <span class="text-danger">*</span></label>
                            <input type="file" name="file" id="importFile" class="form-control" accept=".json" required>
                            <div class="form-text">Selecciona un archivo .json exportado previamente desde este sistema.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1" id="importSubmitBtn">Importar</button>
                        <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('core::components.delete')

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

        if (!action) { toastr.warning('Selecciona una acción.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos un formulario.'); return; }
        if (action === 'delete' && !confirm('¿Eliminar los ' + ids.length + ' formulario(s) seleccionados?')) { return; }

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route('settings.forms.bulk-action') }}',
            method: 'POST',
            data: JSON.stringify({ action, ids, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.count + ' formulario(s) actualizados.');
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });

    $('.delete-btn').on('click', function () {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });

    // Copiar shortcode al portapapeles
    $(document).on('click', '.btn-copy-shortcode', function () {
        navigator.clipboard.writeText($(this).data('shortcode')).then(function () {
            toastr.success('Shortcode copiado al portapapeles');
        });
    });

    // Clonar formulario
    $(document).on('click', '.btn-clone-form', function () {
        const formId = $(this).data('id');
        if (!confirm('¿Clonar este formulario?')) return;

        $.ajax({
            url: '/settings/forms/' + formId + '/clone',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                toastr.success('Formulario clonado correctamente');
                window.location.href = res.redirect;
            },
            error: function () {
                toastr.error('Error al clonar el formulario');
            }
        });
    });

    // Importar — deshabilitar botón al enviar
    $('#importForm').on('submit', function () {
        $('#importSubmitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Importando...');
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush

@extends('layouts.theme')

@section('title', 'Formularios')

@section('content')
    @include('core::components.card', ['title' => 'Formularios'])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Formularios</h5>
                        <p class="small mb-0 text-muted">Gestiona todos los formularios del sitio</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#importModal">
                            <i class="fas fa-file-import me-1"></i> Importar JSON
                        </button>
                        <a href="{{ route('settings.forms.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-1"></i> Nuevo formulario
                        </a>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">Total formularios</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats->total_forms ?? 0 }}</h4>
                                        <small class="text-muted">Formularios registrados</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">Total submissions</h6>
                                        <h4 class="mb-1 fw-bold">{{ $totalSubmissions ?? 0 }}</h4>
                                        <small class="text-muted">Envíos recibidos</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">Formularios activos</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats->active_forms ?? 0 }}</h4>
                                        <small class="text-muted">Habilitados en el sitio</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">Submissions hoy</h6>
                                        <h4 class="mb-1 fw-bold">{{ $submissionsToday ?? 0 }}</h4>
                                        <small class="text-muted">Recibidos hoy</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filtros --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.forms.index') }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control border-start-0 ps-0"
                                       placeholder="Buscar por nombre o slug..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="category_id" class="form-select select2">
                                <option value="">Todas las categorías</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-select">
                                <option value="">Todos los estados</option>
                                <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Activos</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactivos</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                            <a href="{{ route('settings.forms.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Tabla --}}
            <div class="card-body">
                @if ($forms->isEmpty())
                    <div class="text-center py-5">
                        <i class="fas fa-wpforms fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">No hay formularios</h6>
                        <p class="text-muted small">Crea tu primer formulario para comenzar</p>
                        <a href="{{ route('settings.forms.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-1"></i> Nuevo formulario
                        </a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Categoría</th>
                                    <th class="text-center">Campos</th>
                                    <th class="text-center">Submissions</th>
                                    <th>Estado</th>
                                    <th>Creado</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($forms as $form)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $form->name }}</div>
                                            <div class="d-flex align-items-center gap-1 mt-1">
                                                <code class="small text-muted">[form id="{{ $form->id }}"]</code>
                                                <button type="button"
                                                        class="btn btn-link btn-sm p-0 btn-copy-shortcode"
                                                        data-shortcode='[form id="{{ $form->id }}"]'
                                                        title="Copiar shortcode"
                                                        aria-label="Copiar shortcode">
                                                    <i class="far fa-copy text-muted"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td>
                                            @if ($form->category)
                                                <span class="badge bg-light text-dark">
                                                    {{ $form->category->name }}
                                                </span>
                                            @else
                                                <span class="text-muted small">—</span>
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
                                                <span class="badge bg-light-success text-success">Activo</span>
                                            @else
                                                <span class="badge bg-light-danger text-danger">Inactivo</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="text-muted small">
                                                {{ $form->created_at->format('d/m/Y') }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                                        type="button"
                                                        data-bs-toggle="dropdown"
                                                        aria-expanded="false">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.forms.show', $form) }}">
                                                            <i class="far fa-eye me-2"></i> Ver
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.forms.edit', $form) }}">
                                                            <i class="fas fa-pencil-alt me-2"></i> Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.forms.preview', $form) }}" target="_blank">
                                                            <i class="fas fa-desktop me-2"></i> Preview
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.forms.submissions.index', $form) }}">
                                                            <i class="fas fa-inbox me-2"></i> Ver submissions
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button type="button" class="dropdown-item btn-copy-shortcode"
                                                                data-shortcode='[form id="{{ $form->id }}"]'>
                                                            <i class="far fa-copy me-2"></i> Copiar shortcode
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button type="button" class="dropdown-item btn-clone-form"
                                                                data-id="{{ $form->id }}">
                                                            <i class="far fa-clone me-2"></i> Clonar
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.forms.export-json', $form) }}">
                                                            <i class="fas fa-file-export me-2"></i> Exportar JSON
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button type="button" class="dropdown-item text-danger btn-delete-form"
                                                                data-id="{{ $form->id }}"
                                                                data-name="{{ $form->name }}">
                                                            <i class="fas fa-trash-alt me-2"></i> Eliminar
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($forms->hasPages())
                        <div class="mt-3">
                            {{ $forms->withQueryString()->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>

    {{-- Modal importar JSON --}}
    <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog">
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
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="importSubmitBtn">
                            <i class="fas fa-file-import me-1"></i> Importar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal eliminar --}}
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Eliminar formulario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p>¿Eliminar el formulario <strong id="deleteFormName"></strong>?</p>
                    <p class="text-danger small mb-0">Esta acción no se puede deshacer. Solo es posible si no tiene envíos.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <form id="deleteForm" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="fas fa-trash-alt me-1"></i> Eliminar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Copiar shortcode al portapapeles
    $(document).on('click', '.btn-copy-shortcode', function () {
        const shortcode = $(this).data('shortcode');
        navigator.clipboard.writeText(shortcode).then(function () {
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

    // Eliminar formulario — abre modal con datos
    $(document).on('click', '.btn-delete-form', function () {
        const id   = $(this).data('id');
        const name = $(this).data('name');

        $('#deleteFormName').text(name);
        $('#deleteForm').attr('action', '/settings/forms/' + id);

        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    });

    // Importar — deshabilitar botón al enviar
    $('#importForm').on('submit', function () {
        $('#importSubmitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Importando...');
    });
</script>
@endpush

@extends('layouts.theme')

@section('title', 'Formularios')

@section('content')

    @include('core::components.card', ['title' => 'Formularios'])

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
                                <span class="input-group-text bg-white border-end-0">
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
                            <select name="status" class="form-select h-100">
                                <option value="">Todos los estados</option>
                                <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Activos</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactivos</option>
                            </select>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="submit" class="btn btn-primary px-4">
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
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
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
                                        <td>
                                            <div class="fw-semibold">{{ $form->name }}</div>
                                            <div class="d-flex align-items-center gap-1 mt-1">
                                                <code class="small text-muted">[form id="{{ $form->id }}"]</code>
                                                <button type="button"
                                                        class="btn btn-link btn-sm p-0 btn-copy-shortcode"
                                                        data-shortcode='[form id="{{ $form->id }}"]'
                                                        title="Copiar shortcode">
                                                    <i class="far fa-copy text-muted"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td>
                                            @if ($form->category)
                                                <span class="badge bg-light text-dark">{{ $form->category->name }}</span>
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
                                                <span class="badge bg-success-subtle text-success">Activo</span>
                                            @else
                                                <span class="badge bg-light text-black">Inactivo</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $form->created_at->format('d/m/Y') }}</small>
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

    @include('core::components.delete')

@endsection

@push('scripts')
<script>
$(document).ready(function () {
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

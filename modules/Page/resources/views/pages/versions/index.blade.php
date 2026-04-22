@extends('layouts.theme')

@section('page_title', 'Versiones')

@section('content')

    @include('core::components.card', ['title' => 'Versiones'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Versiones de "{{ $page->title }}"</h5>
                        <p class="small mb-0 text-muted">Historial de cambios y snapshots de la página</p>
                    </div>
                    <div class="ms-auto">
                        <div class="btn-group">
                            <button type="button" class="btn bg-primary-subtle text-primary dropdown-toggle"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Acciones
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('create-snapshot-form').submit();">Crear snapshot</a>
                                @if($versions->count() >= 2)
                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#compareModal">Comparar versiones</a>
                                @endif
                                <li><hr class="dropdown-divider"></li>
                                <a class="dropdown-item" href="{{ route('pages.edit', $page->id) }}">Volver a la página</a>
                            </div>
                        </div>
                        <form id="create-snapshot-form" method="POST" action="{{ route('pages.versions.create', $page->id) }}" class="d-none">
                            @csrf
                        </form>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ $versions->count() }}</h4>
                                <small class="text-muted">Versiones guardadas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Publicadas</h6>
                                <h4 class="mb-1 fw-bold">{{ $versions->where('status', 'published')->count() }}</h4>
                                <small class="text-muted">Con estado publicado</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Borradores</h6>
                                <h4 class="mb-1 fw-bold">{{ $versions->where('status', 'draft')->count() }}</h4>
                                <small class="text-muted">En estado borrador</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Última versión</h6>
                                <h4 class="mb-1 fw-bold">{{ $versions->isNotEmpty() ? 'v'.$versions->first()->version_number : '-' }}</h4>
                                <small class="text-muted">Versión más reciente</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($versions->isEmpty())
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <h6 class="mb-1">No hay versiones disponibles</h6>
                            <p class="text-muted mb-3">Crea un snapshot para guardar el estado actual de la página</p>
                            <form method="POST" action="{{ route('pages.versions.create', $page->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-primary">
                                    Crear primer snapshot
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>Versión</th>
                                    <th>Título</th>
                                    <th class="text-center">Estado</th>
                                    <th>Autor</th>
                                    <th>Fecha</th>
                                    <th class="text-center">Tamaño</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($versions as $version)
                                    <tr>
                                        <td>
                                            @if(!$loop->first)
                                                <input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $version->id }}">
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge bg-primary-subtle text-primary">v{{ $version->version_number }}</span>
                                                @if($loop->first)
                                                    <span class="badge bg-success-subtle text-success">Actual</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <a href="{{ route('pages.versions.show', [$page->id, $version->id]) }}" class="text-decoration-none fw-semibold">
                                                {{ Str::limit($version->title, 50) }}
                                            </a>
                                        </td>
                                        <td class="text-center">
                                            @if($version->status === 'published')
                                                <span class="badge bg-success-subtle text-success">Publicado</span>
                                            @elseif($version->status === 'draft')
                                                <span class="badge bg-secondary-subtle text-secondary">Borrador</span>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning">Pendiente</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($version->user)
                                                {{ $version->user->full_name ?? $version->user->name }}
                                            @else
                                                <span class="text-muted">Sistema</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $version->created_at->format('d/m/Y H:i') }}
                                            <small class="d-block text-muted">{{ $version->created_at->diffForHumans() }}</small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark">
                                                {{ number_format($version->getContentSize() / 1024, 2) }} KB
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-auto-close="true" data-bs-boundary="viewport">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a href="{{ route('pages.versions.show', [$page->id, $version->id]) }}" class="dropdown-item">
                                                            Ver versión
                                                        </a>
                                                    </li>
                                                    @if(!$loop->first)
                                                        <li>
                                                            <button type="button" class="dropdown-item restore-btn"
                                                                data-url="{{ route('pages.versions.restore', [$page->id, $version->id]) }}"
                                                                data-version="v{{ $version->version_number }}">
                                                                Restaurar
                                                            </button>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <button type="button" class="dropdown-item delete-version-btn"
                                                                data-url="{{ route('pages.versions.destroy', [$page->id, $version->id]) }}"
                                                                data-version="v{{ $version->version_number }}">
                                                                Eliminar
                                                            </button>
                                                        </li>
                                                    @endif
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

        </div>

    </div>

    {{-- Forms ocultos para acciones POST --}}
    <form id="restore-form" method="POST" class="d-none">
        @csrf
    </form>
    <form id="delete-version-form" method="POST" class="d-none">
        @csrf
        @method('DELETE')
    </form>

    {{-- Bulk toolbar flotante --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none bulk-toolbar-float">
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
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> versión(es)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
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

    {{-- Modal comparar versiones --}}
    @if($versions->count() >= 2)
    <div class="modal fade" id="compareModal" tabindex="-1" aria-labelledby="compareModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="compareModalLabel">Comparar versiones</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form method="GET" action="{{ route('pages.versions.compare', $page->id) }}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="version1" class="form-label fw-semibold">Versión 1 (antigua)</label>
                            <select class="form-select select2" id="version1" name="version1" required>
                                <option value="">Seleccionar versión...</option>
                                @foreach($versions as $version)
                                    <option value="{{ $version->id }}">
                                        v{{ $version->version_number }} — {{ $version->title }} ({{ $version->created_at->format('d/m/Y H:i') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-0">
                            <label for="version2" class="form-label fw-semibold">Versión 2 (nueva)</label>
                            <select class="form-select select2" id="version2" name="version2" required>
                                <option value="">Seleccionar versión...</option>
                                @foreach($versions as $version)
                                    <option value="{{ $version->id }}">
                                        v{{ $version->version_number }} — {{ $version->title }} ({{ $version->created_at->format('d/m/Y H:i') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Comparar</button>
                        <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal confirmar restaurar --}}
    <div class="modal fade" id="modal-restore-version" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Restaurar versión</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    ¿Restaurar <strong id="confirm-restore-version"></strong>? La versión actual se guardará automáticamente.
                </div>
                <div class="modal-footer flex-column">
                    <button type="button" id="btn-confirm-restore" class="btn btn-primary w-100 mb-2">Restaurar</button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal confirmar eliminar versión --}}
    <div class="modal fade" id="modal-delete-version" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Eliminar versión</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    ¿Eliminar <strong id="confirm-delete-version"></strong>? Esta acción no se puede deshacer.
                </div>
                <div class="modal-footer flex-column">
                    <button type="button" id="btn-confirm-delete-version" class="btn btn-primary w-100 mb-2">Eliminar</button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal confirmar bulk delete --}}
    <div class="modal fade" id="modal-bulk-delete-confirm" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    ¿Eliminar las <strong id="modal-bulk-delete-confirm-count">0</strong> versión(es) seleccionadas? Esta acción no se puede deshacer.
                </div>
                <div class="modal-footer flex-column">
                    <button type="button" id="btn-confirm-bulk-delete" class="btn btn-primary w-100 mb-2">Eliminar</button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {

    // Select2 en modal de comparación
    $('#compareModal').on('shown.bs.modal', function () {
        $('#version1, #version2').select2({
            dropdownParent: $('#compareModal'),
            width: '100%',
            placeholder: 'Seleccionar versión...',
            allowClear: true,
        });
    });

    // Restaurar versión
    $(document).on('click', '.restore-btn', function () {
        const url = $(this).data('url');
        const version = $(this).data('version');
        $('#confirm-restore-version').text(version);
        $('#btn-confirm-restore').off('click').on('click', function () {
            $('#modal-restore-version').modal('hide');
            $('#restore-form').attr('action', url).submit();
        });
        $('#modal-restore-version').modal('show');
    });

    // Eliminar versión
    $(document).on('click', '.delete-version-btn', function () {
        const url = $(this).data('url');
        const version = $(this).data('version');
        $('#confirm-delete-version').text(version);
        $('#btn-confirm-delete-version').off('click').on('click', function () {
            $('#modal-delete-version').modal('hide');
            $('#delete-version-form').attr('action', url).submit();
        });
        $('#modal-delete-version').modal('show');
    });

    // Bulk actions
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
        if (!ids.length) { toastr.warning('Selecciona al menos una versión.'); return; }
        if (action === 'delete') {
            if (!window._bulkDeleteConfirmed) {
                $('#modal-bulk-delete-confirm-count').text(ids.length);
                $('#btn-confirm-bulk-delete').off('click').on('click', function () {
                    $('#modal-bulk-delete-confirm').modal('hide');
                    window._bulkDeleteConfirmed = true;
                    $('#bulk-apply-btn').trigger('click');
                });
                $('#modal-bulk-delete-confirm').modal('show');
                return;
            }
            window._bulkDeleteConfirmed = false;
        }

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route('pages.versions.bulk-action', $page->id) }}',
            method: 'POST',
            data: JSON.stringify({ action: action, ids: ids, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.message || res.count + ' versión(es) eliminadas.');
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
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

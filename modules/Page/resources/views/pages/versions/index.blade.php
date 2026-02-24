@extends('layouts.theme')

@section('page_title', 'Versiones')

@section('content')

    @include('core::components.card', ['title' => 'Versiones'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Versiones de "{{ $page->title }}"</h5>
                        <p class="small mb-0 text-muted">Historial de cambios y snapshots de la página</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('pages.edit', $page->id) }}" class="btn btn-secondary">
                            Volver
                        </a>
                        @if($versions->count() >= 2)
                            <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#compareModal">
                                Comparar versiones
                            </button>
                        @endif
                        <form method="POST" action="{{ route('pages.versions.create', $page->id) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-primary">
                                Crear snapshot
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="card-body">
                @if($versions->isEmpty())
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-history fs-7"></i>
                            </div>
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
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
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
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge bg-primary-subtle text-primary">
                                                    v{{ $version->version_number }}
                                                </span>
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
                                            <span class="badge bg-light-subtle text-black">
                                                {{ number_format($version->getContentSize() / 1024, 2) }} KB
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
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
    <form id="restore-form" method="POST" style="display:none">
        @csrf
    </form>
    <form id="delete-version-form" method="POST" style="display:none">
        @csrf
        @method('DELETE')
    </form>

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
                        <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary  w-100">
                            Comparar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

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
        if (!confirm('¿Restaurar ' + version + '? La versión actual se guardará automáticamente.')) return;
        $('#restore-form').attr('action', url).submit();
    });

    // Eliminar versión
    $(document).on('click', '.delete-version-btn', function () {
        const url = $(this).data('url');
        const version = $(this).data('version');
        if (!confirm('¿Eliminar ' + version + '? Esta acción no se puede deshacer.')) return;
        $('#delete-version-form').attr('action', url).submit();
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

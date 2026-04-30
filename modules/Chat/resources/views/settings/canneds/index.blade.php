@extends('layouts.theme')

@section('title', 'Respuestas predefinidas')

@section('content')

@include('core::components.alerts')

<div class="card">
    <!-- Header Section -->
    <div class="card-header p-4 border-bottom border-light">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1 fw-bold">Respuestas predefinidas</h5>
                <p class="small mb-0 text-muted">Plantillas de respuestas rápidas para conversaciones comunes con sustitución de variables</p>
            </div>
            <div class="d-flex gap-2">
                @if(request('search'))
                    <a href="{{ route('settings.chat.canneds.index') }}" class="btn btn-secondary">
                        Limpiar búsqueda
                    </a>
                @endif
                <a href="{{ route('settings.chat.canneds.create') }}" class="btn btn-primary">
                    Nueva respuesta
                </a>
            </div>
        </div>
    </div>

    <!-- Search Section -->
    <div class="card-body border-bottom">
        <form method="GET" action="{{ route('settings.chat.canneds.index') }}">
            <div class="row align-items-center g-2">
                <div class="col-md-9">
                    <div class="input-group">
                        <span class="input-group-text bg-white">
                            <i class="fa fa-magnifying-glass"></i>
                        </span>
                        <input type="search" name="search" class="form-control"
                               placeholder="Buscar por código, contenido..."
                               value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        Buscar
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Canned Responses List -->
    <div class="card-body">
        @if($canneds->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Código</th>
                            <th>Contenido</th>
                            <th class="text-center">Fecha creación</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($canneds as $canned)
                            <tr>
                                <td>
                                    <code class="bg-light px-2 py-1 rounded">{{ $canned->short_code }}</code>
                                </td>
                                <td>
                                    <div>
                                        <small class="text-muted">{{ Str::limit($canned->content, 80) }}</small>
                                    </div>
                                </td>
                                <td class="text-center">
                                    {{ $canned->created_at->format('d/m/Y') }}
                                </td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fa fa-ellipsis-vertical"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item"
                                                   href="{{ route('settings.chat.canneds.edit', $canned) }}">
                                                    Editar
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-success delete-btn"
                                                   data-bs-toggle="modal"
                                                   data-bs-target="#delete-modal"
                                                   data-url="{{ route('settings.chat.canneds.destroy', $canned) }}"
                                                   data-title="Eliminar respuesta: {{ $canned->short_code }}">
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
                <div class="d-flex flex-column align-items-center">
                    <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                        <i class="fa fa-comment-dots fs-7"></i>
                    </div>
                    <h6 class="mb-1">No hay respuestas predefinidas para mostrar</h6>
                    <p class="text-muted mb-3">
                        @if(request('search'))
                            No se encontraron resultados para "{{ request('search') }}"
                        @else
                            Crea tu primera respuesta predefinida para ahorrar tiempo al responder preguntas comunes
                        @endif
                    </p>
                    @if(!request('search'))
                        <a href="{{ route('settings.chat.canneds.create') }}" class="btn btn-sm btn-primary">
                            <i class="fa fa-plus"></i> Crear primera respuesta
                        </a>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <!-- Pagination -->
    @if($canneds->hasPages())
        <div class="card-footer bg-white border-top">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Mostrando <strong>{{ $canneds->firstItem() }}</strong> a <strong>{{ $canneds->lastItem() }}</strong>
                    de <strong>{{ $canneds->total() }}</strong> respuestas
                </div>
                <nav aria-label="Page navigation">
                    {{ $canneds->links() }}
                </nav>
            </div>
        </div>
    @endif
</div>

@include('core::components.delete')

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.delete-btn').on('click', function() {
        const deleteUrl = $(this).data('url');
        const deleteTitle = $(this).data('title');

        $('#delete-modal .modal-title').text(deleteTitle);
        $('#delete-form').attr('action', deleteUrl);
    });

    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush

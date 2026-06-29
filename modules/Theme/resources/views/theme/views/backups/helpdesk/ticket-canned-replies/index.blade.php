@extends('layouts.theme')

@section('title', 'Respuestas predefinidas')

@section('page_header')
    @include('core::components.card', ['title' => 'Respuestas predefinidas'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Respuestas predefinidas</h5>
                        <p class="small mb-0 text-muted">Respuestas rapidas reutilizables para los agentes</p>
                    </div>
                    <div class="ms-auto">
                        <a href="{{ route('manager.helpdesk.settings.ticket-canned-replies.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nueva respuesta
                        </a>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['total']) }}</h4>
                                <small class="text-muted">Respuestas registradas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Activas</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['active']) }}</h4>
                                <small class="text-muted">Disponibles para usar</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Inactivas</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['inactive'] ?? ($stats['total'] - $stats['active'])) }}</h4>
                                <small class="text-muted">Ocultas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Globales</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['global']) }}</h4>
                                <small class="text-muted">Compartidas con el equipo</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('manager.helpdesk.settings.ticket-canned-replies.index') }}" id="filterForm">
                    <div class="d-flex gap-2 align-items-center">
                        <div class="flex-fill">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control -0 ps-0"
                                       placeholder="Buscar por titulo, atajo o contenido..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary flex-shrink-0">
                            <i class="fas fa-search"></i>
                        </button>
                        @if(request('search'))
                            <a href="{{ route('manager.helpdesk.settings.ticket-canned-replies.index') }}"
                               class="btn btn-outline-secondary flex-shrink-0" title="Limpiar">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($replies->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="replies-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Titulo</th>
                                    <th>Categoria</th>
                                    <th>Tags</th>
                                    <th class="text-center">Uso</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($replies as $reply)
                                    <tr>
                                        <td>
                                            <span class="fw-semibold">{{ $reply->title }}</span>
                                            @if($reply->short_code)
                                                <div class="mt-1">
                                                    <code class="bg-light px-2 py-1 rounded small">{{ $reply->short_code }}</code>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $reply->category ?: '—' }}</small>
                                        </td>
                                        <td>
                                            @if($reply->tags && count($reply->tags))
                                                <div class="d-flex flex-wrap gap-1">
                                                    @foreach($reply->tags as $tag)
                                                        <span class="badge bg-light text-secondary border small">{{ $tag }}</span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <small class="text-muted">{{ number_format($reply->usage_count ?? 0) }}</small>
                                        </td>
                                        <td class="text-center">
                                            @if($reply->is_active)
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
                                                        <a class="dropdown-item" href="{{ route('manager.helpdesk.settings.ticket-canned-replies.edit', $reply->id) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn" href="#"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#delete-modal"
                                                           data-url="{{ route('manager.helpdesk.settings.ticket-canned-replies.destroy', $reply->id) }}"
                                                           data-title="Eliminar respuesta: {{ $reply->title }}">
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
                        <i class="fas fa-comment-dots fa-3x mb-3 text-muted opacity-50"></i>
                        <h5 class="fw-bold mb-2">
                            @if(request('search'))
                                No se encontraron resultados
                            @else
                                No hay respuestas predefinidas
                            @endif
                        </h5>
                        <p class="text-muted mb-4">
                            @if(request('search'))
                                No hay resultados para "{{ request('search') }}"
                            @else
                                Aun no hay respuestas creadas
                            @endif
                        </p>
                        @if(request('search'))
                            <a href="{{ route('manager.helpdesk.settings.ticket-canned-replies.index') }}" class="btn btn-secondary">Limpiar filtros</a>
                        @else
                            <a href="{{ route('manager.helpdesk.settings.ticket-canned-replies.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Nueva respuesta
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Pagination --}}
            @if($replies->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando {{ $replies->firstItem() }} - {{ $replies->lastItem() }} de {{ $replies->total() }}
                        </div>
                        <div>
                            {{ $replies->appends(request()->input())->links() }}
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    @include('core::components.delete')

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    $(document).on('click', '.delete-btn', function () {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });
});
</script>
@endpush

@extends('layouts.theme')

@section('title', 'Banners')

@section('page_header')
    @include('core::components.card', ['title' => 'Banners'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Banners configurados</h5>
                        <p class="small mb-0 text-muted">Mensajes informativos mostrados a los clientes en el widget de chat</p>
                    </div>
                    <div class="ms-auto">
                        <a href="{{ route('settings.helpdesk.banners.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nuevo banner
                        </a>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-6 col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['total']) }}</h4>
                                <small class="text-muted">Banners registrados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Activos</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['active']) }}</h4>
                                <small class="text-muted">Visibles ahora</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Programados</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['scheduled']) }}</h4>
                                <small class="text-muted">Con fecha de inicio futura</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.helpdesk.banners.index') }}">
                    <div class="d-flex gap-2 align-items-center">
                        <div class="flex-fill">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control border-start-0 ps-0"
                                       placeholder="Buscar por titulo..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <select name="type" class="form-select w-auto flex-shrink-0">
                            <option value="">Todos los tipos</option>
                            <option value="info" {{ request('type') === 'info' ? 'selected' : '' }}>Info</option>
                            <option value="success" {{ request('type') === 'success' ? 'selected' : '' }}>Exito</option>
                            <option value="warning" {{ request('type') === 'warning' ? 'selected' : '' }}>Advertencia</option>
                            <option value="danger" {{ request('type') === 'danger' ? 'selected' : '' }}>Peligro</option>
                        </select>
                        <button type="submit" class="btn btn-primary flex-shrink-0">
                            <i class="fas fa-search"></i>
                        </button>
                        @if(request()->hasAny(['search', 'type']))
                            <a href="{{ route('settings.helpdesk.banners.index') }}"
                               class="btn btn-outline-secondary flex-shrink-0" title="Limpiar">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($banners->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th>Titulo</th>
                                    <th>Tipo</th>
                                    <th>Vigencia</th>
                                    <th class="text-center">Cerrable</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($banners as $banner)
                                    @php
                                        $typeColors = ['info' => 'primary', 'success' => 'success', 'warning' => 'warning', 'danger' => 'danger'];
                                        $typeLabels = ['info' => 'Info', 'success' => 'Exito', 'warning' => 'Advertencia', 'danger' => 'Peligro'];
                                        $badgeColor = $typeColors[$banner->type] ?? 'secondary';
                                        $typeLabel = $typeLabels[$banner->type] ?? $banner->type;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $banner->title }}</div>
                                            @if($banner->cta_text)
                                                <small class="text-muted">CTA: {{ $banner->cta_text }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $badgeColor }}-subtle text-{{ $badgeColor }}">
                                                {{ $typeLabel }}
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                @if($banner->starts_at || $banner->ends_at)
                                                    {{ $banner->starts_at ? $banner->starts_at->format('d/m/Y') : '—' }}
                                                    →
                                                    {{ $banner->ends_at ? $banner->ends_at->format('d/m/Y') : '—' }}
                                                @else
                                                    Siempre
                                                @endif
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            @if($banner->dismissible)
                                                <span class="badge bg-success-subtle text-success">Si</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">No</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($banner->is_active)
                                                <span class="badge bg-success-subtle text-success">Activo</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Inactivo</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.helpdesk.banners.edit', $banner->id) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn" href="#"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#delete-modal"
                                                           data-url="{{ route('settings.helpdesk.banners.destroy', $banner->id) }}"
                                                           data-title="Eliminar banner: {{ $banner->title }}">
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
                        <i class="fas fa-bullhorn fa-3x mb-3 text-muted opacity-50"></i>
                        <h5 class="fw-bold mb-2">
                            @if(request()->hasAny(['search', 'type']))
                                No se encontraron resultados
                            @else
                                No hay banners configurados
                            @endif
                        </h5>
                        <p class="text-muted mb-4">
                            @if(request()->hasAny(['search', 'type']))
                                Intenta con otros filtros de busqueda
                            @else
                                Crea tu primer banner para mostrar mensajes a tus clientes
                            @endif
                        </p>
                        @if(request()->hasAny(['search', 'type']))
                            <a href="{{ route('settings.helpdesk.banners.index') }}" class="btn btn-secondary">Limpiar filtros</a>
                        @else
                            <a href="{{ route('settings.helpdesk.banners.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Nuevo banner
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Pagination --}}
            @if($banners->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando {{ $banners->firstItem() }} - {{ $banners->lastItem() }} de {{ $banners->total() }}
                        </div>
                        <div>
                            {{ $banners->appends(request()->input())->links() }}
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

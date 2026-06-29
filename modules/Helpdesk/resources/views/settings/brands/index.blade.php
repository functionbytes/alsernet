@extends('layouts.theme')

@section('title', 'Marcas')

@push('styles')
<style>.hd-color-swatch { width: 24px; height: 24px; display: inline-block; }</style>
@endpush

@section('content')

    @include('core::components.alerts')

    <div class="card">

        {{-- Header --}}
        <div class="card-header p-4 border-bottom border-light">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Marcas</h5>
                    <p class="small mb-0 text-muted">Gestiona las marcas del helpdesk, cada una con su propia identidad visual y configuracion de correo</p>
                </div>
                @can('helpdesk.brands.manage')
                    <a href="{{ route('settings.helpdesk.brands.create') }}" class="btn btn-primary">
                        Nueva marca
                    </a>
                @endcan
            </div>
        </div>

        {{-- Stats --}}
        <div class="card-body border-bottom">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-primary mb-2">Total</h6>
                            <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                            <small class="text-muted">Marcas configuradas</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-success mb-2">Activas</h6>
                            <h4 class="mb-1 fw-bold">{{ $stats['active'] }}</h4>
                            <small class="text-muted">Marcas en uso</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-warning mb-2">Con bandejas</h6>
                            <h4 class="mb-1 fw-bold">{{ $stats['with_inboxes'] }}</h4>
                            <small class="text-muted">Tienen inboxes asignados</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Search --}}
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('settings.helpdesk.brands.index') }}">
                <div class="row align-items-center g-2">
                    <div class="col-md-7">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="search" name="search" class="form-control"
                                placeholder="Buscar por nombre, slug, dominio o correo..."
                                value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">Cualquier estado</option>
                            <option value="active" @selected(request('status') === 'active')>Activas</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>Inactivas</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="card-body">
            @if($brands->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nombre</th>
                                <th>Dominio</th>
                                <th class="text-center">Color</th>
                                <th>Correo</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Inboxes</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($brands as $brand)
                                <tr>
                                    <td>
                                        <strong>{{ $brand->name }}</strong>
                                        <div><small class="text-muted">{{ $brand->slug }}</small></div>
                                    </td>
                                    <td>
                                        @if($brand->domain)
                                            <span class="small">{{ $brand->domain }}</span>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($brand->primary_color)
                                            <span class="hd-color-swatch rounded border"
                                                data-color="{{ $brand->primary_color }}"
                                                title="{{ $brand->primary_color }}">
                                            </span>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($brand->email_from_address)
                                            <span class="small">{{ $brand->email_from_address }}</span>
                                            @if($brand->email_from_name)
                                                <div><small class="text-muted">{{ $brand->email_from_name }}</small></div>
                                            @endif
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($brand->is_active)
                                            <span class="badge bg-success-subtle text-success">Activa</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Inactiva</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-semibold">{{ $brand->inboxes_count }}</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-vertical"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                @can('helpdesk.brands.manage')
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.helpdesk.brands.edit', $brand) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <form action="{{ route('settings.helpdesk.brands.toggle', $brand) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="dropdown-item">
                                                                {{ $brand->is_active ? 'Desactivar' : 'Activar' }}
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button class="dropdown-item btn-delete"
                                                            data-id="{{ $brand->id }}"
                                                            data-url="{{ route('settings.helpdesk.brands.destroy', $brand) }}"
                                                            data-name="{{ $brand->name }}">
                                                            Eliminar
                                                        </button>
                                                    </li>
                                                @endcan
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
                            <i class="fas fa-building fs-7"></i>
                        </div>
                        <h6 class="mb-1">No hay marcas configuradas</h6>
                        <p class="text-muted mb-3">
                            @if(request('search') || request('status'))
                                No se encontraron resultados para los filtros aplicados
                            @else
                                Crea tu primera marca para personalizar la identidad del helpdesk
                            @endif
                        </p>
                        @if(! request('search') && ! request('status'))
                            @can('helpdesk.brands.manage')
                                <a href="{{ route('settings.helpdesk.brands.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Crear primera marca
                                </a>
                            @endcan
                        @endif
                    </div>
                </div>
            @endif
        </div>

        @if($brands->hasPages())
            <div class="card-footer bg-white border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted">
                        Mostrando <strong>{{ $brands->firstItem() }}</strong> a <strong>{{ $brands->lastItem() }}</strong>
                        de <strong>{{ $brands->total() }}</strong> marcas
                    </div>
                    {{ $brands->appends(request()->input())->links() }}
                </div>
            </div>
        @endif

    </div>

    @include('core::components.delete')

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('.hd-color-swatch[data-color]').each(function () {
        $(this).css('background-color', $(this).data('color'));
    });

    $(document).on('click', '.btn-delete', function () {
        const url = $(this).data('url');
        const name = $(this).data('name');
        $('#deleteForm').attr('action', url);
        $('#deleteItemName').text(name);
        $('#deleteModal').modal('show');
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif

    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush

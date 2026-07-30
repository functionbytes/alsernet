@extends('layouts.theme')

@section('title', 'Empresas')

@push('styles')
<style>.hd-website-link { max-width: 160px; }</style>
@endpush

@section('page_header')
    @include('core::components.card', ['title' => 'Empresas'])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="card">

        {{-- Header --}}
        <div class="card-header p-4 border-bottom border-light">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Empresas</h5>
                    <p class="small mb-0 text-muted">Gestiona las empresas y organizaciones asociadas a tus clientes</p>
                </div>
                @can('helpdesk.companies.manage')
                    <a href="{{ route('settings.helpdesk.companies.create') }}" class="btn btn-primary">
                        Nueva empresa
                    </a>
                @endcan
            </div>
        </div>

        {{-- Stats --}}
        <div class="card-body border-bottom">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-primary mb-2">Total</h6>
                            <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                            <small class="text-muted">Empresas registradas</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-success mb-2">Saludables</h6>
                            <h4 class="mb-1 fw-bold">{{ $stats['healthy'] }}</h4>
                            <small class="text-muted">Health score &ge; 80</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-danger mb-2">En riesgo</h6>
                            <h4 class="mb-1 fw-bold">{{ $stats['at_risk'] }}</h4>
                            <small class="text-muted">Health score &lt; 50</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-warning mb-2">Con clientes</h6>
                            <h4 class="mb-1 fw-bold">{{ $stats['with_customers'] }}</h4>
                            <small class="text-muted">Tienen clientes asignados</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Search --}}
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('settings.helpdesk.companies.index') }}">
                <div class="row align-items-center g-2">
                    <div class="col-md-10">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="search" name="search" class="form-control"
                                placeholder="Buscar por nombre, dominio o industria..."
                                value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="card-body">
            @if($companies->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Empresa</th>
                                <th scope="col">Industria</th>
                                <th scope="col">Tamaño</th>
                                <th scope="col" class="text-center">Clientes</th>
                                <th scope="col" class="text-center">Health score</th>
                                <th scope="col">Sitio web</th>
                                <th scope="col" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($companies as $company)
                                <tr>
                                    <td>
                                        <strong>{{ $company->name }}</strong>
                                        @if($company->domain)
                                            <div>
                                                <small class="text-muted">{{ $company->domain }}</small>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($company->industry)
                                            <span class="badge bg-secondary-subtle text-secondary">{{ $company->industry }}</span>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($company->size)
                                            {{ \Modules\Helpdesk\Models\Company::SIZES[$company->size] ?? $company->size }}
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-semibold">{{ number_format($company->customers_count) }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if($company->health_score !== null)
                                            <span class="badge bg-{{ $company->getHealthColor() }}-subtle text-{{ $company->getHealthColor() }}">
                                                {{ $company->health_score }} — {{ $company->getHealthLabel() }}
                                            </span>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($company->website)
                                            <a href="{{ safe_external_url($company->website) }}" target="_blank" rel="noopener noreferrer"
                                                class="text-truncate d-inline-block hd-website-link"
                                                title="{{ $company->website }}">
                                                {{ $company->website }}
                                            </a>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-vertical"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                @can('helpdesk.companies.manage')
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.helpdesk.companies.edit', $company) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button class="dropdown-item btn-delete"
                                                            data-id="{{ $company->id }}"
                                                            data-url="{{ route('settings.helpdesk.companies.destroy', $company) }}"
                                                            data-name="{{ $company->name }}">
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
                        <h6 class="mb-1">No hay empresas registradas</h6>
                        <p class="text-muted mb-3">
                            @if(request('search'))
                                No se encontraron resultados para "{{ request('search') }}"
                            @else
                                Agrega tu primera empresa para organizar mejor a tus clientes
                            @endif
                        </p>
                        @if(! request('search'))
                            @can('helpdesk.companies.manage')
                                <a href="{{ route('settings.helpdesk.companies.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Crear primera empresa
                                </a>
                            @endcan
                        @endif
                    </div>
                </div>
            @endif
        </div>

        @if($companies->hasPages())
            <div class="card-footer bg-white border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted">
                        Mostrando <strong>{{ $companies->firstItem() }}</strong> a <strong>{{ $companies->lastItem() }}</strong>
                        de <strong>{{ $companies->total() }}</strong> empresas
                    </div>
                    {{ $companies->appends(request()->input())->links() }}
                </div>
            </div>
        @endif

    </div>

    @include('core::components.delete')

@endsection

@push('scripts')
<script>
$(document).ready(function () {
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

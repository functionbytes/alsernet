@extends('layouts.theme')

@section('title', 'Skills')

@section('content')

    @include('core::components.alerts')

    <div class="card">

        {{-- Header --}}
        <div class="card-header p-4 border-bottom border-light">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Skills</h5>
                    <p class="small mb-0 text-muted">Habilidades que pueden asignarse a los agentes para el enrutamiento automatico de conversaciones</p>
                </div>
                @can('helpdesk.skills.manage')
                    <a href="{{ route('settings.helpdesk.skills.create') }}" class="btn btn-primary">
                        Nuevo skill
                    </a>
                @endcan
            </div>
        </div>

        {{-- Stats --}}
        <div class="card-body border-bottom">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-primary mb-2">Total</h6>
                            <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                            <small class="text-muted">Skills configurados</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-success mb-2">Con agentes asignados</h6>
                            <h4 class="mb-1 fw-bold">{{ $stats['withAgents'] }}</h4>
                            <small class="text-muted">Skills con al menos un agente</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Search --}}
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('settings.helpdesk.skills.index') }}">
                <div class="row align-items-center g-2">
                    <div class="col-md-10">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="search" name="search" class="form-control"
                                placeholder="Buscar por nombre o slug..."
                                value="{{ request('search') }}">
                        </div>
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
            @if($skills->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nombre</th>
                                <th>Descripcion</th>
                                <th class="text-center">Agentes</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($skills as $skill)
                                <tr>
                                    <td>
                                        <strong>{{ $skill->name }}</strong>
                                        <div>
                                            <code class="small text-muted">{{ $skill->slug }}</code>
                                        </div>
                                    </td>
                                    <td>
                                        @if($skill->description)
                                            <span class="text-muted small">{{ Str::limit($skill->description, 60) }}</span>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary-subtle text-secondary">
                                            {{ $skill->users_count ?? 0 }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-vertical"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                @can('helpdesk.skills.manage')
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.helpdesk.skills.edit', $skill) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn" href="#"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#delete-modal"
                                                            data-url="{{ route('settings.helpdesk.skills.destroy', $skill) }}"
                                                            data-title="Eliminar skill: {{ $skill->name }}">
                                                            Eliminar
                                                        </a>
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
                            <i class="fas fa-star fs-7"></i>
                        </div>
                        <h6 class="mb-1">No hay skills configurados</h6>
                        <p class="text-muted mb-3">
                            @if(request('search'))
                                No se encontraron resultados para "{{ request('search') }}"
                            @else
                                Crea el primer skill para comenzar a asignarlo a los agentes
                            @endif
                        </p>
                        @if(! request('search'))
                            @can('helpdesk.skills.manage')
                                <a href="{{ route('settings.helpdesk.skills.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Crear primer skill
                                </a>
                            @endcan
                        @endif
                    </div>
                </div>
            @endif
        </div>

        @if($skills->hasPages())
            <div class="card-footer bg-white border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted">
                        Mostrando <strong>{{ $skills->firstItem() }}</strong> a <strong>{{ $skills->lastItem() }}</strong>
                        de <strong>{{ $skills->total() }}</strong> skills
                    </div>
                    {{ $skills->appends(request()->input())->links() }}
                </div>
            </div>
        @endif

    </div>

    @include('core::components.delete')

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $(document).on('click', '.delete-btn', function () {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
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

@extends('layouts.theme')

@section('title', 'Templates de WhatsApp')

@section('page_header')
    @include('core::components.card', ['title' => 'Templates de WhatsApp'])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="card">

        {{-- Header --}}
        <div class="card-header p-4 border-bottom border-light">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Templates de WhatsApp</h5>
                    <p class="small mb-0 text-muted">Templates aprobados por Meta disponibles para enviar en campanas de WhatsApp</p>
                </div>
                @can('helpdesk.whatsapp-templates.manage')
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.helpdesk.whatsapp-templates.create') }}" class="btn btn-outline-primary">
                            <i class="fas fa-plus"></i> Nueva plantilla
                        </a>
                        <form action="{{ route('settings.helpdesk.whatsapp-templates.sync') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-rotate"></i> Sincronizar desde Meta
                            </button>
                        </form>
                    </div>
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
                            <small class="text-muted">Templates registrados</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-success mb-2">Aprobados</h6>
                            <h4 class="mb-1 fw-bold">{{ $stats['approved'] }}</h4>
                            <small class="text-muted">Listos para enviar</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-warning mb-2">Pendientes</h6>
                            <h4 class="mb-1 fw-bold">{{ $stats['pending'] }}</h4>
                            <small class="text-muted">En revision por Meta</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-danger mb-2">Rechazados</h6>
                            <h4 class="mb-1 fw-bold">{{ $stats['rejected'] }}</h4>
                            <small class="text-muted">No aprobados por Meta</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('settings.helpdesk.whatsapp-templates.index') }}">
                <div class="row align-items-center g-2">
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="search" name="search" class="form-control"
                                placeholder="Buscar por nombre, ID o contenido..."
                                value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">Cualquier estado</option>
                            <option value="approved" @selected(request('status') === 'approved')>Aprobado</option>
                            <option value="pending" @selected(request('status') === 'pending')>Pendiente</option>
                            <option value="rejected" @selected(request('status') === 'rejected')>Rechazado</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="category" class="form-select">
                            <option value="">Cualquier categoria</option>
                            <option value="utility" @selected(request('category') === 'utility')>Utilidad</option>
                            <option value="marketing" @selected(request('category') === 'marketing')>Marketing</option>
                            <option value="authentication" @selected(request('category') === 'authentication')>Autenticacion</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary w-100" aria-label="Filtrar">
                            <i class="fas fa-filter"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="card-body">
            @if($templates->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Nombre</th>
                                <th scope="col" class="text-center">Categoria</th>
                                <th scope="col" class="text-center">Idioma</th>
                                <th scope="col" class="text-center">Estado</th>
                                <th scope="col" class="text-center">Parametros</th>
                                <th scope="col">Preview del cuerpo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($templates as $template)
                                <tr>
                                    <td>
                                        <strong>{{ $template->display_name }}</strong>
                                        @if($template->external_id)
                                            <div><small class="text-muted">{{ $template->external_id }}</small></div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @switch($template->category)
                                            @case('utility')
                                                <span class="badge bg-primary-subtle text-primary">Utilidad</span>
                                                @break
                                            @case('marketing')
                                                <span class="badge bg-info-subtle text-info">Marketing</span>
                                                @break
                                            @case('authentication')
                                                <span class="badge bg-secondary-subtle text-secondary">Autenticacion</span>
                                                @break
                                            @default
                                                <span class="badge bg-light text-muted">{{ $template->category }}</span>
                                        @endswitch
                                    </td>
                                    <td class="text-center">
                                        <span class="small">{{ strtoupper($template->language) }}</span>
                                    </td>
                                    <td class="text-center">
                                        @switch($template->status)
                                            @case('approved')
                                                <span class="badge bg-success-subtle text-success">Aprobado</span>
                                                @break
                                            @case('pending')
                                                <span class="badge bg-warning-subtle text-warning">Pendiente</span>
                                                @break
                                            @case('rejected')
                                                <span class="badge bg-danger-subtle text-danger">Rechazado</span>
                                                @break
                                            @default
                                                <span class="badge bg-light text-muted">{{ $template->status }}</span>
                                        @endswitch
                                    </td>
                                    <td class="text-center">
                                        @if($template->param_count > 0)
                                            <span class="badge bg-light text-dark">{{ $template->param_count }}</span>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($template->body_template)
                                            <small class="text-muted">{{ Str::limit($template->body_template, 80) }}</small>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
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
                            <i class="fab fa-whatsapp fs-7"></i>
                        </div>
                        <h6 class="mb-1">No hay templates sincronizados</h6>
                        <p class="text-muted mb-3">
                            @if(request('search') || request('status') || request('category'))
                                No se encontraron resultados para los filtros aplicados
                            @else
                                Haz clic en "Sincronizar desde Meta" para importar tus templates aprobados
                            @endif
                        </p>
                        @if(! request('search') && ! request('status') && ! request('category'))
                            @can('helpdesk.whatsapp-templates.manage')
                                <form action="{{ route('settings.helpdesk.whatsapp-templates.sync') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="fas fa-rotate"></i> Sincronizar desde Meta
                                    </button>
                                </form>
                            @endcan
                        @endif
                    </div>
                </div>
            @endif
        </div>

        @if($templates->hasPages())
            <div class="card-footer bg-white border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted">
                        Mostrando <strong>{{ $templates->firstItem() }}</strong> a <strong>{{ $templates->lastItem() }}</strong>
                        de <strong>{{ $templates->total() }}</strong> templates
                    </div>
                    {{ $templates->appends(request()->input())->links() }}
                </div>
            </div>
        @endif

    </div>

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
});
</script>
@endpush

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
                            <h6 class="card-title  mb-2">Aprobados</h6>
                            <h4 class="mb-1 fw-bold">{{ $stats['approved'] }}</h4>
                            <small class="text-muted">Listos para enviar</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title  mb-2">Pendientes</h6>
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
            @php
                $activeFilterCount = collect(['status', 'category'])->filter(fn ($k) => request($k))->count();
                $hasAnyFilter = $activeFilterCount > 0 || request('search');
                $statusLabels = ['approved' => 'Aprobado', 'pending' => 'Pendiente', 'rejected' => 'Rechazado'];
                $categoryLabels = ['utility' => 'Utilidad', 'marketing' => 'Marketing', 'authentication' => 'Autenticacion'];
            @endphp
            <form id="whatsapp-templates-filter-form" method="GET" action="{{ route('settings.helpdesk.whatsapp-templates.index') }}">
                <input type="hidden" name="status" id="filter-status" value="{{ request('status') }}">
                <input type="hidden" name="category" id="filter-category" value="{{ request('category') }}">

                <div class="d-flex align-items-center gap-2">
                    <input type="search" name="search" class="form-control flex-grow-1"
                           placeholder="Buscar por nombre, ID o contenido..."
                           value="{{ request('search') }}">

                    <button type="button" class="btn btn-secondary position-relative flex-shrink-0"
                            data-bs-toggle="modal" data-bs-target="#whatsapp-templates-filter-modal" title="Filtros avanzados">
                        <i class="fas fa-filter"></i>
                        @if($activeFilterCount > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary wa-filter-badge">{{ $activeFilterCount }}</span>
                        @endif
                    </button>

                    <div class="d-flex gap-1 flex-shrink-0">
                        <button type="submit" class="btn btn-primary" title="Buscar">
                            <i class="fas fa-magnifying-glass"></i>
                        </button>
                        @if($hasAnyFilter)
                            <a href="{{ route('settings.helpdesk.whatsapp-templates.index') }}"
                               class="btn btn-secondary" title="Limpiar filtros">
                                <i class="fas fa-xmark"></i>
                            </a>
                        @endif
                    </div>
                </div>

                @if($activeFilterCount > 0)
                    <div class="d-flex gap-2 flex-wrap mt-4">
                        <div>
                            <h6 class="mb-1">Filtrados:</h6>
                        </div>
                        @if(request('status'))
                            <span class="badge bg-primary-subtle text-primary py-1 px-2">
                                Estado: {{ $statusLabels[request('status')] ?? request('status') }}
                            </span>
                        @endif
                        @if(request('category'))
                            <span class="badge bg-primary-subtle text-primary py-1 px-2">
                                Categoria: {{ $categoryLabels[request('category')] ?? request('category') }}
                            </span>
                        @endif
                    </div>
                @endif
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

    {{-- Filter modal --}}
    <div class="modal fade" id="whatsapp-templates-filter-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filtros avanzados</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Estado</label>
                        <select id="modal-status" class="form-control select2-filter-modal">
                            <option value="">Cualquier estado</option>
                            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Aprobado</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pendiente</option>
                            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rechazado</option>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Categoria</label>
                        <select id="modal-category" class="form-control select2-filter-modal">
                            <option value="">Cualquier categoria</option>
                            <option value="utility" {{ request('category') === 'utility' ? 'selected' : '' }}>Utilidad</option>
                            <option value="marketing" {{ request('category') === 'marketing' ? 'selected' : '' }}>Marketing</option>
                            <option value="authentication" {{ request('category') === 'authentication' ? 'selected' : '' }}>Autenticacion</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="whatsapp-templates-filter-apply-btn" class="btn btn-primary w-100 mb-1">
                        Aplicar filtros
                    </button>
                    <button type="button" id="whatsapp-templates-filter-clear-btn" class="btn btn-secondary w-100">
                        Limpiar
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<style>
    .wa-filter-badge { font-size: .6rem; }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function () {
    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif

    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    $('.select2-filter-modal').select2({ dropdownParent: $('#whatsapp-templates-filter-modal'), width: '100%' });

    $('#whatsapp-templates-filter-apply-btn').on('click', function () {
        $('#filter-status').val($('#modal-status').val());
        $('#filter-category').val($('#modal-category').val());
        $('#whatsapp-templates-filter-modal').modal('hide');
        $('#whatsapp-templates-filter-form').submit();
    });

    $('#whatsapp-templates-filter-clear-btn').on('click', function () {
        $('#modal-status, #modal-category').val(null).trigger('change');
    });
});
</script>
@endpush

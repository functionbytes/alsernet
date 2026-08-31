@extends('layouts.theme')

@section('title', 'Plantillas de ticket')

@section('page_header')
    @include('core::components.card', ['title' => 'Plantillas de ticket'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Plantillas de ticket</h5>
                        <p class="small mb-0 text-muted">Plantillas reutilizables para crear tickets rapidamente — generales (compartidas) y personales</p>
                    </div>
                    <div class="ms-auto">
                        <a href="{{ route('manager.helpdesk.ticket-templates.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nueva plantilla
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
                                <small class="text-muted">Plantillas registradas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Generales</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['general']) }}</h4>
                                <small class="text-muted">Compartidas con todos</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Mis plantillas</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['mine']) }}</h4>
                                <small class="text-muted">Solo visibles para ti</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Activas</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['active']) }}</h4>
                                <small class="text-muted">Disponibles para uso</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabs --}}
            <div class="card-body">
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tpl-general" type="button">
                            Generales
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tpl-mine" type="button">
                            Mis plantillas
                        </button>
                    </li>
                </ul>

                <div class="tab-content">

                    {{-- Generales --}}
                    <div class="tab-pane fade show active" id="tpl-general">
                        @if(! $canManageGeneral)
                            <p class="small text-muted mb-3">Las plantillas generales las gestiona un administrador. Aqui puedes verlas y usarlas al crear un ticket.</p>
                        @endif
                        @include('helpdesktickets::managers.ticket-templates._table', ['templates' => $general, 'canManage' => $canManageGeneral])
                    </div>

                    {{-- Mias --}}
                    <div class="tab-pane fade" id="tpl-mine">
                        <p class="small text-muted mb-3">Solo tu puedes ver, editar o eliminar estas plantillas.</p>
                        @include('helpdesktickets::managers.ticket-templates._table', ['templates' => $mine, 'canManage' => true])
                    </div>

                </div>
            </div>

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

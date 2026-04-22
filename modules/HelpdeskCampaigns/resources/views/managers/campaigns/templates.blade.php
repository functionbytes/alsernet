@extends('layouts.theme')

@section('title', 'Plantillas de campañas')

@section('content')

    @include('core::components.card', ['title' => 'Plantillas de campañas'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Plantillas de campañas</h5>
                        <p class="small mb-0 text-muted">Elige una plantilla base para crear tu campana rapidamente</p>
                    </div>
                    <div class="ms-auto">
                        <a href="{{ route('manager.helpdesk-campaigns.index') }}" class="btn btn-light">
                            Volver a campanas
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body">

                {{-- Blank template --}}
                <div class="mb-4">
                    <div class="card border-dashed bg-light" style="cursor: pointer; border-style: dashed !important;"
                         onclick="showCreateModal('')">
                        <div class="card-body text-center py-5">
                            <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3"
                                 style="width: 72px; height: 72px;">
                                <i class="fas fa-plus fs-3 text-primary"></i>
                            </div>
                            <h5 class="mb-1">Comenzar en blanco</h5>
                            <p class="text-muted mb-0 small">Crea tu campana desde cero con total libertad</p>
                        </div>
                    </div>
                </div>

                {{-- Templates grid --}}
                @if($templates->count())
                    <h6 class="fw-semibold mb-3">Plantillas predisenas</h6>
                    <div class="row g-3">
                        @foreach($templates as $template)
                            <div class="col-md-4">
                                <div class="card h-100" style="cursor: pointer;"
                                     onclick="showCreateModal({{ $template->id }})">
                                    <div class="card-body bg-light-secondary d-flex align-items-center justify-content-center"
                                         style="min-height: 140px;">
                                        @if($template->type === 'popup')
                                            <i class="far fa-window-maximize fa-3x text-muted opacity-50"></i>
                                        @elseif($template->type === 'banner')
                                            <i class="fas fa-rectangle-ad fa-3x text-muted opacity-50"></i>
                                        @elseif($template->type === 'full-screen')
                                            <i class="fas fa-layer-group fa-3x text-muted opacity-50"></i>
                                        @else
                                            <i class="far fa-file-alt fa-3x text-muted opacity-50"></i>
                                        @endif
                                        @if($template->is_premium)
                                            <span class="position-absolute top-0 end-0 m-2 badge bg-warning">
                                                Premium
                                            </span>
                                        @endif
                                    </div>
                                    <div class="card-body">
                                        <h6 class="card-title mb-1">{{ $template->name }}</h6>
                                        <p class="text-muted small mb-3">{{ $template->description }}</p>
                                        <span class="badge bg-light text-dark border">{{ Str::title($template->type) }}</span>
                                    </div>
                                    <div class="card-footer bg-white">
                                        <button type="button" class="btn btn-primary btn-sm w-100"
                                                onclick="event.stopPropagation(); showCreateModal({{ $template->id }})">
                                            Usar esta plantilla
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="far fa-file-alt fa-3x mb-3 text-muted opacity-50"></i>
                        <h5 class="fw-bold mb-2">No hay plantillas disponibles</h5>
                        <p class="text-muted mb-4">Comienza con una campana en blanco y construye desde cero</p>
                        <button type="button" class="btn btn-primary" onclick="showCreateModal('')">
                            <i class="fas fa-plus me-1"></i> Crear campana en blanco
                        </button>
                    </div>
                @endif

            </div>
        </div>
    </div>

    {{-- Create campaign modal --}}
    <div class="modal fade" id="createCampaignModal" tabindex="-1" aria-labelledby="createCampaignModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createCampaignModalLabel">Nueva campana</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form method="POST" action="{{ route('manager.helpdesk-campaigns.store') }}">
                    @csrf
                    <input type="hidden" name="template_id" id="template-id-input">

                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control"
                                   placeholder="Ej: Promocion de verano 2025"
                                   required autofocus>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipo de campana <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" required>
                                <option value="popup">Pop-up — ventana emergente</option>
                                <option value="banner">Banner — barra superior o inferior</option>
                                <option value="slide-in">Slide-in — deslizar desde esquina</option>
                                <option value="full-screen">Pantalla completa — overlay</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripcion</label>
                            <textarea name="description" class="form-control" rows="2"
                                      placeholder="Opcional: describe el proposito de la campana..."></textarea>
                        </div>
                    </div>

                    <div class="modal-footer flex-column">
                        <button type="submit" class="btn btn-primary w-100 mb-2">Crear campana</button>
                        <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
function showCreateModal(templateId) {
    document.getElementById('template-id-input').value = templateId;
    new bootstrap.Modal(document.getElementById('createCampaignModal')).show();
}
</script>
@endpush

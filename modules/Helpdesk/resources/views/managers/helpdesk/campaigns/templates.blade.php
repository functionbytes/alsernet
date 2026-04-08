@extends('layouts.theme')

@section('title', 'Plantillas de Campañas')

@push('styles')
<style>
    .template-card {
        transition: all 0.3s;
        cursor: pointer;
        border: 2px solid transparent;
        height: 100%;
    }

    .template-card:hover {
        border-color: var(--bs-primary);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transform: translateY(-4px);
    }

    .template-preview {
        height: 300px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 8px 8px 0 0;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }

    .template-preview::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg, transparent 40%, rgba(255,255,255,0.1) 50%, transparent 60%);
        background-size: 200% 200%;
    }

    .template-preview-icon {
        font-size: 4rem;
        color: white;
        opacity: 0.9;
    }

    .template-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 1;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-semibold mb-3">
                        <i class="far fa-file-alt"></i> Plantillas de Campañas
                    </h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="{{ route('manager.dashboard') }}">Dashboard</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="{{ route('manager.helpdesk.campaigns.index') }}">Campañas</a>
                            </li>
                            <li class="breadcrumb-item active">Plantillas</li>
                        </ol>
                    </nav>
                </div>

                <a href="{{ route('manager.helpdesk.campaigns.index') }}" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Volver a Campañas
                </a>
            </div>
        </div>
    </div>

    {{-- Info Banner --}}
    <div class="alert alert-primary d-flex align-items-center mb-4" role="alert">
        <i class="fas fa-info-circle fs-4 me-3"></i>
        <div>
            <h6 class="mb-1">Elige una plantilla para tu campaña</h6>
            <p class="mb-0 small">
                Todas las plantillas son 100% personalizables. Selecciona una que se adapte a tu necesidad
                y luego personalízala con tus propios colores, texto e imágenes.
            </p>
        </div>
    </div>

    {{-- Blank Template --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-light border-2 border-dashed" style="cursor: pointer;" onclick="createBlankCampaign()">
                <div class="card-body text-center py-5">
                    <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="fas fa-plus fs-1 text-primary"></i>
                    </div>
                    <h4>Comenzar con Plantilla en Blanco</h4>
                    <p class="text-muted mb-0">
                        Crea tu campaña desde cero con total libertad creativa
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Templates Grid --}}
    @if($templates->count())
        <h5 class="mb-3">
            <i class="fas fa-sparkles"></i> Plantillas Prediseñadas
        </h5>

        <div class="row">
            @foreach($templates as $template)
                <div class="col-md-4 mb-4">
                    <div class="card template-card" onclick="selectTemplate({{ $template->id }})">
                        {{-- Preview --}}
                        <div class="template-preview" style="background: {{ $template->preview_gradient ?? 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' }};">
                            <div class="template-preview-icon">
                                @if($template->type === 'popup')
                                    <i class="far fa-window-maximize"></i>
                                @elseif($template->type === 'banner')
                                    <i class="fas fa-rectangle-ad"></i>
                                @elseif($template->type === 'slide-in')
                                    <i class="fas fa-sidebar"></i>
                                @elseif($template->type === 'full-screen')
                                    <i class="fas fa-layer-group"></i>
                                @else
                                    <i class="far fa-file-alt"></i>
                                @endif
                            </div>

                            @if($template->is_premium)
                                <span class="template-badge badge bg-warning">
                                    <i class="fas fa-crown"></i> Premium
                                </span>
                            @endif
                        </div>

                        <div class="card-body">
                            <h5 class="card-title">{{ $template->name }}</h5>
                            <p class="text-muted mb-3">{{ $template->description }}</p>

                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-light-primary text-primary">
                                        {{ Str::title($template->type) }}
                                    </span>
                                </div>
                                <button class="btn btn-sm btn-primary" onclick="selectTemplate({{ $template->id }})">
                                    <i class="fas fa-check"></i> Usar Plantilla
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        {{-- No Templates Yet --}}
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="far fa-file-alt" style="font-size: 4rem; color: #ccc;"></i>
                <h4 class="mt-3">No hay plantillas disponibles aún</h4>
                <p class="text-muted">
                    Puedes comenzar con una plantilla en blanco y crear tu propia campaña.
                </p>
                <button class="btn btn-primary" onclick="createBlankCampaign()">
                    <i class="fas fa-plus"></i> Crear Campaña en Blanco
                </button>
            </div>
        </div>
    @endif
</div>

{{-- Create Campaign Modal --}}
<div class="modal fade" id="createCampaignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus"></i> Nueva Campaña
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('manager.helpdesk.campaigns.store') }}" id="create-campaign-form">
                @csrf
                <input type="hidden" name="template_id" id="template-id-input">

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Nombre de la Campaña <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               name="name"
                               class="form-control"
                               placeholder="Ej: Promoción de Verano 2025"
                               required
                               autofocus>
                        <small class="text-muted">Dale un nombre descriptivo a tu campaña</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipo de Campaña</label>
                        <select name="type" class="form-select" required>
                            <option value="popup">Pop-up (Ventana emergente)</option>
                            <option value="banner">Banner (Barra superior/inferior)</option>
                            <option value="slide-in">Slide-in (Deslizar desde esquina)</option>
                            <option value="full-screen">Pantalla Completa (Overlay)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Descripción</label>
                        <textarea name="description"
                                  class="form-control"
                                  rows="3"
                                  placeholder="Opcional: describe el propósito de esta campaña..."></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Crear Campaña
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function createBlankCampaign() {
    const modal = new bootstrap.Modal(document.getElementById('createCampaignModal'));
    document.getElementById('template-id-input').value = '';
    modal.show();
}

function selectTemplate(templateId) {
    const modal = new bootstrap.Modal(document.getElementById('createCampaignModal'));
    document.getElementById('template-id-input').value = templateId;
    modal.show();
}
</script>
@endpush

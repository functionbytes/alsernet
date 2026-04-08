@extends('layouts.theme')

@section('title', 'Editar Campaña - ' . $campaign->name)

@push('styles')
<style>
    .campaign-preview {
        position: sticky;
        top: 20px;
        max-height: calc(100vh - 100px);
        overflow-y: auto;
    }

    .campaign-preview-frame {
        border: 2px solid var(--bs-border-color);
        border-radius: 12px;
        background: #f8f9fa;
        min-height: 400px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .content-block {
        background: white;
        border: 1px solid var(--bs-border-color);
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 0.75rem;
        cursor: move;
        transition: all 0.2s;
    }

    .content-block:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .block-handle {
        cursor: grab;
        color: #6c757d;
    }

    .block-handle:active {
        cursor: grabbing;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    {{-- Breadcrumb Header --}}
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-semibold mb-3">{{ $campaign->name }}</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="{{ route('manager.dashboard') }}">Dashboard</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="{{ route('manager.helpdesk.campaigns.index') }}">Campañas</a>
                            </li>
                            <li class="breadcrumb-item active">{{ $campaign->name }}</li>
                        </ol>
                    </nav>
                </div>

                {{-- Quick Actions --}}
                <div class="d-flex gap-2">
                    @if($campaign->status === 'draft')
                        <form method="POST" action="{{ route('manager.helpdesk.campaigns.publish', $campaign) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="fas fa-rocket"></i> Publicar
                            </button>
                        </form>
                    @elseif($campaign->status === 'active')
                        <form method="POST" action="{{ route('manager.helpdesk.campaigns.pause', $campaign) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-warning btn-sm">
                                <i class="fas fa-pause"></i> Pausar
                            </button>
                        </form>
                    @elseif($campaign->status === 'paused')
                        <form method="POST" action="{{ route('manager.helpdesk.campaigns.resume', $campaign) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="fas fa-play"></i> Reanudar
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('manager.helpdesk.campaigns.show', $campaign) }}" class="btn btn-light btn-sm">
                        <i class="fas fa-chart-bar"></i> Estadísticas
                    </a>
                </div>
            </div>
        </div>
    </div>

    @php
        $currentTab = request()->get('tab', 'general');
    @endphp

    <div class="row">
        {{-- Main Content Area --}}
        <div class="col-lg-8">
            <div class="card">
                {{-- Tab Navigation --}}
                <div class="card-header border-bottom">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link {{ $currentTab === 'general' ? 'active' : '' }}"
                               href="{{ route('manager.helpdesk.campaigns.edit', ['campaign' => $campaign, 'tab' => 'general']) }}">
                                <i class="fas fa-info-circle me-1"></i> General
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $currentTab === 'content' ? 'active' : '' }}"
                               href="{{ route('manager.helpdesk.campaigns.edit', ['campaign' => $campaign, 'tab' => 'content']) }}">
                                <i class="fas fa-layer-group me-1"></i> Contenido
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $currentTab === 'appearance' ? 'active' : '' }}"
                               href="{{ route('manager.helpdesk.campaigns.edit', ['campaign' => $campaign, 'tab' => 'appearance']) }}">
                                <i class="fas fa-palette me-1"></i> Apariencia
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $currentTab === 'conditions' ? 'active' : '' }}"
                               href="{{ route('manager.helpdesk.campaigns.edit', ['campaign' => $campaign, 'tab' => 'conditions']) }}">
                                <i class="fas fa-filter me-1"></i> Condiciones
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="card-body">
                    {{-- General Tab --}}
                    @if($currentTab === 'general')
                        @include('managers.views.helpdesk.campaigns.tabs.general', ['campaign' => $campaign])
                    @endif

                    {{-- Content Tab --}}
                    @if($currentTab === 'content')
                        @include('managers.views.helpdesk.campaigns.tabs.content', ['campaign' => $campaign])
                    @endif

                    {{-- Appearance Tab --}}
                    @if($currentTab === 'appearance')
                        @include('managers.views.helpdesk.campaigns.tabs.appearance', ['campaign' => $campaign])
                    @endif

                    {{-- Conditions Tab --}}
                    @if($currentTab === 'conditions')
                        @include('managers.views.helpdesk.campaigns.tabs.conditions', ['campaign' => $campaign])
                    @endif
                </div>
            </div>
        </div>

        {{-- Preview Sidebar --}}
        <div class="col-lg-4">
            <div class="campaign-preview">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-eye"></i> Vista Previa
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="campaign-preview-frame p-3" id="campaign-preview">
                            <div class="text-center text-muted">
                                <i class="fas fa-eye-slash fs-1 mb-3"></i>
                                <p>La vista previa aparecerá aquí</p>
                                <small>Agrega contenido para ver la campaña</small>
                            </div>
                        </div>

                        <div class="mt-3">
                            <div class="d-flex justify-content-between text-sm mb-2">
                                <span class="text-muted">Tipo:</span>
                                <strong>{{ $campaign->type_label }}</strong>
                            </div>
                            <div class="d-flex justify-content-between text-sm mb-2">
                                <span class="text-muted">Estado:</span>
                                <span class="badge bg-{{ $campaign->status_color }}">{{ $campaign->status_label }}</span>
                            </div>
                            <div class="d-flex justify-content-between text-sm mb-2">
                                <span class="text-muted">Bloques:</span>
                                <strong>{{ $campaign->content_blocks_count }}</strong>
                            </div>
                            @if($campaign->published_at)
                            <div class="d-flex justify-content-between text-sm">
                                <span class="text-muted">Publicado:</span>
                                <strong>{{ $campaign->published_at->format('d/m/Y') }}</strong>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Auto-update preview when content changes
document.addEventListener('DOMContentLoaded', function() {
    const previewFrame = document.getElementById('campaign-preview');

    // Listen for changes in form inputs
    document.querySelectorAll('input, textarea, select').forEach(input => {
        input.addEventListener('change', updatePreview);
        input.addEventListener('input', debounce(updatePreview, 500));
    });

    function updatePreview() {
        // This would normally update the preview
        // For now, just log that something changed
        console.log('Preview update triggered');
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
});
</script>
@endpush

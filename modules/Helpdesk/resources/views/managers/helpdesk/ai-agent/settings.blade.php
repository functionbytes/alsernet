@extends('layouts.theme')

@section('title', 'Configuración - Agente IA')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-3"><i class="ti ti-robot me-2"></i>Configuración del Agente IA</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('manager.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('manager.helpdesk.conversations.index') }}">Helpdesk</a></li>
                            <li class="breadcrumb-item active">Agente IA</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3">
                    <div class="text-center mb-n5">
                        <img src="{{ asset('theme/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid mb-n4" style="max-height: 150px;">
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error al guardar</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ti ti-check me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Main Content Card with Tabs -->
    <div class="card">
        <div class="card-body">
            <!-- Tabs Navigation -->
            <ul class="nav nav-pills user-profile-tab justify-content-start mt-2 bg-light-info rounded-2" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link position-relative rounded-0 active d-flex align-items-center justify-content-center bg-transparent fs-3 py-6" data-bs-toggle="tab" href="#tab-settings" role="tab" aria-selected="true">
                        <i class="ti ti-settings me-2 fs-6"></i>
                        <span class="d-none d-md-block">Configuración</span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-6" data-bs-toggle="tab" href="#tab-tags" role="tab" aria-selected="false">
                        <i class="ti ti-tags me-2 fs-6"></i>
                        <span class="d-none d-md-block">Tags</span>
                        <span class="badge bg-primary ms-2" id="tags-count">0</span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-6" data-bs-toggle="tab" href="#tab-tools" role="tab" aria-selected="false">
                        <i class="ti ti-tool me-2 fs-6"></i>
                        <span class="d-none d-md-block">Tools</span>
                        <span class="badge bg-success ms-2" id="tools-count">0</span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-6" data-bs-toggle="tab" href="#tab-knowledge" role="tab" aria-selected="false">
                        <i class="ti ti-brain me-2 fs-6"></i>
                        <span class="d-none d-md-block">Base de Conocimiento</span>
                        <span class="badge bg-warning ms-2" id="knowledge-count">0</span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-6" data-bs-toggle="tab" href="#tab-flows" role="tab" aria-selected="false">
                        <i class="ti ti-timeline me-2 fs-6"></i>
                        <span class="d-none d-md-block">Flows</span>
                        <span class="badge bg-info ms-2" id="flows-count">0</span>
                    </a>
                </li>
            </ul>

            <!-- Tabs Content -->
            <div class="tab-content mt-4" id="aiAgentTabContent">
                <!-- Settings Tab -->
                <div class="tab-pane fade show active" id="tab-settings" role="tabpanel">
                    @include('managers.views.helpdesk.ai-agent.partials.backups-tab', ['agent' => $agent, 'providers' => $providers, 'statuses' => $statuses])
                </div>

                <!-- Tags Tab -->
                <div class="tab-pane fade" id="tab-tags" role="tabpanel">
                    <div id="tags-container">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tools Tab -->
                <div class="tab-pane fade" id="tab-tools" role="tabpanel">
                    <div id="tools-container">
                        <div class="text-center py-5">
                            <div class="spinner-border text-success" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Knowledge Base Tab -->
                <div class="tab-pane fade" id="tab-knowledge" role="tabpanel">
                    <div id="knowledge-container">
                        <div class="text-center py-5">
                            <div class="spinner-border text-warning" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Flows Tab -->
                <div class="tab-pane fade" id="tab-flows" role="tabpanel">
                    <div id="flows-container">
                        <div class="text-center py-5">
                            <div class="spinner-border text-info" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
@include('managers.views.helpdesk.ai-agent.modals.tag-modal')
@include('managers.views.helpdesk.ai-agent.modals.tool-modal')
@include('managers.views.helpdesk.ai-agent.modals.knowledge-modal')

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Track loaded tabs to avoid reloading
    const loadedTabs = {
        settings: true, // Already loaded
        tags: false,
        tools: false,
        knowledge: false,
        flows: false
    };

    // Load tab content on tab show
    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        const target = $(e.target).attr('href');
        const tabName = target.replace('#tab-', '');

        if (!loadedTabs[tabName]) {
            loadTabContent(tabName);
            loadedTabs[tabName] = true;
        }
    });

    // Load tab content via AJAX
    function loadTabContent(tabName) {
        const container = $(`#${tabName}-container`);

        let url = '';
        switch(tabName) {
            case 'tags':
                url = '{{ route("manager.helpdesk.ai.tags.index") }}';
                break;
            case 'tools':
                url = '{{ route("manager.helpdesk.ai.tools.index") }}';
                break;
            case 'knowledge':
                url = '{{ route("manager.helpdesk.ai.knowledge.index") }}';
                break;
            case 'flows':
                url = '{{ route("manager.helpdesk.ai.flows.index") }}';
                break;
        }

        if (url) {
            $.ajax({
                url: url,
                method: 'GET',
                success: function(response) {
                    container.html(response);
                    updateTabCounter(tabName);
                },
                error: function(xhr) {
                    container.html(`
                        <div class="alert alert-danger">
                            <i class="ti ti-alert-circle me-2"></i>
                            Error al cargar el contenido. Por favor, intenta de nuevo.
                        </div>
                    `);
                }
            });
        }
    }

    // Update tab counter
    function updateTabCounter(tabName) {
        const container = $(`#${tabName}-container`);
        const count = container.find('[data-count-item]').length;
        $(`#${tabName}-count`).text(count);
    }

    // Global success toast
    window.showSuccess = function(message) {
        toastr.success(message, 'Éxito');
    };

    // Global error toast
    window.showError = function(message) {
        toastr.error(message, 'Error');
    };

    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
});
</script>
@endpush

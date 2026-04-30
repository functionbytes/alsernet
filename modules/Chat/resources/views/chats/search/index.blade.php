@extends('layouts.theme')

@section('title', 'Búsqueda global')

@push('styles')
<style>
.text-truncate-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.stat-card { transition: all 0.2s; }
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
.search-result-row:hover { background-color: #f8f9fa; cursor: pointer; }
</style>
@endpush

@section('content')

@include('core::components.card', ['title' => 'Búsqueda global'])

<div class="widget-content searchable-container list">

    @include('core::components.alerts')

    <div class="card">
        <!-- Header Section -->
        <div class="card-header p-4 border-bottom border-light">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Búsqueda global</h5>
                    <p class="small mb-0 text-muted">Busca por Id de conversación, correo electrónico, número de teléfono o contenido de mensajes</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('chat.conversations.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Volver a conversaciones
                    </a>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="card-body border-bottom" id="search-stats-container">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-start justify-content-between">
                                <div class="w-100">
                                    <h6 class="card-title text-primary mb-2">Contactos</h6>
                                    <h4 class="mb-1 fw-bold" id="stat-contacts">-</h4>
                                    <small class="text-muted">Resultados encontrados</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-start justify-content-between">
                                <div class="w-100">
                                    <h6 class="card-title mb-2">Conversaciones</h6>
                                    <h4 class="mb-1 fw-bold" id="stat-conversations">-</h4>
                                    <small class="text-muted">Resultados encontrados</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-start justify-content-between">
                                <div class="w-100">
                                    <h6 class="card-title text-warning mb-2">Mensajes</h6>
                                    <h4 class="mb-1 fw-bold" id="stat-messages">-</h4>
                                    <small class="text-muted">Resultados encontrados</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-start justify-content-between">
                                <div class="w-100">
                                    <h6 class="card-title text-info mb-2">Total</h6>
                                    <h4 class="mb-1 fw-bold" id="stat-total">0</h4>
                                    <small class="text-muted">Resultados totales</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Section -->
        <div class="card-body border-bottom">
            <form id="search-form">
                <div class="row align-items-center">
                    <div class="col-md-9">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search"></i>
                            </span>
                            <input
                                type="text"
                                class="form-control"
                                id="global-search-input"
                                placeholder="Buscar mensajes, contactos o conversaciones..."
                                autocomplete="off"
                                value="{{ request('q') }}"
                            >
                            <button class="btn btn-secondary d-none" id="global-search-clear" type="button">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <small class="text-muted">Presiona <kbd>/</kbd> para enfocar el campo de búsqueda</small>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100" id="search-submit-btn">
                            Buscar
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tabs Section -->
        <div class="card-body border-bottom d-none" id="search-tabs-container">
            <ul class="nav nav-tabs border-0" id="search-tabs">
                <li class="nav-item">
                    <button class="nav-link active" data-tab="all">
                        Todos <span class="badge bg-secondary rounded-pill ms-1" id="tab-count-all">0</span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-tab="contacts">
                        Contactos <span class="badge bg-secondary rounded-pill ms-1" id="tab-count-contacts">0</span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-tab="conversations">
                        Conversaciones <span class="badge bg-secondary rounded-pill ms-1" id="tab-count-conversations">0</span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-tab="messages">
                        Mensajes <span class="badge bg-secondary rounded-pill ms-1" id="tab-count-messages">0</span>
                    </button>
                </li>
            </ul>
        </div>

        <!-- Results Area -->
        <div class="card-body">

            <!-- Empty state (initial) -->
            <div id="search-empty-state" class="text-center py-5">
                <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center mx-auto" style="width: 64px; height: 64px;">
                    <i class="fas fa-search fs-3"></i>
                </div>
                <h6 class="mb-1">Busca en toda tu información</h6>
                <p class="text-muted mb-0">Escribe en el campo de arriba para buscar contactos, conversaciones o mensajes</p>
            </div>

            <!-- Loading spinner -->
            <div id="search-loading" class="text-center py-5 d-none">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Buscando...</span>
                </div>
                <p class="text-muted mb-0">Buscando resultados...</p>
            </div>

            <!-- No results -->
            <div id="search-no-results" class="text-center py-5 d-none">
                <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center mx-auto" style="width: 64px; height: 64px;">
                    <i class="fas fa-search-minus fs-3"></i>
                </div>
                <h6 class="mb-1">No se encontraron resultados</h6>
                <p class="text-muted mb-0">Intenta con otros términos de búsqueda</p>
            </div>

            <!-- Contacts section -->
            <div id="section-contacts" class="d-none mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-uppercase fw-semibold text-muted mb-0">
                        <i class="fas fa-user me-2"></i>Contactos
                    </h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="results-contacts">
                        <thead class="table-light">
                            <tr>
                                <th width="30%">Nombre</th>
                                <th width="25%">Email</th>
                                <th width="20%">Teléfono</th>
                                <th width="15%" class="text-center">Conversaciones</th>
                                <th width="10%" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="text-center mt-3">
                    <button class="btn btn-sm btn-outline-primary d-none" id="load-more-contacts" data-page="1">
                        <i class="fas fa-chevron-down me-1"></i> Cargar más contactos
                    </button>
                </div>
            </div>

            <!-- Conversations section -->
            <div id="section-conversations" class="d-none mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-uppercase fw-semibold text-muted mb-0">
                        <i class="fas fa-comments me-2"></i>Conversaciones
                    </h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="results-conversations">
                        <thead class="table-light">
                            <tr>
                                <th width="10%">ID</th>
                                <th width="30%">Contacto</th>
                                <th width="15%">Canal</th>
                                <th width="15%" class="text-center">Estado</th>
                                <th width="20%">Última actividad</th>
                                <th width="10%" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="text-center mt-3">
                    <button class="btn btn-sm btn-outline-primary d-none" id="load-more-conversations" data-page="1">
                        <i class="fas fa-chevron-down me-1"></i> Cargar más conversaciones
                    </button>
                </div>
            </div>

            <!-- Messages section -->
            <div id="section-messages" class="d-none mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-uppercase fw-semibold text-muted mb-0">
                        <i class="fas fa-comment-dots me-2"></i>Mensajes
                    </h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="results-messages">
                        <thead class="table-light">
                            <tr>
                                <th width="15%">Conversación</th>
                                <th width="20%">Remitente</th>
                                <th width="45%">Contenido</th>
                                <th width="20%">Fecha</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="text-center mt-3">
                    <button class="btn btn-sm btn-outline-primary d-none" id="load-more-messages" data-page="1">
                        <i class="fas fa-chevron-down me-1"></i> Cargar más mensajes
                    </button>
                </div>
            </div>

        </div>

    </div>
</div>

@endsection

@push('scripts')
<script>
    window.SearchConfig = {
        urls: {
            contacts: '{{ route('chat.search.contacts') }}',
            conversations: '{{ route('chat.search.conversations') }}',
            messages: '{{ route('chat.search.messages') }}',
        },
        conversationBase: '{{ rtrim(route('chat.conversations.index'), '/') }}',
        contactBase: '{{ rtrim(route('chat.customers.index'), '/') }}',
    };
</script>
<script src="{{ asset('chat-module/js/chat/ui/search.js') }}"></script>
@endpush

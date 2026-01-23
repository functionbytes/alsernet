@extends('layouts.helpdesk')

@section('title', 'Conversaciones - Groups')

@section('content')
    {{-- Modern Groups Header --}}
    <div class="bg-white border-bottom sticky-top" style="z-index: 10;">
        <div class="d-flex align-items-center px-4 py-3">
            <h4 class="mb-0 fw-bold">
                <i class="ti ti-inbox me-2 text-primary"></i>
                {{ $currentView ? $currentView->name : 'Conversaciones' }}
            </h4>
            @if($currentView && $currentView->description)
                <span class="text-muted mx-2">•</span>
                <small class="text-muted">{{ $currentView->description }}</small>
            @endif
        </div>
    </div>

    <div class="d-flex">
        {{-- Modern Sidebar with Modernize Style --}}
        <div class="left-part border-end w-20 flex-shrink-0 d-none d-lg-block">
            {{-- New Conversation Button --}}
            <div class="px-9 pt-4 pb-3">
                <a href="{{ route('manager.helpdesk.conversation.create') }}" class="btn btn-primary fw-semibold py-8 w-100">
                    <i class="ti ti-plus me-2"></i> Nueva Conversación
                </a>
            </div>

            {{-- Sidebar Menu --}}
            <ul class="list-group mh-n100" data-simplebar>
                {{-- VISTAS Section --}}
                <li class="border-bottom my-3"></li>
                <li class="fw-semibold text-dark text-uppercase mx-9 my-2 px-3 fs-2">
                    VISTAS
                    <a href="{{ route('manager.helpdesk.settings.tickets.views.create') }}" class="float-end text-primary" title="Nueva vista">
                        <i class="ti ti-plus fs-4"></i>
                    </a>
                </li>
                @forelse($views as $view)
                    <li class="list-group-item border-0 p-0 mx-9">
                        <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1 {{ $currentView && $currentView->id == $view->id && !request('group') ? 'bg-primary-subtle' : '' }}"
                           href="{{ route('manager.helpdesk.conversation.index', ['viewId' => $view->id]) }}">
                            <i class="ti ti-eye fs-5"></i>{{ $view->name }}
                        </a>
                    </li>
                @empty
                    <li class="list-group-item border-0 p-0 mx-9">
                        <span class="text-muted small px-3 py-2 d-block">Sin vistas configuradas</span>
                    </li>
                @endforelse

                {{-- GRUPOS Section --}}
                <li class="border-bottom my-3"></li>
                <li class="fw-semibold text-dark text-uppercase mx-9 my-2 px-3 fs-2">
                    GRUPOS
                    <a href="{{ route('manager.helpdesk.settings.tickets.team.groups') }}" class="float-end text-primary" title="Gestionar">
                        <i class="ti ti-settings fs-4"></i>
                    </a>
                </li>
                <li class="list-group-item border-0 p-0 mx-9">
                    <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1 {{ !request('group') ? 'bg-primary-subtle' : '' }}"
                       href="{{ route('manager.helpdesk.conversation.index', array_merge(request()->except('group'), $currentView ? ['viewId' => $currentView->id] : [])) }}">
                        <i class="ti ti-users fs-5"></i>Todos los Grupos
                    </a>
                </li>
                @forelse($groups as $group)
                    <li class="list-group-item border-0 p-0 mx-9">
                        <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1 {{ request('group') == $group->id ? 'bg-primary-subtle' : '' }}"
                           href="{{ route('manager.helpdesk.conversation.index', array_merge(request()->except('group'), ['group' => $group->id], $currentView ? ['viewId' => $currentView->id] : [])) }}">
                            <i class="ti ti-users-group fs-5"></i>{{ $group->name }}
                        </a>
                    </li>
                @empty
                    <li class="list-group-item border-0 p-0 mx-9">
                        <span class="text-muted small px-3 py-2 d-block">Sin grupos configurados</span>
                    </li>
                @endforelse

                {{-- FILTROS Section --}}
                <li class="border-bottom my-3"></li>
                <li class="fw-semibold text-dark text-uppercase mx-9 my-2 px-3 fs-2">
                    FILTROS
                    <button type="button" class="float-end btn btn-sm btn-link text-primary p-0" id="toggleFilters" title="Mostrar/Ocultar">
                        <i class="ti ti-filter fs-4"></i>
                    </button>
                </li>
                <li class="list-group-item border-0 p-0 mx-9" id="filtersPanel" style="display: none;">
                    <form method="GET" action="{{ route('manager.helpdesk.conversation.index') }}" id="filtersForm">
                        @if($currentView)
                            <input type="hidden" name="viewId" value="{{ $currentView->id }}">
                        @endif
                        @if(request('group'))
                            <input type="hidden" name="group" value="{{ request('group') }}">
                        @endif

                        <div class="mb-3">
                            <label class="form-label small">Estado</label>
                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="all">Todos</option>
                                @foreach($statuses as $status)
                                    <option value="{{ $status->id }}" {{ request('status') == $status->id ? 'selected' : '' }}>
                                        {{ $status->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small">Prioridad</label>
                            <select name="priority" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="all">Todas</option>
                                <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>🟢 Baja</option>
                                <option value="normal" {{ request('priority') == 'normal' ? 'selected' : '' }}>🔵 Normal</option>
                                <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>🟡 Alta</option>
                                <option value="urgent" {{ request('priority') == 'urgent' ? 'selected' : '' }}>🔴 Urgente</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small">Buscar</label>
                            <input type="search" name="search" class="form-control form-control-sm" placeholder="Asunto o cliente..." value="{{ request('search') }}">
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="ti ti-search me-1"></i> Buscar
                            </button>
                            <a href="{{ route('manager.helpdesk.conversation.index', $currentView ? ['viewId' => $currentView->id] : []) }}" class="btn btn-light btn-sm">
                                <i class="ti ti-x me-1"></i> Limpiar
                            </a>
                        </div>
                    </form>
                </li>
            </ul>
        </div>

        {{-- Conversations List Center --}}
        <div class="w-30 d-none d-lg-block border-end user-chat-box">
            <div class="px-4 pt-9 pb-6">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="fw-semibold mb-0">Conversaciones</h5>
                    <div class="dropdown">
                        <a class="text-dark fs-6 nav-icon-hover" href="javascript:void(0)" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="ti ti-dots-vertical"></i>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('manager.helpdesk.conversation.create') }}">
                                    <span><i class="ti ti-plus fs-4"></i></span>Nueva Conversación
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('manager.helpdesk.settings.tickets.views.index') }}">
                                    <span><i class="ti ti-settings fs-4"></i></span>Configurar Vistas
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <form class="position-relative mb-4" method="GET" action="{{ route('manager.helpdesk.conversation.index') }}">
                    @if($currentView)
                        <input type="hidden" name="viewId" value="{{ $currentView->id }}">
                    @endif
                    @if(request('group'))
                        <input type="hidden" name="group" value="{{ request('group') }}">
                    @endif
                    <input type="text" name="search" class="form-control search-chat py-2 ps-5" placeholder="Buscar conversaciones..." value="{{ request('search') }}">
                    <i class="ti ti-search position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
                </form>
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-muted fw-semibold">{{ $conversations->total() }} Conversaciones</span>
                    @if(request()->hasAny(['status', 'priority', 'search']))
                        <a href="{{ route('manager.helpdesk.conversation.index', $currentView ? ['viewId' => $currentView->id] : []) }}" class="text-primary small">
                            <i class="ti ti-x"></i> Limpiar filtros
                        </a>
                    @endif
                </div>
            </div>
            <div class="app-chat">
                <ul class="chat-users mb-0 mh-n100" data-simplebar>
                    @forelse($conversations as $conversation)
                        <li>
                            <a href="{{ route('manager.helpdesk.conversation.show', $conversation) }}"
                               class="px-4 py-3 bg-hover-light-black d-flex align-items-start justify-content-between chat-user">
                                <div class="d-flex align-items-center">
                                    <span class="position-relative">
                                        <img src="{{ $conversation->customer->getAvatarUrl() }}"
                                             alt="{{ $conversation->customer->name }}"
                                             width="48"
                                             height="48"
                                             class="rounded-circle">
                                        @if($conversation->status->is_open)
                                            <span class="position-absolute bottom-0 end-0 p-1 badge rounded-pill bg-success">
                                                <span class="visually-hidden">Active</span>
                                            </span>
                                        @else
                                            <span class="position-absolute bottom-0 end-0 p-1 badge rounded-pill bg-secondary">
                                                <span class="visually-hidden">Closed</span>
                                            </span>
                                        @endif
                                    </span>
                                    <div class="ms-3 d-inline-block w-75">
                                        <h6 class="mb-1 fw-semibold chat-title">
                                            {{ $conversation->customer->name }}
                                        </h6>
                                        <span class="fs-3 text-truncate text-body-color d-block">
                                            {{ $conversation->subject ?? '(Sin asunto)' }}
                                        </span>
                                        <div class="d-flex gap-1 mt-1">
                                            <span class="badge badge-sm bg-{{ $conversation->status->color }}-subtle text-{{ $conversation->status->color }}">
                                                {{ $conversation->status->name }}
                                            </span>
                                            @if($conversation->getMessageCount() > 0)
                                                <span class="badge badge-sm bg-light text-muted">
                                                    <i class="ti ti-message"></i> {{ $conversation->getMessageCount() }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <p class="fs-2 mb-0 text-muted">{{ $conversation->updated_at->diffForHumans(null, true) }}</p>
                                    @if(!$conversation->assignee)
                                        <span class="badge badge-sm bg-danger-subtle text-danger mt-1">
                                            Sin asignar
                                        </span>
                                    @endif
                                </div>
                            </a>
                        </li>
                    @empty
                        <li class="text-center py-5">
                            <div class="text-muted">
                                <i class="ti ti-inbox" style="font-size: 48px; opacity: 0.3;"></i>
                                <p class="mt-3 mb-0">No hay conversaciones</p>
                            </div>
                        </li>
                    @endforelse
                </ul>
            </div>

            {{-- Pagination --}}
            @if($conversations->hasPages())
                <div class="p-3 border-top">
                    {{ $conversations->links() }}
                </div>
            @endif
        </div>

        {{-- Conversation Detail / Empty State --}}
        <div class="flex-fill d-flex align-items-center justify-content-center bg-light">
            <div class="text-center">
                <i class="ti ti-message-circle" style="font-size: 120px; opacity: 0.2; color: #5D87FF;"></i>
                <h4 class="mt-4 text-muted">Selecciona una conversación</h4>
                <p class="text-muted mb-4">Elige una conversación del panel izquierdo para ver los detalles</p>
                <a href="{{ route('manager.helpdesk.conversation.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-2"></i> Nueva Conversación
                </a>
            </div>
        </div>
    </div>
@endsection

@section('styles')
<style>
    /* Modern Groups Styles */
    .badge {
        font-weight: 500;
    }

    /* Modernize Sidebar Styles */
    .left-part {
        height: calc(100vh - 140px);
        overflow-y: auto;
    }

    .left-part .list-group-item-action {
        transition: all 0.2s ease;
    }

    .left-part .list-group-item-action:hover {
        background-color: #f8f9fa !important;
    }

    .left-part .bg-primary-subtle {
        background-color: rgba(13, 110, 253, 0.1) !important;
        color: #0d6efd !important;
        font-weight: 600;
    }

    .left-part::-webkit-scrollbar {
        width: 6px;
    }

    .left-part::-webkit-scrollbar-track {
        background: transparent;
    }

    .left-part::-webkit-scrollbar-thumb {
        background: #cbd5e0;
        border-radius: 3px;
    }

    .left-part::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Chat User List Styles */
    .user-chat-box {
        height: calc(100vh - 140px);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .app-chat {
        flex: 1;
        overflow-y: auto;
    }

    .chat-users {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .chat-user {
        text-decoration: none;
        color: inherit;
        display: block;
        transition: background-color 0.2s ease;
        border-bottom: 1px solid #f1f5f9;
    }

    .chat-user:hover {
        background-color: #f8f9fa !important;
    }

    .chat-user.bg-light-subtle {
        background-color: #e9ecef !important;
    }

    .bg-hover-light-black:hover {
        background-color: #f8f9fa !important;
    }

    .search-chat {
        border-radius: 8px;
    }

    .nav-icon-hover:hover {
        background-color: #f8f9fa;
        border-radius: 50%;
    }

    .app-chat::-webkit-scrollbar {
        width: 6px;
    }

    .app-chat::-webkit-scrollbar-track {
        background: transparent;
    }

    .app-chat::-webkit-scrollbar-thumb {
        background: #cbd5e0;
        border-radius: 3px;
    }

    .app-chat::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    .badge-sm {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
    }

    /* Empty state */
    .flex-fill {
        min-height: calc(100vh - 140px);
    }
</style>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Toggle filters panel in sidebar
        $('#toggleFilters').on('click', function() {
            const panel = $('#filtersPanel');
            panel.slideToggle(200);
        });

        // Show filters if any filter is active
        @if(request('status') || request('priority'))
            $('#filtersPanel').show();
        @endif

        // Keyboard shortcuts
        $(document).on('keydown', function(e) {
            // Ctrl/Cmd + K to focus search
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                $('.search-chat').focus();
            }
        });

        // Auto-submit search form on Enter
        $('.search-chat').on('keypress', function(e) {
            if (e.which === 13) {
                $(this).closest('form').submit();
            }
        });
    });
</script>
@endsection

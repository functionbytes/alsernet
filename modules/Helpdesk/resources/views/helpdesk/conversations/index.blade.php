@extends('layouts.theme')

@section('title', 'Conversaciones - Helpdesk')

@section('content')
    {{-- Header --}}
    <div class="bg-white border-bottom sticky-top helpdesk-header">
        <div class="d-flex align-items-center px-4 py-3 gap-2">
            <button class="btn btn-sm btn-outline-secondary d-lg-none" type="button"
                    data-bs-toggle="offcanvas" data-bs-target="#helpdeskSidebar" aria-controls="helpdeskSidebar">
                <i class="fas fa-bars"></i>
            </button>
            <h4 class="mb-0 fw-bold">
                <i class="fas fa-comments me-2 text-primary"></i>
                {{ $currentView ? $currentView->name : 'Conversaciones' }}
            </h4>
            @if($currentView && $currentView->description)
                <span class="text-muted mx-1 d-none d-sm-inline">•</span>
                <small class="text-muted d-none d-sm-inline">{{ $currentView->description }}</small>
            @endif
            <div class="ms-auto d-lg-none">
                <a href="{{ route('manager.helpdesk.conversations.create') }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus"></i>
                </a>
            </div>
        </div>
    </div>

    {{-- Offcanvas sidebar — mobile/tablet --}}
    <div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="helpdeskSidebar" aria-labelledby="helpdeskSidebarLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title fw-bold" id="helpdeskSidebarLabel">
                <i class="fas fa-headset me-2 text-primary"></i> Helpdesk
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-0">
            <div class="px-9 pt-4 pb-3">
                <a href="{{ route('manager.helpdesk.conversations.create') }}" class="btn btn-primary fw-semibold py-8 w-100">
                    <i class="fas fa-plus me-2"></i> Nueva Conversación
                </a>
            </div>
            <ul class="list-group">
                <li class="border-bottom my-3"></li>
                <li class="fw-semibold text-dark text-uppercase mx-9 my-2 px-3 fs-2">
                    VISTAS
                    <a href="{{ route('settings.helpdesk.views.create') }}" class="float-end text-primary" title="Nueva vista">
                        <i class="fas fa-plus fs-4"></i>
                    </a>
                </li>
                @forelse($views as $view)
                    <li class="list-group-item border-0 p-0 mx-9">
                        <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1 {{ $currentView && $currentView->id == $view->id && !request('group') ? 'bg-primary-subtle' : '' }}"
                           href="{{ route('manager.helpdesk.conversations.index', ['viewId' => $view->id]) }}"
                           data-bs-dismiss="offcanvas">
                            <i class="fas fa-eye fs-5"></i>{{ $view->name }}
                        </a>
                    </li>
                @empty
                    <li class="list-group-item border-0 p-0 mx-9">
                        <span class="text-muted px-3 py-2 d-block">Sin vistas configuradas</span>
                    </li>
                @endforelse

                <li class="border-bottom my-3"></li>
                <li class="fw-semibold text-dark text-uppercase mx-9 my-2 px-3 fs-2">
                    GRUPOS
                    <a href="{{ route('settings.helpdesk.team.groups') }}" class="float-end text-primary" title="Gestionar">
                        <i class="fas fa-cog fs-4"></i>
                    </a>
                </li>
                <li class="list-group-item border-0 p-0 mx-9">
                    <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1 {{ !request('group') ? 'bg-primary-subtle' : '' }}"
                       href="{{ route('manager.helpdesk.conversations.index', array_merge(request()->except('group'), $currentView ? ['viewId' => $currentView->id] : [])) }}"
                       data-bs-dismiss="offcanvas">
                        <i class="fas fa-users fs-5"></i>Todos los Grupos
                    </a>
                </li>
                @forelse($groups as $group)
                    <li class="list-group-item border-0 p-0 mx-9">
                        <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1 {{ request('group') == $group->id ? 'bg-primary-subtle' : '' }}"
                           href="{{ route('manager.helpdesk.conversations.index', array_merge(request()->except('group'), ['group' => $group->id], $currentView ? ['viewId' => $currentView->id] : [])) }}"
                           data-bs-dismiss="offcanvas">
                            <i class="fas fa-user-friends fs-5"></i>{{ $group->name }}
                        </a>
                    </li>
                @empty
                    <li class="list-group-item border-0 p-0 mx-9">
                        <span class="text-muted px-3 py-2 d-block">Sin grupos configurados</span>
                    </li>
                @endforelse

                <li class="border-bottom my-3"></li>
                <li class="fw-semibold text-dark text-uppercase mx-9 my-2 px-3 fs-2">
                    FILTROS
                </li>
                <li class="list-group-item border-0 p-0 mx-9">
                    <form method="GET" action="{{ route('manager.helpdesk.conversations.index') }}" class="pb-3">
                        @if($currentView)
                            <input type="hidden" name="viewId" value="{{ $currentView->id }}">
                        @endif
                        @if(request('group'))
                            <input type="hidden" name="group" value="{{ request('group') }}">
                        @endif
                        <div class="mb-3">
                            <label class="form-label small">Estado</label>
                            <select name="status" class="form-select form-select-sm js-auto-submit-mobile">
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
                            <select name="priority" class="form-select form-select-sm js-auto-submit-mobile">
                                <option value="all">Todas</option>
                                <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Baja</option>
                                <option value="normal" {{ request('priority') == 'normal' ? 'selected' : '' }}>Normal</option>
                                <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>Alta</option>
                                <option value="urgent" {{ request('priority') == 'urgent' ? 'selected' : '' }}>Urgente</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Canal</label>
                            <select name="channel" class="form-select form-select-sm js-auto-submit-mobile">
                                <option value="">Todos los canales</option>
                                <option value="widget" {{ request('channel') === 'widget' ? 'selected' : '' }}>Widget</option>
                                <option value="whatsapp" {{ request('channel') === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                                <option value="facebook" {{ request('channel') === 'facebook' ? 'selected' : '' }}>Facebook</option>
                                <option value="instagram" {{ request('channel') === 'instagram' ? 'selected' : '' }}>Instagram</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Buscar</label>
                            <input type="search" name="search" class="form-control form-control-sm" placeholder="Asunto o cliente..." value="{{ request('search') }}">
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-search me-1"></i> Buscar
                            </button>
                            <a href="{{ route('manager.helpdesk.conversations.index', $currentView ? ['viewId' => $currentView->id] : []) }}" class="btn btn-light btn-sm">
                                <i class="fas fa-times me-1"></i> Limpiar
                            </a>
                        </div>
                    </form>
                </li>
            </ul>
        </div>
    </div>

    <div class="d-flex">
        {{-- Desktop sidebar --}}
        <div class="left-part border-end w-20 flex-shrink-0 d-none d-lg-block">
            <div class="px-9 pt-4 pb-3">
                <a href="{{ route('manager.helpdesk.conversations.create') }}" class="btn btn-primary fw-semibold py-8 w-100">
                    <i class="fas fa-plus me-2"></i> Nueva Conversación
                </a>
            </div>

            <ul class="list-group mh-n100" data-simplebar>
                <li class="border-bottom my-3"></li>
                <li class="fw-semibold text-dark text-uppercase mx-9 my-2 px-3 fs-2">
                    VISTAS
                    <a href="{{ route('settings.helpdesk.views.create') }}" class="float-end text-primary" title="Nueva vista">
                        <i class="fas fa-plus fs-4"></i>
                    </a>
                </li>
                @forelse($views as $view)
                    <li class="list-group-item border-0 p-0 mx-9">
                        <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1 {{ $currentView && $currentView->id == $view->id && !request('group') ? 'bg-primary-subtle' : '' }}"
                           href="{{ route('manager.helpdesk.conversations.index', ['viewId' => $view->id]) }}">
                            <i class="fas fa-eye fs-5"></i>{{ $view->name }}
                        </a>
                    </li>
                @empty
                    <li class="list-group-item border-0 p-0 mx-9">
                        <span class="text-muted px-3 py-2 d-block">Sin vistas configuradas</span>
                    </li>
                @endforelse

                <li class="border-bottom my-3"></li>
                <li class="fw-semibold text-dark text-uppercase mx-9 my-2 px-3 fs-2">
                    GRUPOS
                    <a href="{{ route('settings.helpdesk.team.groups') }}" class="float-end text-primary" title="Gestionar">
                        <i class="fas fa-cog fs-4"></i>
                    </a>
                </li>
                <li class="list-group-item border-0 p-0 mx-9">
                    <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1 {{ !request('group') ? 'bg-primary-subtle' : '' }}"
                       href="{{ route('manager.helpdesk.conversations.index', array_merge(request()->except('group'), $currentView ? ['viewId' => $currentView->id] : [])) }}">
                        <i class="fas fa-users fs-5"></i>Todos los Grupos
                    </a>
                </li>
                @forelse($groups as $group)
                    <li class="list-group-item border-0 p-0 mx-9">
                        <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1 {{ request('group') == $group->id ? 'bg-primary-subtle' : '' }}"
                           href="{{ route('manager.helpdesk.conversations.index', array_merge(request()->except('group'), ['group' => $group->id], $currentView ? ['viewId' => $currentView->id] : [])) }}">
                            <i class="fas fa-user-friends fs-5"></i>{{ $group->name }}
                        </a>
                    </li>
                @empty
                    <li class="list-group-item border-0 p-0 mx-9">
                        <span class="text-muted px-3 py-2 d-block">Sin grupos configurados</span>
                    </li>
                @endforelse

                <li class="border-bottom my-3"></li>
                <li class="fw-semibold text-dark text-uppercase mx-9 my-2 px-3 fs-2">
                    FILTROS
                    <button type="button" class="float-end btn btn-sm btn-link text-primary p-0" id="toggleFilters" title="Mostrar/Ocultar">
                        <i class="fas fa-filter fs-4"></i>
                    </button>
                </li>
                <li class="list-group-item border-0 p-0 mx-9" id="filtersPanel" style="display: none;">
                    <form method="GET" action="{{ route('manager.helpdesk.conversations.index') }}" id="filtersForm">
                        @if($currentView)
                            <input type="hidden" name="viewId" value="{{ $currentView->id }}">
                        @endif
                        @if(request('group'))
                            <input type="hidden" name="group" value="{{ request('group') }}">
                        @endif

                        <div class="mb-3">
                            <label class="form-label small">Estado</label>
                            <select name="status" class="form-select form-select-sm js-auto-submit">
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
                            <select name="priority" class="form-select form-select-sm js-auto-submit">
                                <option value="all">Todas</option>
                                <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Baja</option>
                                <option value="normal" {{ request('priority') == 'normal' ? 'selected' : '' }}>Normal</option>
                                <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>Alta</option>
                                <option value="urgent" {{ request('priority') == 'urgent' ? 'selected' : '' }}>Urgente</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small">Canal</label>
                            <select name="channel" class="form-select form-select-sm js-auto-submit">
                                <option value="">Todos los canales</option>
                                <option value="widget" {{ request('channel') === 'widget' ? 'selected' : '' }}>Widget</option>
                                <option value="whatsapp" {{ request('channel') === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                                <option value="facebook" {{ request('channel') === 'facebook' ? 'selected' : '' }}>Facebook</option>
                                <option value="instagram" {{ request('channel') === 'instagram' ? 'selected' : '' }}>Instagram</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small">Buscar</label>
                            <input type="search" name="search" class="form-control form-control-sm" placeholder="Asunto o cliente..." value="{{ request('search') }}">
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-search me-1"></i> Buscar
                            </button>
                            <a href="{{ route('manager.helpdesk.conversations.index', $currentView ? ['viewId' => $currentView->id] : []) }}" class="btn btn-light btn-sm">
                                <i class="fas fa-times me-1"></i> Limpiar
                            </a>
                        </div>
                    </form>
                </li>
            </ul>
        </div>

        {{-- Conversations list — full width on mobile, w-30 on desktop --}}
        <div class="helpdesk-conv-list border-end user-chat-box">
            {{-- Bulk Actions Bar --}}
            <div id="bulk-actions-bar" class="d-none p-3 border-bottom bg-light">
                <form id="bulk-form" method="POST" action="{{ route('manager.helpdesk.conversations.bulk') }}">
                    @csrf
                    <input type="hidden" name="action" id="bulk-action-input">
                    <div id="bulk-conversation-ids"></div>
                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <span id="selected-count" class="text-muted fw-bold me-1">0 seleccionadas</span>
                        <select name="agent_id" id="bulk-agent-select" class="form-select form-select-sm d-none bulk-agent-select-width">
                        </select>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="bulkConvAction('close')">Cerrar</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="bulkConvAction('reopen')">Reabrir</button>
                        <button type="button" class="btn btn-sm btn-outline-warning" onclick="bulkConvAction('archive')">Archivar</button>
                        <button type="button" class="btn btn-sm btn-danger" onclick="bulkConvAction('delete')">Eliminar</button>
                        <button type="submit" id="bulk-submit-btn" class="btn btn-sm btn-success d-none">Aplicar</button>
                        <button type="button" class="btn btn-sm btn-link text-muted" onclick="clearBulkConvSelection()">Cancelar</button>
                    </div>
                </form>
            </div>

            <div class="px-4 pt-9 pb-6">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="select-all-conv" title="Seleccionar todas">
                        </div>
                        <h5 class="fw-semibold mb-0">Conversaciones</h5>
                    </div>
                    <div class="dropdown">
                        <a class="text-dark fs-6 nav-icon-hover" href="javascript:void(0)" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-ellipsis-v"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('manager.helpdesk.conversations.create') }}">
                                    <span><i class="fas fa-plus fs-4"></i></span>Nueva Conversación
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('settings.helpdesk.views.index') }}">
                                    <span><i class="fas fa-cog fs-4"></i></span>Configurar Vistas
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2"
                                   href="{{ route('manager.helpdesk.exports.conversations', request()->query()) }}">
                                    <span><i class="fas fa-file-csv fs-4"></i></span>Exportar CSV
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <form class="position-relative mb-4" method="GET" action="{{ route('manager.helpdesk.conversations.index') }}">
                    @if($currentView)
                        <input type="hidden" name="viewId" value="{{ $currentView->id }}">
                    @endif
                    @if(request('group'))
                        <input type="hidden" name="group" value="{{ request('group') }}">
                    @endif
                    <input type="text" name="search" class="form-control search-chat py-2 ps-5" placeholder="Buscar conversaciones..." value="{{ request('search') }}">
                    <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
                </form>
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-muted fw-semibold">{{ $conversations->total() }} Conversaciones</span>
                    @if(request()->hasAny(['status', 'priority', 'search']))
                        <a href="{{ route('manager.helpdesk.conversations.index', $currentView ? ['viewId' => $currentView->id] : []) }}" class="text-primary small">
                            <i class="fas fa-times"></i> Limpiar filtros
                        </a>
                    @endif
                </div>
            </div>

            <div class="app-chat">
                <ul class="chat-users mb-0 mh-n100" data-simplebar>
                    @forelse($conversations as $conversation)
                        <li class="d-flex align-items-start px-4 py-3 border-bottom bg-hover-light-black chat-user-row">
                            <div class="form-check mt-1 me-2 flex-shrink-0">
                                <input class="form-check-input conv-checkbox" type="checkbox" value="{{ $conversation->id }}">
                            </div>
                            <a href="{{ route('manager.helpdesk.conversations.show', $conversation) }}"
                               class="d-flex align-items-start justify-content-between chat-user flex-grow-1 text-decoration-none">
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
                                            <span title="{{ $conversation->channelInfo['label'] }}"
                                                  style="color: {{ $conversation->channelInfo['color'] }}"
                                                  class="channel-icon me-1">
                                                <i class="{{ $conversation->channelInfo['icon'] }}"></i>
                                            </span>
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
                                                    <i class="fas fa-comment"></i> {{ $conversation->getMessageCount() }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end flex-shrink-0">
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
                                <i class="fas fa-inbox helpdesk-empty-icon"></i>
                                <p class="mt-3 mb-0">No hay conversaciones</p>
                            </div>
                        </li>
                    @endforelse
                </ul>
            </div>

            @if($conversations->hasPages())
                <div class="p-3 border-top">
                    {{ $conversations->links() }}
                </div>
            @endif
        </div>

        {{-- Empty state — desktop only --}}
        <div class="flex-fill d-none d-lg-flex align-items-center justify-content-center bg-light">
            <div class="text-center">
                <i class="fas fa-comment-dots helpdesk-welcome-icon"></i>
                <h4 class="mt-4 text-muted">Selecciona una conversación</h4>
                <p class="text-muted mb-4">Elige una conversación del panel izquierdo para ver los detalles</p>
                <a href="{{ route('manager.helpdesk.conversations.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i> Nueva Conversación
                </a>
            </div>
        </div>
    </div>
@endsection

@section('styles')
<style>
    .helpdesk-header {
        z-index: 10;
    }

    .badge {
        font-weight: 500;
    }

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
        background-color: rgba(177, 1, 0, 0.1) !important;
        color: #90bb13 !important;
        font-weight: 600;
    }

    .left-part::-webkit-scrollbar { width: 6px; }
    .left-part::-webkit-scrollbar-track { background: transparent; }
    .left-part::-webkit-scrollbar-thumb { background: #cbd5e0; border-radius: 3px; }
    .left-part::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

    /* Conversation list: full width on mobile, w-30 on desktop */
    .helpdesk-conv-list {
        width: 100%;
        height: calc(100vh - 140px);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    @media (min-width: 992px) {
        .helpdesk-conv-list {
            width: 30%;
        }
    }

    .app-chat {
        flex: 1;
        overflow-y: auto;
    }

    .app-chat::-webkit-scrollbar { width: 6px; }
    .app-chat::-webkit-scrollbar-track { background: transparent; }
    .app-chat::-webkit-scrollbar-thumb { background: #cbd5e0; border-radius: 3px; }
    .app-chat::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

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

    .badge-sm {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
    }

    .channel-icon {
        font-size: 0.85rem;
    }

    .helpdesk-empty-icon {
        font-size: 48px;
        opacity: 0.3;
    }

    .helpdesk-welcome-icon {
        font-size: 120px;
        opacity: 0.2;
        color: #90bb13;
    }

    .bulk-agent-select-width {
        width: auto;
    }

    .flex-fill {
        min-height: calc(100vh - 140px);
    }

    /* Offcanvas sidebar active state */
    #helpdeskSidebar .bg-primary-subtle {
        background-color: rgba(177, 1, 0, 0.1) !important;
        color: #90bb13 !important;
        font-weight: 600;
    }

    #helpdeskSidebar .list-group-item-action:hover {
        background-color: #f8f9fa !important;
    }
</style>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('#toggleFilters').on('click', function() {
            $('#filtersPanel').slideToggle(200);
        });

        @if(request('status') || request('priority') || request('channel'))
            $('#filtersPanel').show();
        @endif

        $(document).on('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                $('.search-chat').focus();
            }
        });

        $('.search-chat').on('keypress', function(e) {
            if (e.which === 13) {
                $(this).closest('form').submit();
            }
        });

        // Desktop: auto-submit on filter change
        $(document).on('change', '.js-auto-submit', function() {
            $(this).closest('form').submit();
        });

        $(document).on('change', '#select-all-conv', function() {
            $('.conv-checkbox').prop('checked', this.checked);
            updateBulkConvBar();
        });

        $(document).on('change', '.conv-checkbox', function() {
            const total = $('.conv-checkbox').length;
            const checked = $('.conv-checkbox:checked').length;
            $('#select-all-conv').prop('indeterminate', checked > 0 && checked < total);
            $('#select-all-conv').prop('checked', checked === total);
            updateBulkConvBar();
        });

        $(document).on('click', '.conv-checkbox', function(e) {
            e.stopPropagation();
        });

        @if(session('success'))
            toastr.success('{{ session('success') }}', 'Exito');
        @endif
        @if(session('error'))
            toastr.error('{{ session('error') }}', 'Error');
        @endif
    });

    function updateBulkConvBar() {
        const count = $('.conv-checkbox:checked').length;
        if (count > 0) {
            $('#bulk-actions-bar').removeClass('d-none');
            $('#selected-count').text(count + ' seleccionadas');
        } else {
            $('#bulk-actions-bar').addClass('d-none');
            $('#bulk-submit-btn').addClass('d-none');
        }
    }

    function clearBulkConvSelection() {
        $('.conv-checkbox, #select-all-conv').prop('checked', false);
        $('#select-all-conv').prop('indeterminate', false);
        $('#bulk-actions-bar').addClass('d-none');
        $('#bulk-submit-btn').addClass('d-none');
    }

    function doBulkAction(action, ids) {
        $('#bulk-action-input').val(action);
        $('#bulk-conversation-ids').empty();
        ids.forEach(function(id) {
            $('#bulk-conversation-ids').append('<input type="hidden" name="conversation_ids[]" value="' + id + '">');
        });
        $('#bulk-form').submit();
    }

    function bulkConvAction(action) {
        const ids = $('.conv-checkbox:checked').map(function() { return this.value; }).get();
        if (ids.length === 0) { return; }

        if (action === 'delete') {
            window.__confirm('¿Eliminar ' + ids.length + ' conversación(es)? Esta acción no se puede deshacer.', function () {
                doBulkAction(action, ids);
            });
            return;
        }
        doBulkAction(action, ids);
    }
</script>
@endsection

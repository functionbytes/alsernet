@extends('layouts.theme')

@section('content')
    {{-- Modern Helpdesk Header --}}
    <div class="bg-white border-bottom sticky-top" style="z-index: 10;">
        <div class="d-flex align-items-center px-4 py-3">
            <h4 class="mb-0 fw-bold">
                <i class="fas fa-inbox me-2 text-primary"></i>
                Conversaciones
            </h4>
        </div>
    </div>

    <div class="d-flex">
        {{-- Modern Sidebar with Modernize Style --}}
        <div class="left-part border-end w-20 flex-shrink-0 d-none d-lg-block">
            {{-- New Conversation Button --}}
            <div class="px-9 pt-4 pb-3">
                <a href="{{ route('manager.helpdesk.conversations.create') }}" class="btn btn-primary fw-semibold py-8 w-100">
                    <i class="fas fa-plus me-2"></i> Nueva Conversación
                </a>
            </div>

            {{-- Sidebar Menu --}}
            <ul class="list-group mh-n100" data-simplebar>
                {{-- VISTAS Section --}}
                <li class="border-bottom my-3"></li>
                <li class="fw-semibold text-dark text-uppercase mx-9 my-2 px-3 fs-2">
                    VISTAS
                    <a href="{{ route('manager.helpdesk.settings.ticket-views.create') }}" class="float-end text-primary" title="Nueva vista">
                        <i class="fas fa-plus fs-4"></i>
                    </a>
                </li>
                @forelse($views as $view)
                    <li class="list-group-item border-0 p-0 mx-9">
                        <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1 {{ $currentView && $currentView->id == $view->id && !request('group') ? 'bg-primary-subtle' : '' }}"
                           href="{{ route('manager.helpdesk.conversations.show', array_merge(['conversation' => $conversation->id], ['viewId' => $view->id])) }}">
                            <i class="fas fa-eye fs-5"></i>{{ $view->name }}
                        </a>
                    </li>
                @empty
                    <li class="list-group-item border-0 p-0 mx-9">
                        <span class="text-muted px-3 py-2 d-block">Sin vistas configuradas</span>
                    </li>
                @endforelse

                {{-- GRUPOS Section --}}
                <li class="border-bottom my-3"></li>
                <li class="fw-semibold text-dark text-uppercase mx-9 my-2 px-3 fs-2">
                    GRUPOS
                    <a href="{{ route('manager.helpdesk.settings.ticket-groups.index') }}" class="float-end text-primary" title="Gestionar">
                        <i class="fas fa-cog fs-4"></i>
                    </a>
                </li>
                <li class="list-group-item border-0 p-0 mx-9">
                    <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1 {{ !request('group') ? 'bg-primary-subtle' : '' }}"
                       href="{{ route('manager.helpdesk.conversations.show', array_merge(['conversation' => $conversation->id], request()->except('group'), $currentView ? ['viewId' => $currentView->id] : [])) }}">
                        <i class="fas fa-users fs-5"></i>Todos los Grupos
                    </a>
                </li>
                @forelse($groups as $group)
                    <li class="list-group-item border-0 p-0 mx-9">
                        <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1 {{ request('group') == $group->id ? 'bg-primary-subtle' : '' }}"
                           href="{{ route('manager.helpdesk.conversations.show', array_merge(['conversation' => $conversation->id], request()->except('group'), ['group' => $group->id], $currentView ? ['viewId' => $currentView->id] : [])) }}">
                            <i class="fas fa-users fs-5"></i>{{ $group->name }}
                        </a>
                    </li>
                @empty
                    <li class="list-group-item border-0 p-0 mx-9">
                        <span class="text-muted px-3 py-2 d-block">Sin grupos configurados</span>
                    </li>
                @endforelse

                {{-- FILTROS Section --}}
                <li class="border-bottom my-3"></li>
                <li class="fw-semibold text-dark text-uppercase mx-9 my-2 px-3 fs-2">
                    FILTROS
                    <button type="button" class="float-end btn btn-sm btn-link text-primary p-0" id="toggleFilters" title="Mostrar/Ocultar">
                        <i class="fas fa-filter fs-4"></i>
                    </button>
                </li>
                <li class="list-group-item border-0 p-0 mx-9" id="filtersPanel" style="display: none;">
                    <form method="GET" action="{{ route('manager.helpdesk.conversations.show', $conversation) }}" id="filtersForm">
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
                                <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>🟢 Baja</option>
                                <option value="normal" {{ request('priority') == 'normal' ? 'selected' : '' }}>🔵 Normal</option>
                                <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>🟡 Alta</option>
                                <option value="urgent" {{ request('priority') == 'urgent' ? 'selected' : '' }}>🔴 Urgente</option>
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
                            <a href="{{ route('manager.helpdesk.conversations.show', array_merge(['conversation' => $conversation->id], $currentView ? ['viewId' => $currentView->id] : [])) }}" class="btn btn-light btn-sm">
                                <i class="fas fa-times me-1"></i> Limpiar
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
                            <i class="fas fa-ellipsis-v"></i>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('manager.helpdesk.conversations.create') }}">
                                    <span><i class="fas fa-plus fs-4"></i></span>Nueva Conversación
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('manager.helpdesk.settings.ticket-views.index') }}">
                                    <span><i class="fas fa-cog fs-4"></i></span>Configurar Vistas
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <form class="position-relative mb-4" method="GET" action="{{ route('manager.helpdesk.conversations.show', $conversation) }}">
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
                        <a href="{{ route('manager.helpdesk.conversations.show', array_merge(['conversation' => $conversation->id], $currentView ? ['viewId' => $currentView->id] : [])) }}" class="text-primary small">
                            <i class="fas fa-times"></i> Limpiar filtros
                        </a>
                    @endif
                </div>
            </div>
            <div class="app-chat">
                <ul class="chat-users mb-0 mh-n100" data-simplebar>
                    @forelse($conversations as $conv)
                        <li>
                            <a href="{{ route('manager.helpdesk.conversations.show', $conv) }}"
                               class="px-4 py-3 bg-hover-light-black d-flex align-items-start justify-content-between chat-user {{ $conv->id == $conversation->id ? 'bg-light-subtle' : '' }}">
                                <div class="d-flex align-items-center">
                                    <span class="position-relative">
                                        <img src="{{ $conv->customer->getAvatarUrl() }}"
                                             alt="{{ $conv->customer->name }}"
                                             width="48"
                                             height="48"
                                             class="rounded-circle">
                                        @if($conv->status->is_open)
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
                                            <span title="{{ $conv->channelInfo['label'] }}"
                                                  style="color: {{ $conv->channelInfo['color'] }}; font-size: 0.85rem;"
                                                  class="me-1">
                                                <i class="{{ $conv->channelInfo['icon'] }}"></i>
                                            </span>
                                            {{ $conv->customer->name }}
                                        </h6>
                                        <span class="fs-3 text-truncate text-body-color d-block">
                                            {{ $conv->subject ?? '(Sin asunto)' }}
                                        </span>
                                        <div class="d-flex gap-1 mt-1">
                                            <span class="badge badge-sm bg-{{ $conv->status->color }}-subtle text-{{ $conv->status->color }}">
                                                {{ $conv->status->name }}
                                            </span>
                                            @if($conv->getMessageCount() > 0)
                                                <span class="badge badge-sm bg-light text-muted">
                                                    <i class="fas fa-comment"></i> {{ $conv->getMessageCount() }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <p class="fs-2 mb-0 text-muted">{{ $conv->updated_at->diffForHumans(null, true) }}</p>
                                    @if(!$conv->assignee)
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
                                <i class="fas fa-inbox" style="font-size: 48px; opacity: 0.3;"></i>
                                <p class="mt-3 mb-0">No hay conversaciones</p>
                            </div>
                        </li>
                    @endforelse
                </ul>
            </div>

            {{-- Pagination --}}
            @if($conversations->hasPages())
                <div class="p-3 border-top">
                    {{ $conversations->appends(request()->except('page'))->links() }}
                </div>
            @endif
        </div>

        {{-- Conversation Detail Area --}}
        <div class="flex-fill d-flex">
            {{-- Messages Column --}}
            <div class="flex-fill bg-white d-flex flex-column" style="height: calc(100vh - 140px);">
                {{-- Conversation Header --}}
                <div class="border-bottom p-3 bg-white d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <h5 class="mb-0 fw-semibold">{{ $conversation->subject ?: '(Sin asunto)' }}</h5>
                        <span class="badge bg-{{ $conversation->status->color }}-subtle text-{{ $conversation->status->color }}">
                            {{ $conversation->status->name }}
                        </span>
                        <span title="{{ $conversation->channelInfo['label'] }}"
                              style="color: {{ $conversation->channelInfo['color'] }}; font-size: 1rem;">
                            <i class="{{ $conversation->channelInfo['icon'] }}"></i>
                        </span>
                    </div>
                    <button type="button" class="btn btn-light btn-sm d-none d-xl-block" id="toggleSidebarBtn" onclick="toggleCustomerSidebar()">
                        <i class="fas fa-bars"></i>
                        <span class="ms-1">Ocultar</span>
                    </button>
                </div>

                {{-- Messages Container --}}
                <div class="flex-grow-1 p-4" style="overflow-y: auto;" id="messagesContainer">
                    @forelse($conversation->items as $item)
                        @if($item->isMessage())
                            {{-- Message Bubble --}}
                            <div class="mb-4 {{ $item->isFromAgent() ? 'text-end' : '' }}">
                                <div class="d-inline-flex align-items-start gap-3 {{ $item->isFromAgent() ? 'flex-row-reverse' : '' }}">
                                    {{-- Avatar --}}
                                    <img src="{{ $item->sender_avatar }}"
                                         class="rounded-circle flex-shrink-0 shadow-sm"
                                         width="40"
                                         height="40"
                                         alt="{{ $item->sender_name }}"
                                         style="border: 2px solid #fff;">

                                    {{-- Message Content --}}
                                    <div class="{{ $item->isFromAgent() ? 'text-end' : '' }}" style="max-width: 65%;">
                                        <div class="d-flex align-items-center gap-2 mb-2 {{ $item->isFromAgent() ? 'justify-content-end' : '' }}">
                                            <small class="fw-bold text-dark">{{ $item->sender_name }}</small>
                                            <small class="text-muted">{{ $item->created_at->format('H:i') }}</small>
                                            @if($item->is_internal)
                                                <span class="badge bg-warning text-dark px-2 py-1" style="font-size: 10px;">
                                                    <i class="fas fa-lock fs-6"></i> Interna
                                                </span>
                                            @endif
                                        </div>

                                        <div class="p-3 rounded-3 shadow-sm {{ $item->isFromAgent() ? 'bg-primary text-white' : 'bg-light' }}"
                                             style="{{ $item->isFromAgent() ? '' : 'border: 1px solid #e0e0e0;' }}">
                                            <div class="message-body">
                                                {!! clean($item->content) !!}
                                            </div>

                                            @if($item->hasAttachments())
                                                <div class="mt-3 pt-3 border-top {{ $item->isFromAgent() ? 'border-white border-opacity-25' : 'border-secondary border-opacity-25' }}">
                                                    @foreach($item->attachment_urls as $attachment)
                                                        <a href="{{ $attachment['url'] ?? '#' }}"
                                                           class="d-inline-flex align-items-center gap-2 btn btn-sm {{ $item->isFromAgent() ? 'btn-light' : 'btn-white border' }} me-2 mb-2 px-3 py-2 rounded-pill"
                                                           target="_blank"
                                                           style="text-decoration: none;">
                                                            <i class="fas fa-file-alt fs-5"></i>
                                                            <span class="small">{{ $attachment['name'] ?? 'Adjunto' }}</span>
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @empty
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-comment-slash" style="font-size: 64px; opacity: 0.2;"></i>
                            <p class="mt-3">No hay mensajes en esta conversación</p>
                        </div>
                    @endforelse
                </div>

                {{-- Message Input Form --}}
                <div class="border-top p-3 bg-light">
                    <form method="POST" action="{{ route('manager.helpdesk.conversations.messages.store', $conversation) }}" enctype="multipart/form-data" id="messageForm">
                        @csrf
                        <div class="mb-2">
                            <textarea name="body" class="form-control" rows="3" placeholder="Escriba su respuesta..." required></textarea>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex gap-2">
                                {{-- Attachments --}}
                                <label class="btn btn-light btn-sm" for="attachmentInput">
                                    <i class="fas fa-paperclip"></i>
                                </label>
                                <input type="file" id="attachmentInput" name="attachments[]" multiple class="d-none">

                                {{-- Internal Note Toggle --}}
                                <button type="button" class="btn btn-light btn-sm" id="internalToggle" onclick="toggleInternal()">
                                    <i class="fas fa-lock"></i>
                                    <span class="d-none d-md-inline ms-1">Nota interna</span>
                                </button>
                                <input type="hidden" name="is_internal" id="isInternalInput" value="0">

                                {{-- Canned Replies --}}
                                <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#cannedRepliesModal">
                                    <i class="fas fa-file-alt"></i>
                                    <span class="d-none d-md-inline ms-1">Plantillas</span>
                                </button>
                            </div>

                            <div class="d-flex gap-2">
                                @if($conversation->isOpen())
                                    <button type="submit" name="action" value="send_and_close" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-paper-plane me-1"></i>
                                        <span class="d-none d-md-inline">Enviar y Cerrar</span>
                                        <span class="d-inline d-md-none">Cerrar</span>
                                    </button>
                                @endif
                                <button type="submit" name="action" value="send" class="btn btn-primary btn-sm">
                                    <i class="fas fa-paper-plane me-1"></i> Enviar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Customer Details Sidebar --}}
            <div id="customerSidebar" class="w-30 border-start bg-light d-none d-xl-block" style="height: calc(100vh - 140px); overflow-y: auto; transition: all 0.3s ease;">
                {{-- Customer Info --}}
                <div class="p-3 border-bottom bg-white">
                    <div class="text-center mb-3">
                        <img src="{{ $conversation->customer->getAvatarUrl() }}"
                             class="rounded-circle mb-2"
                             width="64"
                             height="64"
                             alt="{{ $conversation->customer->name }}">
                        <h6 class="mb-0 fw-semibold">{{ $conversation->customer->name }}</h6>
                        <small class="text-muted d-block">{{ $conversation->customer->email }}</small>
                        @if($conversation->customer->phone)
                            <small class="text-muted d-block">
                                <i class="fas fa-phone me-1"></i>{{ $conversation->customer->phone }}
                            </small>
                        @endif
                        <a href="{{ route('manager.helpdesk.customers.show', $conversation->customer) }}"
                           class="btn btn-sm btn-light mt-2 w-100">
                            <i class="fas fa-user me-1"></i> Ver Perfil
                        </a>
                    </div>
                </div>

                {{-- Conversation Details --}}
                <div class="p-3">
                    <small class="text-uppercase fw-bold text-muted d-block mb-3">
                        <i class="fas fa-info-circle me-1"></i> Detalles
                    </small>

                    {{-- Priority --}}
                    <div class="mb-3">
                        <label class="form-label small text-muted mb-1">
                            <i class="fas fa-flag me-1"></i> Prioridad
                        </label>
                        <select class="form-select form-select-sm js-auto-submit" id="prioritySelect" onchange="updatePriority(this.value)">
                            <option value="low" {{ $conversation->priority == 'low' ? 'selected' : '' }}>Baja</option>
                            <option value="normal" {{ $conversation->priority == 'normal' ? 'selected' : '' }}>Normal</option>
                            <option value="high" {{ $conversation->priority == 'high' ? 'selected' : '' }}>Alta</option>
                            <option value="urgent" {{ $conversation->priority == 'urgent' ? 'selected' : '' }}>Urgente</option>
                        </select>
                    </div>

                    {{-- Assignee --}}
                    <div class="mb-3">
                        <label class="form-label small text-muted mb-1">
                            <i class="fas fa-user-check me-1"></i> Asignado a
                        </label>
                        @if($conversation->assignee)
                            <div class="d-flex align-items-center gap-2 p-2 bg-white rounded border">
                                <img src="{{ $conversation->assignee->getAvatarUrl() ?? 'https://ui-avatars.com/api/?name=' . urlencode($conversation->assignee->name) }}"
                                     class="rounded-circle"
                                     width="28"
                                     height="28"
                                     alt="{{ $conversation->assignee->name }}">
                                <span class="small flex-grow-1">{{ $conversation->assignee->name }}</span>
                                <button class="btn btn-sm btn-link text-danger p-0" onclick="unassignConversation()" title="Desasignar">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        @else
                            <button class="btn btn-sm btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#assignModal">
                                <i class="fas fa-user-plus me-1"></i> Asignar Agente
                            </button>
                        @endif
                    </div>

                    {{-- Tags --}}
                    <div class="mb-3">
                        <label class="form-label small text-muted mb-1">
                            <i class="fas fa-tags me-1"></i> Etiquetas
                        </label>
                        <div class="d-flex flex-wrap gap-1 mb-2" id="tagsContainer">
                            @if($conversation->conversationTags->count() > 0)
                                @foreach($conversation->conversationTags as $tag)
                                    <span class="badge"
                                          data-tag-id="{{ $tag->id }}"
                                          style="background-color: {{ $tag->color ?? '#6c757d' }}; color: white;">
                                        {{ $tag->name }}
                                        <i class="fas fa-times ms-1" style="cursor: pointer;" onclick="removeTagById({{ $tag->id }})"></i>
                                    </span>
                                @endforeach
                            @endif
                        </div>
                        <button class="btn btn-sm btn-outline-secondary w-100" data-bs-toggle="modal" data-bs-target="#tagsModal">
                            <i class="fas fa-plus me-1"></i> Agregar etiqueta
                        </button>
                    </div>

                    {{-- Stats --}}
                    <hr class="my-3">
                    <small class="text-uppercase fw-bold text-muted d-block mb-3">
                        <i class="fas fa-chart-bar me-1"></i> Estadísticas
                    </small>

                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-primary-subtle text-primary rounded p-2">
                                <i class="fas fa-comments fs-5"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <small class="text-muted d-block">Mensajes</small>
                            <strong class="fs-6">{{ $conversation->getMessageCount() }}</strong>
                        </div>
                    </div>

                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-info-subtle text-info rounded p-2">
                                <i class="fas fa-clock fs-5"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <small class="text-muted d-block">Creada</small>
                            <strong class="fs-6">{{ $conversation->created_at->diffForHumans() }}</strong>
                        </div>
                    </div>

                    @if($conversation->first_response_at)
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0 me-3">
                                <div class="bg-success-subtle text-success rounded p-2">
                                    <i class="fas fa-check fs-5"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <small class="text-muted d-block">Primera respuesta</small>
                                <strong class="fs-6">{{ $conversation->first_response_at->diffForHumans() }}</strong>
                            </div>
                        </div>
                    @endif

                    {{-- Conversation Attributes --}}
                    <hr class="my-3">
                    <small class="text-uppercase fw-bold text-muted d-block mb-3">
                        <i class="fas fa-list me-1"></i> Atributos de Conversación
                    </small>

                    <div class="mb-2">
                        <small class="text-muted">Tipo:</small>
                        <strong class="d-block">{{ ucfirst($conversation->channel ?? 'Chat') }}</strong>
                    </div>

                    <div class="mb-2">
                        <small class="text-muted">ID:</small>
                        <strong class="d-block">#{{ $conversation->id }}</strong>
                    </div>

                    <div class="mb-2">
                        <small class="text-muted">Iniciada:</small>
                        <strong class="d-block">{{ $conversation->created_at->diffForHumans() }}</strong>
                    </div>

                    <div class="mb-2">
                        <small class="text-muted">Última actividad:</small>
                        <strong class="d-block">{{ $conversation->updated_at->diffForHumans() }}</strong>
                    </div>

                    <div class="mb-2">
                        <small class="text-muted">Canal:</small>
                        <strong class="d-block" style="color: {{ $conversation->channelInfo['color'] }};">
                            <i class="{{ $conversation->channelInfo['icon'] }} me-1"></i>
                            {{ $conversation->channelInfo['label'] }}
                        </strong>
                    </div>

                    <div class="mb-2">
                        <small class="text-muted">Categoría:</small>
                        <strong class="d-block">{{ $conversation->category ?? '—' }}</strong>
                    </div>

                    {{-- Technology Info --}}
                    <hr class="my-3">
                    <small class="text-uppercase fw-bold text-muted d-block mb-3">
                        <i class="fas fa-laptop me-1"></i> Tecnología
                    </small>

                    <div class="mb-2">
                        <small class="text-muted">
                            <i class="fas fa-globe me-1"></i>IP address:
                        </small>
                        <strong class="d-block">{{ $conversation->customer->last_ip ?? '127.0.0.1' }}</strong>
                    </div>

                    <div class="mb-2">
                        <small class="text-muted">
                            <i class="fas fa-desktop me-1"></i>Platform:
                        </small>
                        <strong class="d-block">{{ $conversation->customer->platform ?? 'OS X' }}</strong>
                    </div>

                    <div class="mb-2">
                        <small class="text-muted">
                            <i class="fab fa-chrome me-1"></i>Browser:
                        </small>
                        <strong class="d-block">{{ $conversation->customer->browser ?? 'Chrome' }}</strong>
                    </div>

                    <div class="mb-2">
                        <small class="text-muted">
                            <i class="fas fa-laptop me-1"></i>Device:
                        </small>
                        <strong class="d-block">{{ $conversation->customer->device ?? 'desktop' }}</strong>
                    </div>

                    {{-- Visited Pages --}}
                    <hr class="my-3">
                    <small class="text-uppercase fw-bold text-muted d-block mb-3">
                        <i class="fas fa-file-alt me-1"></i> Páginas Visitadas
                    </small>

                    @if(isset($conversation->customer->visited_pages) && count($conversation->customer->visited_pages) > 0)
                        <div class="list-group list-group-flush">
                            @foreach($conversation->customer->visited_pages as $page)
                                <div class="list-group-item p-2 border-0 bg-transparent">
                                    <small class="text-truncate d-block">
                                        <i class="fas fa-link me-1"></i>{{ $page }}
                                    </small>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="small text-muted mb-0">
                            <i class="fas fa-info-circle me-1"></i>
                            El usuario no ha visitado ninguna página todavía
                        </p>
                    @endif

                    {{-- Recent Conversations --}}
                    <hr class="my-3">
                    <small class="text-uppercase fw-bold text-muted d-block mb-3">
                        <i class="fas fa-history me-1"></i> Conversaciones Recientes
                    </small>

                    @php
                        $recentConversations = $conversation->customer->conversations()
                            ->where('id', '!=', $conversation->id)
                            ->latest()
                            ->limit(5)
                            ->get();
                    @endphp

                    @if($recentConversations->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($recentConversations as $recent)
                                <a href="{{ route('manager.helpdesk.conversations.show', $recent) }}"
                                   class="list-group-item list-group-item-action p-2 border-0 bg-transparent">
                                    <div class="d-flex align-items-start gap-2">
                                        <span class="badge bg-{{ $recent->status->color }}-subtle text-{{ $recent->status->color }} flex-shrink-0">
                                            {{ $recent->status->name }}
                                        </span>
                                        <div class="flex-grow-1 overflow-hidden">
                                            <small class="text-truncate d-block fw-semibold">
                                                {{ $recent->subject ?: '(Sin asunto)' }}
                                            </small>
                                            <small class="text-muted">
                                                {{ $recent->created_at->diffForHumans() }}
                                            </small>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <p class="small text-muted mb-0">
                            <i class="fas fa-info-circle me-1"></i>
                            No hay conversaciones recientes
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Assign Agent Modal --}}
    <div class="modal fade" id="assignModal" tabindex="-1" aria-labelledby="assignModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="assignModalLabel">
                        <i class="fas fa-user-plus me-2"></i>Asignar Agente
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Buscar agente</label>
                        <input type="text" class="form-control" id="agentSearch" placeholder="Buscar por nombre...">
                    </div>

                    <div class="list-group" id="agentsList">
                        @php
                            $agents = \App\Models\User::whereHas('roles', function($q) {
                                $q->whereIn('name', ['agent', 'admin', 'super-admin']);
                            })->get();
                        @endphp

                        @forelse($agents as $user)
                            <button type="button"
                                    class="list-group-item list-group-item-action d-flex align-items-center gap-3"
                                    onclick="assignAgent({{ $user->id }})">
                                <img src="{{ $user->getAvatarUrl() ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->name) }}"
                                     class="rounded-circle"
                                     width="36"
                                     height="36"
                                     alt="{{ $user->name }}">
                                <div class="flex-grow-1">
                                    <strong class="d-block">{{ $user->name }}</strong>
                                    <small class="text-muted">{{ $user->email }}</small>
                                </div>
                            </button>
                        @empty
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                No hay agentes disponibles.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Canned Replies Modal --}}
    <div class="modal fade" id="cannedRepliesModal" tabindex="-1" aria-labelledby="cannedRepliesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cannedRepliesModalLabel">
                        <i class="fas fa-file-alt me-2"></i>Plantillas de Respuestas
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <input type="text" class="form-control" id="cannedSearch" placeholder="Buscar plantilla...">
                    </div>

                    <div class="row g-3">
                        @php
                            $cannedReplies = \Modules\Helpdesk\Models\CannedReply::forUser(auth()->id())
                                ->latest()
                                ->get();
                        @endphp

                        @forelse($cannedReplies as $reply)
                            <div class="col-12 canned-reply-item">
                                <div class="card border">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="mb-0">{{ $reply->title }}</h6>
                                            @if($reply->category)
                                                <span class="badge bg-primary-subtle text-primary">{{ $reply->category }}</span>
                                            @endif
                                        </div>
                                        <p class="small text-muted mb-2">{{ $reply->preview }}</p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <i class="fas fa-chart-line me-1"></i>Usado {{ $reply->usage_count }} veces
                                            </small>
                                            <button type="button"
                                                    class="btn btn-sm btn-primary"
                                                    onclick="insertCannedReply(`{{ addslashes($reply->body) }}`, {{ $reply->id }})">
                                                <i class="fas fa-plus me-1"></i>Usar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    No hay plantillas disponibles. Crea una nueva desde el panel de configuración.
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tags Modal --}}
    <div class="modal fade" id="tagsModal" tabindex="-1" aria-labelledby="tagsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tagsModalLabel">
                        <i class="fas fa-tags me-2"></i>Agregar Etiqueta
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Buscar etiqueta</label>
                        <input type="text" class="form-control" id="tagSearchInput" placeholder="Buscar por nombre...">
                        <small class="text-muted">Filtra las etiquetas disponibles</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Etiquetas disponibles</label>
                        @if($availableTags->count() > 0)
                            <div id="availableTagsList" class="d-flex flex-wrap gap-2" style="max-height: 300px; overflow-y: auto;">
                                @foreach($availableTags as $tag)
                                    @php
                                        $isAssigned = $conversation->conversationTags->contains($tag->id);
                                    @endphp
                                    <button type="button"
                                            class="btn btn-sm tag-option {{ $isAssigned ? 'btn-secondary disabled' : 'btn-outline-primary' }}"
                                            data-tag-id="{{ $tag->id }}"
                                            data-tag-name="{{ $tag->name }}"
                                            data-tag-color="{{ $tag->color ?? '#6c757d' }}"
                                            onclick="addTagById({{ $tag->id }}, '{{ addslashes($tag->name) }}', '{{ $tag->color ?? '#6c757d' }}')"
                                            {{ $isAssigned ? 'disabled' : '' }}
                                            style="border-color: {{ $tag->color ?? '#6c757d' }}; color: {{ $tag->color ?? '#6c757d' }};">
                                        @if($tag->color)
                                            <span class="d-inline-block me-1" style="width: 12px; height: 12px; background-color: {{ $tag->color }}; border-radius: 50%;"></span>
                                        @endif
                                        {{ $tag->name }}
                                        @if($isAssigned)
                                            <i class="fas fa-check ms-1"></i>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                No hay etiquetas disponibles. Crea una nueva desde
                                <a href="{{ route('manager.helpdesk.settings.tickets.tags.create') }}" target="_blank">configuración</a>.
                            </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="{{ route('manager.helpdesk.settings.tickets.tags.create') }}" target="_blank" class="btn btn-link me-auto">
                        <i class="fas fa-plus me-1"></i> Crear nueva etiqueta
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css')
<style>
    /* Modern Helpdesk Styles */
    .badge {
        font-weight: 500;
        padding: 0.35em 0.65em;
    }

    /* Modal Improvements */
    .modal-content {
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        border: none;
    }

    .modal-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px 12px 0 0;
        padding: 1.25rem 1.5rem;
    }

    .modal-header .btn-close {
        filter: brightness(0) invert(1);
        opacity: 0.8;
    }

    .modal-header .btn-close:hover {
        opacity: 1;
    }

    .modal-title {
        font-weight: 600;
        font-size: 1.1rem;
    }

    .modal-body {
        padding: 1.5rem;
    }

    .modal-footer {
        border-top: 1px solid #e9ecef;
        padding: 1rem 1.5rem;
    }

    /* Card Improvements */
    .card {
        border-radius: 10px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
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

    /* Messages Container */
    #messagesContainer {
        scrollbar-width: thin;
        scrollbar-color: #cbd5e0 #f1f5f9;
    }

    #messagesContainer::-webkit-scrollbar {
        width: 8px;
    }

    #messagesContainer::-webkit-scrollbar-track {
        background: #f1f5f9;
    }

    #messagesContainer::-webkit-scrollbar-thumb {
        background: #cbd5e0;
        border-radius: 4px;
    }

    #messagesContainer::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Message Body Formatting */
    .message-body {
        word-wrap: break-word;
        white-space: pre-wrap;
    }

    .w-30 {
        width: 30%;
    }


</style>
@endpush

@push('scripts')
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

        // Auto-scroll to bottom of messages
        const messagesContainer = document.getElementById('messagesContainer');
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        // File attachment preview
        $('#attachmentInput').on('change', function() {
            const fileCount = this.files.length;
            if (fileCount > 0) {
                toastr.info(`${fileCount} archivo(s) seleccionado(s)`);
            }
        });

        // Search agents in modal
        $('#agentSearch').on('keyup', function() {
            const searchTerm = $(this).val().toLowerCase();
            $('#agentsList .list-group-item').each(function() {
                const text = $(this).text().toLowerCase();
                $(this).toggle(text.includes(searchTerm));
            });
        });

        // Search canned replies in modal
        $('#cannedSearch').on('keyup', function() {
            const searchTerm = $(this).val().toLowerCase();
            $('.canned-reply-item').each(function() {
                const text = $(this).text().toLowerCase();
                $(this).toggle(text.includes(searchTerm));
            });
        });
    });

    // Toggle internal note
    function toggleInternal() {
        const input = document.getElementById('isInternalInput');
        const button = document.getElementById('internalToggle');

        if (input.value === '0') {
            input.value = '1';
            button.classList.add('btn-warning');
            button.classList.remove('btn-light');
            toastr.info('Nota interna activada');
        } else {
            input.value = '0';
            button.classList.remove('btn-warning');
            button.classList.add('btn-light');
        }
    }

    // Update priority
    function updatePriority(priority) {
        fetch('{{ route('manager.helpdesk.conversations.update', $conversation) }}', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ priority: priority })
        })
        .then(response => response.json())
        .then(data => {
            toastr.success('Prioridad actualizada');
            setTimeout(() => location.reload(), 1000);
        })
        .catch(error => {
            toastr.error('Error al actualizar prioridad');
        });
    }

    // Unassign conversation
    function unassignConversation() {
        if (!confirm('¿Desasignar esta conversación?')) return;

        fetch('{{ route('manager.helpdesk.conversations.update', $conversation) }}', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ assignee_id: null })
        })
        .then(response => response.json())
        .then(data => {
            toastr.success('Conversación desasignada');
            setTimeout(() => location.reload(), 1000);
        })
        .catch(error => {
            toastr.error('Error al desasignar');
        });
    }

    // Toggle customer sidebar
    function toggleCustomerSidebar() {
        const sidebar = document.getElementById('customerSidebar');
        const button = document.getElementById('toggleSidebarBtn');

        if (sidebar.classList.contains('d-none')) {
            sidebar.classList.remove('d-none');
            button.innerHTML = '<i class="fas fa-bars"></i><span class="ms-1">Ocultar</span>';
        } else {
            sidebar.classList.add('d-none');
            button.innerHTML = '<i class="fas fa-bars"></i><span class="ms-1">Mostrar</span>';
        }
    }

    // Assign agent
    function assignAgent(userId) {
        fetch('{{ route('manager.helpdesk.conversations.update', $conversation) }}', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ assignee_id: userId })
        })
        .then(response => response.json())
        .then(data => {
            toastr.success('Agente asignado');
            setTimeout(() => location.reload(), 1000);
        })
        .catch(error => {
            toastr.error('Error al asignar agente');
        });
    }

    // Insert canned reply
    function insertCannedReply(content, replyId) {
        const textarea = document.querySelector('textarea[name="body"]');
        textarea.value = content;
        textarea.focus();

        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('cannedRepliesModal'));
        modal.hide();

        // Increment usage count
        if (replyId) {
            fetch(`/manager/helpdesk/canned-replies/${replyId}/use`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
        }

        toastr.success('Plantilla insertada');
    }

    // Add tag by ID
    function addTagById(tagId, tagName, tagColor) {
        fetch('{{ route('manager.helpdesk.conversations.update', $conversation) }}', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                action: 'add_tag',
                tag_id: tagId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                toastr.success('Etiqueta agregada');

                // Add tag to container
                const tagsContainer = document.getElementById('tagsContainer');
                const newBadge = document.createElement('span');
                newBadge.className = 'badge';
                newBadge.setAttribute('data-tag-id', tagId);
                newBadge.style.backgroundColor = tagColor || '#6c757d';
                newBadge.style.color = 'white';
                newBadge.innerHTML = `${tagName} <i class="fas fa-times ms-1" style="cursor: pointer;" onclick="removeTagById(${tagId})"></i>`;
                tagsContainer.appendChild(newBadge);

                // Disable button in modal
                const button = document.querySelector(`.tag-option[data-tag-id="${tagId}"]`);
                if (button) {
                    button.classList.remove('btn-outline-primary');
                    button.classList.add('btn-secondary', 'disabled');
                    button.disabled = true;
                    button.innerHTML += ' <i class="fas fa-check ms-1"></i>';
                }

                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('tagsModal'));
                if (modal) modal.hide();
            }
        })
        .catch(error => {
            console.error(error);
            toastr.error('Error al agregar etiqueta');
        });
    }

    // Remove tag by ID
    function removeTagById(tagId) {
        if (!confirm('¿Eliminar esta etiqueta?')) return;

        fetch('{{ route('manager.helpdesk.conversations.update', $conversation) }}', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                action: 'remove_tag',
                tag_id: tagId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                toastr.success('Etiqueta eliminada');

                // Remove badge from container
                const badge = document.querySelector(`#tagsContainer [data-tag-id="${tagId}"]`);
                if (badge) {
                    badge.remove();
                }

                // Re-enable button in modal if it exists
                const button = document.querySelector(`.tag-option[data-tag-id="${tagId}"]`);
                if (button) {
                    button.classList.remove('btn-secondary', 'disabled');
                    button.classList.add('btn-outline-primary');
                    button.disabled = false;
                    // Remove check icon
                    const checkIcon = button.querySelector('.fa-check');
                    if (checkIcon) checkIcon.remove();
                }
            }
        })
        .catch(error => {
            console.error(error);
            toastr.error('Error al eliminar etiqueta');
        });
    }

    // Tag search functionality
    $('#tagSearchInput').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('.tag-option').each(function() {
            const tagName = $(this).data('tag-name').toLowerCase();
            if (tagName.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // Clear search when modal is closed
    $('#tagsModal').on('hidden.bs.modal', function() {
        $('#tagSearchInput').val('');
        $('.tag-option').show();
    });
</script>
@endpush

{{-- Variables: $conversations, $nextCursor, $hasMore, $filter --}}
@php
    $filter   = $filter ?? 'all';
    $hasMore  = $hasMore ?? false;
    $nextCursor = $nextCursor ?? null;
@endphp
<div class="container-inbox-items min-width-340">
    <div class="border-end user-chat-box h-100 d-flex flex-column">
        <!-- Busqueda y filtros -->
        <div class="p-3 d-none d-lg-block flex-shrink-0 border-bottom">
            <div class="mb-3">
                <div class="input-group input-group">
                    <span class="input-group-text bg-white">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" class="form-control" id="conversation-search" placeholder="Buscar conversaciones, mensajes, contactos..." autocomplete="off">
                </div>
            </div>

            <button class="btn btn-secondary w-100" type="button" data-bs-toggle="modal" data-bs-target="#advancedFiltersModal">
                <i class="fas fa-filter me-1"></i>
            </button>
        </div>

        <!-- Lista de conversaciones -->
        <div class="container-inbox-chat">
            <ul class="inbox-chat-items" id="conversation-list-items">
                @if($conversations->isEmpty())
                    <li class="text-center py-5">
                        <i class="fas fa-comments fs-1 text-muted mb-3 d-block"></i>
                        <p class="text-muted">No se encontraron conversaciones</p>
                    </li>
                @else
                    @foreach($conversations as $conversation)
                        <li>
                            <a href="javascript:void(0)"
                               class="inbox-chat-item {{ $loop->first ? 'active' : '' }}"
                               data-conversation-id="{{ $conversation->id }}">
                                <div class="position-relative w-100 ms-2">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h6 class="customer">{{ $conversation->customer?->name }}</h6>
                                        <p class="mb-0 fs-2 text-muted">{{ $conversation->last_activity_at?->format('h:i a') ?? 'N/A' }}</p>
                                    </div>
                                    <span class="message">
                                        {{ stripTags($conversation->messages->last()?->content, 100, '...', 'Sin mensajes') }}
                                    </span>
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <span class="d-block">
                                                @if($conversation->priority?->slug === 'urgent')
                                                    <i class="fas fa-exclamation-circle text-danger" title="{{ $conversation->priority->name }}"></i>
                                                @elseif($conversation->priority?->slug === 'high')
                                                    <i class="fas fa-exclamation-circle text-warning" title="{{ $conversation->priority->name }}"></i>
                                                @elseif($conversation->priority)
                                                    <i class="fas fa-exclamation-circle text-muted" title="{{ $conversation->priority->name }}"></i>
                                                @else
                                                    <i class="fas fa-exclamation-circle text-muted"></i>
                                                @endif
                                            </span>
                                        </div>
                                        <span class="badge">{{ $conversation->inbox?->channel_display_name ?? 'Sin canal' }}</span>
                                    </div>
                                </div>
                            </a>
                        </li>
                    @endforeach
                @endif
            </ul>

            <!-- Cargar mas conversaciones (cursor pagination) -->
            @if($hasMore)
                <div class="px-3 py-2" id="load-more-container">
                    <button type="button"
                            class="btn btn-secondary w-100 btn-sm"
                            id="btn-load-more"
                            data-filter="{{ $filter }}"
                            data-cursor="{{ $nextCursor }}"
                            data-load-more-url="{{ route('chat.conversations.loadMore') }}"
                            @isset($inbox) data-inbox-id="{{ $inbox->id }}" @endisset
                            @isset($team) data-team-id="{{ $team->id }}" @endisset
                            @isset($label) data-label="{{ $label->name }}" @endisset>
                        <i class="fas fa-chevron-down me-1"></i>
                        Cargar mas
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

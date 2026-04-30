{{-- Variables: $filter, $filterCounts, $statusCounts, $priorityCounts, $inboxes, $activeFilters, $teams, $labels --}}
<div class="container-filter left-part border-end w-20 flex-shrink-0 d-none d-lg-block">
    <div class="px-9 pt-4 pb-3">
        <button type="button" class="btn btn-primary fw-semibold py-8 w-100" data-bs-toggle="modal" data-bs-target="#newConversationModal">
            Componer
        </button>
    </div>
    <div class="px-9 pb-2">
        <a href="{{ route('chat.search.index') }}"
           class="d-flex align-items-center gap-2 text-muted py-8 px-3 rounded-1 text-decoration-none {{ isset($filter) && $filter === 'search' ? 'bg-light-info fw-semibold text-dark' : '' }}"
           style="font-size:.875rem">
            <i class="fas fa-search fs-6"></i>
            <span>Buscar...</span>
            <kbd class="ms-auto bg-light text-muted border rounded px-1" style="font-size:.7rem">/</kbd>
        </a>
    </div>
    <ul class="list-group mh-n100">
        <li class="list-group-item border-0 p-0 mx-9">
            <a href="{{ route('chat.conversations.index') }}"
               class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1 {{ !isset($filter) || $filter === 'all' ? 'bg-light-info' : '' }}">
                <i class="fas fa-inbox fs-5"></i>Todas
                @if($filterCounts['all'] > 0)
                    <span class="badge rounded-circle ms-auto">{{ $filterCounts['all'] }}</span>
                @endif
            </a>
        </li>
        <li class="list-group-item border-0 p-0 mx-9">
            <a href="{{ route('chat.conversations.mine') }}"
               class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1 {{ isset($filter) && $filter === 'mine' ? 'bg-light-info' : '' }}">
                <i class="fas fa-user-check fs-5"></i>Mías
                @if($filterCounts['mine'] > 0)
                    <span class="badge rounded-circle ms-auto">{{ $filterCounts['mine'] }}</span>
                @endif
            </a>
        </li>
        <li class="list-group-item border-0 p-0 mx-9">
            <a href="{{ route('chat.conversations.unassigned') }}"
               class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1 {{ isset($filter) && $filter === 'unassigned' ? 'bg-light-info' : '' }}">
                <i class="fas fa-user-times fs-5"></i>Sin asignar
                @if($filterCounts['unassigned'] > 0)
                    <span class="badge rounded-circle ms-auto">{{ $filterCounts['unassigned'] }}</span>
                @endif
            </a>
        </li>
        <li class="list-group-item border-0 p-0 mx-9">
            <a href="{{ route('chat.conversations.mentions') }}"
               class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1 {{ isset($filter) && $filter === 'mentions' ? 'bg-light-info' : '' }}">
                <i class="fas fa-at fs-5"></i>Menciones
                @if($filterCounts['mentions'] > 0)
                    <span class="badge rounded-circle ms-auto">{{ $filterCounts['mentions'] }}</span>
                @endif
            </a>
        </li>
        <li class="list-group-item border-0 p-0 mx-9">
            <a href="{{ route('chat.conversations.unattended') }}"
               class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1 {{ isset($filter) && $filter === 'unattended' ? 'bg-light-info' : '' }}">
                <i class="fas fa-exclamation-triangle fs-5"></i>Sin atender
                @if($filterCounts['unattended'] > 0)
                    <span class="badge rounded-circle ms-auto">{{ $filterCounts['unattended'] }}</span>
                @endif
            </a>
        </li>

        <li class="border-bottom my-3"></li>

        <!-- Estado -->
        <li class="list-group-item border-0 p-0 mx-9">
            <h6 class="fw-semibold text-dark fs-4 mb-3">ESTADO</h6>
        </li>
        <li class="list-group-item border-0 p-0 mx-9">
            <a href="{{ route('chat.conversations.index', ['status' => 'open']) }}"
               class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1 {{ isset($activeFilters['status']) && $activeFilters['status'] === 'open' ? 'bg-light-info' : '' }}">
                <i class="fas fa-inbox fs-5 text-primary"></i>Abierto
                @if($statusCounts['open'] > 0)
                    <span class="badge rounded-circle ms-auto">{{ $statusCounts['open'] }}</span>
                @endif
            </a>
        </li>
        <li class="list-group-item border-0 p-0 mx-9">
            <a href="{{ route('chat.conversations.index', ['status' => 'pending']) }}"
               class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1 {{ isset($activeFilters['status']) && $activeFilters['status'] === 'pending' ? 'bg-light-info' : '' }}">
                <i class="fas fa-clock fs-5 text-warning"></i>Pendiente
                @if($statusCounts['pending'] > 0)
                    <span class="badge bg-warning rounded-circle ms-auto">{{ $statusCounts['pending'] }}</span>
                @endif
            </a>
        </li>
        <li class="list-group-item border-0 p-0 mx-9">
            <a href="{{ route('chat.conversations.index', ['status' => 'resolved']) }}"
               class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1 {{ isset($activeFilters['status']) && $activeFilters['status'] === 'resolved' ? 'bg-light-info' : '' }}">
                <i class="fas fa-check-circle fs-5 text-success"></i>Resuelto
                @if($statusCounts['resolved'] > 0)
                    <span class="badge bg-success rounded-circle ms-auto">{{ $statusCounts['resolved'] }}</span>
                @endif
            </a>
        </li>
        @if($statusCounts['closed'] > 0)
            <li class="list-group-item border-0 p-0 mx-9">
                <a href="{{ route('chat.conversations.index', ['status' => 'closed']) }}"
                   class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1 {{ isset($activeFilters['status']) && $activeFilters['status'] === 'closed' ? 'bg-light-info' : '' }}">
                    <i class="fas fa-times-circle fs-5 text-secondary"></i>Cerrado
                    <span class="badge bg-secondary rounded-circle ms-auto">{{ $statusCounts['closed'] }}</span>
                </a>
            </li>
        @endif

        <li class="border-bottom my-3"></li>

        <!-- Prioridad -->
        <li class="list-group-item border-0 p-0 mx-9">
            <h6 class="fw-semibold text-dark fs-4 mb-3">PRIORIDAD</h6>
        </li>
        <li class="list-group-item border-0 p-0 mx-9">
            <a href="{{ route('chat.conversations.index', ['priority' => 'urgent']) }}"
               class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1 {{ isset($activeFilters['priority']) && $activeFilters['priority'] === 'urgent' ? 'bg-light-info' : '' }}">
                <i class="fas fa-bookmark fs-5 text-danger"></i>Urgente
                @if($priorityCounts['urgent'] > 0)
                    <span class="badge bg-danger rounded-circle ms-auto">{{ $priorityCounts['urgent'] }}</span>
                @endif
            </a>
        </li>
        <li class="list-group-item border-0 p-0 mx-9">
            <a href="{{ route('chat.conversations.index', ['priority' => 'high']) }}"
               class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1 {{ isset($activeFilters['priority']) && $activeFilters['priority'] === 'high' ? 'bg-light-info' : '' }}">
                <i class="fas fa-bookmark fs-5 text-warning"></i>Alta
                @if($priorityCounts['high'] > 0)
                    <span class="badge bg-warning rounded-circle ms-auto">{{ $priorityCounts['high'] }}</span>
                @endif
            </a>
        </li>
        <li class="list-group-item border-0 p-0 mx-9">
            <a href="{{ route('chat.conversations.index', ['priority' => 'medium']) }}"
               class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1 {{ isset($activeFilters['priority']) && $activeFilters['priority'] === 'medium' ? 'bg-light-info' : '' }}">
                <i class="fas fa-bookmark fs-5 text-info"></i>Media
                @if($priorityCounts['medium'] > 0)
                    <span class="badge bg-info rounded-circle ms-auto">{{ $priorityCounts['medium'] }}</span>
                @endif
            </a>
        </li>
        <li class="list-group-item border-0 p-0 mx-9">
            <a href="{{ route('chat.conversations.index', ['priority' => 'low']) }}"
               class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1 {{ isset($activeFilters['priority']) && $activeFilters['priority'] === 'low' ? 'bg-light-info' : '' }}">
                <i class="fas fa-bookmark fs-5 text-secondary"></i>Baja
                @if($priorityCounts['low'] > 0)
                    <span class="badge bg-secondary rounded-circle ms-auto">{{ $priorityCounts['low'] }}</span>
                @endif
            </a>
        </li>

        <li class="border-bottom my-3"></li>

        <!-- Canales -->
        <li class="list-group-item border-0 p-0 mx-9">
            <h6 class="fw-semibold text-dark fs-4 mb-3">CANALES</h6>
        </li>
        @foreach($inboxes as $channelInbox)
            <li class="list-group-item border-0 p-0 mx-9">
                <a href="{{ route('chat.conversations.index', ['inbox' => $channelInbox->id]) }}"
                   class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1 {{ isset($activeFilters['inbox']) && $activeFilters['inbox'] == $channelInbox->id ? 'bg-light-info' : '' }}">
                    @if($channelInbox->channel_type === 'Modules\Chat\Models\Channels\Web')
                        <i class="fas fa-globe fs-5 text-primary"></i>
                    @elseif($channelInbox->channel_type === 'Modules\Chat\Models\Channels\Email')
                        <i class="fas fa-envelope fs-5 text-warning"></i>
                    @elseif($channelInbox->channel_type === 'Modules\Chat\Models\Channels\Facebook')
                        <i class="fab fa-facebook fs-5 text-primary"></i>
                    @elseif($channelInbox->channel_type === 'Modules\Chat\Models\Channels\Instagram')
                        <i class="fab fa-instagram fs-5 text-danger"></i>
                    @elseif($channelInbox->channel_type === 'Modules\Chat\Models\Channels\Whatsapp')
                        <i class="fab fa-whatsapp fs-5 text-success"></i>
                    @else
                        <i class="fas fa-comments fs-5 text-secondary"></i>
                    @endif
                    <span class="text-truncate">{{ $channelInbox->name }}</span>
                    @if($channelInbox->conversations_count > 0)
                        <span class="badge bg-secondary rounded-circle ms-auto">{{ $channelInbox->conversations_count }}</span>
                    @endif
                </a>
            </li>
        @endforeach

        <li class="border-bottom my-3"></li>

        <!-- Equipos -->
        @if($teams->isNotEmpty())
            <li class="list-group-item border-0 p-0 mx-9">
                <h6 class="fw-semibold text-dark fs-4 mb-3">EQUIPOS</h6>
            </li>
            @foreach($teams as $team)
                <li class="list-group-item border-0 p-0 mx-9">
                    <a href="{{ route('chat.conversations.index', ['team' => $team->id]) }}"
                       class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1 {{ isset($activeFilters['team']) && $activeFilters['team'] == $team->id ? 'bg-light-info' : '' }}">
                        <i class="fas fa-users fs-5 text-info"></i>
                        <span class="text-truncate">{{ $team->name }}</span>
                        @if($team->conversations_count > 0)
                            <span class="badge bg-secondary rounded-circle ms-auto">{{ $team->conversations_count }}</span>
                        @endif
                    </a>
                </li>
            @endforeach

            <li class="border-bottom my-3"></li>
        @endif

        <!-- Etiquetas -->
        <li class="list-group-item border-0 p-0 mx-9">
            <h6 class="fw-semibold text-dark fs-4 mb-3">ETIQUETAS</h6>
        </li>
        @forelse($labels as $labelItem)
            <li class="list-group-item border-0 p-0 mx-9">
                <a href="{{ route('chat.conversations.index', ['labels' => $labelItem->name]) }}"
                   class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1">
                    <i class="fas fa-bookmark fs-5" style="color: {{ $labelItem->color }};"></i>
                    <span class="text-truncate">{{ $labelItem->name }}</span>
                    @if($labelItem->conversations_count > 0)
                        <span class="badge bg-secondary rounded-circle ms-auto">{{ $labelItem->conversations_count }}</span>
                    @endif
                </a>
            </li>
        @empty
            <li class="list-group-item border-0 p-0 mx-9">
                <span class="text-muted px-3 py-2 d-block">Sin etiquetas</span>
            </li>
        @endforelse
    </ul>
</div>

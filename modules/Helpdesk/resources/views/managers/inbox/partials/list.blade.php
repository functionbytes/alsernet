{{-- Lista de conversaciones (Alvarez/Refined v4) --}}
<div class="bv-list">
    <div class="bv-list-head">
        <div class="bv-list-search">
            <i class="fas fa-magnifying-glass bv-list-search-icon"></i>
            <input type="text" id="bv-search-input" placeholder="Buscar conversaciones…" autocomplete="off">
            <kbd class="bv-list-search-kbd">F</kbd>
        </div>
        <div class="bv-list-actions">
            <button class="btn-ico" data-bv-modal="filter" title="Filtros">
                <i class="fas fa-filter"></i>
            </button>
            <div class="bv-sort-wrap">
                <button class="btn-ico" id="bv-btn-sort" title="Ordenar">
                    <i class="fas fa-arrow-up-arrow-down"></i>
                </button>
                <div class="bv-sort-menu" id="bv-sort-menu">
                    <div class="bv-sort-menu-head">Ordenar por</div>
                    <button class="bv-sort-opt on" data-sort="newest">
                        <i class="bv-sort-opt-ico fas fa-arrow-down-wide-short"></i>
                        <span class="bv-sort-opt-label">Más reciente</span>
                        <i class="bv-sort-opt-check fas fa-check"></i>
                    </button>
                    <button class="bv-sort-opt" data-sort="oldest">
                        <i class="bv-sort-opt-ico fas fa-arrow-up-wide-short"></i>
                        <span class="bv-sort-opt-label">Más antiguo</span>
                        <i class="bv-sort-opt-check fas fa-check"></i>
                    </button>
                    <button class="bv-sort-opt" data-sort="priority">
                        <i class="bv-sort-opt-ico fas fa-fire"></i>
                        <span class="bv-sort-opt-label">Prioridad</span>
                        <i class="bv-sort-opt-check fas fa-check"></i>
                    </button>
                    <div class="bv-sort-menu-sep"></div>
                    <button class="bv-sort-opt" data-sort="unassigned">
                        <i class="bv-sort-opt-ico fas fa-user-slash"></i>
                        <span class="bv-sort-opt-label">Sin asignar</span>
                        <i class="bv-sort-opt-check fas fa-check"></i>
                    </button>
                    <button class="bv-sort-opt" data-sort="unread">
                        <i class="bv-sort-opt-ico far fa-envelope"></i>
                        <span class="bv-sort-opt-label">Sin leer</span>
                        <i class="bv-sort-opt-check fas fa-check"></i>
                    </button>
                </div>
            </div>
            <button class="btn-ico" title="Más">
                <i class="fas fa-ellipsis"></i>
            </button>
        </div>
    </div>

    @php
        $sidebarCounters = $sidebarCounters ?? [];
        $totalConversations = $totalConversations ?? 0;
        $activeChannel = request()->input('channel');
        $isAllChannels = ! $activeChannel;
        $isUnread = request()->boolean('unread');
        $isMine = request()->boolean('mine');
        $isUrgent = request()->boolean('urgent');
        $isVip = request()->boolean('vip');
    @endphp

    <div class="bv-list-channels">
        <a href="{{ route('manager.helpdesk.conversations.index') }}"
           class="bv-chpill {{ $isAllChannels ? 'on' : '' }}"
           data-bv-channel="all">
            Todos <span class="c">{{ $totalConversations }}</span>
        </a>
        <a href="{{ route('manager.helpdesk.conversations.index', ['channel' => 'whatsapp']) }}"
           class="bv-chpill bv-chpill-wa {{ $activeChannel === 'whatsapp' ? 'on' : '' }}"
           data-bv-channel="whatsapp">
            <i class="fab fa-whatsapp"></i>WA <span class="c">{{ $sidebarCounters['whatsapp'] ?? 0 }}</span>
        </a>
        <a href="{{ route('manager.helpdesk.conversations.index', ['channel' => 'facebook']) }}"
           class="bv-chpill bv-chpill-fb {{ $activeChannel === 'facebook' ? 'on' : '' }}"
           data-bv-channel="facebook">
            <i class="fab fa-facebook-messenger"></i>FB <span class="c">{{ $sidebarCounters['facebook'] ?? 0 }}</span>
        </a>
        <a href="{{ route('manager.helpdesk.conversations.index', ['channel' => 'instagram']) }}"
           class="bv-chpill bv-chpill-ig {{ $activeChannel === 'instagram' ? 'on' : '' }}"
           data-bv-channel="instagram">
            <i class="fab fa-instagram"></i>IG <span class="c">{{ $sidebarCounters['instagram'] ?? 0 }}</span>
        </a>
        <a href="{{ route('manager.helpdesk.conversations.index', ['channel' => 'email']) }}"
           class="bv-chpill {{ $activeChannel === 'email' ? 'on' : '' }}"
           data-bv-channel="email">
            <i class="far fa-envelope"></i>Email <span class="c">{{ $sidebarCounters['email'] ?? 0 }}</span>
        </a>
        <a href="{{ route('manager.helpdesk.conversations.index', ['channel' => 'web']) }}"
           class="bv-chpill {{ in_array($activeChannel, ['web', 'widget']) ? 'on' : '' }}"
           data-bv-channel="web">
            <i class="far fa-comment-dots"></i>Widget <span class="c">{{ $sidebarCounters['widget'] ?? 0 }}</span>
        </a>
    </div>

    <div class="bv-list-sub">
        <a href="{{ route('manager.helpdesk.conversations.index', ['unread' => 1]) }}"
           class="bv-chip {{ $isUnread ? 'on' : '' }}"
           data-bv-filter="unread">
            Sin leer <span class="c">{{ $sidebarCounters['unread'] ?? 0 }}</span>
        </a>
        <a href="{{ route('manager.helpdesk.conversations.index', ['mine' => 1]) }}"
           class="bv-chip {{ $isMine ? 'on' : '' }}"
           data-bv-filter="mine">
            Mías <span class="c">{{ $sidebarCounters['mine'] ?? 0 }}</span>
        </a>
        <a href="{{ route('manager.helpdesk.conversations.index', ['urgent' => 1]) }}"
           class="bv-chip {{ $isUrgent ? 'on' : '' }}"
           data-bv-filter="urgent">
            Urgentes <span class="c">{{ $sidebarCounters['urgent'] ?? 0 }}</span>
        </a>
        <a href="{{ route('manager.helpdesk.conversations.index', ['vip' => 1]) }}"
           class="bv-chip {{ $isVip ? 'on' : '' }}"
           data-bv-filter="vip">
            VIP <span class="c">{{ $sidebarCounters['vip'] ?? 0 }}</span>
        </a>
    </div>

    <div class="bv-conv-list" id="bv-conv-list">
        @php
            $groupLabels = [
                'today' => 'HOY',
                'yesterday' => 'AYER',
                'week' => 'ESTA SEMANA',
                'older' => 'ANTERIORES',
            ];
            $inboxGroups = $inboxGroups ?? collect();
            $hasAny = collect($inboxGroups)->flatten(1)->isNotEmpty();
        @endphp

        @foreach($groupLabels as $key => $label)
            @if(! empty($inboxGroups[$key]))
                <div class="bv-list-group-label">
                    <span>{{ $label }}</span>
                    <span class="c">{{ count($inboxGroups[$key]) }} conversaciones</span>
                </div>

                @foreach($inboxGroups[$key] as $conv)
                    @include('helpdesk::managers.inbox.partials.conv-item', ['conv' => $conv])
                @endforeach
            @endif
        @endforeach

        @unless($hasAny)
            <div class="bv-empty-state">
                <i class="far fa-inbox bv-empty-state-icon"></i>
                <div class="bv-empty-state-title">No hay conversaciones</div>
                <div class="bv-empty-state-sub">Inicia una nueva conversación o ajusta los filtros</div>
            </div>
        @endunless
    </div>
</div>

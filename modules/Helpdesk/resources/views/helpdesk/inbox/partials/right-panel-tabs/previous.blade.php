{{-- Contenido de la pestaña "Anteriores" del panel derecho — cargado bajo
     demanda por RightPanelTabController@previous. Recibe $rpPrevious, $rpCust. --}}
@php
    $prevChannelIcons = [
        'whatsapp'  => ['icon' => 'fab fa-whatsapp',     'color' => '#25d366'],
        'facebook'  => ['icon' => 'fab fa-facebook-f',   'color' => '#1877f2'],
        'instagram' => ['icon' => 'fab fa-instagram',    'color' => '#e4405f'],
        'email'     => ['icon' => 'far fa-envelope',     'color' => '#52525b'],
        'twitter'   => ['icon' => 'fab fa-twitter',      'color' => '#1da1f2'],
        'web'       => ['icon' => 'far fa-comment-dots', 'color' => '#14b8a6'],
    ];
@endphp
@if($rpPrevious->isEmpty())
    <div class="bv-tab-empty">
        <i class="fas fa-clock-rotate-left"></i>
        <div class="bv-tab-empty-title">{{ __('helpdesk::helpdesk.inbox.right.no_previous_title') }}</div>
        <div class="bv-tab-empty-sub">{{ __('helpdesk::helpdesk.inbox.right.no_previous_sub') }}</div>
    </div>
@else
    {{-- Search --}}
    <div class="bv-prev-search">
        <i class="fas fa-magnifying-glass"></i>
        <input type="text" class="bv-prev-search-input" placeholder="{{ __('helpdesk::helpdesk.inbox.right.search_history_placeholder') }}">
    </div>

    {{-- Filter pills --}}
    @php
        $prevAll    = $rpPrevious->count();
        $prevOpen   = $rpPrevious->filter(fn($c) => (bool)($c->status?->is_open ?? true))->count();
        $prevClosed = $prevAll - $prevOpen;
    @endphp
    <div class="bv-prev-filter-row">
        <span class="bv-media-pill bv-prev-pill on" data-bv-prev-filter="all">
            {{ __('helpdesk::helpdesk.inbox.right.filter_all_fem') }} <span class="c">{{ $prevAll }}</span>
        </span>
        <span class="bv-media-pill bv-prev-pill" data-bv-prev-filter="open">
            {{ __('helpdesk::helpdesk.inbox.right.filter_open') }} <span class="c">{{ $prevOpen }}</span>
        </span>
        <span class="bv-media-pill bv-prev-pill" data-bv-prev-filter="closed">
            {{ __('helpdesk::helpdesk.inbox.right.filter_closed') }} <span class="c">{{ $prevClosed }}</span>
        </span>
    </div>

    {{-- Conversation cards --}}
    <div class="bv-prev-list" id="bvPrevList">
        @foreach($rpPrevious as $prev)
            @php
                $ch         = $prevChannelIcons[$prev->channel ?? 'web'] ?? $prevChannelIcons['web'];
                $isOpen     = (bool)($prev->status?->is_open ?? true);
                $statusName = $prev->status?->name ?? 'Abierta';
                $custName   = $rpCust?->name ?? 'Cliente';
                $initials   = mb_strtoupper(
                    collect(preg_split('/\s+/', trim($custName)))
                        ->take(2)->map(fn($w) => mb_substr($w,0,1))->implode('')
                );
                $msgCount   = $prev->messages_count ?? 0;
                $dateLabel  = optional($prev->last_message_at ?? $prev->created_at)->diffForHumans(['short' => true]) ?? '—';
                $preview    = $prev->lastMessage?->body ?? '';
                if (!$preview && $prev->subject) { $preview = $prev->subject; }
            @endphp
            <button class="bv-conv-card"
                    data-bv-prev-open="{{ $isOpen ? '1' : '0' }}"
                    data-bv-prev-text="{{ strtolower($statusName . ' ' . ($prev->subject ?? '')) }}"
                    data-conv-id="{{ $prev->id }}"
                    data-conv-subject="{{ e($prev->subject ?? 'Conversación') }}"
                    data-conv-status="{{ e($statusName) }}"
                    data-conv-is-open="{{ $isOpen ? '1' : '0' }}"
                    data-conv-channel="{{ $prev->channel ?? 'web' }}"
                    data-viewer-url="{{ route('manager.helpdesk.conversations.viewer-items', $prev->id) }}">
                <div class="bv-conv-av">
                    {{ $initials ?: '?' }}
                    <span class="bv-ch-badge">
                        <i class="{{ $ch['icon'] }}"></i>
                    </span>
                </div>
                <div class="bv-conv-body">
                    <div class="bv-conv-head">
                        <span class="bv-conv-nm">{{ $custName }}</span>
                        <span class="bv-conv-time">{{ $dateLabel }}</span>
                    </div>
                    @if($preview)
                        <div class="bv-conv-preview">{{ \Illuminate\Support\Str::limit($preview, 80) }}</div>
                    @endif
                    <div class="bv-conv-foot">
                        @if($msgCount > 0)
                            <span class="bv-conv-seg">
                                <i class="fas fa-message"></i> {{ $msgCount }} mensaje{{ $msgCount !== 1 ? 's' : '' }}
                            </span>
                        @endif
                        <span class="bv-conv-status-badge {{ $isOpen ? 'open' : '' }}">
                            {{ $statusName }}
                        </span>
                    </div>
                </div>
            </button>
        @endforeach
    </div>
@endif

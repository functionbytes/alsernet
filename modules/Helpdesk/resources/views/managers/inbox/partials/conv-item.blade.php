{{-- Item individual de la lista de conversaciones — Refined v4 --}}
<div class="bv-conv {{ ($conv['on'] ?? false) ? 'on' : '' }} {{ ($conv['unread'] ?? 0) > 0 ? 'unread' : '' }} {{ ($conv['urgent'] ?? false) ? 'urgent' : '' }}"
     draggable="true"
     data-bv-conv-id="{{ $conv['id'] }}"
     data-bv-conv-url="{{ route('manager.helpdesk.conversations.index', ['selected' => $conv['id']]) }}">
    <input type="checkbox" data-bv-bulk-select onclick="event.stopPropagation()">
    <div class="bv-av {{ $conv['color'] }}">
        {{ $conv['initials'] }}
        @if(!empty($conv['channel']))
            <span class="badge-ch {{ $conv['channel'] }}">
                <i class="{{ $conv['channelIcon'] }}"></i>
            </span>
        @endif
    </div>
    <div class="body">
        <div class="row1">
            <span class="name">{{ $conv['name'] }}</span>
            <span class="time">{{ $conv['time'] }}</span>
        </div>
        <div class="row2">
            <span class="preview">{!! $conv['preview'] !!}</span>
            <span class="meta">
                @if(!empty($conv['sla']))
                    <span class="bv-sla {{ $conv['sla'][0] }}">
                        <i class="far fa-clock bv-sla-icon"></i>{{ $conv['sla'][1] }}
                    </span>
                @endif
                @if(!empty($conv['priority']) && $conv['priority'] !== 'normal')
                    <span class="bv-tag {{ $conv['priority'] }}">{{ $conv['priority'] }}</span>
                @endif
                @if(($conv['unread'] ?? 0) > 0)
                    <span class="bv-ucount">{{ ($conv['unread'] ?? 0) > 9 ? '9+' : $conv['unread'] }}</span>
                @endif
            </span>
        </div>
    </div>
    {{-- Acciones rápidas al hover --}}
    <div class="bv-conv-hactions">
        <button title="Fijar"
                data-bv-action="pin"
                data-bv-url="{{ route('manager.helpdesk.conversations.pin', $conv['id']) }}">
            <i class="fas fa-thumbtack"></i>
        </button>
        <button title="Silenciar"
                data-bv-action="mute"
                data-bv-url="{{ route('manager.helpdesk.conversations.mute', $conv['id']) }}">
            <i class="far fa-bell-slash"></i>
        </button>
        <button title="Archivar"
                data-bv-action="archive"
                data-bv-url="{{ route('manager.helpdesk.conversations.archive', $conv['id']) }}">
            <i class="fas fa-archive"></i>
        </button>
        <button title="Snooze" data-bv-modal="snooze"><i class="far fa-clock"></i></button>
    </div>
</div>

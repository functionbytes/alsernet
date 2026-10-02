{{-- Item individual de la lista de conversaciones — Refined v4 --}}
@php
    // Preserve sidebar filters (inbox, channel, tag, urgent, vip, etc.) so
    // clicking a conversation doesn't drop the active bandeja highlight.
    $convUrlParams = array_merge(
        request()->only(['inbox', 'channel', 'tag', 'urgent', 'vip', 'mine', 'unread', 'archived', 'status', 'group', 'priority', 'search', 'viewId']),
        ['selected' => $conv['id']]
    );

    // Same map used in thread.blade.php / kanban.blade.php / right-panel.blade.php.
    $chipStatus = $conv['status'] ?? null;
    $chipChannel = $conv['channelLabel'] ?? null;
    $chipPriority = in_array($conv['priority'] ?? null, ['high', 'urgent'], true) ? $conv['priority'] : null;
    $chipUnanswered = (bool) ($conv['unanswered'] ?? false);
    $chipSla = $conv['slaChip'] ?? null;
    $chipTags = $conv['tags'] ?? [];
    $chipTagsMore = (int) ($conv['tagsMore'] ?? 0);
    $chipAssignee = $conv['assignee'] ?? null;
    // El color llega de la BD y se inyecta como variable CSS: solo se admiten hex/rgb/hsl/nombres simples.
    $safeColor = fn ($c) => is_string($c) && preg_match('/^(#[0-9a-fA-F]{3,8}|[a-zA-Z]{3,20}|(rgb|hsl)a?\([0-9 ,.%]+\))$/', $c) ? $c : null;
@endphp
<div class="bv-conv {{ ($conv['on'] ?? false) ? 'on' : '' }} {{ ($conv['unread'] ?? 0) > 0 ? 'unread' : '' }} {{ ($conv['urgent'] ?? false) ? 'urgent' : '' }}"
     draggable="true"
     data-bv-conv-id="{{ $conv['id'] }}"
     data-bv-conv-url="{{ route('manager.helpdesk.conversations.index', $convUrlParams) }}">
    <input type="checkbox" data-bv-bulk-select aria-label="{{ __('helpdesk::helpdesk.inbox.thread.select_conversation') }}" onclick="event.stopPropagation()">
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
            <span class="preview">{{ $conv['preview'] }}</span>
            <span class="meta">
                @if(empty($chipSla) && !empty($conv['sla']))
                    <span class="bv-sla {{ $conv['sla'][0] }}">
                        <i class="far fa-clock bv-sla-icon"></i>{{ $conv['sla'][1] }}
                    </span>
                @endif
                @if(($conv['unread'] ?? 0) > 0)
                    <span class="bv-ucount">{{ ($conv['unread'] ?? 0) > 9 ? '9+' : $conv['unread'] }}</span>
                @endif
            </span>
        </div>
        <div class="row3">
            <div class="bv-chips">
                @if(!empty($chipStatus['name']))
                    @php($statusColor = $safeColor($chipStatus['color'] ?? null))
                    <span class="bv-chip bv-chip-status" data-bv-chip="status">
                        @if($statusColor)<span class="bv-chip-dot" style="--bv-chip-dot: {{ $statusColor }}"></span>@endif{{ $chipStatus['name'] }}
                    </span>
                @endif
                @if($chipChannel)
                    <span class="bv-chip outline" data-bv-chip="channel">{{ $chipChannel }}</span>
                @endif
                @if($chipPriority)
                    <span class="bv-chip prio {{ $chipPriority }}" data-bv-chip="priority">{{ __('helpdesk::helpdesk.inbox.thread.card_priority_' . $chipPriority) }}</span>
                @endif
                @if($chipUnanswered)
                    <span class="bv-chip attn" data-bv-chip="unanswered">{{ __('helpdesk::helpdesk.inbox.thread.card_unanswered') }}</span>
                @endif
                @if(!empty($chipSla['text']))
                    <span class="bv-chip sla-{{ in_array($chipSla['kind'] ?? '', ['breach', 'warn', 'ok'], true) ? $chipSla['kind'] : 'ok' }}" data-bv-chip="sla">{{ ($chipSla['label'] ?? '') !== '' ? $chipSla['label'] . ': ' : '' }}{{ $chipSla['text'] }}</span>
                @endif
                @foreach($chipTags as $chipTag)
                    @php($tagColor = $safeColor($chipTag['color'] ?? null))
                    <span class="bv-chip tag" data-bv-chip="tag">
                        @if($tagColor)<span class="bv-chip-dot" style="--bv-chip-dot: {{ $tagColor }}"></span>@endif{{ $chipTag['name'] ?? '' }}
                    </span>
                @endforeach
                @if($chipTagsMore > 0)
                    <span class="bv-chip tag" data-bv-chip="tags-more" title="{{ __('helpdesk::helpdesk.inbox.thread.card_more_tags') }}">+{{ $chipTagsMore }}</span>
                @endif
            </div>
            @if($chipAssignee)
                <span class="bv-assignee" data-bv-assignee-id="{{ $chipAssignee['id'] ?? '' }}" data-bv-base-title="{{ __('helpdesk::helpdesk.inbox.thread.card_assigned_to', ['name' => $chipAssignee['name'] ?? '']) }}" title="{{ __('helpdesk::helpdesk.inbox.thread.card_assigned_to', ['name' => $chipAssignee['name'] ?? '']) }}">{{ $chipAssignee['initials'] ?? '?' }}</span>
            @elseif(array_key_exists('assignee', $conv))
                <span class="bv-assignee unassigned" data-bv-assignee-id="" data-bv-base-title="{{ __('helpdesk::helpdesk.inbox.thread.card_unassigned') }}" title="{{ __('helpdesk::helpdesk.inbox.thread.card_unassigned') }}">&ndash;</span>
            @endif
        </div>
    </div>
    {{-- Acciones rápidas al hover --}}
    <div class="bv-conv-hactions">
        @if(request('view') === 'deleted')
            <button title="{{ __('helpdesk::helpdesk.inbox.thread.restore') }}" aria-label="{{ __('helpdesk::helpdesk.inbox.thread.restore_conversation') }}"
                    data-bv-action="restore"
                    data-bv-url="{{ route('manager.helpdesk.conversations.restore', $conv['id']) }}">
                <i class="fas fa-trash-arrow-up" aria-hidden="true"></i>
            </button>
        @else
            <button title="{{ __('helpdesk::helpdesk.inbox.thread.pin') }}" aria-label="{{ __('helpdesk::helpdesk.inbox.thread.pin_conversation') }}"
                    data-bv-action="pin"
                    data-bv-url="{{ route('manager.helpdesk.conversations.pin', $conv['id']) }}">
                <i class="fas fa-thumbtack" aria-hidden="true"></i>
            </button>
            <button title="{{ __('helpdesk::helpdesk.inbox.thread.mute') }}" aria-label="{{ __('helpdesk::helpdesk.inbox.thread.mute_conversation') }}"
                    data-bv-action="mute"
                    data-bv-url="{{ route('manager.helpdesk.conversations.mute', $conv['id']) }}">
                <i class="far fa-bell-slash" aria-hidden="true"></i>
            </button>
            <button title="{{ __('helpdesk::helpdesk.inbox.thread.archive') }}" aria-label="{{ __('helpdesk::helpdesk.inbox.thread.archive_conversation') }}"
                    data-bv-action="archive"
                    data-bv-url="{{ route('manager.helpdesk.conversations.archive', $conv['id']) }}">
                <i class="fas fa-archive" aria-hidden="true"></i>
            </button>
            <button title="{{ __('helpdesk::helpdesk.inbox.thread.snooze') }}" aria-label="{{ __('helpdesk::helpdesk.inbox.thread.snooze_conversation') }}" data-bv-modal="snooze"><i class="far fa-clock" aria-hidden="true"></i></button>
        @endif
    </div>
</div>

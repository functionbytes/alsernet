{{-- Item individual de la lista de conversaciones --}}
<div class="bv-conv {{ $conv['on'] ?? false ? 'on' : '' }} {{ $conv['unread'] > 0 ? 'unread' : '' }} {{ $conv['urgent'] ? 'urgent' : '' }}"
     data-bv-conv-id="{{ $conv['id'] }}">
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
                        <i class="far fa-clock" style="font-size:9px"></i>{{ $conv['sla'][1] }}
                    </span>
                @endif
                @if(!empty($conv['priority']))
                    <span class="bv-tag {{ $conv['priority'] }}">{{ $conv['priority'] }}</span>
                @endif
                @if(($conv['unread'] ?? 0) > 0)
                    <span class="bv-ucount">{{ $conv['unread'] > 9 ? '9+' : $conv['unread'] }}</span>
                @endif
            </span>
        </div>
    </div>
</div>

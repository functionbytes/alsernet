@php $pending = $row['status'] === 'pending'; @endphp
<div class="psc-opslog-evt {{ $pending ? 'is-pending' : '' }}" data-event-row="{{ $row['id'] }}">
    <div class="psc-opslog-evt-main">
        <span class="info">
            <span class="nm">{{ $row['event'] }}</span>
            <span class="mt">
                {{ $row['subject'] }} ·
                @if ($pending)
                    sin cliente vinculado
                @else
                    {{ $row['received_at']->locale('es')->diffForHumans() }}
                @endif
                @if ($row['customer_name'])
                    · <a href="{{ $row['customer_url'] }}" class="psc-link-ref">{{ $row['customer_name'] }}</a>
                @endif
                @if ($row['reprocess_count'] > 0)
                    · reprocesado {{ $row['reprocess_count'] === 1 ? '1 vez' : $row['reprocess_count'].' veces' }}
                @endif
            </span>
        </span>
        @if ($pending && $canReprocess)
            <button type="button" class="psc-opslog-reprocess"
                    data-url="{{ route('manager.helpdesk.ps.ext.opslog.events.reprocess', $row['id']) }}">Reprocesar</button>
        @elseif ($pending)
            <span class="psc-tag psc-tag--blocked">Pendiente</span>
        @else
            <span class="psc-tag psc-tag--cache">Procesado</span>
        @endif
    </div>
    <details class="psc-opslog-evt-data">
        <summary>Datos · {{ $row['received_at']->format('d/m/Y H:i:s') }}</summary>
        <pre>{{ json_encode($row['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
    </details>
</div>

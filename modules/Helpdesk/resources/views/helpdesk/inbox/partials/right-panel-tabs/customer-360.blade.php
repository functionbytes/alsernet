{{-- Contenido de la pestaña "Cliente 360" del panel derecho — cargado bajo
     demanda por RightPanelTabController@customer360. Recibe $c360 (array de
     Customer360Service::aggregate(), o null si la conversación no tiene
     cliente asociado). --}}
@if(is_null($c360))
    <div class="bv-tab-empty">
        <i class="fas fa-user-slash"></i>
        <div class="bv-tab-empty-title">Sin cliente asociado</div>
        <div class="bv-tab-empty-sub">Esta conversación no tiene un cliente vinculado.</div>
    </div>
@else
    {{-- Datos del cliente --}}
    <div class="rsp-section">
        <div class="lbl"><i class="fa-solid fa-id-card"></i> Datos del cliente</div>
        <div class="rsp-kv">
            <span class="k">Email</span>
            <span class="v">{{ $c360['customer']['email'] ?? '—' }}</span>
        </div>
        <div class="rsp-kv">
            <span class="k">Teléfono</span>
            <span class="v">{{ $c360['customer']['phone'] ?? '—' }}</span>
        </div>
        <div class="rsp-kv">
            <span class="k">Cliente desde</span>
            <span class="v">{{ \Illuminate\Support\Carbon::parse($c360['customer']['created_at'])->translatedFormat('d M Y') }}</span>
        </div>
    </div>

    {{-- Resumen de soporte --}}
    <div class="rsp-section">
        <div class="lbl">
            <i class="fa-solid fa-headset"></i> Resumen de soporte
            <i class="fa-solid fa-arrows-rotate add" role="button" id="customer360Refresh" title="Refrescar datos"></i>
        </div>
        <div class="bv-right-stats">
            <div class="bv-right-stat">
                <div class="val">{{ number_format($c360['helpdesk']['total_conversations']) }}</div>
                <div class="lbl">Conversaciones</div>
            </div>
            <div class="bv-right-stat">
                <div class="val">{{ number_format($c360['helpdesk']['open_conversations']) }}</div>
                <div class="lbl">Abiertas</div>
            </div>
            <div class="bv-right-stat">
                <div class="val">{{ $c360['helpdesk']['avg_csat'] !== null ? number_format($c360['helpdesk']['avg_csat'], 1, ',', '.') : '—' }}</div>
                <div class="lbl">CSAT medio</div>
            </div>
        </div>
    </div>

    {{-- Tickets recientes --}}
    <div class="rsp-section">
        <div class="lbl"><i class="fa-solid fa-ticket"></i> Tickets recientes</div>
        @if(!empty($c360['helpdesk']['recent_tickets']))
            <div class="rsp-timeline">
                @foreach($c360['helpdesk']['recent_tickets'] as $ticket)
                <div class="rsp-tl-item">
                    <div class="ic"><i class="fa-solid fa-ticket"></i></div>
                    <div class="body">
                        <div class="t">{{ $ticket['ticket_number'] }} · {{ $ticket['subject'] }}</div>
                        <div class="s">{{ $ticket['status'] }} · {{ $ticket['created_at_human'] }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <div class="rsp-empty">Sin tickets registrados</div>
        @endif
    </div>

    {{-- Pedidos --}}
    <div class="rsp-section">
        <div class="lbl"><i class="fa-solid fa-bag-shopping"></i> Pedidos</div>
        @forelse($c360['orders'] as $platform)
            <div class="rsp-c360-platform">
                {{ $platform['label'] }} · {{ $platform['connected'] ? 'vinculado #'.$platform['external_id'] : 'sin vincular' }}
            </div>
            @forelse($platform['orders'] as $order)
                <div class="rsp-c360-product">
                    <div class="rsp-c360-prod-img rsp-c360-prod-placeholder"><i class="fa-solid fa-box"></i></div>
                    <div class="rsp-c360-prod-info">
                        <div class="rsp-c360-prod-name">#{{ $order['reference'] }} · {{ $order['status'] }}</div>
                        <div class="rsp-c360-prod-price">{{ $order['date_human'] }}@if($order['total']) · {{ $order['total'] }} € @endif</div>
                    </div>
                </div>
            @empty
                <div class="rsp-empty">Sin pedidos registrados</div>
            @endforelse
        @empty
            <div class="rsp-empty">Sin integraciones de tienda activas</div>
        @endforelse
    </div>
@endif

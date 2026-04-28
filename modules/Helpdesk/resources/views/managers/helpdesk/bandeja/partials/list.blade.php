{{-- Lista de conversaciones (Alvarez/Refined v4) --}}
<div class="bv-list">
    <div class="bv-list-head">
        <div class="bv-list-search">
            <i class="fas fa-magnifying-glass" style="color:var(--bv-text-muted)"></i>
            <input type="text" id="bv-search-input" placeholder="Buscar conversaciones…" autocomplete="off">
            <kbd style="background:var(--bv-bg-panel);border:1px solid var(--bv-border);border-radius:4px;padding:1px 5px;font-size:10px;font-family:'JetBrains Mono',monospace;color:var(--bv-text-soft)">F</kbd>
        </div>
        <div class="bv-list-actions">
            <button class="btn-new" data-bv-modal="newconv">
                <i class="fas fa-plus" style="font-size:11px;margin-right:4px"></i>Nueva
            </button>
            <button class="btn-ico" data-bv-modal="filter" title="Filtros">
                <i class="fas fa-filter"></i>
            </button>
            <button class="btn-ico" title="Ordenar">
                <i class="fas fa-arrow-up-arrow-down"></i>
            </button>
            <button class="btn-ico" title="Más">
                <i class="fas fa-ellipsis"></i>
            </button>
        </div>
    </div>

    <div class="bv-list-channels">
        <button class="bv-chpill on" data-bv-channel="all">Todos <span class="c">79</span></button>
        <button class="bv-chpill" data-bv-channel="whatsapp" style="border-bottom:2px solid #25d366">
            <i class="fab fa-whatsapp" style="color:#25d366"></i>WA <span class="c">42</span>
        </button>
        <button class="bv-chpill" data-bv-channel="facebook" style="border-bottom:2px solid #1877f2">
            <i class="fab fa-facebook-messenger" style="color:#1877f2"></i>FB <span class="c">18</span>
        </button>
        <button class="bv-chpill" data-bv-channel="instagram">
            <i class="fab fa-instagram" style="color:#dd2a7b"></i>IG <span class="c">9</span>
        </button>
        <button class="bv-chpill" data-bv-channel="email">
            <i class="far fa-envelope"></i>Email <span class="c">7</span>
        </button>
        <button class="bv-chpill" data-bv-channel="web">
            <i class="far fa-comment-dots"></i>Widget <span class="c">3</span>
        </button>
    </div>

    <div class="bv-list-sub">
        <button class="bv-chip on" data-bv-filter="unread">Sin leer <span class="c">12</span></button>
        <button class="bv-chip" data-bv-filter="mine">Mías <span class="c">8</span></button>
        <button class="bv-chip" data-bv-filter="urgent">Urgentes <span class="c">2</span></button>
        <button class="bv-chip" data-bv-filter="vip">VIP <span class="c">5</span></button>
    </div>

    <div class="bv-conv-list" id="bv-conv-list">
        {{-- HOY --}}
        <div class="bv-list-group-label">
            <span>HOY</span>
            <span class="c">8 conversaciones</span>
        </div>

        @php
            $sampleConversations = [
                ['id'=>1,'name'=>'Carmen Pérez','initials'=>'CP','color'=>'c1','channel'=>'wa','channelIcon'=>'fab fa-whatsapp','time'=>'2m','preview'=>'Necesito información sobre el producto que vi en su tienda online…','sla'=>['ok','2m'],'priority'=>'high','unread'=>3,'urgent'=>false,'on'=>true],
                ['id'=>2,'name'=>'Roberto García','initials'=>'RG','color'=>'c2','channel'=>'ig','channelIcon'=>'fab fa-instagram','time'=>'8m','preview'=>'Hola, quería consultar sobre el envío del pedido #1234','sla'=>['warn','8m'],'priority'=>null,'unread'=>1,'urgent'=>false],
                ['id'=>3,'name'=>'Laura Martínez','initials'=>'LM','color'=>'c3','channel'=>'fb','channelIcon'=>'fab fa-facebook-messenger','time'=>'15m','preview'=>'<span class="me">Tú:</span><span class="chk read">✓✓</span>Gracias por contactarnos, en breve…','sla'=>['ok','15m'],'priority'=>null,'unread'=>0,'urgent'=>false],
                ['id'=>4,'name'=>'Diego López','initials'=>'DL','color'=>'c4','channel'=>'wa','channelIcon'=>'fab fa-whatsapp','time'=>'24m','preview'=>'<i class="fas fa-image" style="font-size:10px;margin-right:4px;color:var(--bv-text-muted)"></i>Foto del producto','sla'=>['breach','30m'],'priority'=>'urgent','unread'=>2,'urgent'=>true],
                ['id'=>5,'name'=>'Ana Ruiz','initials'=>'AR','color'=>'c5','channel'=>'email','channelIcon'=>'far fa-envelope','time'=>'1h','preview'=>'Re: Consulta sobre devolución del pedido','sla'=>['ok','1h'],'priority'=>null,'unread'=>0,'urgent'=>false],
            ];
        @endphp

        @foreach($sampleConversations as $conv)
            @include('helpdesk::managers.helpdesk.bandeja.partials.conv-item', ['conv' => $conv])
        @endforeach

        {{-- AYER --}}
        <div class="bv-list-group-label">
            <span>AYER</span>
            <span class="c">15 conversaciones</span>
        </div>

        @php
            $yesterdayConversations = [
                ['id'=>10,'name'=>'Marcos Silva','initials'=>'MS','color'=>'c6','channel'=>'wa','channelIcon'=>'fab fa-whatsapp','time'=>'18:42','preview'=>'<span class="me">Tú:</span><span class="chk read">✓✓</span>Pedido enviado, número de seguimiento…','sla'=>['ok','—'],'priority'=>null,'unread'=>0,'urgent'=>false],
                ['id'=>11,'name'=>'Patricia Vega','initials'=>'PV','color'=>'c7','channel'=>'ig','channelIcon'=>'fab fa-instagram','time'=>'15:20','preview'=>'<i class="fas fa-microphone" style="font-size:10px;margin-right:4px;color:var(--bv-text-muted)"></i>Mensaje de voz · 0:34','sla'=>['ok','—'],'priority'=>null,'unread'=>0,'urgent'=>false],
                ['id'=>12,'name'=>'Carlos Mendoza','initials'=>'CM','color'=>'c8','channel'=>'web','channelIcon'=>'far fa-comment-dots','time'=>'12:08','preview'=>'¿Tienen este producto en color azul?','sla'=>['ok','—'],'priority'=>null,'unread'=>0,'urgent'=>false],
            ];
        @endphp

        @foreach($yesterdayConversations as $conv)
            @include('helpdesk::managers.helpdesk.bandeja.partials.conv-item', ['conv' => $conv])
        @endforeach
    </div>
</div>

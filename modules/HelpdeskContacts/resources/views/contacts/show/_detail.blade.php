{{-- Detalle por fuente (pestañas de la versión A): cada pestaña se pide
     por separado y las de módulos apagados no se pintan. #pane-prestashop
     es además el "data holder" de ps-carts/ps-external-id que leen
     findCachedCart()/fetchPsOrderDetail(). --}}
<div class="ctf-detail" id="ctf-detail">
    <div class="ctf-tabs" role="tablist">
        @foreach($detailTabs as $key => $label)
            <button type="button" class="ctf-tab{{ $loop->first ? ' active' : '' }}" role="tab"
                    data-contact-tab="{{ $key }}" data-bs-toggle="tab" data-bs-target="#pane-{{ $key }}"
                    aria-controls="pane-{{ $key }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">{{ $label }}</button>
        @endforeach
        <span class="ctf-tabs-meta"><span class="ctf-source-dot"></span><span id="ctf-data-age">datos de ahora</span></span>
    </div>
    <div class="tab-content">
        @foreach($detailTabs as $key => $label)
            <div class="tab-pane fade{{ $loop->first ? ' show active' : '' }}" id="pane-{{ $key }}" role="tabpanel" data-loaded="0">
                @include('contacts::contacts.partials._skeleton', ['rows' => 3])
            </div>
        @endforeach
    </div>
    <div class="ctf-tabs-hint">teclas 1-{{ count($detailTabs) }} · cambia de pestaña · se recuerda la última abierta</div>
</div>

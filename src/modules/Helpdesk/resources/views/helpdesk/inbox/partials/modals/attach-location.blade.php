{{-- Modal: Adjuntar ubicación --}}
<div class="bv-modal" data-bv-modal-name="attach-location">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head">
            <div class="bv-modal-icon-box"><i class="fas fa-location-dot"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.attach_location_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.attach_location_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-minfo">
                <i class="fas fa-circle-info"></i>
                <div>{{ __('helpdesk::helpdesk.inbox.modals.attach_location_info') }}</div>
            </div>

            {{-- Tabs de tipo --}}
            <div class="bv-loc-tabs" id="attach-location-type">
                <button class="bv-loc-tab" data-loc-type="current">
                    <i class="fas fa-location-crosshairs"></i> {{ __('helpdesk::helpdesk.inbox.modals.attach_location_tab_current') }}
                </button>
                <button class="bv-loc-tab" data-loc-type="search">
                    <i class="fas fa-magnifying-glass"></i> {{ __('helpdesk::helpdesk.inbox.modals.attach_location_tab_search') }}
                </button>
                <button class="bv-loc-tab on" data-loc-type="saved">
                    <i class="far fa-bookmark"></i> {{ __('helpdesk::helpdesk.inbox.modals.attach_location_tab_saved') }}
                </button>
            </div>

            {{-- Buscar dirección (oculto por defecto) --}}
            <div class="bv-modal-search bv-hidden" id="attach-location-search-wrap">
                <i class="fas fa-magnifying-glass"></i>
                <input id="attach-location-search" type="text" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.attach_location_search_placeholder') }}" autocomplete="off">
            </div>

            {{-- Ubicaciones guardadas --}}
            <div class="bv-loc-opts" id="attach-location-saved">
                <button class="bv-loc-opt on" data-loc-id="hq">
                    <div class="ic"><i class="fas fa-building"></i></div>
                    <div class="body">
                        <span class="t">{{ __('helpdesk::helpdesk.inbox.modals.attach_location_hq_name') }}</span>
                        <span class="s">{{ __('helpdesk::helpdesk.inbox.modals.attach_location_hq_address') }}</span>
                    </div>
                    <div class="radio"></div>
                </button>
                <button class="bv-loc-opt" data-loc-id="warehouse">
                    <div class="ic"><i class="fas fa-warehouse"></i></div>
                    <div class="body">
                        <span class="t">{{ __('helpdesk::helpdesk.inbox.modals.attach_location_warehouse_name') }}</span>
                        <span class="s">{{ __('helpdesk::helpdesk.inbox.modals.attach_location_warehouse_address') }}</span>
                    </div>
                    <div class="radio"></div>
                </button>
                <button class="bv-loc-opt" data-loc-id="store">
                    <div class="ic"><i class="fas fa-store"></i></div>
                    <div class="body">
                        <span class="t">{{ __('helpdesk::helpdesk.inbox.modals.attach_location_store_name') }}</span>
                        <span class="s">{{ __('helpdesk::helpdesk.inbox.modals.attach_location_store_address') }}</span>
                    </div>
                    <div class="radio"></div>
                </button>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="attach-location-send">{{ __('helpdesk::helpdesk.inbox.modals.attach_location_send') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
$(document).on('click', '[data-bv-modal-name="attach-location"] .bv-loc-tab', function () {
    $('[data-bv-modal-name="attach-location"] .bv-loc-tab').removeClass('on');
    $(this).addClass('on');
    var type = $(this).data('loc-type');
    $('#attach-location-search-wrap').toggleClass('bv-hidden', type !== 'search');
    $('#attach-location-saved').toggleClass('bv-hidden', type !== 'saved');
});

$(document).on('click', '[data-bv-modal-name="attach-location"] .bv-loc-opt', function () {
    $('[data-bv-modal-name="attach-location"] .bv-loc-opt').removeClass('on');
    $(this).addClass('on');
});

$(document).on('click', '#attach-location-send', function () {
    var $btn = $(this);
    var convId = $('.bv-composer').data('bv-conversation-id');
    if (!convId) { toastr.error('No hay conversación seleccionada'); return; }

    var activeType = $('[data-bv-modal-name="attach-location"] .bv-loc-tab.on').data('loc-type') || 'saved';
    var payload = { type: activeType };

    if (activeType === 'current') {
        if (!navigator.geolocation) { toastr.error('Geolocalización no disponible'); return; }
        $btn.prop('disabled', true).text('Obteniendo ubicación…');
        navigator.geolocation.getCurrentPosition(function (pos) {
            payload.lat = pos.coords.latitude;
            payload.lng = pos.coords.longitude;
            sendLocation(convId, payload, $btn);
        }, function () {
            toastr.error('No se pudo obtener la ubicación');
            $btn.prop('disabled', false).text('Enviar ubicación');
        });
        return;
    }

    if (activeType === 'search') {
        payload.address = ($('#attach-location-search').val() || '').trim();
        if (!payload.address) { toastr.warning('Escribe una dirección'); return; }
    }

    if (activeType === 'saved') {
        var $opt = $('[data-bv-modal-name="attach-location"] .bv-loc-opt.on');
        payload.saved_id = $opt.data('loc-id') || '';
        payload.address  = $opt.find('.s').text().trim();
    }

    $btn.prop('disabled', true);
    sendLocation(convId, payload, $btn);
});

function sendLocation(convId, payload, $btn) {
    $.ajax({
        url: '/panel/helpdesk/conversations/' + convId + '/location',
        method: 'POST',
        dataType: 'json',
        data: payload,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json',
        },
    }).done(function (resp) {
        $('[data-bv-modal-name="attach-location"]').removeClass('on');
        $('body').css('overflow', '');
        if (resp?.item && typeof window.appendBubbleToThread === 'function') {
            window.appendBubbleToThread(resp.item, false);
        }
    }).fail(function (xhr) {
        var msg = xhr?.responseJSON?.message || 'No se pudo enviar la ubicación';
        toastr.error(msg);
    }).always(function () {
        $btn.prop('disabled', false).text('Enviar ubicación');
    });
}
</script>
@endpush
@endonce

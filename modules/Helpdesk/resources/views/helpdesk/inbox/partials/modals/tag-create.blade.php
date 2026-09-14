{{-- Modal: Crear nueva etiqueta (#05 ve-tag-create) --}}
<div class="bv-modal" data-bv-modal-name="tag-create">
    <div class="bv-modal-dialog sm">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-tag"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.tag_create_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.tag_create_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-form-field">
                <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.tag_create_name') }} <span class="bv-req">*</span></label>
                <input id="tagCreateName" type="text" class="bv-form-input" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.tag_create_name_placeholder') }}" autocomplete="off">
            </div>

            <div class="bv-form-field">
                <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.tag_create_color') }}</label>
                <div class="bv-color-picker" id="tagColorPicker">
                    @foreach([
                        '#dc2626','#ea580c','#d97706','#65a30d','#16a34a',
                        '#059669','#0891b2','#14b8a6','#7c3aed','#9333ea',
                        '#c026d3','#be185d','#78716c','#334155','#90bb13',
                    ] as $color)
                        <button type="button" class="bv-color-dot {{ $loop->last ? 'on' : '' }}"
                                data-color="{{ $color }}"
                                style="background:{{ $color }};{{ $loop->last ? 'outline:2px solid #18181b;outline-offset:2px;' : '' }}">
                        </button>
                    @endforeach
                </div>
                <input type="hidden" id="tagCreateColor" value="#90bb13">
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-tag-create-confirm">{{ __('helpdesk::helpdesk.inbox.modals.tag_create_confirm') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function ($) {
    'use strict';

    function closeBvModal(name) {
        $('[data-bv-modal-name="' + name + '"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
    }

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'tag-create') { return; }
        $('#tagCreateName').val('').focus();
        $('#tagCreateColor').val('#90bb13');
        $('#tagColorPicker .bv-color-dot').each(function () {
            $(this).css('outline', '').css('outline-offset', '').removeClass('on');
        });
        $('#tagColorPicker .bv-color-dot[data-color="#90bb13"]').addClass('on')
            .css({ outline: '2px solid #18181b', 'outline-offset': '2px' });
    });

    $(document).on('click', '#tagColorPicker .bv-color-dot', function () {
        $('#tagColorPicker .bv-color-dot').css('outline', '').css('outline-offset', '').removeClass('on');
        $(this).addClass('on').css({ outline: '2px solid #18181b', 'outline-offset': '2px' });
        $('#tagCreateColor').val($(this).data('color'));
    });

    $(document).on('click', '#bv-tag-create-confirm', function () {
        var name = $('#tagCreateName').val().trim();
        if (!name) {
            if (window.toastr) { toastr.warning('El nombre es obligatorio'); }
            $('#tagCreateName').focus();
            return;
        }
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: '/panel/settings/helpdesk/tags',
            method: 'POST',
            data: { name: name, color: $('#tagCreateColor').val() },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function (resp) {
            closeBvModal('tag-create');
            if (window.toastr) { toastr.success('Etiqueta creada'); }
            $(document).trigger('bv:tag:created', [resp]);
        }).fail(function (xhr) {
            var errs = xhr?.responseJSON?.errors;
            var msg = errs ? Object.values(errs)[0]?.[0] : (xhr?.responseJSON?.message || 'Error al crear etiqueta');
            if (window.toastr) { toastr.error(msg); }
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });

    $(document).on('keydown', '#tagCreateName', function (e) {
        if (e.key === 'Enter') { $('#bv-tag-create-confirm').trigger('click'); }
    });

}(window.jQuery));
</script>
@endpush
@endonce

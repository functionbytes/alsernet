{{-- Modal: Nueva conversación (wizard 2 pasos) --}}
<div class="bv-modal" data-bv-modal-name="newconv">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head">
            <div class="bv-modal-title"><i class="fad fa-comment-medical bv-modal-title-icon"></i> Nueva conversación</div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            {{-- Step indicator --}}
            <div class="nc-steps">
                <span class="nc-step active" data-step="1">
                    <span class="nc-step-num">1</span> Canal
                </span>
                <span class="nc-step-line"></span>
                <span class="nc-step" data-step="2">
                    <span class="nc-step-num">2</span> Destinatario
                </span>
            </div>

            {{-- Step 1: Canal --}}
            <div id="ncStep1">
                <div class="mv4-sec-title bv-sec-title-mb10">Elige un canal</div>
                <div class="nc-grid">
                    <div class="nc-channel wa on" data-channel="whatsapp">
                        <div class="logo"><i class="fab fa-whatsapp"></i></div>
                        <div>
                            <div class="t">WhatsApp</div>
                            <div class="s">Plantilla HSM requerida</div>
                        </div>
                    </div>
                    <div class="nc-channel fb" data-channel="facebook">
                        <div class="logo"><i class="fab fa-facebook-messenger"></i></div>
                        <div>
                            <div class="t">Messenger</div>
                            <div class="s">Ventana de 24h</div>
                        </div>
                    </div>
                    <div class="nc-channel ig" data-channel="instagram">
                        <div class="logo"><i class="fab fa-instagram"></i></div>
                        <div>
                            <div class="t">Instagram</div>
                            <div class="s">DM directo</div>
                        </div>
                    </div>
                    <div class="nc-channel widget" data-channel="widget">
                        <div class="logo"><i class="far fa-comment"></i></div>
                        <div>
                            <div class="t">Chat web</div>
                            <div class="s">Widget integrado</div>
                        </div>
                    </div>
                    <div class="nc-channel email" data-channel="email">
                        <div class="logo"><i class="far fa-envelope"></i></div>
                        <div>
                            <div class="t">Email</div>
                            <div class="s">Bandeja SMTP</div>
                        </div>
                    </div>
                    <div class="nc-channel sms" data-channel="sms">
                        <div class="logo"><i class="fas fa-mobile-screen"></i></div>
                        <div>
                            <div class="t">SMS</div>
                            <div class="s">Operador MasVoz</div>
                        </div>
                    </div>
                </div>

                <div id="ncHsmWarning" class="alert warning">
                    <i class="fas fa-triangle-exclamation lead"></i>
                    <div>Al iniciar desde WhatsApp deberás usar una <b>plantilla aprobada (HSM)</b> si han pasado más de 24h desde el último mensaje del cliente.</div>
                </div>
            </div>

            {{-- Step 2: Destinatario --}}
            <div id="ncStep2" class="bv-step-hidden">
                <div class="nc-field">
                    <div class="lbl">Destinatario</div>
                    <div class="mv4-search bv-search-mb0">
                        <i class="fas fa-magnifying-glass"></i>
                        <input type="text" id="ncContactSearch" placeholder="Nombre, teléfono o email…">
                    </div>
                </div>

                <div id="ncContactList">
                    <div class="nc-empty-hint" id="ncContactHint">
                        <i class="fas fa-magnifying-glass"></i>
                        <span>Escribe para buscar contactos</span>
                    </div>
                </div>

                <button class="bv-add-contact-btn" id="ncBtnAddContact">
                    <i class="fas fa-user-plus"></i>Añadir contacto nuevo
                </button>

                {{-- New contact inline form (hidden by default) --}}
                <div id="ncNewContactForm" class="nc-field bv-step-hidden">
                    <div class="lbl">Nuevo contacto</div>
                    <input type="text" id="ncNewName" placeholder="Nombre completo" class="nc-input">
                    <input type="email" id="ncNewEmail" placeholder="Email (opcional)" class="nc-input">
                    <input type="tel" id="ncNewPhone" placeholder="Teléfono (opcional)" class="nc-input">
                </div>

                <div class="nc-field">
                    <div class="lbl">Primer mensaje</div>
                    <textarea id="ncFirstMessage" placeholder="Hola, soy del equipo de soporte. ¿En qué puedo ayudarte?"></textarea>
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="ncBtnNext">Continuar</button>
            <button class="btn-secondary bv-step-hidden" id="ncBtnBack">Atrás</button>
            <button class="btn-secondary" data-bv-close>Cancelar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function() {
    var selectedChannel = 'whatsapp';
    var selectedContact = null;
    var step = 1;

    function goToStep(n) {
        step = n;
        $('#ncStep1').toggle(n === 1);
        $('#ncStep2').toggle(n === 2);
        $('[data-step]').each(function() {
            $(this).toggleClass('active', parseInt($(this).data('step')) <= n);
        });
        $('#ncBtnBack').toggle(n === 2);
        if (n === 2) {
            $('#ncBtnNext').text('Iniciar conversación');
        } else {
            $('#ncBtnNext').text('Continuar');
        }
    }

    $(document).on('click', '.nc-channel', function() {
        selectedChannel = $(this).data('channel');
        $('.nc-channel').removeClass('on');
        $(this).addClass('on');
        $('#ncHsmWarning').toggle(selectedChannel === 'whatsapp');
    });

    var creatingNewContact = false;

    $(document).on('click', '#ncBtnAddContact', function() {
        creatingNewContact = true;
        $('#ncContactList').hide();
        $('#ncBtnAddContact').hide();
        $('#ncNewContactForm').show();
        selectedContact = null;
        $('.nc-contact-card').removeClass('nc-contact-selected');
    });

    $(document).on('click', '#ncBtnNext', function() {
        if (step === 1) {
            goToStep(2);
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true);

        function createConversation(customerId) {
            var csrfToken = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                url: '/panel/helpdesk/conversations',
                method: 'POST',
                dataType: 'json',
                data: {
                    customer_id: customerId,
                    channel: selectedChannel,
                    subject: 'Conversación nueva por ' + selectedChannel,
                    priority: 'normal',
                },
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            })
            .done(function(resp) {
                var convId = resp.conversation && resp.conversation.id;
                $('[data-bv-modal-name="newconv"]').removeClass('on');
                $('body').css('overflow', '');
                if (convId) {
                    window.location.href = '/panel/helpdesk/conversations?selected=' + convId;
                } else {
                }
            })
            .fail(function(xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message || 'Error al crear la conversación';
                toastr.error(msg);
                $btn.prop('disabled', false);
            });
        }

        if (creatingNewContact) {
            var name = $('#ncNewName').val().trim();
            var email = $('#ncNewEmail').val().trim();
            var phone = $('#ncNewPhone').val().trim();
            if (!name) {
                toastr.warning('El nombre del contacto es obligatorio');
                $btn.prop('disabled', false);
                return;
            }
            if (!email && !phone) {
                toastr.warning('Introduce al menos un email o teléfono');
                $btn.prop('disabled', false);
                return;
            }
            var csrfToken = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                url: '/panel/helpdesk/customers',
                method: 'POST',
                dataType: 'json',
                data: { name: name, email: email || undefined, phone: phone || undefined },
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            })
            .done(function(resp) {
                var customerId = resp.customer && resp.customer.id;
                if (!customerId) {
                    toastr.error('No se pudo obtener el ID del contacto');
                    $btn.prop('disabled', false);
                    return;
                }
                createConversation(customerId);
            })
            .fail(function(xhr) {
                var errors = xhr.responseJSON && xhr.responseJSON.errors;
                var msg = errors && Object.values(errors)[0] && Object.values(errors)[0][0]
                    || (xhr.responseJSON && xhr.responseJSON.message)
                    || 'Error al crear el contacto';
                toastr.error(msg);
                $btn.prop('disabled', false);
            });
            return;
        }

        if (!selectedContact) {
            toastr.warning('Selecciona un destinatario');
            $btn.prop('disabled', false);
            return;
        }

        createConversation(selectedContact);
    });

    $(document).on('click', '#ncBtnBack', function() {
        goToStep(1);
    });

    $(document).on('click', '.nc-contact-card', function() {
        selectedContact = $(this).data('contact-id');
        creatingNewContact = false;
        $('.nc-contact-card').removeClass('nc-contact-selected');
        $(this).addClass('nc-contact-selected');
    });

    var ncSearchTimer = null;

    $(document).on('input', '#ncContactSearch', function() {
        var q = $(this).val().trim();
        clearTimeout(ncSearchTimer);
        if (!q) {
            $('#ncContactList').html('<div class="nc-empty-hint" id="ncContactHint"><i class="fas fa-magnifying-glass"></i><span>Escribe para buscar contactos</span></div>');
            return;
        }
        $('#ncContactList').html('<div class="nc-empty-hint"><i class="fas fa-spinner fa-spin"></i><span>Buscando…</span></div>');
        ncSearchTimer = setTimeout(function() {
            $.ajax({
                url: '/panel/helpdesk/customers/search',
                method: 'GET',
                dataType: 'json',
                data: { q: q },
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            }).done(function(data) {
                if (!data.length) {
                    $('#ncContactList').html('<div class="nc-empty-hint"><i class="fas fa-user-slash"></i><span>Sin resultados para "' + $('<span>').text(q).html() + '"</span></div>');
                    return;
                }
                var html = '';
                $.each(data, function(i, c) {
                    var initials = (c.name || '?').split(' ').slice(0,2).map(function(w){ return w[0] || ''; }).join('').toUpperCase() || '?';
                    var sub = c.email || c.phone || '';
                    html += '<div class="nc-contact-card" data-contact-id="' + c.id + '">' +
                        '<div class="av c' + ((i % 8) + 1) + '">' + $('<span>').text(initials).html() + '</div>' +
                        '<div class="info">' +
                        '<div class="nm">' + $('<span>').text(c.name || 'Sin nombre').html() + '</div>' +
                        (sub ? '<div class="s">' + $('<span>').text(sub).html() + '</div>' : '') +
                        '</div></div>';
                });
                $('#ncContactList').html(html);
            }).fail(function() {
                $('#ncContactList').html('<div class="nc-empty-hint"><i class="fas fa-circle-exclamation"></i><span>Error al buscar. Intenta de nuevo.</span></div>');
            });
        }, 300);
    });

    $(document).on('bv:modal:open', function(e, name) {
        if (name !== 'newconv') { return; }
        goToStep(1);
        selectedContact = null;
        creatingNewContact = false;
        selectedChannel = 'whatsapp';
        clearTimeout(ncSearchTimer);
        $('.nc-channel').removeClass('on');
        $('[data-channel="whatsapp"]').addClass('on');
        $('#ncHsmWarning').show();
        $('#ncNewContactForm').hide();
        $('#ncContactSearch').val('');
        $('#ncContactList').html('<div class="nc-empty-hint" id="ncContactHint"><i class="fas fa-magnifying-glass"></i><span>Escribe para buscar contactos</span></div>');
        $('#ncBtnAddContact').show();
        $('#ncBtnNext').prop('disabled', false);
    });
}());
</script>
@endpush
@endonce

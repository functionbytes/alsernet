@extends('template::layouts.default')

@php Theme::set('page', $page); @endphp

@section('seo_head')
    @include(Theme::getThemeNamespace() . '::partials.seo-head')
@endsection

@section('content')
    <div class="ck-content">
        @if($transContent)
            {!! $transContent !!}
        @else
            <p class="text-muted text-center">{{ trans('page::messages.no_content') }}</p>
        @endif
    </div>
@endsection

@push('head')
<link rel="stylesheet" href="{{ asset('core/select2/css/select2.min.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('core/select2/js/select2.min.js') }}"></script>
<script src="{{ asset('core/validate/jquery.validate.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var $ = window.jQuery;

    var $form = $('#multiStepForm');
    if (!$form.length) { return; }

    // Replace nice-select with select2
    $('#multiStepForm select').niceSelect('destroy');
    $('#multiStepForm select').select2({ width: '100%', minimumResultsForSearch: 6 });

    var totalSteps  = 5;
    var currentStep = 1;
    var waPhone     = '{{ preg_replace('/[^0-9]/', '', theme_option('phone', '351913893833')) }}';
    var submitUrl   = '{{ url('/forms/presupuesto/submit') }}';

    // Replace stale CSRF token
    $form.find('input[name="_token"]').val($('meta[name="csrf-token"]').attr('content'));

    // ── jQuery Validate ──────────────────────────────────────────────────────
    var validator = $form.validate({
        errorElement: 'div',
        errorClass: 'invalid-feedback d-block',
        highlight:   function (el) { $(el).addClass('is-invalid'); },
        unhighlight: function (el) { $(el).removeClass('is-invalid').siblings('.invalid-feedback').remove(); },
        errorPlacement: function (error, element) {
            if (element.is(':radio')) {
                var $container = element.closest('.service-selection');
                $container.find('.invalid-feedback').remove();
                error.appendTo($container);
            } else {
                element.siblings('.invalid-feedback').remove();
                error.insertAfter(element);
            }
        },
        messages: @php
            $msgs = [
                'es' => ['nombre' => 'Por favor, introduzca su nombre completo.', 'email_req' => 'Por favor, introduzca su email.', 'email_fmt' => 'Introduzca un email válido.', 'telefono' => 'Por favor, introduzca su teléfono.', 'localidad' => 'Por favor, introduzca su código postal o localidad.', 'servicio' => 'Por favor, seleccione un servicio.'],
                'pt' => ['nombre' => 'Por favor, introduza o seu nome completo.', 'email_req' => 'Por favor, introduza o seu email.', 'email_fmt' => 'Introduza um email válido.', 'telefono' => 'Por favor, introduza o seu telefone.', 'localidad' => 'Por favor, introduza o seu código postal ou localidade.', 'servicio' => 'Por favor, selecione um serviço.'],
                'en' => ['nombre' => 'Please enter your full name.', 'email_req' => 'Please enter your email.', 'email_fmt' => 'Please enter a valid email.', 'telefono' => 'Please enter your phone number.', 'localidad' => 'Please enter your postcode or town.', 'servicio' => 'Please select a service.'],
                'fr' => ['nombre' => 'Veuillez saisir votre prénom et nom complet.', 'email_req' => 'Veuillez saisir votre email.', 'email_fmt' => 'Saisissez un email valide.', 'telefono' => 'Veuillez saisir votre numéro de téléphone.', 'localidad' => 'Veuillez saisir votre code postal ou localité.', 'servicio' => 'Veuillez sélectionner un service.'],
                'de' => ['nombre' => 'Bitte geben Sie Ihren vollständigen Namen ein.', 'email_req' => 'Bitte geben Sie Ihre E-Mail-Adresse ein.', 'email_fmt' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.', 'telefono' => 'Bitte geben Sie Ihre Telefonnummer ein.', 'localidad' => 'Bitte geben Sie Ihre Postleitzahl oder Ihren Ort ein.', 'servicio' => 'Bitte wählen Sie einen Service aus.'],
                'it' => ['nombre' => 'Inserisci il tuo nome completo.', 'email_req' => 'Inserisci la tua email.', 'email_fmt' => 'Inserisci un\'email valida.', 'telefono' => 'Inserisci il tuo numero di telefono.', 'localidad' => 'Inserisci il tuo codice postale o la tua località.', 'servicio' => 'Seleziona un servizio.'],
            ];
            $m = $msgs[app()->getLocale()] ?? $msgs['es'];
            echo json_encode(['nombre' => $m['nombre'], 'email' => ['required' => $m['email_req'], 'email' => $m['email_fmt']], 'telefono' => $m['telefono'], 'localidad' => $m['localidad'], 'servicio' => $m['servicio']]);
        @endphp,
    });

    // ── Stepper ──────────────────────────────────────────────────────────────
    function updateStepper(step) {
        $('.step-item').each(function () {
            var s = parseInt($(this).data('step'));
            $(this).toggleClass('active', s === step).toggleClass('completed', s < step);
        });
    }

    // ── Show step ────────────────────────────────────────────────────────────
    function showStep(step) {
        // Hide all steps; also show any ancestor form-step so nested ones can appear
        $('.form-step').removeClass('active').hide();
        var $target = $('.form-step[data-step="' + step + '"]');
        $target.addClass('active').show();
        // If target is nested inside another form-step, make that ancestor visible too
        $target.parents('.form-step').show();
        updateStepper(step);
        currentStep = step;
        if (step === totalSteps) { buildSummary(); }
        var $card = $('.estimate-form-card');
        if ($card.length) {
            $('html, body').animate({ scrollTop: $card.offset().top - 100 }, 300);
        }
    }

    // ── Validate current step ────────────────────────────────────────────────
    function validateStep(step) {
        var valid = true;
        $('.form-step[data-step="' + step + '"] [required]').each(function () {
            if (!validator.element(this)) { valid = false; }
        });
        return valid;
    }

    // ── Summary ──────────────────────────────────────────────────────────────
    function buildSummary() {
        var rows = [
            { label: 'Nombre',    val: $('[name="nombre"]').val() },
            { label: 'Email',     val: $('[name="email"]').val() },
            { label: 'Teléfono', val: $('[name="telefono"]').val() },
            { label: 'Localidad', val: $('[name="localidad"]').val() },
            { label: 'Servicio',  val: $('[name="servicio"]:checked').val() },
            { label: 'Inmueble',  val: $('[name="tipo_inmueble"]').val() },
            { label: 'Obra',      val: $('[name="tipo_obra"]').val() },
            { label: 'Cantidad',  val: $('[name="cantidad"]').val() },
            { label: 'Urgencia',  val: $('[name="urgencia"]').val() },
        ];
        var html = '<dl class="row mb-0">';
        rows.forEach(function (r) {
            if (r.val) {
                html += '<dt class="col-sm-4">' + r.label + '</dt><dd class="col-sm-8">' + $('<span>').text(r.val).html() + '</dd>';
            }
        });
        html += '</dl>';
        $('#resumenFormulario').html(html);
    }

    // ── Siguiente ────────────────────────────────────────────────────────────
    $(document).on('click', '.btn-estimate-next', function () {
        if (!validateStep(currentStep)) { return; }
        if (currentStep < totalSteps) { showStep(currentStep + 1); }
    });

    // ── Anterior ─────────────────────────────────────────────────────────────
    $(document).on('click', '.btn-estimate-prev', function () {
        if (currentStep > 1) { showStep(currentStep - 1); }
    });

    // ── WhatsApp ─────────────────────────────────────────────────────────────
    $(document).on('click', '#submitWhatsApp', function () {
        var t = '🏠 *Solicitud de Presupuesto — Caixilharia Blanco*\n\n';
        t += '👤 *Nombre:* '    + ($('[name="nombre"]').val()           || '—') + '\n';
        t += '📧 *Email:* '     + ($('[name="email"]').val()            || '—') + '\n';
        t += '📱 *Teléfono:* '  + ($('[name="telefono"]').val()         || '—') + '\n';
        t += '📍 *Localidad:* ' + ($('[name="localidad"]').val()        || '—') + '\n';
        t += '🔧 *Servicio:* '  + ($('[name="servicio"]:checked').val() || '—') + '\n';
        var msg = $('[name="mensaje"]').val();
        if (msg) { t += '\n📝 *Mensaje:* ' + msg; }
        window.open('https://wa.me/' + waPhone + '?text=' + encodeURIComponent(t), '_blank');
    });

    // ── Email submit (AJAX) ──────────────────────────────────────────────────
    $form.on('submit', function (e) {
        e.preventDefault();
        var $btn = $form.find('[type="submit"]').prop('disabled', true);
        var data = $form.serializeArray();
        data.push({ name: '_start_time', value: Math.floor(Date.now() / 1000) - 10 });

        $.ajax({
            url: submitUrl, type: 'POST', data: $.param(data),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                if (res && res.success) {
                    $form.hide();
                    var msg = res.message || '¡Solicitud enviada! Le contactaremos en menos de 24 horas.';
                    $form.after('<div class="alert alert-success mt-4 text-center"><i class="fa-solid fa-circle-check me-2"></i>' + msg + '</div>');
                } else {
                    toastr.error(res.message || 'No se pudo enviar. Inténtelo de nuevo.');
                    $btn.prop('disabled', false);
                }
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    toastr.error(Object.values(xhr.responseJSON.errors).flat().join('<br>'), 'Errores en el formulario');
                } else {
                    toastr.error('Error al enviar. Por favor, inténtelo de nuevo.');
                }
                $btn.prop('disabled', false);
            },
        });
    });

    // ── Init ─────────────────────────────────────────────────────────────────
    // HTML parser may nest steps 3-5 inside step 2 due to the service-selection
    // structure; move them to be direct children of the form.
    [3, 4, 5].forEach(function (n) {
        var $s = $('[data-step="' + n + '"].form-step');
        if ($s.parent()[0] !== $form[0]) { $form.append($s); }
    });
    showStep(1);
});
</script>
@endpush

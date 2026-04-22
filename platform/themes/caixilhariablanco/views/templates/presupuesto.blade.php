@extends('template::layouts.default')

@php Theme::set('page', $page); @endphp

@section('seo_head')
    @include(Theme::getThemeNamespace() . '::partials.seo-head')
@endsection

@section('content')

    <!-- Hero -->
    <div class="hero2-section-area estimate-hero">
        <div class="container text-center text-white" style="position:relative;z-index:10;">
            <div class="row justify-content-center">
                <div class="col-lg-9 col-xl-8">
                    <span class="estimate-hero-badge">500+ instalaciones realizadas</span>
                    <h1 class="estimate-hero-title">Solicite su presupuesto <span>gratis</span></h1>
                    <div class="estimate-hero-benefits">
                        <div class="estimate-benefit-item"><i class="fa-regular fa-circle-check"></i><span>Respuesta en menos de 24h.</span></div>
                        <div class="estimate-benefit-item"><i class="fa-regular fa-circle-check"></i><span>Sin compromiso ni obligación.</span></div>
                        <div class="estimate-benefit-item"><i class="fa-regular fa-circle-check"></i><span>Visita técnica gratuita.</span></div>
                        <div class="estimate-benefit-item"><i class="fa-regular fa-circle-check"></i><span>Financiación hasta 60 meses.</span></div>
                    </div>
                    @php $phone = theme_option('phone', '+351 913 893 833'); @endphp
                    <div class="estimate-hero-ctas">
                        <a href="tel:{{ preg_replace('/\s+/', '', $phone) }}" class="estimate-cta-primary">
                            <i class="fa-solid fa-phone"></i> Llamar ahora
                        </a>
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $phone) }}" class="estimate-cta-secondary" target="_blank" rel="noopener">
                            <i class="fa-brands fa-whatsapp"></i> WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Section -->
    <div class="row form-wrapper-row">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="estimate-form-card">
                        {!! app('shortcode')->compile('[form slug="presupuesto" show_title="false"]') !!}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FAQ -->
    <div class="faq4-section-area sp1">
        <div class="container">
            <div class="row">
                <div class="col-lg-7 m-auto">
                    <div class="faq-header-area heading6 text-center">
                        <h5>FAQ'S</h5>
                        <h2>Preguntas frecuentes sobre presupuestos</h2>
                        <p>Resolvemos sus dudas más comunes sobre nuestro proceso de presupuestos. Transparencia y confianza en cada paso.</p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="faq-auhtoir-area2">
                        <div class="accordion accordion-flush active" id="faqAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">¿Es realmente gratis el presupuesto?</button>
                                </h2>
                                <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">Sí, completamente gratis. Tanto el presupuesto como la visita técnica no tienen ningún coste. Solo pagará si decide contratar nuestros servicios. No hay compromisos ni obligaciones.</div>
                                </div>
                            </div>
                            <div class="space20"></div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">¿Cuánto tardan en responder?</button>
                                </h2>
                                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">Nos comprometemos a dar una respuesta inicial en menos de 24 horas laborables. El presupuesto detallado se entrega en 3-5 días tras la visita técnica.</div>
                                </div>
                            </div>
                            <div class="space20"></div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">¿Me obligo a contratar al solicitar presupuesto?</button>
                                </h2>
                                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">No, en absoluto. Solicitar un presupuesto es totalmente sin compromiso. Puede comparar con otras empresas y decidir libremente sin ningún tipo de presión comercial.</div>
                                </div>
                            </div>
                            <div class="space20"></div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">¿Hacen visitas a domicilio?</button>
                                </h2>
                                <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">Sí, ofrecemos visitas técnicas gratuitas en toda la región de Alcobaça, Leiria, Marinha Grande, Nazaré y Caldas da Rainha. Un técnico especializado visitará su propiedad para tomar medidas exactas y hacer recomendaciones personalizadas.</div>
                                </div>
                            </div>
                            <div class="space20"></div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">¿Puedo modificar el presupuesto después?</button>
                                </h2>
                                <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">Por supuesto. El presupuesto inicial es flexible y puede ajustarse según sus necesidades: materiales, acabados, cantidades o servicios adicionales. Trabajamos con usted hasta encontrar la solución perfecta.</div>
                                </div>
                            </div>
                            <div class="space20"></div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">¿Ofrecen financiación para los proyectos?</button>
                                </h2>
                                <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">Sí, ofrecemos varias opciones de financiación para proyectos superiores a €1.000. Pago al contado con 5% de descuento, pago en 2 veces sin intereses, o financiación bancaria hasta 60 meses desde 4.9% TAN.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    // Add WhatsApp CTA to the presupuesto form success message
    var waPhone = '{{ preg_replace('/[^0-9]/', '', theme_option('phone', '351913893833')) }}';

    function buildWaText($form) {
        var t = '🏠 *Solicitud de Presupuesto — Caixilharia Blanco*\n\n';
        t += '👤 *Nombre:* '    + $form.find('[name="nombre"]').val()    + '\n';
        t += '📧 *Email:* '     + $form.find('[name="email"]').val()     + '\n';
        t += '📱 *Teléfono:* '  + $form.find('[name="telefono"]').val()  + '\n';
        t += '📍 *Localidad:* ' + $form.find('[name="localidad"]').val() + '\n';
        t += '🔧 *Servicio:* '  + ($form.find('[name="servicio"]:checked').val() || '—') + '\n';
        var ms = $form.find('[name="mensaje"]').val();
        if (ms) t += '\n📝 *Mensaje:* ' + ms;
        return t;
    }

    // Observe success message to inject WhatsApp CTA
    var observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (m) {
            m.addedNodes.forEach(function (node) {
                if ($(node).hasClass('forms-success-message') || $(node).find('.forms-success-message').length) {
                    // already injected? skip
                }
            });
        });

        // Check for visible success message
        $('.forms-success-message:not(.d-none):not([data-wa-injected])').each(function () {
            var $success = $(this);
            $success.attr('data-wa-injected', '1');
            var $form = $success.closest('.forms-wrapper').find('form');
            var waText = buildWaText($form);
            var waUrl = 'https://wa.me/' + waPhone + '?text=' + encodeURIComponent(waText);
            $success.find('.alert').append(
                '<div class="mt-3">' +
                '<a href="' + waUrl + '" target="_blank" rel="noopener" class="btn btn-success px-4">' +
                '<i class="fa-brands fa-whatsapp me-2"></i>Continuar por WhatsApp</a>' +
                '</div>'
            );
        });
    });

    observer.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['class'] });
});
</script>
@endpush

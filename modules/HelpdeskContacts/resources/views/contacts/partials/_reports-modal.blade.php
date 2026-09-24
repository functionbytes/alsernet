{{--
    Modal "Informes y clientes en riesgo" (mockup pieza #14) — versión
    resumida de contacts.reports (la página completa sigue existiendo y es
    donde este modal enlaza para ver el detalle). Se rellena por AJAX contra
    GET contacts.reports.summary al abrirse, mismo payload cacheado 2 min por
    agente que ya usa la página completa.
--}}
<div class="modal fade ct-mdl" id="contact-reports-modal" tabindex="-1" aria-labelledby="reportsModalLabel" aria-hidden="true"
     data-summary-url="{{ route('contacts.reports.summary') }}"
     data-risk-index-url="{{ route('contacts.index', ['view' => 'risk']) }}"
     data-risk-export-url="{{ route('contacts.export', ['view' => 'risk', 'columns' => ['health']]) }}">
    <div class="modal-dialog modal-dialog-centered ct-mdl-lg">
        <div class="modal-content">
            <div class="modal-header">
                <span class="ct-modal-icon me-2"><i class="fas fa-chart-line"></i></span>
                <div class="flex-grow-1">
                    <div class="ct-modal-eyebrow">Contactos · Informes</div>
                    <h5 class="modal-title" id="reportsModalLabel">Informes y clientes en riesgo</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body d-flex flex-column gap-2">
                <div class="c3-stats">
                    <div class="c3-stat"><div class="l">Contactos</div><div class="n" id="contact-reports-total">—</div></div>
                    <div class="c3-stat"><div class="l">En riesgo</div><div class="n is-good" id="contact-reports-risk">—</div></div>
                    <div class="c3-stat"><div class="l">Salud media</div><div class="n" id="contact-reports-health">—</div></div>
                </div>
                <div class="psc-card c3-card">
                    <div class="psc-card-head">Clientes en riesgo
                        <a href="{{ route('contacts.index', ['view' => 'risk']) }}" class="c3-link ms-auto" id="contact-reports-view-btn">Ver todos</a>
                    </div>
                    <div id="contact-reports-list">
                        @include('contacts::contacts.partials._skeleton', ['rows' => 3])
                    </div>
                </div>
            </div>
            <div class="modal-footer ct-modal-footer-inline">
                <button type="button" class="psc-btn psc-btn--primary d-none" id="contact-reports-campaign-btn">Enviar plantilla</button>
                <a href="{{ route('contacts.export', ['view' => 'risk', 'columns' => ['health']]) }}" class="psc-btn psc-btn--outline">Exportar informe</a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    @php
        $reportsModalJsMtime = @filemtime(public_path('modules/contacts/js/reports-modal.js'));
    @endphp
    <script src="{{ asset('modules/contacts/js/reports-modal.js') }}?v={{ $reportsModalJsMtime }}"></script>
@endpush

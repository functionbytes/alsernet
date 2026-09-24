{{-- Vínculos e identidad (ContactLinksController + rutas de identidad de
     HelpdeskIntegration). Lo pinta y gestiona contacts-links.js. --}}
<div class="modal fade ct-mdl ct-mdl-md" id="contact-links-modal" tabindex="-1" aria-labelledby="contactLinksLabel" aria-hidden="true"
     data-url="{{ route('contacts.links.show', $customer) }}"
     data-suggestions-url="{{ route('contacts.links.suggestions', $customer) }}"
     data-unlink-url="{{ route('contacts.links.unlink', $customer) }}"
     data-link-url="{{ route('contacts.external-link', $customer) }}">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <span class="ct-modal-icon me-2"><i class="fas fa-link"></i></span>
                <div class="flex-grow-1">
                    <div class="ct-modal-eyebrow">Contacto · Fuentes</div>
                    <h5 class="modal-title" id="contactLinksLabel">Vínculos e identidad</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="c3k-sec">
                    <div class="c3k-title">Identidad del cliente</div>
                    <div id="c3k-identity"><div class="ctf-skel-line"></div></div>
                </div>
                <div class="c3k-sec">
                    <div class="c3k-title">Plataformas vinculadas</div>
                    <div id="c3k-linked"><div class="ctf-skel-line"></div></div>
                </div>
                <div class="c3k-sec d-none" id="c3k-suggest-sec">
                    <div class="c3k-title">Sugerencias</div>
                    <div id="c3k-suggest"></div>
                </div>
                <div class="c3k-sec">
                    <div class="c3k-title">Historial</div>
                    <div id="c3k-history"><div class="ctf-skel-line"></div></div>
                </div>
            </div>
        </div>
    </div>
</div>

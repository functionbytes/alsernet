{{-- Modal: Integraciones del cliente --}}
<div class="bv-modal" data-bv-modal-name="customer-integrations">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box primary" id="ciHeadIcon"><i class="fas fa-plug"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">CLIENTE · INTEGRACIONES</span>
                <div class="bv-modal-title"><span id="ciModalTitle">Integraciones del cliente</span></div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>

        <div class="bv-modal-body">

            {{-- Vista: prompt de verificacion de identidad — el flujo completo
                 (selector de canal, codigo OTP, intentos/bloqueo, caducidad)
                 vive en el modal reutilizable verify-customer-identity. --}}
            <div id="ciGateView" style="display:none">
                <div class="info-table" id="ciGateCustomer">
                    <div class="lbl">Cliente</div>
                    <div class="val" id="ciGateCustomerName">—</div>
                    <div class="lbl">Contacto</div>
                    <div class="val mono" id="ciGateCustomerContact">—</div>
                </div>

                <div class="bv-oc-empty" style="padding:20px 10px">
                    <i class="fas fa-user-lock"></i>
                    <div class="title">Identidad no verificada</div>
                    <div>Verifica la identidad del cliente antes de ver o gestionar sus integraciones.</div>
                </div>
            </div>

            {{-- Vista principal: lista de integraciones --}}
            <div id="ciMainView" style="display:none">
                <div class="info-table" id="ciCustomer">
                    <div class="lbl">Cliente</div>
                    <div class="val" id="ciCustomerName">—</div>
                    <div class="lbl">Email</div>
                    <div class="val mono" id="ciCustomerEmail">—</div>
                </div>

                <div id="ciList">
                    <div class="bv-oc-loading"><i class="fas fa-spinner fa-spin"></i> Cargando…</div>
                </div>

                <div id="ciLastActivity" class="bv-intg-audit" style="display:none"></div>
            </div>

            {{-- Vista: historial completo de auditoría --}}
            <div id="ciAuditView" style="display:none">
                <div id="ciAuditList">
                    <div class="bv-oc-loading"><i class="fas fa-spinner fa-spin"></i> Cargando…</div>
                </div>
            </div>

            {{-- Vista de búsqueda/vinculación --}}
            <div id="ciLinkPanel" style="display:none">
                <div class="field">
                    <div class="flabel">Buscar cliente en las plataformas disponibles</div>
                    <div class="search-field">
                        <i class="fas fa-magnifying-glass"></i>
                        <input type="text" id="ciSearchQ" placeholder="Email, teléfono o ID…">
                    </div>
                    <div class="flabel flabel-hint">Introduce el email, teléfono o identificador con el que el cliente aparece en la plataforma.</div>
                </div>
                <div id="ciAutoNote" class="minfo mb-2" style="display:none">
                    <i class="fas fa-wand-magic-sparkles"></i>
                    <div>Este cliente no tiene ninguna plataforma vinculada — buscando automáticamente.</div>
                </div>
                <div class="field">
                    <div class="flabel flabel-eyebrow">Resultado de la búsqueda</div>
                    <div id="ciSearchResults">
                        <div class="bv-oc-empty"><i class="fas fa-magnifying-glass"></i><div>Introduce un email, teléfono o identificador para buscar al cliente.</div></div>
                    </div>
                </div>
            </div>

        </div>

        <div class="bv-modal-foot">
            {{-- Footer: gate de identidad (delega al modal verify-customer-identity) --}}
            <div id="ciFootGate">
                <button class="btn-primary w-100 mb-2" id="ciOpenVerify" type="button">Verificar identidad</button>
                <button class="btn-secondary w-100 mb-2" id="ciOpenSearch" type="button" style="display:none">Buscar cliente en plataformas</button>
                <button class="btn-secondary w-100" data-bv-close>Cerrar</button>
            </div>
            {{-- Footer vista principal --}}
            <div id="ciFootMain" style="display:none">
                <button class="btn-primary w-100 mb-2" id="ciSyncAll" type="button" style="display:none">Sincronizar todo</button>
                <button class="btn-secondary w-100 mb-2" id="ciLink" type="button" disabled>
                    <span id="ciLinkLabel">Vincular plataforma</span>
                </button>
                <button class="btn-secondary w-100" data-bv-close>Cerrar</button>
            </div>
            {{-- Footer vista de búsqueda --}}
            <div id="ciFootSearch" style="display:none">
                <button class="btn-secondary w-100" id="ciBackBtn" type="button">
                    <i class="fas fa-arrow-left"></i> Volver a integraciones
                </button>
            </div>
            {{-- Footer vista de historial de auditoría --}}
            <div id="ciFootAudit" style="display:none">
                <button class="btn-secondary w-100" id="ciAuditBackBtn" type="button">
                    <i class="fas fa-arrow-left"></i> Volver a integraciones
                </button>
            </div>
        </div>
    </div>
</div>

@once
@push('css')
<style>
    #ciLinkPanel .flabel-hint { font-weight: 400; color: var(--bv-text-muted); }
    #ciLinkPanel .flabel-eyebrow { font-size: 9.5px; text-transform: uppercase; letter-spacing: .06em; }
    #ciSearchResults { display: flex; flex-direction: column; gap: 6px; }
</style>
@endpush
@endonce

@once
@push('scripts')
<script>
    window.HelpdeskIntegrationLang = Object.assign(window.HelpdeskIntegrationLang || {}, @json(__('helpdeskintegration::messages.js')));
</script>
<script src="{{ asset('vendor/helpdeskintegration/customer-integrations.js') }}?v={{ @filemtime(public_path('vendor/helpdeskintegration/customer-integrations.js')) }}" defer></script>
@endpush
@endonce

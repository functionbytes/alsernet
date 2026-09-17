{{-- Modal: Visualizar email enviado (#28 ve-email-viewer) --}}
<div class="modal fade" id="emailViewerModal" tabindex="-1" aria-labelledby="evTitleText" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bv-ev-content">

            @include('helpdesk::helpdesk.inbox.partials.modals._modal-header', [
                'icon'      => 'far fa-envelope-open',
                'label'     => __('helpdesk::helpdesk.inbox.modals.email_viewer_header_label'),
                'labelSub'  => __('helpdesk::helpdesk.inbox.modals.email_viewer_header_label_sub'),
                'titleId'   => 'evTitleText',
                'titleText' => __('helpdesk::helpdesk.inbox.modals.email_viewer_title'),
                'chipId'    => 'evIdChip',
            ])

            {{-- Two-column viewer --}}
            <div class="bv-email-viewer" id="evContainer">

                {{-- Loading --}}
                <div class="bv-ev-loading" id="evLoading">
                    <i class="fas fa-spinner fa-spin"></i> {{ __('helpdesk::helpdesk.inbox.modals.email_viewer_loading') }}
                </div>

                {{-- LEFT: Vista previa HTML --}}
                <div class="bv-ev-preview d-none" id="evPreview">
                    <div class="bv-ev-preview-head">
                        <span>{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_preview_label') }}</span>
                        <div class="bv-ev-device-toggle">
                            <button type="button" class="on bv-ev-dt-btn" data-ev-device="desktop" title="{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_device_desktop') }}">
                                <i class="far fa-window-maximize"></i>
                            </button>
                            <button type="button" class="bv-ev-dt-btn" data-ev-device="mobile" title="{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_device_mobile') }}">
                                <i class="fas fa-mobile-screen"></i>
                            </button>
                        </div>
                    </div>
                    <div class="bv-ev-preview-body">
                        <iframe id="evIframe" class="bv-ev-iframe" sandbox="allow-same-origin" title="{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_iframe_title') }}"></iframe>
                    </div>
                </div>

                {{-- RIGHT: Detalle --}}
                <div class="bv-ev-detail d-none" id="evDetail">

                    {{-- Pestañas: Detalle siempre; Traza/Apertura solo si el
                         email tiene un EmailLog correlacionado (ver
                         ConversationEmailController::emailLogShow) — un
                         correo sin ese cruce no tiene nada real que mostrar
                         ahí y las pestañas se ocultan en vez de quedar vacías. --}}
                    <div class="bv-ev-tabs" id="evTabs">
                        <button type="button" class="bv-ev-tab on" data-ev-tab="detail">{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_tab_detail') }}</button>
                        <button type="button" class="bv-ev-tab d-none" data-ev-tab="trace" id="evTabTrace" data-empty-text="{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_trace_empty') }}">{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_tab_trace') }}</button>
                        <button type="button" class="bv-ev-tab d-none" data-ev-tab="opens" id="evTabOpens">{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_tab_opens') }}</button>
                    </div>

                    <div class="bv-ev-tabpanel" data-ev-panel="detail" id="evPanelDetail">

                    {{-- Detalle del email --}}
                    <div class="bv-ev-section">
                        <div class="bv-ev-hdr">
                            <span class="t">{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_detail_title') }}</span>
                            <span class="s">{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_detail_subtitle') }}</span>
                        </div>
                        <div class="bv-ev-field">
                            <span class="k">{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_field_subject') }}</span>
                            <span class="v" id="evSubject">—</span>
                        </div>
                        <div class="bv-ev-field-row">
                            <span class="k">{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_field_recipient') }}</span>
                            <span class="v mono" id="evTo">—</span>
                        </div>
                        <div class="bv-ev-field-row d-none" id="evCcRow">
                            <span class="k">{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_field_cc') }}</span>
                            <span class="v mono" id="evCc">—</span>
                        </div>
                        <div class="bv-ev-field-row d-none" id="evTypeRow">
                            <span class="k">{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_field_type') }}</span>
                            <span class="v"><span class="bv-ev-tag" id="evType">—</span></span>
                        </div>
                        <div class="bv-ev-field-row">
                            <span class="k">{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_field_status') }}</span>
                            <span class="v"><span class="bv-ev-status" id="evStatus">—</span></span>
                        </div>
                        <div class="bv-ev-field-row d-none" id="evSentByRow">
                            <span class="k">{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_field_sent_by') }}</span>
                            <span class="v" id="evSentBy">—</span>
                        </div>
                        <div class="bv-ev-field-row">
                            <span class="k">{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_field_sent_at') }}</span>
                            <span class="v mono" id="evSentAt">—</span>
                        </div>
                        <div class="bv-ev-field-row d-none" id="evTemplateRow">
                            <span class="k">{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_field_template') }}</span>
                            <span class="v" id="evTemplate">—</span>
                        </div>
                        <div class="bv-ev-field d-none" id="evErrorRow">
                            <span class="k">{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_field_error') }}</span>
                            <span class="v text-dark" id="evError">—</span>
                        </div>
                    </div>

                    {{-- Acciones rápidas — el enlace al inspector completo va
                         primero: es la acción que más vale destacar cuando
                         existe, delante de las rutinarias imprimir/volver. --}}
                    <div class="bv-ev-section">
                        <div class="bv-ev-hdr">
                            <span class="t">{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_actions_title') }}</span>
                            <span class="s">{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_actions_subtitle') }}</span>
                        </div>
                        <div class="bv-ev-actions">
                            {{-- Enlace al inspector completo de HelpdeskEmailActivity
                                 (fuente/cabeceras/link-check/bitácora): ver
                                 "activity_url" en ConversationEmailController::emailLogShow.
                                 Oculto si ese email no tiene EmailLog correlacionado o el
                                 módulo satélite no está activo. --}}
                            <a href="#" target="_blank" rel="noopener" class="btn btn-primary d-none" id="evBtnActivity">{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_btn_full_trace') }}</a>
                            <button type="button" class="btn btn-outline-secondary" id="evBtnPrint">{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_btn_print') }}</button>
                            <button type="button" class="btn btn-outline-secondary" id="evBtnBack">{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_btn_back') }}</button>
                            <button type="button" class="btn btn-outline-secondary d-none" id="evBtnDoc">{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_btn_manage_doc') }}</button>
                        </div>
                    </div>

                    {{-- Documento relacionado --}}
                    <div class="bv-ev-section d-none" id="evDocSection">
                        <div class="bv-ev-hdr">
                            <span class="t">{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_doc_title') }}</span>
                            <span class="s">{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_doc_subtitle') }}</span>
                        </div>
                        <div class="bv-ev-field-row d-none" id="evOrderRow">
                            <span class="k">{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_field_order_id') }}</span>
                            <span class="v mono" id="evOrder">—</span>
                        </div>
                        <div class="bv-ev-field-row d-none" id="evCustomerRow">
                            <span class="k">{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_field_customer') }}</span>
                            <span class="v" id="evCustomer">—</span>
                        </div>
                        <div class="bv-ev-field-row d-none" id="evDocStatusRow">
                            <span class="k">{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_field_doc_status') }}</span>
                            <span class="v"><span class="bv-ev-status" id="evDocStatus">—</span></span>
                        </div>
                    </div>

                    {{-- Metadatos técnicos — id_label/created_at/sent_by ya
                         venían en la respuesta pero no se mostraban en ningún
                         sitio. --}}
                    <div class="bv-ev-section">
                        <div class="bv-ev-hdr">
                            <span class="t">{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_meta_title') }}</span>
                            <span class="s">{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_meta_subtitle') }}</span>
                        </div>
                        <div class="bv-ev-meta-row">
                            <span class="k">{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_meta_id') }}</span>
                            <span class="v" id="evMetaId">—</span>
                        </div>
                        <hr>
                        <div class="bv-ev-meta-group">
                            <div class="bv-ev-meta-row">
                                <span class="k">{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_meta_registered') }}</span>
                                <span class="v" id="evMetaCreated">—</span>
                            </div>
                            <div class="bv-ev-meta-row">
                                <span class="k">{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_field_sent_by') }}</span>
                                <span class="v" id="evMetaSentBy">—</span>
                            </div>
                        </div>
                    </div>

                    </div>{{-- /evPanelDetail --}}

                    {{-- Traza de envío — timeline construido en JS a partir de
                         "trace" (ver ConversationEmailController::buildEmailTrace,
                         misma lectura de EmailLog que el inspector de
                         HelpdeskEmailActivity). --}}
                    <div class="bv-ev-tabpanel d-none" data-ev-panel="trace" id="evPanelTrace">
                        <div class="bv-ev-section">
                            <div class="bv-ev-hdr">
                                <span class="t">{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_trace_title') }}</span>
                                <span class="s">{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_trace_hint') }}</span>
                            </div>
                            <div class="bv-ev-trace" id="evTraceList"></div>
                        </div>
                    </div>

                    {{-- Aperturas + clics en una sola tarjeta, separadas por <hr>
                         — solo se rellenan si el email tuvo seguimiento (ver
                         EmailLog::hasOpenTracking()/hasClickTracking(), hoy
                         true solo para HelpdeskTickets). --}}
                    <div class="bv-ev-tabpanel d-none" data-ev-panel="opens" id="evPanelOpens">
                        <div class="bv-ev-section">
                            <div class="bv-ev-block d-none" id="evOpensSection" data-empty-text="{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_opens_empty') }}">
                                <div class="bv-ev-hdr">
                                    <span class="t">{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_opens_title') }}</span>
                                    <span class="s">{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_opens_hint') }}</span>
                                </div>
                                <div class="bv-ev-event-list" id="evOpensList"></div>
                            </div>
                            <hr class="d-none" id="evOpensClicksSep">
                            <div class="bv-ev-block d-none" id="evClicksSection" data-empty-text="{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_clicks_empty') }}">
                                <div class="bv-ev-hdr">
                                    <span class="t">{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_clicks_title') }}</span>
                                    <span class="s">{{ __('helpdesk::helpdesk.inbox.modals.email_viewer_clicks_hint') }}</span>
                                </div>
                                <div class="bv-ev-event-list" id="evClicksList"></div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="bv-ev-bottom-note d-none" id="evBottomNote">
                <i class="fas fa-circle-info"></i>
                {{ __('helpdesk::helpdesk.inbox.modals.email_viewer_bottom_note') }}
            </div>

        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/email-viewer.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/email-viewer.js')) }}" defer></script>
@endpush
@endonce

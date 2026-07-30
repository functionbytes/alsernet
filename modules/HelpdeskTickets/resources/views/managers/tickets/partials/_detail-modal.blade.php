{{-- Detalle lateral (slide-in) tipo Alvarez. El contenido se rellena desde JS al hacer click en una fila. --}}
<div class="htk-detail-overlay" id="htkd-overlay"></div>

<aside class="htk-detail" id="htkd" role="dialog" aria-label="Detalle del ticket">

    {{-- Hero --}}
    <div class="htk-d-hero">
        <div class="htk-d-hero-cover"></div>
        <div class="htk-d-hero-btns">
            <a id="htkd-detail-link" class="htk-btn htk-btn-icon" title="Vista completa" href="#">
                <i class="fas fa-arrow-up-right-from-square"></i>
            </a>
            <button type="button" class="htk-btn htk-btn-icon" id="htkd-close" title="Cerrar">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <div class="htk-d-hero-ico"><i class="fas fa-ticket"></i></div>
        <div class="htk-d-hero-num mono" id="htkd-id">—</div>
        <div class="htk-d-hero-subj" id="htkd-subj">—</div>
        <div class="htk-d-hero-pills">
            <span id="htkd-channel"></span>
            <span id="htkd-status"></span>
            <span id="htkd-priority"></span>
            <span id="htkd-sla"></span>
        </div>
        <div class="htk-d-hero-qa">
            <button class="htk-d-qa" type="button" data-action="assign"><i class="fas fa-user-plus"></i>Asignar</button>
            <button class="htk-d-qa" type="button" data-action="priority"><i class="fas fa-arrow-up"></i>Prioridad</button>
            <button class="htk-d-qa htk-d-qa-success" type="button" data-action="resolve"><i class="fas fa-check"></i>Resolver</button>
            <button class="htk-d-qa htk-d-qa-danger" type="button" data-action="close"><i class="fas fa-xmark-circle"></i>Cerrar</button>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="htk-d-tabs">
        <button type="button" class="htk-d-tab on" data-tab="conv">
            <i class="fas fa-comments"></i> Conversación
        </button>
        <button type="button" class="htk-d-tab" data-tab="activity">
            <i class="fas fa-bolt-lightning"></i> Actividad
        </button>
        <button type="button" class="htk-d-tab" data-tab="related">
            <i class="fas fa-link"></i> Relacionados
        </button>
        <button type="button" class="htk-d-tab" data-tab="files">
            <i class="fas fa-paperclip"></i> Archivos
        </button>
    </div>

    {{-- Body --}}
    <div class="htk-d-body">
        <div class="htk-d-main">
            <div data-htkd-pane="conv" id="htkd-conv"></div>
            <div data-htkd-pane="activity" class="d-none">
                <div class="htk-act" id="htkd-activity"></div>
            </div>
            <div data-htkd-pane="related" class="d-none">
                <div class="htk-empty-state">
                    <div class="ic"><i class="fas fa-link"></i></div>
                    <div class="t">Tickets relacionados</div>
                    <div class="s">Próximamente: enlazar tickets duplicados, bloqueantes o relacionados.</div>
                </div>
            </div>
            <div data-htkd-pane="files" class="d-none">
                <div class="htk-empty-state">
                    <div class="ic"><i class="fas fa-paperclip"></i></div>
                    <div class="t">Archivos adjuntos</div>
                    <div class="s">Los archivos compartidos en la conversación aparecerán aquí.</div>
                </div>
            </div>
        </div>

        {{-- Side panel --}}
        <div class="htk-d-side">
            <div class="htk-d-section">
                <div class="htk-d-section-hd"><span class="htk-d-section-lbl">Cliente</span></div>
                <div class="htk-d-side-row" id="htkd-cust"></div>
            </div>
            <div class="htk-d-section">
                <div class="htk-d-section-hd"><span class="htk-d-section-lbl">Asignado a</span></div>
                <div class="htk-d-side-row" id="htkd-assignee"></div>
            </div>
            <div class="htk-d-section">
                <div class="htk-d-section-hd"><span class="htk-d-section-lbl">Etiquetas</span></div>
                <div id="htkd-tags" class="htk-d-tags"></div>
            </div>
            <div class="htk-d-section">
                <div class="htk-d-section-hd"><span class="htk-d-section-lbl">Detalles</span></div>
                <div id="htkd-meta"></div>
            </div>
        </div>
    </div>

    {{-- Reply box --}}
    <div class="htk-reply" id="htkd-reply-box">
        <div class="htk-reply-tabs">
            <button type="button" class="htk-reply-tab on" data-reply-tab="reply">Responder</button>
            <button type="button" class="htk-reply-tab" data-reply-tab="note"><i class="fas fa-lock"></i> Nota interna</button>
            <button type="button" class="htk-reply-tab" data-reply-tab="macro"><i class="fas fa-bolt"></i> Macro</button>
        </div>

        {{-- Textarea pane --}}
        <div id="htkd-text-pane">
            <textarea id="htkd-reply-text"
                      placeholder="Escribe tu respuesta… usa / para macros, @ para mencionar"></textarea>
            <div class="htk-reply-bar">
                <button type="button" class="ic" title="Adjuntar"><i class="fas fa-paperclip"></i></button>
                <button type="button" class="ic" title="Emoji"><i class="far fa-face-smile"></i></button>
                <div class="ml">
                    <span class="htk-reply-hint">⌘ + ↵ enviar</span>
                    <button type="button" id="htkd-reply-send" class="htk-btn htk-btn-primary htk-btn-sm">
                        <i class="fas fa-paper-plane"></i> Enviar
                    </button>
                </div>
            </div>
        </div>

        {{-- Macro pane --}}
        <div id="htkd-macro-pane" class="d-none">
            <div class="htk-reply-search-wrap">
                <input type="text" id="htkd-macro-search" placeholder="Buscar macro…" autocomplete="off">
            </div>
            <div id="htkd-macro-list" class="htk-macros-list">
                <div class="htk-macros-loading"><i class="fas fa-spinner fa-spin"></i> Cargando…</div>
            </div>
        </div>
    </div>


</aside>

{{-- ══════════ MODALES DE ACCIÓN (fuera del aside — z-index limpio) ══════════ --}}

{{-- Modal: Asignar agente --}}
<div class="bv-modal" data-bv-modal-name="htk-assign">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head">
            <div>
                <span class="bv-modal-label">Ticket · Acción</span>
                <div class="bv-modal-title"><i class="fas fa-user-plus me-2 text-secondary"></i>Asignar agente</div>
            </div>
            <button type="button" class="bv-modal-close" data-htk-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">
            <div class="bv-modal-search">
                <i class="fas fa-magnifying-glass"></i>
                <input id="htk-assign-search" type="text" placeholder="Buscar agente…" autocomplete="off">
            </div>
            <div class="bv-opt-list" id="htk-assign-list">
                {{-- Populated by JS --}}
            </div>
        </div>
        <div class="bv-modal-foot">
            <button type="button" class="btn-primary" id="htk-assign-apply">Asignar</button>
            <button type="button" class="btn-secondary" data-htk-close>Cancelar</button>
        </div>
    </div>
</div>

{{-- Modal: Cambiar prioridad --}}
<div class="bv-modal" data-bv-modal-name="htk-priority">
    <div class="bv-modal-dialog sm">
        <div class="bv-modal-head">
            <div>
                <span class="bv-modal-label">Ticket · Acción</span>
                <div class="bv-modal-title"><i class="fas fa-flag me-2 text-secondary"></i>Cambiar prioridad</div>
            </div>
            <button type="button" class="bv-modal-close" data-htk-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">
            <div class="bv-r-prio-group">
                <button type="button" class="bv-r-prio-btn low"    data-prio="low">    <span class="d"></span>Baja</button>
                <button type="button" class="bv-r-prio-btn normal" data-prio="normal"> <span class="d"></span>Normal</button>
                <button type="button" class="bv-r-prio-btn high"   data-prio="high">   <span class="d"></span>Alta</button>
                <button type="button" class="bv-r-prio-btn urgent" data-prio="urgent"> <span class="d"></span>Urgente</button>
            </div>
        </div>
        <div class="bv-modal-foot">
            <button type="button" class="btn-primary" id="htk-priority-apply">Guardar cambios</button>
            <button type="button" class="btn-secondary" data-htk-close>Cancelar</button>
        </div>
    </div>
</div>

{{-- Modal: Resolver ticket --}}
<div class="bv-modal" data-bv-modal-name="htk-resolve">
    <div class="bv-modal-dialog sm">
        <div class="bv-modal-head">
            <div>
                <span class="bv-modal-label">Ticket · Confirmación</span>
                <div class="bv-modal-title"><i class="fas fa-circle-check me-2 text-success"></i>Resolver ticket</div>
            </div>
            <button type="button" class="bv-modal-close" data-htk-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body text-center">
            <div class="bv-confirm-icon-wrap">
                <div class="bv-confirm-icon success"><i class="fas fa-circle-check"></i></div>
            </div>
            <p class="small text-body-secondary mt-3 mb-0 lh-base">
                ¿Marcar este ticket como <strong>resuelto</strong>?<br>
                <span class="small text-secondary">El cliente recibirá una notificación de cierre.</span>
            </p>
        </div>
        <div class="bv-modal-foot">
            <button type="button" class="btn-success" id="htk-resolve-apply"><i class="fas fa-check"></i>Resolver</button>
            <button type="button" class="btn-secondary" data-htk-close>Cancelar</button>
        </div>
    </div>
</div>

{{-- Modal: Cerrar ticket --}}
<div class="bv-modal" data-bv-modal-name="htk-close-ticket">
    <div class="bv-modal-dialog sm">
        <div class="bv-modal-head">
            <div>
                <span class="bv-modal-label">Ticket · Confirmación</span>
                <div class="bv-modal-title"><i class="fas fa-circle-xmark me-2 text-secondary"></i>Cerrar ticket</div>
            </div>
            <button type="button" class="bv-modal-close" data-htk-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body text-center">
            <div class="bv-confirm-icon-wrap">
                <div class="bv-confirm-icon warning"><i class="fas fa-circle-xmark"></i></div>
            </div>
            <p class="small text-body-secondary mt-3 mb-0 lh-base">
                ¿Cerrar este ticket?<br>
                <span class="small text-secondary">Podrás reabrirlo en cualquier momento.</span>
            </p>
        </div>
        <div class="bv-modal-foot">
            <button type="button" class="btn-primary" id="htk-close-apply">Cerrar ticket</button>
            <button type="button" class="btn-secondary" data-htk-close>Cancelar</button>
        </div>
    </div>
</div>

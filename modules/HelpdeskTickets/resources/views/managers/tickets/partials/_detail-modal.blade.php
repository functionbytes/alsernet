{{-- Detalle lateral (slide-in) tipo Alvarez. El contenido se rellena desde JS al hacer click en una fila. --}}
<div class="htk-detail-overlay" id="htkd-overlay"></div>

<aside class="htk-detail" id="htkd" role="dialog" aria-label="Detalle del ticket">

    {{-- Cabecera --}}
    <div class="htk-d-head">
        <div class="ico"><i class="fas fa-ticket"></i></div>
        <div class="body">
            <div class="row1">
                <span id="htkd-id" class="mono">—</span>
                <span class="sep">·</span>
                <span id="htkd-channel"></span>
                <span class="sep">·</span>
                <span id="htkd-status"></span>
            </div>
            <div class="ts" id="htkd-subj">—</div>
            <div class="row3">
                <span id="htkd-priority"></span>
                <span id="htkd-sla"></span>
            </div>
        </div>
        <div class="acts">
            <a id="htkd-detail-link" class="htk-btn htk-btn-icon" title="Abrir vista completa" href="#">
                <i class="fas fa-arrow-up-right-from-square"></i>
            </a>
            <button type="button" class="htk-btn htk-btn-icon" id="htkd-close" title="Cerrar">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="htk-d-tabs">
        <button type="button" class="htk-d-tab on" data-tab="conv">
            <i class="fas fa-comments"></i> Conversación
        </button>
        <button type="button" class="htk-d-tab" data-tab="activity">
            <i class="fas fa-timeline"></i> Actividad
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
            <div data-htkd-pane="activity" style="display:none">
                <div class="htk-act" id="htkd-activity"></div>
            </div>
            <div data-htkd-pane="related" style="display:none">
                <div class="htk-empty-state">
                    <div class="ic"><i class="fas fa-link"></i></div>
                    <div class="t">Tickets relacionados</div>
                    <div class="s">Próximamente: enlazar tickets duplicados, bloqueantes o relacionados.</div>
                </div>
            </div>
            <div data-htkd-pane="files" style="display:none">
                <div class="htk-empty-state">
                    <div class="ic"><i class="fas fa-paperclip"></i></div>
                    <div class="t">Archivos adjuntos</div>
                    <div class="s">Los archivos compartidos en la conversación aparecerán aquí.</div>
                </div>
            </div>
        </div>

        {{-- Side panel --}}
        <div class="htk-d-side">
            <div class="htk-d-side-block">
                <div class="lbl">Cliente</div>
                <div class="htk-d-side-row" id="htkd-cust"></div>
            </div>
            <div class="htk-d-side-block">
                <div class="lbl">Asignado a</div>
                <div class="htk-d-side-row" id="htkd-assignee"></div>
            </div>
            <div class="htk-d-side-block">
                <div class="lbl">Etiquetas</div>
                <div id="htkd-tags" style="display:flex;flex-wrap:wrap;gap:4px"></div>
            </div>
            <div class="htk-d-side-block">
                <div class="lbl">Detalles</div>
                <div id="htkd-meta"></div>
            </div>
            <div class="htk-d-side-block">
                <div class="lbl">Acciones</div>
                <button type="button" class="htk-btn htk-btn-outline htk-btn-sm" style="justify-content:flex-start;width:100%">
                    <i class="fas fa-user-plus"></i> Reasignar
                </button>
                <button type="button" class="htk-btn htk-btn-outline htk-btn-sm" style="justify-content:flex-start;width:100%">
                    <i class="fas fa-arrow-up"></i> Cambiar prioridad
                </button>
                <button type="button" class="htk-btn htk-btn-success htk-btn-sm" style="justify-content:flex-start;width:100%">
                    <i class="fas fa-check"></i> Marcar resuelto
                </button>
            </div>
        </div>
    </div>

    {{-- Reply box --}}
    <div class="htk-reply">
        <div class="htk-reply-tabs">
            <button type="button" class="htk-reply-tab on" data-reply-tab="reply">Responder</button>
            <button type="button" class="htk-reply-tab" data-reply-tab="note">Nota interna</button>
            <button type="button" class="htk-reply-tab" data-reply-tab="macro">Macro</button>
        </div>
        <textarea id="htkd-reply-text"
                  placeholder="Escribe tu respuesta… usa / para macros, @ para mencionar"></textarea>
        <div class="htk-reply-bar">
            <button type="button" class="ic" title="Adjuntar"><i class="fas fa-paperclip"></i></button>
            <button type="button" class="ic" title="Emoji"><i class="far fa-face-smile"></i></button>
            <button type="button" class="ic" title="Plantilla"><i class="fas fa-bolt"></i></button>
            <div class="ml">
                <span style="font-size:10.5px;color:#a1a1aa;margin-right:4px">⌘ + ↵ enviar</span>
                <button type="button" class="htk-btn htk-btn-outline htk-btn-sm">Guardar borrador</button>
                <button type="button" id="htkd-reply-send" class="htk-btn htk-btn-primary htk-btn-sm">
                    <i class="fas fa-paper-plane"></i> Enviar
                </button>
            </div>
        </div>
    </div>
</aside>

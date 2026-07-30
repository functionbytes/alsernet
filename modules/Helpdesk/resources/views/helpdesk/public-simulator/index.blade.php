<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Simulador de canales · Helpdesk</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.css" rel="stylesheet">

    <style>
        :root { --sim-primary: #90bb13; }
        body { background: #f4f5f7; }
        .sim-wrap { max-width: 760px; }
        .sim-card { border: none; border-radius: 1rem; box-shadow: 0 10px 30px rgba(0,0,0,.06); }
        .sim-brand { color: var(--sim-primary); }
        .sim-channel-btn.active { background: var(--sim-primary); border-color: var(--sim-primary); color: #fff; }
        .sim-channel-btn i { font-size: 1.25rem; display: block; margin-bottom: .25rem; }
        .sim-thread { height: 52vh; overflow-y: auto; background: #eef0f3; border-radius: .75rem; padding: 1rem; }
        .sim-msg { max-width: 78%; margin-bottom: .65rem; padding: .55rem .8rem; border-radius: 1rem; clear: both; word-wrap: break-word; }
        .sim-msg-who { font-size: .68rem; opacity: .7; margin-bottom: .15rem; }
        .sim-msg-body { white-space: pre-wrap; }
        .sim-msg-time { font-size: .65rem; opacity: .55; margin-top: .15rem; }
        .sim-msg-in { float: left; background: #fff; border-bottom-left-radius: .25rem; }
        .sim-msg-out { float: right; background: var(--sim-primary); color: #fff; border-bottom-right-radius: .25rem; }
        .sim-msg-out .sim-msg-who { color: #fff; }
        .sim-conn-dot { width: 9px; height: 9px; border-radius: 50%; display: inline-block; background: #adb5bd; }
        .sim-conn-dot.live { background: #13C672; }
        .sim-btn-primary { background: var(--sim-primary); border-color: var(--sim-primary); color: #fff; }
        .sim-btn-primary:hover, .sim-btn-primary:focus { background: #7da010; border-color: #7da010; color: #fff; }

        /* Typing indicator */
        .sim-typing { float: left; clear: both; padding: .4rem .8rem; background: #fff; border-radius: 1rem; border-bottom-left-radius: .25rem; margin-bottom: .5rem; color: #6c757d; font-size: .8rem; }
        .sim-typing span { display: inline-block; width: 6px; height: 6px; background: #adb5bd; border-radius: 50%; margin: 0 1px; animation: simDot 1.2s infinite; }
        .sim-typing span:nth-child(2) { animation-delay: .2s; }
        .sim-typing span:nth-child(3) { animation-delay: .4s; }
        @keyframes simDot { 0%,60%,100% { transform: translateY(0); } 30% { transform: translateY(-5px); } }

        /* System messages */
        .sim-system-msg { text-align: center; margin: .5rem 0; color: #adb5bd; font-size: .75rem; clear: both; display: block; }
        .sim-system-msg::before, .sim-system-msg::after { content: ''; display: inline-block; vertical-align: middle; width: 30px; height: 1px; background: #dee2e6; margin: 0 .5rem; }

        /* Resolved banner */
        .sim-resolved-banner { background: #f0fff4; border: 1px solid #13C672; border-radius: .5rem; padding: .75rem 1rem; margin-bottom: .5rem; }

        /* Lookup results */
        .sim-lookup-wrap { position: relative; }
        .sim-lookup-results { background: #fff; border: 1px solid #dee2e6; border-radius: .5rem; box-shadow: 0 4px 12px rgba(0,0,0,.08); position: absolute; z-index: 100; width: 100%; max-height: 220px; overflow-y: auto; top: 100%; left: 0; }
        .sim-lookup-item { padding: .5rem .75rem; cursor: pointer; border-bottom: 1px solid #f0f0f0; }
        .sim-lookup-item:hover { background: #f8f9fa; }
        .sim-lookup-item:last-child { border-bottom: none; }
        .sim-lookup-name { font-weight: 600; font-size: .875rem; }
        .sim-lookup-detail { font-size: .75rem; color: #6c757d; }
        .sim-badge-linked { font-size: .7rem; color: #13C672; }

        /* Rich media attachments */
        .sim-att-img { max-width: 220px; border-radius: .5rem; cursor: zoom-in; display: block; }
        .sim-att-audio { width: 220px; }
        .sim-att-video { max-width: 250px; border-radius: .5rem; display: block; }
        .sim-att-file { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: .5rem; padding: .5rem .75rem; color: #495057; font-size: .85rem; }
        .sim-att-file:hover { background: #e9ecef; }
        .sim-link-preview { border: 1px solid #dee2e6; border-radius: .5rem; overflow: hidden; margin-top: .25rem; max-width: 240px; }
        .sim-lp-img { width: 100%; height: 120px; object-fit: cover; }
        .sim-lp-title { font-weight: 600; font-size: .8rem; padding: .4rem .5rem .1rem; }
        .sim-lp-desc { font-size: .72rem; color: #6c757d; padding: 0 .5rem .25rem; }
        .sim-lp-url { font-size: .7rem; color: #90bb13; padding: 0 .5rem .4rem; display: block; }

        /* Footer action buttons */
        .sim-btn-outline { border: 1px solid #dee2e6; background: #fff; color: #6c757d; border-radius: .375rem; padding: .375rem .6rem; cursor: pointer; }
        .sim-btn-outline:hover { background: #f8f9fa; }

        /* CSAT widget */
        .sim-csat-widget { background: #f8fff0; border-color: #90bb13 !important; }
        .sim-star-btn.active { background: #ffc107; border-color: #ffc107; color: #000; }

        /* Textarea auto-resize */
        #sim-message { line-height: 1.5; transition: height .1s; }

        /* Grouped messages */
        .sim-msg-grouped .sim-msg-who { display: none; }
        .sim-msg-grouped { margin-top: .2rem; }
        .sim-date-sep { text-align: center; margin: .75rem 0 .4rem; color: #adb5bd; font-size: .72rem; clear: both; display: block; }
        .sim-date-sep span { background: #eef0f3; padding: 0 .6rem; }
        .sim-date-sep::before, .sim-date-sep::after { content: ''; display: inline-block; vertical-align: middle; width: 40px; height: 1px; background: #dee2e6; margin: 0 .3rem; }

        /* Internal notes */
        .sim-msg-note { background: #fff8e1 !important; border: 1px dashed #ffc107; color: #5d4037; }
        .sim-msg-note .sim-msg-who { color: #5d4037; }
        .sim-msg-note::before { content: '📝 Nota interna'; display: block; font-size: .65rem; font-weight: 700; color: #f9a825; margin-bottom: .2rem; letter-spacing: .04em; }

        /* Quick replies */
        .sim-quick-replies { margin-top: .5rem; display: flex; flex-wrap: wrap; gap: .35rem; }
        .sim-qr-btn { border: 1px solid #90bb13; color: #90bb13; background: #fff; border-radius: 1rem; padding: .25rem .75rem; font-size: .8rem; cursor: pointer; transition: all .15s; }
        .sim-qr-btn:hover { background: #90bb13; color: #fff; }

        /* Drag & drop */
        #sim-dropzone { pointer-events: none; }
        #sim-chat.drag-over #sim-dropzone { display: flex !important; }

        /* Session count */
        .sim-session-count { font-size: .65rem; color: #adb5bd; margin-top: .1rem; }

        /* Sessions panel */
        .sim-sessions-panel { margin-bottom: 1.25rem; }
        .sim-sessions-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: .75rem; }
        .sim-session-card { position: relative; background: #fff; border: 1px solid #dee2e6; border-radius: .75rem; padding: .75rem 1rem; cursor: pointer; transition: border-color .15s, box-shadow .15s; }
        .sim-session-card:hover { border-color: #90bb13; box-shadow: 0 2px 8px rgba(144,187,19,.15); }
        .sim-session-card.active { border-color: #90bb13; background: #f8fff0; }
        .sim-session-ch { font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: #6c757d; margin-bottom: .15rem; }
        .sim-session-name { font-weight: 600; font-size: .85rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sim-session-preview { font-size: .75rem; color: #6c757d; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: .1rem; }
        .sim-session-time { font-size: .68rem; color: #adb5bd; margin-top: .3rem; }
        .sim-session-status { display: inline-block; width: 7px; height: 7px; border-radius: 50%; margin-right: .3rem; background: #13C672; }
        .sim-session-status.closed { background: #adb5bd; }
        .sim-sessions-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: .75rem; }
        .sim-sessions-toggle { font-size: .8rem; color: #6c757d; cursor: pointer; background: none; border: none; padding: 0; }
        .sim-sessions-empty { text-align: center; color: #adb5bd; font-size: .85rem; padding: .75rem; }
        .sim-session-del { position: absolute; top: .35rem; right: .4rem; background: none; border: none; color: #ced4da; font-size: .85rem; line-height: 1; cursor: pointer; padding: 0 .15rem; border-radius: .2rem; }
        .sim-session-del:hover { color: #6c757d; background: #f0f0f0; }

        /* Emoji picker */
        .sim-emoji-picker { position: absolute; bottom: 100%; left: 0; margin-bottom: .35rem; background: #fff; border: 1px solid #dee2e6; border-radius: .75rem; box-shadow: 0 8px 24px rgba(0,0,0,.12); padding: .5rem; width: 280px; z-index: 200; }
        .sim-emoji-grid { display: grid; grid-template-columns: repeat(8, 1fr); gap: 1px; max-height: 200px; overflow-y: auto; }
        .sim-emoji-btn-item { font-size: 1.2rem; cursor: pointer; border: none; background: none; padding: .2rem; border-radius: .35rem; line-height: 1; text-align: center; transition: background .1s; }
        .sim-emoji-btn-item:hover { background: #f0f0f0; }
        .sim-emoji-search { width: 100%; font-size: .8rem; border: 1px solid #dee2e6; border-radius: .375rem; padding: .3rem .5rem; margin-bottom: .4rem; outline: none; }
        .sim-emoji-search:focus { border-color: #90bb13; }

        /* ── Estilos por canal ── */

        /* WhatsApp */
        .sim-channel-whatsapp .sim-thread { background: #e5ddd5; }
        .sim-channel-whatsapp .sim-msg-out { background: #dcf8c6; color: #111; }
        .sim-channel-whatsapp .sim-msg-out .sim-msg-who { color: #075e54; opacity: 1; }
        .sim-channel-whatsapp .sim-msg-out .sim-msg-time { color: #666; }
        .sim-channel-whatsapp #sim-send-btn { background: #25d366; border-color: #25d366; }
        .sim-channel-whatsapp #sim-send-btn:hover { background: #1da851; border-color: #1da851; }

        /* Facebook Messenger */
        .sim-channel-facebook .sim-thread { background: #f0f2f5; }
        .sim-channel-facebook .sim-msg-out { background: #0084ff; color: #fff; }
        .sim-channel-facebook .sim-msg-out .sim-msg-who { color: rgba(255,255,255,.85); opacity: 1; }
        .sim-channel-facebook .sim-msg-out .sim-msg-time { color: rgba(255,255,255,.7); opacity: 1; }
        .sim-channel-facebook #sim-send-btn { background: #0084ff; border-color: #0084ff; }
        .sim-channel-facebook #sim-send-btn:hover { background: #006fd6; border-color: #006fd6; }

        /* Instagram */
        .sim-channel-instagram .sim-thread { background: #fafafa; }
        .sim-channel-instagram .sim-msg-out { background: linear-gradient(135deg, #833ab4 0%, #fd1d1d 50%, #fcb045 100%); color: #fff; border-bottom-right-radius: .25rem; }
        .sim-channel-instagram .sim-msg-out .sim-msg-who { color: rgba(255,255,255,.9); opacity: 1; }
        .sim-channel-instagram .sim-msg-out .sim-msg-time { color: rgba(255,255,255,.75); opacity: 1; }
        .sim-channel-instagram #sim-send-btn { background: linear-gradient(135deg, #833ab4, #fd1d1d); border: none; }
        .sim-channel-instagram #sim-send-btn:hover { background: linear-gradient(135deg, #6a2f94, #d41818); border: none; }

        /* ── Cabecera por canal (identidad visual) ── */
        .sim-channel-whatsapp .card-header { border-bottom: 3px solid #25d366; }
        .sim-channel-facebook .card-header { border-bottom: 3px solid #0084ff; }
        .sim-channel-instagram .card-header { border-bottom: 3px solid #fd1d1d; }
        .sim-channel-web .card-header { border-bottom: 3px solid #90bb13; }
        .sim-channel-whatsapp #sim-channel-badge { background: #25d366; color: #fff; border-color: #25d366 !important; }
        .sim-channel-facebook #sim-channel-badge { background: #0084ff; color: #fff; border-color: #0084ff !important; }
        .sim-channel-instagram #sim-channel-badge { background: linear-gradient(135deg,#833ab4,#fd1d1d,#fcb045); color: #fff; border: none; }
        .sim-channel-web #sim-channel-badge { background: #90bb13; color: #fff; border-color: #90bb13 !important; }

        /* ── Carrusel de tarjetas (productos) ── */
        .sim-cards { display: flex; gap: .5rem; overflow-x: auto; padding: .25rem 0; margin-top: .4rem; }
        .sim-pcard { flex: 0 0 200px; max-width: 200px; border: 1px solid #dee2e6; border-radius: .6rem; overflow: hidden; background: #fff; }
        .sim-pcard img { width: 100%; height: 110px; object-fit: cover; display: block; background: #f1f3f5; }
        .sim-pcard-body { padding: .5rem .6rem; }
        .sim-pcard-title { font-weight: 600; font-size: .85rem; color: #212529; }
        .sim-pcard-sub { font-size: .8rem; color: #6c757d; }
        .sim-pcard-btn { display: block; text-align: center; margin-top: .4rem; font-size: .8rem; padding: .25rem; border-radius: .4rem; background: #f1f3f5; color: #212529; text-decoration: none; }
        .sim-pcard-btn:hover { background: #e9ecef; }
        /* WhatsApp e Instagram apilan las tarjetas (imágenes), Messenger/web las muestran en carrusel horizontal */
        .sim-channel-whatsapp .sim-cards, .sim-channel-instagram .sim-cards { flex-direction: column; }
        .sim-channel-whatsapp .sim-pcard, .sim-channel-instagram .sim-pcard { flex: 0 0 auto; max-width: 100%; }

        /* ── Aviso de límite del canal ── */
        .sim-limit-note { font-size: .72rem; color: #997404; background: #fff8e1; border: 1px dashed #ffc107; border-radius: .4rem; padding: .3rem .5rem; margin-top: .35rem; }

        /* ── Recibo de lectura (✓✓) ── */
        .sim-msg-receipt { font-size: .7rem; color: #adb5bd; margin-top: .15rem; text-align: right; }
        .sim-channel-whatsapp .sim-msg-out .sim-msg-receipt { color: #34b7f1; }

        /* ── Reloj simulado activo ── */
        .sim-clock-badge { font-size: .72rem; color: #6c757d; }

        /* Responsive móvil */
        @media (max-width: 576px) {
            .sim-wrap { padding: .5rem !important; }
            .sim-sessions-grid { grid-template-columns: 1fr 1fr; }
            .sim-thread { height: 45vh; }
            #sim-sessions-search { width: 110px !important; }
            .sim-msg { max-width: 92%; }
        }
        @media (max-width: 380px) {
            .sim-sessions-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="container sim-wrap py-4">

    <div class="text-center mb-4">
        <h4 class="mb-1"><i class="fas fa-vials sim-brand"></i> Simulador de canales</h4>
        <p class="text-muted mb-0">Simula un mensaje entrante por cualquier canal y conversa en tiempo real con el agente.</p>
    </div>

    {{-- ── Panel sesiones recientes ── --}}
    <div id="sim-sessions-panel" class="sim-sessions-panel d-none">
        <div class="sim-sessions-header">
            <span class="text-muted small fw-semibold"><i class="fas fa-history me-1"></i>Sesiones recientes</span>
            <div class="d-flex align-items-center gap-2">
                <input type="text" id="sim-sessions-search" class="form-control form-control-sm" placeholder="Buscar…" style="width:140px">
                <button class="sim-sessions-toggle" id="sim-sessions-toggle-btn">Ocultar</button>
            </div>
        </div>
        <div id="sim-sessions-grid" class="sim-sessions-grid"></div>
        <div class="text-center mt-2 d-none" id="sim-sessions-more">
            <button class="btn btn-sm btn-link text-muted" id="sim-sessions-show-all">Ver todas</button>
        </div>
    </div>

    {{-- ── Paso 1: configurar la sesión ── --}}
    <div id="sim-setup" class="card sim-card">
        <div class="card-body p-4">
            <form id="sim-start-form">
                <label class="form-label fw-semibold">Canal</label>
                <div class="row g-2 mb-3" role="group" aria-label="Canal">
                    <div class="col"><button type="button" class="btn btn-outline-secondary w-100 sim-channel-btn active" data-channel="whatsapp"><i class="fab fa-whatsapp"></i>WhatsApp</button></div>
                    <div class="col"><button type="button" class="btn btn-outline-secondary w-100 sim-channel-btn" data-channel="facebook"><i class="fab fa-facebook-messenger"></i>Messenger</button></div>
                    <div class="col"><button type="button" class="btn btn-outline-secondary w-100 sim-channel-btn" data-channel="instagram"><i class="fab fa-instagram"></i>Instagram</button></div>
                    <div class="col"><button type="button" class="btn btn-outline-secondary w-100 sim-channel-btn" data-channel="web"><i class="fas fa-globe"></i>Website</button></div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="sim-name">Nombre del cliente</label>
                        <input type="text" class="form-control" id="sim-name" maxlength="120" placeholder="Ej: María López">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" id="sim-identifier-label" for="sim-identifier">Identificador</label>
                        <div class="sim-lookup-wrap">
                            <input type="text" class="form-control" id="sim-identifier" maxlength="191" autocomplete="off">
                            <div id="sim-lookup-identifier" class="sim-lookup-results d-none"></div>
                        </div>
                        <div id="sim-linked-badge" class="sim-badge-linked mt-1 d-none">
                            <i class="fas fa-check-circle"></i> Vinculado desde PrestaShop
                        </div>
                    </div>
                </div>

                <details class="mt-3 sim-links" id="sim-links-details">
                    <summary class="text-muted small fw-semibold">
                        <i class="fas fa-link me-1"></i> Vincular cliente (e-commerce) · opcional
                    </summary>
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label" for="sim-phone">Celular</label>
                            <input type="text" class="form-control" id="sim-phone" maxlength="30" placeholder="Ej: 34600111222">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="sim-email">Email</label>
                            <div class="sim-lookup-wrap">
                                <input type="email" class="form-control" id="sim-email" maxlength="191" placeholder="cliente@correo.com" autocomplete="off">
                                <div id="sim-lookup-email" class="sim-lookup-results d-none"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="sim-ps-id">ID PrestaShop</label>
                            <input type="number" min="1" class="form-control" id="sim-ps-id" placeholder="Ej: 10452">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="sim-gestion-id">ID gestión (ERP)</label>
                            <input type="number" min="1" class="form-control" id="sim-gestion-id" placeholder="Ej: 88123">
                        </div>
                    </div>
                    <p class="text-muted small mt-2 mb-0">
                        Vincula la conversación con PrestaShop y gestión para que el agente vea los pedidos y el contexto del cliente.
                    </p>
                </details>

                <div class="mt-3">
                    <label class="form-label" for="sim-first-message">Primer mensaje</label>
                    <textarea class="form-control" id="sim-first-message" rows="3" maxlength="2000" placeholder="Hola, tengo una consulta sobre mi pedido…"></textarea>
                </div>

                <div class="mt-3">
                    <label class="form-label" for="sim-simulated-datetime">
                        <i class="fas fa-clock me-1 text-muted"></i>Hora simulada <span class="text-muted small">(opcional · hora del servidor)</span>
                    </label>
                    <input type="datetime-local" class="form-control" id="sim-simulated-datetime">
                    <div class="form-text">Para probar el nodo «horario de atención»: fija una fecha/hora y el bot responderá la rama dentro/fuera de horario.</div>
                </div>

                <button type="submit" class="btn btn-lg w-100 mt-4 sim-btn-primary" id="sim-start-btn">
                    <i class="fas fa-paper-plane me-1"></i> Iniciar conversación
                </button>
            </form>
        </div>
    </div>

    {{-- ── Paso 2: conversación en vivo ── --}}
    <div id="sim-chat" class="card sim-card d-none">
        <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
            <div>
                <span class="badge rounded-pill text-bg-light border" id="sim-channel-badge"><i class="fas fa-circle-nodes me-1"></i></span>
                <span class="text-muted small ms-1" id="sim-identity"></span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="sim-conn-dot" id="sim-conn" title="Estado de conexión en tiempo real"></span>
                <button class="btn btn-sm btn-outline-secondary" id="sim-share-btn" title="Copiar enlace"><i class="fas fa-share-nodes"></i></button>
                <button class="btn btn-sm btn-outline-secondary" id="sim-export-btn" title="Exportar conversación"><i class="fas fa-download"></i></button>
                <button class="btn btn-sm btn-outline-warning" id="sim-dev-btn" title="Herramientas de desarrollo"><i class="fas fa-flask"></i></button>
                <button class="btn btn-sm btn-outline-secondary" id="sim-reset"><i class="fas fa-rotate-left me-1"></i>Nueva</button>
            </div>
        </div>
        <div class="card-body" style="position:relative">
            <div id="sim-dropzone" class="d-none" style="position:absolute;inset:0;background:rgba(144,187,19,.12);border:2px dashed #90bb13;border-radius:.75rem;z-index:20;display:none;align-items:center;justify-content:center;font-size:1.1rem;color:#90bb13;font-weight:600">
                <div><i class="fas fa-cloud-upload-alt fa-2x mb-2 d-block text-center"></i>Suelta el archivo aquí</div>
            </div>
            <div id="sim-scroll-badge" class="d-none" style="position:absolute;bottom:80px;left:50%;transform:translateX(-50%);z-index:10">
                <button type="button" class="btn btn-sm btn-dark opacity-90 rounded-pill px-3" id="sim-scroll-btn">
                    <i class="fas fa-arrow-down me-1"></i><span id="sim-scroll-count">0</span> nuevos
                </button>
            </div>
            <div class="sim-thread" id="sim-thread">
                {{-- Typing indicator lives inside the thread --}}
                <div id="sim-typing-indicator" class="sim-typing d-none">
                    Agente escribiendo<span></span><span></span><span></span>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white">
            <div id="sim-inject-panel" class="d-none mb-2 p-2 rounded" style="background:#fffbf0;border:1px dashed #ffc107">
                <div class="d-flex align-items-center gap-1 mb-1">
                    <i class="fas fa-flask text-warning" style="font-size:.75rem"></i>
                    <span class="text-warning fw-semibold" style="font-size:.75rem">Simular respuesta del agente</span>
                </div>
                <div class="d-flex gap-2">
                    <input type="text" class="form-control form-control-sm" id="sim-inject-msg" placeholder="Escribe la respuesta del agente…" maxlength="2000">
                    <button type="button" class="btn btn-sm btn-warning" id="sim-inject-btn" style="white-space:nowrap">Inyectar</button>
                </div>
            </div>
            <div id="sim-resolved-banner" class="sim-resolved-banner d-none">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <span class="text-success small"><i class="fas fa-check-circle me-1"></i>Esta conversación fue resuelta. ¿Necesitas ayuda adicional?</span>
                    <button type="button" class="btn btn-sm btn-outline-success" id="sim-new-conv-btn">Nueva conversación</button>
                </div>
            </div>
            <form id="sim-send-form" class="d-flex gap-2" style="position:relative">
                <input type="file" id="sim-file-input" accept="image/*,video/*,audio/*,.pdf,.doc,.docx" class="d-none">
                <label for="sim-file-input" class="btn sim-btn-outline mb-0" title="Adjuntar archivo" id="sim-file-btn">
                    <i class="fas fa-paperclip"></i>
                </label>
                <button type="button" class="btn sim-btn-outline mb-0" id="sim-audio-btn" title="Grabar nota de voz">
                    <i class="fas fa-microphone" id="sim-audio-icon"></i>
                </button>
                <button type="button" class="btn sim-btn-outline mb-0" id="sim-emoji-btn" title="Emojis" style="font-size:1rem">😊</button>
                <div id="sim-emoji-picker" class="sim-emoji-picker d-none">
                    <input type="text" class="sim-emoji-search" id="sim-emoji-search" placeholder="Buscar emoji…" autocomplete="off">
                    <div class="sim-emoji-grid" id="sim-emoji-grid"></div>
                </div>
                <textarea class="form-control" id="sim-message" maxlength="2000" placeholder="Escribe un mensaje como cliente…" autocomplete="off" rows="1" style="resize:none;overflow:hidden;max-height:120px"></textarea>
                <button type="submit" class="btn sim-btn-primary" id="sim-send-btn"><i class="fas fa-paper-plane"></i></button>
            </form>
        </div>
    </div>

    <p class="text-center text-muted small mt-3">
        <i class="fas fa-circle-info me-1"></i>
        Las conversaciones llegan al inbox real del agente. Las respuestas del agente no se envían a las APIs reales (modo simulación).
    </p>
</div>

<script>
    window.SIM = {
        reverb:     @json($reverb),
        base:       @json(route('helpdesk.sim.index', [], false)),
        startUrl:   @json(route('helpdesk.sim.start', [], false)),
        csrf:       document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        lookupUrl:  @json(route('helpdesk.sim.lookup', [], false)),
    };
</script>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.js"></script>
<script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>

<script>
(function () {
    const S = window.SIM;

    let conv = null;                // { id, token, channel }
    let selectedChannel = 'whatsapp';
    let savedSession = null;        // { channel, name, identifier } for pre-fill on "Nueva"
    const seen = new Set();         // rendered message ids (dedupe)
    let lastId = 0;
    let echo = null;
    let pollTimer = null;
    let typingTimer = null;
    let conversationResolved = false;
    let unreadCount = 0;
    const originalTitle = document.title;

    // Auto-scroll state
    let pendingScrollCount = 0;
    let userScrolledUp     = false;

    // Message grouping state
    let lastRenderFrom = null;
    let lastRenderDate = null;

    // Reconnect state
    let reconnectTimer = null;
    let reconnectDelay = 3000;

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': S.csrf } });
    toastr.options = { positionClass: 'toast-bottom-right', timeOut: 4000 };

    // Auto-resize textarea
    $(document).on('input', '#sim-message', function () {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });

    // Shift+Enter = newline, Enter = send
    $(document).on('keydown', '#sim-message', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            $('#sim-send-form').trigger('submit');
        }
    });

    const LABELS = {
        whatsapp:  { name: 'WhatsApp',  icon: 'fab fa-whatsapp',           id: 'Teléfono (opcional)',             ph: 'Ej: 34600111222' },
        facebook:  { name: 'Messenger', icon: 'fab fa-facebook-messenger', id: 'PSID de Facebook (opcional)',     ph: 'Ej: PSID_123456' },
        instagram: { name: 'Instagram', icon: 'fab fa-instagram',          id: 'Usuario/ID Instagram (opcional)', ph: 'Ej: IG_123456' },
        web:       { name: 'Website',   icon: 'fas fa-globe',              id: 'Email (opcional)',                ph: 'Ej: cliente@correo.com' },
    };

    // ── Channel selector ──
    function updateIdentityField() {
        const l = LABELS[selectedChannel];
        $('#sim-identifier-label').text(l.id);
        $('#sim-identifier').attr('placeholder', l.ph);
    }
    updateIdentityField();

    $('.sim-channel-btn').on('click', function () {
        selectedChannel = $(this).data('channel');
        $('.sim-channel-btn').removeClass('active');
        $(this).addClass('active');
        updateIdentityField();
    });

    // ── Lookup helpers ──
    function buildLookupResults(items, $container, triggerFill) {
        $container.empty();
        if (!items || !items.length) { $container.addClass('d-none'); return; }

        items.slice(0, 5).forEach(function (item) {
            const details = [];
            if (item.prestashop_id) details.push('PrestaShop #' + item.prestashop_id);
            if (item.gestion_id)    details.push('ERP #' + item.gestion_id);
            if (item.ps_orders)     details.push(item.ps_orders + ' pedidos');

            const $item = $('<div class="sim-lookup-item">')
                .append($('<div class="sim-lookup-name">').text(
                    (item.name || '') + (item.email ? ' · ' + item.email : '')
                ))
                .append($('<div class="sim-lookup-detail">').text(details.join(' · ')));

            $item.on('click', function () { triggerFill(item); });
            $container.append($item);
        });
        $container.removeClass('d-none');
    }

    function fillFromLookup(item) {
        if (item.name)           $('#sim-name').val(item.name);
        if (item.email)          $('#sim-email').val(item.email);
        if (item.prestashop_id)  $('#sim-ps-id').val(item.prestashop_id);
        if (item.gestion_id)     $('#sim-gestion-id').val(item.gestion_id);

        // Expand links section if collapsed
        const details = document.getElementById('sim-links-details');
        if (details && !details.open) { details.open = true; }

        $('#sim-lookup-identifier').addClass('d-none');
        $('#sim-lookup-email').addClass('d-none');
        $('#sim-linked-badge').removeClass('d-none');
    }

    function makeLookupDebounce($input, $container) {
        let timer;
        $input.on('input', function () {
            clearTimeout(timer);
            const q = $input.val().trim();
            if (q.length < 2) { $container.addClass('d-none'); return; }
            timer = setTimeout(function () {
                $.getJSON(S.lookupUrl, { q: q }, function (res) {
                    buildLookupResults(res.data || [], $container, fillFromLookup);
                });
            }, 600);
        });

        // Close on outside click
        $(document).on('click', function (e) {
            if (!$input.is(e.target) && !$container.is(e.target) && !$container.has(e.target).length) {
                $container.addClass('d-none');
            }
        });
    }

    makeLookupDebounce($('#sim-identifier'), $('#sim-lookup-identifier'));
    makeLookupDebounce($('#sim-email'),      $('#sim-lookup-email'));

    // ── Start session ──
    $('#sim-start-form').on('submit', function (e) {
        e.preventDefault();
        const message = $('#sim-first-message').val().trim();
        if (!message) { toastr.warning('Escribe un mensaje para iniciar.'); return; }

        const $btn = $('#sim-start-btn').prop('disabled', true);
        $.ajax({
            url: S.startUrl,
            method: 'POST',
            data: {
                channel:        selectedChannel,
                name:           $('#sim-name').val(),
                identifier:     $('#sim-identifier').val(),
                message:        message,
                phone:          $('#sim-phone').val(),
                email:          $('#sim-email').val(),
                prestashop_id:  $('#sim-ps-id').val(),
                gestion_id:     $('#sim-gestion-id').val(),
                simulated_datetime: $('#sim-simulated-datetime').val(),
            },
        }).done(function (res) {
            if (!res.ok) { toastr.error(res.error || 'No se pudo iniciar.'); return; }
            conv = { id: res.conversation_id, token: res.token, channel: res.channel, simulatedAt: $('#sim-simulated-datetime').val() || null };
            enterChat(res.item);
        }).fail(handleAjaxError).always(function () {
            $btn.prop('disabled', false);
        });
    });

    function enterChat(firstItem) {
        const l = LABELS[conv.channel];
        const name = $('#sim-name').val() || 'Cliente simulado';

        $('#sim-channel-badge').html('<i class="' + l.icon + ' me-1"></i>' + l.name);
        let identity = name + ' · #' + conv.id;
        if (conv.simulatedAt) {
            identity += ' · <span class="sim-clock-badge"><i class="fas fa-clock me-1"></i>' + conv.simulatedAt.replace('T', ' ') + '</span>';
        }
        $('#sim-identity').html(identity);
        $('#sim-setup').addClass('d-none');
        $('#sim-chat')
            .removeClass('sim-channel-whatsapp sim-channel-facebook sim-channel-instagram sim-channel-web')
            .addClass('sim-channel-' + conv.channel)
            .removeClass('d-none');
        conversationResolved = false;
        closeEmojiPicker();

        renderMessage(firstItem);
        loadSessions();
        subscribeRealtime();
        startPolling();
        $('#sim-message').focus();
    }

    // ── Send follow-up message as customer ──
    $('#sim-send-form').on('submit', function (e) {
        e.preventDefault();
        if (!conv || conversationResolved) return;

        const message = $('#sim-message').val().trim();
        if (!message) return;
        $('#sim-message').val('').focus();

        const $sendBtn = $('#sim-send-btn').prop('disabled', true).html('<i class="fas fa-circle-notch fa-spin"></i>');

        $.ajax({
            url: S.base + '/' + conv.id + '/inbound',
            method: 'POST',
            data: { message: message, token: conv.token },
        }).done(function (res) {
            if (!res.ok) { toastr.error(res.error || 'No se pudo enviar.'); return; }
            renderMessage(res.item);
        }).fail(handleAjaxError).always(function () {
            $sendBtn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i>');
        });
    });

    // ── Reset: pre-fill form, don't reload page ──
    $('#sim-reset, #sim-new-conv-btn').on('click', startNewConversation);

    function startNewConversation() {
        setUnreadCount(0);

        // Save session data for pre-fill
        savedSession = {
            channel:    conv ? conv.channel : selectedChannel,
            name:       $('#sim-name').val(),
            identifier: $('#sim-identifier').val(),
        };

        // Tear down realtime connections
        if (echo) { echo.disconnect(); echo = null; }
        if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
        if (typingTimer) { clearTimeout(typingTimer); typingTimer = null; }
        if (reconnectTimer) { clearTimeout(reconnectTimer); reconnectTimer = null; }

        // Reset state
        conv = null;
        seen.clear();
        lastId = 0;
        conversationResolved = false;
        lastRenderFrom = null;
        lastRenderDate = null;
        reconnectDelay = 3000;
        userScrolledUp = false;
        pendingScrollCount = 0;

        // Reset UI
        $('#sim-thread').empty().append(
            $('<div id="sim-typing-indicator" class="sim-typing d-none">').html(
                'Agente escribiendo<span></span><span></span><span></span>'
            )
        );
        $('#sim-resolved-banner').addClass('d-none');
        $('#sim-inject-panel').addClass('d-none');
        $('#sim-inject-msg').val('');
        $('#sim-scroll-badge').addClass('d-none');
        $('#sim-message').prop('disabled', false).css('height', 'auto');
        $('#sim-send-btn').prop('disabled', false);
        $('#sim-file-btn, #sim-audio-btn').prop('disabled', false);
        $('#sim-linked-badge').addClass('d-none');
        $('#sim-lookup-identifier').addClass('d-none');
        $('#sim-lookup-email').addClass('d-none');
        $('#sim-conn').removeClass('live');

        // Switch views
        closeEmojiPicker();
        $('#sim-chat')
            .removeClass('sim-channel-whatsapp sim-channel-facebook sim-channel-instagram sim-channel-web')
            .addClass('d-none');
        $('#sim-setup').removeClass('d-none');
        renderSessions(allSessions); // deselect active card

        // Pre-fill saved session data
        if (savedSession) {
            selectedChannel = savedSession.channel;
            $('.sim-channel-btn').removeClass('active');
            $('.sim-channel-btn[data-channel="' + savedSession.channel + '"]').addClass('active');
            updateIdentityField();
            $('#sim-name').val(savedSession.name);
            $('#sim-identifier').val(savedSession.identifier);
        }

        // Only clear the message so user types a new one
        $('#sim-first-message').val('').focus();
    }

    // ── Rendering ──
    function formatTime(isoString) {
        const d = isoString ? new Date(isoString) : new Date();
        return d.getHours().toString().padStart(2, '0') + ':' + d.getMinutes().toString().padStart(2, '0');
    }

    function buildMessageBody(m) {
        const type  = m.content_type || 'text';
        const att   = m.attachments || [];
        const first = att[0] || {};

        let $body;

        if (m.link_preview && m.link_preview.title) {
            const lp    = m.link_preview;
            const $card = $('<div>').addClass('sim-link-preview');
            if (lp.image_url) { $card.append($('<img>').attr('src', lp.image_url).addClass('sim-lp-img')); }
            $card.append($('<div>').addClass('sim-lp-title').text(lp.title));
            if (lp.description) { $card.append($('<div>').addClass('sim-lp-desc').text(lp.description)); }
            if (lp.url) { $card.append($('<a>').attr({ href: lp.url, target: '_blank', rel: 'noopener' }).addClass('sim-lp-url').text(lp.domain || lp.url)); }
            const $wrap = $('<div>');
            if (m.body) { $wrap.append($('<div>').addClass('sim-msg-body').text(m.body)); }
            $body = $wrap.append($card);
        } else {
            switch (type) {
                case 'image': {
                    const $img = $('<img>').attr({ src: first.url, alt: first.name || 'imagen', loading: 'lazy' }).addClass('sim-att-img');
                    $img.on('click', function () { window.open(first.url, '_blank'); });
                    $img.on('load', scrollBottom);
                    $body = $('<div>').append($img);
                    break;
                }
                case 'audio': {
                    $body = $('<div>').append($('<audio>').attr({ controls: true, src: first.url, preload: 'metadata' }).addClass('sim-att-audio'));
                    break;
                }
                case 'video': {
                    const ytMatch = (first.url || '').match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([A-Za-z0-9_-]{11})/);
                    if (ytMatch) {
                        const $iframe = $('<iframe>').attr({ src: 'https://www.youtube.com/embed/' + ytMatch[1], width: 250, height: 141, frameborder: 0, allow: 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture', allowfullscreen: true }).addClass('sim-att-video');
                        $body = $('<div>').append($iframe);
                    } else {
                        $body = $('<div>').append($('<video>').attr({ controls: true, src: first.url, preload: 'metadata' }).addClass('sim-att-video'));
                    }
                    break;
                }
                case 'file': {
                    const icon    = (first.mime_type || '').includes('pdf') ? 'fa-file-pdf' : 'fa-file';
                    const sizeStr = first.size > 0 ? ' · ' + Math.round(first.size / 1024) + ' KB' : '';
                    const $card   = $('<a>').attr({ href: first.url, target: '_blank', rel: 'noopener', download: first.name }).addClass('sim-att-file d-flex align-items-center gap-2 text-decoration-none');
                    $card.append($('<i>').addClass('fas ' + icon + ' fa-lg'));
                    $card.append($('<span>').text((first.name || 'archivo') + sizeStr));
                    $body = $('<div>').append($card);
                    break;
                }
                default:
                    $body = $('<div>').addClass('sim-msg-body').text(m.body || '');
            }
        }

        // Product carousel (native cards on FB/web, stacked images on WhatsApp/Instagram)
        const cards = m.cards || [];
        if (cards.length) {
            const $cards = $('<div>').addClass('sim-cards');
            cards.slice(0, 10).forEach(function (card) {
                const $c = $('<div>').addClass('sim-pcard');
                if (card.image_url) {
                    $c.append($('<img>').attr({ src: card.image_url, alt: card.title || 'producto', loading: 'lazy' }).on('load', scrollBottom));
                }
                const $cb = $('<div>').addClass('sim-pcard-body');
                if (card.title)    { $cb.append($('<div>').addClass('sim-pcard-title').text(card.title)); }
                if (card.subtitle) { $cb.append($('<div>').addClass('sim-pcard-sub').text(card.subtitle)); }
                if (card.url)      { $cb.append($('<a>').attr({ href: card.url, target: '_blank', rel: 'noopener' }).addClass('sim-pcard-btn').text('Ver')); }
                $c.append($cb);
                $cards.append($c);
            });
            $body = $('<div>').append($body).append($cards);
        }

        // Options → native buttons within each channel's limit; otherwise a note
        // (the body already carries the numbered "1, 2, 3…" list as the fallback).
        const qr = m.quick_replies || [];
        if (qr.length) {
            const NATIVE_LIMIT = { whatsapp: 3, facebook: 11, instagram: 13, web: 99 };
            const ch    = (conv && conv.channel) || 'web';
            const limit = NATIVE_LIMIT[ch] || 99;

            if (qr.length <= limit) {
                const $qrRow = $('<div>').addClass('sim-quick-replies');
                qr.forEach(function (reply) {
                    const label = typeof reply === 'string' ? reply : (reply.title || reply.label || reply);
                    const $btn  = $('<button>').addClass('sim-qr-btn').text(label);
                    $btn.on('click', function () {
                        if (!conv || conversationResolved) return;
                        $('#sim-message').val(label);
                        $('#sim-send-form').trigger('submit');
                        $btn.closest('.sim-quick-replies').remove();
                    });
                    $qrRow.append($btn);
                });
                $body = $('<div>').append($body).append($qrRow);
            } else {
                const chName = (LABELS[ch] || {}).name || 'Este canal';
                const $note  = $('<div>').addClass('sim-limit-note').html(
                    '<i class="fas fa-info-circle me-1"></i>' + qr.length + ' opciones · ' + chName +
                    ' solo admite ' + limit + ' botones: se envían como lista numerada (1, 2, 3…).'
                );
                $body = $('<div>').append($body).append($note);
            }
        }

        return $body;
    }

    function playNotificationBeep() {
        try {
            const ctx  = new (window.AudioContext || window.webkitAudioContext)();
            const osc  = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = 880;
            osc.type = 'sine';
            gain.gain.setValueAtTime(0.2, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 0.3);
        } catch (e) { /* no-op */ }
    }

    function showBrowserNotification(senderName, body) {
        if (!('Notification' in window) || Notification.permission !== 'granted' || document.hasFocus()) return;
        const text = typeof body === 'string' && body.length > 80 ? body.substring(0, 77) + '…' : (body || '📎 Archivo');
        const n = new Notification('Nuevo mensaje de ' + senderName, {
            body: text,
            icon: '/favicon.ico',
            tag: 'helpdesk-sim-msg',
        });
        n.onclick = function () { window.focus(); n.close(); };
        setTimeout(function () { n.close(); }, 6000);
    }

    function setUnreadCount(n) {
        unreadCount = n;
        document.title = n > 0 ? '💬 (' + n + ') ' + originalTitle : originalTitle;
    }

    $(window).on('focus', function () { setUnreadCount(0); });

    function renderMessage(m) {
        if (!m || seen.has(m.id)) return;
        seen.add(m.id);
        if (m.id > lastId) lastId = m.id;

        hideTypingIndicator();

        if (m.from === 'agent' && !document.hasFocus()) {
            playNotificationBeep();
            showBrowserNotification(m.sender_name || 'Agente', m.body || '');
            setUnreadCount(unreadCount + 1);
        }

        const mine = m.from === 'customer';
        const who  = mine ? 'Tú' : (m.sender_name || 'Agente');
        const time = formatTime(m.created_at || null);

        // Date separator
        const msgDate = m.created_at ? new Date(m.created_at).toLocaleDateString('es-ES', { day: 'numeric', month: 'long', year: 'numeric' }) : null;
        if (msgDate && msgDate !== lastRenderDate) {
            const label = (function () {
                const today     = new Date().toLocaleDateString('es-ES', { day: 'numeric', month: 'long', year: 'numeric' });
                const yesterday = new Date(Date.now() - 86400000).toLocaleDateString('es-ES', { day: 'numeric', month: 'long', year: 'numeric' });
                if (msgDate === today)     return 'Hoy';
                if (msgDate === yesterday) return 'Ayer';
                return msgDate;
            }());
            $('#sim-typing-indicator').before($('<span>').addClass('sim-date-sep').html('<span>' + label + '</span>'));
            lastRenderDate = msgDate;
            lastRenderFrom = null;
        }

        // Consecutive same-sender grouping
        const grouped = m.from === lastRenderFrom;
        lastRenderFrom = m.from;

        const $msg = $('<div>').addClass('sim-msg ' + (mine ? 'sim-msg-out' : 'sim-msg-in') + (grouped ? ' sim-msg-grouped' : ''));
        $msg.append($('<div>').addClass('sim-msg-who').text(who));
        $msg.append(buildMessageBody(m));
        $msg.append($('<div>').addClass('sim-msg-time').text(time));

        // Delivery receipt on the customer's own messages (✓ sent → ✓✓ read once the bot replies).
        if (mine) {
            $msg.append($('<div>').addClass('sim-msg-receipt').html('<i class="fas fa-check"></i>'));
        } else {
            $('.sim-msg-out .sim-msg-receipt').html('<i class="fas fa-check-double"></i> Leído');
        }

        if (m.type === 'note') {
            $msg.addClass('sim-msg-note');
        }

        // Scroll badge for agent messages when user has scrolled up
        if (userScrolledUp && m.from === 'agent') {
            pendingScrollCount++;
            $('#sim-scroll-count').text(pendingScrollCount);
            $('#sim-scroll-badge').removeClass('d-none');
        }

        $('#sim-typing-indicator').before($msg);
        scrollBottom();
    }

    function renderSystemMessage(text) {
        const $sys = $('<span>').addClass('sim-system-msg').text(text);
        $('#sim-typing-indicator').before($sys);
        scrollBottom();
    }

    function scrollBottom(force) {
        const el = document.getElementById('sim-thread');
        if (force || !userScrolledUp) {
            el.scrollTop = el.scrollHeight;
        }
    }

    // Detect manual scroll
    $('#sim-thread').on('scroll', function () {
        const el = this;
        const atBottom = el.scrollHeight - el.scrollTop - el.clientHeight < 40;
        if (atBottom) {
            userScrolledUp = false;
            pendingScrollCount = 0;
            $('#sim-scroll-badge').addClass('d-none');
        } else {
            userScrolledUp = true;
        }
    });

    $('#sim-scroll-btn').on('click', function () {
        userScrolledUp = false;
        pendingScrollCount = 0;
        $('#sim-scroll-badge').addClass('d-none');
        scrollBottom(true);
    });

    // ── Typing indicator ──
    function showTypingIndicator() {
        $('#sim-typing-indicator').removeClass('d-none');
        scrollBottom();
        clearTimeout(typingTimer);
        typingTimer = setTimeout(hideTypingIndicator, 8000);
    }

    function hideTypingIndicator() {
        clearTimeout(typingTimer);
        typingTimer = null;
        $('#sim-typing-indicator').addClass('d-none');
    }

    // ── Conversation resolved ──
    function markResolved() {
        if (conversationResolved) return;
        conversationResolved = true;
        renderSystemMessage('✅ Conversación resuelta');
        $('#sim-resolved-banner').removeClass('d-none');
        $('#sim-message').prop('disabled', true);
        $('#sim-send-btn').prop('disabled', true);
        $('#sim-file-btn, #sim-audio-btn').prop('disabled', true);

        // Try to show CSAT
        $.ajax({
            url:    S.base + '/' + conv.id + '/csat',
            method: 'GET',
            data:   { token: conv.token },
        }).done(function (res) {
            if (res.ok && res.token) { showCsatWidget(res.token); }
        });
    }

    function showCsatWidget(csatToken) {
        const $widget = $('<div>').addClass('sim-csat-widget p-3 mt-2 rounded border').html(
            '<p class="mb-2 fw-semibold small">¿Cómo te atendimos? <span class="text-muted fw-normal">(Califica del 1 al 5)</span><' + '/p>' +
            '<div class="sim-csat-stars d-flex gap-2 mb-2">' +
                [1,2,3,4,5].map(function (i) {
                    return '<button type="button" class="sim-star-btn btn btn-sm btn-outline-secondary" data-val="' + i + '" title="' + i + ' estrella' + (i > 1 ? 's' : '') + '">⭐<' + '/button>';
                }).join('') +
            '<' + '/div>' +
            '<textarea class="form-control form-control-sm mb-2" id="sim-csat-comment" rows="2" placeholder="Comentario opcional…" maxlength="500"><' + '/textarea>' +
            '<button type="button" class="btn btn-sm sim-btn-primary w-100" id="sim-csat-submit" disabled>Enviar calificación<' + '/button>'
        );

        let selectedRating = 0;

        $widget.on('click', '.sim-star-btn', function () {
            selectedRating = parseInt($(this).data('val'));
            $widget.find('.sim-star-btn').each(function () {
                $(this).toggleClass('active btn-warning border-warning', parseInt($(this).data('val')) <= selectedRating);
            });
            $('#sim-csat-submit').prop('disabled', false);
        });

        $widget.on('click', '#sim-csat-submit', function () {
            if (!selectedRating) return;
            $(this).prop('disabled', true).text('Enviando…');
            $.ajax({
                url:    '/helpdesk/csat/' + csatToken,
                method: 'POST',
                data:   { rating: selectedRating, comment: $('#sim-csat-comment').val(), _token: S.csrf },
            }).always(function () {
                $widget.html('<p class="mb-0 text-success small fw-semibold">✅ ¡Gracias por tu calificación!</p>');
            });
        });

        $('#sim-thread').append($widget);
        scrollBottom();
    }

    // ── File upload ──
    function uploadFile(file) {
        if (!conv || conversationResolved || !file) return;

        const fd      = new FormData();
        fd.append('file', file);
        fd.append('token', conv.token);

        let $pending = null;
        let blobUrl  = null;

        if (file.type.startsWith('image/')) {
            blobUrl  = URL.createObjectURL(file);
            $pending = $('<div>').addClass('sim-msg sim-msg-out');
            $pending.append($('<div>').addClass('sim-msg-who').text('Tú'));
            const $img = $('<img>').attr({ src: blobUrl, alt: file.name, loading: 'eager' }).addClass('sim-att-img').css('opacity', '.55');
            $img.on('load', scrollBottom);
            $pending.append($('<div>').append($img));
            $pending.append($('<div>').addClass('sim-msg-time').html('<i class="fas fa-circle-notch fa-spin me-1" style="font-size:.6rem"></i>Enviando…'));
            $('#sim-typing-indicator').before($pending);
            scrollBottom();
        } else {
            toastr.info('Subiendo ' + file.name + '…', '', { timeOut: 2500 });
        }

        const $btn = $('#sim-file-btn').prop('disabled', true);

        $.ajax({
            url:         S.base + '/' + conv.id + '/attachment',
            method:      'POST',
            data:        fd,
            processData: false,
            contentType: false,
        }).done(function (res) {
            if ($pending) { $pending.remove(); if (blobUrl) { URL.revokeObjectURL(blobUrl); } }
            if (!res.ok) { toastr.error(res.error || 'No se pudo subir.'); return; }
            renderMessage(res.item);
        }).fail(function (xhr) {
            if ($pending) { $pending.remove(); }
            handleAjaxError(xhr);
        }).always(function () {
            $btn.prop('disabled', false);
        });
    }

    $('#sim-file-input').on('change', function () {
        uploadFile(this.files[0]);
        this.value = '';
    });

    // ── Paste image from clipboard ──
    $(document).on('paste', function (e) {
        if (!conv || conversationResolved) return;
        const items = (e.originalEvent.clipboardData || e.clipboardData || {}).items;
        if (!items) return;

        for (let i = 0; i < items.length; i++) {
            if (items[i].type.startsWith('image/')) {
                e.preventDefault();
                const file = items[i].getAsFile();
                if (!file) continue;

                const blobUrl  = URL.createObjectURL(file);
                const $pending = $('<div>').addClass('sim-msg sim-msg-out');
                $pending.append($('<div>').addClass('sim-msg-who').text('Tú'));
                const $img = $('<img>').attr({ src: blobUrl, alt: 'imagen', loading: 'eager' }).addClass('sim-att-img').css('opacity', '.55');
                $img.on('load', scrollBottom);
                $pending.append($('<div>').append($img));
                $pending.append($('<div>').addClass('sim-msg-time').html('<i class="fas fa-circle-notch fa-spin me-1" style="font-size:.6rem"></i>Enviando…'));
                $('#sim-typing-indicator').before($pending);
                scrollBottom();

                const fd = new FormData();
                fd.append('file', file, 'paste-' + Date.now() + '.png');
                fd.append('token', conv.token);

                $.ajax({
                    url:         S.base + '/' + conv.id + '/attachment',
                    method:      'POST',
                    data:        fd,
                    processData: false,
                    contentType: false,
                }).done(function (res) {
                    $pending.remove();
                    URL.revokeObjectURL(blobUrl);
                    if (res.ok) { renderMessage(res.item); }
                    else { toastr.error(res.error || 'No se pudo subir la imagen.'); }
                }).fail(function (xhr) {
                    $pending.remove();
                    handleAjaxError(xhr);
                });
                break;
            }
        }
    });

    // ── Drag & drop ──
    const $chatCard = $('#sim-chat');

    $chatCard.on('dragover dragenter', function (e) {
        if (!conv || conversationResolved) return;
        e.preventDefault();
        e.stopPropagation();
        $chatCard.addClass('drag-over');
    }).on('dragleave drop', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $chatCard.removeClass('drag-over');
    }).on('drop', function (e) {
        if (!conv || conversationResolved) return;
        const files = e.originalEvent.dataTransfer.files;
        if (!files || !files.length) return;
        uploadFile(files[0]);
    });

    // ── Audio recording ──
    let mediaRecorder  = null;
    let audioChunks    = [];
    let recordingTimer = null;
    let isRecording    = false;

    $('#sim-audio-btn').on('click', async function () {
        if (!conv || conversationResolved) return;

        if (isRecording) {
            mediaRecorder.stop();
            return;
        }

        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            audioChunks  = [];
            mediaRecorder = new MediaRecorder(stream);
            isRecording   = true;
            $('#sim-audio-icon').removeClass('fa-microphone').addClass('fa-stop text-danger');
            $('#sim-audio-btn').addClass('border-danger');

            mediaRecorder.ondataavailable = function (e) {
                if (e.data.size > 0) { audioChunks.push(e.data); }
            };

            mediaRecorder.onstop = function () {
                isRecording = false;
                $('#sim-audio-icon').removeClass('fa-stop text-danger').addClass('fa-microphone');
                $('#sim-audio-btn').removeClass('border-danger');
                stream.getTracks().forEach(function (t) { t.stop(); });
                clearTimeout(recordingTimer);

                const blob = new Blob(audioChunks, { type: 'audio/webm' });
                const fd   = new FormData();
                fd.append('file', blob, 'audio-' + Date.now() + '.webm');
                fd.append('token', conv.token);

                toastr.info('Enviando audio…', '', { timeOut: 2000 });
                $.ajax({
                    url:         S.base + '/' + conv.id + '/attachment',
                    method:      'POST',
                    data:        fd,
                    processData: false,
                    contentType: false,
                }).done(function (res) {
                    if (res.ok) { renderMessage(res.item); }
                    else { toastr.error(res.error || 'No se pudo enviar el audio.'); }
                }).fail(handleAjaxError);
            };

            mediaRecorder.start();
            // Auto-stop after 60s
            recordingTimer = setTimeout(function () {
                if (isRecording && mediaRecorder) { mediaRecorder.stop(); }
            }, 60000);
        } catch (e) {
            toastr.error('No se pudo acceder al micrófono.');
        }
    });

    // ── Share conversation link ──
    $('#sim-share-btn').on('click', function () {
        if (!conv) return;
        const url = window.location.origin + S.base + '?conv=' + conv.id + '&token=' + conv.token;
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(url).then(function () { toastr.success('Enlace copiado'); });
        } else {
            const $tmp = $('<input>').val(url).appendTo('body').select();
            document.execCommand('copy');
            $tmp.remove();
            toastr.success('Enlace copiado');
        }
    });

    // ── Export conversation ──
    $('#sim-export-btn').on('click', function () {
        if (!conv) return;
        const $btn = $(this).prop('disabled', true);

        $.ajax({
            url:    S.base + '/' + conv.id + '/messages',
            method: 'GET',
            data:   { token: conv.token, after_id: 0 },
        }).done(function (res) {
            if (!res.ok) { toastr.error('No se pudo exportar.'); return; }

            const lines = ['Conversación #' + conv.id, 'Exportado: ' + new Date().toLocaleString('es-ES'), Array(40).fill('─').join(''), ''];
            res.messages.forEach(function (m) {
                const time   = m.created_at ? new Date(m.created_at).toLocaleString('es-ES') : '';
                const sender = m.from === 'agent' ? (m.sender_name || 'Agente') : 'Tú';
                const prefix = m.type === 'note' ? '[NOTA] ' : '';
                lines.push('[' + time + '] ' + prefix + sender + ':');
                if (m.body) { lines.push(m.body); }
                if (m.attachments && m.attachments.length) {
                    m.attachments.forEach(function (a) { lines.push('📎 ' + (a.name || a.url)); });
                }
                lines.push('');
            });

            const blob = new Blob([lines.join('\n')], { type: 'text/plain;charset=utf-8' });
            const a    = document.createElement('a');
            a.href     = URL.createObjectURL(blob);
            a.download = 'conv-' + conv.id + '.txt';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(a.href);
            toastr.success('Conversación exportada');
        }).fail(handleAjaxError).always(function () {
            $btn.prop('disabled', false);
        });
    });

    // ── Dev inject panel ──
    $('#sim-dev-btn').on('click', function () {
        $('#sim-inject-panel').toggleClass('d-none');
        if (!$('#sim-inject-panel').hasClass('d-none')) {
            $('#sim-inject-msg').focus();
        }
    });

    $('#sim-inject-btn').on('click', function () {
        if (!conv) return;
        const msg = $('#sim-inject-msg').val().trim();
        if (!msg) return;

        const $btn = $(this).prop('disabled', true).html('<i class="fas fa-circle-notch fa-spin"></i>');

        $.ajax({
            url:    S.base + '/' + conv.id + '/inject',
            method: 'POST',
            data:   { message: msg, token: conv.token },
        }).done(function (res) {
            if (res.ok) {
                renderMessage(res.item);
                $('#sim-inject-msg').val('');
            } else {
                toastr.error(res.error || 'No se pudo inyectar.');
            }
        }).fail(handleAjaxError).always(function () {
            $btn.prop('disabled', false).html('Inyectar');
        });
    });

    $('#sim-inject-msg').on('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); $('#sim-inject-btn').trigger('click'); }
    });

    // ── Reconnect with exponential backoff ──
    function scheduleReconnect() {
        if (reconnectTimer || !conv) return;
        reconnectTimer = setTimeout(function () {
            reconnectTimer = null;
            reconnectDelay = Math.min(reconnectDelay * 2, 30000);
            if (!conv) return;
            if (echo) { try { echo.disconnect(); } catch (e) {} echo = null; }
            subscribeRealtime();
        }, reconnectDelay);
    }

    // ── Real-time (Reverb/Echo) ──
    function subscribeRealtime() {
        if (typeof window.Echo === 'undefined' || !S.reverb.key) { return; }

        try {
            let host = S.reverb.host;
            if (!host || host === '0.0.0.0' || host === '127.0.0.1') {
                host = window.location.hostname;
            }

            echo = new window.Echo({
                broadcaster:        'reverb',
                key:                S.reverb.key,
                wsHost:             host,
                wsPort:             S.reverb.port,
                wssPort:            S.reverb.port,
                forceTLS:           S.reverb.scheme === 'https',
                enabledTransports:  ['ws', 'wss'],
                disableStats:       true,
            });

            const pusher = echo.connector.pusher;
            pusher.connection.bind('connected', function () {
                $('#sim-conn').addClass('live');
                reconnectDelay = 3000;
                if (reconnectTimer) { clearTimeout(reconnectTimer); reconnectTimer = null; }
            });
            pusher.connection.bind('disconnected', function () {
                $('#sim-conn').removeClass('live');
                scheduleReconnect();
            });
            pusher.connection.bind('unavailable', function () {
                $('#sim-conn').removeClass('live');
                scheduleReconnect();
            });

            echo.channel('helpdesk-widget-conversation.' + conv.id)
                .listen('.message.received', function (ev) {
                    // Only render agent (outgoing) messages; customer messages come from POST response
                    if (ev.message_type === 'outgoing') {
                        renderMessage({
                            id:          ev.id,
                            body:        ev.content,
                            from:        'agent',
                            sender_name: (ev.sender && ev.sender.name) || 'Agente',
                            created_at:  ev.created_at || null,
                        });
                    }
                })
                .listen('.UserTyping', function (ev) {
                    if (ev.is_typing) {
                        showTypingIndicator();
                    } else {
                        hideTypingIndicator();
                    }
                })
                .listen('.conversation.assigned', function (ev) {
                    const agent = ev.agent_name || 'Un agente';
                    renderSystemMessage('👤 ' + agent + ' ha tomado tu consulta');
                })
                .listen('.conversation.status.changed', function (ev) {
                    if (ev.is_closed) { markResolved(); }
                });
        } catch (err) {
            console.warn('Echo no disponible; se usará polling.', err);
        }
    }

    // ── Polling fallback (always on; deduped by id) ──
    function startPolling() {
        if (pollTimer) clearInterval(pollTimer);
        pollTimer = setInterval(pollMessages, 3000);
    }

    function pollMessages() {
        if (!conv) return;
        $.ajax({
            url:    S.base + '/' + conv.id + '/messages',
            method: 'GET',
            data:   { token: conv.token, after_id: lastId },
        }).done(function (res) {
            if (res.ok && res.messages) { res.messages.forEach(renderMessage); }
        });
    }

    function handleAjaxError(xhr) {
        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
            Object.values(xhr.responseJSON.errors).forEach(function (msgs) {
                toastr.error(msgs[0]);
            });
            return;
        }
        const msg = (xhr.responseJSON && (xhr.responseJSON.error || xhr.responseJSON.message)) || 'Error de red.';
        toastr.error(msg);
    }

    // ── Emoji picker ──
    const ALL_EMOJIS = [
        '😀','😁','😂','🤣','😃','😄','😅','😆','😊','😍','🥰','😘','😎','🤔','😐','😑','😶','🙄','😏','😒',
        '😞','😔','😟','😕','🙁','☹️','😣','😖','😫','😩','🥺','😢','😭','😤','😠','😡','🤬','🤯','😳','🥵',
        '😰','😱','🤗','🤭','🤫','🤥','😶','😇','🥳','🥸','🤩','🥴','😵','🤑','🤠','😷','🤒','🤕',
        '👍','👎','👋','🤝','🙏','💪','✌️','🤞','👌','🤌','🤏','☝️','👆','👇','👉','👈','🫵','✋','🖐️','👏',
        '❤️','🧡','💛','💚','💙','💜','🖤','🤍','💕','💞','💓','💗','💖','💘','💝','💔','❤️‍🔥',
        '🎉','🎊','🎈','🎁','✅','❌','⚠️','🔥','⭐','💯','🚀','💡','📦','📋','📞','✉️','🔔','💬','🏠',
        '😺','😸','😹','😻','😼','😽','🙀','😿','😾','🐶','🐱','🐭','🐰','🐻','🐼','🐨','🦁','🐸','🐣',
        '🍕','🍔','🍟','🌮','🍜','🍣','🍦','🎂','🍩','☕','🍺','🥂','🍷','🍾',
        '⚽','🏀','🎮','🎯','🎸','🎵','🎤','🏆','🥇','🎭','🎨',
        '🌍','🌈','⛅','🌙','⭐','🌸','🌺','🌻','🍀','🌴','🌊','🏔️','🏖️',
        '💻','📱','⌨️','🖥️','🖨️','💾','📷','📸','📹','🔍','🔎','📚','✏️','📝','🗒️',
    ];

    function buildEmojiGrid(filter) {
        const $grid  = $('#sim-emoji-grid').empty();
        const emojis = filter ? ALL_EMOJIS.filter(function (e) { return e.includes(filter); }) : ALL_EMOJIS;
        emojis.forEach(function (emoji) {
            $('<button>').attr({ type: 'button', title: emoji }).addClass('sim-emoji-btn-item').text(emoji)
                .on('click', function () {
                    insertAtCursor($('#sim-message'), emoji);
                    closeEmojiPicker();
                })
                .appendTo($grid);
        });
    }

    function openEmojiPicker() {
        $('#sim-emoji-search').val('');
        buildEmojiGrid('');
        $('#sim-emoji-picker').removeClass('d-none');
        $('#sim-emoji-search').focus();
    }

    function closeEmojiPicker() {
        $('#sim-emoji-picker').addClass('d-none');
    }

    function insertAtCursor($el, text) {
        const el    = $el[0];
        const start = el.selectionStart || 0;
        const end   = el.selectionEnd   || 0;
        const val   = el.value;
        el.value = val.substring(0, start) + text + val.substring(end);
        el.selectionStart = el.selectionEnd = start + text.length;
        $el.trigger('input'); // trigger auto-resize
        $el.focus();
    }

    $('#sim-emoji-btn').on('click', function (e) {
        e.stopPropagation();
        if ($('#sim-emoji-picker').hasClass('d-none')) {
            openEmojiPicker();
        } else {
            closeEmojiPicker();
        }
    });

    $('#sim-emoji-search').on('input', function () {
        buildEmojiGrid($(this).val().trim());
    }).on('keydown', function (e) {
        if (e.key === 'Escape') { closeEmojiPicker(); }
    });

    // Close on click outside
    $(document).on('click', function (e) {
        if (!$('#sim-emoji-picker').hasClass('d-none') &&
            !$(e.target).closest('#sim-emoji-picker, #sim-emoji-btn').length) {
            closeEmojiPicker();
        }
    });

    // ── Sessions list ──
    const CHANNEL_ICONS = {
        whatsapp:  'fab fa-whatsapp text-success',
        facebook:  'fab fa-facebook-messenger text-primary',
        instagram: 'fab fa-instagram text-danger',
        web:       'fas fa-globe text-info',
    };

    let allSessions = [];
    let showAllSessions = false;

    function timeAgo(isoString) {
        if (!isoString) return '';
        const diff = Math.floor((Date.now() - new Date(isoString).getTime()) / 1000);
        if (diff < 60)    return 'hace un momento';
        if (diff < 3600)  return 'hace ' + Math.floor(diff / 60) + ' min';
        if (diff < 86400) return 'hace ' + Math.floor(diff / 3600) + ' h';
        return 'hace ' + Math.floor(diff / 86400) + ' d';
    }

    function getHiddenSessions() {
        try { return JSON.parse(localStorage.getItem('sim_hidden_sessions') || '[]'); } catch (e) { return []; }
    }

    function addHiddenSession(id) {
        const hidden = getHiddenSessions();
        if (hidden.indexOf(id) === -1) { hidden.push(id); }
        localStorage.setItem('sim_hidden_sessions', JSON.stringify(hidden));
    }

    function renderSessions(rawSessions) {
        const hidden   = getHiddenSessions();
        const sessions = rawSessions.filter(function (s) { return hidden.indexOf(s.id) === -1; });

        const $grid  = $('#sim-sessions-grid').empty();
        const visible = showAllSessions ? sessions : sessions.slice(0, 6);

        if (!sessions.length) {
            $grid.append('<div class="sim-sessions-empty">No hay sesiones aún</div>');
            $('#sim-sessions-more').addClass('d-none');
            return;
        }

        visible.forEach(function (s) {
            const iconClass   = CHANNEL_ICONS[s.channel] || 'fas fa-circle text-secondary';
            const statusClass = s.is_closed ? 'closed' : '';
            const isActive    = conv && conv.id === s.id;
            const chLabel     = (s.channel || 'web').charAt(0).toUpperCase() + (s.channel || 'web').slice(1);

            const $card = $('<div>')
                .addClass('sim-session-card' + (isActive ? ' active' : ''))
                .attr('data-id', s.id)
                .html(
                    '<div class="sim-session-ch"><i class="' + iconClass + ' me-1"></i>' + chLabel + '</div>' +
                    '<div class="sim-session-name">' + $('<span>').text(s.customer_name || 'Cliente').html() + ' <span class="text-muted fw-normal">#' + s.id + '</span></div>' +
                    '<div class="sim-session-preview">' + $('<span>').text(s.last_message || '—').html() + '</div>' +
                    '<div class="sim-session-time"><span class="sim-session-status ' + statusClass + '"></span>' + timeAgo(s.last_message_at) + '</div>'
                );

            if (s.message_count) {
                $card.append($('<div>').addClass('sim-session-count').text(s.message_count + ' mensajes'));
            }

            const $del = $('<button>').addClass('sim-session-del').attr('title', 'Ocultar sesión').html('&times;');
            $del.on('click', function (e) {
                e.stopPropagation();
                addHiddenSession(s.id);
                renderSessions(allSessions);
            });
            $card.append($del);

            $card.on('click', function () {
                if (conv && conv.id === s.id) return;
                resumeSession(s);
            });

            $grid.append($card);
        });

        if (sessions.length > 6) {
            $('#sim-sessions-more').toggleClass('d-none', showAllSessions);
            $('#sim-sessions-show-all').text('Ver todas (' + sessions.length + ')');
        } else {
            $('#sim-sessions-more').addClass('d-none');
        }
    }

    function resumeSession(s) {
        if (echo)       { echo.disconnect(); echo = null; }
        if (pollTimer)  { clearInterval(pollTimer); pollTimer = null; }
        if (typingTimer){ clearTimeout(typingTimer); typingTimer = null; }

        conv = { id: s.id, token: s.token, channel: s.channel };
        seen.clear();
        lastId = 0;
        conversationResolved = s.is_closed;
        lastRenderFrom = null;
        lastRenderDate = null;
        userScrolledUp = false;
        pendingScrollCount = 0;

        const l = LABELS[s.channel] || LABELS['web'];
        $('#sim-channel-badge').html('<i class="' + l.icon + ' me-1"></i>' + l.name);
        $('#sim-identity').text((s.customer_name || 'Cliente') + ' · #' + s.id);

        $('#sim-thread').empty().append(
            $('<div id="sim-typing-indicator" class="sim-typing d-none">').html(
                'Agente escribiendo<span></span><span></span><span></span>'
            )
        );
        $('#sim-resolved-banner').addClass('d-none');
        $('#sim-inject-panel').addClass('d-none');
        $('#sim-inject-msg').val('');
        $('#sim-scroll-badge').addClass('d-none');
        $('#sim-message').prop('disabled', s.is_closed).css('height', 'auto');
        $('#sim-send-btn').prop('disabled', s.is_closed);
        $('#sim-file-btn, #sim-audio-btn').prop('disabled', s.is_closed);
        $('#sim-conn').removeClass('live');

        $('#sim-setup').addClass('d-none');
        $('#sim-chat')
            .removeClass('sim-channel-whatsapp sim-channel-facebook sim-channel-instagram sim-channel-web')
            .addClass('sim-channel-' + s.channel)
            .removeClass('d-none');
        closeEmojiPicker();

        if (s.is_closed) {
            renderSystemMessage('✅ Esta conversación está resuelta');
            $('#sim-resolved-banner').removeClass('d-none');
        }

        $.ajax({
            url:    S.base + '/' + s.id + '/messages',
            method: 'GET',
            data:   { token: s.token, after_id: 0 },
        }).done(function (res) {
            if (res.ok && res.messages) { res.messages.forEach(renderMessage); }
        });

        subscribeRealtime();
        startPolling();

        renderSessions(allSessions);
    }

    function loadSessions() {
        $.ajax({
            url:    S.base + '/sessions',
            method: 'GET',
        }).done(function (res) {
            if (!res.ok || !res.data) return;
            allSessions = res.data;
            if (allSessions.length) {
                $('#sim-sessions-panel').removeClass('d-none');
                renderSessions(allSessions);
            }
        });
    }

    $('#sim-sessions-toggle-btn').on('click', function () {
        const $grid  = $('#sim-sessions-grid, #sim-sessions-more');
        const hidden = $grid.first().hasClass('d-none');
        $grid.toggleClass('d-none', !hidden);
        $(this).text(hidden ? 'Ocultar' : 'Mostrar');
    });

    $('#sim-sessions-show-all').on('click', function () {
        showAllSessions = true;
        renderSessions(allSessions);
    });

    $('#sim-sessions-search').on('input', function () {
        const q = $(this).val().toLowerCase().trim();
        const filtered = q
            ? allSessions.filter(function (s) {
                return (s.customer_name || '').toLowerCase().indexOf(q) !== -1
                    || (s.last_message || '').toLowerCase().indexOf(q) !== -1
                    || String(s.id).indexOf(q) !== -1;
            })
            : allSessions;
        renderSessions(filtered);
    });

    // URL-param resume (enlace compartido)
    (function () {
        const urlParams  = new URLSearchParams(window.location.search);
        const urlConvId  = parseInt(urlParams.get('conv'), 10);
        const urlToken   = urlParams.get('token');

        if (urlConvId && urlToken) {
            history.replaceState({}, '', window.location.pathname);
            $.ajax({ url: S.base + '/sessions', method: 'GET' }).done(function (res) {
                allSessions = (res.ok && res.data) ? res.data : [];
                if (allSessions.length) {
                    $('#sim-sessions-panel').removeClass('d-none');
                    renderSessions(allSessions);
                }
                const found = allSessions.find(function (s) { return s.id === urlConvId; });
                resumeSession(found || { id: urlConvId, token: urlToken, channel: 'web', customer_name: 'Conversación compartida', is_closed: false });
            }).fail(function () {
                resumeSession({ id: urlConvId, token: urlToken, channel: 'web', customer_name: 'Conversación compartida', is_closed: false });
            });
        } else {
            loadSessions();
        }
    }());
})();
</script>
</body>
</html>

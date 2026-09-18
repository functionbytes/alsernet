<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Simulador de canales · Helpdesk</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.css" rel="stylesheet">

    <style>
        /* ── Tokens ──────────────────────────────────────────────────────
           Paleta propia del simulador: verde de marca + tinta cálida sobre
           papel, nunca rojo (los estados "peligro" usan índigo, igual que
           --bv-danger en el resto de la app). */
        :root {
            --sim-paper:       #f6f7f1;
            --sim-surface:     #ffffff;
            --sim-ink:         #1d2116;
            --sim-ink-soft:    #5b6152;
            --sim-ink-faint:   #93998a;
            --sim-line:        #e0e3d6;
            --sim-line-soft:   #ecefe3;
            --sim-brand:       #6f8f0d;
            --sim-brand-bright:#90bb13;
            --sim-brand-tint:  #eef6d1;
            --sim-brand-deep:  #4f6b0a;
            --sim-signal:      #4b4f9e;
            --sim-signal-tint: #eceeff;
            --r-sm: .5rem;
            --r-md: .875rem;
            --r-lg: 1.25rem;
            --r-pill: 999px;
            --shadow-card: 0 1px 2px rgba(29,33,22,.04), 0 12px 28px -16px rgba(29,33,22,.14);
        }

        * { -webkit-font-smoothing: antialiased; }

        body {
            background: var(--sim-paper);
            color: var(--sim-ink);
            font-family: "IBM Plex Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: .95rem;
        }

        .sim-wrap { max-width: 730px; }

        a, button, input, textarea, select { font-family: inherit; }

        :focus-visible {
            outline: 2px solid var(--sim-brand-bright);
            outline-offset: 2px;
        }

        .form-control, .form-select {
            border-color: var(--sim-line);
            border-radius: var(--r-sm);
            color: var(--sim-ink);
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--sim-brand-bright);
            box-shadow: 0 0 0 .2rem var(--sim-brand-tint);
        }
        .form-label { font-weight: 600; font-size: .82rem; color: var(--sim-ink); margin-bottom: .3rem; }
        .form-text { color: var(--sim-ink-faint); font-size: .78rem; }
        .text-muted { color: var(--sim-ink-faint) !important; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: .001ms !important; animation-iteration-count: 1 !important; transition-duration: .001ms !important; }
        }

        /* ── Page header ── */
        .sim-page-head { display: flex; align-items: flex-start; gap: .85rem; margin-bottom: 1.5rem; }
        .sim-mark {
            position: relative;
            width: 2.5rem; height: 2.5rem; flex: 0 0 auto;
            border-radius: 50%;
            background: var(--sim-brand-tint);
            display: flex; align-items: center; justify-content: center;
        }
        .sim-mark::before {
            content: '';
            width: .95rem; height: .95rem;
            border-radius: 50%;
            background: var(--sim-brand-bright);
        }
        .sim-page-head h1 { font-size: 1.15rem; font-weight: 650; margin: 0 0 .15rem; letter-spacing: -.01em; }
        .sim-page-head p { margin: 0; color: var(--sim-ink-soft); font-size: .85rem; max-width: 46ch; }

        /* ── Cards ── */
        .sim-card { border: 1px solid var(--sim-line); border-radius: var(--r-lg); box-shadow: var(--shadow-card); background: var(--sim-surface); }

        /* ── Channel selector (segmented, per canal) ── */
        .sim-channel-group {
            display: grid; grid-template-columns: repeat(4, 1fr); gap: .4rem;
            background: var(--sim-paper);
            border: 1px solid var(--sim-line-soft);
            border-radius: var(--r-md);
            padding: .35rem;
            margin-bottom: 1.25rem;
        }
        .sim-channel-btn {
            display: flex; flex-direction: column; align-items: center; gap: .3rem;
            border: 1px solid transparent; background: transparent; color: var(--sim-ink-soft);
            border-radius: calc(var(--r-md) - .3rem); padding: .6rem .3rem;
            font-size: .78rem; font-weight: 600; cursor: pointer;
            transition: background .12s, color .12s, border-color .12s;
        }
        .sim-channel-btn i { font-size: 1.1rem; }
        .sim-channel-btn:hover { background: var(--sim-surface); border-color: var(--sim-line); color: var(--sim-ink); }
        .sim-channel-btn.active { background: var(--sim-surface); border-color: var(--sim-line); color: var(--sim-ink); box-shadow: 0 1px 3px rgba(29,33,22,.08); }
        .sim-channel-btn[data-channel="whatsapp"].active i  { color: #25d366; }
        .sim-channel-btn[data-channel="facebook"].active i  { color: #0084ff; }
        .sim-channel-btn[data-channel="instagram"].active i { background: linear-gradient(135deg,#833ab4,#fd1d1d,#fcb045); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; }
        .sim-channel-btn[data-channel="web"].active i       { color: var(--sim-brand-bright); }

        details.sim-links summary { color: var(--sim-ink-soft); cursor: pointer; font-size: .82rem; font-weight: 600; list-style: none; }
        details.sim-links summary::-webkit-details-marker { display: none; }
        details.sim-links[open] summary { color: var(--sim-brand); }

        .sim-brand { color: var(--sim-brand); }
        .sim-btn-primary { background: var(--sim-brand-bright); border-color: var(--sim-brand-bright); color: #fff; font-weight: 600; }
        .sim-btn-primary:hover, .sim-btn-primary:focus { background: var(--sim-brand-deep); border-color: var(--sim-brand-deep); color: #fff; }
        .sim-btn-primary:disabled { opacity: .6; }

        /* ── Chat card ── */
        .sim-thread { height: 52vh; overflow-y: auto; background: var(--sim-paper); border-radius: var(--r-md); padding: 1rem; }
        .sim-msg { max-width: 78%; margin-bottom: .65rem; padding: .55rem .8rem; border-radius: 1rem; clear: both; word-wrap: break-word; }
        .sim-msg-who { font-size: .68rem; opacity: .7; margin-bottom: .15rem; font-weight: 600; }
        .sim-msg-body { white-space: pre-wrap; }
        .sim-msg-time { font-size: .65rem; opacity: .55; margin-top: .15rem; }
        .sim-msg-in { float: left; background: #fff; border: 1px solid var(--sim-line-soft); border-bottom-left-radius: .25rem; }
        .sim-msg-out { float: right; background: var(--sim-brand-bright); color: #fff; border-bottom-right-radius: .25rem; }
        .sim-msg-out .sim-msg-who { color: #fff; }

        /* Live signal dot */
        .sim-conn-dot { width: 9px; height: 9px; border-radius: 50%; display: inline-block; background: #c9cdbc; position: relative; }
        .sim-conn-dot.live { background: var(--sim-brand-bright); }
        .sim-conn-dot.live::after {
            content: ''; position: absolute; inset: -4px; border-radius: 50%;
            border: 1.5px solid var(--sim-brand-bright);
            animation: simPulse 1.8s ease-out infinite;
        }
        @keyframes simPulse { 0% { transform: scale(.6); opacity: .9; } 100% { transform: scale(1.8); opacity: 0; } }

        /* Toolbar (compartir / exportar / dev / nueva) */
        .sim-toolbar { display: flex; align-items: center; gap: .15rem; background: var(--sim-paper); border: 1px solid var(--sim-line-soft); border-radius: var(--r-pill); padding: .2rem; }
        .sim-toolbar .btn {
            border: none; background: transparent; color: var(--sim-ink-soft);
            width: 2rem; height: 2rem; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; padding: 0;
        }
        .sim-toolbar .btn:hover { background: var(--sim-surface); color: var(--sim-ink); box-shadow: 0 1px 2px rgba(29,33,22,.08); }
        .sim-toolbar .btn#sim-dev-btn:hover { color: var(--sim-signal); }
        .sim-toolbar .sim-reset-btn { width: auto; border-radius: var(--r-pill); padding: 0 .75rem; font-size: .78rem; font-weight: 600; }

        /* Typing indicator */
        .sim-typing { float: left; clear: both; padding: .4rem .8rem; background: #fff; border: 1px solid var(--sim-line-soft); border-radius: 1rem; border-bottom-left-radius: .25rem; margin-bottom: .5rem; color: var(--sim-ink-soft); font-size: .8rem; }
        .sim-typing span { display: inline-block; width: 6px; height: 6px; background: var(--sim-ink-faint); border-radius: 50%; margin: 0 1px; animation: simDot 1.2s infinite; }
        .sim-typing span:nth-child(2) { animation-delay: .2s; }
        .sim-typing span:nth-child(3) { animation-delay: .4s; }
        @keyframes simDot { 0%,60%,100% { transform: translateY(0); } 30% { transform: translateY(-5px); } }

        /* System messages */
        .sim-system-msg { text-align: center; margin: .5rem 0; color: var(--sim-ink-faint); font-size: .75rem; clear: both; display: block; }
        .sim-system-msg::before, .sim-system-msg::after { content: ''; display: inline-block; vertical-align: middle; width: 30px; height: 1px; background: var(--sim-line); margin: 0 .5rem; }

        /* Resolved banner */
        .sim-resolved-banner { background: var(--sim-brand-tint); border: 1px solid var(--sim-brand-bright); border-radius: var(--r-sm); padding: .75rem 1rem; margin-bottom: .5rem; }

        /* Lookup results */
        .sim-lookup-wrap { position: relative; }
        .sim-lookup-results { background: #fff; border: 1px solid var(--sim-line); border-radius: var(--r-sm); box-shadow: 0 4px 12px rgba(29,33,22,.1); position: absolute; z-index: 100; width: 100%; max-height: 220px; overflow-y: auto; top: 100%; left: 0; }
        .sim-lookup-item { padding: .5rem .75rem; cursor: pointer; border-bottom: 1px solid var(--sim-line-soft); }
        .sim-lookup-item:hover { background: var(--sim-paper); }
        .sim-lookup-item:last-child { border-bottom: none; }
        .sim-lookup-name { font-weight: 600; font-size: .875rem; }
        .sim-lookup-detail { font-size: .75rem; color: var(--sim-ink-soft); }
        .sim-badge-linked { font-size: .7rem; color: var(--sim-brand); }

        /* Rich media attachments */
        .sim-att-img { max-width: 220px; border-radius: .5rem; cursor: zoom-in; display: block; }
        .sim-att-audio { width: 220px; }
        .sim-att-video { max-width: 250px; border-radius: .5rem; display: block; }
        .sim-att-file { background: var(--sim-paper); border: 1px solid var(--sim-line); border-radius: .5rem; padding: .5rem .75rem; color: var(--sim-ink); font-size: .85rem; }
        .sim-att-file:hover { background: var(--sim-line-soft); }
        .sim-link-preview { border: 1px solid var(--sim-line); border-radius: .5rem; overflow: hidden; margin-top: .25rem; max-width: 240px; }
        .sim-lp-img { width: 100%; height: 120px; object-fit: cover; }
        .sim-lp-title { font-weight: 600; font-size: .8rem; padding: .4rem .5rem .1rem; }
        .sim-lp-desc { font-size: .72rem; color: var(--sim-ink-soft); padding: 0 .5rem .25rem; }
        .sim-lp-url { font-size: .7rem; color: var(--sim-brand); padding: 0 .5rem .4rem; display: block; }

        /* Footer action buttons (adjuntar / audio) */
        .sim-btn-outline { border: none; background: transparent; color: var(--sim-ink-soft); border-radius: 50%; width: 2.1rem; height: 2.1rem; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; padding: 0; }
        .sim-btn-outline:hover { background: var(--sim-line-soft); color: var(--sim-ink); }

        /* CSAT widget */
        .sim-csat-widget { background: var(--sim-brand-tint); border-color: var(--sim-brand-bright) !important; }
        .sim-star-btn { border: 1px solid var(--sim-line); background: #fff; color: var(--sim-ink-soft); border-radius: var(--r-sm); width: 2.1rem; height: 2.1rem; display: inline-flex; align-items: center; justify-content: center; padding: 0; cursor: pointer; transition: background .12s, border-color .12s; }
        .sim-star-btn:hover { border-color: var(--sim-brand-bright); }
        .sim-star-btn.active { background: var(--sim-brand-bright); border-color: var(--sim-brand-bright); color: #fff; }

        /* Composer (pill) */
        .sim-composer {
            display: flex; align-items: flex-end; gap: .1rem;
            background: var(--sim-paper); border: 1px solid var(--sim-line);
            border-radius: var(--r-lg); padding: .3rem .3rem .3rem .4rem; flex: 1 1 auto;
        }
        .sim-composer:focus-within { border-color: var(--sim-brand-bright); box-shadow: 0 0 0 .2rem var(--sim-brand-tint); }
        #sim-message {
            border: none; background: transparent; box-shadow: none !important;
            line-height: 1.5; transition: height .1s; padding: .45rem .3rem;
        }
        #sim-message:focus { border: none; box-shadow: none; }

        /* Grouped messages */
        .sim-msg-grouped .sim-msg-who { display: none; }
        .sim-msg-grouped { margin-top: .2rem; }
        .sim-date-sep { text-align: center; margin: .75rem 0 .4rem; color: var(--sim-ink-faint); font-size: .72rem; clear: both; display: block; }
        .sim-date-sep span { background: var(--sim-paper); padding: 0 .6rem; }
        .sim-date-sep::before, .sim-date-sep::after { content: ''; display: inline-block; vertical-align: middle; width: 40px; height: 1px; background: var(--sim-line); margin: 0 .3rem; }

        /* Internal notes */
        .sim-msg-note { background: var(--sim-brand-tint) !important; border: 1px dashed var(--sim-brand-bright); color: var(--sim-brand-deep); }
        .sim-msg-note .sim-msg-who { color: var(--sim-brand-deep); }
        .sim-msg-note::before { content: '📝 Nota interna'; display: block; font-size: .65rem; font-weight: 700; color: var(--sim-brand-deep); margin-bottom: .2rem; letter-spacing: .04em; }

        /* Quick replies */
        .sim-quick-replies { margin-top: .5rem; display: flex; flex-wrap: wrap; gap: .35rem; }
        .sim-qr-btn { border: 1px solid var(--sim-brand-bright); color: var(--sim-brand); background: #fff; border-radius: var(--r-pill); padding: .25rem .75rem; font-size: .8rem; cursor: pointer; transition: all .15s; }
        .sim-qr-btn:hover { background: var(--sim-brand-bright); color: #fff; }

        /* Drag & drop */
        #sim-dropzone { pointer-events: none; }
        #sim-chat.drag-over #sim-dropzone { display: flex !important; }

        /* Session count */
        .sim-session-count { font-size: .65rem; color: var(--sim-ink-faint); margin-top: .1rem; }

        /* ── Sessions panel: fichas tipo ticket, no tarjetas SaaS uniformes.
           El borde izquierdo codifica el canal — es informacion (a que
           canal pertenece), no decoracion. ── */
        .sim-sessions-panel { margin-bottom: 1.25rem; }
        .sim-sessions-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: .5rem; }
        .sim-session-card {
            position: relative; background: var(--sim-surface);
            border: 1px solid var(--sim-line); border-left: 3px solid var(--sim-ink-faint);
            border-radius: var(--r-sm); padding: .65rem .85rem; cursor: pointer;
            transition: border-color .15s, background .15s;
        }
        .sim-session-card:hover { background: var(--sim-paper); }
        .sim-session-card.active { border-color: var(--sim-brand-bright); background: var(--sim-brand-tint); }
        .sim-session-card.sim-session-whatsapp  { border-left-color: #25d366; }
        .sim-session-card.sim-session-facebook  { border-left-color: #0084ff; }
        .sim-session-card.sim-session-instagram { border-left-color: #c13584; }
        .sim-session-card.sim-session-web       { border-left-color: var(--sim-brand-bright); }
        .sim-session-ch { font-size: .68rem; font-weight: 600; color: var(--sim-ink-soft); margin-bottom: .15rem; }
        .sim-session-name { font-weight: 600; font-size: .85rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sim-session-preview { font-size: .75rem; color: var(--sim-ink-soft); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: .1rem; }
        .sim-session-time { font-size: .68rem; color: var(--sim-ink-faint); margin-top: .3rem; }
        .sim-session-status { display: inline-block; width: 7px; height: 7px; border-radius: 50%; margin-right: .3rem; background: var(--sim-brand-bright); }
        .sim-session-status.closed { background: var(--sim-ink-faint); }
        .sim-ico-whatsapp  { color: #25d366; }
        .sim-ico-facebook  { color: #0084ff; }
        .sim-ico-instagram { color: #c13584; }
        .sim-ico-web       { color: var(--sim-brand-bright); }
        .sim-sessions-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: .75rem; gap: .5rem; }
        .sim-sessions-toggle { font-size: .8rem; color: var(--sim-ink-soft); cursor: pointer; background: none; border: none; padding: 0; }
        .sim-sessions-empty { text-align: center; color: var(--sim-ink-faint); font-size: .85rem; padding: .75rem; }
        .sim-session-del { position: absolute; top: .35rem; right: .4rem; background: none; border: none; color: var(--sim-ink-faint); font-size: .85rem; line-height: 1; cursor: pointer; padding: 0 .15rem; border-radius: .2rem; }
        .sim-session-del:hover { color: var(--sim-ink); background: var(--sim-line-soft); }

        /* Emoji picker */
        .sim-emoji-picker { position: absolute; bottom: 100%; left: 0; margin-bottom: .35rem; background: #fff; border: 1px solid var(--sim-line); border-radius: var(--r-md); box-shadow: 0 8px 24px rgba(29,33,22,.14); padding: .5rem; width: 280px; z-index: 200; }
        .sim-emoji-grid { display: grid; grid-template-columns: repeat(8, 1fr); gap: 1px; max-height: 200px; overflow-y: auto; }
        .sim-emoji-btn-item { font-size: 1.2rem; cursor: pointer; border: none; background: none; padding: .2rem; border-radius: .35rem; line-height: 1; text-align: center; transition: background .1s; }
        .sim-emoji-btn-item:hover { background: var(--sim-line-soft); }
        .sim-emoji-search { width: 100%; font-size: .8rem; border: 1px solid var(--sim-line); border-radius: var(--r-sm); padding: .3rem .5rem; margin-bottom: .4rem; outline: none; }
        .sim-emoji-search:focus { border-color: var(--sim-brand-bright); }

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
        .sim-channel-web .card-header { border-bottom: 3px solid var(--sim-brand-bright); }
        .sim-channel-whatsapp #sim-channel-badge { background: #25d366; color: #fff; border-color: #25d366 !important; }
        .sim-channel-facebook #sim-channel-badge { background: #0084ff; color: #fff; border-color: #0084ff !important; }
        .sim-channel-instagram #sim-channel-badge { background: linear-gradient(135deg,#833ab4,#fd1d1d,#fcb045); color: #fff; border: none; }
        .sim-channel-web #sim-channel-badge { background: var(--sim-brand-bright); color: #fff; border-color: var(--sim-brand-bright) !important; }

        /* ── Carrusel de tarjetas (productos) ── */
        .sim-cards { display: flex; gap: .5rem; overflow-x: auto; padding: .25rem 0; margin-top: .4rem; }
        .sim-pcard { flex: 0 0 200px; max-width: 200px; border: 1px solid var(--sim-line); border-radius: .6rem; overflow: hidden; background: #fff; }
        .sim-pcard img { width: 100%; height: 110px; object-fit: cover; display: block; background: var(--sim-line-soft); }
        .sim-pcard-body { padding: .5rem .6rem; }
        .sim-pcard-title { font-weight: 600; font-size: .85rem; color: var(--sim-ink); }
        .sim-pcard-sub { font-size: .8rem; color: var(--sim-ink-soft); }
        .sim-pcard-btn { display: block; text-align: center; margin-top: .4rem; font-size: .8rem; padding: .25rem; border-radius: .4rem; background: var(--sim-line-soft); color: var(--sim-ink); text-decoration: none; }
        .sim-pcard-btn:hover { background: var(--sim-line); }
        /* WhatsApp e Instagram apilan las tarjetas (imágenes), Messenger/web las muestran en carrusel horizontal */
        .sim-channel-whatsapp .sim-cards, .sim-channel-instagram .sim-cards { flex-direction: column; }
        .sim-channel-whatsapp .sim-pcard, .sim-channel-instagram .sim-pcard { flex: 0 0 auto; max-width: 100%; }

        /* ── Aviso de límite del canal ── */
        .sim-limit-note { font-size: .72rem; color: var(--sim-brand-deep); background: var(--sim-brand-tint); border: 1px dashed var(--sim-brand-bright); border-radius: .4rem; padding: .3rem .5rem; margin-top: .35rem; }

        /* ── Recibo de lectura (✓✓) ── */
        .sim-msg-receipt { font-size: .7rem; color: var(--sim-ink-faint); margin-top: .15rem; text-align: right; }
        .sim-channel-whatsapp .sim-msg-out .sim-msg-receipt { color: #34b7f1; }

        /* ── Reloj simulado activo ── */
        .sim-clock-badge { font-size: .72rem; color: var(--sim-ink-soft); }

        /* ── Entrada de la conversacion: un unico gesto, no animaciones sueltas ── */
        #sim-chat:not(.d-none) { animation: simRise .28s ease-out; }
        @keyframes simRise { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }

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

        /* Esta pagina es un documento HTML autonomo (no extiende layouts.theme
           ni carga conversations.css), asi que las utilidades .bv-* que usa
           se definen aqui en vez de depender del CSS interno del panel. */
        .bv-fs-60  { font-size: .6rem; }
        .bv-fs-75  { font-size: .75rem; }
        .bv-fs-100 { font-size: 1rem; }
        .bv-nowrap { white-space: nowrap; }
        .bv-pos-relative { position: relative; }
        .bv-w-140 { width: 140px; }
        .bv-sim-dropzone {
            position: absolute;
            inset: 0;
            background: var(--sim-brand-tint);
            border: 2px dashed var(--sim-brand-bright);
            border-radius: var(--r-lg);
            z-index: 20;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            color: var(--sim-brand-deep);
            font-weight: 600;
        }
        .bv-sim-scroll-badge {
            position: absolute;
            bottom: 80px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10;
        }
        .bv-sim-inject-panel {
            background: var(--sim-brand-tint);
            border: 1px dashed var(--sim-brand-bright);
            border-radius: var(--r-sm);
        }
        .bv-sim-message {
            resize: none;
            overflow: hidden;
            max-height: 120px;
        }
    </style>
</head>
<body>
<div class="container sim-wrap py-4">

    <div class="sim-page-head">
        <div class="sim-mark" aria-hidden="true"></div>
        <div>
            <h1>Simulador de canales</h1>
            <p>Simula un mensaje entrante por cualquier canal y conversa en tiempo real con el agente.</p>
        </div>
    </div>

    {{-- ── Panel sesiones recientes ── --}}
    <div id="sim-sessions-panel" class="sim-sessions-panel d-none">
        <div class="sim-sessions-header">
            <span class="text-muted small fw-semibold"><i class="fas fa-clock-rotate-left me-1"></i>Sesiones recientes</span>
            <div class="d-flex align-items-center gap-2">
                <input type="text" id="sim-sessions-search" class="form-control form-control-sm bv-w-140" placeholder="Buscar…">
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
                <label class="form-label" id="sim-channel-group-label">Canal</label>
                <div class="sim-channel-group" role="group" aria-labelledby="sim-channel-group-label">
                    <button type="button" class="sim-channel-btn active" data-channel="whatsapp"><i class="fab fa-whatsapp"></i><span>WhatsApp</span></button>
                    <button type="button" class="sim-channel-btn" data-channel="facebook"><i class="fab fa-facebook-messenger"></i><span>Messenger</span></button>
                    <button type="button" class="sim-channel-btn" data-channel="instagram"><i class="fab fa-instagram"></i><span>Instagram</span></button>
                    <button type="button" class="sim-channel-btn" data-channel="web"><i class="fas fa-globe"></i><span>Website</span></button>
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
                    <summary><i class="fas fa-link me-1"></i> Vincular con tienda o gestión (opcional)</summary>
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
                        <i class="fas fa-clock me-1 text-muted"></i>Hora simulada
                    </label>
                    <input type="datetime-local" class="form-control" id="sim-simulated-datetime">
                    <div class="form-text">Opcional, hora del servidor. Fija una fecha/hora para probar la rama dentro/fuera del horario de atención.</div>
                </div>

                <button type="submit" class="btn btn-lg w-100 mt-4 sim-btn-primary" id="sim-start-btn">
                    Iniciar conversación
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
                <div class="sim-toolbar">
                    <button class="btn" id="sim-share-btn" title="Copiar enlace"><i class="fas fa-share-nodes"></i></button>
                    <button class="btn" id="sim-export-btn" title="Exportar conversación"><i class="fas fa-download"></i></button>
                    <button class="btn" id="sim-dev-btn" title="Herramientas de desarrollo"><i class="fas fa-flask"></i></button>
                    <button class="btn sim-reset-btn" id="sim-reset">Nueva</button>
                </div>
            </div>
        </div>
        <div class="card-body bv-pos-relative">
            <div id="sim-dropzone" class="d-none bv-sim-dropzone">
                <div><i class="fas fa-cloud-upload-alt fa-2x mb-2 d-block text-center"></i>Suelta el archivo aquí</div>
            </div>
            <div id="sim-scroll-badge" class="d-none bv-sim-scroll-badge">
                <button type="button" class="btn btn-sm btn-dark opacity-90 rounded-pill px-3" id="sim-scroll-btn">
                    <span id="sim-scroll-count">0</span> nuevos
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
            <div id="sim-inject-panel" class="d-none mb-2 p-2 bv-sim-inject-panel">
                <div class="d-flex align-items-center gap-1 mb-1">
                    <i class="fas fa-flask sim-brand bv-fs-75"></i>
                    <span class="sim-brand fw-semibold bv-fs-75">Simular respuesta del agente</span>
                </div>
                <div class="d-flex gap-2">
                    <input type="text" class="form-control form-control-sm" id="sim-inject-msg" placeholder="Escribe la respuesta del agente…" maxlength="2000">
                    <button type="button" class="btn btn-sm sim-btn-primary bv-nowrap" id="sim-inject-btn">Inyectar</button>
                </div>
            </div>
            <div id="sim-resolved-banner" class="sim-resolved-banner d-none">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <span class="text-success small"><i class="fas fa-check-circle me-1"></i>Esta conversación fue resuelta. ¿Necesitas ayuda adicional?</span>
                    <button type="button" class="btn btn-sm sim-btn-primary" id="sim-new-conv-btn">Nueva conversación</button>
                </div>
            </div>
            <form id="sim-send-form" class="d-flex gap-2 align-items-end bv-pos-relative">
                <input type="file" id="sim-file-input" accept="image/*,video/*,audio/*,.pdf,.doc,.docx" class="d-none">
                <div class="sim-composer">
                    <label for="sim-file-input" class="sim-btn-outline" title="Adjuntar archivo" id="sim-file-btn">
                        <i class="fas fa-paperclip"></i>
                    </label>
                    <button type="button" class="sim-btn-outline" id="sim-audio-btn" title="Grabar nota de voz">
                        <i class="fas fa-microphone" id="sim-audio-icon"></i>
                    </button>
                    <button type="button" class="sim-btn-outline bv-fs-100" id="sim-emoji-btn" title="Emojis">😊</button>
                    <textarea class="form-control bv-sim-message" id="sim-message" maxlength="2000" placeholder="Escribe un mensaje como cliente…" autocomplete="off" rows="1"></textarea>
                </div>
                <div id="sim-emoji-picker" class="sim-emoji-picker d-none">
                    <input type="text" class="sim-emoji-search" id="sim-emoji-search" placeholder="Buscar emoji…" autocomplete="off">
                    <div class="sim-emoji-grid" id="sim-emoji-grid"></div>
                </div>
                <button type="submit" class="btn sim-btn-primary rounded-circle" id="sim-send-btn"><i class="fas fa-paper-plane"></i></button>
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

<script src="{{ asset('vendor/helpdesk/public-simulator.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/public-simulator.js')) }}" defer></script>
</body>
</html>

{{-- Panel de artículos sugeridos (knowledge base + helpcenter) para el composer.
     Incluido desde thread.blade.php dentro de .bv-composer; el botón que lo abre
     se inyecta en la barra del composer vía el stack hd-composer-toolbar-buttons.
     La lógica vive en public/vendor/helpdesk/kb-suggestions.js (archivo propio). --}}
<div class="bv-kb-suggest" id="bv-kb-suggest-panel">
    <div class="bv-kb-suggest__head">
        <i class="far fa-lightbulb"></i>
        <span>Artículos sugeridos</span>
        <button type="button" class="bv-kb-suggest__close" id="bv-kb-suggest-close" aria-label="Cerrar artículos sugeridos">
            <i class="fas fa-xmark"></i>
        </button>
    </div>
    <div class="bv-kb-suggest__body" id="bv-kb-suggest-body"
         data-bv-kb-url-template="{{ route('manager.helpdesk.conversations.suggested-articles', ['conversation' => '__CONV__']) }}">
        <div class="bv-kb-suggest__state" id="bv-kb-suggest-state">
            <i class="fas fa-spinner fa-spin"></i> Buscando artículos relevantes…
        </div>
        <div class="bv-kb-suggest__list" id="bv-kb-suggest-list"></div>
    </div>
</div>

@push('hd-composer-toolbar-buttons')
<button class="btn-ico" type="button" id="bv-btn-kb-suggest" data-bv-tip="Artículos sugeridos" aria-label="Artículos sugeridos" aria-expanded="false" aria-controls="bv-kb-suggest-panel">
    <i class="far fa-lightbulb" aria-hidden="true"></i>
</button>
@endpush

@once
@push('styles')
<style>
    .bv-kb-suggest { display: none; border-bottom: 1px solid var(--bv-border); background: var(--bv-bg-panel); animation: bv-pop-in .18s ease; }
    .bv-kb-suggest.on { display: block; }
    .bv-kb-suggest__head { display: flex; align-items: center; gap: 8px; padding: 10px 12px; font-weight: 600; font-size: 13px; border-bottom: 1px solid var(--bv-border); }
    .bv-kb-suggest__head .bv-kb-suggest__close { margin-left: auto; background: none; border: 0; cursor: pointer; color: inherit; opacity: .7; }
    .bv-kb-suggest__body { max-height: 260px; overflow-y: auto; padding: 8px 12px; }
    .bv-kb-suggest__state { padding: 10px 4px; font-size: 13px; opacity: .8; }
    .bv-kb-suggest__item { display: flex; gap: 10px; align-items: flex-start; padding: 8px 6px; border-radius: 8px; }
    .bv-kb-suggest__item:hover { background: rgba(125, 125, 125, .08); }
    .bv-kb-suggest__meta { flex: 1; min-width: 0; }
    .bv-kb-suggest__title { font-size: 13px; font-weight: 600; margin-bottom: 2px; overflow-wrap: anywhere; }
    .bv-kb-suggest__excerpt { font-size: 12px; opacity: .75; overflow-wrap: anywhere; }
    .bv-kb-suggest__src { font-size: 10px; text-transform: uppercase; letter-spacing: .04em; opacity: .55; }
    .bv-kb-suggest__actions { display: flex; gap: 4px; flex-shrink: 0; }
    .bv-kb-suggest__actions button { border: 1px solid var(--bv-border); background: none; border-radius: 6px; padding: 3px 8px; font-size: 11px; cursor: pointer; color: inherit; }
    .bv-kb-suggest__actions button:hover { background: rgba(125, 125, 125, .12); }
</style>
@endpush
@push('scripts')
<script src="{{ asset('vendor/helpdesk/kb-suggestions.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/kb-suggestions.js')) }}" defer></script>
@endpush
@endonce

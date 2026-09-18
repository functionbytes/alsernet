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

{{-- El CSS vivía aquí en un @push('styles') que nunca llega al <head> en
     los parciales del inbox (gotcha conocido) — movido a conversations.css. --}}
@once
@push('scripts')
<script src="{{ asset('vendor/helpdesk/kb-suggestions.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/kb-suggestions.js')) }}" defer></script>
@endpush
@endonce

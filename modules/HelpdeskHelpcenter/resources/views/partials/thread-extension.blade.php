{{-- HelpdeskHelpcenter — injects article search into Helpdesk thread --}}

@push('hd-composer-toolbar-buttons')
<button class="btn-ico" type="button" data-bv-tip="Buscar artículo de ayuda" aria-label="Buscar artículo de ayuda" onclick="openArticleModal()">
    <i class="fas fa-book-open" aria-hidden="true"></i>
</button>
@endpush

@push('hd-thread-modals')
<style>
.hd-article-empty { text-align: center; padding: 16px; color: #9aa0ab; font-size: 13px; }
</style>
<div class="hd-overlay" id="hdArticleOverlay">
    <div class="hd-modal w-md">
        <div class="modal-head">
            <div class="modal-icon"><i class="fas fa-book-open"></i></div>
            <div class="modal-title-wrap">
                <span class="modal-label">HELPDESK · BASE DE CONOCIMIENTO</span>
                <span class="modal-title">Buscar artículo de ayuda</span>
            </div>
            <button class="modal-close" onclick="closeArticleModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div class="search-field">
                <i class="fa-solid fa-magnifying-glass sf-ic"></i>
                <input class="finput" id="hdArticleSearch" placeholder="Buscar artículo…" autocomplete="off">
            </div>
            <div class="flat-col" id="hdArticleList">
                <div class="hd-article-empty">Escribe para buscar…</div>
            </div>
            <div class="field">
                <label class="flabel">Vista previa <span class="hint">Se insertará el enlace al artículo</span></label>
                <textarea class="finput" id="hdArticlePreview" rows="2" placeholder="Selecciona un artículo…" readonly></textarea>
            </div>
        </div>
        <div class="modal-foot">
            <div class="hint-txt">
                <span class="kbd">↑↓</span> navegar &nbsp;
                <span class="kbd">↵</span> insertar
            </div>
            <div class="ml"></div>
            <button class="btn btn-ghost btn-sm" onclick="closeArticleModal()">Cancelar</button>
            <button class="btn btn-primary btn-sm" onclick="hdInsertArticle()">
                <i class="fa-solid fa-arrow-right-to-bracket"></i> Insertar enlace
            </button>
        </div>
    </div>
</div>
@endpush

@push('hd-thread-scripts')
    {{-- Bootstrap minimo de datos (URL de route()) que thread-extension.js no
         puede resolver por su cuenta — toda la logica vive ahi. --}}
    @php
        $hcThreadConfig = [
            'searchUrl' => route('manager.helpcenter.articles.search'),
        ];
    @endphp
    <script>window.HelpcenterThreadConfig = @json($hcThreadConfig);</script>
    <script src="{{ asset('modules/helpdeskhelpcenter/js/thread-extension.js') }}?v={{ filemtime(public_path('modules/helpdeskhelpcenter/js/thread-extension.js')) }}"></script>
@endpush

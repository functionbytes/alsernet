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
<script>
(function() {
    var hdArticleAll = [], hdArticleActive = -1, hdArticleTimer = null;
    var hdArticleSearchRoute = '{{ route("manager.helpcenter.articles.search") }}';
    var hdCsrfArt = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '';

    document.getElementById('hdArticleOverlay').addEventListener('click', function(e) {
        if (e.target === this) { closeArticleModal(); }
    });

    window.openArticleModal = function() {
        document.getElementById('hdArticleOverlay').classList.add('open');
        var inp = document.getElementById('hdArticleSearch');
        inp.value = '';
        inp.focus();
        if (!hdArticleAll.length) { hdArticleFetch(''); }
    };

    window.closeArticleModal = function() {
        document.getElementById('hdArticleOverlay').classList.remove('open');
    };

    function hdArticleFetch(q) {
        var url = hdArticleSearchRoute + (q ? '?q=' + encodeURIComponent(q) : '');
        document.getElementById('hdArticleList').innerHTML = '<div class="hd-article-empty">Cargando…</div>';
        fetch(url, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': hdCsrfArt } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                hdArticleAll = data.data || [];
                hdArticleRender(hdArticleAll);
            })
            .catch(function() {
                document.getElementById('hdArticleList').innerHTML = '<div class="hd-article-empty">Error al cargar artículos</div>';
            });
    }

    function hdArticleRender(list) {
        hdArticleActive = list.length ? 0 : -1;
        var el = document.getElementById('hdArticleList');
        if (!list.length) {
            el.innerHTML = '<div class="hd-article-empty">Sin resultados</div>';
            document.getElementById('hdArticlePreview').value = '';
            return;
        }
        function esc(s) { return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }
        el.innerHTML = list.map(function(a, i) {
            return '<div class="row-pick ' + (i === 0 ? 'on' : '') + '" data-idx="' + i + '" onclick="hdArticleSelect(' + i + ')">'
                + '<div class="body">'
                + '<span class="t">' + esc(a.title) + '</span>'
                + '<span class="s">' + esc(a.excerpt || '') + '</span>'
                + '</div></div>';
        }).join('');
        document.getElementById('hdArticlePreview').value = list[0] ? list[0].title + '\n' + list[0].url : '';
    }

    window.hdArticleSelect = function(idx) {
        hdArticleActive = idx;
        document.querySelectorAll('#hdArticleList .row-pick').forEach(function(el, i) {
            el.classList.toggle('on', i === idx);
        });
        var a = hdArticleAll[idx];
        document.getElementById('hdArticlePreview').value = a ? a.title + '\n' + a.url : '';
    };

    window.hdInsertArticle = function() {
        var a = hdArticleAll[hdArticleActive];
        if (!a) { return; }
        var txt = a.title + ': ' + a.url;
        var ta = document.querySelector('.bv-composer-input');
        if (ta) {
            var cur = ta.value;
            ta.value = cur ? cur + '\n' + txt : txt;
            ta.focus();
            ta.dispatchEvent(new Event('input'));
        }
        closeArticleModal();
    };

    var hdArticleInp = document.getElementById('hdArticleSearch');
    hdArticleInp.addEventListener('input', function() {
        clearTimeout(hdArticleTimer);
        var q = this.value.trim();
        hdArticleTimer = setTimeout(function() { hdArticleFetch(q); }, 250);
    });
    hdArticleInp.addEventListener('keydown', function(e) {
        if (e.key === 'ArrowDown') { e.preventDefault(); if (hdArticleActive < hdArticleAll.length - 1) { hdArticleSelect(hdArticleActive + 1); } }
        else if (e.key === 'ArrowUp') { e.preventDefault(); if (hdArticleActive > 0) { hdArticleSelect(hdArticleActive - 1); } }
        else if (e.key === 'Enter') { e.preventDefault(); hdInsertArticle(); }
        else if (e.key === 'Escape') { closeArticleModal(); }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && document.getElementById('hdArticleOverlay').classList.contains('open')) {
            closeArticleModal();
        }
    });
})();
</script>
@endpush

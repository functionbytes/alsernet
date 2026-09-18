/**
 * Buscador de articulos de ayuda insertado en el composer del thread del
 * inbox (helpdeskhelpcenter::partials.thread-extension, incluido desde
 * helpdesk::helpdesk.inbox.partials.thread). Extraido del <script> inline
 * de esa vista.
 *
 * Depende de window.HelpcenterThreadConfig.searchUrl (sembrado por un
 * bootstrap inline minimo en la propia vista con la URL de
 * route('manager.helpcenter.articles.search') — lo unico que este fichero
 * no puede resolver por su cuenta) y de que el DOM de thread-extension
 * (overlay #hdArticleOverlay, input #hdArticleSearch, etc.) ya este
 * presente en la pagina.
 */
(function () {
    var hdArticleAll = [], hdArticleActive = -1, hdArticleTimer = null;
    var hdArticleSearchRoute = (window.HelpcenterThreadConfig || {}).searchUrl || '';
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

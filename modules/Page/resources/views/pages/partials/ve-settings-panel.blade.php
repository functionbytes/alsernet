<div id="ve-settings-panel" class="ve-panel-root">

    <div class="ve-panel-header">
        <div>
            <div class="ve-panel-label">Ajustes</div>
            <span class="ve-panel-title">Configuración de página</span>
        </div>
        <button type="button" class="btn ve-panel-action-btn ve-code-apply-btn" id="btn-save-settings" title="Guardar configuración">
            <i class="fa-solid fa-save"></i>
        </button>
    </div>

    <div class="ve-scrollable-area ve-settings-body">

        {{-- Basic --}}
        <div class="ve-section-title">Básico</div>

        <div class="ve-field">
            <label>Título de la página</label>
            <input type="text" class="form-control" id="ve-settings-title"
                   value="{{ $translation->title ?? $page->title }}" placeholder="Título...">
        </div>

        <div class="ve-field">
            <label>Estado</label>
            <select class="form-select" id="ve-settings-status">
                <option value="published" {{ $page->status->value === 'published' ? 'selected' : '' }}>Publicado</option>
                <option value="draft"     {{ $page->status->value === 'draft'     ? 'selected' : '' }}>Borrador</option>
                <option value="scheduled" {{ ($page->status->value ?? '') === 'scheduled' ? 'selected' : '' }}>Programado</option>
            </select>
        </div>

        <div class="ve-field">
            <label>Permalink (slug)</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text text-muted" style="font-size:10px;max-width:80px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ url('/') }}/">{{ url('/') }}/</span>
                <input type="text" class="form-control" id="ve-settings-slug"
                       value="{{ $translation->slug ?? $page->slug }}" placeholder="slug-de-la-pagina">
            </div>
        </div>

        {{-- Scheduled publish date (visible only when status=scheduled) --}}
        <div class="ve-field" id="ve-scheduled-field" style="display:none;">
            <label>Fecha de publicación</label>
            <input type="datetime-local" class="form-control" id="ve-settings-publish-at"
                   value="{{ $page->publish_at ? $page->publish_at->format('Y-m-d\TH:i') : '' }}">
        </div>

        <div class="ve-field ve-field-last">
            <label>Plantilla</label>
            <select class="form-select" id="ve-settings-template">
                <option value="default" {{ ($page->template ?? 'default') === 'default' ? 'selected' : '' }}>Default</option>
                <option value="homepage" {{ ($page->template ?? '') === 'homepage' ? 'selected' : '' }}>Homepage</option>
                <option value="landing" {{ ($page->template ?? '') === 'landing' ? 'selected' : '' }}>Landing</option>
                <option value="fullwidth" {{ ($page->template ?? '') === 'fullwidth' ? 'selected' : '' }}>Full Width</option>
            </select>
        </div>

        {{-- SEO --}}
        <div class="ve-section-title ve-section-title-bordered">SEO</div>
        <p class="ve-seo-subtitle">Optimización para buscadores</p>

        {{-- SEO Score card --}}
        <div class="ve-seo-score-card">
            <div class="ve-seo-score-left">
                <div class="ve-seo-score-label">Score SEO</div>
                <div class="ve-seo-score-value" id="ve-seo-score-num">--</div>
                <div class="ve-seo-score-grade" id="ve-seo-score-grade">--</div>
            </div>
            <div class="ve-seo-score-ring" id="ve-seo-score-ring">
                <span id="ve-seo-score-letter">?</span>
            </div>
        </div>

        {{-- SERP Preview (Google snippet) --}}
        <div class="ve-serp-card">
            <div class="ve-serp-label">Vista previa en Google</div>
            <div class="ve-serp-url" id="ve-serp-url">{{ url('/') }} › {{ $translation->slug ?? $page->slug }}</div>
            <div class="ve-serp-title" id="ve-seo-preview-title">{{ $translation->seo_title ?? $page->seo_title ?? $page->title }}</div>
            <div class="ve-serp-desc" id="ve-seo-preview-desc">{{ Str::limit($translation->seo_description ?? $page->seo_description ?? '', 155) }}</div>
            @php
                $serpTitle = $translation->seo_title ?? $page->seo_title ?? $page->title ?? '';
                $serpDesc  = $translation->seo_description ?? $page->seo_description ?? '';
                $tLen = mb_strlen($serpTitle);
                $dLen = mb_strlen($serpDesc);
            @endphp
            <div class="ve-serp-chars">
                <span id="ve-serp-title-chars" class="ve-serp-char-badge{{ $tLen > 60 ? ' over' : '' }}">{{ $tLen }}/60</span>
                <span id="ve-serp-desc-chars" class="ve-serp-char-badge{{ $dLen > 155 ? ' over' : '' }}">{{ $dLen }}/155</span>
            </div>
        </div>

        {{-- Edit SEO button (toggles detail fields) --}}
        <button type="button" class="btn ve-btn-primary w-100 mb-3" id="btn-toggle-seo-fields">
            Editar SEO
        </button>

        {{-- SEO fields (hidden by default) --}}
        <div id="ve-seo-fields" class="ve-hidden">
            <div class="ve-field">
                <label>Meta título</label>
                <input type="text" class="form-control" id="ve-settings-seo-title"
                       value="{{ $translation->seo_title ?? $page->seo_title ?? '' }}" placeholder="Igual al título si está vacío">
            </div>
            <div class="ve-field">
                <label>Meta descripción</label>
                <textarea class="form-control ve-textarea-resizable" id="ve-settings-seo-description"
                          rows="3" placeholder="Descripción para buscadores (max 160 caracteres)...">{{ $translation->seo_description ?? $page->seo_description ?? '' }}</textarea>
                <div id="ve-seo-desc-count" class="ve-field-counter">0 / 160</div>
            </div>
            <div class="ve-field">
                <label>Keywords</label>
                <div class="ve-tag-input-wrap" id="ve-tag-input-wrap">
                    <div id="ve-tags-container" style="display:contents;"></div>
                    <input type="text" id="ve-tag-input-field" class="ve-tag-input-field" placeholder="Agregar y Enter...">
                </div>
                <input type="hidden" id="ve-settings-seo-keywords" value="{{ $translation->seo_keywords ?? $page->seo_keywords ?? '' }}">
            </div>
            <div class="ve-field ve-field-last">
                <label>Imagen destacada (URL)</label>
                <div class="input-group">
                    <input type="url" class="form-control" id="ve-settings-featured-image"
                           value="{{ $page->featured_image ?? '' }}" placeholder="https://...">
                    <button type="button" class="btn btn-outline-secondary" id="btn-settings-media-picker" title="Seleccionar de medios">
                        <i class="fa-solid fa-photo-video"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- OG Preview --}}
        <div class="ve-collapsible-section">
            <div id="ve-og-header" class="ve-collapsible-header">
                <span class="ve-collapsible-label">VISTA PREVIA OG</span>
                <i class="fa-solid fa-chevron-down ve-collapsible-chevron" id="ve-og-chevron"></i>
            </div>
            <div id="ve-og-body" class="ve-collapsible-body">
                <div class="ve-og-tab-row">
                    <button type="button" class="btn btn-sm btn-outline-secondary ve-og-tab active" data-tab="fb">Facebook</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary ve-og-tab" data-tab="tw">Twitter</button>
                </div>
                {{-- Facebook card --}}
                <div id="ve-og-fb" class="ve-og-card ve-og-card-fb">
                    <div id="ve-og-fb-img" class="ve-og-img-area">
                        <i class="fa-solid fa-image text-muted"></i>
                    </div>
                    <div class="ve-og-fb-body">
                        <div id="ve-og-fb-domain" class="ve-og-fb-domain">ejemplo.com</div>
                        <div id="ve-og-fb-title" class="ve-og-fb-title">Título de la página</div>
                        <div id="ve-og-fb-desc" class="ve-og-fb-desc">Descripción de la página</div>
                    </div>
                </div>
                {{-- Twitter card --}}
                <div id="ve-og-tw" class="ve-og-card ve-og-card-tw ve-hidden">
                    <div id="ve-og-tw-img" class="ve-og-img-area">
                        <i class="fa-solid fa-image text-muted"></i>
                    </div>
                    <div class="ve-og-tw-body">
                        <div id="ve-og-tw-title" class="ve-og-tw-title">Título de la página</div>
                        <div id="ve-og-tw-desc" class="ve-og-tw-desc">Descripción de la página</div>
                        <div id="ve-og-tw-domain" class="ve-og-tw-domain">ejemplo.com</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- SEO Analysis --}}
        <div class="ve-collapsible-section">
            <div id="ve-seo-anal-header" class="ve-collapsible-header">
                <span class="ve-collapsible-label">ANÁLISIS SEO</span>
                <div class="ve-collapsible-actions">
                    <button type="button" id="btn-run-seo" class="btn btn-outline-secondary ve-btn-micro" onclick="event.stopPropagation(); runSeoAnalysis();">Analizar</button>
                    <i class="fa-solid fa-chevron-down ve-collapsible-chevron" id="ve-seo-anal-chevron"></i>
                </div>
            </div>
            <div id="ve-seo-anal-body" class="ve-collapsible-body">
                <div id="ve-seo-results" class="ve-seo-results">
                    <p class="text-muted ve-seo-results-hint">Haz clic en "Analizar" para ejecutar el análisis.</p>
                </div>
            </div>
        </div>

    </div>

    <div class="ve-settings-footer">
        <small class="ve-settings-footer-text">
            <i class="fa-solid fa-info-circle me-1"></i>Los cambios se aplican al guardar la página
        </small>
    </div>

</div>

<script>
(function ($) {
    'use strict';

    // ── Scheduled date field visibility ──────────────────────────────
    $('#ve-settings-status').on('change', function () {
        $('#ve-scheduled-field').toggle($(this).val() === 'scheduled');
    }).trigger('change');

    // ── Keywords tag chips ────────────────────────────────────────────
    (function () {
        var $wrap    = $('#ve-tag-input-wrap');
        var $input   = $('#ve-tag-input-field');
        var $hidden  = $('#ve-settings-seo-keywords');

        function getTags() {
            var val = $hidden.val().trim();
            return val ? val.split(',').map(function (t) { return t.trim(); }).filter(Boolean) : [];
        }

        function setTags(tags) {
            $hidden.val(tags.join(', '));
        }

        function renderTags() {
            $('#ve-tags-container').children().remove();
            getTags().forEach(function (tag) {
                var $chip = $('<span style="display:inline-flex;align-items:center;gap:3px;background:#90bb13;color:#fff;border-radius:3px;padding:1px 6px;font-size:11px;">'
                    + $('<span>').text(tag).html()
                    + '<i class="fa-solid fa-times ve-tag-remove" style="cursor:pointer;font-size:9px;margin-left:2px;" data-tag="' + $('<span>').text(tag).html() + '"></i>'
                    + '</span>');
                $('#ve-tags-container').append($chip);
            });
        }

        $wrap.on('click', function () { $input.trigger('focus'); });

        $input.on('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                var tag = $(this).val().trim().replace(/,$/, '');
                if (!tag) return;
                var tags = getTags();
                if (!tags.includes(tag)) { tags.push(tag); setTags(tags); renderTags(); }
                $(this).val('');
            } else if (e.key === 'Backspace' && !$(this).val()) {
                var tags = getTags();
                if (tags.length) { tags.pop(); setTags(tags); renderTags(); }
            }
        });

        $(document).on('click', '.ve-tag-remove', function (e) {
            e.stopPropagation();
            var tag = $(this).data('tag');
            var tags = getTags().filter(function (t) { return t !== tag; });
            setTags(tags); renderTags();
        });

        renderTags();
    }());

    // Count meta description chars
    $('#ve-settings-seo-description').on('input', function () {
        const len = $(this).val().length;
        const $count = $('#ve-seo-desc-count');
        $count.text(len + ' / 160');
        $count.css('color', len > 160 ? '#dc3545' : (len > 130 ? '#ffc107' : '#999'));
    }).trigger('input');

    // Preview featured image
    $('#ve-settings-featured-image').on('input', function () {
        const url = $(this).val().trim();
        if (url) {
            $('#ve-settings-img-thumb').attr('src', url);
            $('#ve-settings-img-preview').show();
        } else {
            $('#ve-settings-img-preview').hide();
        }
        updateOgPreview();
    }).trigger('input');

    // Media picker button in settings
    $('#btn-settings-media-picker').on('click', function () {
        var parentWin = window.parent || window;
        if (parentWin.veOpenMediaManager) {
            parentWin.veOpenMediaManager('settings');
        }
    });

    /* ── OG Preview ──────────────────────────────────────────────── */
    function updateOgPreview() {
        var title  = $('#ve-settings-title').val() || 'Título de la página';
        var desc   = $('#ve-settings-seo-description').val() || 'Descripción de la página';
        var imgUrl = $('#ve-settings-featured-image').val().trim();
        var domain = window.location.hostname || 'ejemplo.com';

        $('#ve-og-fb-title, #ve-og-tw-title').text(title);
        $('#ve-og-fb-desc, #ve-og-tw-desc').text(desc);
        $('#ve-og-fb-domain, #ve-og-tw-domain').text(domain);

        if (imgUrl) {
            var imgHtml = '<img src="' + imgUrl + '" class="ve-og-img-fill">';
            $('#ve-og-fb-img, #ve-og-tw-img').html(imgHtml);
        } else {
            var placeholder = '<i class="fa-solid fa-image text-muted"></i>';
            $('#ve-og-fb-img, #ve-og-tw-img').html(placeholder);
        }
    }

    /* ── SERP Preview live update ────────────────────────────────── */
    var BASE_URL = '{{ url("/") }}';

    function updateSerpPreview() {
        var title  = $('#ve-settings-seo-title').val() || $('#ve-settings-title').val() || 'Título de la página';
        var desc   = $('#ve-settings-seo-description').val() || '';
        var slug   = $('#ve-settings-slug').val() || '';
        var tLen   = title.length;
        var dLen   = desc.length;

        var breadcrumb = BASE_URL.replace(/^https?:\/\//, '') + (slug ? ' › ' + slug : '');
        $('#ve-serp-url').text(breadcrumb);
        $('#ve-seo-preview-title').text(title).toggleClass('over-limit', tLen > 60);
        $('#ve-seo-preview-desc').text(desc || 'Sin descripción meta configurada.');
        $('#ve-serp-title-chars').text(tLen + '/60').toggleClass('over', tLen > 60);
        $('#ve-serp-desc-chars').text(dLen + '/155').toggleClass('over', dLen > 155);
    }

    $('#ve-settings-title, #ve-settings-seo-title, #ve-settings-seo-description, #ve-settings-slug').on('input', function () {
        updateOgPreview();
        updateSerpPreview();
    });

    updateOgPreview();
    updateSerpPreview();

    // OG collapsible
    $('#ve-og-header').on('click', function () {
        var $body = $('#ve-og-body');
        var $chev = $('#ve-og-chevron');
        if ($body.is(':visible')) {
            $body.slideUp(150);
            $chev.addClass('ve-chevron-collapsed');
        } else {
            $body.slideDown(150);
            $chev.removeClass('ve-chevron-collapsed');
            updateOgPreview();
        }
    });

    // OG tabs
    $(document).on('click', '.ve-og-tab', function () {
        $('.ve-og-tab').removeClass('active');
        $(this).addClass('active');
        var tab = $(this).data('tab');
        if (tab === 'fb') {
            $('#ve-og-fb').show();
            $('#ve-og-tw').hide();
        } else {
            $('#ve-og-fb').hide();
            $('#ve-og-tw').show();
        }
    });

    /* ── SEO Analysis ────────────────────────────────────────────── */
    window.runSeoAnalysis = function () {
        var p = window.parent || window;
        var editorData = '';
        try {
            editorData = p.veEditor ? p.veEditor.getData() : '';
        } catch (ex) { editorData = ''; }

        var titleVal   = $('#ve-settings-seo-title').val() || $('#ve-settings-title').val() || '';
        var descVal    = $('#ve-settings-seo-description').val() || '';
        var keywords   = $('#ve-settings-seo-keywords').val() || '';

        // Strip HTML tags
        var textOnly = editorData.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
        var words    = textOnly ? textOnly.split(/\s+/).filter(Boolean) : [];
        var wordCount   = words.length;
        var charCount   = textOnly.length;

        var titleLen = titleVal.length;
        var descLen  = descVal.length;

        var titleColor = titleLen === 0 ? '#888' : (titleLen > 60 ? '#dc3545' : (titleLen >= 50 ? '#ffc107' : '#28a745'));
        var titleMsg   = titleLen === 0 ? 'Sin título' : (titleLen > 60 ? 'Muy largo (' + titleLen + ' car.)' : (titleLen >= 50 ? 'Aceptable (' + titleLen + ' car.)' : 'Ideal (' + titleLen + ' car.)'));

        var descColor  = descLen === 0 ? '#888' : (descLen > 160 ? '#dc3545' : (descLen >= 140 ? '#ffc107' : '#28a745'));
        var descMsg    = descLen === 0 ? 'Sin descripción' : (descLen > 160 ? 'Muy larga (' + descLen + ' car.)' : (descLen >= 140 ? 'Aceptable (' + descLen + ' car.)' : 'Ideal (' + descLen + ' car.)'));

        // Keyword density
        var kws = keywords.split(',').map(function (k) { return k.trim().toLowerCase(); }).filter(Boolean);
        var kwHtml = '';
        if (kws.length && wordCount > 0) {
            kwHtml = '<div class="ve-anal-kw-header"><strong>Densidad de keywords:</strong></div>';
            kws.forEach(function (kw) {
                if (!kw) return;
                var re2   = new RegExp('\\b' + kw.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\b', 'gi');
                var count = (textOnly.match(re2) || []).length;
                var density = wordCount > 0 ? ((count / wordCount) * 100).toFixed(1) : 0;
                var densColor = density > 3 ? '#dc3545' : (density >= 1 ? '#28a745' : '#888');
                kwHtml += '<div class="ve-anal-row">' +
                    '<span class="ve-anal-kw">' + kw + '</span>' +
                    '<span style="color:' + densColor + ';">' + count + ' veces (' + density + '%)</span>' +
                    '</div>';
            });
        }

        // SEO Score
        var score = 0;
        if (titleLen > 0 && titleLen <= 60) score += 25;
        else if (titleLen > 0) score += 10;
        if (descLen > 0 && descLen <= 160) score += 25;
        else if (descLen > 0) score += 10;
        if (wordCount >= 300) score += 25;
        else if (wordCount >= 100) score += 10;
        if (kws.length > 0) score += 15;
        if ($('#ve-settings-featured-image').val().trim()) score += 10;
        score = Math.min(score, 100);
        var scoreColor = score >= 70 ? '#28a745' : (score >= 40 ? '#ffc107' : '#dc3545');

        var html2 = [
            '<div class="ve-anal-row mb-1"><span>Palabras:</span><strong>' + wordCount + '</strong></div>',
            '<div class="ve-anal-row mb-1"><span>Caracteres:</span><strong>' + charCount + '</strong></div>',
            '<div class="ve-anal-row mb-1"><span>Meta título:</span><strong style="color:' + titleColor + ';">' + titleMsg + '</strong></div>',
            '<div class="ve-anal-row mb-2"><span>Meta descripción:</span><strong style="color:' + descColor + ';">' + descMsg + '</strong></div>',
            kwHtml,
            '<div class="ve-anal-score-wrap">',
            '<div class="ve-anal-row mb-1"><strong>Puntuación SEO:</strong><strong style="color:' + scoreColor + ';">' + score + '/100</strong></div>',
            '<div class="ve-anal-bar-track"><div class="ve-anal-bar-fill" style="background:' + scoreColor + ';width:' + score + '%;"></div></div>',
            '</div>',
        ].join('');

        $('#ve-seo-results').html(html2);
        if ($('#ve-seo-anal-body').is(':hidden')) {
            $('#ve-seo-anal-body').slideDown(150);
            $('#ve-seo-anal-chevron').removeClass('ve-chevron-collapsed');
        }
    };

    // SEO Analysis collapsible
    $('#ve-seo-anal-header').on('click', function () {
        var $body = $('#ve-seo-anal-body');
        var $chev = $('#ve-seo-anal-chevron');
        if ($body.is(':visible')) {
            $body.slideUp(150);
            $chev.addClass('ve-chevron-collapsed');
        } else {
            $body.slideDown(150);
            $chev.removeClass('ve-chevron-collapsed');
            window.runSeoAnalysis();
        }
    });

    // Save settings: update PAGE_DATA and trigger full save
    $('#btn-save-settings').on('click', function () {
        // Update PAGE_DATA in parent window
        if (window.parent && window.parent.veUpdatePageData) {
            window.parent.veUpdatePageData({
                title:            $('#ve-settings-title').val().trim(),
                slug:             $('#ve-settings-slug').val().trim(),
                status:           $('#ve-settings-status').val(),
                template:         $('#ve-settings-template').val(),
                seo_title:        $('#ve-settings-seo-title').val().trim(),
                seo_description:  $('#ve-settings-seo-description').val().trim(),
                seo_keywords:     $('#ve-settings-seo-keywords').val().trim(),
                featured_image:   $('#ve-settings-featured-image').val().trim()
            });
        } else {
            // Direct update (panel is inside the same page via @@include)
            if (window.veUpdatePageData) {
                window.veUpdatePageData({
                    title:           $('#ve-settings-title').val().trim(),
                    slug:            $('#ve-settings-slug').val().trim(),
                    status:          $('#ve-settings-status').val(),
                    template:        $('#ve-settings-template').val(),
                    seo_title:       $('#ve-settings-seo-title').val().trim(),
                    seo_description: $('#ve-settings-seo-description').val().trim(),
                    seo_keywords:    $('#ve-settings-seo-keywords').val().trim(),
                    featured_image:  $('#ve-settings-featured-image').val().trim()
                });
            }
        }
        // Trigger full page save
        $('#btn-save').trigger('click');
    });

})(jQuery);
</script>

<style>
/* ── Collapsible sections ─────────────────────────────────────── */
.ve-collapsible-section {
    border-top: 1px solid #f0f0f0;
    margin-top: 4px;
}
.ve-collapsible-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 7px 0;
    cursor: pointer;
    user-select: none;
}
.ve-collapsible-label {
    font-size: 10px;
    font-weight: 600;
    color: #888;
    text-transform: uppercase;
    letter-spacing: .5px;
}
.ve-collapsible-chevron {
    font-size: 10px;
    color: #888;
    transition: transform .2s;
}
.ve-chevron-collapsed {
    transform: rotate(-90deg);
}
.ve-collapsible-actions {
    display: flex;
    align-items: center;
    gap: 6px;
}
.ve-collapsible-body {
    display: none;
    padding-bottom: 12px;
}

/* ── Micro button (analizar) ──────────────────────────────────── */
.ve-btn-micro {
    font-size: 10px !important;
    padding: 1px 8px !important;
}

/* ── OG tab row ───────────────────────────────────────────────── */
.ve-og-tab-row {
    display: flex;
    gap: 6px;
    margin-bottom: 8px;
}

/* ── OG cards ─────────────────────────────────────────────────── */
.ve-og-card-fb {
    border: 1px solid #dddfe2;
    border-radius: 4px;
    font-family: Helvetica, Arial, sans-serif;
}
.ve-og-card-tw {
    border: 1px solid #e1e8ed;
    border-radius: 14px;
    font-family: -apple-system, sans-serif;
}
.ve-og-img-area {
    background: #e9ecef;
    height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.ve-og-img-fill {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.ve-og-fb-body {
    padding: 8px 10px;
    background: #f2f3f5;
}
.ve-og-fb-domain {
    font-size: 10px;
    color: #606770;
    text-transform: uppercase;
    margin-bottom: 2px;
}
.ve-og-fb-title {
    font-size: 12px;
    font-weight: 700;
    color: #1d2129;
    margin-bottom: 2px;
}
.ve-og-fb-desc {
    font-size: 11px;
    color: #606770;
    line-height: 1.3;
}
.ve-og-tw-body {
    padding: 8px 12px;
}
.ve-og-tw-title {
    font-size: 12px;
    font-weight: 700;
    color: #14171a;
    margin-bottom: 2px;
}
.ve-og-tw-desc {
    font-size: 11px;
    color: #657786;
    line-height: 1.3;
    margin-bottom: 2px;
}
.ve-og-tw-domain {
    font-size: 10px;
    color: #657786;
}

/* ── SEO analysis results ─────────────────────────────────────── */
.ve-seo-results {
    font-size: 11px;
}
.ve-seo-results-hint {
    font-size: 11px;
}
.ve-anal-row {
    display: flex;
    justify-content: space-between;
    padding: 2px 0;
}
.ve-anal-kw-header {
    margin-top: 8px;
}
.ve-anal-kw-header strong {
    font-size: 11px;
}
.ve-anal-kw {
    color: #555;
}
.ve-anal-score-wrap {
    margin-top: 10px;
}
.ve-anal-bar-track {
    background: #e9ecef;
    border-radius: 4px;
    height: 8px;
    overflow: hidden;
}
.ve-anal-bar-fill {
    height: 100%;
    border-radius: 4px;
    transition: width .5s;
}

/* ── Settings panel footer ────────────────────────────────────── */
.ve-settings-footer {
    padding: 8px 12px;
    border-top: 1px solid #e9ecef;
    background: #f8f9fa;
    flex-shrink: 0;
}
.ve-settings-footer-text {
    font-size: 10px;
    color: #999;
}
</style>

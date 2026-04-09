<div id="ve-settings-panel" class="ve-panel-root">

    <div class="ve-panel-header">
        <div>
            <div class="ve-panel-label">Ajustes</div>
            <span class="ve-panel-title">Configuración de página</span>
        </div>
        <button type="button" class="btn ve-btn-primary" id="btn-save-settings">
            <i class="fa-duotone fa-solid fa-save me-1"></i>Guardar
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
            <label>Slug (URL)</label>
            <div class="input-group">
                <span class="input-group-text">/</span>
                <input type="text" class="form-control" id="ve-settings-slug"
                       value="{{ $translation->slug ?? $page->slug }}" placeholder="mi-pagina">
            </div>
        </div>

        <div class="ve-field">
            <label>Estado</label>
            <select class="form-select" id="ve-settings-status">
                <option value="published" {{ $page->status->value === 'published' ? 'selected' : '' }}>Publicado</option>
                <option value="draft"     {{ $page->status->value === 'draft'     ? 'selected' : '' }}>Borrador</option>
                <option value="scheduled" {{ ($page->status->value ?? '') === 'scheduled' ? 'selected' : '' }}>Programado</option>
            </select>
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

        {{-- Global Design System --}}
        <div class="ve-section-title ve-section-title-bordered">Design system</div>

        <div class="ve-field">
            <label>Color primario</label>
            <div class="ve-color-input-row">
                <input type="color" id="ve-global-color-primary" value="#b10100" class="ve-color-picker">
                <input type="text" class="form-control" id="ve-global-color-primary-text" value="#b10100" placeholder="#hex">
            </div>
        </div>
        <div class="ve-field">
            <label>Color secundario</label>
            <div class="ve-color-input-row">
                <input type="color" id="ve-global-color-secondary" value="#333333" class="ve-color-picker">
                <input type="text" class="form-control" id="ve-global-color-secondary-text" value="#333333" placeholder="#hex">
            </div>
        </div>
        <div class="ve-field">
            <label>Color de acento</label>
            <div class="ve-color-input-row">
                <input type="color" id="ve-global-color-accent" value="#90bb13" class="ve-color-picker">
                <input type="text" class="form-control" id="ve-global-color-accent-text" value="#90bb13" placeholder="#hex">
            </div>
        </div>
        <div class="ve-field">
            <label>Fuente de títulos</label>
            <select class="form-select" id="ve-global-font-heading">
                <option value="">Predeterminada del tema</option>
                <option value="'Manrope', sans-serif">Manrope</option>
                <option value="'Inter', sans-serif">Inter</option>
                <option value="'Poppins', sans-serif">Poppins</option>
                <option value="'Montserrat', sans-serif">Montserrat</option>
                <option value="'Playfair Display', serif">Playfair Display</option>
                <option value="'Roboto', sans-serif">Roboto</option>
                <option value="'Open Sans', sans-serif">Open Sans</option>
                <option value="'Lato', sans-serif">Lato</option>
            </select>
        </div>
        <div class="ve-field ve-field-last">
            <label>Fuente de cuerpo</label>
            <select class="form-select" id="ve-global-font-body">
                <option value="">Predeterminada del tema</option>
                <option value="'Inter', sans-serif">Inter</option>
                <option value="'Roboto', sans-serif">Roboto</option>
                <option value="'Open Sans', sans-serif">Open Sans</option>
                <option value="'Lato', sans-serif">Lato</option>
                <option value="'Nunito', sans-serif">Nunito</option>
                <option value="'Source Sans Pro', sans-serif">Source Sans Pro</option>
                <option value="system-ui, sans-serif">System UI</option>
            </select>
        </div>

        {{-- SEO --}}
        <div class="ve-section-title ve-section-title-bordered">SEO</div>

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
            <input type="text" class="form-control" id="ve-settings-seo-keywords"
                   value="{{ $translation->seo_keywords ?? $page->seo_keywords ?? '' }}" placeholder="palabra1, palabra2, ...">
        </div>

        <div class="ve-field ve-field-last">
            <label>Imagen destacada (URL)</label>
            <div class="input-group">
                <input type="url" class="form-control" id="ve-settings-featured-image"
                       value="{{ $page->featured_image ?? '' }}" placeholder="https://...">
                <button type="button" class="btn btn-outline-secondary" id="btn-settings-media-picker" title="Seleccionar de medios">
                    <i class="fa-duotone fa-solid fa-photo-video"></i>
                </button>
            </div>
            <div id="ve-settings-img-preview" class="ve-img-preview ve-hidden">
                <img id="ve-settings-img-thumb" src="" alt="" class="ve-img-thumb">
            </div>
        </div>

        {{-- OG Preview --}}
        <div style="border-top:1px solid #f0f0f0; margin-top:4px;">
            <div id="ve-og-header" style="display:flex; align-items:center; justify-content:space-between; padding:7px 0; cursor:pointer; user-select:none;">
                <span style="font-size:10px; font-weight:600; color:#888; text-transform:uppercase; letter-spacing:.5px;">VISTA PREVIA OG</span>
                <i class="fa-duotone fa-solid fa-chevron-down" id="ve-og-chevron" style="font-size:10px; color:#888; transition:transform .2s;"></i>
            </div>
            <div id="ve-og-body" style="display:none; padding-bottom:12px;">
                <div style="display:flex; gap:6px; margin-bottom:8px;">
                    <button type="button" class="btn btn-xs btn-outline-secondary ve-og-tab active" data-tab="fb" style="font-size:10px; padding:2px 8px;">Facebook</button>
                    <button type="button" class="btn btn-xs btn-outline-secondary ve-og-tab" data-tab="tw" style="font-size:10px; padding:2px 8px;">Twitter</button>
                </div>
                {{-- Facebook card --}}
                <div id="ve-og-fb" class="ve-og-card" style="border:1px solid #dddfe2; border-radius:4px; overflow:hidden; font-family:Helvetica,Arial,sans-serif; background:#fff;">
                    <div id="ve-og-fb-img" style="background:#e9ecef; height:100px; display:flex; align-items:center; justify-content:center; overflow:hidden;">
                        <i class="fa-duotone fa-solid fa-image text-muted"></i>
                    </div>
                    <div style="padding:8px 10px; background:#f2f3f5;">
                        <div id="ve-og-fb-domain" style="font-size:10px; color:#606770; text-transform:uppercase; margin-bottom:2px;">ejemplo.com</div>
                        <div id="ve-og-fb-title" style="font-size:12px; font-weight:700; color:#1d2129; margin-bottom:2px;">Título de la página</div>
                        <div id="ve-og-fb-desc" style="font-size:11px; color:#606770; line-height:1.3;">Descripción de la página</div>
                    </div>
                </div>
                {{-- Twitter card --}}
                <div id="ve-og-tw" class="ve-og-card" style="display:none; border:1px solid #e1e8ed; border-radius:14px; overflow:hidden; font-family:-apple-system,sans-serif; background:#fff;">
                    <div id="ve-og-tw-img" style="background:#e9ecef; height:100px; display:flex; align-items:center; justify-content:center; overflow:hidden;">
                        <i class="fa-duotone fa-solid fa-image text-muted"></i>
                    </div>
                    <div style="padding:8px 12px;">
                        <div id="ve-og-tw-title" style="font-size:12px; font-weight:700; color:#14171a; margin-bottom:2px;">Título de la página</div>
                        <div id="ve-og-tw-desc" style="font-size:11px; color:#657786; line-height:1.3; margin-bottom:2px;">Descripción de la página</div>
                        <div id="ve-og-tw-domain" style="font-size:10px; color:#657786;">ejemplo.com</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- SEO Analysis --}}
        <div style="border-top:1px solid #f0f0f0; margin-top:4px;">
            <div id="ve-seo-anal-header" style="display:flex; align-items:center; justify-content:space-between; padding:7px 0; cursor:pointer; user-select:none;">
                <span style="font-size:10px; font-weight:600; color:#888; text-transform:uppercase; letter-spacing:.5px;">ANÁLISIS SEO</span>
                <div style="display:flex; align-items:center; gap:6px;">
                    <button type="button" id="btn-run-seo" class="btn btn-outline-secondary" style="font-size:10px; padding:1px 8px;" onclick="event.stopPropagation(); runSeoAnalysis();">Analizar</button>
                    <i class="fa-duotone fa-solid fa-chevron-down" id="ve-seo-anal-chevron" style="font-size:10px; color:#888; transition:transform .2s;"></i>
                </div>
            </div>
            <div id="ve-seo-anal-body" style="display:none; padding-bottom:12px;">
                <div id="ve-seo-results" style="font-size:11px;">
                    <p class="text-muted" style="font-size:11px;">Haz clic en "Analizar" para ejecutar el análisis.</p>
                </div>
            </div>
        </div>

    </div>

    <div style="padding:8px 12px; border-top:1px solid #e9ecef; background:#f8f9fa; flex-shrink:0;">
        <small style="font-size:10px; color:#999;">
            <i class="fa-duotone fa-solid fa-info-circle me-1"></i>Los cambios se aplican al guardar la página
        </small>
    </div>

</div>

<script>
(function ($) {
    'use strict';

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
            var imgHtml = '<img src="' + imgUrl + '" style="width:100%;height:100%;object-fit:cover;">';
            $('#ve-og-fb-img, #ve-og-tw-img').html(imgHtml);
        } else {
            var placeholder = '<i class="fa-duotone fa-solid fa-image text-muted"></i>';
            $('#ve-og-fb-img, #ve-og-tw-img').html(placeholder);
        }
    }

    $('#ve-settings-title, #ve-settings-seo-description').on('input', function () {
        updateOgPreview();
    });

    updateOgPreview();

    // OG collapsible
    $('#ve-og-header').on('click', function () {
        var $body = $('#ve-og-body');
        var $chev = $('#ve-og-chevron');
        if ($body.is(':visible')) {
            $body.slideUp(150);
            $chev.css('transform', 'rotate(-90deg)');
        } else {
            $body.slideDown(150);
            $chev.css('transform', 'rotate(0deg)');
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
            kwHtml = '<div style="margin-top:8px;"><strong style="font-size:11px;">Densidad de keywords:</strong></div>';
            kws.forEach(function (kw) {
                if (!kw) return;
                var re2   = new RegExp('\\b' + kw.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\b', 'gi');
                var count = (textOnly.match(re2) || []).length;
                var density = wordCount > 0 ? ((count / wordCount) * 100).toFixed(1) : 0;
                var densColor = density > 3 ? '#dc3545' : (density >= 1 ? '#28a745' : '#888');
                kwHtml += '<div style="display:flex;justify-content:space-between;padding:2px 0;">' +
                    '<span style="color:#555;">' + kw + '</span>' +
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
            '<div style="display:flex;justify-content:space-between;margin-bottom:4px;">',
            '<span>Palabras:</span><strong>' + wordCount + '</strong></div>',
            '<div style="display:flex;justify-content:space-between;margin-bottom:4px;">',
            '<span>Caracteres:</span><strong>' + charCount + '</strong></div>',
            '<div style="display:flex;justify-content:space-between;margin-bottom:4px;">',
            '<span>Meta título:</span><strong style="color:' + titleColor + ';">' + titleMsg + '</strong></div>',
            '<div style="display:flex;justify-content:space-between;margin-bottom:8px;">',
            '<span>Meta descripción:</span><strong style="color:' + descColor + ';">' + descMsg + '</strong></div>',
            kwHtml,
            '<div style="margin-top:10px;">',
            '<div style="display:flex;justify-content:space-between;margin-bottom:4px;">',
            '<strong>Puntuación SEO:</strong><strong style="color:' + scoreColor + ';">' + score + '/100</strong></div>',
            '<div style="background:#e9ecef;border-radius:4px;height:8px;overflow:hidden;">',
            '<div style="background:' + scoreColor + ';width:' + score + '%;height:100%;border-radius:4px;transition:width .5s;"></div>',
            '</div></div>',
        ].join('');

        $('#ve-seo-results').html(html2);
        if ($('#ve-seo-anal-body').is(':hidden')) {
            $('#ve-seo-anal-body').slideDown(150);
            $('#ve-seo-anal-chevron').css('transform', 'rotate(0deg)');
        }
    };

    // SEO Analysis collapsible
    $('#ve-seo-anal-header').on('click', function () {
        var $body = $('#ve-seo-anal-body');
        var $chev = $('#ve-seo-anal-chevron');
        if ($body.is(':visible')) {
            $body.slideUp(150);
            $chev.css('transform', 'rotate(-90deg)');
        } else {
            $body.slideDown(150);
            $chev.css('transform', 'rotate(0deg)');
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

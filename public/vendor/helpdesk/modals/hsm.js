/*!
 * Helpdesk · modal "hsm" del inbox.
 *
 * Extraido de resources/views/helpdesk/inbox/partials/modals/hsm.blade.php.
 *
 * OJO: los marcadores {{1}}/{{nombre}} de las plantillas HSM van aqui con
 * llaves normales. En el Blade habia que escribirlos @{{ para que Blade no se
 * los comiera como echo; en un .js no hay Blade, asi que el escape sobra y de
 * hecho romperia el marcador (saldria un @ literal).
 */
(function () {
    var VAR_RE = /\{\{([a-zA-Z0-9_]+)\}\}/g;

    function hsmVarDescMap(varsStr) {
        var map = {};
        if (!varsStr) { return map; }
        varsStr.split(',').forEach(function (item) {
            var parts = item.trim().split(':');
            if (parts.length === 2) {
                map[parts[0].trim()] = parts[1].trim();
            }
        });
        return map;
    }

    function hsmUniqueVars(body) {
        var seen = {}, list = [];
        var m;
        VAR_RE.lastIndex = 0;
        while ((m = VAR_RE.exec(body)) !== null) {
            if (!seen[m[1]]) { seen[m[1]] = true; list.push(m[1]); }
        }
        return list;
    }

    function hsmRenderPreview(body) {
        VAR_RE.lastIndex = 0;
        var html = $('<span>').text(body).html();
        return html.replace(/\{\{([a-zA-Z0-9_]+)\}\}/g, function (match, n) {
            return '<strong>' + match + '</strong>';
        });
    }

    function hsmUpdatePreview() {
        var tpl = $('[data-bv-modal-name="hsm"] .bv-opt.on').data('body') || '';
        var html = $('<span>').text(tpl).html();
        html = html.replace(/\{\{([a-zA-Z0-9_]+)\}\}/g, function (match, n) {
            var $input = $('#hsm-vars-form input[data-var="' + n + '"]');
            var val = $input.val() ? $('<span>').text($input.val()).html() : match;
            return '<strong>' + val + '</strong>';
        });
        $('#hsm-preview-bubble').html(html);
    }

    $(document).on('click', '[data-bv-modal-name="hsm"] .bv-opt', function () {
        $('[data-bv-modal-name="hsm"] .bv-opt').removeClass('on');
        $(this).addClass('on');

        var body = $(this).data('body') || '';
        var descMap = hsmVarDescMap($(this).data('vars') || '');
        var vars = hsmUniqueVars(body);

        $('#hsm-preview-bubble').html(hsmRenderPreview(body));

        var form = '';
        vars.forEach(function (v) {
            var placeholder = descMap[v] || v.replace(/_/g, ' ');
            form += '<div class="bv-hsm-var-row">'
                                + '<span class="bv-var-label">{{' + v + '}}</span>'
                + '<input type="text" placeholder="' + placeholder + '" data-var="' + v + '" class="bv-var-input">'
                + '</div>';
        });
        $('#hsm-vars-form').html(form || '<p class="bv-text-muted bv-x28">Esta plantilla no tiene variables.</p>');
    });

    $(document).on('click', '[data-bv-modal-name="hsm"] .bv-chip', function () {
        $('[data-bv-modal-name="hsm"] .bv-chip').removeClass('on');
        $(this).addClass('on');
        var cat = $(this).data('cat');
        $('[data-bv-modal-name="hsm"] .bv-opt').each(function () {
            $(this).toggle(!cat || $(this).data('cat') === cat);
        });
    });

    $(document).on('input', '#hsm-search', function () {
        var q = $(this).val().toLowerCase();
        $('[data-bv-modal-name="hsm"] .bv-opt').each(function () {
            var name = $(this).find('.name').text().toLowerCase();
            $(this).toggle(!q || name.includes(q));
        });
    });

    $(document).on('input', '#hsm-vars-form .bv-var-input', hsmUpdatePreview);
}());

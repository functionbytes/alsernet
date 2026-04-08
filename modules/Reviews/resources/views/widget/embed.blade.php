(function () {
    var WIDGET_URL = '{{ $apiUrl }}';

    function buildStars(rating) {
        var stars = '';
        for (var i = 1; i <= 5; i++) {
            stars += '<span style="color:' + (i <= rating ? '#f5a623' : '#ccc') + ';font-size:18px;">&#9733;</span>';
        }
        return stars;
    }

    function buildWidgetHtml(data) {
        var cards = '';
        (data.reviews || []).forEach(function (r) {
            var comment = r.comment
                ? '<p style="margin:8px 0 0;font-size:14px;color:#555;line-height:1.5;">' + escapeHtml(r.comment) + '</p>'
                : '';
            cards += '<div style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:16px;margin-bottom:12px;">'
                + '<div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">'
                + '<span style="font-weight:600;font-size:14px;color:#1a202c;">' + escapeHtml(r.reviewer_name || 'Anonimo') + '</span>'
                + '<span>' + buildStars(r.star_rating) + '</span>'
                + '</div>'
                + comment
                + '</div>';
        });

        return '<div style="font-family:sans-serif;max-width:600px;">'
            + '<div style="margin-bottom:16px;">'
            + '<h3 style="margin:0 0 4px;font-size:18px;color:#1a202c;">' + escapeHtml(data.location_name || '') + '</h3>'
            + '<div style="display:flex;align-items:center;gap:8px;">'
            + buildStars(Math.round(data.avg_rating || 0))
            + '<span style="font-size:14px;color:#4a5568;">' + (data.avg_rating ? data.avg_rating.toFixed(1) : '—') + ' (' + (data.review_count || 0) + ' reseñas)</span>'
            + '</div>'
            + '</div>'
            + cards
            + '</div>';
    }

    function escapeHtml(str) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(str));
        return d.innerHTML;
    }

    function createWidget(container) {
        container.innerHTML = '<p style="font-family:sans-serif;color:#999;font-size:13px;">Cargando reseñas...</p>';
        fetch(WIDGET_URL)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                container.innerHTML = buildWidgetHtml(data);
            })
            .catch(function () {
                container.innerHTML = '<p style="font-family:sans-serif;color:#e53e3e;font-size:13px;">No se pudieron cargar las reseñas.</p>';
            });
    }

    document.querySelectorAll('[data-reviews-widget]').forEach(function (el) {
        createWidget(el);
    });
})();

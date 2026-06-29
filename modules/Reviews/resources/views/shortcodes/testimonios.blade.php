@php
$locale = app()->getLocale();
$i18n = [
    'es' => [
        'based_on'       => 'Basado en',
        'reviews'        => 'reseñas',
        'of_google'      => 'de Google',
        'view_maps'      => 'Ver en Google Maps',
        'imported'       => 'Reseñas importadas directamente de Google Maps',
        'all'            => 'Todas',
        'showing'        => 'Mostrando',
        'verified'       => 'reseñas verificadas de Google',
        'review_s'       => ['reseña', 'reseñas'],
        'no_results'     => 'No se encontraron reseñas con ese criterio.',
        'no_results_sub' => 'Prueba con otro filtro o término de búsqueda.',
        'all_cats'       => 'todas las categorías',
        'category'       => 'categoría',
        'with'           => 'con',
        'of'             => 'de',
        'read_more'      => 'Leer más',
        'read_less'      => 'Leer menos',
        'verified_cust'  => 'Cliente verificado',
        'search_ph'      => 'Buscar en reseñas...',
        'cta_title'      => '¿Listo para transformar tu hogar?',
        'cta_sub'        => 'Presupuesto gratuito y sin compromiso. Contacta con nuestro equipo.',
        'cta_btn'        => 'Solicitar presupuesto',
        'cta_url'        => '/contacto',
        'loading'        => 'Cargando reseñas...',
    ],
    'pt' => [
        'based_on'       => 'Baseado em',
        'reviews'        => 'opiniões',
        'of_google'      => 'do Google',
        'view_maps'      => 'Ver no Google Maps',
        'imported'       => 'Opiniões importadas diretamente do Google Maps',
        'all'            => 'Todas',
        'showing'        => 'A mostrar',
        'verified'       => 'opiniões verificadas do Google',
        'review_s'       => ['opinião', 'opiniões'],
        'no_results'     => 'Não foram encontradas opiniões com esse critério.',
        'no_results_sub' => 'Tente outro filtro ou termo de pesquisa.',
        'all_cats'       => 'todas as categorias',
        'category'       => 'categoria',
        'with'           => 'com',
        'of'             => 'de',
        'read_more'      => 'Ler mais',
        'read_less'      => 'Ler menos',
        'verified_cust'  => 'Cliente verificado',
        'search_ph'      => 'Pesquisar opiniões...',
        'cta_title'      => 'Pronto para transformar a sua casa?',
        'cta_sub'        => 'Orçamento gratuito e sem compromisso. Contacte a nossa equipa.',
        'cta_btn'        => 'Solicitar orçamento',
        'cta_url'        => '/orcamento',
        'loading'        => 'A carregar opiniões...',
    ],
    'en' => [
        'based_on'       => 'Based on',
        'reviews'        => 'reviews',
        'of_google'      => 'on Google',
        'view_maps'      => 'View on Google Maps',
        'imported'       => 'Reviews imported directly from Google Maps',
        'all'            => 'All',
        'showing'        => 'Showing',
        'verified'       => 'verified Google reviews',
        'review_s'       => ['review', 'reviews'],
        'no_results'     => 'No reviews found matching that criteria.',
        'no_results_sub' => 'Try a different filter or search term.',
        'all_cats'       => 'all categories',
        'category'       => 'category',
        'with'           => 'with',
        'of'             => 'of',
        'read_more'      => 'Read more',
        'read_less'      => 'Read less',
        'verified_cust'  => 'Verified customer',
        'search_ph'      => 'Search reviews...',
        'cta_title'      => 'Ready to transform your home?',
        'cta_sub'        => 'Free, no-obligation quote. Contact our team.',
        'cta_btn'        => 'Request a quote',
        'cta_url'        => '/quote',
        'loading'        => 'Loading reviews...',
    ],
    'fr' => [
        'based_on'       => 'Basé sur',
        'reviews'        => 'avis',
        'of_google'      => 'sur Google',
        'view_maps'      => 'Voir sur Google Maps',
        'imported'       => 'Avis importés directement depuis Google Maps',
        'all'            => 'Tous',
        'showing'        => 'Affichage de',
        'verified'       => 'avis Google vérifiés',
        'review_s'       => ['avis', 'avis'],
        'no_results'     => 'Aucun avis trouvé avec ce critère.',
        'no_results_sub' => 'Essayez un autre filtre ou terme de recherche.',
        'all_cats'       => 'toutes les catégories',
        'category'       => 'catégorie',
        'with'           => 'avec',
        'of'             => 'de',
        'read_more'      => 'Lire plus',
        'read_less'      => 'Lire moins',
        'verified_cust'  => 'Client vérifié',
        'search_ph'      => 'Rechercher des avis...',
        'cta_title'      => 'Prêt à transformer votre maison ?',
        'cta_sub'        => 'Devis gratuit et sans engagement. Contactez notre équipe.',
        'cta_btn'        => 'Demander un devis',
        'cta_url'        => '/devis',
        'loading'        => 'Chargement des avis...',
    ],
][$locale] ?? [];

$i18n = $i18n ?: [
    'based_on'=>'Basado en','reviews'=>'reseñas','of_google'=>'de Google',
    'view_maps'=>'Ver en Google Maps','imported'=>'Reseñas importadas directamente de Google Maps',
    'all'=>'Todas','showing'=>'Mostrando','verified'=>'reseñas verificadas de Google',
    'review_s'=>['reseña','reseñas'],'no_results'=>'No se encontraron reseñas.',
    'no_results_sub'=>'Prueba con otro filtro.','all_cats'=>'todas las categorías',
    'category'=>'categoría','with'=>'con','of'=>'de',
    'read_more'=>'Leer más','read_less'=>'Leer menos','verified_cust'=>'Cliente verificado',
    'search_ph'=>'Buscar...','cta_title'=>'¿Listo para transformar tu hogar?',
    'cta_sub'=>'Presupuesto gratuito.','cta_btn'=>'Solicitar presupuesto','cta_url'=>'/contacto',
    'loading'=>'Cargando...',
];

// Self-fetch stats if not provided
if (!isset($avgRating) || !isset($totalCount)) {
    [$totalCount, $avgRating] = app(\Modules\Reviews\Http\Controllers\PublicReviewController::class)->getAggregateStats();
}

// Locale-specific tag labels for filter chips
$tagDefs = [
    'ventanas-pvc' => [
        'icon'   => 'fa-border-all',
        'label'  => ['es'=>'Ventanas PVC','pt'=>'Janelas PVC','en'=>'PVC Windows','fr'=>'Fenêtres PVC'][$locale] ?? 'Ventanas PVC',
    ],
    'estores-y-persianas' => [
        'icon'   => 'fa-layer-group',
        'label'  => ['es'=>'Estores','pt'=>'Estores','en'=>'Blinds','fr'=>'Stores'][$locale] ?? 'Estores',
    ],
    'mosquiteras' => [
        'icon'   => 'fa-shield-halved',
        'label'  => ['es'=>'Mosquiteras','pt'=>'Mosquiteiros','en'=>'Mosquito Nets','fr'=>'Moustiquaires'][$locale] ?? 'Mosquiteras',
    ],
    'barandillas-de-vidrio' => [
        'icon'   => 'fa-grip-lines-vertical',
        'label'  => ['es'=>'Barandillas','pt'=>'Guarda-Corpos','en'=>'Railings','fr'=>'Garde-Corps'][$locale] ?? 'Barandillas',
    ],
];
@endphp

<!-- Stats Bar -->
<div class="reviews-stats-bar">
    <div class="container">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-score" id="rp-avg-score">{{ number_format($avgRating, 1) }}</div>
                <div>
                    <div class="stat-stars" id="rp-stars">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="fa-solid fa-star{{ $i <= round($avgRating) ? '' : ' opacity-25' }}"></i>
                        @endfor
                    </div>
                    <div class="stat-count">
                        {{ $i18n['based_on'] }}
                        <strong><span id="rp-total-count">{{ number_format($totalCount) }}</span> {{ $i18n['reviews'] }}</strong>
                        {{ $i18n['of_google'] }}
                    </div>
                </div>
            </div>
            <div class="stat-divider d-none d-md-block"></div>
            @if(theme_option('google_maps_url'))
            <a href="{{ theme_option('google_maps_url') }}" target="_blank" rel="noopener" class="google-badge">
                <svg viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                    <path fill="#EA4335" d="M24 9.5c3.1 0 5.8 1.1 8 2.9l6-6C34.4 3.1 29.5 1 24 1 14.9 1 7.2 6.4 3.7 14.1l7 5.4C12.5 13.3 17.8 9.5 24 9.5z"/>
                    <path fill="#4285F4" d="M46.5 24.5c0-1.6-.1-3.1-.4-4.5H24v8.5h12.7c-.5 2.9-2.2 5.4-4.7 7.1l7.3 5.7c4.3-3.9 6.8-9.7 6.8-16.8z"/>
                    <path fill="#FBBC05" d="M10.7 28.5c-.5-1.6-.8-3.2-.8-5s.3-3.4.8-5l-7-5.4C2.4 16.1 1.5 19.9 1.5 24s.9 7.9 2.3 11l6.9-6.5z"/>
                    <path fill="#34A853" d="M24 47c5.5 0 10.1-1.8 13.5-4.9l-7.3-5.7c-1.9 1.3-4.3 2.1-6.2 2.1-6.2 0-11.5-3.8-13.3-9.5l-6.9 6.5C7.2 41.6 14.9 47 24 47z"/>
                </svg>
                {{ $i18n['view_maps'] }}
            </a>
            @endif
            <div class="ms-auto d-none d-lg-block">
                <span class="verified-badge">
                    <i class="fa-solid fa-shield-halved me-1"></i>{{ $i18n['imported'] }}
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="reviews-filter-bar">
    <div class="container">
        <div class="filter-chips">
            <button class="filter-chip active" data-filter="all">
                <i class="fa-solid fa-th-large"></i> {{ $i18n['all'] }}
                <span class="chip-count" id="rp-count-all">{{ $totalCount }}</span>
            </button>

            @foreach($tagDefs as $slug => $def)
            <button class="filter-chip" data-filter="{{ $slug }}">
                <i class="fa-solid {{ $def['icon'] }}"></i>
                {{ $def['label'] }}
                <span class="chip-count" id="rp-count-{{ $slug }}">0</span>
            </button>
            @endforeach

            <div class="filter-chips-divider d-none d-md-block"></div>
            <div class="filter-search">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" id="rp-search" placeholder="{{ $i18n['search_ph'] }}">
            </div>
        </div>
    </div>
</div>

<!-- Reviews Grid -->
<section class="reviews-grid-section">
    <div class="container">
        <p class="reviews-results-info" id="rp-results-info">
            {{ $i18n['showing'] }} <strong>{{ $totalCount }}</strong> {{ $i18n['verified'] }}
        </p>
        <div class="row g-4" id="rp-grid">
            <div class="col-12 text-center py-5">
                <i class="fa-solid fa-spinner fa-spin fa-2x opacity-50"></i>
                <p class="mt-2 text-muted">{{ $i18n['loading'] }}</p>
            </div>
        </div>
        <div class="no-results" id="rp-no-results" style="display:none">
            <i class="fa-solid fa-magnifying-glass"></i>
            <p>{{ $i18n['no_results'] }}<br><small>{{ $i18n['no_results_sub'] }}</small></p>
        </div>
    </div>
</section>

<script>
(function () {
    var DATA_URL = '/reviews/data?locale={{ $locale }}';
    var I18N = {
        readMore:    @json($i18n['read_more']),
        readLess:    @json($i18n['read_less']),
        verified:    @json($i18n['verified_cust']),
        allCats:     @json($i18n['all_cats']),
        category:    @json($i18n['category']),
        reviewSing:  @json($i18n['review_s'][0]),
        reviewPlur:  @json($i18n['review_s'][1]),
        showing:     @json($i18n['showing']),
        with:        @json($i18n['with']),
        of:          @json($i18n['of']),
    };
    var TAG_LABELS = @json(collect($tagDefs)->mapWithKeys(fn($def, $slug) => [$slug => $def['label']])->all());
    var COLORS = ['av-0','av-1','av-2','av-3','av-4','av-5','av-6','av-7','av-8','av-9','av-10','av-11'];

    var allReviews = [];
    var activeFilter = 'all';
    var searchTerm = '';

    function getInitials(name) {
        return name.split(' ').slice(0,2).map(function(w){ return (w[0]||'').toUpperCase(); }).join('');
    }
    function getColorClass(name) {
        var hash = 0;
        for (var i = 0; i < name.length; i++) { hash = name.charCodeAt(i) + ((hash << 5) - hash); }
        return COLORS[Math.abs(hash) % COLORS.length];
    }
    function toTitleCase(str) {
        return str.replace(/\w\S*/g, function(w){ return w.charAt(0).toUpperCase() + w.slice(1).toLowerCase(); });
    }

    function renderCard(r, idx) {
        var displayName = toTitleCase(r.name || I18N.verified);
        var initials    = getInitials(displayName);
        var colorClass  = getColorClass(r.name || 'default');
        var starsHtml   = '';
        for (var s = 0; s < (r.rating||5); s++) { starsHtml += '<li><i class="fa-solid fa-star"></i></li>'; }
        var tagsAttr = (r.tags||[]).join(',');
        var text = (r.text || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        return '<div class="col-lg-4 col-md-6 rp-card" data-tags="'+tagsAttr+'" data-name="'+r.name.toLowerCase()+'" data-text="'+(r.text||'').toLowerCase()+'">'
            + '<div class="testimonial-inner-boxarea">'
            + '<div class="img1"><div class="avatar-circle '+colorClass+'">'+initials+'</div></div>'
            + '<div class="content-area">'
            + '<ul>'+starsHtml+'</ul>'
            + '<p id="rp-ct-'+idx+'">&ldquo;'+text+'&rdquo;</p>'
            + '<button class="expand-btn" onclick="rpToggle(this,\'rp-ct-'+idx+'\')">'+I18N.readMore+'</button>'
            + '<div class="text"><a href="#">'+displayName+'</a>'
            + '<p>'+( r.date || I18N.verified )+'<span class="card-google-icon"><i class="fa-brands fa-google"></i></span></p>'
            + '</div></div></div></div>';
    }

    window.rpToggle = function(btn, id) {
        var el = document.getElementById(id);
        if (!el) return;
        el.classList.toggle('expanded');
        btn.textContent = el.classList.contains('expanded') ? I18N.readLess : I18N.readMore;
    };

    function applyFilters() {
        var cards = document.querySelectorAll('.rp-card');
        var visible = 0;
        cards.forEach(function(card) {
            var tags        = card.dataset.tags.split(',').filter(Boolean);
            var matchFilter = activeFilter === 'all' || tags.includes(activeFilter);
            var matchSearch = !searchTerm || (card.dataset.name + ' ' + card.dataset.text).includes(searchTerm);
            var show = matchFilter && matchSearch;
            card.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        document.getElementById('rp-no-results').style.display = visible === 0 ? 'block' : 'none';
        var filterLabel = activeFilter === 'all'
            ? I18N.allCats
            : I18N.category + ' <strong>' + (TAG_LABELS[activeFilter] || activeFilter) + '</strong>';
        var searchLabel = searchTerm ? ' ' + I18N.with + ' "<strong>' + searchTerm + '</strong>"' : '';
        var word = visible === 1 ? I18N.reviewSing : I18N.reviewPlur;
        document.getElementById('rp-results-info').innerHTML =
            I18N.showing + ' <strong>' + visible + '</strong> ' + word + ' ' + I18N.of + ' ' + filterLabel + searchLabel;
    }

    function initEvents() {
        document.querySelectorAll('.filter-chip').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-chip').forEach(function(b){ b.classList.remove('active'); });
                btn.classList.add('active');
                activeFilter = btn.dataset.filter;
                applyFilters();
            });
        });
        var searchEl = document.getElementById('rp-search');
        if (searchEl) {
            searchEl.addEventListener('input', function(e) {
                searchTerm = e.target.value.trim().toLowerCase();
                applyFilters();
            });
        }
        document.querySelectorAll('.rp-card .expand-btn').forEach(function(btn) {
            var match = btn.getAttribute('onclick').match(/'(rp-ct-[^']+)'/);
            if (match) {
                var el = document.getElementById(match[1]);
                if (el && el.scrollHeight <= el.clientHeight + 2) btn.style.display = 'none';
            }
        });
    }

    fetch(DATA_URL)
        .then(function(r){ return r.json(); })
        .then(function(data) {
            allReviews = data.reviews || [];

            // Update stats bar
            var scoreEl = document.getElementById('rp-avg-score');
            if (scoreEl) scoreEl.textContent = data.avg.toFixed(1);
            var countEl = document.getElementById('rp-total-count');
            if (countEl) countEl.textContent = data.total;
            var chipAll = document.getElementById('rp-count-all');
            if (chipAll) chipAll.textContent = data.total;

            // Update chip counts
            var tagCounts = data.tag_counts || {};
            Object.keys(tagCounts).forEach(function(slug) {
                var el = document.getElementById('rp-count-' + slug);
                if (el) el.textContent = tagCounts[slug];
            });

            // Render cards
            var grid = document.getElementById('rp-grid');
            if (grid) {
                grid.innerHTML = allReviews.map(function(r, i){ return renderCard(r, i); }).join('');
                initEvents();
                applyFilters();
            }
        })
        .catch(function() {
            var grid = document.getElementById('rp-grid');
            if (grid) grid.innerHTML = '';
        });
})();
</script>

@php
$reviewsJs = $reviews->map(function ($r) use ($localeCode) {
    $translation = $r->translations->firstWhere('locale_code', $localeCode);
    $text = $translation?->translated_text ?? $r->comment ?? '';

    return [
        'name'   => $r->reviewer_name ?? 'Usuario',
        'rating' => $r->star_rating->value(),
        'text'   => $text,
        'tags'   => $r->moderation?->tags ?? [],
        'date'   => $r->review_time?->diffForHumans() ?? '',
    ];
})->values();

// Build lookup maps from available_tags defined for this location
$tagMap = collect($availableTags)->keyBy('slug');
@endphp

<!-- Stats Bar -->
<div class="reviews-stats-bar">
    <div class="container">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-score">{{ number_format($avgRating, 1) }}</div>
                <div>
                    <div class="stat-stars">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="fa-solid fa-star{{ $i <= round($avgRating) ? '' : ' opacity-25' }}"></i>
                        @endfor
                    </div>
                    <div class="stat-count">Basado en <strong>{{ number_format($totalCount) }} reseñas</strong> de Google</div>
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
                Ver en Google Maps
            </a>
            @endif
            <div class="ms-auto d-none d-lg-block">
                <span class="verified-badge">
                    <i class="fa-solid fa-shield-halved me-1"></i>Reseñas importadas directamente de Google Maps
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
                <i class="fa-solid fa-th-large"></i> Todas
                <span class="chip-count" id="count-all-{{ $instanceId }}">{{ $totalCount }}</span>
            </button>

            @foreach($tagCounts as $tag => $count)
            @php $tagDef = $tagMap->get($tag); @endphp
            <button class="filter-chip" data-filter="{{ $tag }}">
                <i class="fa-solid {{ $tagDef['icon'] ?? 'fa-tag' }}"></i>
                {{ $tagDef['label'] ?? ucfirst(str_replace(['-', '_'], ' ', $tag)) }}
                <span class="chip-count" id="count-{{ $tag }}-{{ $instanceId }}">{{ $count }}</span>
            </button>
            @endforeach

            <div class="filter-chips-divider d-none d-md-block"></div>
            <div class="filter-search">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" id="reviewSearch-{{ $instanceId }}" placeholder="Buscar en reseñas...">
            </div>
        </div>
    </div>
</div>

<!-- Reviews Grid -->
<section class="reviews-grid-section">
    <div class="container">
        <p class="reviews-results-info" id="resultsInfo-{{ $instanceId }}">
            Mostrando <strong>{{ $totalCount }}</strong> reseñas verificadas de Google
        </p>
        <div class="row g-4" id="reviewsGrid-{{ $instanceId }}"></div>
        <div class="no-results" id="noResults-{{ $instanceId }}">
            <i class="fa-solid fa-magnifying-glass"></i>
            <p>No se encontraron reseñas con ese criterio.<br><small>Prueba con otro filtro o término de búsqueda.</small></p>
        </div>
    </div>
</section>

<!-- CTA -->
<div class="reviews-cta">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h2>¿Listo para transformar tu hogar?</h2>
                <p>Presupuesto gratuito y sin compromiso. Contacta con nuestro equipo.</p>
            </div>
            <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                <a href="{{ url(theme_option('cta_url', '/contacto')) }}" class="cta-btn">
                    Solicitar presupuesto <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const REVIEWS = {!! json_encode($reviewsJs) !!};
    const iid     = {{ $instanceId }};

    const COLORS = ['av-0','av-1','av-2','av-3','av-4','av-5','av-6','av-7','av-8','av-9','av-10','av-11'];
    const TAG_LABELS = {!! json_encode(collect($availableTags)->pluck('label', 'slug')->all()) !!};

    function getInitials(name) {
        return name.split(' ').slice(0,2).map(w => w[0]?.toUpperCase() || '').join('');
    }
    function getColorClass(name) {
        let hash = 0;
        for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash);
        return COLORS[Math.abs(hash) % COLORS.length];
    }
    function toTitleCase(str) {
        return str.replace(/\w\S*/g, w => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase());
    }

    function renderCard(r, idx) {
        const displayName = toTitleCase(r.name);
        const initials    = getInitials(displayName);
        const colorClass  = getColorClass(r.name);
        const starsHtml   = Array.from({length: r.rating||5}, () => '<li><i class="fa-solid fa-star"></i></li>').join('');
        const tagsAttr    = (r.tags||[]).join(',');

        return `<div class="col-lg-4 col-md-6 review-card-${iid}" data-tags="${tagsAttr}" data-name="${r.name.toLowerCase()}" data-text="${(r.text||'').toLowerCase()}">
            <div class="testimonial-inner-boxarea">
                <div class="img1"><div class="avatar-circle ${colorClass}">${initials}</div></div>
                <div class="content-area">
                    <ul>${starsHtml}</ul>
                    <p id="ct-${iid}-${idx}">&ldquo;${r.text}&rdquo;</p>
                    <button class="expand-btn" onclick="toggleRevText_${iid}(this,'ct-${iid}-${idx}')">Leer más</button>
                    <div class="text">
                        <a href="#">${displayName}</a>
                        <p>${r.date||'Cliente verificado'}<span class="card-google-icon"><i class="fa-brands fa-google"></i></span></p>
                    </div>
                </div>
            </div>
        </div>`;
    }

    window['toggleRevText_' + iid] = function (btn, id) {
        const el = document.getElementById(id);
        el.classList.toggle('expanded');
        btn.textContent = el.classList.contains('expanded') ? 'Leer menos' : 'Leer más';
        if (!el.classList.contains('expanded') && el.scrollHeight <= el.clientHeight + 2) btn.style.display = 'none';
    };

    let activeFilter = 'all';
    let searchTerm   = '';

    function applyFilters() {
        const cards = document.querySelectorAll('.review-card-' + iid);
        let visible = 0;
        cards.forEach(card => {
            const tags        = card.dataset.tags.split(',').filter(Boolean);
            const matchFilter = activeFilter === 'all' || tags.includes(activeFilter);
            const matchSearch = !searchTerm || (card.dataset.name + ' ' + card.dataset.text).includes(searchTerm);
            const show        = matchFilter && matchSearch;
            card.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        document.getElementById('noResults-' + iid).style.display = visible === 0 ? 'block' : 'none';
        const filterLabel = activeFilter === 'all'
            ? 'todas las categorías'
            : `categoría <strong>${TAG_LABELS[activeFilter] || activeFilter}</strong>`;
        const searchLabel = searchTerm ? ` con "<strong>${searchTerm}</strong>"` : '';
        document.getElementById('resultsInfo-' + iid).innerHTML =
            `Mostrando <strong>${visible}</strong> reseña${visible!==1?'s':''} de ${filterLabel}${searchLabel}`;
    }

    document.getElementById('reviewsGrid-' + iid).innerHTML = REVIEWS.map((r,i) => renderCard(r,i)).join('');

    document.querySelectorAll('.filter-chip').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.filter-chip').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            activeFilter = btn.dataset.filter;
            applyFilters();
        });
    });

    const searchEl = document.getElementById('reviewSearch-' + iid);
    if (searchEl) {
        searchEl.addEventListener('input', e => {
            searchTerm = e.target.value.trim().toLowerCase();
            applyFilters();
        });
    }

    document.querySelectorAll('.review-card-' + iid + ' .expand-btn').forEach(btn => {
        const match = btn.getAttribute('onclick').match(/'(ct-[^']+)'/);
        if (match) {
            const el = document.getElementById(match[1]);
            if (el && el.scrollHeight <= el.clientHeight + 2) btn.style.display = 'none';
        }
    });

    applyFilters();
})();
</script>

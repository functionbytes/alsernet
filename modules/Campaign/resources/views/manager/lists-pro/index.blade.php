@extends('campaign::refactor.layout')

@section('title', trans('campaign::lists.page.title'))

@section('page-header')
    <div class="mc-page-header">
        <div>
            <h1 class="mc-page-title">{{ trans('campaign::lists.page.title') }}</h1>
            <p class="mc-page-subtitle">{{ trans('campaign::lists.page.subtitle') }}</p>
        </div>
        <div class="mc-page-actions">
            <a href="{{ route('manager.maillists.index') }}" class="mc-btn mc-btn-secondary mc-btn-sm">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'list', 'size' => 16])
                {{ trans('campaign::lists.back_to_lists') }}
            </a>
        </div>
    </div>
@endsection

@section('content')

    {{-- Banner con stats --}}
    <div class="mc-banner mc-banner-open">
        <div class="mc-banner-content">
            <div class="mc-banner-title">{{ trans('campaign::lists.page.title') }}</div>
            <div class="mc-banner-desc">{{ trans('campaign::lists.page.subtitle') }}</div>
            <div class="mc-banner-meta">
                <span class="mc-banner-meta-item">
                    <span class="material-symbols-rounded">list</span>
                    <span class="mc-banner-meta-value">{{ number_format($stats['total'] ?? 0) }}</span>
                    {{ trans('campaign::lists.stat.total') }}
                </span>
                <span class="mc-banner-meta-item">
                    <span class="material-symbols-rounded">group</span>
                    <span class="mc-banner-meta-value">{{ number_format($stats['subscribers'] ?? 0) }}</span>
                    {{ trans('campaign::lists.stat.subscribers') }}
                </span>
                <span class="mc-banner-meta-item">
                    <span class="material-symbols-rounded">check_circle</span>
                    <span class="mc-banner-meta-value">{{ number_format($stats['active'] ?? 0) }}</span>
                    {{ trans('campaign::lists.stat.active') }}
                </span>
                <span class="mc-banner-meta-item">
                    <span class="material-symbols-rounded">cancel</span>
                    <span class="mc-banner-meta-value">{{ number_format($stats['inactive'] ?? 0) }}</span>
                    {{ trans('campaign::lists.stat.inactive') }}
                </span>
            </div>
        </div>
        <div class="mc-banner-illustration">
            <svg viewBox="0 0 180 140" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="90" cy="70" r="58" fill="var(--illust-teal)"/>
                {{-- List illustration --}}
                <rect x="40" y="30" width="100" height="80" rx="8" fill="var(--color-card-bg)" stroke="var(--illust-stroke)" stroke-width="1.5"/>
                <line x1="56" y1="50" x2="124" y2="50" stroke="var(--illust-stroke-bold)" stroke-width="2.5" stroke-linecap="round"/>
                <circle cx="52" cy="66" r="4" fill="var(--illust-teal-bold)"/>
                <line x1="62" y1="66" x2="120" y2="66" stroke="var(--illust-stroke)" stroke-width="2" stroke-linecap="round"/>
                <circle cx="52" cy="82" r="4" fill="var(--color-border)"/>
                <line x1="62" y1="82" x2="110" y2="82" stroke="var(--illust-stroke)" stroke-width="2" stroke-linecap="round"/>
                <circle cx="52" cy="98" r="4" fill="var(--color-border)"/>
                <line x1="62" y1="98" x2="100" y2="98" stroke="var(--illust-stroke)" stroke-width="2" stroke-linecap="round"/>
                {{-- Add badge --}}
                <circle cx="144" cy="34" r="13" fill="var(--illust-teal)"/>
                <line x1="138" y1="34" x2="150" y2="34" stroke="var(--illust-teal-bold)" stroke-width="2.5" stroke-linecap="round"/>
                <line x1="144" y1="28" x2="144" y2="40" stroke="var(--illust-teal-bold)" stroke-width="2.5" stroke-linecap="round"/>
                {{-- Sparkles --}}
                <circle cx="28" cy="38" r="3" fill="var(--illust-teal)"/>
                <circle cx="22" cy="56" r="2" fill="var(--illust-chart-3)"/>
            </svg>
        </div>
    </div>

    {{-- Tabla de listas (AJAX) --}}
    <div id="ListsProIndexContainer"
         data-url="{{ route('manager.lists_pro.listing') }}"
         data-per-page="25">

        <div class="mc-card mc-card-table">
            <div class="mc-filter-bar">
                <div class="mc-filter-search">
                    <span class="mc-filter-search-icon material-symbols-rounded">search</span>
                    <input class="mc-form-input" type="text" name="keyword"
                           placeholder="{{ trans('campaign::lists.filter.search') }}"
                           value="{{ request()->keyword }}">
                </div>

                <select class="mc-form-input mc-form-select mc-select-inline" name="sort_order">
                    <option value="created_at">{{ trans('campaign::campaigns.filter.sort_date') }}</option>
                    <option value="name">{{ trans('campaign::campaigns.filter.sort_name') }}</option>
                </select>
                <input type="hidden" name="sort_direction" value="desc">
                <button type="button" class="mc-btn mc-btn-ghost mc-btn-icon mc-sort-dir-btn" title="Toggle sort direction">
                    <span class="material-symbols-rounded mc-sort-icon">arrow_downward</span>
                </button>
            </div>

            <div id="ListsProIndexContent">
                <div class="mc-empty-state">
                    <div class="mc-spinner mc-spinner-lg"></div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
<script>
(function () {
    var container = document.getElementById('ListsProIndexContainer');
    var content   = document.getElementById('ListsProIndexContent');
    if (!container) return;

    var url     = container.dataset.url;
    var perPage = container.dataset.perPage || 25;
    var timer   = null;

    function getParams() {
        var params = new URLSearchParams();
        params.set('per_page', perPage);
        var keyword  = container.querySelector('[name="keyword"]');
        var sortOrder = container.querySelector('[name="sort_order"]');
        var sortDir   = container.querySelector('[name="sort_direction"]');
        if (keyword && keyword.value)   params.set('keyword', keyword.value);
        if (sortOrder) params.set('sort_order', sortOrder.value);
        if (sortDir)   params.set('sort_direction', sortDir.value);
        return params;
    }

    function load(page) {
        var params = getParams();
        if (page) params.set('page', page);
        content.style.opacity = '0.5';
        fetch(url + '?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.text(); })
            .then(function (html) {
                content.innerHTML = html;
                content.style.opacity = '1';
                bindContent();
                if (window.McDropdown) McDropdown.init();
            })
            .catch(function () {
                content.innerHTML = '<div class="mc-empty-state"><div class="mc-empty-state-text">Error loading lists.</div></div>';
                content.style.opacity = '1';
            });
    }

    function bindContent() {
        content.querySelectorAll('[data-page]').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                load(link.dataset.page);
            });
        });
    }

    container.querySelectorAll('select[name]').forEach(function (sel) {
        sel.addEventListener('change', function () { load(); });
    });

    var searchInput = container.querySelector('[name="keyword"]');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () { load(); }, 300);
        });
    }

    var sortDirBtn   = container.querySelector('.mc-sort-dir-btn');
    var sortDirInput = container.querySelector('[name="sort_direction"]');
    var sortIcon     = container.querySelector('.mc-sort-icon');
    if (sortDirBtn && sortDirInput) {
        sortDirBtn.addEventListener('click', function () {
            var isAsc = sortDirInput.value === 'desc';
            sortDirInput.value = isAsc ? 'asc' : 'desc';
            if (sortIcon) sortIcon.textContent = isAsc ? 'arrow_upward' : 'arrow_downward';
            load();
        });
    }

    document.addEventListener('list:reload', function () { load(); });

    load();
})();
</script>
@endsection

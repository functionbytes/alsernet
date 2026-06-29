@extends('campaign::refactor.layout')

@section('title', trans('campaign::campaigns.page.title'))

@section('page-header')
    <div class="mc-page-header">
        <div>
            <h1 class="mc-page-title">{{ trans('campaign::campaigns.page.title') }}</h1>
            <p class="mc-page-subtitle">{{ trans('campaign::campaigns.page.subtitle') }}</p>
        </div>
        <div class="mc-page-actions">
            <a href="{{ route('manager.campaigns_pro.select_type') }}" class="mc-btn mc-btn-primary mc-btn-sm">
                <span class="material-symbols-rounded">add</span>
                {{ trans('campaign::campaigns.banner.create') }}
            </a>
        </div>
    </div>
@endsection

@section('content')
    {{-- Announcement banner --}}
    <div class="mc-banner mc-banner-open">
        <div class="mc-banner-content">
            <div class="mc-banner-title">{{ trans('campaign::campaigns.banner.title') }}</div>
            <div class="mc-banner-desc">{{ trans('campaign::campaigns.banner.desc') }}</div>
            <div class="mc-banner-meta">
                <span class="mc-banner-meta-item">
                    <span class="material-symbols-rounded">campaign</span>
                    <span class="mc-banner-meta-value">{{ number_format($stats['total'] ?? 0) }}</span> {{ trans('campaign::campaigns.banner.campaigns_count') }}
                </span>
                <span class="mc-banner-meta-item">
                    <span class="material-symbols-rounded">send</span>
                    <span class="mc-banner-meta-value">{{ number_format($stats['done'] ?? 0) }}</span> {{ trans('campaign::campaigns.status.sent') }}
                </span>
                <span class="mc-banner-meta-item">
                    <span class="material-symbols-rounded">edit_note</span>
                    <span class="mc-banner-meta-value">{{ number_format($stats['draft'] ?? 0) }}</span> {{ trans('campaign::campaigns.status.draft') }}
                </span>
                <span class="mc-banner-meta-item">
                    <span class="material-symbols-rounded">schedule</span>
                    {{ trans('campaign::campaigns.banner.ready_to_send') }}
                </span>
            </div>
        </div>
        <div class="mc-banner-illustration">
            <svg viewBox="0 0 180 140" fill="none" xmlns="http://www.w3.org/2000/svg">
                {{-- Subtle teal background glow --}}
                <circle cx="90" cy="70" r="58" fill="var(--illust-teal)"/>
                {{-- Email envelope --}}
                <rect x="36" y="50" width="100" height="62" rx="8" fill="var(--color-card-bg)" stroke="var(--illust-stroke)" stroke-width="1.5"/>
                <path d="M36 58l50 28 50-28" stroke="var(--illust-stroke)" stroke-width="1.5" fill="var(--illust-teal)" fill-opacity="0.15"/>
                {{-- Letter peeking out --}}
                <rect x="50" y="24" width="72" height="45" rx="6" fill="var(--color-card-bg)" stroke="var(--illust-stroke)" stroke-width="1.2"/>
                <line x1="62" y1="38" x2="110" y2="38" stroke="var(--illust-stroke-bold)" stroke-width="2.5" stroke-linecap="round"/>
                <line x1="62" y1="47" x2="100" y2="47" stroke="var(--illust-stroke)" stroke-width="2" stroke-linecap="round"/>
                <line x1="62" y1="56" x2="86" y2="56" stroke="var(--illust-stroke)" stroke-width="1.8" stroke-linecap="round"/>
                {{-- Send arrow badge --}}
                <circle cx="144" cy="32" r="13" fill="var(--illust-teal)"/>
                <path d="M138 32l8-5v3.5h5v3h-5v3.5z" fill="var(--illust-teal-bold)"/>
                {{-- Performance chart bars --}}
                <rect x="130" y="82" width="8" height="20" rx="2.5" fill="var(--chart-1)" opacity="0.6"/>
                <rect x="142" y="74" width="8" height="28" rx="2.5" fill="var(--illust-teal-bold)"/>
                <rect x="154" y="78" width="8" height="24" rx="2.5" fill="var(--chart-2)" opacity="0.6"/>
                {{-- Trend line on chart --}}
                <path d="M134 88 L146 80 L158 84" stroke="var(--color-teal)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" opacity="0.8" fill="none"/>
                {{-- Sparkle accents --}}
                <circle cx="28" cy="38" r="3" fill="var(--illust-teal)"/>
                <circle cx="22" cy="55" r="2" fill="var(--illust-chart-3)"/>
                <path d="M165 60l1.5 4 4-1.5-4 1.5-1.5 4-1.5-4-4 1.5 4-1.5z" fill="var(--illust-teal)"/>
            </svg>
        </div>
    </div>

    {{-- Quick-start guide cards --}}
    <div class="mc-guide-cards">
        <a href="{{ route('manager.page_templates.index') }}" class="mc-guide-card">
            <div class="mc-guide-card-icon">
                <span class="material-symbols-rounded">description</span>
            </div>
            <div class="mc-guide-card-body">
                <div class="mc-guide-card-title">{{ trans('campaign::campaigns.guide.templates') }}</div>
                <div class="mc-guide-card-desc">{{ trans('campaign::campaigns.guide.templates_desc') }}</div>
            </div>
        </a>
        <a href="{{ route('manager.flow_automations.index') }}" class="mc-guide-card">
            <div class="mc-guide-card-icon">
                <span class="material-symbols-rounded">bolt</span>
            </div>
            <div class="mc-guide-card-body">
                <div class="mc-guide-card-title">{{ trans('campaign::campaigns.guide.automation') }}</div>
                <div class="mc-guide-card-desc">{{ trans('campaign::campaigns.guide.automation_desc') }}</div>
            </div>
        </a>
    </div>

    {{-- Campaigns table --}}
    <div id="CampaignsIndexContainer"
         data-url="{{ route('manager.campaigns_pro.listing') }}"
         data-per-page="25">

        <div class="mc-card mc-card-table">
            {{-- Filter bar --}}
            <div class="mc-filter-bar">
                <div class="mc-filter-search">
                    <span class="mc-filter-search-icon material-symbols-rounded">search</span>
                    <input class="mc-form-input" type="text" name="keyword" placeholder="{{ trans('campaign::campaigns.filter.search') }}" value="{{ request()->keyword }}">
                </div>

                <select class="mc-form-input mc-form-select mc-select-inline" name="sort_order">
                    <option value="created_at">{{ trans('campaign::campaigns.filter.sort_date') }}</option>
                    <option value="name">{{ trans('campaign::campaigns.filter.sort_name') }}</option>
                </select>
                <input type="hidden" name="sort_direction" value="desc">
                <button type="button" class="mc-btn mc-btn-ghost mc-btn-icon mc-sort-dir-btn" title="Toggle sort direction">
                    <span class="material-symbols-rounded mc-sort-icon">arrow_downward</span>
                </button>

                <select class="mc-form-input mc-form-select mc-select-inline" name="status">
                    <option value="">{{ trans('campaign::campaigns.filter.all_statuses') }}</option>
                    <option {{ request()->status == 'new' ? 'selected' : '' }} value="new">{{ trans('campaign::campaigns.status.new') }}</option>
                    <option {{ request()->status == 'draft' ? 'selected' : '' }} value="draft">{{ trans('campaign::campaigns.status.draft') }}</option>
                    <option {{ request()->status == 'scheduled' ? 'selected' : '' }} value="scheduled">{{ trans('campaign::campaigns.status.scheduled') }}</option>
                    <option {{ request()->status == 'sending' ? 'selected' : '' }} value="sending">{{ trans('campaign::campaigns.status.sending') }}</option>
                    <option {{ request()->status == 'done' ? 'selected' : '' }} value="done">{{ trans('campaign::campaigns.status.done') }}</option>
                    <option {{ request()->status == 'paused' ? 'selected' : '' }} value="paused">{{ trans('campaign::campaigns.status.paused') }}</option>
                    <option {{ request()->status == 'error' ? 'selected' : '' }} value="error">{{ trans('campaign::campaigns.status.error') }}</option>
                </select>
            </div>

            {{-- Campaign list content (AJAX loaded) --}}
            <div id="CampaignsIndexContent">
                <div class="mc-empty-state">
                    <div class="mc-spinner mc-spinner-lg"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
(function() {
    var container = document.getElementById('CampaignsIndexContainer');
    var content = document.getElementById('CampaignsIndexContent');
    if (!container) return;

    var url = container.dataset.url;
    var perPage = container.dataset.perPage || 25;
    var timer = null;

    function getParams() {
        var params = new URLSearchParams();
        params.set('per_page', perPage);
        var keyword = container.querySelector('[name="keyword"]');
        var sortOrder = container.querySelector('[name="sort_order"]');
        var sortDir = container.querySelector('[name="sort_direction"]');
        var status = container.querySelector('[name="status"]');
        if (keyword && keyword.value) params.set('keyword', keyword.value);
        if (sortOrder) params.set('sort_order', sortOrder.value);
        if (sortDir) params.set('sort_direction', sortDir.value);
        if (status && status.value) params.set('status', status.value);
        return params;
    }

    function load(page) {
        var params = getParams();
        if (page) params.set('page', page);
        content.style.opacity = '0.5';

        fetch(url + '?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.text(); })
        .then(function(html) {
            content.innerHTML = html;
            content.style.opacity = '1';
            bindContentEvents();
            if (window.McDropdown) McDropdown.init();
        })
        .catch(function() {
            content.innerHTML = '<div class="mc-empty-state"><div class="mc-empty-state-text">{{ trans("campaign::campaigns.error.load_failed") }}</div></div>';
            content.style.opacity = '1';
        });
    }

    function bindContentEvents() {
        // Pagination links
        content.querySelectorAll('[data-page]').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                load(link.dataset.page);
            });
        });
    }

    // Filter events
    container.querySelectorAll('select[name]').forEach(function(sel) {
        sel.addEventListener('change', function() { load(); });
    });

    var searchInput = container.querySelector('[name="keyword"]');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(timer);
            timer = setTimeout(function() { load(); }, 300);
        });
    }

    // Sort direction toggle
    var sortDirBtn = container.querySelector('.mc-sort-dir-btn');
    var sortDirInput = container.querySelector('[name="sort_direction"]');
    var sortIcon = container.querySelector('.mc-sort-icon');
    if (sortDirBtn && sortDirInput) {
        sortDirBtn.addEventListener('click', function() {
            var isAsc = sortDirInput.value === 'desc';
            sortDirInput.value = isAsc ? 'asc' : 'desc';
            if (sortIcon) sortIcon.textContent = isAsc ? 'arrow_upward' : 'arrow_downward';
            load();
        });
    }

    // Reload list when an action completes
    document.addEventListener('list:reload', function() {
        load();
    });

    // Initial load
    load();
})();
</script>
@endsection

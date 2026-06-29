@extends('campaign::refactor.layout')

@section('title', trans('campaign::automations.page.title'))

@section('page-header')
    <div class="mc-page-header">
        <div>
            <h1 class="mc-page-title">{{ trans('campaign::automations.page.title') }}</h1>
            <p class="mc-page-subtitle">{{ trans('campaign::automations.page.subtitle') }}</p>
        </div>
        <div class="mc-page-actions">
            <a href="{{ route('manager.flow_automations.create') }}" class="mc-btn mc-btn-primary mc-btn-sm">
                <span class="material-symbols-rounded">add</span> {{ trans('campaign::automations.create.button') }}
            </a>
        </div>
    </div>
@endsection

@section('content')
    {{-- Banner --}}
    <div class="mc-banner mc-banner-open">
        <div class="mc-banner-content">
            <h2 class="mc-banner-title">{{ trans('campaign::automations.banner.title') }}</h2>
            <p class="mc-banner-desc">{{ trans('campaign::automations.banner.desc') }}</p>
            <div class="mc-banner-meta">
                <span class="mc-banner-meta-item">
                    <span class="material-symbols-rounded">bolt</span>
                    <span class="mc-banner-meta-value">{{ number_format($total) }}</span> {{ trans('campaign::automations.banner.meta_automations') }}
                </span>
                <span class="mc-banner-meta-item">
                    <span class="material-symbols-rounded">play_circle</span>
                    <span class="mc-banner-meta-value">{{ number_format($active) }}</span> {{ trans('campaign::automations.banner.meta_active') }}
                </span>
                <span class="mc-banner-meta-item">
                    <span class="material-symbols-rounded">pause_circle</span>
                    <span class="mc-banner-meta-value">{{ number_format($inactive) }}</span> {{ trans('campaign::automations.banner.meta_inactive') }}
                </span>
            </div>
        </div>
        <div class="mc-banner-illustration">
            <svg viewBox="0 0 200 160" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <ellipse cx="108" cy="80" rx="86" ry="62" fill="var(--illust-teal)" opacity="0.5"/>
                <ellipse cx="80" cy="94" rx="56" ry="42" fill="var(--illust-fill)"/>
                <path d="M52 44 q24 -4 38 4" stroke="var(--illust-stroke)" stroke-width="1.3" stroke-linecap="round" stroke-dasharray="3 3" fill="none" opacity="0.6"/>
                <path d="M116 64 q-3 3 -5 6" stroke="var(--illust-stroke)" stroke-width="1.3" stroke-linecap="round" stroke-dasharray="3 3" fill="none" opacity="0.6"/>
                <path d="M88 90 q-18 14 -32 22" stroke="var(--illust-stroke)" stroke-width="1.2" stroke-linecap="round" stroke-dasharray="3 3" fill="none" opacity="0.55"/>
                <path d="M132 90 q14 12 22 22" stroke="var(--illust-stroke)" stroke-width="1.2" stroke-linecap="round" stroke-dasharray="3 3" fill="none" opacity="0.55"/>
                <path d="M88 45 L93 48 L88 51" stroke="var(--illust-stroke-bold)" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <path d="M108 68 L111 72 L114 68" stroke="var(--illust-stroke-bold)" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <path d="M58 110 L56 114 L60 116" stroke="var(--illust-stroke-bold)" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <path d="M150 110 L155 113 L153 117" stroke="var(--illust-stroke-bold)" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <circle cx="36" cy="42" r="14" fill="var(--color-card-bg)" stroke="var(--illust-stroke-bold)" stroke-width="1.6"/>
                <path d="M32 35 L32 49 L44 42 Z" fill="var(--chart-1)" opacity="0.9"/>
                <circle cx="48" cy="30" r="5.5" fill="var(--chart-2)" stroke="var(--color-card-bg)" stroke-width="1.3"/>
                <path d="M45.6 30 h4.8 M48 27.6 v4.8" stroke="var(--color-card-bg)" stroke-width="1.4" stroke-linecap="round"/>
                <rect x="94" y="34" width="44" height="30" rx="5" fill="var(--color-card-bg)" stroke="var(--illust-stroke-bold)" stroke-width="1.6"/>
                <path d="M94 38 L116 50 L138 38" stroke="var(--illust-stroke-bold)" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <line x1="100" y1="56" x2="124" y2="56" stroke="var(--illust-stroke)" stroke-width="1" stroke-linecap="round" opacity="0.45"/>
                <line x1="100" y1="60" x2="118" y2="60" stroke="var(--illust-stroke)" stroke-width="1" stroke-linecap="round" opacity="0.3"/>
                <circle cx="134" cy="38" r="4" fill="var(--chart-1)" opacity="0.9"/>
                <path d="M132 38 l1.5 1.5 3 -3" stroke="var(--color-card-bg)" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
                <line x1="78" y1="48" x2="86" y2="48" stroke="var(--chart-1)" stroke-width="1.2" stroke-linecap="round" opacity="0.4"/>
                <line x1="82" y1="52" x2="88" y2="52" stroke="var(--chart-1)" stroke-width="1" stroke-linecap="round" opacity="0.25"/>
                <path d="M110 70 L132 90 L110 110 L88 90 Z" fill="var(--chart-4)" fill-opacity="0.16" stroke="var(--chart-4)" stroke-width="1.5" stroke-linejoin="round"/>
                <path d="M105 86 a5 5 0 0 1 10 0 q0 3 -3 4 q-2 1 -2 4" stroke="var(--chart-4)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <circle cx="110" cy="100" r="1.3" fill="var(--chart-4)"/>
                <circle cx="46" cy="128" r="13" fill="var(--color-card-bg)" stroke="var(--illust-stroke-bold)" stroke-width="1.5"/>
                <circle cx="46" cy="128" r="13" fill="var(--chart-5)" fill-opacity="0.18"/>
                <line x1="46" y1="128" x2="46" y2="121" stroke="var(--chart-5)" stroke-width="1.7" stroke-linecap="round"/>
                <line x1="46" y1="128" x2="51" y2="130" stroke="var(--chart-5)" stroke-width="1.5" stroke-linecap="round"/>
                <circle cx="46" cy="128" r="1.4" fill="var(--chart-5)"/>
                <rect x="146" y="116" width="28" height="26" rx="6" fill="var(--color-card-bg)" stroke="var(--illust-stroke-bold)" stroke-width="1.5"/>
                <rect x="146" y="116" width="28" height="26" rx="6" fill="var(--chart-2)" fill-opacity="0.18"/>
                <path d="M152 130 l5 5 10 -11" stroke="var(--chart-2)" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <circle cx="172" cy="118" r="2.6" fill="var(--chart-2)" opacity="0.9"/>
                <path d="M174 14 L164 30 L172 30 L168 44 L184 26 L174 26 Z" fill="var(--chart-4)" opacity="0.85" stroke="var(--illust-stroke-bold)" stroke-width="1" stroke-linejoin="round"/>
                <path d="M158 18 q-2 6 0 14" stroke="var(--chart-4)" stroke-width="1" stroke-linecap="round" stroke-dasharray="2 2" fill="none" opacity="0.5"/>
            </svg>
        </div>
    </div>

    {{-- Automations table --}}
    <div class="mc-card mc-card-table mc-card-table-flush">
        <div class="mc-filter-bar">
            <div id="automations-bulk-actions" style="display:none">
                <button type="button" class="mc-btn mc-btn-secondary mc-btn-sm"
                        data-bulk-delete="{{ route('manager.flow_automations.delete') }}"
                        data-confirm="{{ trans('campaign::automations.filter.delete_confirm') }}">
                    <span class="material-symbols-rounded">delete</span> {{ trans('campaign::automations.common.buttons.delete') }}
                </button>
            </div>
            <div class="mc-filter-search">
                <span class="mc-filter-search-icon material-symbols-rounded">search</span>
                <input type="text" class="mc-form-input mc-search-input" name="keyword" placeholder="{{ trans('campaign::automations.filter.search') }}">
            </div>
            <select class="mc-form-input mc-form-select mc-select-inline mc-filter-select" name="status">
                <option value="">{{ trans('campaign::automations.filter.status_all') }}</option>
                <option value="active">{{ trans('campaign::automations.filter.status_active') }}</option>
                <option value="inactive">{{ trans('campaign::automations.filter.status_inactive') }}</option>
            </select>
            <select class="mc-form-input mc-form-select mc-select-inline mc-filter-select" name="sort_order">
                <option value="created_at">{{ trans('campaign::automations.filter.sort_date') }}</option>
                <option value="name">{{ trans('campaign::automations.filter.sort_name') }}</option>
            </select>
        </div>

        <div data-mc-list
             data-url="{{ route('manager.flow_automations.listing') }}"
             id="automations-listing">
            <div data-list-content>
                <div class="mc-text-center mc-p-8 mc-text-muted">{{ trans('campaign::automations.common.loading') }}</div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
(function() {
    var list = new McList({
        container: '#automations-listing',
        url: '{{ route("manager.flow_automations.listing") }}',
        perPage: 25,
        onLoad: function() {
            if (window.McDropdown) McDropdown.init();
        }
    });

    // Search input
    var searchInput = document.querySelector('.mc-search-input[name="keyword"]');
    if (searchInput) {
        var timeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                list.search = searchInput.value;
                list.page = 1;
                list.load();
            }, 300);
        });
    }

    // Status filter
    var statusSelect = document.querySelector('.mc-filter-select[name="status"]');
    if (statusSelect) {
        statusSelect.addEventListener('change', function() {
            list.setParam('status', statusSelect.value);
            list.page = 1;
            list.load();
        });
    }

    // Sort select
    var sortSelect = document.querySelector('.mc-filter-select[name="sort_order"]');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            list.setSort(sortSelect.value, 'desc');
        });
    }

    // Bulk select-all checkbox + row checkboxes
    document.getElementById('automations-listing').addEventListener('change', function(e) {
        if (e.target.matches('[data-check-all]')) {
            var cbs = document.querySelectorAll('#automations-listing input[name="uids[]"]');
            cbs.forEach(function(cb) { cb.checked = e.target.checked; });
            var bulkBar = document.getElementById('automations-bulk-actions');
            if (bulkBar) bulkBar.style.display = e.target.checked && cbs.length ? '' : 'none';
        }
        if (e.target.matches('input[name="uids[]"]')) {
            var anyChecked = document.querySelectorAll('#automations-listing input[name="uids[]"]:checked').length > 0;
            var bulkBar = document.getElementById('automations-bulk-actions');
            if (bulkBar) bulkBar.style.display = anyChecked ? '' : 'none';
        }
    });

    // Bulk delete
    var bulkDeleteBtn = document.querySelector('[data-bulk-delete]');
    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', function() {
            var checked = document.querySelectorAll('#automations-listing input[name="uids[]"]:checked');
            var uids = Array.from(checked).map(function(cb) { return cb.value; });
            if (!uids.length) return;
            McDialog.confirm({
                title: '{{ trans("campaign::automations.bulk_delete.title") }}',
                message: '{{ trans("campaign::automations.bulk_delete.message") }}'.replace(':count', uids.length),
                type: 'danger',
                confirmText: '{{ trans("campaign::automations.common.buttons.delete") }}',
                onConfirm: function() {
                    fetch(bulkDeleteBtn.dataset.bulkDelete, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        },
                        body: JSON.stringify({ uids: uids })
                    }).then(function(r) { return r.json(); }).then(function(data) {
                        if (window.McNotify) McNotify.success(data.message || 'Deleted');
                        list.refresh();
                        var ca = document.querySelector('#automations-listing [data-check-all]');
                        if (ca) ca.checked = false;
                        var bulkBar = document.getElementById('automations-bulk-actions');
                        if (bulkBar) bulkBar.style.display = 'none';
                    });
                }
            });
        });
    }

    // Row delete action — sends uids in body
    var deleteUrl = '{{ route("manager.flow_automations.delete") }}';

    document.getElementById('automations-listing').addEventListener('click', function(e) {
        var link = e.target.closest('[data-auto-action="delete"]');
        if (!link) return;
        e.preventDefault();

        var uid = link.dataset.uid;
        var name = link.dataset.name || '';

        function doDelete() {
            fetch(deleteUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ uids: uid })
            }).then(function(r) { return r.json(); }).then(function(data) {
                if (data.message && window.McNotify) McNotify.success(data.message);
                list.refresh();
            }).catch(function() {
                if (window.McNotify) McNotify.error('{{ trans("campaign::automations.common.error.generic") }}');
            });
        }

        McDialog.confirm({
            title: '{{ trans("campaign::automations.action.delete") }}',
            message: '{{ trans("campaign::automations.action.delete_confirm_msg") }}'.replace(':name', name),
            type: 'danger',
            confirmText: '{{ trans("campaign::automations.common.buttons.delete") }}',
            onConfirm: doDelete
        });
    });

    // Listen for list:reload (after popup actions)
    document.addEventListener('list:reload', function() {
        list.refresh();
    });

    list.load();
})();
</script>
@endsection

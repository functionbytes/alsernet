@extends('campaign::refactor.layout')

@section('title', trans('campaign::funnels.page.title'))

@section('page-header')
    <div class="mc-page-header">
        <div>
            <h1 class="mc-page-title">{{ trans('campaign::funnels.page.title') }}</h1>
            <p class="mc-page-subtitle">{{ trans('campaign::funnels.page.subtitle') }}</p>
        </div>
        <div class="mc-page-actions">
            <a href="{{ route('manager.funnels.create') }}" class="mc-btn mc-btn-primary mc-btn-sm">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'add', 'size' => 16])
                {{ trans('campaign::funnels.buttons.create') }}
            </a>
        </div>
    </div>
@endsection

@section('content')

{{-- Stats row: Total | Published | Drafts --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:var(--space-4);margin-bottom:var(--space-5);">
    <div class="mc-stat-card">
        <div class="mc-stat-icon" style="background:var(--chart-1-bg);color:var(--chart-1);">
            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'layers', 'size' => 22])
        </div>
        <div class="mc-stat-content">
            <div class="mc-stat-label">{{ trans('campaign::funnels.stat.total') }}</div>
            <div class="mc-stat-value">{{ number_format($stats['total'] ?? 0) }}</div>
        </div>
    </div>
    <div class="mc-stat-card">
        <div class="mc-stat-icon" style="background:var(--chart-2-bg);color:var(--chart-2);">
            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'check', 'size' => 22])
        </div>
        <div class="mc-stat-content">
            <div class="mc-stat-label">{{ trans('campaign::funnels.stat.published') }}</div>
            <div class="mc-stat-value">{{ number_format($stats['published'] ?? 0) }}</div>
        </div>
    </div>
    <div class="mc-stat-card">
        <div class="mc-stat-icon" style="background:var(--chart-3-bg);color:var(--chart-3);">
            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'edit', 'size' => 22])
        </div>
        <div class="mc-stat-content">
            <div class="mc-stat-label">{{ trans('campaign::funnels.stat.draft') }}</div>
            <div class="mc-stat-value">{{ number_format($stats['draft'] ?? 0) }}</div>
        </div>
    </div>
</div>

{{-- Info banner --}}
<div class="mc-alert mc-alert-teal" style="margin-bottom:var(--space-5);">
    <div class="mc-alert-icon">
        @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'info', 'size' => 18])
    </div>
    <div class="mc-alert-content">
        <div class="mc-alert-title">{{ trans('campaign::funnels.banner.title') }}</div>
        <div class="mc-alert-text">{{ trans('campaign::funnels.banner.desc') }}</div>
    </div>
</div>

{{-- Listing card --}}
<div class="mc-card mc-card-table">
    <div class="mc-filter-bar">
        <div class="mc-filter-search">
            <span class="mc-filter-search-icon">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'search', 'size' => 16])
            </span>
            <input class="mc-form-input mc-search-input" type="text" name="keyword"
                   placeholder="{{ trans('campaign::funnels.filter.search') }}"
                   data-list-filter="keyword">
        </div>
        <select class="mc-form-input mc-form-select mc-select-inline" name="status" data-list-filter="status">
            <option value="">{{ trans('campaign::funnels.filter.status_all') }}</option>
            <option value="published">{{ trans('campaign::funnels.status.published') }}</option>
            <option value="draft">{{ trans('campaign::funnels.status.draft') }}</option>
        </select>
    </div>

    <div id="funnels-list"
         data-url="{{ route('manager.funnels.listing') }}"
         class="mc-list-container">
        <div data-list-content>
            <div style="padding:var(--space-8);text-align:center;color:var(--color-text-muted);">
                <div class="mc-spinner"></div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var container = document.getElementById('funnels-list');
    if (!container) return;

    var list = new McList({
        container: container,
        url: container.dataset.url,
    });

    // Bind filters (keyword + status)
    document.querySelectorAll('[data-list-filter]').forEach(function(el) {
        var filterName = el.dataset.listFilter;
        var eventType = el.tagName === 'SELECT' ? 'change' : 'input';
        var debounceTimer;
        el.addEventListener(eventType, function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                list.setFilter(filterName, el.value);
                list.load();
            }, eventType === 'input' ? 300 : 0);
        });
    });

    // Initial load
    list.load();

    // Reload listener
    document.addEventListener('list:reload', function() { list.load(); });

    // Action handlers via event delegation
    document.addEventListener('click', function(e) {
        // Publish / Unpublish
        var pubBtn = e.target.closest('[data-action-publish]');
        if (pubBtn) {
            e.preventDefault();
            fetch(pubBtn.dataset.actionPublish, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            }).then(function(r) { return r.json(); }).then(function(d) {
                if (window.McNotify && d.message) window.McNotify.success(d.message);
                list.load();
            });
            return;
        }

        // Duplicate
        var dupBtn = e.target.closest('[data-action-duplicate]');
        if (dupBtn) {
            e.preventDefault();
            fetch(dupBtn.dataset.actionDuplicate, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            }).then(function(r) { return r.json(); }).then(function(d) {
                if (window.McNotify && d.message) window.McNotify.success(d.message);
                list.load();
            });
            return;
        }

        // Delete
        var deleteBtn = e.target.closest('[data-action-delete]');
        if (deleteBtn) {
            e.preventDefault();
            window.McDialog.confirm({
                title: '{{ trans("campaign::funnels.action.delete") }}',
                message: '{{ trans("campaign::funnels.empty.desc") }}',
                type: 'danger',
                confirmText: '{{ trans("campaign::funnels.action.delete") }}',
                onConfirm: function() {
                    fetch(deleteBtn.dataset.actionDelete, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'uids=' + deleteBtn.dataset.uid
                    }).then(function(r) { return r.json(); }).then(function(d) {
                        if (window.McNotify && d.message) window.McNotify.success(d.message);
                        list.load();
                    });
                }
            });
        }
    });
});
</script>
@endsection

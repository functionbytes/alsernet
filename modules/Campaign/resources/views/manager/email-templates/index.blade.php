@extends('campaign::refactor.layout')

@section('title', trans('campaign::email-templates.page.title'))

@section('page-header')
    <div class="mc-page-header">
        <div>
            <h1 class="mc-page-title">{{ trans('campaign::email-templates.page.title') }}</h1>
            <p class="mc-page-subtitle">{{ trans('campaign::email-templates.page.subtitle') }}</p>
        </div>
        <div class="mc-page-actions">
            <a href="{{ route('manager.email_templates.add') }}"
               class="mc-btn mc-btn-primary mc-btn-sm">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'add', 'size' => 16])
                {{ trans('campaign::email-templates.buttons.create') }}
            </a>
        </div>
    </div>
@endsection

@section('content')

{{-- Stats row: Total | Base | Extended --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:var(--space-4);margin-bottom:var(--space-5);">
    <div class="mc-stat-card">
        <div class="mc-stat-icon" style="background:var(--chart-1-bg);color:var(--chart-1);">
            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'file-text', 'size' => 22])
        </div>
        <div class="mc-stat-content">
            <div class="mc-stat-label">{{ trans('campaign::email-templates.stat.total') }}</div>
            <div class="mc-stat-value">{{ number_format($stats['total']) }}</div>
        </div>
    </div>
    <div class="mc-stat-card">
        <div class="mc-stat-icon" style="background:var(--chart-2-bg);color:var(--chart-2);">
            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'star', 'size' => 22])
        </div>
        <div class="mc-stat-content">
            <div class="mc-stat-label">{{ trans('campaign::email-templates.stat.base') }}</div>
            <div class="mc-stat-value">{{ number_format($stats['base'] ?? 0) }}</div>
        </div>
    </div>
    <div class="mc-stat-card">
        <div class="mc-stat-icon" style="background:var(--chart-3-bg);color:var(--chart-3);">
            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'layers', 'size' => 22])
        </div>
        <div class="mc-stat-content">
            <div class="mc-stat-label">{{ trans('campaign::email-templates.stat.extended') }}</div>
            <div class="mc-stat-value">{{ number_format($stats['extended'] ?? 0) }}</div>
        </div>
    </div>
</div>

{{-- Info banner --}}
<div class="mc-alert mc-alert-teal" style="margin-bottom:var(--space-5);">
    <div class="mc-alert-icon">
        @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'info', 'size' => 18])
    </div>
    <div class="mc-alert-content">
        <div class="mc-alert-title">{{ trans('campaign::email-templates.banner.title') }}</div>
        <div class="mc-alert-text">{{ trans('campaign::email-templates.banner.desc') }}</div>
    </div>
</div>

{{-- Listing card --}}
<div class="mc-card mc-card-table">
    {{-- Base / Extended tabs --}}
    <div class="mc-tabs mc-tabs-card-header" data-mc-tabs id="page-tpl-tabs" style="padding:0 var(--space-5);border-bottom:var(--space-px) solid var(--color-border);">
        <button type="button" class="mc-tab active" data-tab="">
            {{ trans('campaign::email-templates.tabs.all') }}
            <span class="mc-tab-count">{{ number_format($stats['total']) }}</span>
        </button>
        <button type="button" class="mc-tab" data-tab="base">
            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'star', 'size' => 14])
            {{ trans('campaign::email-templates.tabs.base') }}
            <span class="mc-tab-count">{{ number_format($stats['base'] ?? 0) }}</span>
        </button>
        <button type="button" class="mc-tab" data-tab="extended">
            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'layers', 'size' => 14])
            {{ trans('campaign::email-templates.tabs.extended') }}
            <span class="mc-tab-count">{{ number_format($stats['extended'] ?? 0) }}</span>
        </button>
    </div>

    <div class="mc-filter-bar">
        <div class="mc-filter-search">
            <span class="mc-filter-search-icon">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'search', 'size' => 16])
            </span>
            <input class="mc-form-input mc-search-input" type="text" name="keyword"
                   placeholder="{{ trans('campaign::email-templates.filter.search') }}"
                   data-list-filter="keyword">
        </div>
        <div class="mc-view-toggle" id="view-toggle">
            <button type="button" class="mc-view-toggle-btn is-active" data-view="list" title="{{ trans('campaign::email-templates.view.list') }}">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'list', 'size' => 16])
            </button>
            <button type="button" class="mc-view-toggle-btn" data-view="grid" title="{{ trans('campaign::email-templates.view.grid') }}">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'grid', 'size' => 16])
            </button>
        </div>
    </div>

    <div id="page-template-list"
         data-url="{{ route('manager.email_templates.listing') }}"
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
    var container = document.getElementById('page-template-list');
    if (!container) return;

    var currentView = localStorage.getItem('admin_page_tpl_view') || 'list';

    var list = new McList({
        container: container,
        url: container.dataset.url,
    });

    list.setFilter('view', currentView);

    // Base/Extended tabs filter
    var tabsEl = document.getElementById('page-tpl-tabs');
    if (tabsEl) {
        tabsEl.querySelectorAll('.mc-tab').forEach(function(t) {
            t.addEventListener('click', function() {
                tabsEl.querySelectorAll('.mc-tab').forEach(function(x) { x.classList.remove('active'); });
                this.classList.add('active');
                list.setFilter('tab', this.dataset.tab || '');
                list.load();
            });
        });
    }

    // View toggle
    var toggleBtns = document.querySelectorAll('#view-toggle [data-view]');
    function setActiveView(view) {
        currentView = view;
        localStorage.setItem('admin_page_tpl_view', view);
        toggleBtns.forEach(function(b) {
            b.classList.toggle('is-active', b.dataset.view === view);
        });
        list.setFilter('view', view);
        list.load();
    }
    toggleBtns.forEach(function(btn) {
        btn.addEventListener('click', function() { setActiveView(btn.dataset.view); });
    });
    // Restore saved view state
    toggleBtns.forEach(function(b) {
        b.classList.toggle('is-active', b.dataset.view === currentView);
    });

    // Bind filters
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
        var deleteBtn = e.target.closest('[data-action-delete]');
        if (deleteBtn) {
            e.preventDefault();
            window.McDialog.confirm({
                title: '{{ trans("campaign::email-templates.confirm.delete_title") }}',
                message: '{{ trans("campaign::email-templates.confirm.delete_msg") }}',
                type: 'danger',
                confirmText: '{{ trans("campaign::email-templates.confirm.delete_btn") }}',
                onConfirm: function() {
                    fetch(deleteBtn.dataset.actionDelete, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'uids=' + deleteBtn.dataset.uid
                    }).then(function(r) { return r.json(); }).then(function(d) {
                        window.McNotify.success(d.message);
                        list.load();
                    });
                }
            });
        }
    });
});
</script>
@endsection

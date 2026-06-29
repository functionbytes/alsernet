@extends('campaign::refactor.layout')

@section('title', $list->name . ' — ' . trans('campaign::lists.subscribers.title'))

@section('page-header')
    <div class="mc-page-header">
        <div>
            <h1 class="mc-page-title">{{ $list->name }}</h1>
            <p class="mc-page-subtitle">{{ trans('campaign::lists.subscribers.title') }}</p>
        </div>
        <div class="mc-page-actions">
            <a href="{{ route('manager.lists_pro.overview', $list->uid) }}" class="mc-btn mc-btn-secondary mc-btn-sm">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'arrow-left', 'size' => 16])
                {{ trans('campaign::lists.overview.title') }}
            </a>
            <a href="{{ route('manager.lists_pro.subscriber_create', $list->uid) }}" class="mc-btn mc-btn-primary mc-btn-sm">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'add', 'size' => 16])
                {{ trans('campaign::lists.subscribers.add') }}
            </a>
        </div>
    </div>
@endsection

@section('content')

    <div id="SubscribersProContainer"
         data-url="{{ route('manager.lists_pro.subscribers_listing', $list->uid) }}"
         data-list-uid="{{ $list->uid }}"
         data-per-page="25">

        <div class="mc-card mc-card-table">
            <div class="mc-filter-bar">
                <div class="mc-filter-search">
                    <span class="mc-filter-search-icon material-symbols-rounded">search</span>
                    <input class="mc-form-input" type="text" name="keyword"
                           placeholder="{{ trans('campaign::lists.subscribers.filter') }}">
                </div>
            </div>

            <div id="SubscribersProContent">
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
    var container = document.getElementById('SubscribersProContainer');
    var content   = document.getElementById('SubscribersProContent');
    if (!container) return;

    var url     = container.dataset.url;
    var listUid = container.dataset.listUid;
    var perPage = container.dataset.perPage || 25;
    var timer   = null;
    var csrfToken = document.querySelector('meta[name="csrf-token"]') ?
        document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';

    function getParams() {
        var params = new URLSearchParams();
        params.set('per_page', perPage);
        var keyword = container.querySelector('[name="keyword"]');
        if (keyword && keyword.value) params.set('keyword', keyword.value);
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
                content.innerHTML = '<div class="mc-empty-state"><div class="mc-empty-state-text">Error loading subscribers.</div></div>';
                content.style.opacity = '1';
            });
    }

    function bindContent() {
        // Pagination
        content.querySelectorAll('[data-page]').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                load(link.dataset.page);
            });
        });

        // Delete buttons
        content.querySelectorAll('[data-delete-sub]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var subUid = btn.dataset.deleteSubUid;
                var deleteUrl = btn.dataset.deleteUrl;
                if (!confirm('{{ trans("campaign::lists.action.delete") }}?')) return;
                fetch(deleteUrl, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                }).then(function (r) { return r.json(); })
                  .then(function (data) {
                      if (data.success) { load(); }
                  });
            });
        });
    }

    var searchInput = container.querySelector('[name="keyword"]');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () { load(); }, 300);
        });
    }

    document.addEventListener('list:reload', function () { load(); });

    load();
})();
</script>
@endsection

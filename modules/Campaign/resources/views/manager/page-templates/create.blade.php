@extends('campaign::refactor.layout')

@section('title', trans('campaign::page-templates.create.title'))

@section('page-header')
    <div class="mc-page-header">
        <div>
            <h1 class="mc-page-title">{{ trans('campaign::page-templates.create.title') }}</h1>
            <p class="mc-page-subtitle">{{ trans('campaign::page-templates.create.subtitle') }}</p>
        </div>
        <div class="mc-page-actions">
            <a href="{{ route('manager.page_templates.index') }}" class="mc-btn mc-btn-secondary mc-btn-sm">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'arrow-left', 'size' => 16])
                {{ trans('campaign::page-templates.create.back_to_index') }}
            </a>
        </div>
    </div>
@endsection

@section('content')
    <form id="admin-form-template-create-form" method="POST" action="{{ route('manager.page_templates.store') }}">
        @csrf

        {{-- Name card --}}
        <div class="mc-card" style="margin-bottom:var(--space-5);">
            <div class="mc-card-header">
                <h3 class="mc-card-title">
                    @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'edit', 'size' => 18])
                    {{ trans('campaign::page-templates.create.name_card_title') }}
                </h3>
            </div>
            <div style="padding:0 var(--space-5) var(--space-5);">
                <input type="text"
                       name="name"
                       id="admin-form-tpl-name"
                       class="mc-form-input"
                       placeholder="{{ trans('campaign::page-templates.fields.name_placeholder') }}"
                       value="{{ old('name', trans('campaign::page-templates.fields.name_placeholder')) }}"
                       autofocus
                       required>
                <div class="mc-form-help">{{ trans('campaign::page-templates.field_help.name') }}</div>
                <div class="mc-form-error mc-hidden" data-error="name"></div>
            </div>
        </div>

        <input type="hidden" name="template" id="admin-form-tpl-selected-uid" value="">

        {{-- Gallery card --}}
        <div class="mc-card mc-card-flush" style="margin-bottom:var(--space-5);">
            <div class="mc-card-header">
                <div>
                    <h3 class="mc-card-title">
                        @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'grid', 'size' => 18])
                        {{ trans('campaign::page-templates.create.gallery_card_title') }}
                    </h3>
                    <p class="mc-card-subtitle">{{ trans('campaign::page-templates.create.preview_hint') }}</p>
                </div>
                <div class="mc-tabs" data-mc-tabs id="admin-form-tpl-gallery-tabs">
                    <button type="button" class="mc-tab active" data-tab="base">
                        @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'star', 'size' => 14])
                        {{ trans('campaign::page-templates.tabs.base') }}
                    </button>
                    <button type="button" class="mc-tab" data-tab="extended">
                        @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'layers', 'size' => 14])
                        {{ trans('campaign::page-templates.tabs.extended') }}
                    </button>
                </div>
            </div>
            <div id="admin-form-tpl-gallery"
                 data-gallery-url="{{ route('manager.page_templates.gallery') }}"
                 class="mc-gallery-container">
                <div style="padding:var(--space-12) var(--space-6);text-align:center;color:var(--color-text-muted);">
                    <div class="mc-spinner"></div>
                    <div style="margin-top:var(--space-3);font-size:var(--text-sm);">
                        {{ trans('campaign::page-templates.create.loading') }}
                    </div>
                </div>
            </div>
        </div>

        <div class="mc-form-error mc-hidden" data-error="template" style="margin-bottom:var(--space-3);"></div>

        <div class="mc-form-actions" style="display:flex;justify-content:flex-end;gap:var(--space-3);">
            <a href="{{ route('manager.page_templates.index') }}" class="mc-btn mc-btn-secondary">
                {{ trans('campaign::page-templates.buttons.cancel') }}
            </a>
            <button type="submit" id="admin-form-tpl-submit" class="mc-btn mc-btn-primary" disabled>
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'check', 'size' => 16])
                {{ trans('campaign::page-templates.create.submit') }}
            </button>
        </div>
    </form>
@endsection

@section('scripts')
<script>
(function() {
    var galleryEl  = document.getElementById('admin-form-tpl-gallery');
    var galleryUrl = galleryEl.dataset.galleryUrl;
    var selectedInput = document.getElementById('admin-form-tpl-selected-uid');
    var submitBtn  = document.getElementById('admin-form-tpl-submit');
    var nameInput  = document.getElementById('admin-form-tpl-name');
    var form       = document.getElementById('admin-form-template-create-form');
    var tabsEl     = document.getElementById('admin-form-tpl-gallery-tabs');
    var currentTab = 'base';

    function loadGallery(page) {
        var params = new URLSearchParams();
        params.set('tab', currentTab);
        if (page) params.set('page', page);

        galleryEl.innerHTML = '<div style="padding:var(--space-12) var(--space-6);text-align:center;color:var(--color-text-muted);"><div class="mc-spinner"></div><div style="margin-top:var(--space-3);font-size:var(--text-sm);">{{ trans('campaign::page-templates.create.loading') }}</div></div>';

        fetch(galleryUrl + '?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.text(); })
        .then(function(html) {
            galleryEl.innerHTML = html;
            bindGalleryEvents();
        })
        .catch(function() {
            galleryEl.innerHTML = '<div style="padding:var(--space-8);text-align:center;color:var(--color-text-muted);">Failed to load gallery.</div>';
        });
    }

    function bindGalleryEvents() {
        galleryEl.querySelectorAll('[data-template-uid]').forEach(function(card) {
            card.addEventListener('click', function() {
                galleryEl.querySelectorAll('[data-template-uid]').forEach(function(c) {
                    c.classList.remove('is-selected');
                });
                this.classList.add('is-selected');
                selectedInput.value = this.dataset.templateUid;
                submitBtn.disabled = false;
            });
        });

        galleryEl.querySelectorAll('[data-page]').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                if (this.classList.contains('is-disabled') || this.classList.contains('is-current')) return;
                loadGallery(this.dataset.page);
            });
        });
    }

    if (tabsEl) {
        tabsEl.querySelectorAll('.mc-tab').forEach(function(tab) {
            tab.addEventListener('click', function() {
                tabsEl.querySelectorAll('.mc-tab').forEach(function(t) { t.classList.remove('active'); });
                this.classList.add('active');
                currentTab = this.dataset.tab;
                selectedInput.value = '';
                submitBtn.disabled = true;
                loadGallery();
            });
        });
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        form.querySelectorAll('[data-error]').forEach(function(el) {
            el.classList.add('mc-hidden');
            el.textContent = '';
        });

        if (!selectedInput.value) {
            var err = form.querySelector('[data-error="template"]');
            err.textContent = '{{ trans('campaign::page-templates.create.select_first') }}';
            err.classList.remove('mc-hidden');
            return;
        }

        var formData = new FormData(form);
        submitBtn.disabled = true;

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': formData.get('_token'),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(function(r) { return r.json().then(function(d) { return { status: r.status, data: d }; }); })
        .then(function(res) {
            if (res.status === 422 && res.data.errors) {
                Object.keys(res.data.errors).forEach(function(field) {
                    var errEl = form.querySelector('[data-error="' + field + '"]');
                    if (errEl) {
                        errEl.textContent = res.data.errors[field][0];
                        errEl.classList.remove('mc-hidden');
                    }
                });
                submitBtn.disabled = false;
            } else if (res.data.status === 'success') {
                if (window.McNotify) window.McNotify.success(res.data.message);
                window.location.href = res.data.redirect || '{{ route('manager.page_templates.index') }}';
            } else {
                submitBtn.disabled = false;
            }
        })
        .catch(function() {
            submitBtn.disabled = false;
        });
    });

    nameInput.addEventListener('focus', function() { this.select(); });
    loadGallery();
})();
</script>
@endsection

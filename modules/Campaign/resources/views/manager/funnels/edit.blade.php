@extends('campaign::refactor.layout')

@section('title', trans('campaign::funnels.edit.title'))

@php
    $funnelPublished = false;
    try { $funnelPublished = $funnel->isPublished(); } catch (\Throwable $e) { $funnelPublished = ($funnel->status ?? null) === 'published'; }

    $stepTypes = [
        'landing'   => trans('campaign::funnels.step.type.landing'),
        'optin'     => trans('campaign::funnels.step.type.optin'),
        'thank_you' => trans('campaign::funnels.step.type.thank_you'),
        'sales'     => trans('campaign::funnels.step.type.sales'),
        'checkout'  => trans('campaign::funnels.step.type.checkout'),
        'custom'    => trans('campaign::funnels.step.type.custom'),
    ];
@endphp

@section('page-header')
    <div class="mc-page-header">
        <div>
            <h1 class="mc-page-title">{{ $funnel->name ?: trans('campaign::funnels.untitled') }}</h1>
            <p class="mc-page-subtitle">{{ trans('campaign::funnels.edit.subtitle') }}</p>
        </div>
        <div class="mc-page-actions">
            <a href="{{ route('manager.funnels.index') }}" class="mc-btn mc-btn-secondary mc-btn-sm">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'arrow-left', 'size' => 16])
                {{ trans('campaign::funnels.buttons.cancel') }}
            </a>
            @if ($funnelPublished)
                <button type="button" class="mc-btn mc-btn-secondary mc-btn-sm"
                        id="funnel-publish-toggle"
                        data-publish-url="{{ route('manager.funnels.unpublish', $funnel->uid) }}">
                    @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'unpublished', 'size' => 16])
                    {{ trans('campaign::funnels.action.unpublish') }}
                </button>
            @else
                <button type="button" class="mc-btn mc-btn-primary mc-btn-sm"
                        id="funnel-publish-toggle"
                        data-publish-url="{{ route('manager.funnels.publish', $funnel->uid) }}">
                    @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'publish', 'size' => 16])
                    {{ trans('campaign::funnels.action.publish') }}
                </button>
            @endif
        </div>
    </div>
@endsection

@section('content')

{{-- Steps card --}}
<div class="mc-card" style="margin-bottom:var(--space-5);">
    <div class="mc-card-header">
        <h3 class="mc-card-title">
            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'layers', 'size' => 18])
            {{ trans('campaign::funnels.edit.title') }}
        </h3>
        <button type="button" class="mc-btn mc-btn-primary mc-btn-sm" id="funnel-add-step-btn">
            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'add', 'size' => 16])
            {{ trans('campaign::funnels.buttons.add_step') }}
        </button>
    </div>

    <div style="padding:var(--space-2) var(--space-5) var(--space-5);">
        @if ($funnel->steps->count() > 0)
            <div class="mc-form-list" id="funnel-steps-list">
                @foreach ($funnel->steps as $step)
                @php
                    $thumbUrl = null;
                    try { $thumbUrl = $step->getThumbnailUrl(); } catch (\Throwable $e) { $thumbUrl = null; }
                    $typeLabel = $stepTypes[$step->type] ?? $step->type;
                @endphp
                <div class="mc-form-list-row" data-step-uid="{{ $step->uid }}">
                    <a href="{{ route('manager.funnels.steps.builder', $step->uid) }}" class="mc-form-list-thumb">
                        @if ($thumbUrl)
                            <img src="{{ $thumbUrl }}" alt="{{ $step->name }}" decoding="async">
                        @else
                            <div class="mc-form-list-thumb-placeholder">
                                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'web', 'size' => 22])
                            </div>
                        @endif
                    </a>

                    <div class="mc-form-list-body">
                        <div class="mc-form-list-header">
                            <a href="{{ route('manager.funnels.steps.builder', $step->uid) }}" class="mc-form-list-name">{{ $step->name }}</a>
                            <span class="mc-badge mc-badge-default">{{ $typeLabel }}</span>
                        </div>
                        <div class="mc-form-list-meta">
                            <span class="mc-form-list-meta-item">
                                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'list', 'size' => 14])
                                #{{ $step->sort_order }}
                            </span>
                        </div>
                    </div>

                    <div class="mc-form-list-actions">
                        <a href="{{ route('manager.funnels.steps.builder', $step->uid) }}" class="mc-btn mc-btn-secondary mc-btn-sm">
                            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'edit', 'size' => 16])
                            {{ trans('campaign::funnels.action.edit') }}
                        </a>
                        <div class="mc-dropdown" data-dropdown>
                            <button class="mc-btn mc-btn-ghost mc-btn-sm mc-btn-icon" data-dropdown-trigger>
                                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'more-v', 'size' => 18])
                            </button>
                            <div class="mc-dropdown-menu mc-dropdown-menu-end">
                                <a class="mc-dropdown-item" href="{{ route('manager.funnels.steps.preview', $step->uid) }}" target="_blank">
                                    @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'eye', 'size' => 16])
                                    {{ trans('campaign::funnels.action.preview') }}
                                </a>
                                <a class="mc-dropdown-item" href="#"
                                   data-step-duplicate="{{ route('manager.funnels.steps.duplicate', $step->uid) }}">
                                    @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'copy', 'size' => 16])
                                    {{ trans('campaign::funnels.action.duplicate') }}
                                </a>
                                <div class="mc-dropdown-divider"></div>
                                <a class="mc-dropdown-item mc-dropdown-item-danger" href="#"
                                   data-step-delete="{{ route('manager.funnels.steps.delete', $step->uid) }}">
                                    @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'delete', 'size' => 16])
                                    {{ trans('campaign::funnels.action.delete') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <div class="mc-empty-state" id="funnel-steps-empty">
                <div class="mc-empty-illustration">
                    <svg viewBox="0 0 160 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="48" y="20" width="64" height="20" rx="5" fill="var(--color-card-bg)" stroke="var(--color-border-strong)" stroke-width="1.5"/>
                        <rect x="56" y="50" width="48" height="20" rx="5" fill="var(--color-card-bg)" stroke="var(--color-border-strong)" stroke-width="1.5"/>
                        <rect x="64" y="80" width="32" height="20" rx="5" fill="var(--color-teal)" opacity="0.18" stroke="var(--color-teal)" stroke-width="1.5"/>
                    </svg>
                </div>
                <div class="mc-empty-state-text">{{ trans('campaign::funnels.edit.no_steps') }}</div>
            </div>
        @endif
    </div>
</div>

{{-- Add step inline panel (hidden by default) --}}
<div class="mc-card mc-hidden" id="funnel-add-step-panel" style="margin-bottom:var(--space-5);">
    <div class="mc-card-header">
        <h3 class="mc-card-title">
            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'add', 'size' => 18])
            {{ trans('campaign::funnels.buttons.add_step') }}
        </h3>
        <button type="button" class="mc-btn mc-btn-ghost mc-btn-sm mc-btn-icon" id="funnel-add-step-close">
            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'close', 'size' => 18])
        </button>
    </div>
    <form id="funnel-step-form"
          method="POST"
          action="{{ route('manager.funnels.steps.store', $funnel->uid) }}"
          data-gallery-url="{{ route('manager.funnels.steps.template_gallery', $funnel->uid) }}">
        @csrf
        <input type="hidden" name="template_uid" id="funnel-step-template-uid" value="">
        <div style="padding:0 var(--space-5) var(--space-5);">
            <div style="display:grid;grid-template-columns:1fr 220px;gap:var(--space-4);">
                <div class="mc-form-group">
                    <label class="mc-form-label" for="funnel-step-name">
                        {{ trans('campaign::funnels.edit.step_name') }}
                        <span style="color:var(--color-error)">*</span>
                    </label>
                    <input type="text" name="name" id="funnel-step-name" class="mc-form-input" required>
                    <div class="mc-form-error mc-hidden" data-error="name"></div>
                </div>
                <div class="mc-form-group">
                    <label class="mc-form-label" for="funnel-step-type">{{ trans('campaign::funnels.edit.step_type') }}</label>
                    <select name="type" id="funnel-step-type" class="mc-form-input mc-form-select">
                        @foreach ($stepTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="mc-form-error mc-hidden" data-error="type"></div>
                </div>
            </div>

            {{-- Template gallery (optional starting point; blank if none selected) --}}
            <div class="mc-form-group" style="margin-top:var(--space-4);">
                <label class="mc-form-label">{{ trans('campaign::funnels.banner.title') }}</label>
                <div id="funnel-step-gallery" class="mc-gallery-container">
                    <div style="padding:var(--space-8);text-align:center;color:var(--color-text-muted);">
                        <div class="mc-spinner"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mc-form-actions" style="display:flex;justify-content:flex-end;gap:var(--space-3);padding:0 var(--space-5) var(--space-5);">
            <button type="button" class="mc-btn mc-btn-secondary" id="funnel-add-step-cancel">
                {{ trans('campaign::funnels.buttons.cancel') }}
            </button>
            <button type="submit" class="mc-btn mc-btn-primary" id="funnel-step-submit">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'check', 'size' => 16])
                {{ trans('campaign::funnels.buttons.add_step') }}
            </button>
        </div>
    </form>
</div>

@endsection

@section('scripts')
<script>
(function() {
    var CSRF = '{{ csrf_token() }}';

    // ---- Publish / unpublish toggle ----
    var pubBtn = document.getElementById('funnel-publish-toggle');
    if (pubBtn) {
        pubBtn.addEventListener('click', function() {
            pubBtn.disabled = true;
            fetch(pubBtn.dataset.publishUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' }
            }).then(function(r) { return r.json(); }).then(function(d) {
                if (window.McNotify && d.message) window.McNotify.success(d.message);
                window.location.reload();
            }).catch(function() { pubBtn.disabled = false; });
        });
    }

    // ---- Step duplicate / delete (event delegation) ----
    document.addEventListener('click', function(e) {
        var dupBtn = e.target.closest('[data-step-duplicate]');
        if (dupBtn) {
            e.preventDefault();
            fetch(dupBtn.dataset.stepDuplicate, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' }
            }).then(function(r) { return r.json(); }).then(function(d) {
                if (window.McNotify && d.message) window.McNotify.success(d.message);
                window.location.reload();
            });
            return;
        }

        var delBtn = e.target.closest('[data-step-delete]');
        if (delBtn) {
            e.preventDefault();
            window.McDialog.confirm({
                title: '{{ trans("campaign::funnels.action.delete") }}',
                message: '{{ trans("campaign::funnels.edit.no_steps") }}',
                type: 'danger',
                confirmText: '{{ trans("campaign::funnels.action.delete") }}',
                onConfirm: function() {
                    fetch(delBtn.dataset.stepDelete, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' }
                    }).then(function(r) { return r.json(); }).then(function(d) {
                        if (window.McNotify && d.message) window.McNotify.success(d.message);
                        window.location.reload();
                    });
                }
            });
        }
    });

    // ---- Add step panel ----
    var panel      = document.getElementById('funnel-add-step-panel');
    var openBtn    = document.getElementById('funnel-add-step-btn');
    var closeBtn   = document.getElementById('funnel-add-step-close');
    var cancelBtn  = document.getElementById('funnel-add-step-cancel');
    var form       = document.getElementById('funnel-step-form');
    var galleryEl  = document.getElementById('funnel-step-gallery');
    var tplInput   = document.getElementById('funnel-step-template-uid');
    var submitBtn  = document.getElementById('funnel-step-submit');
    var galleryLoaded = false;

    function renderGallery(items) {
        if (!items || !items.length) {
            galleryEl.innerHTML = '<div style="padding:var(--space-6);text-align:center;color:var(--color-text-muted);font-size:var(--text-sm);">—</div>';
            return;
        }
        var html = '<div class="mc-template-grid mc-template-grid--picker" style="padding:var(--space-3) 0;">';
        items.forEach(function(it) {
            var thumb = it.thumb
                ? '<img src="' + it.thumb + '" alt="" decoding="async">'
                : '<div class="mc-template-thumb-placeholder"></div>';
            html += '<div class="mc-template-card mc-template-card--picker" data-template-uid="' + it.uid + '" tabindex="0" role="button">' +
                        '<div class="mc-template-thumb">' + thumb +
                            '<div class="mc-template-card-check">&#10003;</div>' +
                        '</div>' +
                        '<div class="mc-template-info"><div class="mc-template-name">' + (it.name || '') + '</div></div>' +
                    '</div>';
        });
        html += '</div>';
        galleryEl.innerHTML = html;

        galleryEl.querySelectorAll('[data-template-uid]').forEach(function(card) {
            card.addEventListener('click', function() {
                var alreadySelected = this.classList.contains('is-selected');
                galleryEl.querySelectorAll('[data-template-uid]').forEach(function(c) { c.classList.remove('is-selected'); });
                if (alreadySelected) {
                    // toggle off → blank page
                    tplInput.value = '';
                } else {
                    this.classList.add('is-selected');
                    tplInput.value = this.dataset.templateUid;
                }
            });
        });
    }

    function loadGallery() {
        if (galleryLoaded) return;
        galleryLoaded = true;
        fetch(form.dataset.galleryUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(items) { renderGallery(items); })
            .catch(function() {
                galleryEl.innerHTML = '<div style="padding:var(--space-6);text-align:center;color:var(--color-text-muted);font-size:var(--text-sm);">—</div>';
            });
    }

    function openPanel() {
        panel.classList.remove('mc-hidden');
        loadGallery();
        var nameEl = document.getElementById('funnel-step-name');
        if (nameEl) nameEl.focus();
    }
    function closePanel() { panel.classList.add('mc-hidden'); }

    if (openBtn) openBtn.addEventListener('click', openPanel);
    if (closeBtn) closeBtn.addEventListener('click', closePanel);
    if (cancelBtn) cancelBtn.addEventListener('click', closePanel);

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            form.querySelectorAll('[data-error]').forEach(function(el) {
                el.classList.add('mc-hidden');
                el.textContent = '';
            });

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
                    if (window.McNotify && res.data.message) window.McNotify.success(res.data.message);
                    window.location.href = res.data.redirect || '{{ route('manager.funnels.edit', $funnel->uid) }}';
                } else {
                    if (window.McNotify && res.data.message) window.McNotify.error(res.data.message);
                    submitBtn.disabled = false;
                }
            })
            .catch(function() { submitBtn.disabled = false; });
        });
    }
})();
</script>
@endsection

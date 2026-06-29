{{--
    Theme picker popup (Builder).

    Replaces the legacy Bootstrap modal — uses the rui/ui mc-modal + mc-tabs
    primitives so it matches the admin email-templates listing the user
    already accepts. Server-renders cards (no JS innerHTML rebuild) and
    partitions templates by the actual Base/Extended taxonomy instead of
    the legacy `take(13) / skip(13)` magic split.

    Required variables (passed from builder/default.blade.php):
      - $templates          Collection<Template>  Eager-loaded with categories.
      - $changeTemplateUrl  string                Endpoint for change-template POST.
--}}
@php
    // Partition by category (Base / Extended). Templates without a category
    // attached fall into Base so they remain visible — the taxonomy migration
    // (2026_04_09) backfilled all rows, but a defensive default avoids hiding
    // edge-case data in the picker.
    $baseTemplates = $templates->filter(function ($t) {
        return $t->categories->contains('name', 'Extended') === false;
    })->values();

    $extendedTemplates = $templates->filter(function ($t) {
        return $t->categories->contains('name', 'Extended') === true;
    })->values();
@endphp

<div class="mc-modal-backdrop mc-theme-picker-backdrop" data-theme-picker-close></div>
<div class="mc-modal mc-modal-xxl mc-theme-picker" role="dialog" aria-modal="true" aria-labelledby="ThemePickerTitle">
    <div class="mc-modal-header">
        <h3 class="mc-modal-title" id="ThemePickerTitle">{{ trans('campaign::builder.select_theme_title') }}</h3>
        <button type="button" class="mc-modal-close" aria-label="{{ trans('campaign::builder.close') }}" data-theme-picker-close>
            <span class="material-symbols-rounded" aria-hidden="true">close</span>
        </button>
    </div>

    <div class="mc-modal-body mc-theme-picker-body">
        {{-- Hero intro: lead-line + 3 inline guidelines with icons. Replaces
             the legacy blue mc-alert-info that visually clashed with the
             rest of the rui/ui (teal-accented). The hero earns its space
             by being scannable + reassuring at a glance. --}}
        <div class="mc-theme-picker-hero">
            <div class="mc-theme-picker-hero-text">
                <h4 class="mc-theme-picker-hero-title">{{ trans('campaign::builder.theme_picker_hero_title') }}</h4>
                <p class="mc-theme-picker-hero-lead">{{ trans('campaign::builder.theme_intro') }}</p>
                <ul class="mc-theme-picker-tips">
                    <li>
                        <span class="material-symbols-rounded" aria-hidden="true">touch_app</span>
                        {{ trans('campaign::builder.theme_picker_tip_click') }}
                    </li>
                    <li>
                        <span class="material-symbols-rounded" aria-hidden="true">history</span>
                        {{ trans('campaign::builder.theme_picker_tip_undo') }}
                    </li>
                    <li>
                        <span class="material-symbols-rounded" aria-hidden="true">verified</span>
                        {{ trans('campaign::builder.theme_picker_tip_safe') }}
                    </li>
                </ul>
            </div>
            <div class="mc-theme-picker-hero-art" aria-hidden="true">
                <span class="material-symbols-rounded" style="font-size:96px;opacity:.25">palette</span>
            </div>
        </div>

        {{-- Compact rich-tab pills: icon + 2-line label/description. --}}
        <div class="mc-rich-tabs mc-theme-picker-rich-tabs" id="theme-picker-tabs" role="tablist">
            <button type="button" class="mc-rich-tab active" data-theme-picker-tab="base" role="tab" aria-selected="true">
                <span class="mc-rich-tab-icon material-symbols-rounded" aria-hidden="true">backup_table</span>
                <span class="mc-rich-tab-body">
                    <span class="mc-rich-tab-title">
                        {{ trans('campaign::builder.theme_picker_tab_base') }}
                        <span class="mc-rich-tab-count">{{ $baseTemplates->count() }}</span>
                    </span>
                    <span class="mc-rich-tab-desc">{{ trans('campaign::builder.theme_picker_tab_base_desc') }}</span>
                </span>
            </button>
            <button type="button" class="mc-rich-tab" data-theme-picker-tab="extended" role="tab" aria-selected="false">
                <span class="mc-rich-tab-icon material-symbols-rounded" aria-hidden="true">view_quilt</span>
                <span class="mc-rich-tab-body">
                    <span class="mc-rich-tab-title">
                        {{ trans('campaign::builder.theme_picker_tab_extended') }}
                        <span class="mc-rich-tab-count">{{ $extendedTemplates->count() }}</span>
                    </span>
                    <span class="mc-rich-tab-desc">{{ trans('campaign::builder.theme_picker_tab_extended_desc') }}</span>
                </span>
            </button>
        </div>

        <div class="mc-theme-picker-panels">
            <div data-theme-picker-panel="base">
                @include('campaign::builder._change_theme_popup_grid', [
                    'collection' => $baseTemplates,
                    'changeTemplateUrl' => $changeTemplateUrl,
                ])
            </div>
            <div data-theme-picker-panel="extended" hidden>
                @include('campaign::builder._change_theme_popup_grid', [
                    'collection' => $extendedTemplates,
                    'changeTemplateUrl' => $changeTemplateUrl,
                ])
            </div>
        </div>
    </div>

    <div class="mc-modal-footer">
        <button type="button" class="mc-btn mc-btn-light" data-theme-picker-close>
            {{ trans('campaign::builder.close') }}
        </button>
    </div>

    {{-- Mask shown after a card click while the change-template POST is in
         flight + the page is reloading. Keeps the popup visible (modal stays
         active) so the user gets clear feedback even when source/target
         template content is identical and the post-reload canvas looks the
         same as before. Re-uses the rui/ui mc-popup-mask primitive. --}}
    <div class="mc-popup-mask mc-theme-picker-mask" hidden>
        <div class="mc-spinner mc-spinner-lg"></div>
        <div class="mc-popup-mask-text">{{ trans('campaign::builder.theme_picker_applying') }}</div>
    </div>
</div>

<script>
    (function () {
        'use strict';

        var backdrop = document.querySelector('.mc-theme-picker-backdrop');
        var modal    = document.querySelector('.mc-theme-picker');
        if (!backdrop || !modal) return;

        function show() {
            requestAnimationFrame(function () {
                backdrop.classList.add('active');
                modal.classList.add('active');
            });
            document.body.style.overflow = 'hidden';
        }

        function hide() {
            backdrop.classList.remove('active');
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }

        // Tab switching — pure DOM toggle (no McTabs dep; builder layout
        // doesn't load Tabs.js, and the popup only ever has 2 tabs).
        var tabs   = modal.querySelectorAll('[data-theme-picker-tab]');
        var panels = modal.querySelectorAll('[data-theme-picker-panel]');
        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                if (tab.classList.contains('active')) return;
                var key = tab.dataset.themePickerTab;
                tabs.forEach(function (t) {
                    var on = t === tab;
                    t.classList.toggle('active', on);
                    t.setAttribute('aria-selected', on ? 'true' : 'false');
                });
                panels.forEach(function (p) {
                    p.hidden = (p.dataset.themePickerPanel !== key);
                });
            });
        });

        // Close: backdrop click, [data-theme-picker-close], Escape
        backdrop.addEventListener('click', hide);
        modal.addEventListener('click', function (e) {
            if (e.target.closest('[data-theme-picker-close]')) {
                hide();
            }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal.classList.contains('active')) hide();
        });

        // "Add a new theme" tile → open builder side-panel import tab + close.
        modal.addEventListener('click', function (e) {
            var addTile = e.target.closest('[data-theme-picker-add]');
            if (!addTile) return;
            e.preventDefault();
            if (window.smenu) {
                var importTab = document.querySelector('[data-smenu=import]');
                if (importTab) window.smenu.openTab(importTab);
            }
            hide();
        });

        // Theme card click bubbles up to the existing
        // [data-control="change-template"] jQuery handler at the bottom of
        // builder/default.blade.php (POST → reload). DON'T hide the popup
        // here — many demo templates share identical JSON content, so a
        // silent close + reload looks like nothing happened. Instead show a
        // mask + "Applying theme…" spinner over the popup; the mask stays
        // up until the page reloads (or 3.5s safety timeout if reload is
        // somehow blocked by the upstream handler).
        var mask = modal.querySelector('.mc-theme-picker-mask');
        modal.addEventListener('click', function (e) {
            var card = e.target.closest('[data-control="change-template"]');
            if (!card || !mask) return;
            mask.hidden = false;
            modal.classList.add('mc-theme-picker--applying');
            // Safety: if the upstream POST fails silently, drop the mask
            // after 8s so the popup stays usable.
            setTimeout(function () {
                if (!mask.hidden) {
                    mask.hidden = true;
                    modal.classList.remove('mc-theme-picker--applying');
                }
            }, 8000);
        });

        // Public API — keeps the trigger button (`changeThemePopup.show()`)
        // from the topbar working without renaming.
        window.changeThemePopup = { show: show, hide: hide };
    }());
</script>

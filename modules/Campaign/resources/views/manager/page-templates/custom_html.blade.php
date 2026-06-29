@extends('campaign::refactor.layout')

@section('title', trans('campaign::page-templates.custom_html.title') . ' — ' . $pageTemplate->name)

@section('head')
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
<script src="{{ asset('refactor/js/codemirror-html.js') }}"></script>
@endsection

@section('page-header')
    <div>
        <h1 class="mc-page-title">{{ trans('campaign::page-templates.custom_html.title') }}</h1>
        <p class="mc-section-desc">
            <a href="{{ route('manager.page_templates.index') }}" class="mc-breadcrumb-link">{{ trans('campaign::page-templates.page.title') }}</a>
            <span class="mc-breadcrumb-sep">/</span>
            {{ $pageTemplate->name }}
            <span class="mc-breadcrumb-sep">/</span>
            {{ trans('campaign::page-templates.custom_html.title') }}
        </p>
    </div>
@endsection

@section('content')
    <form id="html-form" method="POST" action="{{ route('manager.page_templates.custom_html', $pageTemplate->uid) }}" data-mc-spinner style="margin-top:var(--space-4)">
        @csrf
        <div class="mc-card">
            <div class="mc-card-header" style="border-bottom:0;margin-bottom:0;padding-bottom:0">
                <div class="mc-tabs">
                    <a href="{{ route('manager.page_templates.builder', $pageTemplate->uid) }}" class="mc-tab mc-tab-link">
                        @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'grid', 'size' => 14])
                        {{ trans('campaign::page-templates.action.edit_builder') }}
                    </a>
                    <button type="button" class="mc-tab active" disabled>
                        @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'edit', 'size' => 14])
                        {{ trans('campaign::page-templates.custom_html.title') }}
                    </button>
                </div>
            </div>
            <div class="mc-wizard-card-body">
                @include('campaign::refactor.partials._custom_html_editor', [
                    'currentHtml' => $currentHtml,
                    'labels' => [
                        'edit_source' => trans('campaign::page-templates.custom_html.edit_source'),
                        'visual'      => trans('campaign::page-templates.custom_html.visual_editor'),
                        'themeDark'   => trans('campaign::page-templates.custom_html.theme_dark'),
                        'themeLight'  => trans('campaign::page-templates.custom_html.theme_light'),
                        'toggleTheme' => trans('campaign::page-templates.custom_html.toggle_editor_theme'),
                        'tags_hint'   => trans('campaign::page-templates.custom_html.tags_hint'),
                    ],
                    'firstSwitchWarning' => $pageTemplate->template?->isUploaded() ? null : [
                        'title'   => trans('campaign::page-templates.custom_html.first_switch_title'),
                        'message' => trans('campaign::page-templates.custom_html.first_switch_message'),
                        'confirm' => trans('campaign::page-templates.custom_html.first_switch_confirm'),
                    ],
                ])
            </div>
        </div>

        <div class="mc-wizard-footer">
            <a href="{{ route('manager.page_templates.index') }}" class="mc-btn mc-btn-secondary" data-mc-spinner>
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'arrow-left', 'size' => 18, 'class' => 'mc-btn-icon-left']) {{ trans('campaign::page-templates.action.cancel') }}
            </a>
            <button type="submit" class="mc-btn mc-btn-primary">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'check', 'size' => 18, 'class' => 'mc-btn-icon-left']) {{ trans('campaign::page-templates.custom_html.save') }}
            </button>
        </div>
    </form>
@endsection

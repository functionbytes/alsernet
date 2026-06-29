{{--
    Theme grid for one tab of the theme picker.

    Inputs:
      - $collection         Collection<Template>
      - $changeTemplateUrl  string
--}}
@if ($collection->isEmpty())
    <div class="mc-theme-picker-empty">
        <span class="material-symbols-rounded" aria-hidden="true">inbox</span>
        <p>{{ trans('campaign::builder.theme_picker_empty') }}</p>
    </div>
@else
    <div class="mc-theme-picker-grid">
        @foreach ($collection as $tpl)
            <a href="{{ $changeTemplateUrl }}"
               data-control="change-template"
               data-id="{{ $tpl->uid }}"
               class="mc-theme-card"
               title="{{ $tpl->name }}">
                <div class="mc-theme-card-thumb">
                    <img src="{{ $tpl->getThumbUrl() }}" alt="" loading="lazy">
                </div>
                <div class="mc-theme-card-meta">
                    <span class="mc-theme-card-label">{{ $tpl->name }}</span>
                </div>
            </a>
        @endforeach

        <button type="button" class="mc-theme-card mc-theme-card--add" data-theme-picker-add>
            <div class="mc-theme-card-thumb mc-theme-card-thumb--add">
                <span class="material-symbols-rounded" aria-hidden="true">add_box</span>
            </div>
            <div class="mc-theme-card-meta">
                <span class="mc-theme-card-label">{{ trans('campaign::builder.theme_picker_add_label') }}</span>
                <span class="mc-theme-card-desc">{{ trans('campaign::builder.theme_picker_add_desc') }}</span>
            </div>
        </button>
    </div>
@endif

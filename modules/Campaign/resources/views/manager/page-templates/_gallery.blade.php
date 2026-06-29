{{--
    Admin form templates picker gallery — returned by PageTemplateController@gallery.
    Used inside `create.blade.php` (loaded via fetch on tab change).

    Inputs:
      $items (LengthAwarePaginator of PageTemplate)
      $tab   ('base' | 'extended')
--}}
@if ($items->count() > 0)
    <div class="mc-template-grid mc-template-grid--picker" style="padding:var(--space-5);">
        @foreach ($items as $item)
            <div class="mc-template-card mc-template-card--picker"
                 data-template-uid="{{ $item->uid }}"
                 tabindex="0"
                 role="button"
                 aria-label="{{ $item->name }}">
                <div class="mc-template-thumb">
                    @if ($item->template && $item->template->getThumbnailUrl())
                        <img src="{{ $item->template->getThumbnailUrl() }}"
                             alt="{{ $item->name }}"
                             decoding="async">
                    @else
                        <div class="mc-template-thumb-placeholder">
                            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'file-text', 'size' => 48])
                        </div>
                    @endif
                    <div class="mc-template-card-check">
                        @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'check', 'size' => 18])
                    </div>
                </div>
                <div class="mc-template-info">
                    <div class="mc-template-name">{{ $item->name }}</div>
                    <div class="mc-template-meta">
                        @if ($item->template && $item->template->categories()->count() > 0)
                            @foreach ($item->template->categories()->get() as $cat)
                                @if ($cat->name === 'Popup')
                                    <span class="mc-badge mc-badge-default" style="font-size:var(--text-xs);">{{ $cat->name }}</span>
                                @endif
                            @endforeach
                        @endif
                    </div>
                </div>

                {{-- Creative touch — hover info popover with full name + category context + preview link --}}
                <div class="mc-template-hover-info" aria-hidden="true">
                    <div class="mc-template-hover-name">{{ $item->name }}</div>
                    @if ($item->template && $item->template->categories()->count() > 0)
                        <div class="mc-template-hover-cats">
                            @foreach ($item->template->categories()->get() as $cat)
                                @if (in_array($cat->name, ['Popup', 'Base', 'Extended']))
                                    <span class="mc-badge mc-badge-default">{{ $cat->name }}</span>
                                @endif
                            @endforeach
                        </div>
                    @endif
                    <a href="{{ route('manager.page_templates.preview', $item->uid) }}"
                       data-preview="{{ route('manager.page_templates.preview', $item->uid) }}"
                       data-preview-title="{{ trans('campaign::page-templates.action.preview') }}: {{ $item->name }}"
                       class="mc-template-hover-preview"
                       onclick="event.stopPropagation();">
                        @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'eye', 'size' => 14])
                        {{ trans('campaign::page-templates.action.preview') }}
                    </a>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Pagination (uses shared mc-pagination component) --}}
    @include('campaign::refactor.partials._pagination', ['items' => $items])
@else
    <div class="mc-empty-state" style="padding:var(--space-12) var(--space-6);">
        <div class="mc-empty-illustration">
            <svg viewBox="0 0 160 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="35" y="20" width="90" height="80" rx="6" fill="var(--illust-bg)" stroke="var(--illust-stroke)" stroke-width="1.5"/>
                <rect x="50" y="35" width="60" height="8" rx="2" fill="var(--illust-fill)" stroke="var(--illust-stroke)" stroke-width="1"/>
                <rect x="50" y="50" width="60" height="8" rx="2" fill="var(--illust-fill)" stroke="var(--illust-stroke)" stroke-width="1"/>
                <rect x="50" y="65" width="40" height="8" rx="2" fill="var(--illust-teal)" stroke="var(--illust-teal-bold)" stroke-width="1"/>
            </svg>
        </div>
        <div class="mc-empty-state-title">{{ trans('campaign::page-templates.create.no_templates') }}</div>
        <div class="mc-empty-state-text">
            {{ $tab === 'extended'
                ? trans('campaign::page-templates.empty.gallery_extended')
                : trans('campaign::page-templates.empty.gallery_base') }}
        </div>
    </div>
@endif

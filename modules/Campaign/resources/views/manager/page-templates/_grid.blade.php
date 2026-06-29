@if ($items->count() > 0)
    <div class="mc-template-grid" style="padding:var(--space-5)">
        @foreach ($items as $item)
            @php
                $isUploaded = $item->template && $item->template->isUploaded();
                $editRoute  = $isUploaded
                    ? route('manager.page_templates.custom_html', $item->uid)
                    : route('manager.page_templates.builder', $item->uid);
                $editLabel  = $isUploaded
                    ? trans('campaign::page-templates.action.edit_html')
                    : trans('campaign::page-templates.action.edit_builder');
            @endphp
            <div class="mc-template-card">
                <div class="mc-template-thumb">
                    @if ($item->template && $item->template->getThumbnailUrl())
                        <img src="{{ $item->template->getThumbnailUrl() }}" alt="{{ $item->name }}" decoding="async">
                    @else
                        <div class="mc-template-thumb-placeholder">
                            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'file-text', 'size' => 48])
                        </div>
                    @endif
                    <div class="mc-template-overlay">
                        <a href="{{ route('manager.page_templates.preview', $item->uid) }}"
                           data-preview="{{ route('manager.page_templates.preview', $item->uid) }}"
                           data-preview-title="{{ trans('campaign::page-templates.action.preview') }}: {{ $item->name }}"
                           data-preview-subtitle="{{ $item->template && $item->template->categories()->count() > 0 ? $item->template->categories()->first()->name : trans('campaign::page-templates.action.preview') }}"
                           data-preview-edit-href="{{ $editRoute }}"
                           data-preview-edit-label="{{ $editLabel }}"
                           class="mc-btn mc-btn-white mc-btn-sm">
                            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'eye', 'size' => 16])
                            {{ trans('campaign::page-templates.action.preview') }}
                        </a>
                        <a href="{{ $editRoute }}" class="mc-btn mc-btn-primary mc-btn-sm">
                            @include('campaign::refactor.components.icons.mc-icon', ['icon' => $isUploaded ? 'code' : 'edit', 'size' => 16])
                            {{ $editLabel }}
                        </a>
                    </div>
                </div>
                <div class="mc-template-info">
                    <div class="mc-template-name">{{ $item->name }}</div>
                    <div class="mc-template-meta">
                        @if ($item->template && $item->template->categories()->count() > 0)
                        <span class="mc-badge mc-badge-default" style="font-size:var(--text-xs);">{{ $item->template->categories()->first()->name }}</span>
                        @endif
                        <span style="color:var(--color-text-muted);font-size:var(--text-xs)">{{ $item->created_at ? $item->created_at->diffForHumans() : '' }}</span>
                    </div>
                    <div class="mc-template-actions" data-dropdown>
                        <button class="mc-template-action-btn" data-dropdown-trigger>
                            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'more-v', 'size' => 18])
                        </button>
                        <div class="mc-dropdown-menu mc-dropdown-menu-end">
                            @unless ($isUploaded)
                            <a class="mc-dropdown-item" href="{{ route('manager.page_templates.custom_html', $item->uid) }}">
                                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'code', 'size' => 16])
                                {{ trans('campaign::page-templates.action.custom_html') }}
                            </a>
                            @endunless
                            <a class="mc-dropdown-item" href="{{ route('manager.page_templates.copy', $item->uid) }}"
                               data-popup data-popup-title="{{ trans('campaign::page-templates.copy.title') }}">
                                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'copy', 'size' => 16])
                                {{ trans('campaign::page-templates.action.copy') }}
                            </a>
                            <a class="mc-dropdown-item" href="{{ route('manager.page_templates.change_name', $item->uid) }}"
                               data-popup data-popup-title="{{ trans('campaign::page-templates.rename.title') }}">
                                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'type', 'size' => 16])
                                {{ trans('campaign::page-templates.action.rename') }}
                            </a>
                            <div class="mc-dropdown-divider"></div>
                            <a class="mc-dropdown-item mc-dropdown-item-danger" href="javascript:;"
                               data-action-delete="{{ route('manager.page_templates.delete') }}" data-uid="{{ $item->uid }}">
                                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'delete', 'size' => 16])
                                {{ trans('campaign::page-templates.action.delete') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Pagination (uses shared mc-pagination component) --}}
    @include('campaign::refactor.partials._pagination', ['items' => $items])
@else
    {{-- Empty state --}}
    <div class="mc-empty-state" style="padding:var(--space-12) var(--space-6);">
        <div class="mc-empty-illustration">
            <svg viewBox="0 0 160 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="35" y="20" width="90" height="80" rx="6" fill="var(--illust-bg)" stroke="var(--illust-stroke)" stroke-width="1.5"/>
                <rect x="50" y="35" width="60" height="8" rx="2" fill="var(--illust-fill)" stroke="var(--illust-stroke)" stroke-width="1"/>
                <rect x="50" y="50" width="60" height="8" rx="2" fill="var(--illust-fill)" stroke="var(--illust-stroke)" stroke-width="1"/>
                <rect x="50" y="65" width="40" height="8" rx="2" fill="var(--illust-teal)" stroke="var(--illust-teal-bold)" stroke-width="1"/>
                <circle cx="125" cy="30" r="3" fill="var(--illust-teal)" />
                <circle cx="35" cy="85" r="2.5" fill="var(--illust-teal)" />
            </svg>
        </div>
        <div class="mc-empty-state-title">{{ trans('campaign::page-templates.empty.title') }}</div>
        <div class="mc-empty-state-text">{{ trans('campaign::page-templates.empty.desc') }}</div>
        <a href="{{ route('manager.page_templates.add') }}"
           data-popup data-popup-title="{{ trans('campaign::page-templates.create.title') }}"
           class="mc-btn mc-btn-primary mc-btn-sm">
            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'add', 'size' => 16])
            {{ trans('campaign::page-templates.buttons.create') }}
        </a>
    </div>
@endif

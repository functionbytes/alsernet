@if ($items->count() > 0)
    <div>
        @foreach ($items as $item)
            @php
                $isUploaded = $item->template && $item->template->isUploaded();
                $editRoute  = $isUploaded
                    ? route('manager.email_templates.custom_html', $item->uid)
                    : route('manager.email_templates.builder', $item->uid);
                $editLabel  = $isUploaded
                    ? trans('campaign::email-templates.action.edit_html')
                    : trans('campaign::email-templates.action.edit_builder');
            @endphp
            <div class="mc-template-list-row">
                {{-- Thumbnail — taller, same ratio as grid cards --}}
                <div class="mc-template-list-thumb">
                    @if ($item->template && $item->template->getThumbnailUrl())
                        <img src="{{ $item->template->getThumbnailUrl() }}" alt="{{ $item->name }}" decoding="async">
                    @else
                        <div class="mc-template-list-thumb-placeholder">
                            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'file-text', 'size' => 28])
                        </div>
                    @endif
                </div>

                {{-- Info block — name + meta --}}
                <div class="mc-template-list-info">
                    <div class="mc-template-list-name">{{ $item->name }}</div>
                    <div class="mc-template-list-meta">
                        <span class="mc-template-list-meta-item">
                            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'calendar', 'size' => 14])
                            {{ $item->created_at ? $item->created_at->diffForHumans() : '--' }}
                        </span>
                        @if ($item->updated_at && $item->updated_at->ne($item->created_at))
                        <span class="mc-template-list-meta-item">
                            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'edit', 'size' => 14])
                            {{ trans('campaign::email-templates.meta.edited') }} {{ $item->updated_at->diffForHumans() }}
                        </span>
                        @endif
                        @if ($item->template && $item->template->categories()->count() > 0)
                        <span class="mc-badge mc-badge-default" style="font-size:var(--text-xs);">{{ $item->template->categories()->first()->name }}</span>
                        @endif
                    </div>
                </div>

                {{-- Actions — visible quick buttons + overflow dropdown --}}
                <div class="mc-template-list-actions">
                    {{-- Quick actions: Edit, Preview --}}
                    <a href="{{ $editRoute }}" class="mc-btn mc-btn-primary mc-btn-sm">
                        @include('campaign::refactor.components.icons.mc-icon', ['icon' => $isUploaded ? 'code' : 'edit', 'size' => 16, 'class' => 'mc-btn-icon-left'])
                        {{ $editLabel }}
                    </a>
                    <a href="{{ route('manager.email_templates.preview', $item->uid) }}"
                       data-preview="{{ route('manager.email_templates.preview', $item->uid) }}"
                       data-preview-title="{{ trans('campaign::email-templates.action.preview') }}: {{ $item->name }}"
                       data-preview-subtitle="{{ $item->template && $item->template->categories()->count() > 0 ? $item->template->categories()->first()->name : trans('campaign::email-templates.action.preview') }}"
                       data-preview-edit-href="{{ $editRoute }}"
                       data-preview-edit-label="{{ $editLabel }}"
                       class="mc-template-action-btn" title="{{ trans('campaign::email-templates.action.preview') }}">
                        @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'eye', 'size' => 18])
                    </a>

                    <div class="mc-template-action-divider"></div>

                    {{-- Overflow menu: Copy, Rename, Delete --}}
                    <div data-dropdown class="mc-dropdown">
                        <button class="mc-template-action-btn" data-dropdown-trigger>
                            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'more-v', 'size' => 18])
                        </button>
                        <div class="mc-dropdown-menu mc-dropdown-menu-end">
                            @unless ($isUploaded)
                            <a class="mc-dropdown-item" href="{{ route('manager.email_templates.custom_html', $item->uid) }}">
                                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'code', 'size' => 16])
                                {{ trans('campaign::email-templates.action.custom_html') }}
                            </a>
                            @endunless
                            <a class="mc-dropdown-item" href="{{ route('manager.email_templates.copy', $item->uid) }}"
                               data-popup data-popup-title="{{ trans('campaign::email-templates.copy.title') }}">
                                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'copy', 'size' => 16])
                                {{ trans('campaign::email-templates.action.copy') }}
                            </a>
                            <a class="mc-dropdown-item" href="{{ route('manager.email_templates.change_name', $item->uid) }}"
                               data-popup data-popup-title="{{ trans('campaign::email-templates.rename.title') }}">
                                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'type', 'size' => 16])
                                {{ trans('campaign::email-templates.action.rename') }}
                            </a>
                            <div class="mc-dropdown-divider"></div>
                            <a class="mc-dropdown-item mc-dropdown-item-danger" href="javascript:;"
                               data-action-delete="{{ route('manager.email_templates.delete') }}" data-uid="{{ $item->uid }}">
                                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'delete', 'size' => 16])
                                {{ trans('campaign::email-templates.action.delete') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Pagination --}}
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
        <div class="mc-empty-state-title">{{ trans('campaign::email-templates.empty.title') }}</div>
        <div class="mc-empty-state-text">{{ trans('campaign::email-templates.empty.desc') }}</div>
        <a href="{{ route('manager.email_templates.add') }}"
           data-popup data-popup-title="{{ trans('campaign::email-templates.create.title') }}"
           class="mc-btn mc-btn-primary mc-btn-sm">
            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'add', 'size' => 16])
            {{ trans('campaign::email-templates.buttons.create') }}
        </a>
    </div>
@endif

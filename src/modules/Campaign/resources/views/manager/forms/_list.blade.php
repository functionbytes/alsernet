@if ($forms->count() > 0)
<div class="mc-form-list">
    @foreach ($forms as $form)
    @php
        // Relaciones/métricas que pueden no existir → protegidas con fallback.
        $thumbUrl = null;
        try { $thumbUrl = $form->getThumbnailUrl(); } catch (\Throwable $e) { $thumbUrl = null; }

        $isPublished = false;
        try { $isPublished = $form->isPublished(); } catch (\Throwable $e) { $isPublished = ($form->status ?? null) === 'published'; }

        $listName = null;
        try { $listName = $form->mailList ? $form->mailList->name : null; } catch (\Throwable $e) { $listName = null; }

        $createdHuman = null;
        try { $createdHuman = $form->created_at ? $form->created_at->diffForHumans() : null; } catch (\Throwable $e) { $createdHuman = null; }
    @endphp
    <div class="mc-form-list-row">
        <a href="{{ route('manager.forms.builder', $form->uid) }}" class="mc-form-list-thumb">
            @if ($thumbUrl)
                <img src="{{ $thumbUrl }}" alt="{{ $form->name }}" decoding="async">
            @else
                <div class="mc-form-list-thumb-placeholder">
                    @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'web', 'size' => 22])
                </div>
            @endif
        </a>

        <div class="mc-form-list-body">
            <div class="mc-form-list-header">
                <a href="{{ route('manager.forms.builder', $form->uid) }}" class="mc-form-list-name">{{ $form->name ?: trans('campaign::forms.untitled') }}</a>
                @if ($isPublished)
                    <span class="mc-badge mc-badge-green">{{ trans('campaign::forms.status.published') }}</span>
                @else
                    <span class="mc-badge mc-badge-default">{{ trans('campaign::forms.status.draft') }}</span>
                @endif
            </div>
            <div class="mc-form-list-meta">
                @if ($listName)
                    <span class="mc-form-list-meta-item" title="{{ trans('campaign::forms.col.list') }}">
                        @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'contacts', 'size' => 14])
                        {{ $listName }}
                    </span>
                @endif
                @if ($createdHuman)
                    <span class="mc-form-list-meta-item">
                        @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'schedule', 'size' => 14])
                        {{ $createdHuman }}
                    </span>
                @endif
            </div>
        </div>

        <div class="mc-form-list-actions">
            <a href="{{ route('manager.forms.builder', $form->uid) }}" class="mc-btn mc-btn-secondary mc-btn-sm">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'edit', 'size' => 16])
                {{ trans('campaign::forms.action.edit') }}
            </a>
            <div class="mc-dropdown" data-dropdown>
                <button class="mc-btn mc-btn-ghost mc-btn-sm mc-btn-icon" data-dropdown-trigger>
                    @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'more-v', 'size' => 18])
                </button>
                <div class="mc-dropdown-menu mc-dropdown-menu-end">
                    <a class="mc-dropdown-item" href="{{ route('manager.forms.preview', $form->uid) }}" target="_blank">
                        @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'eye', 'size' => 16])
                        {{ trans('campaign::forms.action.preview') }}
                    </a>
                    @if ($isPublished)
                        <a class="mc-dropdown-item" href="#"
                           data-action-publish="{{ route('manager.forms.unpublish') }}"
                           data-uid="{{ $form->uid }}">
                            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'unpublished', 'size' => 16])
                            {{ trans('campaign::forms.action.unpublish') }}
                        </a>
                    @else
                        <a class="mc-dropdown-item" href="#"
                           data-action-publish="{{ route('manager.forms.publish') }}"
                           data-uid="{{ $form->uid }}">
                            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'publish', 'size' => 16])
                            {{ trans('campaign::forms.action.publish') }}
                        </a>
                    @endif
                    <div class="mc-dropdown-divider"></div>
                    <a class="mc-dropdown-item mc-dropdown-item-danger" href="#"
                       data-action-delete="{{ route('manager.forms.delete') }}"
                       data-uid="{{ $form->uid }}">
                        @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'delete', 'size' => 16])
                        {{ trans('campaign::forms.action.delete') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

@include('campaign::refactor.partials._pagination', ['items' => $forms])

@elseif (!empty(request()->keyword))
<div class="mc-empty-state">
    <div class="mc-empty-illustration">
        <svg viewBox="0 0 160 120" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="80" cy="56" r="44" fill="var(--color-hover-bg)"/>
            <circle cx="72" cy="50" r="24" stroke="var(--color-border-strong)" stroke-width="2.5"/>
            <path d="M89 67l14 14" stroke="var(--color-border-strong)" stroke-width="2.5" stroke-linecap="round"/>
            <text x="72" y="57" text-anchor="middle" font-family="Inter, sans-serif" font-size="18" font-weight="500" fill="var(--color-text-disabled)">?</text>
        </svg>
    </div>
    <div class="mc-empty-state-title">{{ trans('campaign::forms.empty.title') }}</div>
    <div class="mc-empty-state-text">{{ trans('campaign::forms.empty.desc') }}</div>
</div>

@else
<div class="mc-empty-state">
    <div class="mc-empty-illustration">
        <svg viewBox="0 0 160 120" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="50" y="24" width="60" height="72" rx="6" fill="var(--color-card-bg)" stroke="var(--color-border-strong)" stroke-width="1.5"/>
            <line x1="62" y1="40" x2="98" y2="40" stroke="var(--color-border)" stroke-width="2" stroke-linecap="round"/>
            <rect x="62" y="48" width="36" height="9" rx="2" fill="var(--color-hover-bg)" stroke="var(--color-border)" stroke-width="1"/>
            <rect x="62" y="62" width="36" height="9" rx="2" fill="var(--color-hover-bg)" stroke="var(--color-border)" stroke-width="1"/>
            <rect x="62" y="78" width="24" height="9" rx="4.5" fill="var(--color-teal)" opacity="0.2"/>
            <circle cx="118" cy="32" r="12" fill="var(--color-teal)" opacity="0.15"/>
            <path d="M118 26v12M112 32h12" stroke="var(--color-teal)" stroke-width="2" stroke-linecap="round"/>
        </svg>
    </div>
    <div class="mc-empty-state-title">{{ trans('campaign::forms.empty.title') }}</div>
    <div class="mc-empty-state-text">{{ trans('campaign::forms.empty.desc') }}</div>
    <a href="{{ route('manager.forms.create') }}" class="mc-btn mc-btn-primary">
        @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'add', 'size' => 16])
        {{ trans('campaign::forms.buttons.create') }}
    </a>
</div>
@endif

@if ($funnels->count() > 0)
<div class="mc-form-list">
    @foreach ($funnels as $f)
    @php
        // Métricas/relaciones que pueden no existir → protegidas con fallback.
        $isPublished = false;
        try { $isPublished = $f->isPublished(); } catch (\Throwable $e) { $isPublished = ($f->status ?? null) === 'published'; }

        $stepsCount = 0;
        try { $stepsCount = (int) ($f->steps_count ?? 0); } catch (\Throwable $e) { $stepsCount = 0; }

        $createdHuman = null;
        try { $createdHuman = $f->created_at ? $f->created_at->diffForHumans() : null; } catch (\Throwable $e) { $createdHuman = null; }
    @endphp
    <div class="mc-form-list-row">
        <a href="{{ route('manager.funnels.edit', $f->uid) }}" class="mc-form-list-thumb">
            <div class="mc-form-list-thumb-placeholder">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'layers', 'size' => 22])
            </div>
        </a>

        <div class="mc-form-list-body">
            <div class="mc-form-list-header">
                <a href="{{ route('manager.funnels.edit', $f->uid) }}" class="mc-form-list-name">{{ $f->name ?: trans('campaign::funnels.untitled') }}</a>
                @if ($isPublished)
                    <span class="mc-badge mc-badge-green">{{ trans('campaign::funnels.status.published') }}</span>
                @else
                    <span class="mc-badge mc-badge-default">{{ trans('campaign::funnels.status.draft') }}</span>
                @endif
            </div>
            <div class="mc-form-list-meta">
                <span class="mc-form-list-meta-item" title="{{ trans('campaign::funnels.col.steps') }}">
                    @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'layers', 'size' => 14])
                    {{ trans('campaign::funnels.col.steps') }}: {{ number_format($stepsCount) }}
                </span>
                @if ($createdHuman)
                    <span class="mc-form-list-meta-item">
                        @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'schedule', 'size' => 14])
                        {{ $createdHuman }}
                    </span>
                @endif
            </div>
        </div>

        <div class="mc-form-list-actions">
            <a href="{{ route('manager.funnels.edit', $f->uid) }}" class="mc-btn mc-btn-secondary mc-btn-sm">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'edit', 'size' => 16])
                {{ trans('campaign::funnels.action.edit') }}
            </a>
            <div class="mc-dropdown" data-dropdown>
                <button class="mc-btn mc-btn-ghost mc-btn-sm mc-btn-icon" data-dropdown-trigger>
                    @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'more-v', 'size' => 18])
                </button>
                <div class="mc-dropdown-menu mc-dropdown-menu-end">
                    @if ($isPublished)
                        <a class="mc-dropdown-item" href="#"
                           data-action-publish="{{ route('manager.funnels.unpublish', $f->uid) }}"
                           data-uid="{{ $f->uid }}">
                            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'unpublished', 'size' => 16])
                            {{ trans('campaign::funnels.action.unpublish') }}
                        </a>
                    @else
                        <a class="mc-dropdown-item" href="#"
                           data-action-publish="{{ route('manager.funnels.publish', $f->uid) }}"
                           data-uid="{{ $f->uid }}">
                            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'publish', 'size' => 16])
                            {{ trans('campaign::funnels.action.publish') }}
                        </a>
                    @endif
                    <a class="mc-dropdown-item" href="#"
                       data-action-duplicate="{{ route('manager.funnels.duplicate', $f->uid) }}"
                       data-uid="{{ $f->uid }}">
                        @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'copy', 'size' => 16])
                        {{ trans('campaign::funnels.action.duplicate') }}
                    </a>
                    <div class="mc-dropdown-divider"></div>
                    <a class="mc-dropdown-item mc-dropdown-item-danger" href="#"
                       data-action-delete="{{ route('manager.funnels.delete') }}"
                       data-uid="{{ $f->uid }}">
                        @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'delete', 'size' => 16])
                        {{ trans('campaign::funnels.action.delete') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

@include('campaign::refactor.partials._pagination', ['items' => $funnels])

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
    <div class="mc-empty-state-title">{{ trans('campaign::funnels.empty.title') }}</div>
    <div class="mc-empty-state-text">{{ trans('campaign::funnels.empty.desc') }}</div>
</div>

@else
<div class="mc-empty-state">
    <div class="mc-empty-illustration">
        <svg viewBox="0 0 160 120" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="48" y="20" width="64" height="20" rx="5" fill="var(--color-card-bg)" stroke="var(--color-border-strong)" stroke-width="1.5"/>
            <rect x="56" y="50" width="48" height="20" rx="5" fill="var(--color-card-bg)" stroke="var(--color-border-strong)" stroke-width="1.5"/>
            <rect x="64" y="80" width="32" height="20" rx="5" fill="var(--color-teal)" opacity="0.18" stroke="var(--color-teal)" stroke-width="1.5"/>
            <path d="M80 40v10M80 70v10" stroke="var(--color-border-strong)" stroke-width="2" stroke-linecap="round"/>
        </svg>
    </div>
    <div class="mc-empty-state-title">{{ trans('campaign::funnels.empty.title') }}</div>
    <div class="mc-empty-state-text">{{ trans('campaign::funnels.empty.desc') }}</div>
    <a href="{{ route('manager.funnels.create') }}" class="mc-btn mc-btn-primary">
        @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'add', 'size' => 16])
        {{ trans('campaign::funnels.buttons.create') }}
    </a>
</div>
@endif

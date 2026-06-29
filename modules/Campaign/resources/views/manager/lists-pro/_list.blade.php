@if ($lists->count() > 0)
    <table class="mc-table mc-table-mobile-cards">
        <thead>
            <tr>
                <th></th>
                <th>{{ trans('campaign::lists.col.name') }}</th>
                <th>{{ trans('campaign::lists.col.subscribers') }}</th>
                <th>{{ trans('campaign::lists.col.created') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lists as $list)
            @php
                $subscriberCount = 0;
                try { $subscriberCount = $list->subscribers()->count(); } catch (\Throwable) {}
            @endphp
            <tr>
                <td class="mc-mobile-check">
                    <span class="material-symbols-rounded mc-list-item-icon">list</span>
                </td>

                {{-- Name --}}
                <td class="mc-mobile-title">
                    <a href="{{ route('manager.lists_pro.overview', $list->uid) }}" class="mc-list-item-name">
                        {{ $list->name }}
                    </a>
                    @if ($list->from_email)
                        <div class="mc-table-meta">{{ $list->from_email }}</div>
                    @endif
                </td>

                {{-- Subscriber count --}}
                <td data-label="{{ trans('campaign::lists.col.subscribers') }}">
                    <span class="mc-badge mc-badge-default">{{ number_format($subscriberCount) }}</span>
                </td>

                {{-- Created at --}}
                <td data-label="{{ trans('campaign::lists.col.created') }}">
                    @if ($list->created_at)
                        <span>{{ $list->created_at->format('M j, Y') }}</span>
                        <div class="mc-table-meta">{{ $list->created_at->diffForHumans() }}</div>
                    @else
                        <span class="mc-rate-muted">—</span>
                    @endif
                </td>

                {{-- Actions --}}
                <td class="mc-mobile-actions" style="text-align:right">
                    <div class="mc-btn-group">
                        <a href="{{ route('manager.lists_pro.overview', $list->uid) }}"
                           class="mc-btn mc-btn-secondary mc-btn-sm"
                           title="{{ trans('campaign::lists.action.overview') }}">
                            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'eye', 'size' => 14])
                            <span class="mc-btn-label">{{ trans('campaign::lists.action.overview') }}</span>
                        </a>
                        <a href="{{ route('manager.maillists.edit', $list->uid) }}"
                           class="mc-btn mc-btn-ghost mc-btn-sm"
                           title="{{ trans('campaign::lists.action.edit') }}">
                            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'edit', 'size' => 14])
                        </a>
                        <a href="{{ route('manager.lists_pro.subscribers', $list->uid) }}"
                           class="mc-btn mc-btn-ghost mc-btn-sm"
                           title="{{ trans('campaign::lists.action.subscribers') }}">
                            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'layers', 'size' => 14])
                        </a>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @include('campaign::refactor.partials._pagination', ['items' => $lists])

@elseif (!empty(request()->keyword))
    <div class="mc-empty-state">
        <div class="mc-empty-illustration">
            <svg viewBox="0 0 160 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="80" cy="56" r="44" fill="var(--color-hover-bg)"/>
                <circle cx="72" cy="50" r="24" stroke="var(--color-border-strong)" stroke-width="2.5"/>
                <path d="M89 67l14 14" stroke="var(--color-border-strong)" stroke-width="2.5" stroke-linecap="round"/>
                <path d="M72 40c-5.5 0-10 4.5-10 10" stroke="var(--color-teal)" stroke-width="2" stroke-linecap="round" opacity="0.6"/>
                <text x="72" y="57" text-anchor="middle" font-family="Inter, sans-serif" font-size="18" font-weight="500" fill="var(--color-text-disabled)">?</text>
            </svg>
        </div>
        <div class="mc-empty-state-title">{{ trans('campaign::lists.empty.title') }}</div>
        <div class="mc-empty-state-text">{{ trans('campaign::lists.empty.desc') }}</div>
    </div>
@else
    <div class="mc-empty-state">
        <div class="mc-empty-illustration">
            <svg viewBox="0 0 160 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="20" y="20" width="120" height="80" rx="8" fill="var(--color-hover-bg)" stroke="var(--color-border-strong)" stroke-width="1.5"/>
                <line x1="36" y1="42" x2="124" y2="42" stroke="var(--color-border)" stroke-width="2" stroke-linecap="round"/>
                <circle cx="32" cy="58" r="4" fill="var(--color-teal)" opacity="0.4"/>
                <line x1="42" y1="58" x2="110" y2="58" stroke="var(--color-border)" stroke-width="2" stroke-linecap="round"/>
                <circle cx="32" cy="74" r="4" fill="var(--color-border)"/>
                <line x1="42" y1="74" x2="100" y2="74" stroke="var(--color-border)" stroke-width="2" stroke-linecap="round"/>
                <circle cx="140" cy="28" r="12" fill="var(--color-teal)" opacity="0.15"/>
                <path d="M140 22v12M134 28h12" stroke="var(--color-teal)" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </div>
        <div class="mc-empty-state-title">{{ trans('campaign::lists.empty.title') }}</div>
        <div class="mc-empty-state-text">{{ trans('campaign::lists.empty.desc') }}</div>
        <a href="{{ route('manager.maillists.create') }}" class="mc-btn mc-btn-primary">
            <span class="material-symbols-rounded">add</span>
            {{ trans('campaign::lists.action.edit') }}
        </a>
    </div>
@endif

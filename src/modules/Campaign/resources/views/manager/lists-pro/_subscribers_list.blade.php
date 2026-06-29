@if ($subscribers->count() > 0)
    <table class="mc-table mc-table-mobile-cards">
        <thead>
            <tr>
                <th></th>
                <th>{{ trans('campaign::lists.sub.email') }}</th>
                <th>{{ trans('campaign::lists.sub.first_name') }}</th>
                <th>{{ trans('campaign::lists.sub.last_name') }}</th>
                <th>{{ trans('campaign::lists.sub.status') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($subscribers as $subscriber)
            @php
                $pivotStatus = 'subscribed';
                try { $pivotStatus = $subscriber->pivot->status ?? 'subscribed'; } catch (\Throwable) {}
                $badgeClass = match($pivotStatus) {
                    'subscribed'   => 'mc-badge-green',
                    'unsubscribed' => 'mc-badge-red',
                    default        => 'mc-badge-default',
                };
            @endphp
            <tr>
                <td class="mc-mobile-check">
                    <span class="material-symbols-rounded mc-list-item-icon">person</span>
                </td>

                {{-- Email --}}
                <td class="mc-mobile-title">
                    <span class="mc-list-item-name">{{ $subscriber->email }}</span>
                </td>

                {{-- First name --}}
                <td data-label="{{ trans('campaign::lists.sub.first_name') }}">
                    {{ $subscriber->first_name ?: '—' }}
                </td>

                {{-- Last name --}}
                <td data-label="{{ trans('campaign::lists.sub.last_name') }}">
                    {{ $subscriber->last_name ?: '—' }}
                </td>

                {{-- Status --}}
                <td data-label="{{ trans('campaign::lists.sub.status') }}">
                    <span class="mc-badge {{ $badgeClass }}">{{ ucfirst($pivotStatus) }}</span>
                </td>

                {{-- Actions --}}
                <td class="mc-mobile-actions" style="text-align:right">
                    <div class="mc-btn-group">
                        <a href="{{ route('manager.lists_pro.subscriber_edit', [$list->uid, $subscriber->uid]) }}"
                           class="mc-btn mc-btn-secondary mc-btn-sm"
                           title="{{ trans('campaign::lists.action.edit') }}">
                            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'edit', 'size' => 14])
                        </a>
                        <button type="button"
                                class="mc-btn mc-btn-ghost mc-btn-sm"
                                data-delete-sub
                                data-delete-sub-uid="{{ $subscriber->uid }}"
                                data-delete-url="{{ route('manager.lists_pro.subscriber_delete', [$list->uid, $subscriber->uid]) }}"
                                title="{{ trans('campaign::lists.action.delete') }}">
                            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'delete', 'size' => 14])
                        </button>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @include('campaign::refactor.partials._pagination', ['items' => $subscribers])

@else
    <div class="mc-empty-state">
        <div class="mc-empty-illustration">
            <svg viewBox="0 0 160 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="80" cy="60" r="44" fill="var(--color-hover-bg)"/>
                <circle cx="80" cy="50" r="20" stroke="var(--color-border-strong)" stroke-width="2" fill="none"/>
                <path d="M48 95c0-17.7 14.3-32 32-32s32 14.3 32 32" stroke="var(--color-border-strong)" stroke-width="2" fill="none" stroke-linecap="round"/>
                <path d="M80 40c-5.5 0-10 4.5-10 10" stroke="var(--color-teal)" stroke-width="2" stroke-linecap="round" opacity="0.6"/>
            </svg>
        </div>
        <div class="mc-empty-state-title">{{ trans('campaign::lists.subscribers.empty') }}</div>
        <a href="{{ route('manager.lists_pro.subscriber_create', $list->uid) }}" class="mc-btn mc-btn-primary">
            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'add', 'size' => 16])
            {{ trans('campaign::lists.subscribers.add') }}
        </a>
    </div>
@endif

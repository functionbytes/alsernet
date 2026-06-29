@if ($automations->count() > 0)
<table class="mc-table">
    <thead>
        <tr>
            <th style="width:36px">
                <label class="mc-checkbox-label" style="padding:0">
                    <input type="checkbox" class="mc-checkbox" data-check-all>
                </label>
            </th>
            <th>{{ trans('campaign::automations.col.name') }}</th>
            <th style="width:100px;text-align:center">{{ trans('campaign::automations.col.status') }}</th>
            <th style="width:140px">{{ trans('campaign::automations.col.trigger') }}</th>
            <th style="width:80px;text-align:center">{{ trans('campaign::automations.col.emails') }}</th>
            <th style="width:120px">{{ trans('campaign::automations.col.created') }}</th>
            <th style="width:180px;text-align:right"></th>
        </tr>
    </thead>
    <tbody>
        @foreach ($automations as $automation)
        @php
            // Malformed automations (missing/invalid flow) throw — log and fall back so the list still renders.
            $triggerType = 'unknown';
            $emailCount = 0;
            try {
                $flow = $automation->getFlow();
                $triggerType = $flow->trigger()->data['key'] ?? 'unknown';
                foreach ($flow->nodes as $node) {
                    if ($node->type->value === 'send') {
                        $emailCount++;
                    }
                }
            } catch (\Throwable $e) {
                \Log::warning('Automation has malformed flow', [
                    'automation_id' => $automation->id,
                    'error' => $e->getMessage(),
                ]);
            }
        @endphp
        <tr>
            <td>
                <label class="mc-checkbox-label" style="padding:0">
                    <input type="checkbox" class="mc-checkbox" name="uids[]" value="{{ $automation->uid }}">
                </label>
            </td>
            <td>
                <a href="{{ route('manager.flow_automations.edit', $automation->uid) }}" class="mc-table-link">{{ $automation->name }}</a>
                <div style="font-size:var(--text-xs);color:var(--color-text-muted);margin-top:2px">
                    @if ($automation->mailList)
                    {{ $automation->mailList->name }}
                    @endif
                </div>
            </td>
            <td style="text-align:center">
                @if ($automation->status === 'active')
                <span class="mc-badge mc-badge-green">{{ trans('campaign::automations.status.active') }}</span>
                @else
                <span class="mc-badge mc-badge-default">{{ trans('campaign::automations.status.inactive') }}</span>
                @endif
            </td>
            <td>
                <span style="font-size:var(--text-sm);color:var(--color-text-secondary)">
                    {{ trans('campaign::automations.trigger.' . $triggerType, [], null) ?: trans('campaign::automations.trigger.unknown') }}
                </span>
            </td>
            <td style="text-align:center">
                <span style="font-size:var(--text-sm);font-weight:var(--weight-medium)">{{ $emailCount }}</span>
            </td>
            <td>
                <span style="font-size:var(--text-sm);color:var(--color-text-muted)">{{ $automation->created_at->format('M j, Y') }}</span>
            </td>
            <td style="text-align:right">
                <div style="display:inline-flex;gap:6px;align-items:center">
                    <a class="mc-btn mc-btn-secondary mc-btn-sm"
                       href="{{ route('manager.flow_automations.edit', $automation->uid) }}">
                        <span class="material-symbols-rounded">edit</span>
                        {{ trans('campaign::automations.action.edit') }}
                    </a>
                    <div class="mc-dropdown" data-dropdown>
                        <button class="mc-btn mc-btn-ghost mc-btn-sm mc-btn-icon" data-dropdown-trigger>
                            <span class="material-symbols-rounded">more_horiz</span>
                        </button>
                        <div class="mc-dropdown-menu mc-dropdown-menu-end">
                            <a class="mc-dropdown-item mc-dropdown-item-danger" href="#"
                               data-auto-action="delete" data-uid="{{ $automation->uid }}"
                               data-name="{{ $automation->name }}">
                                <span class="material-symbols-rounded">delete</span> {{ trans('campaign::automations.action.delete') }}
                            </a>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

@include('campaign::refactor.partials._pagination', ['items' => $automations])

@elseif (!empty(request()->keyword))
<div class="mc-empty-state">
    <svg viewBox="0 0 120 100" fill="none" xmlns="http://www.w3.org/2000/svg" class="mc-empty-state-icon">
        <circle cx="60" cy="50" r="40" fill="var(--chart-3-bg)"/>
        <circle cx="60" cy="45" r="18" stroke="var(--chart-3)" stroke-width="1.5" fill="none"/>
        <line x1="73" y1="58" x2="85" y2="70" stroke="var(--chart-3)" stroke-width="1.5" stroke-linecap="round"/>
    </svg>
    <h3 class="mc-empty-state-title">{{ trans('campaign::automations.empty.search_title') }}</h3>
    <p class="mc-empty-state-desc">{{ trans('campaign::automations.empty.search_desc') }}</p>
</div>
@else
<div class="mc-empty-state">
    <svg viewBox="0 0 120 100" fill="none" xmlns="http://www.w3.org/2000/svg" class="mc-empty-state-icon">
        <circle cx="60" cy="50" r="40" fill="var(--chart-3-bg)"/>
        <circle cx="40" cy="42" r="10" fill="var(--color-teal)" opacity="0.1" stroke="var(--color-teal)" stroke-width="1.2"/>
        <path d="M37 42l2 2 4-4" stroke="var(--color-teal)" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" opacity="0.6"/>
        <line x1="50" y1="42" x2="60" y2="42" stroke="var(--chart-3)" stroke-width="1.2" stroke-linecap="round" opacity="0.4"/>
        <path d="M58 39l4 3-4 3" stroke="var(--chart-3)" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" opacity="0.4"/>
        <rect x="62" y="34" width="20" height="16" rx="4" fill="var(--chart-1)" opacity="0.1" stroke="var(--chart-1)" stroke-width="1.2"/>
        <rect x="66" y="38" width="12" height="8" rx="1.5" fill="var(--chart-1)" opacity="0.15"/>
        <path d="M66 39l6 4 6-4" stroke="var(--chart-1)" stroke-width="0.8" stroke-linecap="round" opacity="0.4"/>
        <line x1="40" y1="52" x2="40" y2="62" stroke="var(--chart-3)" stroke-width="1.2" stroke-linecap="round" opacity="0.4"/>
        <path d="M37 60l3 4 3-4" stroke="var(--chart-3)" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" opacity="0.4"/>
        <rect x="30" y="64" width="20" height="14" rx="4" fill="var(--chart-2)" opacity="0.1" stroke="var(--chart-2)" stroke-width="1.2"/>
        <circle cx="40" cy="69" r="3" fill="var(--chart-2)" opacity="0.2"/>
    </svg>
    <h3 class="mc-empty-state-title">{{ trans('campaign::automations.empty.title') }}</h3>
    <p class="mc-empty-state-desc">{{ trans('campaign::automations.empty.desc') }}</p>
    <a href="{{ route('manager.flow_automations.create') }}" class="mc-btn mc-btn-primary" style="margin-top:var(--space-3)">
        <span class="material-symbols-rounded">add</span> {{ trans('campaign::automations.empty.create_button') }}
    </a>
</div>
@endif

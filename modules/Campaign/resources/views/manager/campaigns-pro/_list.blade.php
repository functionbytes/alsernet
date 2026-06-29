@if ($campaigns->count() > 0)
    <table class="mc-table mc-table-mobile-cards">
        <thead>
            <tr>
                <th>{{ trans('campaign::campaigns.overview.status') }}</th>
                <th>Name</th>
                <th>Status</th>
                <th>{{ trans('campaign::campaigns.overview.stat_delivered') }}</th>
                <th>{{ trans('campaign::campaigns.overview.open_rate') }}</th>
                <th>{{ trans('campaign::campaigns.overview.click_rate') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($campaigns as $campaign)
            @php
                $isDone = $campaign->status === 'done';
                $isSending = in_array($campaign->status, ['sending', 'queuing', 'queued']);

                $sc = ['new'=>'default','draft'=>'default','queuing'=>'blue','queued'=>'blue','sending'=>'teal','done'=>'green','scheduled'=>'blue','paused'=>'orange','error'=>'red'];
                $sl = [
                    'new' => trans('campaign::campaigns.status.new'),
                    'draft' => trans('campaign::campaigns.status.draft'),
                    'queuing' => trans('campaign::campaigns.status.queuing'),
                    'queued' => trans('campaign::campaigns.status.queued'),
                    'sending' => trans('campaign::campaigns.status.sending'),
                    'done' => trans('campaign::campaigns.status.done'),
                    'scheduled' => trans('campaign::campaigns.status.scheduled'),
                    'paused' => trans('campaign::campaigns.status.paused'),
                    'error' => trans('campaign::campaigns.status.error'),
                ];

                // Métricas protegidas: el módulo expone deliveredCount/openRate/clickRate
                // pero pueden requerir datos que aún no existen — fallback 0 ante cualquier fallo.
                $subscriberCount = 0;
                $deliveredCount = 0;
                $openPct = 0;
                $clickPct = 0;
                try { $subscriberCount = (int) $campaign->subscribersCount(); } catch (\Throwable $e) { $subscriberCount = 0; }
                try { $deliveredCount = (int) $campaign->deliveredCount(); } catch (\Throwable $e) { $deliveredCount = 0; }
                try { $openPct = round((float) $campaign->openRate(), 1); } catch (\Throwable $e) { $openPct = 0; }
                try { $clickPct = round((float) $campaign->clickRate(), 1); } catch (\Throwable $e) { $clickPct = 0; }

                $deliveredPct = $subscriberCount > 0 ? round($deliveredCount / $subscriberCount * 100) : 0;

                // Ring color by engagement level
                $openRingColor = $openPct >= 40 ? 'mc-ring-green' : ($openPct >= 20 ? '' : ($openPct >= 10 ? 'mc-ring-orange' : 'mc-ring-red'));
                $clickRingColor = $clickPct >= 20 ? 'mc-ring-green' : ($clickPct >= 8 ? '' : ($clickPct >= 3 ? 'mc-ring-orange' : 'mc-ring-red'));
            @endphp
            <tr>
                <td class="mc-mobile-check">
                    <span class="material-symbols-rounded mc-list-item-icon">mail</span>
                </td>

                {{-- Name --}}
                <td class="mc-mobile-title">
                    <a href="{{ route('manager.campaigns.show', $campaign->uid) }}" class="mc-list-item-name">
                        {{ $campaign->name ?: ($campaign->title ?: trans('campaign::campaigns.untitled')) }}
                    </a>
                </td>

                {{-- Status + date --}}
                <td data-label="Status">
                    <span class="mc-badge mc-badge-{{ $sc[$campaign->status] ?? 'default' }}">{{ $sl[$campaign->status] ?? ucfirst($campaign->status) }}</span>
                    @if ($campaign->run_at)
                        <div class="mc-table-meta">{{ $campaign->run_at->diffForHumans() }}</div>
                    @elseif ($campaign->created_at)
                        <div class="mc-table-meta">{{ $campaign->created_at->format('M j, Y') }}</div>
                    @endif
                </td>

                {{-- Delivered / Total with mini bar --}}
                <td data-label="Delivered">
                    @if ($isDone || $isSending)
                        <div class="mc-stat-cell">
                            <span class="mc-stat-cell-value">{{ number_format($deliveredCount) }}</span>
                            <span class="mc-stat-cell-sub">/ {{ number_format($subscriberCount) }}</span>
                        </div>
                        <div class="mc-delivery-bar">
                            <div class="mc-delivery-bar-fill" style="width:{{ $deliveredPct }}%"></div>
                        </div>
                    @else
                        <span class="mc-rate-muted">--</span>
                    @endif
                </td>

                {{-- Open rate with ring chart --}}
                <td data-label="Opens">
                    @if ($isDone && $openPct > 0)
                        <div class="mc-rate-cell">
                            <div class="mc-ring {{ $openRingColor }}" style="--ring-pct:{{ $openPct }}"></div>
                            <span class="mc-rate-value">{{ $openPct }}%</span>
                        </div>
                    @elseif ($isDone)
                        <span class="mc-rate-muted">0%</span>
                    @else
                        <span class="mc-rate-muted">--</span>
                    @endif
                </td>

                {{-- Click rate with ring chart --}}
                <td data-label="Clicks">
                    @if ($isDone && $clickPct > 0)
                        <div class="mc-rate-cell">
                            <div class="mc-ring {{ $clickRingColor }}" style="--ring-pct:{{ $clickPct }}"></div>
                            <span class="mc-rate-value">{{ $clickPct }}%</span>
                        </div>
                    @elseif ($isDone)
                        <span class="mc-rate-muted">0%</span>
                    @else
                        <span class="mc-rate-muted">--</span>
                    @endif
                </td>

                {{-- Actions --}}
                <td class="mc-mobile-actions" style="text-align:right">
                    {{-- Overview (campaigns that have been sent or are sending) --}}
                    @if (in_array($campaign->status, ['done', 'sending', 'queuing', 'queued', 'paused', 'scheduled']))
                        <a href="{{ route('manager.campaigns_pro.overview', $campaign->uid) }}" class="mc-btn mc-btn-secondary mc-btn-sm" style="margin-right:4px">
                            <span class="material-symbols-rounded">bar_chart</span>
                        </a>
                    @endif

                    {{-- Edit via wizard (only drafts / paused) --}}
                    @if (in_array($campaign->status, ['new', 'paused']))
                        <a href="{{ route('manager.campaigns_pro.recipients', $campaign->uid) }}" class="mc-btn mc-btn-secondary mc-btn-sm" style="margin-right:4px">
                            <span class="material-symbols-rounded">edit</span>
                        </a>
                    @endif

                    <a href="{{ route('manager.campaigns.show', $campaign->uid) }}" class="mc-btn mc-btn-secondary mc-btn-sm">
                        <span class="material-symbols-rounded">visibility</span> {{ trans('campaign::campaigns.wizard.open_builder') }}
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Pagination (uses shared mc-pagination component) --}}
    @include('campaign::refactor.partials._pagination', ['items' => $campaigns])
@elseif (!empty(request()->keyword))
    <div class="mc-empty-state">
        {{-- Search not found illustration --}}
        <div class="mc-empty-illustration">
            <svg viewBox="0 0 160 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="80" cy="56" r="44" fill="var(--color-hover-bg)"/>
                <circle cx="72" cy="50" r="24" stroke="var(--color-border-strong)" stroke-width="2.5"/>
                <path d="M89 67l14 14" stroke="var(--color-border-strong)" stroke-width="2.5" stroke-linecap="round"/>
                <path d="M72 40c-5.5 0-10 4.5-10 10" stroke="var(--color-teal)" stroke-width="2" stroke-linecap="round" opacity="0.6"/>
                <text x="72" y="57" text-anchor="middle" font-family="Inter, sans-serif" font-size="18" font-weight="500" fill="var(--color-text-disabled)">?</text>
            </svg>
        </div>
        <div class="mc-empty-state-title">No campaigns found</div>
        <div class="mc-empty-state-text">We couldn't find any campaigns matching your search. Try a different keyword or clear your filters.</div>
    </div>
@else
    <div class="mc-empty-state">
        {{-- First campaign illustration --}}
        <div class="mc-empty-illustration">
            <svg viewBox="0 0 160 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="20" y="56" width="120" height="50" rx="6" fill="var(--color-hover-bg)" stroke="var(--color-border-strong)" stroke-width="1.5"/>
                <path d="M20 62l60 30 60-30" stroke="var(--color-border-strong)" stroke-width="1.5"/>
                <rect x="40" y="24" width="80" height="48" rx="4" fill="var(--color-card-bg)" stroke="var(--color-border-strong)" stroke-width="1.5"/>
                <line x1="54" y1="38" x2="106" y2="38" stroke="var(--color-border)" stroke-width="2" stroke-linecap="round"/>
                <line x1="54" y1="48" x2="96" y2="48" stroke="var(--color-border)" stroke-width="2" stroke-linecap="round"/>
                <line x1="54" y1="58" x2="82" y2="58" stroke="var(--color-border)" stroke-width="2" stroke-linecap="round"/>
                <circle cx="128" cy="28" r="12" fill="var(--color-teal)" opacity="0.15"/>
                <path d="M128 22v12M122 28h12" stroke="var(--color-teal)" stroke-width="2" stroke-linecap="round"/>
                <circle cx="32" cy="32" r="3" fill="var(--color-teal)" opacity="0.3"/>
                <circle cx="24" cy="44" r="2" fill="var(--color-teal)" opacity="0.2"/>
            </svg>
        </div>
        <div class="mc-empty-state-title">No campaigns yet</div>
        <div class="mc-empty-state-text">Create your first email campaign to start reaching your audience. Choose from a template or build one from scratch.</div>
        <a href="{{ route('manager.campaigns_pro.select_type') }}" class="mc-btn mc-btn-primary">
            <span class="material-symbols-rounded">add</span>
            {{ trans('campaign::campaigns.banner.create') }}
        </a>
    </div>
@endif

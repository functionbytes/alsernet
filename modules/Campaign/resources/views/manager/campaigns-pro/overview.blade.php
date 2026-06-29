@extends('campaign::refactor.layout')

@section('title', trans('campaign::campaigns.overview.title'))

@section('page-header')
    <div class="mc-page-header">
        <div>
            <h1 class="mc-page-title">{{ $campaign->title ?: $campaign->name ?: trans('campaign::campaigns.untitled') }}</h1>
            <p class="mc-page-subtitle">{{ trans('campaign::campaigns.overview.title') }}</p>
        </div>
        <div class="mc-page-actions">
            <a href="{{ route('manager.campaigns_pro.index') }}" class="mc-btn mc-btn-secondary mc-btn-sm">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'arrow-left', 'size' => 16])
                {{ trans('campaign::campaigns.wizard.all_campaigns') }}
            </a>

            @if (in_array($campaign->status, ['sending', 'queuing', 'queued']))
                <button type="button"
                        class="mc-btn mc-btn-warning mc-btn-sm"
                        id="pause-btn"
                        data-url="{{ route('manager.campaigns_pro.pause', $campaign->uid) }}">
                    <span class="material-symbols-rounded" style="font-size:16px;vertical-align:-2px">pause</span>
                    {{ trans('campaign::campaigns.bulk.pause') }}
                </button>
            @elseif ($campaign->status === 'paused')
                <button type="button"
                        class="mc-btn mc-btn-primary mc-btn-sm"
                        id="resume-btn"
                        data-url="{{ route('manager.campaigns_pro.resume', $campaign->uid) }}">
                    <span class="material-symbols-rounded" style="font-size:16px;vertical-align:-2px">play_arrow</span>
                    {{ trans('campaign::campaigns.bulk.resume') }}
                </button>
            @endif
        </div>
    </div>
@endsection

@section('content')
<div style="max-width:900px;margin:0 auto">

    {{-- Status badge --}}
    @php
        $sc = ['new'=>'default','draft'=>'default','queuing'=>'blue','queued'=>'blue','sending'=>'teal','done'=>'green','scheduled'=>'blue','paused'=>'orange','error'=>'red'];
        $sl = [
            'new'       => trans('campaign::campaigns.status.new'),
            'draft'     => trans('campaign::campaigns.status.draft'),
            'queuing'   => trans('campaign::campaigns.status.queuing'),
            'queued'    => trans('campaign::campaigns.status.queued'),
            'sending'   => trans('campaign::campaigns.status.sending'),
            'done'      => trans('campaign::campaigns.status.done'),
            'scheduled' => trans('campaign::campaigns.status.scheduled'),
            'paused'    => trans('campaign::campaigns.status.paused'),
            'error'     => trans('campaign::campaigns.status.error'),
        ];
    @endphp

    <div style="margin-bottom:var(--space-5);display:flex;align-items:center;gap:var(--space-3)">
        <span class="mc-badge mc-badge-{{ $sc[$campaign->status] ?? 'default' }}" style="font-size:var(--text-sm)">
            {{ $sl[$campaign->status] ?? ucfirst($campaign->status) }}
        </span>
        @if ($campaign->run_at)
            <span style="font-size:var(--text-sm);color:var(--color-text-secondary)">
                <span class="material-symbols-rounded" style="font-size:14px;vertical-align:-2px">schedule</span>
                {{ $campaign->run_at->format('M j, Y H:i') }}
            </span>
        @endif
    </div>

    {{-- Stats grid --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:var(--space-4);margin-bottom:var(--space-6)">
        <div class="mc-card mc-stat-card" style="padding:var(--space-5)">
            <div style="font-size:var(--text-xs);color:var(--color-text-secondary);text-transform:uppercase;letter-spacing:.05em;margin-bottom:var(--space-2)">
                {{ trans('campaign::campaigns.overview.delivered') }}
            </div>
            <div style="font-size:var(--text-2xl);font-weight:var(--font-bold)">{{ number_format($deliveredCount) }}</div>
            <div style="font-size:var(--text-xs);color:var(--color-text-disabled);margin-top:2px">
                {{ trans('campaign::campaigns.overview.recipients') }}: {{ number_format($subscribersCount) }}
            </div>
        </div>

        <div class="mc-card mc-stat-card" style="padding:var(--space-5)">
            <div style="font-size:var(--text-xs);color:var(--color-text-secondary);text-transform:uppercase;letter-spacing:.05em;margin-bottom:var(--space-2)">
                {{ trans('campaign::campaigns.overview.open_rate') }}
            </div>
            <div style="font-size:var(--text-2xl);font-weight:var(--font-bold)">{{ round($openRate, 1) }}%</div>
            <div style="font-size:var(--text-xs);color:var(--color-text-disabled);margin-top:2px">
                {{ trans('campaign::campaigns.overview.unique_opens') }}
            </div>
        </div>

        <div class="mc-card mc-stat-card" style="padding:var(--space-5)">
            <div style="font-size:var(--text-xs);color:var(--color-text-secondary);text-transform:uppercase;letter-spacing:.05em;margin-bottom:var(--space-2)">
                {{ trans('campaign::campaigns.overview.click_rate') }}
            </div>
            <div style="font-size:var(--text-2xl);font-weight:var(--font-bold)">{{ round($clickRate, 1) }}%</div>
            <div style="font-size:var(--text-xs);color:var(--color-text-disabled);margin-top:2px">
                {{ trans('campaign::campaigns.overview.unique_clicks') }}
            </div>
        </div>

        <div class="mc-card mc-stat-card" style="padding:var(--space-5)">
            <div style="font-size:var(--text-xs);color:var(--color-text-secondary);text-transform:uppercase;letter-spacing:.05em;margin-bottom:var(--space-2)">
                {{ trans('campaign::campaigns.overview.bounce_rate') }}
            </div>
            <div style="font-size:var(--text-2xl);font-weight:var(--font-bold)">{{ number_format($bounceCount) }}</div>
            <div style="font-size:var(--text-xs);color:var(--color-text-disabled);margin-top:2px">
                {{ trans('campaign::campaigns.overview.bounced_count') }}
            </div>
        </div>
    </div>

    {{-- Campaign details --}}
    <div class="mc-card" style="margin-bottom:var(--space-4)">
        <div style="padding:var(--space-5) var(--space-6);border-bottom:1px solid var(--color-border)">
            <h2 style="font-size:var(--text-base);font-weight:var(--font-semibold);margin:0">
                {{ trans('campaign::campaigns.confirm.summary') }}
            </h2>
        </div>

        <table style="width:100%;border-collapse:collapse">
            <tr style="border-bottom:1px solid var(--color-border)">
                <td style="padding:var(--space-4) var(--space-6);font-size:var(--text-sm);color:var(--color-text-secondary);white-space:nowrap;width:160px">
                    {{ trans('campaign::campaigns.confirm.field_subject') }}
                </td>
                <td style="padding:var(--space-4) var(--space-6);font-size:var(--text-sm)">
                    {{ $campaign->subject ?: '—' }}
                </td>
            </tr>
            <tr style="border-bottom:1px solid var(--color-border)">
                <td style="padding:var(--space-4) var(--space-6);font-size:var(--text-sm);color:var(--color-text-secondary)">
                    {{ trans('campaign::campaigns.confirm.field_from') }}
                </td>
                <td style="padding:var(--space-4) var(--space-6);font-size:var(--text-sm)">
                    {{ $campaign->from_name }} &lt;{{ $campaign->from_email }}&gt;
                </td>
            </tr>
            <tr>
                <td style="padding:var(--space-4) var(--space-6);font-size:var(--text-sm);color:var(--color-text-secondary)">
                    {{ trans('campaign::campaigns.overview.subscribers') }}
                </td>
                <td style="padding:var(--space-4) var(--space-6);font-size:var(--text-sm)">
                    @if (empty($lists) || (is_object($lists) && $lists->isEmpty()) || (is_array($lists) && count($lists) === 0))
                        <span style="color:var(--color-text-disabled)">—</span>
                    @else
                        <div style="display:flex;flex-wrap:wrap;gap:var(--space-1-5)">
                            @foreach ($lists as $list)
                                <span class="mc-badge mc-badge-default">{{ $list->name }}</span>
                            @endforeach
                        </div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- Quick actions --}}
    <div style="display:flex;gap:var(--space-3);flex-wrap:wrap">
        @if (in_array($campaign->status, ['new', 'paused']))
            <a href="{{ route('manager.campaigns_pro.recipients', $campaign->uid) }}" class="mc-btn mc-btn-secondary mc-btn-sm">
                <span class="material-symbols-rounded" style="font-size:16px;vertical-align:-2px">edit</span>
                {{ trans('campaign::campaigns.wizard.recipients_title') }}
            </a>
        @endif
        <a href="{{ route('manager.campaigns.show', $campaign->uid) }}" class="mc-btn mc-btn-ghost mc-btn-sm">
            <span class="material-symbols-rounded" style="font-size:16px;vertical-align:-2px">open_in_new</span>
            {{ trans('campaign::campaigns.wizard.open_builder') }}
        </a>
    </div>

</div>
@endsection

@section('scripts')
<script>
(function () {
    function actionBtn(id) {
        var btn = document.getElementById(id);
        if (!btn) return;
        btn.addEventListener('click', function () {
            btn.disabled = true;
            fetch(btn.dataset.url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    window.location.reload();
                } else {
                    btn.disabled = false;
                    alert('Action failed.');
                }
            })
            .catch(function () {
                btn.disabled = false;
            });
        });
    }
    actionBtn('pause-btn');
    actionBtn('resume-btn');
})();
</script>
@endsection

@extends('campaign::refactor.layout')

@section('title', trans('campaign::campaigns.wizard.confirm_title'))

@section('page-header')
    <div class="mc-page-header">
        <div>
            <h1 class="mc-page-title">{{ trans('campaign::campaigns.wizard.confirm_title') }}</h1>
            <p class="mc-page-subtitle">{{ trans('campaign::campaigns.confirm.desc') }}</p>
        </div>
        <div class="mc-page-actions">
            <a href="{{ route('manager.campaigns_pro.index') }}" class="mc-btn mc-btn-secondary mc-btn-sm">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'arrow-left', 'size' => 16])
                {{ trans('campaign::campaigns.wizard.all_campaigns') }}
            </a>
        </div>
    </div>
@endsection

@section('content')
<div style="max-width:720px;margin:0 auto">

    {{-- Breadcrumb / steps --}}
    <div class="mc-wizard-steps" style="display:flex;gap:var(--space-2);margin-bottom:var(--space-6);align-items:center;flex-wrap:wrap">
        <a href="{{ route('manager.campaigns_pro.recipients', $campaign->uid) }}" class="mc-badge">{{ trans('campaign::campaigns.wizard.step_1') }}</a>
        <span class="mc-wizard-arrow" style="color:var(--color-text-disabled)">›</span>
        <a href="{{ route('manager.campaigns_pro.setup', $campaign->uid) }}" class="mc-badge">{{ trans('campaign::campaigns.wizard.step_2') }}</a>
        <span class="mc-wizard-arrow" style="color:var(--color-text-disabled)">›</span>
        <a href="{{ route('manager.campaigns_pro.schedule', $campaign->uid) }}" class="mc-badge">{{ trans('campaign::campaigns.wizard.step_4') }}</a>
        <span class="mc-wizard-arrow" style="color:var(--color-text-disabled)">›</span>
        <span class="mc-badge mc-badge-teal">{{ trans('campaign::campaigns.wizard.step_5') }}</span>
    </div>

    @if (session('error'))
        <div class="mc-alert mc-alert-danger" style="margin-bottom:var(--space-4)">{{ session('error') }}</div>
    @endif

    {{-- Summary card --}}
    <div class="mc-card" style="margin-bottom:var(--space-4)">
        <div style="padding:var(--space-5) var(--space-6);border-bottom:1px solid var(--color-border)">
            <h2 style="font-size:var(--text-base);font-weight:var(--font-semibold);margin:0">{{ trans('campaign::campaigns.confirm.summary') }}</h2>
        </div>

        <table style="width:100%;border-collapse:collapse">
            {{-- Campaign name --}}
            <tr style="border-bottom:1px solid var(--color-border)">
                <td style="padding:var(--space-4) var(--space-6);font-size:var(--text-sm);color:var(--color-text-secondary);white-space:nowrap;width:180px">
                    {{ trans('campaign::campaigns.confirm.campaign_label') }}
                </td>
                <td style="padding:var(--space-4) var(--space-6);font-size:var(--text-sm);font-weight:var(--font-medium)">
                    {{ $campaign->title ?: $campaign->name ?: trans('campaign::campaigns.untitled') }}
                </td>
            </tr>

            {{-- Subject --}}
            <tr style="border-bottom:1px solid var(--color-border)">
                <td style="padding:var(--space-4) var(--space-6);font-size:var(--text-sm);color:var(--color-text-secondary)">
                    {{ trans('campaign::campaigns.confirm.field_subject') }}
                </td>
                <td style="padding:var(--space-4) var(--space-6);font-size:var(--text-sm)">
                    @if ($campaign->subject)
                        {{ $campaign->subject }}
                    @else
                        <span style="color:var(--color-text-disabled)">{{ trans('campaign::campaigns.confirm.not_set') }}</span>
                    @endif
                </td>
            </tr>

            {{-- Sender --}}
            <tr style="border-bottom:1px solid var(--color-border)">
                <td style="padding:var(--space-4) var(--space-6);font-size:var(--text-sm);color:var(--color-text-secondary)">
                    {{ trans('campaign::campaigns.confirm.field_from') }}
                </td>
                <td style="padding:var(--space-4) var(--space-6);font-size:var(--text-sm)">
                    @if ($campaign->from_name || $campaign->from_email)
                        {{ $campaign->from_name }} &lt;{{ $campaign->from_email }}&gt;
                    @else
                        <span style="color:var(--color-text-disabled)">{{ trans('campaign::campaigns.confirm.not_configured') }}</span>
                    @endif
                </td>
            </tr>

            {{-- Recipients --}}
            <tr style="border-bottom:1px solid var(--color-border)">
                <td style="padding:var(--space-4) var(--space-6);font-size:var(--text-sm);color:var(--color-text-secondary)">
                    {{ trans('campaign::campaigns.confirm.field_recipients') }}
                </td>
                <td style="padding:var(--space-4) var(--space-6);font-size:var(--text-sm)">
                    @php $lists = []; try { $lists = $campaign->mailLists()->get(); } catch(\Throwable $e){} @endphp
                    @if (count($lists ?? []) === 0)
                        <span style="color:var(--color-danger)">{{ trans('campaign::campaigns.confirm.no_recipients') }}</span>
                    @else
                        <div style="display:flex;flex-wrap:wrap;gap:var(--space-1-5)">
                            @foreach ($lists as $list)
                                <span class="mc-badge mc-badge-default">{{ $list->name }}</span>
                            @endforeach
                        </div>
                        <div style="font-size:var(--text-xs);color:var(--color-text-secondary);margin-top:4px">
                            {{ number_format($subscribersCount) }} {{ trans('campaign::campaigns.confirm.subscribers') }}
                        </div>
                    @endif
                </td>
            </tr>

            {{-- Tracking --}}
            <tr style="border-bottom:1px solid var(--color-border)">
                <td style="padding:var(--space-4) var(--space-6);font-size:var(--text-sm);color:var(--color-text-secondary)">
                    {{ trans('campaign::campaigns.confirm.field_tracking') }}
                </td>
                <td style="padding:var(--space-4) var(--space-6);font-size:var(--text-sm)">
                    @if ($campaign->track_open && $campaign->track_click)
                        <span class="mc-badge mc-badge-teal">{{ trans('campaign::campaigns.confirm.tracking_both') }}</span>
                    @elseif ($campaign->track_open)
                        <span class="mc-badge">{{ trans('campaign::campaigns.confirm.tracking_opens_only') }}</span>
                    @elseif ($campaign->track_click)
                        <span class="mc-badge">{{ trans('campaign::campaigns.confirm.tracking_clicks_only') }}</span>
                    @else
                        <span class="mc-badge" style="color:var(--color-text-disabled)">{{ trans('campaign::campaigns.confirm.tracking_none') }}</span>
                    @endif
                </td>
            </tr>

            {{-- Schedule --}}
            <tr>
                <td style="padding:var(--space-4) var(--space-6);font-size:var(--text-sm);color:var(--color-text-secondary)">
                    {{ trans('campaign::campaigns.confirm.field_schedule') }}
                </td>
                <td style="padding:var(--space-4) var(--space-6);font-size:var(--text-sm)">
                    @if ($campaign->run_at)
                        <span class="mc-badge mc-badge-blue">
                            <span class="material-symbols-rounded" style="font-size:13px;vertical-align:-2px">schedule</span>
                            {{ $campaign->run_at->format('M j, Y H:i') }}
                        </span>
                    @else
                        <span class="mc-badge mc-badge-teal">
                            <span class="material-symbols-rounded" style="font-size:13px;vertical-align:-2px">send</span>
                            {{ trans('campaign::campaigns.confirm.send_immediately') }}
                        </span>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- Launch form --}}
    <form method="POST" action="{{ route('manager.campaigns_pro.confirm', $campaign->uid) }}" id="launch-form">
        @csrf
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:var(--space-6)">
            <a href="{{ route('manager.campaigns_pro.schedule', $campaign->uid) }}" class="mc-btn mc-btn-ghost mc-btn-sm">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'arrow-left', 'size' => 14])
                {{ trans('campaign::campaigns.wizard.back') }}
            </a>
            <button type="submit" class="mc-btn mc-btn-primary" id="launch-btn">
                <span class="material-symbols-rounded" style="font-size:16px;vertical-align:-2px;margin-right:4px">rocket_launch</span>
                {{ trans('campaign::campaigns.wizard.send') }}
            </button>
        </div>
    </form>

</div>
@endsection

@section('scripts')
<script>
document.getElementById('launch-form').addEventListener('submit', function () {
    var btn = document.getElementById('launch-btn');
    if (btn) {
        btn.disabled = true;
        btn.textContent = '...';
    }
});
</script>
@endsection

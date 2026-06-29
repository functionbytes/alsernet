@extends('campaign::refactor.layout')

@section('title', trans('campaign::dashboard.title'))

@section('content')
<div class="mc-page-header">
    <div>
        <h1 class="mc-page-title">{{ trans('campaign::dashboard.title') }}</h1>
        <p class="mc-page-subtitle">{{ trans('campaign::dashboard.subtitle') }}</p>
    </div>
</div>

{{-- Tarjetas de totales --}}
<div class="mc-stats-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:var(--space-3,16px);margin-bottom:var(--space-4,24px)">
    @php
        $cards = [
            ['k' => 'campaigns', 'icon' => 'megaphone', 'route' => 'manager.campaigns_pro.index'],
            ['k' => 'lists', 'icon' => 'list', 'route' => null],
            ['k' => 'subscribers', 'icon' => 'group', 'route' => null],
            ['k' => 'forms', 'icon' => 'description', 'route' => 'manager.forms.index'],
            ['k' => 'funnels', 'icon' => 'filter_alt', 'route' => 'manager.funnels.index'],
            ['k' => 'automations', 'icon' => 'account_tree', 'route' => 'manager.flow_automations.index'],
        ];
    @endphp
    @foreach ($cards as $c)
        @php $href = $c['route'] && \Route::has($c['route']) ? route($c['route']) : null; @endphp
        <{{ $href ? 'a' : 'div' }} @if($href) href="{{ $href }}" @endif class="mc-card" style="padding:var(--space-3,16px);text-decoration:none;color:inherit;display:block">
            <div style="display:flex;align-items:center;gap:8px;color:var(--mc-text-muted,#64748b)">
                <span class="material-symbols-rounded">{{ $c['icon'] }}</span>
                <span style="font-size:var(--text-sm,13px)">{{ trans('campaign::dashboard.count.'.$c['k']) }}</span>
            </div>
            <div style="font-size:28px;font-weight:700;margin-top:6px">{{ number_format($counts[$c['k']] ?? 0) }}</div>
        </{{ $href ? 'a' : 'div' }}>
    @endforeach
</div>

{{-- Métricas de email --}}
<div class="mc-card" style="padding:var(--space-4,24px);margin-bottom:var(--space-4,24px)">
    <h3 style="margin:0 0 16px">{{ trans('campaign::dashboard.email.title') }}</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:16px">
        @foreach (['sent','opens','clicks','bounces'] as $m)
            <div>
                <div style="color:var(--mc-text-muted,#64748b);font-size:13px">{{ trans('campaign::dashboard.email.'.$m) }}</div>
                <div style="font-size:22px;font-weight:600">{{ number_format($email[$m] ?? 0) }}</div>
            </div>
        @endforeach
        @foreach (['open_rate' => 'open_rate','click_rate' => 'click_rate','bounce_rate' => 'bounce_rate'] as $rk => $rl)
            <div>
                <div style="color:var(--mc-text-muted,#64748b);font-size:13px">{{ trans('campaign::dashboard.email.'.$rl) }}</div>
                <div style="font-size:22px;font-weight:600">{{ $email[$rk] ?? 0 }}%</div>
            </div>
        @endforeach
    </div>
</div>

{{-- Tendencia de envíos (7 días) — barras CSS --}}
<div class="mc-card" style="padding:var(--space-4,24px);margin-bottom:var(--space-4,24px)">
    <h3 style="margin:0 0 16px">{{ trans('campaign::dashboard.trend.title') }}</h3>
    @php $max = max(1, collect($sendingTrend)->max('count')); @endphp
    <div style="display:flex;align-items:flex-end;gap:10px;height:140px">
        @foreach ($sendingTrend as $d)
            <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:6px;height:100%">
                <div style="flex:1;display:flex;align-items:flex-end;width:100%">
                    <div title="{{ $d['count'] }}" style="width:100%;background:var(--mc-primary,#6366f1);border-radius:4px 4px 0 0;height:{{ max(2, (int) round($d['count'] * 100 / $max)) }}%"></div>
                </div>
                <span style="font-size:11px;color:var(--mc-text-muted,#64748b)">{{ \Illuminate\Support\Carbon::parse($d['date'])->format('d/m') }}</span>
            </div>
        @endforeach
    </div>
</div>

{{-- Campañas recientes --}}
<div class="mc-card" style="padding:var(--space-4,24px)">
    <h3 style="margin:0 0 12px">{{ trans('campaign::dashboard.recent.title') }}</h3>
    @if ($recentCampaigns->isEmpty())
        <p style="color:var(--mc-text-muted,#64748b)">{{ trans('campaign::dashboard.recent.empty') }}</p>
    @else
        <table class="mc-table" style="width:100%">
            <thead><tr>
                <th style="text-align:left">{{ trans('campaign::dashboard.recent.name') }}</th>
                <th style="text-align:left">{{ trans('campaign::dashboard.recent.status') }}</th>
                <th style="text-align:left">{{ trans('campaign::dashboard.recent.created') }}</th>
            </tr></thead>
            <tbody>
                @foreach ($recentCampaigns as $c)
                    <tr>
                        <td>{{ $c->title ?: $c->name }}</td>
                        <td><span class="mc-badge mc-badge-default">{{ $c->status }}</span></td>
                        <td>{{ optional($c->created_at)->diffForHumans() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection

@extends('layouts.theme')
@section('title', __('helpdeskanalytics::messages.chat_sales_title'))
@section('page_header')
    @include('core::components.card', ['title' => __('helpdeskanalytics::messages.chat_sales_title')])
@endsection

@php
    $money = fn ($v) => number_format((float) $v, 2, ',', '.').' €';
@endphp

@section('content')

<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <h1 class="h4 mb-0 fw-bold">
        <i class="fas fa-bag-shopping text-primary me-2"></i>{{ __('helpdeskanalytics::messages.chat_sales_title') }}
    </h1>
    <p class="text-muted small mb-0 w-100 order-3 mt-1">{{ __('helpdeskanalytics::messages.chat_sales_subtitle') }}</p>
    <form method="GET" action="{{ route('helpdeskanalytics.chat-sales') }}" class="ms-auto order-2 d-flex gap-2 align-items-end">
        <div>
            <label class="form-label small mb-1" for="cs-from">{{ __('helpdeskanalytics::messages.from') }}</label>
            <input type="date" id="cs-from" name="from" value="{{ $from->format('Y-m-d') }}" class="form-control form-control-sm">
        </div>
        <div>
            <label class="form-label small mb-1" for="cs-to">{{ __('helpdeskanalytics::messages.to') }}</label>
            <input type="date" id="cs-to" name="to" value="{{ $to->format('Y-m-d') }}" class="form-control form-control-sm">
        </div>
        <button type="submit" class="btn btn-sm btn-primary">
            <i class="fas fa-filter me-1"></i>{{ __('helpdeskanalytics::messages.apply') }}
        </button>
    </form>
</div>

@if($errors->any())
    <div class="alert alert-warning small">{{ $errors->first() }}</div>
@endif

@if(! $available)
    <div class="alert alert-secondary">{{ __('helpdeskanalytics::messages.chat_sales_unavailable') }}</div>
@else
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg">
            <div class="card border-0 shadow-sm"><div class="card-body">
                <div class="text-muted small">{{ __('helpdeskanalytics::messages.chat_sales_revenue') }}</div>
                <div class="h4 fw-bold mb-0">{{ $money($report['revenue']) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-lg">
            <div class="card border-0 shadow-sm"><div class="card-body">
                <div class="text-muted small">{{ __('helpdeskanalytics::messages.chat_sales_orders') }}</div>
                <div class="h4 fw-bold mb-0">{{ number_format($report['orders'], 0, ',', '.') }}</div>
            </div></div>
        </div>
        <div class="col-6 col-lg">
            <div class="card border-0 shadow-sm"><div class="card-body">
                <div class="text-muted small">{{ __('helpdeskanalytics::messages.chat_sales_avg_ticket') }}</div>
                <div class="h4 fw-bold mb-0">{{ $money($report['avg_ticket']) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-lg">
            <div class="card border-0 shadow-sm"><div class="card-body">
                <div class="text-muted small" title="{{ __('helpdeskanalytics::messages.chat_sales_conversion_hint') }}">
                    {{ __('helpdeskanalytics::messages.chat_sales_conversion') }} <i class="fas fa-circle-info"></i>
                </div>
                <div class="h4 fw-bold mb-0">
                    {{ $report['conversion'] === null ? '—' : number_format($report['conversion'] * 100, 1, ',', '.').' %' }}
                </div>
                <div class="text-muted small">{{ $report['orders'] }} / {{ $report['web_conversations'] }}</div>
            </div></div>
        </div>
        <div class="col-6 col-lg">
            <div class="card border-0 shadow-sm"><div class="card-body">
                <div class="text-muted small">{{ __('helpdeskanalytics::messages.chat_sales_same_session') }}</div>
                <div class="h4 fw-bold mb-0">{{ number_format($report['same_session'], 0, ',', '.') }}</div>
            </div></div>
        </div>
    </div>

    @if($report['pilot'])
        <div class="card border-0 shadow-sm mb-4"><div class="card-body">
            <h6 class="fw-semibold mb-1">{{ __('helpdeskanalytics::messages.chat_pilot_title') }}</h6>
            <p class="text-muted small mb-3">{{ __('helpdeskanalytics::messages.chat_pilot_hint') }}</p>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr>
                        <th>{{ __('helpdeskanalytics::messages.chat_pilot_group') }}</th>
                        <th class="text-end">{{ __('helpdeskanalytics::messages.chat_pilot_share') }}</th>
                        <th class="text-end">{{ __('helpdeskanalytics::messages.chat_sales_orders') }}</th>
                        <th class="text-end">{{ __('helpdeskanalytics::messages.chat_sales_revenue') }}</th>
                        <th class="text-end">{{ __('helpdeskanalytics::messages.chat_pilot_per_point') }}</th>
                    </tr></thead>
                    <tbody>
                    @foreach($report['pilot'] as $bucket => $row)
                        <tr>
                            <td>{{ __('helpdeskanalytics::messages.chat_pilot_'.$bucket) }}</td>
                            <td class="text-end">{{ $row['share'] === null ? '—' : $row['share'].' %' }}</td>
                            <td class="text-end">{{ $row['orders'] }}</td>
                            <td class="text-end">{{ $money($row['revenue']) }}</td>
                            <td class="text-end">{{ $row['revenue_per_point'] === null ? '—' : $money($row['revenue_per_point']) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div></div>
    @endif

    @if($report['orders'] === 0)
        <div class="card border-0 shadow-sm"><div class="card-body text-center text-muted py-5">
            <i class="fas fa-bag-shopping fa-2x mb-2 d-block"></i>{{ __('helpdeskanalytics::messages.chat_sales_empty') }}
        </div></div>
    @else
        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100"><div class="card-body">
                    <h6 class="fw-semibold mb-3">{{ __('helpdeskanalytics::messages.chat_sales_by_day') }}</h6>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr>
                                <th>{{ __('helpdeskanalytics::messages.chat_sales_day') }}</th>
                                <th class="text-end">{{ __('helpdeskanalytics::messages.chat_sales_orders') }}</th>
                                <th class="text-end">{{ __('helpdeskanalytics::messages.chat_sales_revenue') }}</th>
                            </tr></thead>
                            <tbody>
                            @foreach($report['by_day'] as $row)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($row->day)->format('d/m/Y') }}</td>
                                    <td class="text-end">{{ $row->orders }}</td>
                                    <td class="text-end">{{ $money($row->revenue) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div></div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100"><div class="card-body">
                    <h6 class="fw-semibold mb-3">{{ __('helpdeskanalytics::messages.chat_sales_by_agent') }}</h6>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr>
                                <th>{{ __('helpdeskanalytics::messages.chat_sales_agent') }}</th>
                                <th class="text-end">{{ __('helpdeskanalytics::messages.chat_sales_orders') }}</th>
                                <th class="text-end">{{ __('helpdeskanalytics::messages.chat_sales_revenue') }}</th>
                                <th class="text-end">{{ __('helpdeskanalytics::messages.chat_sales_avg_ticket') }}</th>
                            </tr></thead>
                            <tbody>
                            @foreach($report['by_agent'] as $row)
                                <tr>
                                    <td>
                                        @if($row->agent_id)
                                            {{ $report['agent_names'][$row->agent_id] ?? '#'.$row->agent_id }}
                                        @else
                                            <span class="text-muted"><i class="fas fa-robot me-1"></i>{{ __('helpdeskanalytics::messages.chat_sales_bot') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ $row->orders }}</td>
                                    <td class="text-end">{{ $money($row->revenue) }}</td>
                                    <td class="text-end">{{ $money($row->orders ? $row->revenue / $row->orders : 0) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div></div>
            </div>
        </div>

        <div class="card border-0 shadow-sm"><div class="card-body">
            <h6 class="fw-semibold mb-3">{{ __('helpdeskanalytics::messages.chat_sales_recent') }}</h6>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr>
                        <th>{{ __('helpdeskanalytics::messages.chat_sales_date') }}</th>
                        <th>{{ __('helpdeskanalytics::messages.chat_sales_order') }}</th>
                        <th class="text-end">{{ __('helpdeskanalytics::messages.chat_sales_total') }}</th>
                        <th>{{ __('helpdeskanalytics::messages.chat_sales_conversation') }}</th>
                        <th>{{ __('helpdeskanalytics::messages.chat_sales_agent') }}</th>
                        <th>{{ __('helpdeskanalytics::messages.chat_sales_matched_by') }}</th>
                    </tr></thead>
                    <tbody>
                    @foreach($report['recent'] as $sale)
                        <tr>
                            <td class="text-nowrap">{{ $sale->ordered_at?->format('d/m/Y H:i') }}</td>
                            <td class="text-nowrap">{{ $sale->order_reference ?: '#'.$sale->order_id }}</td>
                            <td class="text-end text-nowrap">{{ $money($sale->total) }}</td>
                            <td>
                                @if(\Illuminate\Support\Facades\Route::has('manager.helpdesk.conversations.index'))
                                    <a href="{{ route('manager.helpdesk.conversations.index', ['selected' => $sale->conversation_id]) }}">#{{ $sale->conversation_id }}</a>
                                @else
                                    #{{ $sale->conversation_id }}
                                @endif
                            </td>
                            <td>
                                @if($sale->agent_id)
                                    {{ $report['agent_names'][$sale->agent_id] ?? '#'.$sale->agent_id }}
                                @else
                                    <span class="text-muted">{{ __('helpdeskanalytics::messages.chat_sales_bot') }}</span>
                                @endif
                            </td>
                            <td class="small text-muted">
                                {{ $sale->matched_by === 'cart' ? __('helpdeskanalytics::messages.chat_sales_matched_cart') : __('helpdeskanalytics::messages.chat_sales_matched_cookie') }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div></div>
    @endif
@endif

@endsection

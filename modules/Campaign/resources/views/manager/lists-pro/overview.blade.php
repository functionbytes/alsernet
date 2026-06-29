@extends('campaign::refactor.layout')

@section('title', $list->name . ' — ' . trans('campaign::lists.overview.title'))

@section('page-header')
    <div class="mc-page-header">
        <div>
            <h1 class="mc-page-title">{{ $list->name }}</h1>
            <p class="mc-page-subtitle">{{ trans('campaign::lists.overview.title') }}</p>
        </div>
        <div class="mc-page-actions">
            <a href="{{ route('manager.lists_pro.index') }}" class="mc-btn mc-btn-secondary mc-btn-sm">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'arrow-left', 'size' => 16])
                {{ trans('campaign::lists.back_to_lists') }}
            </a>
            <a href="{{ route('manager.lists_pro.subscribers', $list->uid) }}" class="mc-btn mc-btn-primary mc-btn-sm">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'layers', 'size' => 16])
                {{ trans('campaign::lists.overview.btn_subs') }}
            </a>
        </div>
    </div>
@endsection

@section('content')

    {{-- Stats --}}
    <div class="mc-stats-row" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:var(--space-4);margin-bottom:var(--space-6);">
        <div class="mc-stat-card">
            <div class="mc-stat-card-icon">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'check', 'size' => 22])
            </div>
            <div class="mc-stat-card-value">{{ number_format($activeCount) }}</div>
            <div class="mc-stat-card-label">{{ trans('campaign::lists.stat.active') }}</div>
        </div>
        <div class="mc-stat-card">
            <div class="mc-stat-card-icon">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'info', 'size' => 22])
            </div>
            <div class="mc-stat-card-value">{{ number_format($inactiveCount) }}</div>
            <div class="mc-stat-card-label">{{ trans('campaign::lists.stat.inactive') }}</div>
        </div>
        <div class="mc-stat-card">
            <div class="mc-stat-card-icon">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'layers', 'size' => 22])
            </div>
            <div class="mc-stat-card-value">{{ number_format($fields->count()) }}</div>
            <div class="mc-stat-card-label">{{ trans('campaign::lists.overview.fields') }}</div>
        </div>
        <div class="mc-stat-card">
            <div class="mc-stat-card-icon">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'grid', 'size' => 22])
            </div>
            <div class="mc-stat-card-value">{{ number_format($segments->count()) }}</div>
            <div class="mc-stat-card-label">{{ trans('campaign::lists.overview.segments') }}</div>
        </div>
    </div>

    {{-- Custom Fields --}}
    <div class="mc-card" style="margin-bottom:var(--space-6);">
        <div class="mc-card-header">
            <h2 class="mc-card-title">{{ trans('campaign::lists.overview.fields') }}</h2>
        </div>
        <div class="mc-card-body">
            @if ($fields->count() > 0)
                <table class="mc-table">
                    <thead>
                        <tr>
                            <th>Tag</th>
                            <th>Label</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($fields as $field)
                        <tr>
                            <td><code>{{ '{{' . $field->tag . '}}' }}</code></td>
                            <td>{{ $field->label }}</td>
                            <td><span class="mc-badge mc-badge-default">{{ $field->type }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="mc-empty-state" style="padding:var(--space-6) 0;">
                    <div class="mc-empty-state-text">No custom fields defined.</div>
                </div>
            @endif
        </div>
    </div>

    {{-- Segments --}}
    <div class="mc-card">
        <div class="mc-card-header">
            <h2 class="mc-card-title">{{ trans('campaign::lists.overview.segments') }}</h2>
        </div>
        <div class="mc-card-body">
            @if ($segments->count() > 0)
                <table class="mc-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($segments as $segment)
                        <tr>
                            <td>{{ $segment->name }}</td>
                            <td>
                                @if ($segment->created_at)
                                    {{ $segment->created_at->format('M j, Y') }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="mc-empty-state" style="padding:var(--space-6) 0;">
                    <div class="mc-empty-state-text">No segments defined.</div>
                </div>
            @endif
        </div>
    </div>

@endsection

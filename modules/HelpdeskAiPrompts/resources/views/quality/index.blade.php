@extends('layouts.theme')

@section('title', __('helpdeskaiprompts::quality.title'))

@section('content')

    @include('core::components.card', ['title' => __('helpdeskaiprompts::quality.title')])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        {{-- Alertas activas --}}
        <div class="card">
            <div class="card-header p-4 border-bottom d-flex justify-content-between align-items-start">
                <div>
                    <h5 class="mb-1 fw-bold">{{ __('helpdeskaiprompts::quality.alerts_title') }}</h5>
                    <p class="small mb-0 text-muted">{{ __('helpdeskaiprompts::quality.alerts_subtitle', ['hours' => $thresholds['window_hours'], 'cooldown' => $thresholds['cooldown_hours']]) }}</p>
                </div>
                <a href="{{ route('helpdesk-ai-prompts.index') }}" class="btn bg-primary-subtle text-primary">
                    <i class="fas fa-arrow-left me-1"></i>{{ __('helpdeskaiprompts::quality.back') }}
                </a>
            </div>
            <div class="card-body">
                @if($alerts->isEmpty())
                    <div class="text-center py-4">
                        <i class="fas fa-circle-check fa-3x mb-3 text-success opacity-50"></i>
                        <p class="text-muted mb-0">{{ __('helpdeskaiprompts::quality.alerts_none') }}</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('helpdeskaiprompts::quality.col_case') }}</th>
                                    <th>{{ __('helpdeskaiprompts::quality.col_type') }}</th>
                                    <th class="text-end">{{ __('helpdeskaiprompts::quality.col_value') }}</th>
                                    <th class="text-end">{{ __('helpdeskaiprompts::quality.col_threshold') }}</th>
                                    <th class="text-end">{{ __('helpdeskaiprompts::quality.col_sample') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($alerts as $alert)
                                    @php($unit = $alert['type'] === 'cost' ? ' €' : ' %')
                                    <tr>
                                        <td class="fw-semibold">{{ $caseNames[$alert['case_key']] ?? $alert['case_key'] }}</td>
                                        <td><span class="badge bg-danger-subtle text-danger">{{ __('helpdeskaiprompts::quality.alert_'.$alert['type']) }}</span></td>
                                        <td class="text-end fw-bold">{{ number_format($alert['value'], 1, ',', '.') }}{{ $unit }}</td>
                                        <td class="text-end text-muted">{{ number_format($alert['threshold'], 1, ',', '.') }}{{ $unit }}</td>
                                        <td class="text-end text-muted">{{ $alert['sample'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- Regresión por caso --}}
        <div class="card">
            <div class="card-header p-4 border-bottom">
                <h5 class="mb-1 fw-bold">{{ __('helpdeskaiprompts::quality.cases_title') }}</h5>
                <p class="small mb-0 text-muted">{{ __('helpdeskaiprompts::quality.cases_subtitle', ['max' => $maxQuestions]) }}</p>
            </div>
            <div class="card-body">
                @if($cases->isEmpty())
                    <p class="text-muted text-center py-4 mb-0">{{ __('helpdeskaiprompts::quality.cases_empty') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('helpdeskaiprompts::quality.col_case') }}</th>
                                    <th class="text-center">{{ __('helpdeskaiprompts::quality.col_version') }}</th>
                                    <th>{{ __('helpdeskaiprompts::quality.col_last_report') }}</th>
                                    @if($canManage)
                                        <th class="text-center">{{ __('helpdeskaiprompts::quality.col_actions') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cases as $case)
                                    @php($last = $lastReports->get($case->key))
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $case->name }}</div>
                                            <small class="text-muted">{{ $case->key }}</small>
                                        </td>
                                        <td class="text-center">v{{ $case->version }}</td>
                                        <td>
                                            @if($last)
                                                <button type="button" class="btn btn-link p-0 quality-view-report" data-url="{{ route('helpdesk-ai-prompts.quality.reports.show', $last) }}">
                                                    {{ $last->created_at->format('d/m/Y H:i') }}
                                                </button>
                                                @include('helpdeskaiprompts::quality.partials.report-badge', ['report' => $last])
                                            @else
                                                <span class="text-muted">{{ __('helpdeskaiprompts::quality.no_report') }}</span>
                                            @endif
                                        </td>
                                        @if($canManage)
                                            <td class="text-center">
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-vertical"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <button type="button" class="dropdown-item quality-run-regression" data-url="{{ route('helpdesk-ai-prompts.quality.regression.run', $case) }}">
                                                                {{ __('helpdeskaiprompts::quality.run_regression') }}
                                                            </button>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- Historial --}}
        <div class="card">
            <div class="card-header p-4 border-bottom">
                <h5 class="mb-0 fw-bold">{{ __('helpdeskaiprompts::quality.history_title') }}</h5>
            </div>
            <div class="card-body">
                @if($reports->isEmpty())
                    <p class="text-muted text-center py-4 mb-0">{{ __('helpdeskaiprompts::quality.history_empty') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('helpdeskaiprompts::quality.col_date') }}</th>
                                    <th>{{ __('helpdeskaiprompts::quality.col_case') }}</th>
                                    <th class="text-center">{{ __('helpdeskaiprompts::quality.col_version') }}</th>
                                    <th class="text-center">{{ __('helpdeskaiprompts::quality.col_questions') }}</th>
                                    <th class="text-center">{{ __('helpdeskaiprompts::quality.col_regressions') }}</th>
                                    <th class="text-center">{{ __('helpdeskaiprompts::quality.col_score') }}</th>
                                    <th class="text-end">{{ __('helpdeskaiprompts::quality.col_cost') }}</th>
                                    <th>{{ __('helpdeskaiprompts::quality.col_status') }}</th>
                                    <th class="text-center">{{ __('helpdeskaiprompts::quality.col_actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reports as $report)
                                    <tr>
                                        <td>{{ $report->created_at->format('d/m/Y H:i') }}</td>
                                        <td>{{ $caseNames[$report->case_key] ?? $report->case_key }}</td>
                                        <td class="text-center">
                                            v{{ $report->case_version }}
                                            @if($report->is_draft)
                                                <span class="badge bg-secondary-subtle text-secondary">{{ __('helpdeskaiprompts::quality.draft') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $report->questions_total }}</td>
                                        <td class="text-center">
                                            <span class="badge {{ $report->regressions > 0 ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }}">{{ $report->regressions }}</span>
                                        </td>
                                        <td class="text-center">{{ $report->avg_score !== null ? number_format($report->avg_score, 2, ',', '.') : '—' }}</td>
                                        <td class="text-end">{{ number_format($report->cost_eur, 4, ',', '.') }} €</td>
                                        <td>@include('helpdeskaiprompts::quality.partials.report-badge', ['report' => $report])</td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <button type="button" class="dropdown-item quality-view-report" data-url="{{ route('helpdesk-ai-prompts.quality.reports.show', $report) }}">
                                                            {{ __('helpdeskaiprompts::quality.view_detail') }}
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @include('helpdeskaiprompts::quality.partials.report-modal')
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('modules/helpdeskaiprompts/css/ai-prompts.css') }}?v={{ @filemtime(public_path('modules/helpdeskaiprompts/css/ai-prompts.css')) }}">
@endpush

@push('scripts')
    <script>
        window.AiQualityConfig = {
            i18n: {
                running: @json(__('helpdeskaiprompts::quality.running')),
                runRegression: @json(__('helpdeskaiprompts::quality.run_regression')),
                runError: @json(__('helpdeskaiprompts::quality.run_error')),
                finishedOk: @json(__('helpdeskaiprompts::quality.finished_ok')),
                finishedFailed: @json(__('helpdeskaiprompts::quality.finished_failed')),
                loadError: @json(__('helpdeskaiprompts::quality.load_error')),
                question: @json(__('helpdeskaiprompts::quality.detail_question')),
                original: @json(__('helpdeskaiprompts::quality.detail_original')),
                newAnswer: @json(__('helpdeskaiprompts::quality.detail_new')),
                tools: @json(__('helpdeskaiprompts::quality.detail_tools')),
                escalated: @json(__('helpdeskaiprompts::quality.detail_escalated')),
                length: @json(__('helpdeskaiprompts::quality.detail_length')),
                judge: @json(__('helpdeskaiprompts::quality.detail_judge')),
                noBaseline: @json(__('helpdeskaiprompts::quality.detail_no_baseline')),
                yes: @json(__('helpdeskaiprompts::quality.yes')),
                no: @json(__('helpdeskaiprompts::quality.no')),
                none: @json(__('helpdeskaiprompts::quality.none')),
                truncated: @json(__('helpdeskaiprompts::quality.truncated')),
                summary: @json(__('helpdeskaiprompts::quality.summary_line')),
                verdicts: {
                    regression: @json(__('helpdeskaiprompts::quality.verdict_regression')),
                    ok: @json(__('helpdeskaiprompts::quality.verdict_ok')),
                    no_baseline: @json(__('helpdeskaiprompts::quality.verdict_no_baseline')),
                    error: @json(__('helpdeskaiprompts::quality.verdict_error')),
                },
            },
        };
    </script>
    <script src="{{ asset('modules/helpdeskaiprompts/js/ai-prompts-quality.js') }}?v={{ @filemtime(public_path('modules/helpdeskaiprompts/js/ai-prompts-quality.js')) }}"></script>
@endpush

@extends('layouts.theme')

@section('title', __('helpdeskaiprompts::ai-prompts.title'))

@section('content')

    @include('core::components.card', ['title' => __('helpdeskaiprompts::ai-prompts.title')])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom">
                <h5 class="mb-1 fw-bold">{{ __('helpdeskaiprompts::ai-prompts.title') }}</h5>
                <p class="small mb-0 text-muted">{{ __('helpdeskaiprompts::ai-prompts.subtitle') }}</p>
            </div>

            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-sm-6 col-lg-3">
                        <div class="card bg-light-primary h-100 mb-0">
                            <div class="card-body">
                                <h6 class="card-title mb-1">{{ __('helpdeskaiprompts::ai-prompts.stat_runs_30d') }}</h6>
                                <h3 class="mb-0 fw-bold">{{ number_format($stats['runs_30d']) }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card bg-light-warning h-100 mb-0">
                            <div class="card-body">
                                <h6 class="card-title mb-1">{{ __('helpdeskaiprompts::ai-prompts.stat_escalation_rate') }}</h6>
                                <h3 class="mb-0 fw-bold">{{ number_format($stats['escalation_rate'], 1) }}%</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card bg-light-success h-100 mb-0">
                            <div class="card-body">
                                <h6 class="card-title mb-1">{{ __('helpdeskaiprompts::ai-prompts.stat_satisfaction') }}</h6>
                                <h3 class="mb-0 fw-bold">{{ number_format($stats['satisfaction'], 1) }}%</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card bg-light-info h-100 mb-0">
                            <div class="card-body">
                                <h6 class="card-title mb-1">{{ __('helpdeskaiprompts::ai-prompts.stat_cost_30d') }}</h6>
                                <h3 class="mb-0 fw-bold">{{ number_format($stats['cost_30d'], 2) }} €</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card bg-light-secondary h-100 mb-0">
                            <div class="card-body">
                                <h6 class="card-title mb-1">{{ __('helpdeskaiprompts::ai-prompts.stat_active_cases') }}</h6>
                                <h3 class="mb-0 fw-bold">{{ number_format($stats['active_cases']) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-header border-bottom p-0">
                <ul class="nav nav-tabs" id="aiPromptsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab === 'casos' ? 'active' : '' }}" id="casos-tab" data-bs-toggle="tab" data-bs-target="#casos-content" type="button" role="tab">
                            <i class="fas fa-diagram-project me-1"></i> {{ __('helpdeskaiprompts::ai-prompts.tab_casos') }}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab === 'conocimiento' ? 'active' : '' }}" id="conocimiento-tab" data-bs-toggle="tab" data-bs-target="#conocimiento-content" type="button" role="tab">
                            <i class="fas fa-book me-1"></i> {{ __('helpdeskaiprompts::ai-prompts.tab_conocimiento') }}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab === 'prompt-base' ? 'active' : '' }}" id="prompt-base-tab" data-bs-toggle="tab" data-bs-target="#prompt-base-content" type="button" role="tab">
                            <i class="fas fa-file-lines me-1"></i> {{ __('helpdeskaiprompts::ai-prompts.tab_prompt_base') }}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab === 'acciones' ? 'active' : '' }}" id="acciones-tab" data-bs-toggle="tab" data-bs-target="#acciones-content" type="button" role="tab">
                            <i class="fas fa-bolt me-1"></i> {{ __('helpdeskaiprompts::ai-prompts.tab_acciones') }}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab === 'probador' ? 'active' : '' }}" id="probador-tab" data-bs-toggle="tab" data-bs-target="#probador-content" type="button" role="tab">
                            <i class="fas fa-flask me-1"></i> {{ __('helpdeskaiprompts::ai-prompts.tab_probador') }}
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body">
                <div class="tab-content">

                    {{-- ===== Casos ===== --}}
                    <div class="tab-pane fade {{ $activeTab === 'casos' ? 'show active' : '' }}" id="casos-content" role="tabpanel">
                        @if($canManage)
                            <div class="text-end mb-3">
                                <a href="{{ route('helpdesk-ai-prompts.cases.create') }}" class="btn bg-primary-subtle text-primary">
                                    <i class="fas fa-plus me-1"></i>{{ __('helpdeskaiprompts::ai-prompts.new_case') }}
                                </a>
                            </div>
                        @endif

                        @if($cases->isEmpty())
                            <div class="text-center py-5">
                                <i class="fas fa-diagram-project fa-3x mb-3 text-muted opacity-50"></i>
                                <p class="text-muted mb-0">{{ __('helpdeskaiprompts::ai-prompts.empty_cases') }}</p>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>{{ __('helpdeskaiprompts::ai-prompts.col_name') }}</th>
                                            <th>{{ __('helpdeskaiprompts::ai-prompts.col_keywords') }}</th>
                                            <th>{{ __('helpdeskaiprompts::ai-prompts.col_escalation') }}</th>
                                            <th class="text-center">{{ __('helpdeskaiprompts::ai-prompts.col_tools') }}</th>
                                            <th class="text-center">{{ __('helpdeskaiprompts::ai-prompts.col_active') }}</th>
                                            <th class="text-center">{{ __('helpdeskaiprompts::ai-prompts.col_runs_30d') }}</th>
                                            <th class="text-center">{{ __('helpdeskaiprompts::ai-prompts.col_escalation_rate') }}</th>
                                            <th class="text-center">{{ __('helpdeskaiprompts::ai-prompts.col_satisfaction') }}</th>
                                            <th class="text-center">{{ __('helpdeskaiprompts::ai-prompts.col_tokens_30d') }}</th>
                                            <th class="text-center">{{ __('helpdeskaiprompts::ai-prompts.col_cost_30d') }}</th>
                                            @if($canManage)
                                                <th class="text-center">{{ __('helpdeskaiprompts::ai-prompts.col_actions') }}</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($cases as $case)
                                            @php $metrics = $case->metrics; @endphp
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold small">{{ $case->name }}</div>
                                                    <div class="text-muted small">
                                                        {{ $case->key }}
                                                        @if($case->channel)
                                                            · <span class="badge bg-info-subtle text-info">{{ __('helpdeskaiprompts::ai-prompts.channels.'.$case->channel) }}</span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="small">
                                                    @forelse(array_slice($case->keywords ?? [], 0, 4) as $keyword)
                                                        <span class="badge bg-light-secondary text-dark me-1 mb-1">{{ $keyword }}</span>
                                                    @empty
                                                        <span class="text-muted">{{ __('helpdeskaiprompts::ai-prompts.no_keywords') }}</span>
                                                    @endforelse
                                                    @if(count($case->keywords ?? []) > 4)
                                                        <span class="text-muted">+{{ count($case->keywords) - 4 }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge bg-{{ ['never' => 'secondary', 'on_doubt' => 'warning', 'always' => 'danger'][$case->escalation] ?? 'secondary' }}-subtle text-{{ ['never' => 'secondary', 'on_doubt' => 'warning', 'always' => 'danger'][$case->escalation] ?? 'secondary' }}">
                                                        {{ __('helpdeskaiprompts::ai-prompts.escalation_'.$case->escalation) }}
                                                    </span>
                                                </td>
                                                <td class="text-center">{{ $case->allowed_tools ? count($case->allowed_tools) : __('helpdeskaiprompts::ai-prompts.no_tools_limit') }}</td>
                                                <td class="text-center">
                                                    <div class="form-check form-switch d-flex justify-content-center mb-0">
                                                        <input type="checkbox" class="form-check-input ai-toggle-active" role="switch"
                                                               data-url="{{ route('helpdesk-ai-prompts.cases.toggle-active', $case) }}"
                                                               @checked($case->is_active) @disabled(! $canManage)>
                                                    </div>
                                                </td>
                                                <td class="text-center">{{ $metrics['runs'] ?? 0 }}</td>
                                                <td class="text-center">{{ isset($metrics['escalation_rate']) ? number_format($metrics['escalation_rate'] * 100, 1).'%' : '—' }}</td>
                                                <td class="text-center">{{ isset($metrics['satisfaction']) && ($metrics['likes'] + $metrics['dislikes']) > 0 ? number_format($metrics['satisfaction'] * 100, 1).'%' : '—' }}</td>
                                                <td class="text-center">{{ isset($metrics['tokens']) && $metrics['tokens'] > 0 ? number_format($metrics['tokens']) : '—' }}</td>
                                                <td class="text-center">{{ isset($metrics['cost_eur']) && $metrics['cost_eur'] > 0 ? number_format($metrics['cost_eur'], 4).' €' : '—' }}</td>
                                                @if($canManage)
                                                    <td class="text-center">
                                                        <div class="dropdown">
                                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                                                <i class="fas fa-ellipsis-vertical"></i>
                                                            </a>
                                                            <ul class="dropdown-menu dropdown-menu-end">
                                                                <li><a class="dropdown-item" href="{{ route('helpdesk-ai-prompts.cases.edit', $case) }}">{{ __('helpdeskaiprompts::ai-prompts.action_edit') }}</a></li>
                                                                <li>
                                                                    <a class="dropdown-item ai-run-test" href="#"
                                                                       data-url="{{ route('helpdesk-ai-prompts.cases.test', $case) }}"
                                                                       data-title="{{ $case->name }}">
                                                                        {{ __('helpdeskaiprompts::ai-prompts.action_test') }}
                                                                    </a>
                                                                </li>
                                                                <li><a class="dropdown-item" href="{{ route('helpdesk-ai-prompts.cases.history', $case) }}">{{ __('helpdeskaiprompts::ai-prompts.action_history') }}</a></li>
                                                                <li>
                                                                    <a class="dropdown-item ai-duplicate-case" href="#" data-bs-toggle="modal" data-bs-target="#duplicate-modal"
                                                                       data-url="{{ route('helpdesk-ai-prompts.cases.duplicate', $case) }}"
                                                                       data-name="{{ $case->name }}">
                                                                        {{ __('helpdeskaiprompts::ai-prompts.action_duplicate') }}
                                                                    </a>
                                                                </li>
                                                                <li><hr class="dropdown-divider"></li>
                                                                <li>
                                                                    <a class="dropdown-item delete-btn" href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"
                                                                       data-url="{{ route('helpdesk-ai-prompts.cases.destroy', $case) }}"
                                                                       data-title="{{ __('helpdeskaiprompts::ai-prompts.action_delete') }}: {{ $case->name }}">
                                                                        {{ __('helpdeskaiprompts::ai-prompts.action_delete') }}
                                                                    </a>
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

                    {{-- ===== Conocimiento ===== --}}
                    <div class="tab-pane fade {{ $activeTab === 'conocimiento' ? 'show active' : '' }}" id="conocimiento-content" role="tabpanel">
                        @include('helpdeskaiprompts::partials.blocks-table', [
                            'blocks' => $knowledgeBlocks,
                            'kind' => 'knowledge',
                            'emptyKey' => 'empty_knowledge',
                            'newKey' => 'new_knowledge_block',
                        ])
                    </div>

                    {{-- ===== Prompt base ===== --}}
                    <div class="tab-pane fade {{ $activeTab === 'prompt-base' ? 'show active' : '' }}" id="prompt-base-content" role="tabpanel">
                        @include('helpdeskaiprompts::partials.blocks-table', [
                            'blocks' => $baseBlocks,
                            'kind' => 'base',
                            'emptyKey' => 'empty_base',
                            'newKey' => 'new_base_block',
                        ])
                    </div>

                    {{-- ===== Acciones ===== --}}
                    <div class="tab-pane fade {{ $activeTab === 'acciones' ? 'show active' : '' }}" id="acciones-content" role="tabpanel">
                        @include('helpdeskaiprompts::partials.actions-tab')
                    </div>

                    {{-- ===== Probador ===== --}}
                    <div class="tab-pane fade {{ $activeTab === 'probador' ? 'show active' : '' }}" id="probador-content" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-lg-5">
                                <form id="tester-form">
                                    <div class="mb-3">
                                        <label class="form-label" for="tester-question">{{ __('helpdeskaiprompts::ai-prompts.field_question') }}</label>
                                        <textarea id="tester-question" class="form-control" rows="3" maxlength="2000"
                                                  placeholder="{{ __('helpdeskaiprompts::ai-prompts.field_question_placeholder') }}"></textarea>
                                    </div>
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label" for="tester-channel">{{ __('helpdeskaiprompts::ai-prompts.field_channel') }}</label>
                                            <select id="tester-channel" class="form-select">
                                                <option value="">{{ __('helpdeskaiprompts::ai-prompts.channel_global') }}</option>
                                                @foreach($channels as $channel)
                                                    <option value="{{ $channel }}">{{ __('helpdeskaiprompts::ai-prompts.channels.'.$channel) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="tester-locale">{{ __('helpdeskaiprompts::ai-prompts.field_locale') }}</label>
                                            <input type="text" id="tester-locale" class="form-control" maxlength="5" value="es">
                                        </div>
                                    </div>
                                    <div class="form-check form-switch mb-3">
                                        <input type="checkbox" class="form-check-input" id="tester-logged-in">
                                        <label class="form-check-label" for="tester-logged-in">{{ __('helpdeskaiprompts::ai-prompts.field_logged_in') }}</label>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" id="tester-detect" class="btn btn-outline-primary">
                                            <i class="fas fa-magnifying-glass me-1"></i>{{ __('helpdeskaiprompts::ai-prompts.btn_detect') }}
                                        </button>
                                        <button type="button" id="tester-execute" class="btn btn-primary">
                                            <i class="fas fa-wand-magic-sparkles me-1"></i>{{ __('helpdeskaiprompts::ai-prompts.btn_execute') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <div class="col-lg-7">
                                <div id="tester-empty" class="text-center py-5 text-muted">
                                    <i class="fas fa-flask fa-3x mb-3 opacity-50"></i>
                                    <p class="mb-0">{{ __('helpdeskaiprompts::ai-prompts.field_question_placeholder') }}</p>
                                </div>
                                <div id="tester-results" class="d-none">
                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <div class="text-muted small">{{ __('helpdeskaiprompts::ai-prompts.result_case') }}</div>
                                            <div class="fw-semibold" id="tester-result-case">—</div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted small">{{ __('helpdeskaiprompts::ai-prompts.result_method') }}</div>
                                            <div class="fw-semibold" id="tester-result-method">—</div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="form-label mb-0">{{ __('helpdeskaiprompts::ai-prompts.result_prompt') }}</label>
                                            <button type="button" id="tester-copy-prompt" class="btn btn-sm btn-light">
                                                <i class="fas fa-copy me-1"></i>{{ __('helpdeskaiprompts::ai-prompts.copy_prompt') }}
                                            </button>
                                        </div>
                                        <pre id="tester-result-prompt" class="border rounded p-3 bg-light small ai-prompt-preview"></pre>
                                    </div>
                                    <div id="tester-execution" class="d-none">
                                        <hr>
                                        <div class="mb-2">
                                            <div class="text-muted small">{{ __('helpdeskaiprompts::ai-prompts.result_answer') }}</div>
                                            <div id="tester-result-answer" class="border rounded p-3"></div>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <div class="text-muted small">{{ __('helpdeskaiprompts::ai-prompts.result_tools') }}</div>
                                                <div id="tester-result-tools">—</div>
                                            </div>
                                            <div class="col-6">
                                                <div class="text-muted small">{{ __('helpdeskaiprompts::ai-prompts.result_action') }}</div>
                                                <div id="tester-result-action">—</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    @include('helpdeskaiprompts::partials.test-results-modal')
    @if($canManage)
        @include('helpdeskaiprompts::partials.action-test-modal')
    @endif

    @if($canManage)
        <div class="modal fade" id="duplicate-modal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form id="duplicate-form" method="POST" action="">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">{{ __('helpdeskaiprompts::ai-prompts.modal_duplicate_title') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted small" id="duplicate-case-name"></p>
                            <p class="text-muted small">{{ __('helpdeskaiprompts::ai-prompts.modal_duplicate_help') }}</p>
                            <label class="form-label" for="duplicate-channel">{{ __('helpdeskaiprompts::ai-prompts.field_channel') }}</label>
                            <select name="channel" id="duplicate-channel" class="form-select" required>
                                @foreach($channels as $channel)
                                    <option value="{{ $channel }}">{{ __('helpdeskaiprompts::ai-prompts.channels.'.$channel) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary w-100 mb-2">{{ __('helpdeskaiprompts::ai-prompts.modal_duplicate_confirm') }}</button>
                            <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">{{ __('helpdeskaiprompts::ai-prompts.cancel') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @include('core::components.delete')
    @endif
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('modules/helpdeskaiprompts/css/ai-prompts.css') }}?v={{ @filemtime(public_path('modules/helpdeskaiprompts/css/ai-prompts.css')) }}">
@endpush

@push('scripts')
    <script>
        window.AiPromptsConfig = {
            testerDetectUrl: @json(route('helpdesk-ai-prompts.tester.detect')),
            testerExecuteUrl: @json(route('helpdesk-ai-prompts.tester.execute')),
            activeTab: @json($activeTab),
            i18n: {
                methodKeyword: @json(__('helpdeskaiprompts::ai-prompts.method_keyword')),
                methodLlm: @json(__('helpdeskaiprompts::ai-prompts.method_llm')),
                methodDefault: @json(__('helpdeskaiprompts::ai-prompts.method_default')),
                methodForced: @json(__('helpdeskaiprompts::ai-prompts.method_forced')),
                methodNone: @json(__('helpdeskaiprompts::ai-prompts.method_none')),
                noneDetected: @json(__('helpdeskaiprompts::ai-prompts.none_detected')),
                copied: @json(__('helpdeskaiprompts::ai-prompts.copied')),
                copyPrompt: @json(__('helpdeskaiprompts::ai-prompts.copy_prompt')),
                resultPass: @json(__('helpdeskaiprompts::ai-prompts.result_pass')),
                resultFail: @json(__('helpdeskaiprompts::ai-prompts.result_fail')),
                noTestQuestions: @json(__('helpdeskaiprompts::ai-prompts.no_test_questions')),
                thQuestion: @json(__('helpdeskaiprompts::ai-prompts.th_question')),
                thAnswer: @json(__('helpdeskaiprompts::ai-prompts.th_answer')),
                thTools: @json(__('helpdeskaiprompts::ai-prompts.th_tools')),
                thResult: @json(__('helpdeskaiprompts::ai-prompts.th_result')),
            },
        };
    </script>
    <script src="{{ asset('modules/helpdeskaiprompts/js/ai-prompts-index.js') }}?v={{ @filemtime(public_path('modules/helpdeskaiprompts/js/ai-prompts-index.js')) }}"></script>
    @if($canManage)
        <script>
            window.AiActionsConfig = {
                i18n: {
                    statusOk: @json(__('helpdeskaiprompts::ai-prompts.actions.status_ok')),
                    statusDenied: @json(__('helpdeskaiprompts::ai-prompts.actions.status_denied')),
                    statusError: @json(__('helpdeskaiprompts::ai-prompts.actions.status_error')),
                    helpOk: @json(__('helpdeskaiprompts::ai-prompts.actions.test_help_ok')),
                    helpDenied: @json(__('helpdeskaiprompts::ai-prompts.actions.test_help_denied')),
                    helpError: @json(__('helpdeskaiprompts::ai-prompts.actions.test_help_error')),
                    inactive: @json(__('helpdeskaiprompts::ai-prompts.actions.test_inactive')),
                    running: @json(__('helpdeskaiprompts::ai-prompts.actions.test_running')),
                    run: @json(__('helpdeskaiprompts::ai-prompts.actions.test_run')),
                    testFailed: @json(__('helpdeskaiprompts::ai-prompts.actions.test_failed')),
                    boolYes: @json(__('helpdeskaiprompts::ai-prompts.actions.bool_yes')),
                },
            };
        </script>
        <script src="{{ asset('modules/helpdeskaiprompts/js/ai-prompts-actions.js') }}?v={{ @filemtime(public_path('modules/helpdeskaiprompts/js/ai-prompts-actions.js')) }}"></script>
    @endif
@endpush

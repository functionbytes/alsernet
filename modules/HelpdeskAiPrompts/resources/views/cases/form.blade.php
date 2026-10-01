@extends('layouts.theme')

@php
    $isEdit = $case->exists;
    $title = $isEdit ? __('helpdeskaiprompts::ai-prompts.edit_case_title') : __('helpdeskaiprompts::ai-prompts.new_case_title');
    $filters = old('filters', (array) ($case->filters ?? []));
    $examples = old('examples', (array) ($case->examples ?? []));
    $testQuestions = old('test_questions', (array) ($case->test_questions ?? []));
    $keywords = old('keywords', (array) ($case->keywords ?? []));
    $allowedTools = old('allowed_tools', (array) ($case->allowed_tools ?? []));
    $knowledgeKeys = old('knowledge_keys', (array) ($case->knowledge_keys ?? []));
    $filterChannels = (array) ($filters['channels'] ?? []);
    $filterLocales = (array) ($filters['locales'] ?? []);
    $filterUrlContains = (array) ($filters['url_contains'] ?? []);
@endphp

@section('title', $title)

@section('content')
    @include('core::components.card', ['title' => $title])

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ $isEdit ? route('helpdesk-ai-prompts.cases.update', $case) : route('helpdesk-ai-prompts.cases.store') }}"
                      method="POST" id="case-form">
                    @csrf
                    @if($isEdit) @method('PUT') @endif

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">{{ $title }}</h5>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')
                        @if($errors->any())
                            <div class="alert alert-danger small mb-3">
                                @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                            </div>
                        @endif

                        {{-- Información básica --}}
                        <h6 class="fw-semibold mb-3">{{ __('helpdeskaiprompts::ai-prompts.section_basic') }}</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label" for="c-key">{{ __('helpdeskaiprompts::ai-prompts.field_key') }} *</label>
                                <input type="text" name="key" id="c-key" class="form-control @error('key') is-invalid @enderror"
                                       maxlength="64" required value="{{ old('key', $case->key) }}" {{ $isEdit ? 'readonly' : '' }}>
                                <small class="text-muted">{{ __('helpdeskaiprompts::ai-prompts.field_key_help') }}</small>
                                @error('key') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="c-name">{{ __('helpdeskaiprompts::ai-prompts.field_name') }} *</label>
                                <input type="text" name="name" id="c-name" class="form-control @error('name') is-invalid @enderror"
                                       maxlength="255" required value="{{ old('name', $case->name) }}">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="c-description">{{ __('helpdeskaiprompts::ai-prompts.field_description') }} *</label>
                                <textarea name="description" id="c-description" class="form-control @error('description') is-invalid @enderror"
                                          rows="2" maxlength="2000" required>{{ old('description', $case->description) }}</textarea>
                                <small class="text-muted">{{ __('helpdeskaiprompts::ai-prompts.field_description_help') }}</small>
                                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="c-priority">{{ __('helpdeskaiprompts::ai-prompts.field_priority') }}</label>
                                <input type="number" name="priority" id="c-priority" class="form-control" min="-1000" max="1000"
                                       value="{{ old('priority', $case->priority ?? 0) }}">
                                <small class="text-muted">{{ __('helpdeskaiprompts::ai-prompts.field_priority_help') }}</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="c-channel">{{ __('helpdeskaiprompts::ai-prompts.field_case_channel') }}</label>
                                <select name="channel" id="c-channel" class="form-select">
                                    <option value="">{{ __('helpdeskaiprompts::ai-prompts.channel_global') }}</option>
                                    @foreach($channels as $channel)
                                        <option value="{{ $channel }}" @selected(old('channel', $case->channel) === $channel)>{{ __('helpdeskaiprompts::ai-prompts.channels.'.$channel) }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">{{ __('helpdeskaiprompts::ai-prompts.field_case_channel_help') }}</small>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check form-switch mb-2">
                                    <input type="hidden" name="is_active" value="0">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="c-active" @checked(old('is_active', $case->is_active ?? true))>
                                    <label class="form-check-label" for="c-active">{{ __('helpdeskaiprompts::ai-prompts.field_active') }}</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="c-instructions">{{ __('helpdeskaiprompts::ai-prompts.field_instructions') }} *</label>
                                <textarea name="instructions" id="c-instructions" class="form-control @error('instructions') is-invalid @enderror"
                                          rows="6" maxlength="8000" required data-counter="#c-instructions-counter">{{ old('instructions', $case->instructions) }}</textarea>
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted">{{ __('helpdeskaiprompts::ai-prompts.field_instructions_help') }}</small>
                                    <small class="text-muted"><span id="c-instructions-counter">0</span>/8000</small>
                                </div>
                                @error('instructions') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Ejemplos --}}
                        <h6 class="fw-semibold mb-1">{{ __('helpdeskaiprompts::ai-prompts.section_examples') }}</h6>
                        <p class="text-muted small mb-2">{{ __('helpdeskaiprompts::ai-prompts.section_examples_help') }}</p>
                        <div id="examples-rows" class="d-flex flex-column gap-2 mb-2">
                            @foreach($examples as $i => $example)
                                <div class="row g-2 align-items-start ai-example-row">
                                    <div class="col-md-5">
                                        <input type="text" name="examples[{{ $i }}][question]" class="form-control form-control-sm"
                                               placeholder="{{ __('helpdeskaiprompts::ai-prompts.field_example_question') }}" maxlength="500" value="{{ $example['question'] ?? '' }}">
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" name="examples[{{ $i }}][answer]" class="form-control form-control-sm"
                                               placeholder="{{ __('helpdeskaiprompts::ai-prompts.field_example_answer') }}" maxlength="1000" value="{{ $example['answer'] ?? '' }}">
                                    </div>
                                    <div class="col-md-1 text-end">
                                        <button type="button" class="btn btn-light btn-sm ai-remove-row" aria-label="{{ __('helpdeskaiprompts::ai-prompts.remove') }}"><i class="fas fa-xmark"></i></button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm mb-4" id="add-example">
                            <i class="fas fa-plus me-1"></i>{{ __('helpdeskaiprompts::ai-prompts.add_example') }}
                        </button>

                        {{-- Palabras clave --}}
                        <h6 class="fw-semibold mb-1">{{ __('helpdeskaiprompts::ai-prompts.section_keywords') }}</h6>
                        <p class="text-muted small mb-2">{{ __('helpdeskaiprompts::ai-prompts.field_keywords_help') }}</p>
                        <div class="ai-tags mb-1" id="keywords-tags" data-name="keywords">
                            @foreach($keywords as $keyword)
                                <span class="badge bg-light-secondary text-dark ai-tag">{{ $keyword }}<input type="hidden" name="keywords[]" value="{{ $keyword }}"><button type="button" class="ai-tag-remove">&times;</button></span>
                            @endforeach
                        </div>
                        <input type="text" class="form-control form-control-sm mb-4 ai-tag-add" data-target="#keywords-tags"
                               placeholder="{{ __('helpdeskaiprompts::ai-prompts.field_keywords_placeholder') }}">

                        {{-- Herramientas --}}
                        <h6 class="fw-semibold mb-1">{{ __('helpdeskaiprompts::ai-prompts.section_tools') }}</h6>
                        <p class="text-muted small mb-2">{{ __('helpdeskaiprompts::ai-prompts.section_tools_help') }}</p>
                        <div class="row g-2 mb-4">
                            @foreach($tools as $tool => $toolDescription)
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="allowed_tools[]" value="{{ $tool }}" id="tool-{{ $tool }}"
                                               @checked(in_array($tool, $allowedTools, true))>
                                        <label class="form-check-label small" for="tool-{{ $tool }}">
                                            <span class="fw-semibold">{{ $tool }}</span> — {{ $toolDescription }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Conocimiento --}}
                        <h6 class="fw-semibold mb-1">{{ __('helpdeskaiprompts::ai-prompts.section_knowledge') }}</h6>
                        <p class="text-muted small mb-2">{{ __('helpdeskaiprompts::ai-prompts.section_knowledge_help') }}</p>
                        <div class="row g-2 mb-4">
                            @forelse($knowledgeBlocks as $block)
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="knowledge_keys[]" value="{{ $block->key }}" id="kb-{{ $block->key }}"
                                               @checked(in_array($block->key, $knowledgeKeys, true))>
                                        <label class="form-check-label small" for="kb-{{ $block->key }}">{{ $block->name }} <span class="text-muted">({{ $block->key }})</span></label>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12 text-muted small">{{ __('helpdeskaiprompts::ai-prompts.empty_knowledge') }}</div>
                            @endforelse
                        </div>

                        {{-- Derivación --}}
                        <h6 class="fw-semibold mb-3">{{ __('helpdeskaiprompts::ai-prompts.section_escalation') }}</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label" for="c-escalation">{{ __('helpdeskaiprompts::ai-prompts.field_escalation') }}</label>
                                <select name="escalation" id="c-escalation" class="form-select">
                                    @foreach(['never', 'on_doubt', 'always'] as $option)
                                        <option value="{{ $option }}" @selected(old('escalation', $case->escalation ?? 'on_doubt') === $option)>
                                            {{ __('helpdeskaiprompts::ai-prompts.escalation_'.$option) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label" for="c-escalation-message">{{ __('helpdeskaiprompts::ai-prompts.field_escalation_message') }}</label>
                                <input type="text" name="escalation_message" id="c-escalation-message" class="form-control" maxlength="500"
                                       value="{{ old('escalation_message', $case->escalation_message) }}">
                                <small class="text-muted">{{ __('helpdeskaiprompts::ai-prompts.field_escalation_message_help') }}</small>
                            </div>
                        </div>

                        {{-- Procedimiento --}}
                        @if($procedureFlows->isNotEmpty() || $case->procedure_flow_id)
                            <h6 class="fw-semibold mb-1">{{ __('helpdeskaiprompts::ai-prompts.section_procedure') }}</h6>
                            <p class="text-muted small mb-2">{{ __('helpdeskaiprompts::ai-prompts.section_procedure_help') }}</p>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label" for="c-procedure">{{ __('helpdeskaiprompts::ai-prompts.field_procedure') }}</label>
                                    <select name="procedure_flow_id" id="c-procedure" class="form-select">
                                        <option value="">{{ __('helpdeskaiprompts::ai-prompts.procedure_none') }}</option>
                                        @foreach($procedureFlows as $flow)
                                            <option value="{{ $flow->id }}" @selected((int) old('procedure_flow_id', $case->procedure_flow_id) === $flow->id)>{{ $flow->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('procedure_flow_id')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="c-procedure-input">{{ __('helpdeskaiprompts::ai-prompts.field_procedure_input') }}</label>
                                    <textarea name="procedure_input_text" id="c-procedure-input" class="form-control" rows="3" placeholder="email={{ '{{customer_email}}' }}">{{ old('procedure_input_text', collect((array) $case->procedure_input)->map(fn ($value, $name) => $name.'='.$value)->implode("\n")) }}</textarea>
                                    <small class="text-muted">{{ __('helpdeskaiprompts::ai-prompts.field_procedure_input_help') }}</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="c-procedure-outputs">{{ __('helpdeskaiprompts::ai-prompts.field_procedure_outputs') }}</label>
                                    <input type="text" name="procedure_outputs_text" id="c-procedure-outputs" class="form-control"
                                           value="{{ old('procedure_outputs_text', implode(', ', (array) $case->procedure_outputs)) }}">
                                    <small class="text-muted">{{ __('helpdeskaiprompts::ai-prompts.field_procedure_outputs_help') }}</small>
                                </div>
                            </div>
                        @endif

                        {{-- Filtros --}}
                        <h6 class="fw-semibold mb-1">{{ __('helpdeskaiprompts::ai-prompts.section_filters') }}</h6>
                        <p class="text-muted small mb-2">{{ __('helpdeskaiprompts::ai-prompts.section_filters_help') }}</p>
                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <label class="form-label small">{{ __('helpdeskaiprompts::ai-prompts.field_filter_channels') }}</label>
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach($channels as $channel)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="filters[channels][]" value="{{ $channel }}" id="fc-{{ $channel }}"
                                                   @checked(in_array($channel, $filterChannels, true))>
                                            <label class="form-check-label small" for="fc-{{ $channel }}">{{ __('helpdeskaiprompts::ai-prompts.channels.'.$channel) }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small" for="filters-locales-add">{{ __('helpdeskaiprompts::ai-prompts.field_filter_locales') }}</label>
                                <div class="ai-tags mb-1" id="filters-locales-tags" data-name="filters[locales]">
                                    @foreach($filterLocales as $locale)
                                        <span class="badge bg-light-secondary text-dark ai-tag">{{ $locale }}<input type="hidden" name="filters[locales][]" value="{{ $locale }}"><button type="button" class="ai-tag-remove">&times;</button></span>
                                    @endforeach
                                </div>
                                <input type="text" id="filters-locales-add" class="form-control form-control-sm ai-tag-add" data-target="#filters-locales-tags" placeholder="es, en, fr…">
                            </div>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small" for="filters-url-add">{{ __('helpdeskaiprompts::ai-prompts.field_filter_url_contains') }}</label>
                                <div class="ai-tags mb-1" id="filters-url-tags" data-name="filters[url_contains]">
                                    @foreach($filterUrlContains as $needle)
                                        <span class="badge bg-light-secondary text-dark ai-tag">{{ $needle }}<input type="hidden" name="filters[url_contains][]" value="{{ $needle }}"><button type="button" class="ai-tag-remove">&times;</button></span>
                                    @endforeach
                                </div>
                                <input type="text" id="filters-url-add" class="form-control form-control-sm ai-tag-add" data-target="#filters-url-tags" placeholder="/pedido, /carrito…">
                                <small class="text-muted">{{ __('helpdeskaiprompts::ai-prompts.field_filter_url_contains_help') }}</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small" for="filters-logged-in">{{ __('helpdeskaiprompts::ai-prompts.field_filter_logged_in') }}</label>
                                <select name="filters[logged_in]" id="filters-logged-in" class="form-select">
                                    @foreach(['any', 'yes', 'no'] as $option)
                                        <option value="{{ $option }}" @selected(($filters['logged_in'] ?? 'any') === $option)>
                                            {{ __('helpdeskaiprompts::ai-prompts.filter_logged_in_'.$option) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small" for="filters-hours">{{ __('helpdeskaiprompts::ai-prompts.field_filter_hours') }}</label>
                                <input type="text" name="filters[hours]" id="filters-hours" class="form-control @error('filters.hours') is-invalid @enderror"
                                       maxlength="11" placeholder="9-14" value="{{ $filters['hours'] ?? '' }}">
                                <small class="text-muted">{{ __('helpdeskaiprompts::ai-prompts.field_filter_hours_help') }}</small>
                                @error('filters.hours') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Preguntas de prueba --}}
                        <h6 class="fw-semibold mb-1">{{ __('helpdeskaiprompts::ai-prompts.section_test_questions') }}</h6>
                        <p class="text-muted small mb-2">{{ __('helpdeskaiprompts::ai-prompts.section_test_questions_help') }}</p>
                        <div id="test-questions-rows" class="d-flex flex-column gap-3 mb-2">
                            @foreach($testQuestions as $i => $tq)
                                @include('helpdeskaiprompts::cases.partials.test-question-row', ['i' => $i, 'tq' => $tq, 'tools' => $tools])
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm mb-2" id="add-test-question">
                            <i class="fas fa-plus me-1"></i>{{ __('helpdeskaiprompts::ai-prompts.add_test_question') }}
                        </button>
                    </div>

                    <div class="card-footer d-flex flex-column gap-2">
                        <button type="submit" class="btn btn-primary w-100">{{ __('helpdeskaiprompts::ai-prompts.save') }}</button>
                        <button type="button" id="test-draft-btn" class="btn btn-outline-primary w-100">
                            <i class="fas fa-flask me-1"></i>{{ __('helpdeskaiprompts::ai-prompts.test_draft') }}
                        </button>
                        @if($isEdit)
                            <a href="{{ route('helpdesk-ai-prompts.cases.history', $case) }}" class="btn btn-light w-100">{{ __('helpdeskaiprompts::ai-prompts.action_history') }}</a>
                        @endif
                        <a href="{{ route('helpdesk-ai-prompts.index', ['tab' => 'casos']) }}" class="btn btn-light w-100">{{ __('helpdeskaiprompts::ai-prompts.cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">{{ __('helpdeskaiprompts::ai-prompts.help_case_title') }}</h6>
                    <p class="card-text text-muted small">{{ __('helpdeskaiprompts::ai-prompts.help_case_body') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Hidden templates cloned by JS for new repeater rows --}}
    <template id="example-row-template">
        <div class="row g-2 align-items-start ai-example-row">
            <div class="col-md-5">
                <input type="text" name="examples[__INDEX__][question]" class="form-control form-control-sm"
                       placeholder="{{ __('helpdeskaiprompts::ai-prompts.field_example_question') }}" maxlength="500">
            </div>
            <div class="col-md-6">
                <input type="text" name="examples[__INDEX__][answer]" class="form-control form-control-sm"
                       placeholder="{{ __('helpdeskaiprompts::ai-prompts.field_example_answer') }}" maxlength="1000">
            </div>
            <div class="col-md-1 text-end">
                <button type="button" class="btn btn-light btn-sm ai-remove-row" aria-label="{{ __('helpdeskaiprompts::ai-prompts.remove') }}"><i class="fas fa-xmark"></i></button>
            </div>
        </div>
    </template>

    <template id="test-question-row-template">
        @include('helpdeskaiprompts::cases.partials.test-question-row', ['i' => '__INDEX__', 'tq' => [], 'tools' => $tools])
    </template>

    @include('helpdeskaiprompts::partials.test-results-modal')
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('modules/helpdeskaiprompts/css/ai-prompts.css') }}?v={{ @filemtime(public_path('modules/helpdeskaiprompts/css/ai-prompts.css')) }}">
@endpush

@push('scripts')
    <script>
        window.AiPromptsCaseFormConfig = {
            testDraftUrl: @json(route('helpdesk-ai-prompts.cases.test-draft')),
            i18n: {
                resultPass: @json(__('helpdeskaiprompts::ai-prompts.result_pass')),
                resultFail: @json(__('helpdeskaiprompts::ai-prompts.result_fail')),
                noTestQuestions: @json(__('helpdeskaiprompts::ai-prompts.no_test_questions')),
            },
        };
    </script>
    <script src="{{ asset('modules/helpdeskaiprompts/js/ai-prompts-case-form.js') }}?v={{ @filemtime(public_path('modules/helpdeskaiprompts/js/ai-prompts-case-form.js')) }}"></script>
@endpush

@extends('layouts.theme')

@php
    $t = fn (string $key, array $replace = []) => __('helpdeskaiprompts::ai-prompts.actions.'.$key, $replace);
    $isEdit = $action->exists;
    $isBuiltin = $form['is_builtin'];
    $isBridge = $action->type === 'bridge';
    $rules = $form['rules'];
    $title = match (true) {
        $isBuiltin => $t('edit_builtin_title'),
        $isEdit => $t('edit_title'),
        default => $t('new_title_'.$action->type),
    };
    $formAction = $isEdit
        ? route('helpdesk-ai-prompts.actions.update', $action)
        : route('helpdesk-ai-prompts.actions.store');
    $backUrl = route('helpdesk-ai-prompts.index', ['tab' => 'acciones']);
    $overrideValue = $form['description_overridden'] ? $action->description : '';
@endphp

@section('title', $title)

@section('content')
    @include('core::components.card', ['title' => $title])

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ $formAction }}" method="POST" id="action-form" novalidate>
                    @csrf
                    @if($isEdit) @method('PUT') @else <input type="hidden" name="type" value="{{ $action->type }}"> @endif

                    <div class="card-header border-bottom p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h5 class="mb-0 fw-bold">{{ $title }}</h5>
                            <small class="text-muted">{{ $t('type_'.$action->type) }}@if($isEdit) · {{ $action->key }} · v{{ $action->version }}@endif</small>
                        </div>
                        @if($isEdit)
                            <a href="{{ route('helpdesk-ai-prompts.actions.history', $action) }}" class="btn btn-sm btn-light">
                                <i class="fas fa-clock-rotate-left me-1"></i>{{ __('helpdeskaiprompts::ai-prompts.action_history') }}
                            </a>
                        @endif
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')
                        <div class="alert alert-danger small d-none" id="action-form-errors" role="alert"></div>

                        {{-- Información básica --}}
                        <h6 class="fw-semibold mb-3">{{ $t('section_basic') }}</h6>
                        <div class="row g-3 mb-4">
                            @if($isBuiltin)
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('helpdeskaiprompts::ai-prompts.field_name') }}</label>
                                    <input type="text" class="form-control" value="{{ $action->name }}" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('helpdeskaiprompts::ai-prompts.field_key') }}</label>
                                    <input type="text" class="form-control font-monospace" value="{{ $action->key }}" readonly>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="a-description">{{ $t('field_description_override') }}</label>
                                    <textarea name="description" id="a-description" class="form-control" rows="4" maxlength="2000"
                                              placeholder="{{ $form['code_description'] }}">{{ $overrideValue }}</textarea>
                                    <small class="text-muted">{{ $t('field_description_override_help') }}</small>
                                    <div class="invalid-feedback" data-error="description"></div>
                                </div>
                            @else
                                <div class="col-md-6">
                                    <label class="form-label" for="a-key">{{ __('helpdeskaiprompts::ai-prompts.field_key') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="key" id="a-key" class="form-control font-monospace" maxlength="48" required
                                           value="{{ $action->key }}" @readonly($isEdit)>
                                    <small class="text-muted">{{ $t('field_key_help') }}</small>
                                    <div class="invalid-feedback" data-error="key"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="a-name">{{ __('helpdeskaiprompts::ai-prompts.field_name') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="a-name" class="form-control" maxlength="120" required value="{{ $action->name }}">
                                    <div class="invalid-feedback" data-error="name"></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="a-description">{{ $t('field_description') }} <span class="text-danger">*</span></label>
                                    <textarea name="description" id="a-description" class="form-control" rows="3" maxlength="2000" required>{{ $action->description }}</textarea>
                                    <small class="text-muted">{{ $t('field_description_help') }}</small>
                                    <div class="invalid-feedback" data-error="description"></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label d-block">{{ $t('field_channels') }}</label>
                                    <div class="d-flex flex-wrap gap-3">
                                        @foreach($channels as $channel)
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="channels[]" value="{{ $channel }}" id="a-channel-{{ $channel }}"
                                                       @checked(in_array($channel, $form['channels'], true))>
                                                <label class="form-check-label small" for="a-channel-{{ $channel }}">{{ __('helpdeskaiprompts::ai-prompts.channels.'.$channel) }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                    <small class="text-muted">{{ $t('field_channels_help') }}</small>
                                    <div class="invalid-feedback" data-error="channels"></div>
                                </div>
                            @endif
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" role="switch" id="a-active" name="is_active" value="1" @checked($action->exists ? $action->is_active : false)>
                                    <label class="form-check-label" for="a-active">{{ $t('field_active') }}</label>
                                </div>
                                @unless($isBuiltin)
                                    <small class="text-muted">{{ $t('field_active_help') }}</small>
                                @endunless
                            </div>
                        </div>

                        @unless($isBuiltin)
                            @if($isBridge)
                                @include('helpdeskaiprompts::actions.partials.bridge-section')
                            @else
                                @include('helpdeskaiprompts::actions.partials.http-section')
                            @endif

                            {{-- Parámetros --}}
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="fw-semibold mb-0">{{ $t('section_params') }}</h6>
                                <button type="button" class="btn btn-sm bg-primary-subtle text-primary" id="param-add">
                                    <i class="fas fa-plus me-1"></i>{{ $t('param_add') }}
                                </button>
                            </div>
                            <p class="text-muted small mb-2">{{ $t('section_params_help') }}</p>
                            <div id="params-list" class="mb-1">
                                @foreach($form['parameters'] as $i => $param)
                                    @include('helpdeskaiprompts::actions.partials.param-row', ['i' => $i, 'p' => $param])
                                @endforeach
                            </div>
                            <template id="param-row-template">
                                @include('helpdeskaiprompts::actions.partials.param-row', ['i' => '__INDEX__', 'p' => []])
                            </template>
                            <p class="text-muted small {{ $form['parameters'] === [] ? '' : 'd-none' }}" id="params-empty">{{ $t('params_empty') }}</p>
                            <div class="alert alert-info small mb-4">
                                <i class="fas fa-circle-info me-1"></i>{{ $t('params_auto_note') }}
                            </div>

                            {{-- Respuesta --}}
                            <h6 class="fw-semibold mb-1">{{ $t('section_response') }}</h6>
                            <p class="text-muted small mb-2">{{ $t('section_response_help') }}</p>
                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label class="form-label">{{ $t('field_response_fields') }}</label>
                                    @include('helpdeskaiprompts::actions.partials.tags', [
                                        'id' => 'response-fields-tags',
                                        'name' => 'response[fields][]',
                                        'values' => $form['response_fields'],
                                        'placeholder' => $t('field_response_fields_placeholder'),
                                        'errorKey' => 'response.fields',
                                    ])
                                    <small class="text-muted">{{ $t('field_response_fields_help') }}</small>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">{{ $t('field_allow_pii') }}</label>
                                    @include('helpdeskaiprompts::actions.partials.tags', [
                                        'id' => 'allow-pii-tags',
                                        'name' => 'response[allow_pii][]',
                                        'values' => $form['allow_pii'],
                                        'placeholder' => $t('field_allow_pii_placeholder'),
                                        'errorKey' => 'response.allow_pii',
                                    ])
                                    <small class="text-muted">{{ $t('field_allow_pii_help') }}</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="a-max-chars">{{ $t('field_max_chars') }}</label>
                                    <input type="number" name="response[max_chars]" id="a-max-chars" class="form-control" min="100" max="6000"
                                           placeholder="{{ $defaultMaxChars }}" value="{{ $form['max_chars'] }}">
                                    <div class="invalid-feedback" data-error="response.max_chars"></div>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label" for="a-empty-message">{{ $t('field_empty_message') }}</label>
                                    <input type="text" name="response[empty_message]" id="a-empty-message" class="form-control" maxlength="300"
                                           value="{{ $form['empty_message'] }}">
                                    <div class="invalid-feedback" data-error="response.empty_message"></div>
                                </div>
                            </div>

                            {{-- Reglas --}}
                            <h6 class="fw-semibold mb-3">{{ $t('section_rules') }}</h6>
                            <div class="row g-3 mb-2">
                                <div class="col-md-6">
                                    <label class="form-label" for="a-ownership">{{ $t('field_ownership') }}</label>
                                    <select name="rules[ownership]" id="a-ownership" class="form-select">
                                        @foreach(['none', 'verified', 'order_email_pair'] as $ownership)
                                            <option value="{{ $ownership }}" @selected($rules['ownership'] === $ownership)>{{ $t('ownership_'.$ownership) }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted" id="ownership-help">{{ $t('field_ownership_help') }}</small>
                                    <div class="invalid-feedback" data-error="rules.ownership"></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch mb-2">
                                        <input type="checkbox" class="form-check-input" role="switch" id="a-requires-verified" name="rules[requires_verified]" value="1" @checked($rules['requires_verified'])>
                                        <label class="form-check-label" for="a-requires-verified">{{ $t('field_requires_verified') }}</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input" role="switch" id="a-confirm" name="rules[confirm]" value="1" @checked($rules['confirm'])>
                                        <label class="form-check-label" for="a-confirm">{{ $t('field_confirm') }}</label>
                                    </div>
                                    <small class="text-muted d-none" id="confirm-forced-note">{{ $t('field_confirm_forced') }}</small>
                                    <div class="invalid-feedback" data-error="rules.confirm"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="a-max-per-conversation">{{ $t('field_max_per_conversation') }}</label>
                                    <input type="number" name="rules[max_per_conversation]" id="a-max-per-conversation" class="form-control" min="1" max="100" value="{{ $rules['max_per_conversation'] }}">
                                    <div class="invalid-feedback" data-error="rules.max_per_conversation"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="a-timeout">{{ $t('field_timeout') }}</label>
                                    <input type="number" name="rules[timeout]" id="a-timeout" class="form-control" min="1" max="{{ $maxTimeout }}" value="{{ $rules['timeout'] }}">
                                    <small class="text-muted">{{ $t('field_timeout_help', ['max' => $maxTimeout]) }}</small>
                                    <div class="invalid-feedback" data-error="rules.timeout"></div>
                                </div>
                            </div>
                        @endunless
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1" id="action-form-submit">{{ __('helpdeskaiprompts::ai-prompts.save') }}</button>
                        <a href="{{ $backUrl }}" class="btn btn-light w-100">{{ __('helpdeskaiprompts::ai-prompts.cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">{{ $t('help_about_title') }}</h6>
                    <p class="card-text text-muted">{{ $t('help_about_'.$action->type) }}</p>
                </div>
                @unless($isBuiltin)
                    <hr class="my-0">
                    <div class="card-body">
                        <h6 class="card-title mb-3">{{ $t('help_variables_title') }}</h6>
                        <ul class="small text-muted ps-3 mb-0">
                            @verbatim
                                <li><code>{{args.nombre}}</code> — un parámetro declarado</li>
                                <li><code>{{args.nombre|0}}</code> — con valor por defecto</li>
                                <li><code>{{customer.email}}</code>, <code>{{customer.ps_id}}</code> — cliente verificado</li>
                                <li><code>{{order.id}}</code> — pedido ya comprobado</li>
                                <li><code>{{conversation.id}}</code></li>
                            @endverbatim
                        </ul>
                    </div>
                    <hr class="my-0">
                    <div class="card-body">
                        <h6 class="card-title mb-3">{{ $t('help_security_title') }}</h6>
                        <p class="card-text text-muted small mb-0">{{ $t('help_security_text') }}</p>
                    </div>
                @endunless
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('modules/helpdeskaiprompts/css/ai-prompts.css') }}?v={{ @filemtime(public_path('modules/helpdeskaiprompts/css/ai-prompts.css')) }}">
@endpush

@push('scripts')
    <script>
        window.AiActionFormConfig = {
            isBridge: @json($isBridge),
            allowlist: @json($allowlist),
            paramCount: @json(count($form['parameters'])),
            i18n: {
                specCustomer: @json($t('spec_customer')),
                specOrder: @json($t('spec_order')),
                specOrderOptional: @json($t('spec_order_optional')),
                specWrite: @json($t('spec_write')),
                specRead: @json($t('spec_read')),
                saving: @json($t('saving')),
                save: @json(__('helpdeskaiprompts::ai-prompts.save')),
                reviewErrors: @json($t('review_errors')),
                saveFailed: @json($t('save_failed')),
            },
        };
    </script>
    <script src="{{ asset('modules/helpdeskaiprompts/js/ai-prompts-action-form.js') }}?v={{ @filemtime(public_path('modules/helpdeskaiprompts/js/ai-prompts-action-form.js')) }}"></script>
@endpush

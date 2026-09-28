@extends('layouts.theme')

@php
    $isEdit = $trigger !== null;
    $title = $isEdit ? __('helpdesklivechat::triggers.edit') : __('helpdesklivechat::triggers.new');
    $conditions = old('conditions', $isEdit ? (array) $trigger->conditions : [['type' => 'page_time', 'op' => 'gte', 'value' => '30']]);
    $types = __('helpdesklivechat::triggers.types');
    $ops = __('helpdesklivechat::triggers.ops');
    // Operadores válidos por tipo (el servidor valida igual en WidgetTriggerRequest).
    $opsByType = [
        'site_time' => ['gte', 'lte'], 'page_time' => ['gte', 'lte'], 'pages_visited' => ['gte', 'lte'],
        'products_viewed' => ['gte', 'lte'], 'cart_value' => ['gte', 'lte'], 'cart_items' => ['gte', 'lte'],
        'product_viewed' => ['eq'], 'current_product' => ['eq'], 'locale' => ['eq'],
        'url' => ['contains', 'starts', 'ends', 'equals'], 'weekday' => ['in'], 'hour_range' => ['between'],
    ];
@endphp

@section('title', $title)

@section('content')
    @include('core::components.card', ['title' => $title])

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ $isEdit ? route('settings.helpdesk-livechat.triggers.update', $trigger->id) : route('settings.helpdesk-livechat.triggers.store') }}"
                      method="POST" id="trigger-form"
                      data-ops-by-type='@json($opsByType)' data-op-labels='@json($ops)'>
                    @csrf
                    @if($isEdit) @method('PUT') @endif

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">{{ $title }}</h5>
                        <small class="text-muted">{{ __('helpdesklivechat::triggers.subtitle') }}</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')
                        @if($errors->any())
                            <div class="alert alert-danger small mb-3">
                                @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                            </div>
                        @endif

                        <h6 class="fw-semibold mb-3">{{ __('helpdesklivechat::triggers.section_basic') }}</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label" for="t-web">{{ __('helpdesklivechat::triggers.field_channel') }} *</label>
                                <select name="web_id" id="t-web" class="form-select" required>
                                    @foreach($webs as $web)
                                        <option value="{{ $web->id }}" @selected((int) old('web_id', $trigger?->web_id) === $web->id)>
                                            {{ $web->inbox?->name ?? $web->website_url }} · {{ $web->website_url }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="t-name">{{ __('helpdesklivechat::triggers.field_name') }} *</label>
                                <input type="text" name="name" id="t-name" class="form-control" maxlength="120" required value="{{ old('name', $trigger?->name) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="t-priority">{{ __('helpdesklivechat::triggers.field_priority') }}</label>
                                <input type="number" name="priority" id="t-priority" class="form-control" min="0" max="1000" value="{{ old('priority', $trigger?->priority ?? 50) }}">
                                <small class="text-muted">{{ __('helpdesklivechat::triggers.field_priority_help') }}</small>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check form-switch mb-2">
                                    <input type="hidden" name="is_active" value="0">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="t-active" @checked(old('is_active', $trigger?->is_active ?? true))>
                                    <label class="form-check-label" for="t-active">{{ __('helpdesklivechat::triggers.field_active') }}</label>
                                </div>
                            </div>
                        </div>

                        <h6 class="fw-semibold mb-3">{{ __('helpdesklivechat::triggers.section_conditions') }}</h6>
                        <div class="mb-3">
                            <label class="form-label" for="t-match">{{ __('helpdesklivechat::triggers.field_match') }}</label>
                            <select name="match" id="t-match" class="form-select">
                                <option value="all" @selected(old('match', $trigger?->match ?? 'all') === 'all')>{{ __('helpdesklivechat::triggers.match_all') }}</option>
                                <option value="any" @selected(old('match', $trigger?->match) === 'any')>{{ __('helpdesklivechat::triggers.match_any') }}</option>
                            </select>
                        </div>
                        <div id="t-conditions" class="d-flex flex-column gap-2 mb-2">
                            @foreach($conditions as $i => $c)
                                <div class="row g-2 align-items-center t-condition">
                                    <div class="col-md-5">
                                        <select name="conditions[{{ $i }}][type]" class="form-select t-type">
                                            @foreach($types as $key => $label)
                                                <option value="{{ $key }}" @selected(($c['type'] ?? '') === $key)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <select name="conditions[{{ $i }}][op]" class="form-select t-op" data-selected="{{ $c['op'] ?? '' }}">
                                            @foreach($opsByType[$c['type'] ?? 'page_time'] ?? ['gte'] as $op)
                                                <option value="{{ $op }}" @selected(($c['op'] ?? '') === $op)>{{ $ops[$op] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="text" name="conditions[{{ $i }}][value]" class="form-control" maxlength="500" required value="{{ $c['value'] ?? '' }}">
                                    </div>
                                    <div class="col-md-1 text-end">
                                        <button type="button" class="btn btn-light t-remove" title="{{ __('helpdesklivechat::triggers.remove_condition') }}" aria-label="{{ __('helpdesklivechat::triggers.remove_condition') }}">
                                            <i class="fas fa-xmark"></i>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm mb-4" id="t-add">
                            <i class="fas fa-plus me-1"></i>{{ __('helpdesklivechat::triggers.add_condition') }}
                        </button>

                        <h6 class="fw-semibold mb-3">{{ __('helpdesklivechat::triggers.section_action') }}</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="t-action">{{ __('helpdesklivechat::triggers.field_action') }}</label>
                                <select name="action" id="t-action" class="form-select">
                                    @foreach(\Modules\HelpdeskLivechat\Models\WidgetTrigger::ACTIONS as $action)
                                        <option value="{{ $action }}" @selected(old('action', $trigger?->action ?? 'message') === $action)>{{ __('helpdesklivechat::triggers.action_'.$action) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="t-frequency">{{ __('helpdesklivechat::triggers.field_frequency') }}</label>
                                <select name="frequency" id="t-frequency" class="form-select">
                                    @foreach(\Modules\HelpdeskLivechat\Models\WidgetTrigger::FREQUENCIES as $f)
                                        <option value="{{ $f }}" @selected(old('frequency', $trigger?->frequency ?? 'once_visitor') === $f)>{{ __('helpdesklivechat::triggers.frequency_'.$f) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12" id="t-message-wrap">
                                <label class="form-label" for="t-message">{{ __('helpdesklivechat::triggers.field_message') }}</label>
                                <textarea name="message" id="t-message" class="form-control" rows="3" maxlength="500">{{ old('message', $trigger?->message) }}</textarea>
                                <small class="text-muted">{{ __('helpdesklivechat::triggers.field_message_help') }}</small>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">{{ __('helpdesklivechat::triggers.save') }}</button>
                        <a href="{{ route('settings.helpdesk-livechat.triggers.index') }}" class="btn btn-light w-100">{{ __('helpdesklivechat::triggers.cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">{{ __('helpdesklivechat::triggers.help_title') }}</h6>
                    <p class="card-text text-muted">{{ __('helpdesklivechat::triggers.help_body') }}</p>
                    <p class="card-text text-muted small mb-0">{{ __('helpdesklivechat::triggers.help_types') }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="{{ asset('modules/helpdesklivechat/js/widget-triggers-admin.js') }}?v={{ @filemtime(public_path('modules/helpdesklivechat/js/widget-triggers-admin.js')) }}"></script>
@endpush

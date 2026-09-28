@extends('layouts.theme')

@php
    $isEdit = $block->exists;
    $title = $isEdit ? __('helpdeskaiprompts::ai-prompts.edit_block_title') : __('helpdeskaiprompts::ai-prompts.new_block_title');
@endphp

@section('title', $title)

@section('content')
    @include('core::components.card', ['title' => $title])

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ $isEdit ? route('helpdesk-ai-prompts.blocks.update', $block) : route('helpdesk-ai-prompts.blocks.store') }}"
                      method="POST" id="block-form">
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

                        <h6 class="fw-semibold mb-3">{{ __('helpdeskaiprompts::ai-prompts.block_section_basic') }}</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label" for="b-key">{{ __('helpdeskaiprompts::ai-prompts.field_block_key') }} *</label>
                                <input type="text" name="key" id="b-key" class="form-control @error('key') is-invalid @enderror"
                                       maxlength="64" required value="{{ old('key', $block->key) }}" {{ $isEdit ? 'readonly' : '' }}>
                                @error('key') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="b-kind">{{ __('helpdeskaiprompts::ai-prompts.field_block_kind') }} *</label>
                                <select name="kind" id="b-kind" class="form-select" required>
                                    <option value="base" @selected(old('kind', $block->kind) === 'base')>{{ __('helpdeskaiprompts::ai-prompts.block_kind_base') }}</option>
                                    <option value="knowledge" @selected(old('kind', $block->kind ?: 'knowledge') === 'knowledge')>{{ __('helpdeskaiprompts::ai-prompts.block_kind_knowledge') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="b-name">{{ __('helpdeskaiprompts::ai-prompts.field_block_name') }} *</label>
                                <input type="text" name="name" id="b-name" class="form-control @error('name') is-invalid @enderror"
                                       maxlength="255" required value="{{ old('name', $block->name) }}">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="b-channel">{{ __('helpdeskaiprompts::ai-prompts.field_block_channel') }}</label>
                                <select name="channel" id="b-channel" class="form-select">
                                    <option value="">{{ __('helpdeskaiprompts::ai-prompts.channel_global') }}</option>
                                    @foreach($channels as $channel)
                                        <option value="{{ $channel }}" @selected(old('channel', $block->channel) === $channel)>{{ __('helpdeskaiprompts::ai-prompts.channels.'.$channel) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="b-locale">{{ __('helpdeskaiprompts::ai-prompts.field_block_locale') }}</label>
                                <input type="text" name="locale" id="b-locale" class="form-control" maxlength="5" value="{{ old('locale', $block->locale) }}">
                                <small class="text-muted">{{ __('helpdeskaiprompts::ai-prompts.field_block_locale_help') }}</small>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="b-content">{{ __('helpdeskaiprompts::ai-prompts.field_block_content') }} *</label>
                                <textarea name="content" id="b-content" class="form-control @error('content') is-invalid @enderror"
                                          rows="12" maxlength="20000" required data-counter="#b-content-counter">{{ old('content', $block->content) }}</textarea>
                                <div class="d-flex justify-content-end">
                                    <small class="text-muted"><span id="b-content-counter">0</span>/20000</small>
                                </div>
                                @error('content') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="is_active" value="0">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="b-active" @checked(old('is_active', $block->is_active ?? true))>
                                    <label class="form-check-label" for="b-active">{{ __('helpdeskaiprompts::ai-prompts.field_block_active') }}</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer d-flex flex-column gap-2">
                        <button type="submit" class="btn btn-primary w-100">{{ __('helpdeskaiprompts::ai-prompts.save') }}</button>
                        @if($isEdit)
                            <a href="{{ route('helpdesk-ai-prompts.blocks.history', $block) }}" class="btn btn-light w-100">{{ __('helpdeskaiprompts::ai-prompts.action_history') }}</a>
                        @endif
                        <a href="{{ route('helpdesk-ai-prompts.index', ['tab' => $block->kind === 'base' ? 'prompt-base' : 'conocimiento']) }}" class="btn btn-light w-100">{{ __('helpdeskaiprompts::ai-prompts.cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">{{ __('helpdeskaiprompts::ai-prompts.help_block_title') }}</h6>
                    <p class="card-text text-muted small">{{ __('helpdeskaiprompts::ai-prompts.help_block_body') }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('modules/helpdeskaiprompts/js/ai-prompts-block-form.js') }}?v={{ @filemtime(public_path('modules/helpdeskaiprompts/js/ai-prompts-block-form.js')) }}"></script>
@endpush

@extends('layouts.theme')

@section('title', trans('captcha::captcha.settings.title'))

@section('content')

    @include('core::components.card', ['title' => trans('captcha::captcha.settings.title')])

    @include('core::components.alerts')

    <div class="widget-content searchable-container list">

        <div class="row g-4 align-items-start">

            {{-- Form (left) --}}
            <div class="col-lg-8">
                <form action="{{ route('captcha.settings.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card">

                        {{-- Service status --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">@lang('captcha::captcha.settings.service_status')</h6>
                            <p class="text-muted mb-3">@lang('captcha::captcha.settings.service_status_description')</p>

                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="enable_captcha"
                                       name="enable_captcha" value="1"
                                       {{ $enable_captcha ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="enable_captcha">
                                    @lang('captcha::captcha.settings.enable_recaptcha')
                                </label>
                            </div>
                            <small class="text-muted d-block mb-3">@lang('captcha::captcha.settings.enable_recaptcha_description')</small>

                            <div class="alert alert-info border-0 mb-0">
                                <small>
                                    <strong>@lang('captcha::captcha.settings.information')</strong>
                                    <a href="https://www.google.com/recaptcha/admin/create" target="_blank" class="fw-semibold">@lang('captcha::captcha.settings.get_keys')</a>
                                </small>
                            </div>
                        </div>

                        <div id="recaptcha-settings" class="{{ $enable_captcha ? '' : 'd-none' }}">

                            <hr class="my-0">

                            {{-- Verification type --}}
                            <div class="card-body">
                                <h6 class="fw-bold text-dark mb-1">@lang('captcha::captcha.settings.verification_type')</h6>
                                <p class="text-muted mb-3">@lang('captcha::captcha.settings.verification_type_description')</p>

                                <div class="alert alert-warning border-0 mb-3 py-2">
                                    <small>
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        @lang('captcha::captcha.settings.incorrect_config_warning')
                                    </small>
                                </div>

                                <label for="captcha_type" class="form-label fw-semibold">@lang('captcha::captcha.settings.version')</label>
                                <select class="form-select select2" id="captcha_type" name="captcha_type">
                                    <option value="v2" {{ $captcha_type === 'v2' ? 'selected' : '' }}>@lang('captcha::captcha.settings.v2_description')</option>
                                    <option value="v3" {{ $captcha_type === 'v3' ? 'selected' : '' }}>@lang('captcha::captcha.settings.v3_description')</option>
                                </select>
                                <small class="text-muted d-block mt-1">@lang('captcha::captcha.settings.v2_help')</small>

                                <div class="mt-3" id="v3-score-setting" style="display: {{ $captcha_type === 'v3' ? 'block' : 'none' }}">
                                    <label for="captcha_score" class="form-label fw-semibold">@lang('captcha::captcha.settings.score_threshold')</label>
                                    <input type="number" step="0.1" min="0" max="1"
                                           class="form-control @error('captcha_score') is-invalid @enderror"
                                           id="captcha_score" name="captcha_score"
                                           value="{{ $captcha_score }}">
                                    <small class="text-muted d-block mt-1">@lang('captcha::captcha.settings.score_threshold_help')</small>
                                    @error('captcha_score')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <hr class="my-0">

                            {{-- Credentials --}}
                            <div class="card-body">
                                <h6 class="fw-bold text-dark mb-1">@lang('captcha::captcha.settings.credentials')</h6>
                                <p class="text-muted mb-3">@lang('captcha::captcha.settings.credentials_description')</p>

                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="captcha_site_key" class="form-label fw-semibold">
                                            @lang('captcha::captcha.settings.site_key') <span class="text-danger">*</span>
                                        </label>
                                        <input type="text"
                                               class="form-control @error('captcha_site_key') is-invalid @enderror"
                                               id="captcha_site_key" name="captcha_site_key"
                                               value="{{ $captcha_site_key }}"
                                               placeholder="@lang('captcha::captcha.settings.site_key_placeholder')">
                                        @error('captcha_site_key')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted d-block mt-1">@lang('captcha::captcha.settings.site_key_help')</small>
                                    </div>
                                    <div class="col-12">
                                        <label for="captcha_secret" class="form-label fw-semibold">
                                            @lang('captcha::captcha.settings.secret_key') <span class="text-danger">*</span>
                                        </label>
                                        <input type="password"
                                               class="form-control @error('captcha_secret') is-invalid @enderror"
                                               id="captcha_secret" name="captcha_secret"
                                               value=""
                                               placeholder="{{ $captcha_secret_is_set ? '••••••••••••••••••••••••• ('.__('captcha::captcha.settings.secret_key_keep_current').')' : __('captcha::captcha.settings.secret_key_placeholder') }}"
                                               autocomplete="off">
                                        @error('captcha_secret')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted d-block mt-1">
                                            @lang('captcha::captcha.settings.secret_key_help')
                                            @if($captcha_secret_is_set)
                                                @lang('captcha::captcha.settings.secret_key_keep_current')
                                            @endif
                                        </small>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary w-100">
                                @lang('captcha::captcha.settings.save')
                            </button>
                        </div>

                    </div>

                </form>
            </div>

            {{-- Info panel (right) --}}
            <div class="col-lg-4">

                <div class="card mb-3">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-bold">@lang('captcha::captcha.settings.how_to_get_keys')</h6>
                    </div>
                    <div class="card-body">
                        <ol class="text-muted ps-3 mb-0">
                            <li class="mb-2">
                                @lang('captcha::captcha.settings.step_1', ['link' => '<a href="https://www.google.com/recaptcha/admin/create" target="_blank" class="fw-semibold">Google reCAPTCHA Admin</a>'])
                            </li>
                            <li class="mb-2">
                                @lang('captcha::captcha.settings.step_2', ['domain' => request()->getHost()])
                            </li>
                            <li class="mb-2">
                                @lang('captcha::captcha.settings.step_3', ['type' => '<strong>v2</strong> o <strong>v3</strong>'])
                            </li>
                            <li>
                                @lang('captcha::captcha.settings.step_4')
                            </li>
                        </ol>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-bold">@lang('captcha::captcha.settings.v2_vs_v3')</h6>
                    </div>
                    <div class="card-body">
                        <h6 class="fw-semibold mb-2">@lang('captcha::captcha.settings.v2_checkbox')</h6>
                        <p class="text-muted mb-3">@lang('captcha::captcha.settings.v2_description_detail')</p>

                        <hr class="my-3">

                        <h6 class="fw-semibold mb-2">@lang('captcha::captcha.settings.v3_invisible')</h6>
                        <p class="text-muted mb-0">@lang('captcha::captcha.settings.v3_description_detail')</p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-bold">@lang('captcha::captcha.settings.security')</h6>
                    </div>
                    <div class="card-body">
                        <ul class="text-muted mb-0">
                            <li class="mb-2">@lang('captcha::captcha.settings.secret_encrypted')</li>
                            <li class="mb-2">@lang('captcha::captcha.settings.never_expose_secret')</li>
                            <li>@lang('captcha::captcha.settings.regenerate_keys')</li>
                        </ul>
                    </div>
                </div>

            </div>

        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('.select2').select2({ width: '100%' });

    $('#enable_captcha').on('change', function () {
        $('#recaptcha-settings').toggleClass('d-none', !$(this).is(':checked'));
    });

    $('#captcha_type').on('change', function () {
        $('#v3-score-setting').toggle($(this).val() === 'v3');
    });
});
</script>
@endpush

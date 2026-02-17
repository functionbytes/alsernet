@extends('layouts.theme')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ trans('captcha::captcha.settings.title') }}</h3>
                    <p class="card-subtitle">{!! trans('captcha::captcha.settings.description') !!}</p>
                </div>

                <form action="{{ route('captcha.settings.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-body">
                        {{-- Enable reCAPTCHA --}}
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    id="enable_captcha"
                                    name="enable_captcha"
                                    value="1"
                                    {{ $enable_captcha ? 'checked' : '' }}
                                >
                                <label class="form-check-label" for="enable_captcha">
                                    {{ trans('captcha::captcha.settings.enable_recaptcha') }}
                                </label>
                            </div>
                        </div>

                        {{-- reCAPTCHA Settings --}}
                        <div id="recaptcha-settings" style="{{ $enable_captcha ? '' : 'display: none;' }}">
                            {{-- Warning Alert --}}
                            <div class="alert alert-warning" role="alert">
                                <i class="fa fa-exclamation-triangle me-2"></i>
                                {{ trans('captcha::captcha.settings.recaptcha_warning') }}
                            </div>

                            {{-- reCAPTCHA Type --}}
                            <div class="mb-3">
                                <label class="form-label">{{ trans('captcha::captcha.settings.type') }}</label>
                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        type="radio"
                                        name="captcha_type"
                                        id="captcha_type_v2"
                                        value="v2"
                                        {{ $captcha_type === 'v2' ? 'checked' : '' }}
                                    >
                                    <label class="form-check-label" for="captcha_type_v2">
                                        {{ trans('captcha::captcha.settings.v2_description') }}
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        type="radio"
                                        name="captcha_type"
                                        id="captcha_type_v3"
                                        value="v3"
                                        {{ $captcha_type === 'v3' ? 'checked' : '' }}
                                    >
                                    <label class="form-check-label" for="captcha_type_v3">
                                        {{ trans('captcha::captcha.settings.v3_description') }}
                                    </label>
                                </div>
                            </div>

                            {{-- Site Key --}}
                            <div class="mb-3">
                                <label for="captcha_site_key" class="form-label">
                                    {{ trans('captcha::captcha.settings.site_key') }}
                                    <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="text"
                                    class="form-control @error('captcha_site_key') is-invalid @enderror"
                                    id="captcha_site_key"
                                    name="captcha_site_key"
                                    value="{{ $captcha_site_key }}"
                                    placeholder="{{ trans('captcha::captcha.settings.site_key_placeholder') }}"
                                >
                                @error('captcha_site_key')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Secret Key --}}
                            <div class="mb-3">
                                <label for="captcha_secret" class="form-label">
                                    {{ trans('captcha::captcha.settings.secret_key') }}
                                    <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="text"
                                    class="form-control @error('captcha_secret') is-invalid @enderror"
                                    id="captcha_secret"
                                    name="captcha_secret"
                                    value="{{ $captcha_secret }}"
                                    placeholder="{{ trans('captcha::captcha.settings.secret_key_placeholder') }}"
                                >
                                @error('captcha_secret')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Score (v3 only) --}}
                            <div class="mb-3" id="v3-score-setting" style="display: {{ $captcha_type === 'v3' ? 'block' : 'none' }}">
                                <label for="captcha_score" class="form-label">
                                    {{ trans('captcha::captcha.settings.score') }}
                                </label>
                                <input
                                    type="number"
                                    step="0.1"
                                    min="0"
                                    max="1"
                                    class="form-control @error('captcha_score') is-invalid @enderror"
                                    id="captcha_score"
                                    name="captcha_score"
                                    value="{{ $captcha_score }}"
                                >
                                <small class="form-text text-muted">
                                    {{ trans('captcha::captcha.settings.score_helper') }}
                                </small>
                                @error('captcha_score')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- Math CAPTCHA --}}
                        <h5 class="mb-3">{{ trans('captcha::captcha.settings.math_captcha') }}</h5>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    id="enable_math_captcha"
                                    name="enable_math_captcha"
                                    value="1"
                                    {{ $enable_math_captcha ? 'checked' : '' }}
                                >
                                <label class="form-check-label" for="enable_math_captcha">
                                    {{ trans('captcha::captcha.settings.enable_math_captcha') }}
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    id="enable_math_captcha_contact_form"
                                    name="enable_math_captcha_contact_form"
                                    value="1"
                                    {{ $enable_math_captcha_contact_form ? 'checked' : '' }}
                                >
                                <label class="form-check-label" for="enable_math_captcha_contact_form">
                                    {{ trans('captcha::captcha.settings.enable_for_contact_form') }}
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    id="enable_math_captcha_newsletter_form"
                                    name="enable_math_captcha_newsletter_form"
                                    value="1"
                                    {{ $enable_math_captcha_newsletter_form ? 'checked' : '' }}
                                >
                                <label class="form-check-label" for="enable_math_captcha_newsletter_form">
                                    {{ trans('captcha::captcha.settings.enable_for_newsletter_form') }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save me-2"></i>
                            {{ trans('captcha::captcha.settings.save_settings') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $('#enable_captcha').on('change', function() {
        $('#recaptcha-settings').toggle($(this).is(':checked'));
    });

    $('input[name="captcha_type"]').on('change', function() {
        $('#v3-score-setting').toggle($(this).val() === 'v3');
    });
</script>
@endpush
@endsection

@extends('layouts.theme')

@section('title', __('helpdesktranslate::messages.settings.page_title'))

@push('css')
<link rel="stylesheet" href="{{ asset('vendor/helpdesktranslate/settings.css') }}?v={{ @filemtime(public_path('vendor/helpdesktranslate/settings.css')) }}">
@endpush

@section('content')

    @include('core::components.alerts')

    <div class="card">
        <div class="card-header p-4 border-bottom border-light">
            <div class="d-flex align-items-start gap-3">
                <div class="ht-settings-icon">
                    <i class="fas fa-language"></i>
                </div>
                <div class="flex-grow-1">
                    <h5 class="mb-1 fw-bold">{{ __('helpdesktranslate::messages.settings.card_title') }}</h5>
                    <p class="small mb-0 text-muted">
                        {!! __('helpdesktranslate::messages.settings.card_description') !!}
                    </p>
                </div>
            </div>
        </div>

        {{-- Cache stats --}}
        <div class="card-body border-bottom">
            <h6 class="text-uppercase fw-bold text-muted mb-3 small">{{ __('helpdesktranslate::messages.settings.cache_section') }}</h6>
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="ht-stat-card">
                        <div class="ht-stat-label"><i class="fas fa-database me-1"></i> {{ __('helpdesktranslate::messages.settings.stat_cached') }}</div>
                        <div class="ht-stat-value">{{ number_format($stats['cached'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="ht-stat-card">
                        <div class="ht-stat-label"><i class="fas fa-bullseye me-1"></i> {{ __('helpdesktranslate::messages.settings.stat_hits') }}</div>
                        <div class="ht-stat-value">{{ number_format($stats['hits'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="ht-stat-card">
                        <div class="ht-stat-label"><i class="fas fa-text-slash me-1"></i> {{ __('helpdesktranslate::messages.settings.stat_chars_saved') }}</div>
                        <div class="ht-stat-value">{{ number_format($stats['chars_saved'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="ht-stat-card ht-stat-card-success">
                        <div class="ht-stat-label"><i class="fas fa-coins me-1"></i> {{ __('helpdesktranslate::messages.settings.stat_saving') }}</div>
                        <div class="ht-stat-value">€{{ number_format($stats['estimated_saving_eur'] ?? 0, 4) }}</div>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-end mt-3">
                <button type="button" class="btn btn-sm btn-outline-danger" id="ht-clear-cache">
                    <i class="fas fa-broom me-1"></i> {{ __('helpdesktranslate::messages.settings.btn_clear_cache') }}
                </button>
            </div>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('settings.helpdesk-translate.update') }}" id="ht-settings-form">
                @csrf
                @method('PUT')

                {{-- Proveedor + idioma destino --}}
                <h6 class="text-uppercase fw-bold text-muted mb-3 small">{{ __('helpdesktranslate::messages.settings.provider_section') }}</h6>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="provider" class="form-label">{{ __('helpdesktranslate::messages.settings.provider_label') }}</label>
                        <select id="provider" name="provider" class="form-select">
                            <option value="deepl" {{ ($backups['provider'] ?? 'deepl') === 'deepl' ? 'selected' : '' }}>{{ __('helpdesktranslate::messages.settings.provider_deepl') }}</option>
                            <option value="libretranslate" {{ ($backups['provider'] ?? 'deepl') === 'libretranslate' ? 'selected' : '' }}>{{ __('helpdesktranslate::messages.settings.provider_libre') }}</option>
                        </select>
                        <small class="text-muted">{{ __('helpdesktranslate::messages.settings.provider_help') }}</small>
                    </div>

                    <div class="col-md-6">
                        <label for="default_target" class="form-label">{{ __('helpdesktranslate::messages.settings.target_label') }}</label>
                        <select id="default_target" name="default_target" class="form-select">
                            @php $target = strtolower($backups['default_target'] ?? 'es'); @endphp
                            <option value="es" {{ $target === 'es' ? 'selected' : '' }}>{{ __('helpdesktranslate::messages.languages.es') }} (ES)</option>
                            <option value="en" {{ $target === 'en' ? 'selected' : '' }}>{{ __('helpdesktranslate::messages.languages.en') }} (EN)</option>
                            <option value="fr" {{ $target === 'fr' ? 'selected' : '' }}>{{ __('helpdesktranslate::messages.languages.fr') }} (FR)</option>
                            <option value="de" {{ $target === 'de' ? 'selected' : '' }}>{{ __('helpdesktranslate::messages.languages.de') }} (DE)</option>
                            <option value="pt" {{ $target === 'pt' ? 'selected' : '' }}>{{ __('helpdesktranslate::messages.languages.pt') }} (PT)</option>
                            <option value="it" {{ $target === 'it' ? 'selected' : '' }}>{{ __('helpdesktranslate::messages.languages.it') }} (IT)</option>
                        </select>
                        <small class="text-muted">{{ __('helpdesktranslate::messages.settings.target_help') }}</small>
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input type="hidden" name="auto_translate_incoming" value="0">
                            <input class="form-check-input" type="checkbox" id="auto_translate_incoming"
                                   name="auto_translate_incoming" value="1"
                                   {{ ! empty($backups['auto_translate_incoming']) ? 'checked' : '' }}>
                            <label class="form-check-label" for="auto_translate_incoming">
                                {{ __('helpdesktranslate::messages.settings.auto_incoming_label') }}
                            </label>
                        </div>
                        <small class="text-muted">
                            {!! __('helpdesktranslate::messages.settings.auto_incoming_help') !!}
                        </small>
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input type="hidden" name="auto_translate_outgoing" value="0">
                            <input class="form-check-input" type="checkbox" id="auto_translate_outgoing"
                                   name="auto_translate_outgoing" value="1"
                                   {{ ! empty($backups['auto_translate_outgoing']) ? 'checked' : '' }}>
                            <label class="form-check-label" for="auto_translate_outgoing">
                                {{ __('helpdesktranslate::messages.settings.auto_outgoing_label') }}
                            </label>
                        </div>
                        <small class="text-muted">
                            {{ __('helpdesktranslate::messages.settings.auto_outgoing_help') }}
                        </small>
                    </div>
                </div>

                {{-- LibreTranslate endpoint --}}
                <div id="ht-libre-section" class="ht-provider-section mb-4 {{ ($backups['provider'] ?? 'deepl') !== 'libretranslate' ? 'd-none' : '' }}">
                    <h6 class="text-uppercase fw-bold text-muted mb-3 small">{{ __('helpdesktranslate::messages.settings.libre_section') }}</h6>
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label for="libretranslate_endpoint" class="form-label">{{ __('helpdesktranslate::messages.settings.libre_endpoint') }}</label>
                            <input type="url"
                                   class="form-control"
                                   id="libretranslate_endpoint"
                                   name="libretranslate_endpoint"
                                   value="{{ $backups['libretranslate_endpoint'] ?? '' }}"
                                   placeholder="http://libretranslate:5000/translate">
                            <small class="text-muted">URL completa al endpoint <code>/translate</code> de tu instancia LibreTranslate.</small>
                        </div>
                        <div class="col-md-5">
                            <label for="libretranslate_api_key" class="form-label">{{ __('helpdesktranslate::messages.settings.libre_key_label') }}</label>
                            <input type="password"
                                   class="form-control"
                                   id="libretranslate_api_key"
                                   name="libretranslate_api_key"
                                   value=""
                                   placeholder="{{ ! empty($backups['has_libretranslate_key']) ? __('helpdesktranslate::messages.settings.libre_key_ph_saved') : __('helpdesktranslate::messages.settings.libre_key_none') }}"
                                   autocomplete="new-password">
                            <small class="text-muted">
                                @if(! empty($backups['has_libretranslate_key']))
                                    {{ __('helpdesktranslate::messages.settings.libre_key_saved') }}
                                @else
                                    {{ __('helpdesktranslate::messages.settings.libre_key_none') }}
                                @endif
                            </small>
                        </div>
                    </div>
                    <div class="alert alert-info mt-3 mb-0 small d-flex align-items-start gap-2">
                        <i class="fas fa-circle-info mt-1"></i>
                        <div>
                            {!! __('helpdesktranslate::messages.settings.libre_info') !!}
                        </div>
                    </div>
                </div>

                {{-- Credenciales DeepL --}}
                <div id="ht-deepl-section" class="ht-provider-section {{ ($backups['provider'] ?? 'deepl') !== 'deepl' ? 'd-none' : '' }}">
                <h6 class="text-uppercase fw-bold text-muted mb-3 small">{{ __('helpdesktranslate::messages.settings.deepl_section') }}</h6>

                <div class="row g-3 mb-4">
                    <div class="col-md-7">
                        <label for="deepl_key" class="form-label">{{ __('helpdesktranslate::messages.settings.deepl_key_label') }}</label>
                        <div class="input-group">
                            <input type="password"
                                   class="form-control"
                                   id="deepl_key"
                                   name="deepl_key"
                                   value=""
                                   placeholder="{{ ! empty($backups['has_deepl_key']) ? __('helpdesktranslate::messages.settings.deepl_key_ph_saved') : 'DeepL-Auth-Key …' }}"
                                   autocomplete="new-password">
                            <button type="button" class="btn btn-outline-secondary" id="ht-toggle-key" title="{{ __('helpdesktranslate::messages.settings.deepl_key_label') }}">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted">
                            @if(! empty($backups['has_env_key']))
                                {!! __('helpdesktranslate::messages.settings.deepl_key_env') !!}
                            @elseif(! empty($backups['has_deepl_key']))
                                {{ __('helpdesktranslate::messages.settings.deepl_key_saved') }}
                            @else
                                {!! __('helpdesktranslate::messages.settings.deepl_key_none') !!}
                            @endif
                        </small>
                    </div>

                    <div class="col-md-5">
                        <label for="deepl_url" class="form-label">{{ __('helpdesktranslate::messages.settings.deepl_url_label') }}</label>
                        <select id="deepl_url" name="deepl_url" class="form-select">
                            @php $url = $backups['deepl_url'] ?? 'https://api-free.deepl.com'; @endphp
                            <option value="https://api-free.deepl.com" {{ $url === 'https://api-free.deepl.com' ? 'selected' : '' }}>
                                {{ __('helpdesktranslate::messages.settings.deepl_url_free') }}
                            </option>
                            <option value="https://api.deepl.com" {{ $url === 'https://api.deepl.com' ? 'selected' : '' }}>
                                {{ __('helpdesktranslate::messages.settings.deepl_url_pro') }}
                            </option>
                        </select>
                        <small class="text-muted">{{ __('helpdesktranslate::messages.settings.deepl_url_help') }}</small>
                    </div>
                </div>
                </div>{{-- /#ht-deepl-section --}}

                {{-- Acciones --}}
                <div class="d-flex flex-wrap gap-2 align-items-center pt-3 border-top">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> {{ __('helpdesktranslate::messages.settings.btn_save') }}
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="ht-test-connection">
                        <i class="fas fa-plug me-1"></i> {{ __('helpdesktranslate::messages.settings.btn_test') }}
                    </button>
                    <span id="ht-test-result" class="ms-2 small"></span>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(function () {
    // Toggle visibility of API key
    $('#ht-toggle-key').on('click', function () {
        var input = $('#deepl_key');
        var isPassword = input.attr('type') === 'password';
        input.attr('type', isPassword ? 'text' : 'password');
        $(this).find('i').toggleClass('fa-eye fa-eye-slash');
    });

    // Show only the section matching the selected provider
    $('#provider').on('change', function () {
        var p = $(this).val();
        $('#ht-deepl-section').toggleClass('d-none', p !== 'deepl');
        $('#ht-libre-section').toggleClass('d-none', p !== 'libretranslate');
    });

    // Clear cache button
    $('#ht-clear-cache').on('click', function () {
        if (!confirm('{{ __('helpdesktranslate::messages.settings.js_confirm_clear') }}')) {
            return;
        }
        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> {{ __('helpdesktranslate::messages.settings.js_clearing') }}');
        $.ajax({
            url: "{{ route('settings.helpdesk-translate.cache.clear') }}",
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (resp) {
                if (window.toastr) {
                    toastr.success(resp.message || '{{ __('helpdesktranslate::messages.success.cache_cleared', ['count' => '']) }}');
                }
                setTimeout(function () { window.location.reload(); }, 800);
            },
            error: function () {
                if (window.toastr) {
                    toastr.error('{{ __('helpdesktranslate::messages.errors.cache_clear_failed') }}');
                }
                $btn.prop('disabled', false).html('<i class="fas fa-broom me-1"></i> {{ __('helpdesktranslate::messages.settings.btn_clear_cache') }}');
            }
        });
    });

    // Test DeepL connection
    $('#ht-test-connection').on('click', function () {
        var $btn = $(this);
        var $result = $('#ht-test-result');

        $btn.prop('disabled', true);
        $result.removeClass('text-success text-danger').addClass('text-muted')
            .html('<i class="fas fa-spinner fa-spin"></i> {{ __('helpdesktranslate::messages.settings.js_testing') }}');

        $.ajax({
            url: "{{ route('settings.helpdesk-translate.test') }}",
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (resp) {
                var msg = resp.message || '{{ __('helpdesktranslate::messages.settings.js_test_ok_default') }}';
                if (resp.usage && resp.usage.character_count !== null) {
                    msg += ' — {{ __('helpdesktranslate::messages.settings.js_test_usage') }}'
                        .replace(':count', resp.usage.character_count.toLocaleString())
                        .replace(':limit', (resp.usage.character_limit || '∞').toLocaleString());
                }
                $result.removeClass('text-muted text-danger').addClass('text-success')
                    .html('<i class="fas fa-check-circle"></i> ' + msg);
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || '{{ __('helpdesktranslate::messages.settings.js_error_default') }}';
                $result.removeClass('text-muted text-success').addClass('text-danger')
                    .html('<i class="fas fa-circle-xmark"></i> ' + msg);
            },
            complete: function () {
                $btn.prop('disabled', false);
            }
        });
    });
});
</script>
@endpush

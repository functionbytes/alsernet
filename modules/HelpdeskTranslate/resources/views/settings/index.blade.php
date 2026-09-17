@extends('layouts.theme')

@section('title', __('helpdesktranslate::messages.settings.page_title'))

@push('css')
<link rel="stylesheet" href="{{ asset('vendor/helpdesktranslate/settings.css') }}?v={{ @filemtime(public_path('vendor/helpdesktranslate/settings.css')) }}">
@endpush

@section('page_header')
    @include('core::components.card', ['title' => __('helpdesktranslate::messages.settings.page_title')])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="row g-3">

    {{-- Configuracion --}}
    <div class="col-12 col-lg-8">
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
            <h6 class="fw-semibold mb-1">{{ __('helpdesktranslate::messages.settings.cache_section') }}</h6>
                <p class="text-muted small mb-3">Frases ya traducidas que se reutilizan sin volver a llamar al proveedor</p>
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

        {{-- Usage / consumption report --}}
        <div class="card-body border-bottom">
            <h6 class="fw-semibold mb-1">{{ __('helpdesktranslate::messages.settings.usage_section') }}</h6>
                <p class="text-muted small mb-3">Caracteres y llamadas consumidas en el rango elegido</p>

            <div class="row g-2 align-items-end mb-3">
                <div class="col-auto">
                    <label for="ht-usage-from" class="form-label small mb-1">{{ __('helpdesktranslate::messages.settings.usage_from_label') }}</label>
                    <input type="date" class="form-control form-control-sm" id="ht-usage-from">
                </div>
                <div class="col-auto">
                    <label for="ht-usage-to" class="form-label small mb-1">{{ __('helpdesktranslate::messages.settings.usage_to_label') }}</label>
                    <input type="date" class="form-control form-control-sm" id="ht-usage-to">
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="ht-usage-filter">
                        <i class="fas fa-filter me-1"></i> {{ __('helpdesktranslate::messages.settings.usage_btn_filter') }}
                    </button>
                </div>
                <div class="col-auto ms-auto">
                    <a href="#" id="ht-usage-export" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-download me-1"></i> {{ __('helpdesktranslate::messages.settings.usage_btn_export') }}
                    </a>
                </div>
            </div>

            <div id="ht-usage-loading" class="text-muted small">
                <i class="fas fa-spinner fa-spin"></i> {{ __('helpdesktranslate::messages.settings.usage_js_loading') }}
            </div>

            <div id="ht-usage-content" class="d-none">
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <div class="ht-stat-card">
                            <div class="ht-stat-label"><i class="fas fa-font me-1"></i> {{ __('helpdesktranslate::messages.settings.usage_stat_characters') }}</div>
                            <div class="ht-stat-value" id="ht-usage-characters">0</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="ht-stat-card">
                            <div class="ht-stat-label"><i class="fas fa-arrow-right-arrow-left me-1"></i> {{ __('helpdesktranslate::messages.settings.usage_stat_calls') }}</div>
                            <div class="ht-stat-value" id="ht-usage-calls">0</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="ht-stat-card">
                            <div class="ht-stat-label"><i class="fas fa-triangle-exclamation me-1"></i> {{ __('helpdesktranslate::messages.settings.usage_stat_failed') }}</div>
                            <div class="ht-stat-value" id="ht-usage-failed">0</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="ht-stat-card ht-stat-card-success">
                            <div class="ht-stat-label"><i class="fas fa-coins me-1"></i> {{ __('helpdesktranslate::messages.settings.usage_stat_cost') }}</div>
                            <div class="ht-stat-value" id="ht-usage-cost">€0.00</div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <div class="ht-stat-card ht-stat-card-success">
                            <div class="ht-stat-label"><i class="fas fa-gauge me-1"></i> {{ __('helpdesktranslate::messages.settings.usage_stat_quota') }}</div>
                            <div class="ht-stat-value" id="ht-usage-quota">—</div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <h6 class="small fw-bold text-muted mb-2">{{ __('helpdesktranslate::messages.settings.usage_trend') }}</h6>
                    <div class="ht-usage-chart-wrap">
                        <canvas id="ht-usage-chart"></canvas>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <h6 class="small fw-bold text-muted mb-2">{{ __('helpdesktranslate::messages.settings.usage_by_feature') }}</h6>
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th class="text-end">{{ __('helpdesktranslate::messages.settings.usage_col_characters') }}</th>
                                    <th class="text-end">{{ __('helpdesktranslate::messages.settings.usage_col_calls') }}</th>
                                </tr>
                            </thead>
                            <tbody id="ht-usage-by-feature"></tbody>
                        </table>
                    </div>
                    <div class="col-md-4">
                        <h6 class="small fw-bold text-muted mb-2">{{ __('helpdesktranslate::messages.settings.usage_by_operation') }}</h6>
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th class="text-end">{{ __('helpdesktranslate::messages.settings.usage_col_characters') }}</th>
                                    <th class="text-end">{{ __('helpdesktranslate::messages.settings.usage_col_calls') }}</th>
                                </tr>
                            </thead>
                            <tbody id="ht-usage-by-operation"></tbody>
                        </table>
                    </div>
                    <div class="col-md-4">
                        <h6 class="small fw-bold text-muted mb-2">{{ __('helpdesktranslate::messages.settings.usage_by_provider') }}</h6>
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th class="text-end">{{ __('helpdesktranslate::messages.settings.usage_col_characters') }}</th>
                                    <th class="text-end">{{ __('helpdesktranslate::messages.settings.usage_col_calls') }}</th>
                                </tr>
                            </thead>
                            <tbody id="ht-usage-by-provider"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="ht-usage-empty" class="text-muted small d-none">
                {{ __('helpdesktranslate::messages.settings.usage_empty') }}
            </div>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('settings.helpdesk-translate.update') }}" id="ht-settings-form">
                @csrf
                @method('PUT')

                {{-- Proveedor + idioma destino --}}
                <h6 class="fw-semibold mb-1">{{ __('helpdesktranslate::messages.settings.provider_section') }}</h6>
                <p class="text-muted small mb-3">Que motor traduce y como se comporta con los mensajes</p>

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

                    <div class="col-md-6">
                        <label for="auto_translate_incoming" class="form-label">{{ __('helpdesktranslate::messages.settings.auto_incoming_label') }}</label>
                        <select class="form-select" id="auto_translate_incoming" name="auto_translate_incoming">
                            <option value="1" @selected(! empty($backups['auto_translate_incoming'])) >Activado</option>
                            <option value="0" @selected(empty($backups['auto_translate_incoming'])) >Desactivado</option>
                        </select>
                        <small class="text-muted">{!! __('helpdesktranslate::messages.settings.auto_incoming_help') !!}</small>
                    </div>

                    <div class="col-md-6">
                        <label for="auto_translate_outgoing" class="form-label">{{ __('helpdesktranslate::messages.settings.auto_outgoing_label') }}</label>
                        <select class="form-select" id="auto_translate_outgoing" name="auto_translate_outgoing">
                            <option value="1" @selected(! empty($backups['auto_translate_outgoing'])) >Activado</option>
                            <option value="0" @selected(empty($backups['auto_translate_outgoing'])) >Desactivado</option>
                        </select>
                        <small class="text-muted">{{ __('helpdesktranslate::messages.settings.auto_outgoing_help') }}</small>
                    </div>
                </div>

                {{-- LibreTranslate endpoint --}}
                <div id="ht-libre-section" class="ht-provider-section mb-4 {{ ($backups['provider'] ?? 'deepl') !== 'libretranslate' ? 'd-none' : '' }}">
                    <h6 class="fw-semibold mb-1">{{ __('helpdesktranslate::messages.settings.libre_section') }}</h6>
                <p class="text-muted small mb-3">Servidor propio de LibreTranslate</p>
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
                            @if(! empty($backups['has_libretranslate_key']))
                                <div class="form-check mt-2">
                                    <input type="checkbox" class="form-check-input" id="remove_libretranslate_api_key" name="remove_libretranslate_api_key" value="1">
                                    <label class="form-check-label small" for="remove_libretranslate_api_key">
                                        {{ __('helpdesktranslate::messages.settings.remove_key_label') }}
                                    </label>
                                </div>
                            @endif
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
                <h6 class="fw-semibold mb-1">{{ __('helpdesktranslate::messages.settings.deepl_section') }}</h6>
                <p class="text-muted small mb-3">Credenciales de acceso a la API de DeepL</p>

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
                        @if(! empty($backups['has_deepl_key']))
                            <div class="form-check mt-2">
                                <input type="checkbox" class="form-check-input" id="remove_deepl_key" name="remove_deepl_key" value="1">
                                <label class="form-check-label small" for="remove_deepl_key">
                                    {{ __('helpdesktranslate::messages.settings.remove_key_label') }}
                                </label>
                            </div>
                        @endif
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
                <div class="pt-3 border-top">
                    <span id="ht-test-result" class="d-block small mb-2"></span>
                    <button type="submit" class="btn btn-primary w-100 mb-1">
                        {{ __('helpdesktranslate::messages.settings.btn_save') }}
                    </button>
                    <button type="button" class="btn btn-light w-100" id="ht-test-connection">
                        {{ __('helpdesktranslate::messages.settings.btn_test') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
    </div>

    {{-- Panel de instrucciones --}}
    <div class="col-12 col-lg-4">
        <div class="card mb-3">
            <div class="card-header border-bottom">
                <h6 class="mb-0 fw-bold">Sobre esta configuracion</h6>
            </div>
            <div class="card-body">
                <p class="card-text text-muted mb-0">
                    Estos ajustes gobiernan el boton <strong>Traducir</strong> del chat y la traduccion
                    automatica de los mensajes que entran y salen. Se aplican a todos los agentes.
                </p>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header border-bottom">
                <h6 class="mb-0 fw-bold">Que proveedor elegir</h6>
            </div>
            <div class="card-body">
                <ul class="text-muted mb-0">
                    <li class="mb-2"><span class="fw-semibold">DeepL:</span> mejor calidad, se paga por caracter traducido</li>
                    <li class="mb-0"><span class="fw-semibold">LibreTranslate:</span> corre en tu propio Docker, sin coste por uso</li>
                </ul>
            </div>
        </div>
        <div class="card">
            <div class="card-header border-bottom">
                <h6 class="mb-0 fw-bold">Buenas practicas</h6>
            </div>
            <div class="card-body">
                <ul class="text-muted mb-0">
                    <li class="mb-2">La cache evita pagar dos veces por la misma frase: no la limpies sin motivo</li>
                    <li class="mb-2">Usa "Probar conexion" tras cambiar la API key</li>
                    <li class="mb-2">La traduccion de salida necesita que se haya detectado el idioma del visitante</li>
                    <li class="mb-0">El consumo se puede exportar a CSV para contabilidad</li>
                </ul>
            </div>
        </div>
    </div>

    </div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
{{-- Bootstrap minimo de datos (cadenas traducidas + URLs de route()) que
     settings.js no puede resolver por su cuenta — toda la logica vive ahi,
     mismo patron que window.HelpdeskTranslateI18n en
     partials/translate-panel.blade.php. --}}
@php
    $htSettingsI18n = [
        'confirmClear' => __('helpdesktranslate::messages.settings.js_confirm_clear'),
        'clearing' => __('helpdesktranslate::messages.settings.js_clearing'),
        'cacheCleared' => __('helpdesktranslate::messages.success.cache_cleared', ['count' => '']),
        'cacheClearFailed' => __('helpdesktranslate::messages.errors.cache_clear_failed'),
        'btnClearCache' => __('helpdesktranslate::messages.settings.btn_clear_cache'),
        'testing' => __('helpdesktranslate::messages.settings.js_testing'),
        'testOkDefault' => __('helpdesktranslate::messages.settings.js_test_ok_default'),
        'testUsage' => __('helpdesktranslate::messages.settings.js_test_usage'),
        'testErrorDefault' => __('helpdesktranslate::messages.settings.js_error_default'),
        'usageFeatureManual' => __('helpdesktranslate::messages.settings.usage_feature_manual'),
        'usageFeatureAutoIncoming' => __('helpdesktranslate::messages.settings.usage_feature_auto_incoming'),
        'usageFeatureAutoOutgoing' => __('helpdesktranslate::messages.settings.usage_feature_auto_outgoing'),
        'usageFeatureOther' => __('helpdesktranslate::messages.settings.usage_feature_other'),
        'usageOperationTranslate' => __('helpdesktranslate::messages.settings.usage_operation_translate'),
        'usageOperationDetect' => __('helpdesktranslate::messages.settings.usage_operation_detect'),
        'usageQuotaUnavailable' => __('helpdesktranslate::messages.settings.usage_quota_unavailable'),
        'usageStatCharacters' => __('helpdesktranslate::messages.settings.usage_stat_characters'),
        'usageJsError' => __('helpdesktranslate::messages.settings.usage_js_error'),
    ];
    $htSettingsRoutes = [
        'cacheClear' => route('settings.helpdesk-translate.cache.clear'),
        'test' => route('settings.helpdesk-translate.test'),
        'usage' => route('settings.helpdesk-translate.usage'),
        'usageExport' => route('settings.helpdesk-translate.usage.export'),
    ];
@endphp
<script>window.HelpdeskTranslateSettings = { i18n: @json($htSettingsI18n), routes: @json($htSettingsRoutes) };</script>
<script src="{{ asset('vendor/helpdesktranslate/settings.js') }}?v={{ @filemtime(public_path('vendor/helpdesktranslate/settings.js')) }}"></script>
@endpush

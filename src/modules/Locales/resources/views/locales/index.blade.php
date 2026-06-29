@extends('layouts.theme')

@section('title', 'Idiomas')

@section('page_header')
    @include('core::components.card', ['title' => 'Idiomas'])
@endsection

@section('content')

    <div class="widget-content">

        @include('core::components.alerts')

        <div class="card">
            <div class="card-body">

                {{-- Tabs nav --}}
                <ul class="nav nav-tabs mb-4" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-detalle-trigger"
                                data-bs-toggle="tab" data-bs-target="#tab-detalle"
                                type="button" role="tab">
                            Detalle
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-config-trigger"
                                data-bs-toggle="tab" data-bs-target="#tab-config"
                                type="button" role="tab">
                            Configuraciones
                        </button>
                    </li>
                </ul>

                <div class="tab-content">

                    {{-- ==================== TAB 1: Detalle ==================== --}}
                    <div class="tab-pane fade show active" id="tab-detalle" role="tabpanel">
                        <div class="row g-4">

                            {{-- LEFT: Add locale form --}}
                            <div class="col-md-5">
                                <form action="{{ route('locales.store') }}" method="POST">
                                    @csrf
                                    <h6 class="fw-bold mb-3 border-bottom pb-2">Agregar idioma</h6>

                                    {{-- Preset picker --}}
                                    <div class="mb-3">
                                        <label for="preset-language" class="form-label">Elige un idioma</label>
                                        <select id="preset-language" class="form-select">
                                            <option value="">Seleccionar un idioma...</option>
                                            @foreach ($presetLanguages as $key => $lang)
                                                <option value="{{ $key }}">{{ $lang['flag'] }} {{ $lang['name'] }} ({{ $lang['native_name'] }})</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Name --}}
                                    <div class="mb-3">
                                        <label for="field-name" class="form-label">Nombre del idioma</label>
                                        <input type="text" id="field-name" name="name"
                                               class="form-control @error('name') is-invalid @enderror"
                                               value="{{ old('name') }}" placeholder="Inglés">
                                        <div class="form-text">El nombre es cómo se muestra en tu sitio (por ejemplo: Inglés).</div>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- Code select --}}
                                    <div class="mb-3">
                                        <label for="field-code" class="form-label">Localidad</label>
                                        <select id="field-code" name="code"
                                                class="form-select @error('code') is-invalid @enderror">
                                            <option value="">Seleccionar...</option>
                                            @foreach (['ar', 'zh', 'nl', 'en', 'fr', 'de', 'it', 'ja', 'ko', 'pl', 'pt', 'ru', 'es', 'tr'] as $c)
                                                <option value="{{ $c }}" {{ old('code') == $c ? 'selected' : '' }}>{{ $c }}</option>
                                            @endforeach
                                        </select>
                                        <div class="form-text">Localidad de Laravel para el idioma (por ejemplo: <code>en</code>).</div>
                                        @error('code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- Language code --}}
                                    <div class="mb-3">
                                        <label for="field-language-code" class="form-label">Código de idioma</label>
                                        <input type="text" id="field-language-code" name="language_code"
                                               class="form-control @error('language_code') is-invalid @enderror"
                                               value="{{ old('language_code') }}" placeholder="en">
                                        <div class="form-text">Código de idioma, preferiblemente ISO 639-1 de 2 letras (por ejemplo: en)</div>
                                        @error('language_code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- RTL radio --}}
                                    <div class="mb-3">
                                        <label class="form-label">Dirección del texto</label>
                                        <div class="d-flex gap-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="rtl"
                                                       id="rtl-ltr" value="0"
                                                       {{ old('rtl', '0') == '0' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="rtl-ltr">De izquierda a derecha</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="rtl"
                                                       id="rtl-rtl" value="1"
                                                       {{ old('rtl') == '1' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="rtl-rtl">De derecha a izquierda</label>
                                            </div>
                                        </div>
                                        <div class="form-text">Elige la dirección del texto para el idioma</div>
                                    </div>

                                    {{-- Flag select --}}
                                    <div class="mb-3">
                                        <label for="field-flag" class="form-label">Bandera</label>
                                        <select id="field-flag" name="flag"
                                                class="form-select @error('flag') is-invalid @enderror">
                                            <option value="">Sin bandera</option>
                                            <option value="🇦🇷" {{ old('flag') == '🇦🇷' ? 'selected' : '' }}>🇦🇷 Argentina</option>
                                            <option value="🇧🇷" {{ old('flag') == '🇧🇷' ? 'selected' : '' }}>🇧🇷 Brasil</option>
                                            <option value="🇨🇦" {{ old('flag') == '🇨🇦' ? 'selected' : '' }}>🇨🇦 Canadá</option>
                                            <option value="🇨🇱" {{ old('flag') == '🇨🇱' ? 'selected' : '' }}>🇨🇱 Chile</option>
                                            <option value="🇨🇳" {{ old('flag') == '🇨🇳' ? 'selected' : '' }}>🇨🇳 China</option>
                                            <option value="🇨🇴" {{ old('flag') == '🇨🇴' ? 'selected' : '' }}>🇨🇴 Colombia</option>
                                            <option value="🇩🇪" {{ old('flag') == '🇩🇪' ? 'selected' : '' }}>🇩🇪 Alemania</option>
                                            <option value="🇪🇸" {{ old('flag') == '🇪🇸' ? 'selected' : '' }}>🇪🇸 España</option>
                                            <option value="🇫🇷" {{ old('flag') == '🇫🇷' ? 'selected' : '' }}>🇫🇷 Francia</option>
                                            <option value="🇬🇧" {{ old('flag') == '🇬🇧' ? 'selected' : '' }}>🇬🇧 Reino Unido</option>
                                            <option value="🇮🇹" {{ old('flag') == '🇮🇹' ? 'selected' : '' }}>🇮🇹 Italia</option>
                                            <option value="🇯🇵" {{ old('flag') == '🇯🇵' ? 'selected' : '' }}>🇯🇵 Japón</option>
                                            <option value="🇰🇷" {{ old('flag') == '🇰🇷' ? 'selected' : '' }}>🇰🇷 Corea</option>
                                            <option value="🇲🇽" {{ old('flag') == '🇲🇽' ? 'selected' : '' }}>🇲🇽 México</option>
                                            <option value="🇳🇱" {{ old('flag') == '🇳🇱' ? 'selected' : '' }}>🇳🇱 Países Bajos</option>
                                            <option value="🇵🇪" {{ old('flag') == '🇵🇪' ? 'selected' : '' }}>🇵🇪 Perú</option>
                                            <option value="🇵🇱" {{ old('flag') == '🇵🇱' ? 'selected' : '' }}>🇵🇱 Polonia</option>
                                            <option value="🇵🇹" {{ old('flag') == '🇵🇹' ? 'selected' : '' }}>🇵🇹 Portugal</option>
                                            <option value="🇷🇺" {{ old('flag') == '🇷🇺' ? 'selected' : '' }}>🇷🇺 Rusia</option>
                                            <option value="🇸🇦" {{ old('flag') == '🇸🇦' ? 'selected' : '' }}>🇸🇦 Arabia Saudita</option>
                                            <option value="🇺🇸" {{ old('flag') == '🇺🇸' ? 'selected' : '' }}>🇺🇸 Estados Unidos</option>
                                            <option value="🇻🇪" {{ old('flag') == '🇻🇪' ? 'selected' : '' }}>🇻🇪 Venezuela</option>
                                        </select>
                                        <div class="form-text">Elige una bandera para el idioma.</div>
                                        @error('flag')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- Order --}}
                                    <div class="mb-4">
                                        <label for="field-order" class="form-label">Orden</label>
                                        <input type="number" id="field-order" name="order"
                                               class="form-control @error('order') is-invalid @enderror"
                                               value="{{ old('order', 0) }}" min="0">
                                        <div class="form-text">Posición del idioma en el selector de idiomas</div>
                                        @error('order')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <button type="submit" class="btn btn-danger">Agregar nuevo idioma</button>
                                </form>
                            </div>

                            {{-- RIGHT: Existing locales table --}}
                            <div class="col-md-7">
                                <h6 class="fw-bold mb-3 border-bottom pb-2">Idiomas registrados</h6>

                                @if ($locales->isEmpty())
                                    <p class="text-muted">No hay idiomas registrados</p>
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Nombre del idioma</th>
                                                    <th>Localidad</th>
                                                    <th>Código</th>
                                                    <th class="text-center">¿Es predeterminado?</th>
                                                    <th class="text-center">Orden</th>
                                                    <th class="text-center" width="60">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($locales as $locale)
                                                    <tr>
                                                        <td class="fw-semibold">{{ $locale->flag }} {{ $locale->name }}</td>
                                                        <td><code>{{ $locale->code }}</code></td>
                                                        <td><code>{{ $locale->language_code ?? $locale->code }}</code></td>
                                                        <td class="text-center">
                                                            @if ($locale->is_default)
                                                                <i class="fas fa-star text-warning" title="Predeterminado"></i>
                                                            @else
                                                                <span class="text-muted">—</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center text-muted">{{ $locale->order }}</td>
                                                        <td class="text-center">
                                                            <div class="dropdown">
                                                                <a href="#" class="text-muted" data-bs-toggle="dropdown"
                                                                   data-bs-auto-close="true" data-bs-boundary="viewport">
                                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                                </a>
                                                                <ul class="dropdown-menu dropdown-menu-end">
                                                                    <li>
                                                                        <a class="dropdown-item" href="{{ route('locales.edit', $locale) }}">Editar</a>
                                                                    </li>
                                                                    @if (!$locale->is_default)
                                                                        <li>
                                                                            <button type="button" class="dropdown-item btn-set-default"
                                                                                    data-url="{{ route('locales.default', $locale) }}"
                                                                                    data-name="{{ $locale->name }}">
                                                                                Establecer como predeterminado
                                                                            </button>
                                                                        </li>
                                                                    @endif
                                                                    <li><hr class="dropdown-divider"></li>
                                                                    <li>
                                                                        <button type="button"
                                                                                class="dropdown-item btn-delete-locale"
                                                                                data-url="{{ route('locales.destroy', $locale) }}"
                                                                                data-name="{{ $locale->name }}"
                                                                                {{ $locale->is_default ? 'disabled' : '' }}>
                                                                            Eliminar
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

                    {{-- ==================== TAB 2: Configuraciones ==================== --}}
                    <div class="tab-pane fade" id="tab-config" role="tabpanel">
                        <form action="{{ route('locales.config') }}" method="POST">
                            @csrf
                            @method('PATCH')

                            <h6 class="fw-bold mb-3 border-bottom pb-2">Opciones de idioma</h6>

                            {{-- Hide default from URL --}}
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="hide-default-url"
                                           name="hide_default_from_url" value="1"
                                           {{ ($settings['hide_default_from_url'] ?? '0') === '1' ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="hide-default-url">
                                        ¿Ocultar el idioma predeterminado del URL?
                                    </label>
                                </div>
                            </div>

                            {{-- Language display --}}
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Mostrar idioma</label>
                                <div class="d-flex flex-column gap-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="language_display"
                                               id="display-flags-names" value="flags_names"
                                               {{ ($settings['language_display'] ?? 'flags_names') === 'flags_names' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="display-flags-names">Mostrar todas las banderas y nombres</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="language_display"
                                               id="display-flag" value="flag"
                                               {{ ($settings['language_display'] ?? '') === 'flag' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="display-flag">Bandera solamente</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="language_display"
                                               id="display-name" value="name"
                                               {{ ($settings['language_display'] ?? '') === 'name' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="display-name">Nombre solamente</label>
                                    </div>
                                </div>
                            </div>

                            {{-- Selector type --}}
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Mostrar selector de idioma</label>
                                <div class="d-flex flex-column gap-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="selector_type"
                                               id="selector-dropdown" value="dropdown"
                                               {{ ($settings['selector_type'] ?? 'dropdown') === 'dropdown' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="selector-dropdown">Menú desplegable</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="selector_type"
                                               id="selector-list" value="list"
                                               {{ ($settings['selector_type'] ?? '') === 'list' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="selector-list">Lista</label>
                                    </div>
                                </div>
                            </div>

                            {{-- Active locales info --}}
                            <div class="alert alert-info mb-4">
                                {{ $locales->where('is_active', true)->count() }} idioma(s) están actualmente visibles.
                            </div>

                            {{-- Auto detect --}}
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="auto-detect"
                                           name="auto_detect" value="1"
                                           {{ ($settings['auto_detect'] ?? '0') === '1' ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="auto-detect">
                                        ¿Detectar automáticamente el idioma del usuario?
                                    </label>
                                </div>
                                <div class="form-text ms-4">Si está habilitado, el sistema intentará detectar el idioma del usuario según el idioma del navegador.</div>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Guardar ajustes
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </div>

    </div>

    {{-- Hidden delete form --}}
    <form id="delete-form" method="POST" style="display:none">
        @csrf
        @method('DELETE')
    </form>

@endsection

@push('scripts')
<script>
$(document).ready(function () {

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    // Restore active tab from URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('tab') === 'config') {
        $('#tab-config-trigger').tab('show');
    }

    // Preset language auto-fill
    const presets = @json($presetLanguages);

    $('#preset-language').on('change', function () {
        const key = $(this).val();
        if (!key || !presets[key]) { return; }

        const lang = presets[key];
        $('[name="name"]').val(lang.name);
        $('[name="language_code"]').val(lang.language_code);
        $('[name="code"]').val(lang.code);
        $('[name="flag"]').val(lang.flag);
        $(`[name="rtl"][value="${lang.rtl ? '1' : '0'}"]`).prop('checked', true);
    });

    // Set default locale
    $(document).on('click', '.btn-set-default', function () {
        const url  = $(this).data('url');
        const name = $(this).data('name');

        if (!confirm('¿Establecer "' + name + '" como predeterminado?')) { return; }

        $.post(url, { _token: $('meta[name="csrf-token"]').attr('content') }, function (res) {
            if (res.success) {
                toastr.success('Predeterminado actualizado.');
                setTimeout(() => location.reload(), 800);
            }
        }).fail(function () { toastr.error('Error.'); });
    });

    // Delete locale
    $(document).on('click', '.btn-delete-locale', function () {
        const url  = $(this).data('url');
        const name = $(this).data('name');

        if (!confirm('¿Eliminar el idioma "' + name + '"?')) { return; }

        $('#delete-form').attr('action', url).submit();
    });
});
</script>
@endpush

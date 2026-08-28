@extends('layouts.theme')

@section('title', $pageTitle)

@php
    // Cache-busting: sin esto el navegador puede servir copias viejas de
    // editor.css/settings.js tras un deploy hasta un hard-refresh manual.
    $giftmessageAssetVersion = fn (string $path) => file_exists(public_path($path)) ? filemtime(public_path($path)) : time();
@endphp

@push('styles')
<link rel="stylesheet" href="{{ asset('modules/giftmessage/css/editor.css') }}?v={{ $giftmessageAssetVersion('modules/giftmessage/css/editor.css') }}">
@if($fontFaceCss !== '')
    {{-- Las fuentes subidas tambien deben verse en el lienzo, no solo en el PDF. --}}
    <style>{!! $fontFaceCss !!}</style>
@endif
@endpush

@section('page_header')
    @include('core::components.card', ['title' => $pageTitle])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="card">
        <div class="card-body">

            {{-- Vista previa: texto de muestra que se refleja en vivo en ambos lienzos,
                 para ver ANTES de generar un PDF real si un mensaje largo o con emoji
                 se recorta o rompe mal la linea dentro de la caja. --}}
            <div class="mb-4">
                <h6 class="mb-1 fw-bold text-dark">Vista previa</h6>
                <p class="text-muted small mb-3">
                    Texto de muestra para ver como quedaria un mensaje real en las cajas de abajo. Solo es vista previa, no se guarda.
                </p>
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold" for="preview-message">Mensaje de muestra</label>
                        <input type="text" id="preview-message" class="form-control"
                               value="¡Feliz cumpleaños, Jaime! 🎉" maxlength="1000">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label fw-bold" for="preview-recipient">Nombre de muestra</label>
                        <input type="text" id="preview-recipient" class="form-control"
                               value="Jorge Da Silva Orallo" maxlength="80">
                        <small class="form-text text-muted">Se usa en la pieza que imprima el nombre.</small>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label fw-bold" for="preview-order">N. de pedido de muestra</label>
                        <input type="text" id="preview-order" class="form-control" value="29394" maxlength="30">
                    </div>
                </div>
            </div>

            <hr class="my-4">

            {{-- Sobre --}}
            <div class="mb-4">
                <h5 class="mb-1 fw-bold text-dark">Sobre</h5>
                <p class="text-muted small mb-3">Imagen de fondo, posicion del texto y tipografia del sobre.</p>

                {{-- Imagen --}}
                <div class="mb-4">
                    <h6 class="mb-1 fw-bold text-dark">Imagen de fondo</h6>
                    <p class="text-muted small mb-3">Arrastra la imagen sobre la zona o haz clic para elegirla. Se guarda sola al soltarla.</p>
                    <div class="row g-3">
                        <div class="col-12 col-lg-6 col-xxl-5">
                            {{-- La imagen guardada se pinta como fondo de la propia zona (via JS,
                                 desde data-image) con el ratio real de la pieza: es a la vez la
                                 vista previa y el sitio donde soltar la sustituta. --}}
                            <div class="dropzone giftmessage-dropzone giftmessage-dropzone-envelope {{ $config->envelope_image ? 'giftmessage-dropzone-filled' : '' }}"
                                 id="dropzone-envelope" data-scope="envelope" data-field="envelope_image"
                                 data-image="{{ $config->envelope_image ? asset('storage/'.$config->envelope_image) : '' }}">
                                <div class="dz-message">
                                    <span class="d-block fw-semibold" data-dropzone-title>{{ $config->envelope_image ? 'Arrastra otra imagen para reemplazarla' : 'Arrastra la imagen aqui' }}</span>
                                    <span class="d-block small">o haz clic para elegirla &middot; JPG o PNG &middot; maximo 5 MB</span>
                                </div>
                                <div class="fallback">
                                    <input type="file" name="envelope_image" accept="image/*">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between gap-2 mt-2">
                                <small class="text-muted text-truncate" data-preview-label>{{ $config->envelope_image ? basename($config->envelope_image) : 'Sin imagen todavia' }}</small>
                                <small class="text-muted flex-shrink-0">220 x 110 mm</small>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-3">

                {{-- Posicion --}}
                <div class="mb-4">
                    <h6 class="mb-1 fw-bold text-dark">Posicion del texto</h6>
                    <p class="text-muted small mb-3">
                        Arrastra las cajas T1 (texto principal) y T2 (numero de gestion). Cada caja es el limite
                        maximo del texto: el ancho fuerza el salto de linea y lo que no entre en el alto se recorta.
                    </p>

                    {{-- Que va en la caja grande de esta pieza: el sobre suele llevar el
                         nombre de quien recibe el regalo y la tarjeta el mensaje. --}}
                    <form action="{{ route('settings.giftmessage.content.update') }}" method="POST" class="mb-3">
                        @csrf
                        <input type="hidden" name="scope" value="envelope">
                        <div class="row g-3">
                            <div class="col-12 col-xl-4">
                                <label class="form-label fw-bold" for="env_t1_content">Que se imprime en T1</label>
                                <select class="form-select giftmessage-content-select" id="env_t1_content"
                                        name="env_t1_content" data-scope="envelope">
                                    @foreach(\Modules\GiftMessage\Services\GiftMessagePdfService::CONTENT_LABELS as $value => $label)
                                        <option value="{{ $value }}" @selected($config->env_t1_content === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @foreach(['t1' => 'T1 (texto principal)', 't2' => 'T2 (numero)'] as $slot => $slotLabel)
                                <div class="col-6 col-xl-2">
                                    <label class="form-label fw-bold" for="env_{{ $slot }}_align">{{ $slotLabel }}: horizontal</label>
                                    <select class="form-select giftmessage-align-select" id="env_{{ $slot }}_align"
                                            name="env_{{ $slot }}_align" data-scope="envelope" data-slot="{{ $slot }}" data-axis="h">
                                        @foreach(\Modules\GiftMessage\Services\GiftMessagePdfService::ALIGNMENTS as $value => $label)
                                            <option value="{{ $value }}" @selected(($config->{'env_'.$slot.'_align'} ?? 'center') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6 col-xl-2">
                                    <label class="form-label fw-bold" for="env_{{ $slot }}_valign">{{ $slotLabel }}: vertical</label>
                                    <select class="form-select giftmessage-align-select" id="env_{{ $slot }}_valign"
                                            name="env_{{ $slot }}_valign" data-scope="envelope" data-slot="{{ $slot }}" data-axis="v">
                                        @foreach(\Modules\GiftMessage\Services\GiftMessagePdfService::VERTICAL_ALIGNMENTS as $value => $label)
                                            <option value="{{ $value }}" @selected(($config->{'env_'.$slot.'_valign'} ?? 'middle') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endforeach
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary btn-sm">Guardar contenido y alineacion</button>
                            </div>
                        </div>
                    </form>
                    @if(! $config->envelope_image)
                        <div class="alert alert-warning mb-0">Sube primero la imagen de arriba y guardala para poder posicionar los textos.</div>
                    @else
                        <div class="giftmessage-canvas-outer">
                            <div id="canvas-envelope" class="giftmessage-canvas giftmessage-canvas-envelope"
                                 data-bg="{{ asset('storage/'.$config->envelope_image) }}">
                                <div class="giftmessage-drag" data-scope="envelope" data-slot="t1"
                                     data-x="{{ $config->env_t1_x }}" data-y="{{ $config->env_t1_y }}"
                                     data-w="{{ $config->env_t1_w }}" data-h="{{ $config->env_t1_h }}" tabindex="0">
                                    T1 &middot; Mensaje
                                </div>
                                <div class="giftmessage-drag" data-scope="envelope" data-slot="t2"
                                     data-x="{{ $config->env_t2_x }}" data-y="{{ $config->env_t2_y }}"
                                     data-w="{{ $config->env_t2_w }}" data-h="{{ $config->env_t2_h }}" tabindex="0">
                                    T2 &middot; Gestion
                                </div>
                            </div>
                        </div>
                        <p class="small mt-2 mb-0 giftmessage-fit-note" data-fit-note="envelope"></p>
                        @include('giftmessage::admin.settings.partials.fine-tune', ['canvas' => 'envelope', 'prefix' => 'env', 'config' => $config])
                        <button type="button" id="save-positions-envelope" class="btn btn-primary w-100 mt-3">
                            Guardar posiciones
                        </button>
                        {{-- PDF de prueba con lo que hay en pantalla, sin guardar
                             y sin pasar por el historial. --}}
                        <button type="button" class="btn btn-secondary w-100 mt-2 giftmessage-preview-pdf" data-scope="envelope">
                            Ver PDF de prueba del sobre
                        </button>
                    @endif
                </div>

                <hr class="my-3">

                {{-- Tipografia --}}
                <div class="mb-0">
                    <h6 class="mb-1 fw-bold text-dark">Tipografia</h6>
                    <p class="text-muted small mb-3">Fuente, tamano, color y opacidad del mensaje y del numero de gestion.</p>
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-xl-6">
                            <label class="form-label fw-bold">Texto 1 (mensaje) &mdash; fuente</label>
                            <select class="form-select select2" name="env_t1_font">
                                @foreach($fonts as $code => $fontLabel)
                                    <option value="{{ $code }}" {{ $config->env_t1_font === $code ? 'selected' : '' }}>{{ $fontLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-4 col-xl-2">
                            <label class="form-label fw-bold">Tamano</label>
                            <input type="number" class="form-control" name="env_t1_size" min="6" max="72" value="{{ $config->env_t1_size }}">
                        </div>
                        <div class="col-4 col-xl-2">
                            <label class="form-label fw-bold">Color</label>
                            <div class="d-flex gap-1">
                                <input type="color" class="form-control form-control-color giftmessage-color-swatch"
                                       id="env_t1_color" name="env_t1_color" value="{{ $config->env_t1_color }}">
                                <input type="text" class="form-control form-control-sm giftmessage-color-hex"
                                       data-color-target="env_t1_color" value="{{ $config->env_t1_color }}" maxlength="7" placeholder="#000000">
                            </div>
                        </div>
                        <div class="col-4 col-xl-2">
                            <label class="form-label fw-bold">Opacidad %</label>
                            <input type="number" class="form-control" name="env_t1_opacity" min="0" max="100" step="5" value="{{ $config->env_t1_opacity }}">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-xl-6">
                            <label class="form-label fw-bold">Texto 2 (gestion) &mdash; fuente</label>
                            <select class="form-select select2" name="env_t2_font">
                                @foreach($fonts as $code => $fontLabel)
                                    <option value="{{ $code }}" {{ $config->env_t2_font === $code ? 'selected' : '' }}>{{ $fontLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-4 col-xl-2">
                            <label class="form-label fw-bold">Tamano</label>
                            <input type="number" class="form-control" name="env_t2_size" min="6" max="72" value="{{ $config->env_t2_size }}">
                        </div>
                        <div class="col-4 col-xl-2">
                            <label class="form-label fw-bold">Color</label>
                            <div class="d-flex gap-1">
                                <input type="color" class="form-control form-control-color giftmessage-color-swatch"
                                       id="env_t2_color" name="env_t2_color" value="{{ $config->env_t2_color }}">
                                <input type="text" class="form-control form-control-sm giftmessage-color-hex"
                                       data-color-target="env_t2_color" value="{{ $config->env_t2_color }}" maxlength="7" placeholder="#000000">
                            </div>
                        </div>
                        <div class="col-4 col-xl-2">
                            <label class="form-label fw-bold">Opacidad %</label>
                            <input type="number" class="form-control" name="env_t2_opacity" min="0" max="100" step="5" value="{{ $config->env_t2_opacity }}">
                        </div>
                    </div>
                    <button type="button" id="save-fonts-envelope" class="btn btn-primary w-100 ">Guardar tipografia</button>
                </div>
            </div>

            <hr class="my-4">

            {{-- Tarjeta --}}
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-1">
                    <h5 class="mb-0 fw-bold text-dark">Tarjeta</h5>
                    <button type="button" id="copy-to-card" class="btn btn-secondary btn-sm">
                        <i class="fas fa-copy me-1"></i> Copiar posicion y tipografia del sobre
                    </button>
                </div>
                <p class="text-muted small mb-3">Imagen de fondo, posicion del texto y tipografia de la tarjeta.</p>

                {{-- Imagen --}}
                <div class="mb-4">
                    <h6 class="mb-1 fw-bold text-dark">Imagen de fondo</h6>
                    <p class="text-muted small mb-3">Arrastra la imagen sobre la zona o haz clic para elegirla. Se guarda sola al soltarla.</p>
                    <div class="row g-3">
                        <div class="col-12 col-lg-6 col-xxl-5">
                            {{-- La imagen guardada se pinta como fondo de la propia zona (via JS,
                                 desde data-image) con el ratio real de la pieza: es a la vez la
                                 vista previa y el sitio donde soltar la sustituta. --}}
                            <div class="dropzone giftmessage-dropzone giftmessage-dropzone-card {{ $config->card_image ? 'giftmessage-dropzone-filled' : '' }}"
                                 id="dropzone-card" data-scope="card" data-field="card_image"
                                 data-image="{{ $config->card_image ? asset('storage/'.$config->card_image) : '' }}">
                                <div class="dz-message">
                                    <span class="d-block fw-semibold" data-dropzone-title>{{ $config->card_image ? 'Arrastra otra imagen para reemplazarla' : 'Arrastra la imagen aqui' }}</span>
                                    <span class="d-block small">o haz clic para elegirla &middot; JPG o PNG &middot; maximo 5 MB</span>
                                </div>
                                <div class="fallback">
                                    <input type="file" name="card_image" accept="image/*">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between gap-2 mt-2">
                                <small class="text-muted text-truncate" data-preview-label>{{ $config->card_image ? basename($config->card_image) : 'Sin imagen todavia' }}</small>
                                <small class="text-muted flex-shrink-0">200 x 90 mm</small>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-3">

                {{-- Posicion --}}
                <div class="mb-4">
                    <h6 class="mb-1 fw-bold text-dark">Posicion del texto</h6>
                    <p class="text-muted small mb-3">
                        Arrastra las cajas T1 (texto principal) y T2 (numero de gestion). Cada caja es el limite
                        maximo del texto: el ancho fuerza el salto de linea y lo que no entre en el alto se recorta.
                    </p>

                    {{-- Que va en la caja grande de esta pieza: el sobre suele llevar el
                         nombre de quien recibe el regalo y la tarjeta el mensaje. --}}
                    <form action="{{ route('settings.giftmessage.content.update') }}" method="POST" class="mb-3">
                        @csrf
                        <input type="hidden" name="scope" value="card">
                        <div class="row g-3">
                            <div class="col-12 col-xl-4">
                                <label class="form-label fw-bold" for="card_t1_content">Que se imprime en T1</label>
                                <select class="form-select giftmessage-content-select" id="card_t1_content"
                                        name="card_t1_content" data-scope="card">
                                    @foreach(\Modules\GiftMessage\Services\GiftMessagePdfService::CONTENT_LABELS as $value => $label)
                                        <option value="{{ $value }}" @selected($config->card_t1_content === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @foreach(['t1' => 'T1 (texto principal)', 't2' => 'T2 (numero)'] as $slot => $slotLabel)
                                <div class="col-6 col-xl-2">
                                    <label class="form-label fw-bold" for="card_{{ $slot }}_align">{{ $slotLabel }}: horizontal</label>
                                    <select class="form-select giftmessage-align-select" id="card_{{ $slot }}_align"
                                            name="card_{{ $slot }}_align" data-scope="card" data-slot="{{ $slot }}" data-axis="h">
                                        @foreach(\Modules\GiftMessage\Services\GiftMessagePdfService::ALIGNMENTS as $value => $label)
                                            <option value="{{ $value }}" @selected(($config->{'card_'.$slot.'_align'} ?? 'center') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6 col-xl-2">
                                    <label class="form-label fw-bold" for="card_{{ $slot }}_valign">{{ $slotLabel }}: vertical</label>
                                    <select class="form-select giftmessage-align-select" id="card_{{ $slot }}_valign"
                                            name="card_{{ $slot }}_valign" data-scope="card" data-slot="{{ $slot }}" data-axis="v">
                                        @foreach(\Modules\GiftMessage\Services\GiftMessagePdfService::VERTICAL_ALIGNMENTS as $value => $label)
                                            <option value="{{ $value }}" @selected(($config->{'card_'.$slot.'_valign'} ?? 'middle') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endforeach
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary btn-sm">Guardar contenido y alineacion</button>
                            </div>
                        </div>
                    </form>
                    @if(! $config->card_image)
                        <div class="alert alert-warning mb-0">Sube primero la imagen de arriba y guardala para poder posicionar los textos.</div>
                    @else
                        <div class="giftmessage-canvas-outer">
                            <div id="canvas-card" class="giftmessage-canvas giftmessage-canvas-card"
                                 data-bg="{{ asset('storage/'.$config->card_image) }}">
                                <div class="giftmessage-drag" data-scope="card" data-slot="t1"
                                     data-x="{{ $config->card_t1_x }}" data-y="{{ $config->card_t1_y }}"
                                     data-w="{{ $config->card_t1_w }}" data-h="{{ $config->card_t1_h }}" tabindex="0">
                                    T1 &middot; Mensaje
                                </div>
                                <div class="giftmessage-drag" data-scope="card" data-slot="t2"
                                     data-x="{{ $config->card_t2_x }}" data-y="{{ $config->card_t2_y }}"
                                     data-w="{{ $config->card_t2_w }}" data-h="{{ $config->card_t2_h }}" tabindex="0">
                                    T2 &middot; Gestion
                                </div>
                            </div>
                        </div>
                        <p class="small mt-2 mb-0 giftmessage-fit-note" data-fit-note="card"></p>
                        @include('giftmessage::admin.settings.partials.fine-tune', ['canvas' => 'card', 'prefix' => 'card', 'config' => $config])
                        <button type="button" id="save-positions-card" class="btn btn-primary w-100 mt-3">
                            Guardar posiciones
                        </button>
                        {{-- PDF de prueba con lo que hay en pantalla, sin guardar
                             y sin pasar por el historial. --}}
                        <button type="button" class="btn btn-secondary w-100 mt-2 giftmessage-preview-pdf" data-scope="card">
                            Ver PDF de prueba de la tarjeta
                        </button>
                    @endif
                </div>

                <hr class="my-3">

                {{-- Tipografia --}}
                <div class="mb-0">
                    <h6 class="mb-1 fw-bold text-dark">Tipografia</h6>
                    <p class="text-muted small mb-3">Fuente, tamano, color y opacidad del mensaje y del numero de gestion.</p>
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-xl-6">
                            <label class="form-label fw-bold">Texto 1 (mensaje) &mdash; fuente</label>
                            <select class="form-select select2" name="card_t1_font">
                                @foreach($fonts as $code => $fontLabel)
                                    <option value="{{ $code }}" {{ $config->card_t1_font === $code ? 'selected' : '' }}>{{ $fontLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-4 col-xl-2">
                            <label class="form-label fw-bold">Tamano</label>
                            <input type="number" class="form-control" name="card_t1_size" min="6" max="72" value="{{ $config->card_t1_size }}">
                        </div>
                        <div class="col-4 col-xl-2">
                            <label class="form-label fw-bold">Color</label>
                            <div class="d-flex gap-1">
                                <input type="color" class="form-control form-control-color giftmessage-color-swatch"
                                       id="card_t1_color" name="card_t1_color" value="{{ $config->card_t1_color }}">
                                <input type="text" class="form-control form-control-sm giftmessage-color-hex"
                                       data-color-target="card_t1_color" value="{{ $config->card_t1_color }}" maxlength="7" placeholder="#000000">
                            </div>
                        </div>
                        <div class="col-4 col-xl-2">
                            <label class="form-label fw-bold">Opacidad %</label>
                            <input type="number" class="form-control" name="card_t1_opacity" min="0" max="100" step="5" value="{{ $config->card_t1_opacity }}">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-xl-6">
                            <label class="form-label fw-bold">Texto 2 (gestion) &mdash; fuente</label>
                            <select class="form-select select2" name="card_t2_font">
                                @foreach($fonts as $code => $fontLabel)
                                    <option value="{{ $code }}" {{ $config->card_t2_font === $code ? 'selected' : '' }}>{{ $fontLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-4 col-xl-2">
                            <label class="form-label fw-bold">Tamano</label>
                            <input type="number" class="form-control" name="card_t2_size" min="6" max="72" value="{{ $config->card_t2_size }}">
                        </div>
                        <div class="col-4 col-xl-2">
                            <label class="form-label fw-bold">Color</label>
                            <div class="d-flex gap-1">
                                <input type="color" class="form-control form-control-color giftmessage-color-swatch"
                                       id="card_t2_color" name="card_t2_color" value="{{ $config->card_t2_color }}">
                                <input type="text" class="form-control form-control-sm giftmessage-color-hex"
                                       data-color-target="card_t2_color" value="{{ $config->card_t2_color }}" maxlength="7" placeholder="#000000">
                            </div>
                        </div>
                        <div class="col-4 col-xl-2">
                            <label class="form-label fw-bold">Opacidad %</label>
                            <input type="number" class="form-control" name="card_t2_opacity" min="0" max="100" step="5" value="{{ $config->card_t2_opacity }}">
                        </div>
                    </div>
                    <button type="button" id="save-fonts-card" class="btn btn-primary w-100 ">Guardar tipografia</button>
                </div>
            </div>

            <hr class="my-4">

            {{-- Limites de legibilidad: comunes a sobre y tarjeta --}}
            <div class="mb-4">
                <h5 class="mb-1 fw-bold text-dark">Limites del texto</h5>
                <p class="text-muted small mb-3">
                    Cuando un mensaje no cabe, primero se aprieta el interlineado y despues se reduce la letra.
                    Aqui se decide hasta donde puede reducirse y a partir de que longitud avisar.
                </p>
                <form action="{{ route('settings.giftmessage.limits.update') }}" method="POST">
                    @csrf
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-xl-4">
                            <label class="form-label fw-bold" for="min_font_size">Tamano minimo de letra (pt)</label>
                            <input type="number" class="form-control" id="min_font_size" name="min_font_size"
                                   min="5" max="72" value="{{ old('min_font_size', $config->min_font_size) }}">
                            <small class="form-text text-muted">
                                El ajuste automatico no baja de aqui. Si aun asi el mensaje no cabe se imprime a este
                                tamano y se avisa, en vez de recortarlo en silencio.
                            </small>
                            @error('min_font_size')
                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-12 col-xl-4">
                            <label class="form-label fw-bold" for="paragraph_spacing">Aire entre parrafos</label>
                            <input type="number" step="0.05" min="0" max="2" class="form-control"
                                   id="paragraph_spacing" name="paragraph_spacing"
                                   value="{{ old('paragraph_spacing', $config->paragraph_spacing) }}">
                            <small class="form-text text-muted">
                                En fracciones del tamano de letra. 0,35 deja un aire discreto; 0 pega los parrafos.
                            </small>
                            @error('paragraph_spacing')
                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-12 col-xl-4">
                            <label class="form-label fw-bold" for="max_message_length">Longitud a partir de la que avisar (caracteres)</label>
                            <input type="number" class="form-control" id="max_message_length" name="max_message_length"
                                   min="50" max="5000" value="{{ old('max_message_length', $config->max_message_length) }}">
                            <small class="form-text text-muted">
                                Los pedidos que la superen salen marcados en el listado antes de generar el PDF.
                            </small>
                            @error('max_message_length')
                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Guardar limites</button>
                </form>
            </div>

            <hr class="my-4">

            {{-- Fuentes personalizadas: recurso compartido entre sobre y tarjeta --}}
            <div class="mb-0">
                <h5 class="mb-1 fw-bold text-dark">Fuentes personalizadas</h5>
                <p class="text-muted small mb-3">
                    Las fuentes subidas aqui se embeben en el PDF y aparecen en los desplegables de arriba.
                </p>

                @if($uploadedFonts->isEmpty())
                    <p class="text-muted mb-3">
                        Todavia no hay fuentes personalizadas. Solo estan disponibles las del sistema
                        (Helvetica, Times, Courier y las DejaVu, que son las que cubren emojis y acentos poco comunes).
                    </p>
                @else
                    <div class="table-responsive mb-3">
                        <table class="table table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Familia</th>
                                    <th>Variante</th>
                                    <th>Muestra</th>
                                    <th>Subida</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($uploadedFonts as $font)
                                    <tr>
                                        <td class="fw-semibold">{{ $font->name }}</td>
                                        <td><code>{{ $font->family }}</code></td>
                                        <td>{{ $font->variantLabel() }}</td>
                                        <td class="giftmessage-font-sample" data-family="{{ $font->family }}"
                                            data-weight="{{ $font->weight }}" data-style="{{ $font->style }}">
                                            Feliz cumpleanos 123
                                        </td>
                                        <td class="text-muted">{{ $font->created_at?->format('d/m/Y H:i') }}</td>
                                        <td class="text-end">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <button type="button" class="dropdown-item delete-font-btn"
                                                                data-bs-toggle="modal" data-bs-target="#delete-modal"
                                                                data-title="Eliminar fuente: {{ $font->name }} ({{ $font->variantLabel() }})"
                                                                data-url="{{ route('settings.giftmessage.fonts.destroy', $font) }}">
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

                <h6 class="mb-1 fw-bold text-dark">Subir fuente</h6>
                <p class="text-muted small mb-3">
                    Sube un archivo TTF u OTF por cada variante. Para que negrita y cursiva salgan bien en el PDF,
                    sube cada variante con el mismo nombre cambiando el grosor o el estilo.
                </p>

                <form action="{{ route('settings.giftmessage.fonts.store') }}" method="POST"
                      enctype="multipart/form-data" class="row g-2 align-items-end">
                    @csrf

                    <div class="col-12 col-md-4">
                        <label class="form-label fw-bold">Nombre de la fuente <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                               name="name" value="{{ old('name') }}" placeholder="ej: Montserrat" required>
                        @error('name')
                            <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                        @error('family')
                            <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-6 col-md-2">
                        <label class="form-label fw-bold">Grosor</label>
                        <select class="form-select" name="weight">
                            <option value="normal" {{ old('weight') === 'normal' ? 'selected' : '' }}>Normal</option>
                            <option value="bold" {{ old('weight') === 'bold' ? 'selected' : '' }}>Negrita</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <label class="form-label fw-bold">Estilo</label>
                        <select class="form-select" name="style">
                            <option value="normal" {{ old('style') === 'normal' ? 'selected' : '' }}>Normal</option>
                            <option value="italic" {{ old('style') === 'italic' ? 'selected' : '' }}>Cursiva</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label fw-bold">Archivo TTF/OTF <span class="text-danger">*</span></label>
                        <input type="file" class="form-control @error('font_file') is-invalid @enderror"
                               name="font_file" accept=".ttf,.otf" required>
                        @error('font_file')
                            <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-12 col-md-1">
                        <button type="submit" class="btn btn-primary w-100">Subir</button>
                    </div>
                </form>
            </div>

        </div>

        <div class="card-footer">
            <a href="{{ route('giftmessage.index') }}" class="btn btn-secondary w-100">Volver</a>
        </div>
    </div>

    @include('core::components.delete')

    {{-- Previsualizacion del PDF de una pieza --}}
    <div id="preview-pdf-modal" class="modal fade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="preview-pdf-title">PDF de prueba</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">
                        Generado con lo que tienes ahora en pantalla, aunque no lo hayas guardado.
                        No se guarda en el historial.
                    </p>
                    <p class="small mb-2" id="preview-pdf-status"></p>
                    <iframe id="preview-pdf-frame" class="giftmessage-preview-frame" title="PDF de prueba"></iframe>
                </div>
                <div class="modal-footer">
                    <a id="preview-pdf-open" class="btn btn-primary w-100 mb-2" target="_blank" rel="noopener">Abrir en una pestana nueva</a>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.GIFTMESSAGE_SETTINGS = {
            urls: {
                savePositions: "{{ route('settings.giftmessage.positions.save') }}",
                saveFonts: "{{ route('settings.giftmessage.typography.update') }}",
                uploadImage: "{{ route('settings.giftmessage.images.store') }}",
                previewMetrics: "{{ route('settings.giftmessage.preview.metrics') }}",
                previewPdf: "{{ route('settings.giftmessage.preview.pdf') }}",
            },
            stacks: @json($fontStacks),
            fonts: {
                envelope: {
                    t1: { font: @json($config->env_t1_font), size: @json($config->env_t1_size), color: @json($config->env_t1_color), opacity: @json($config->env_t1_opacity) },
                    t2: { font: @json($config->env_t2_font), size: @json($config->env_t2_size), color: @json($config->env_t2_color), opacity: @json($config->env_t2_opacity) },
                },
                card: {
                    t1: { font: @json($config->card_t1_font), size: @json($config->card_t1_size), color: @json($config->card_t1_color), opacity: @json($config->card_t1_opacity) },
                    t2: { font: @json($config->card_t2_font), size: @json($config->card_t2_size), color: @json($config->card_t2_color), opacity: @json($config->card_t2_opacity) },
                },
            },
        };
    </script>

@endsection

@push('scripts')
<script src="{{ asset('modules/giftmessage/js/vendor/interact.min.js') }}"></script>
<script src="{{ asset('modules/giftmessage/js/settings.js') }}?v={{ $giftmessageAssetVersion('modules/giftmessage/js/settings.js') }}"></script>
<script>
$(document).ready(function () {
    $('.select2').select2({ width: '100%' });

    $('.giftmessage-font-sample').each(function () {
        $(this).css({
            fontFamily: "'" + $(this).data('family') + "', sans-serif",
            fontWeight: $(this).data('weight'),
            fontStyle: $(this).data('style'),
        });
    });

    $('.delete-font-btn').on('click', function () {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif
});
</script>
@endpush

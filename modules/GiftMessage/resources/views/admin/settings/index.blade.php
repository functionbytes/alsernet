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
                    <div class="col-12 col-md-8">
                        <label class="form-label fw-bold">Mensaje de muestra</label>
                        <input type="text" id="preview-message" class="form-control"
                               value="¡Feliz cumpleaños, Jaime! 🎉" maxlength="200">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-bold">N. de pedido de muestra</label>
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
                        Arrastra las cajas T1 (mensaje) y T2 (numero de gestion). Cada caja es el limite maximo
                        del texto: el ancho fuerza el salto de linea y lo que no entre en el alto se recorta.
                    </p>
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
                        @include('giftmessage::admin.settings.partials.fine-tune', ['canvas' => 'envelope', 'prefix' => 'env', 'config' => $config])
                        <button type="button" id="save-positions-envelope" class="btn btn-primary w-100 mt-3">
                            Guardar posiciones
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
                        Arrastra las cajas T1 (mensaje) y T2 (numero de gestion). Cada caja es el limite maximo
                        del texto: el ancho fuerza el salto de linea y lo que no entre en el alto se recorta.
                    </p>
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
                        @include('giftmessage::admin.settings.partials.fine-tune', ['canvas' => 'card', 'prefix' => 'card', 'config' => $config])
                        <button type="button" id="save-positions-card" class="btn btn-primary w-100 mt-3">
                            Guardar posiciones
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

    <script>
        window.GIFTMESSAGE_SETTINGS = {
            urls: {
                savePositions: "{{ route('settings.giftmessage.positions.save') }}",
                saveFonts: "{{ route('settings.giftmessage.typography.update') }}",
                uploadImage: "{{ route('settings.giftmessage.images.store') }}",
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

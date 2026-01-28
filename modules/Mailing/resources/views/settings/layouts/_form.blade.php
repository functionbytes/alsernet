{{-- Email Layout Form Component --}}

<div class="row">
    <div class="col-12 col-md-8">
        {{-- Sección 1: Información básica --}}
        <h6 class="fw-bold mb-3 border-bottom pb-2">
            Información básica
        </h6>

        <div class="row mb-4">
            <div class="col-12 col-md-6">
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Nombre del layout <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                           class="form-control @error('name') is-invalid @enderror"
                           name="name"
                           value="{{ old('name', $layout->name ?? '') }}"
                           maxlength="100"
                           placeholder="Ej: Layout corporativo"
                           required>
                    <small class="form-text text-muted d-block mt-1">Nombre interno para identificar el layout</small>
                    @error('name')
                        <div class="invalid-feedback d-block mt-1">
                            <i class="fas fa-circle-exclamation me-1"></i>{{ $message }}
                        </div>
                    @enderror
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tipo de layout</label>
                    <select class="form-select @error('type') is-invalid @enderror" name="type">
                        <option value="">Seleccionar tipo...</option>
                        <option value="single-column" {{ old('type', $layout->type ?? '') == 'single-column' ? 'selected' : '' }}>Una columna</option>
                        <option value="two-column" {{ old('type', $layout->type ?? '') == 'two-column' ? 'selected' : '' }}>Dos columnas</option>
                        <option value="three-column" {{ old('type', $layout->type ?? '') == 'three-column' ? 'selected' : '' }}>Tres columnas</option>
                    </select>
                    @error('type')
                        <div class="invalid-feedback d-block">
                            <i class="fas fa-circle-exclamation me-1"></i>{{ $message }}
                        </div>
                    @enderror
                </div>
            </div>

            <div class="col-12">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Descripción</label>
                    <textarea class="form-control @error('description') is-invalid @enderror"
                              name="description"
                              rows="2"
                              maxlength="255"
                              placeholder="Descripción breve del layout">{{ old('description', $layout->description ?? '') }}</textarea>
                    <small class="form-text text-muted d-block mt-1">Información adicional sobre el propósito</small>
                    @error('description')
                        <div class="invalid-feedback d-block mt-1">
                            <i class="fas fa-circle-exclamation me-1"></i>{{ $message }}
                        </div>
                    @enderror
                </div>
            </div>
        </div>

        <hr class="my-4">

        {{-- Sección 2: Configuración HTML --}}
        <h6 class="fw-bold mb-3 border-bottom pb-2">
            Estructura del layout
        </h6>

        <div class="row mb-4">
            <div class="col-12">
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Contenido HTML <span class="text-danger">*</span>
                    </label>
                    <div class="alert alert-info small mb-3">
                        <i class="fas fa-lightbulb me-1"></i>
                        Utilice <code>{'{'}{'{'}content{'}'}{{'}'}}</code> como placeholder para el contenido principal
                    </div>
                    <textarea class="form-control @error('html') is-invalid @enderror"
                              id="html-editor"
                              name="html"
                              rows="15"
                              placeholder="Ingrese el HTML del layout"
                              required>{{ old('html', $layout->html ?? '') }}</textarea>
                    <small class="form-text text-muted d-block mt-1">
                        Puede incluir CSS inline y variables como {{'{{'}}company.name{{'}}'}}, {{'{{'}}company.logo{{'}}'}}, etc.
                    </small>
                    @error('html')
                        <div class="invalid-feedback d-block mt-1">
                            <i class="fas fa-circle-exclamation me-1"></i>{{ $message }}
                        </div>
                    @enderror
                </div>
            </div>
        </div>

        <hr class="my-4">

        {{-- Sección 3: Opciones de diseño --}}
        <h6 class="fw-bold mb-3 border-bottom pb-2">
            Opciones de diseño
        </h6>

        <div class="row mb-4">
            <div class="col-12 col-md-6">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Color de fondo</label>
                    <div class="input-group">
                        <input type="color"
                               class="form-control form-control-color"
                               name="background_color"
                               value="{{ old('background_color', $layout->background_color ?? '#ffffff') }}"
                               style="max-width: 60px;">
                        <input type="text"
                               class="form-control"
                               name="background_color_text"
                               value="{{ old('background_color', $layout->background_color ?? '#ffffff') }}"
                               placeholder="#ffffff">
                    </div>
                    <small class="form-text text-muted d-block mt-1">Color de fondo del email</small>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Color de texto</label>
                    <div class="input-group">
                        <input type="color"
                               class="form-control form-control-color"
                               name="text_color"
                               value="{{ old('text_color', $layout->text_color ?? '#333333') }}"
                               style="max-width: 60px;">
                        <input type="text"
                               class="form-control"
                               name="text_color_text"
                               value="{{ old('text_color', $layout->text_color ?? '#333333') }}"
                               placeholder="#333333">
                    </div>
                    <small class="form-text text-muted d-block mt-1">Color de texto predeterminado</small>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Ancho máximo (px)</label>
                    <input type="number"
                           class="form-control @error('max_width') is-invalid @enderror"
                           name="max_width"
                           value="{{ old('max_width', $layout->max_width ?? 600) }}"
                           min="300"
                           max="1200"
                           placeholder="600">
                    <small class="form-text text-muted d-block mt-1">Ancho máximo del contenido</small>
                    @error('max_width')
                        <div class="invalid-feedback d-block mt-1">
                            <i class="fas fa-circle-exclamation me-1"></i>{{ $message }}
                        </div>
                    @enderror
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Espaciado (px)</label>
                    <input type="number"
                           class="form-control @error('padding') is-invalid @enderror"
                           name="padding"
                           value="{{ old('padding', $layout->padding ?? 20) }}"
                           min="0"
                           max="50"
                           placeholder="20">
                    <small class="form-text text-muted d-block mt-1">Espaciado interno predeterminado</small>
                    @error('padding')
                        <div class="invalid-feedback d-block mt-1">
                            <i class="fas fa-circle-exclamation me-1"></i>{{ $message }}
                        </div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        {{-- Panel lateral: Configuración adicional --}}
        <div class="card border-light">
            <div class="card-header bg-light border-bottom">
                <h6 class="mb-0 fw-bold">Configuración</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Estado</label>
                    <div class="form-check form-switch">
                        <input class="form-check-input"
                               type="checkbox"
                               name="is_active"
                               id="isActive"
                               value="1"
                               {{ old('is_active', $layout->is_active ?? 0) ? 'checked' : '' }}>
                        <label class="form-check-label" for="isActive">
                            Layout activo
                        </label>
                    </div>
                    <small class="form-text text-muted d-block mt-1">Solo los layouts activos pueden ser utilizados</small>
                </div>

                <hr>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Vista previa</label>
                    <button type="button" class="btn btn-sm btn-light-info w-100" data-bs-toggle="modal" data-bs-target="#layoutPreviewModal">
                        <i class="fas fa-eye me-1"></i>Previsualizar
                    </button>
                </div>

                <hr>

                <div class="mb-0">
                    <p class="small text-muted mb-2"><strong>Información:</strong></p>
                    @if(isset($layout))
                        <ul class="list-unstyled small text-muted">
                            <li class="mb-1">
                                <i class="fas fa-calendar me-2"></i>
                                <strong>Creado:</strong> {{ $layout->created_at->format('d/m/Y H:i') }}
                            </li>
                            <li>
                                <i class="fas fa-clock me-2"></i>
                                <strong>Modificado:</strong> {{ $layout->updated_at->format('d/m/Y H:i') }}
                            </li>
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        // Color input synchronization
        $('input[name="background_color"]').on('change', function() {
            $('input[name="background_color_text"]').val($(this).val());
        });
        $('input[name="background_color_text"]').on('change', function() {
            $('input[name="background_color"]').val($(this).val());
        });

        $('input[name="text_color"]').on('change', function() {
            $('input[name="text_color_text"]').val($(this).val());
        });
        $('input[name="text_color_text"]').on('change', function() {
            $('input[name="text_color"]').val($(this).val());
        });
    });
</script>
@endpush

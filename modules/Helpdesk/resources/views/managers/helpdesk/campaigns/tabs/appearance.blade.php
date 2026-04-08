{{-- Appearance Editor Tab --}}
<form method="POST" action="{{ route('manager.helpdesk.campaigns.update', $campaign) }}" id="appearance-form">
    @csrf
    @method('PUT')

    @php
        $appearance = old('appearance', $campaign->appearance ?? []);
        $bgColor = $appearance['background_color'] ?? '#ffffff';
        $textColor = $appearance['text_color'] ?? '#000000';
        $primaryColor = $appearance['primary_color'] ?? '#90bb13';
        $position = $appearance['position'] ?? 'center';
        $fontSize = $appearance['font_size'] ?? 'medium';
    @endphp

    <h5 class="mb-3">
        <i class="fas fa-palette"></i> Personalización Visual
    </h5>

    {{-- Colors Section --}}
    <div class="card bg-light mb-3">
        <div class="card-header">
            <h6 class="mb-0"><i class="fas fa-swatchbook"></i> Colores</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Color de Fondo</label>
                    <div class="input-group">
                        <input type="color"
                               name="appearance[background_color]"
                               class="form-control form-control-color"
                               value="{{ $bgColor }}"
                               title="Seleccionar color de fondo">
                        <input type="text"
                               class="form-control"
                               value="{{ $bgColor }}"
                               readonly>
                    </div>
                    <small class="text-muted">Color de fondo de la campaña</small>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Color de Texto</label>
                    <div class="input-group">
                        <input type="color"
                               name="appearance[text_color]"
                               class="form-control form-control-color"
                               value="{{ $textColor }}"
                               title="Seleccionar color de texto">
                        <input type="text"
                               class="form-control"
                               value="{{ $textColor }}"
                               readonly>
                    </div>
                    <small class="text-muted">Color principal del texto</small>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Color Primario (Botones)</label>
                    <div class="input-group">
                        <input type="color"
                               name="appearance[primary_color]"
                               class="form-control form-control-color"
                               value="{{ $primaryColor }}"
                               title="Seleccionar color primario">
                        <input type="text"
                               class="form-control"
                               value="{{ $primaryColor }}"
                               readonly>
                    </div>
                    <small class="text-muted">Color para botones y acentos</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Typography Section --}}
    <div class="card bg-light mb-3">
        <div class="card-header">
            <h6 class="mb-0"><i class="fas fa-font"></i> Tipografía</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Tamaño de Fuente</label>
                    <select name="appearance[font_size]" class="form-select">
                        <option value="small" {{ $fontSize === 'small' ? 'selected' : '' }}>Pequeña (12px)</option>
                        <option value="medium" {{ $fontSize === 'medium' ? 'selected' : '' }}>Mediana (14px)</option>
                        <option value="large" {{ $fontSize === 'large' ? 'selected' : '' }}>Grande (16px)</option>
                        <option value="xlarge" {{ $fontSize === 'xlarge' ? 'selected' : '' }}>Extra Grande (18px)</option>
                    </select>
                    <small class="text-muted">Tamaño base del texto</small>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Familia de Fuente</label>
                    <select name="appearance[font_family]" class="form-select">
                        <option value="system" {{ ($appearance['font_family'] ?? 'system') === 'system' ? 'selected' : '' }}>
                            Sistema (Por defecto)
                        </option>
                        <option value="sans-serif" {{ ($appearance['font_family'] ?? '') === 'sans-serif' ? 'selected' : '' }}>
                            Sans Serif
                        </option>
                        <option value="serif" {{ ($appearance['font_family'] ?? '') === 'serif' ? 'selected' : '' }}>
                            Serif
                        </option>
                        <option value="monospace" {{ ($appearance['font_family'] ?? '') === 'monospace' ? 'selected' : '' }}>
                            Monospace
                        </option>
                    </select>
                    <small class="text-muted">Tipo de fuente a usar</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Positioning Section --}}
    <div class="card bg-light mb-3">
        <div class="card-header">
            <h6 class="mb-0"><i class="fas fa-align-center"></i> Posicionamiento</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Posición en Pantalla</label>
                    <select name="appearance[position]" class="form-select" id="position-select">
                        <option value="top-left" {{ $position === 'top-left' ? 'selected' : '' }}>Superior Izquierda</option>
                        <option value="top-center" {{ $position === 'top-center' ? 'selected' : '' }}>Superior Centro</option>
                        <option value="top-right" {{ $position === 'top-right' ? 'selected' : '' }}>Superior Derecha</option>
                        <option value="center" {{ $position === 'center' ? 'selected' : '' }}>Centro</option>
                        <option value="bottom-left" {{ $position === 'bottom-left' ? 'selected' : '' }}>Inferior Izquierda</option>
                        <option value="bottom-center" {{ $position === 'bottom-center' ? 'selected' : '' }}>Inferior Centro</option>
                        <option value="bottom-right" {{ $position === 'bottom-right' ? 'selected' : '' }}>Inferior Derecha</option>
                    </select>
                    <small class="text-muted">Dónde aparecerá la campaña</small>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Ancho Máximo</label>
                    <div class="input-group">
                        <input type="number"
                               name="appearance[max_width]"
                               class="form-control"
                               value="{{ $appearance['max_width'] ?? 600 }}"
                               min="300"
                               max="1200"
                               step="50">
                        <span class="input-group-text">px</span>
                    </div>
                    <small class="text-muted">Ancho máximo del contenedor (300-1200px)</small>
                </div>
            </div>

            {{-- Position Preview Grid --}}
            <div class="mt-3">
                <label class="form-label small">Vista previa de posición:</label>
                <div class="position-preview-grid border rounded p-2" style="height: 150px; position: relative; background: #f8f9fa;">
                    <div id="position-indicator" class="bg-primary rounded" style="width: 40px; height: 30px; position: absolute; transition: all 0.3s;"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Border and Spacing --}}
    <div class="card bg-light mb-3">
        <div class="card-header">
            <h6 class="mb-0"><i class="fas fa-vector-square"></i> Bordes y Espaciado</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Radio de Borde</label>
                    <div class="input-group">
                        <input type="range"
                               name="appearance[border_radius]"
                               class="form-range"
                               min="0"
                               max="50"
                               value="{{ $appearance['border_radius'] ?? 12 }}"
                               id="border-radius-range">
                        <output class="ms-2">{{ $appearance['border_radius'] ?? 12 }}px</output>
                    </div>
                    <small class="text-muted">Curvatura de las esquinas (0 = cuadrado, 50 = muy redondeado)</small>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Padding Interno</label>
                    <div class="input-group">
                        <input type="number"
                               name="appearance[padding]"
                               class="form-control"
                               value="{{ $appearance['padding'] ?? 20 }}"
                               min="0"
                               max="50"
                               step="5">
                        <span class="input-group-text">px</span>
                    </div>
                    <small class="text-muted">Espacio interno del contenedor</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="d-flex justify-content-between border-top pt-3">
        <a href="{{ route('manager.helpdesk.campaigns.edit', ['campaign' => $campaign, 'tab' => 'content']) }}" class="btn btn-light">
            <i class="fas fa-arrow-left"></i> Anterior: Contenido
        </a>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="far fa-save"></i> Guardar Apariencia
            </button>
            <a href="{{ route('manager.helpdesk.campaigns.edit', ['campaign' => $campaign, 'tab' => 'conditions']) }}" class="btn btn-success">
                Siguiente: Condiciones <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sync color inputs
    document.querySelectorAll('input[type="color"]').forEach(colorInput => {
        const textInput = colorInput.nextElementSibling;
        colorInput.addEventListener('input', () => {
            textInput.value = colorInput.value;
        });
    });

    // Border radius range
    const borderRadiusRange = document.getElementById('border-radius-range');
    if (borderRadiusRange) {
        const output = borderRadiusRange.nextElementSibling;
        borderRadiusRange.addEventListener('input', () => {
            output.textContent = borderRadiusRange.value + 'px';
        });
    }

    // Position preview
    const positionSelect = document.getElementById('position-select');
    const positionIndicator = document.getElementById('position-indicator');

    function updatePositionPreview() {
        const position = positionSelect.value;
        const positions = {
            'top-left': { top: '5px', left: '5px', transform: 'none' },
            'top-center': { top: '5px', left: '50%', transform: 'translateX(-50%)' },
            'top-right': { top: '5px', right: '5px', left: 'auto', transform: 'none' },
            'center': { top: '50%', left: '50%', transform: 'translate(-50%, -50%)' },
            'bottom-left': { bottom: '5px', left: '5px', top: 'auto', transform: 'none' },
            'bottom-center': { bottom: '5px', left: '50%', top: 'auto', transform: 'translateX(-50%)' },
            'bottom-right': { bottom: '5px', right: '5px', left: 'auto', top: 'auto', transform: 'none' }
        };

        const styles = positions[position] || positions['center'];
        Object.assign(positionIndicator.style, styles);
    }

    positionSelect.addEventListener('change', updatePositionPreview);
    updatePositionPreview();
});
</script>
@endpush

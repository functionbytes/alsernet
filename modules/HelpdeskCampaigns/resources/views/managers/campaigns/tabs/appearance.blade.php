{{-- Appearance tab --}}
<form method="POST" action="{{ route('helpdesk.campaigns.update', $campaign) }}" id="appearance-form">
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

    {{-- Colores --}}
    <h6 class="fw-semibold mb-1 border-bottom pb-2">Colores</h6>
    <p class="text-muted small mb-3">Paleta de colores para el fondo, texto y elementos de accion</p>
    <div class="row g-3 mb-4">

        <div class="col-12 col-md-4">
            <label class="form-label">Color de fondo</label>
            <div class="input-group">
                <input type="color" name="appearance[background_color]"
                       class="form-control form-control-color"
                       value="{{ $bgColor }}" id="bg-color-input">
                <input type="text" class="form-control" value="{{ $bgColor }}" id="bg-color-text" readonly>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <label class="form-label">Color de texto</label>
            <div class="input-group">
                <input type="color" name="appearance[text_color]"
                       class="form-control form-control-color"
                       value="{{ $textColor }}" id="text-color-input">
                <input type="text" class="form-control" value="{{ $textColor }}" id="text-color-text" readonly>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <label class="form-label">Color primario (botones)</label>
            <div class="input-group">
                <input type="color" name="appearance[primary_color]"
                       class="form-control form-control-color"
                       value="{{ $primaryColor }}" id="primary-color-input">
                <input type="text" class="form-control" value="{{ $primaryColor }}" id="primary-color-text" readonly>
            </div>
        </div>

    </div>

    {{-- Tipografia --}}
    <h6 class="fw-semibold mb-1 border-bottom pb-2">Tipografia</h6>
    <p class="text-muted small mb-3">Tamano y familia de fuente del contenido</p>
    <div class="row g-3 mb-4">

        <div class="col-12 col-md-6">
            <label class="form-label">Tamano de fuente</label>
            <select name="appearance[font_size]" class="form-select">
                <option value="small" {{ $fontSize === 'small' ? 'selected' : '' }}>Pequena (12px)</option>
                <option value="medium" {{ $fontSize === 'medium' ? 'selected' : '' }}>Mediana (14px)</option>
                <option value="large" {{ $fontSize === 'large' ? 'selected' : '' }}>Grande (16px)</option>
                <option value="xlarge" {{ $fontSize === 'xlarge' ? 'selected' : '' }}>Extra grande (18px)</option>
            </select>
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label">Familia de fuente</label>
            <select name="appearance[font_family]" class="form-select">
                <option value="system" {{ ($appearance['font_family'] ?? 'system') === 'system' ? 'selected' : '' }}>Sistema (por defecto)</option>
                <option value="sans-serif" {{ ($appearance['font_family'] ?? '') === 'sans-serif' ? 'selected' : '' }}>Sans Serif</option>
                <option value="serif" {{ ($appearance['font_family'] ?? '') === 'serif' ? 'selected' : '' }}>Serif</option>
                <option value="monospace" {{ ($appearance['font_family'] ?? '') === 'monospace' ? 'selected' : '' }}>Monospace</option>
            </select>
        </div>

    </div>

    {{-- Posicionamiento --}}
    <h6 class="fw-semibold mb-1 border-bottom pb-2">Posicionamiento</h6>
    <p class="text-muted small mb-3">Ubicacion y tamano del contenedor de la campana</p>
    <div class="row g-3 mb-4">

        <div class="col-12 col-md-6">
            <label class="form-label">Posicion en pantalla</label>
            <select name="appearance[position]" class="form-select" id="position-select">
                <option value="top-left" {{ $position === 'top-left' ? 'selected' : '' }}>Superior izquierda</option>
                <option value="top-center" {{ $position === 'top-center' ? 'selected' : '' }}>Superior centro</option>
                <option value="top-right" {{ $position === 'top-right' ? 'selected' : '' }}>Superior derecha</option>
                <option value="center" {{ $position === 'center' ? 'selected' : '' }}>Centro</option>
                <option value="bottom-left" {{ $position === 'bottom-left' ? 'selected' : '' }}>Inferior izquierda</option>
                <option value="bottom-center" {{ $position === 'bottom-center' ? 'selected' : '' }}>Inferior centro</option>
                <option value="bottom-right" {{ $position === 'bottom-right' ? 'selected' : '' }}>Inferior derecha</option>
            </select>
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label">Ancho maximo</label>
            <div class="input-group">
                <input type="number" name="appearance[max_width]" class="form-control"
                       value="{{ $appearance['max_width'] ?? 600 }}" min="300" max="1200" step="50">
                <span class="input-group-text">px</span>
            </div>
            <small class="text-muted">Entre 300 y 1200 px</small>
        </div>

    </div>

    {{-- Bordes y espaciado --}}
    <h6 class="fw-semibold mb-1 border-bottom pb-2">Bordes y espaciado</h6>
    <p class="text-muted small mb-3">Curvatura de esquinas y espacio interno</p>
    <div class="row g-3">

        <div class="col-12 col-md-6">
            <label class="form-label">Radio de borde</label>
            <div class="d-flex align-items-center gap-2">
                <input type="range" name="appearance[border_radius]" class="form-range"
                       min="0" max="50" value="{{ $appearance['border_radius'] ?? 12 }}"
                       id="border-radius-range">
                <output id="border-radius-output">{{ $appearance['border_radius'] ?? 12 }}px</output>
            </div>
            <small class="text-muted">0 = cuadrado, 50 = muy redondeado</small>
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label">Padding interno</label>
            <div class="input-group">
                <input type="number" name="appearance[padding]" class="form-control"
                       value="{{ $appearance['padding'] ?? 20 }}" min="0" max="50" step="5">
                <span class="input-group-text">px</span>
            </div>
        </div>

    </div>

    <div class="d-flex justify-content-between border-top pt-3 mt-4">
        <a href="{{ route('helpdesk.campaigns.edit', ['campaign' => $campaign, 'tab' => 'content']) }}" class="btn btn-light">
            Anterior: Contenido
        </a>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Guardar apariencia</button>
            <a href="{{ route('helpdesk.campaigns.edit', ['campaign' => $campaign, 'tab' => 'conditions']) }}" class="btn btn-light">
                Siguiente: Condiciones
            </a>
        </div>
    </div>
</form>

@push('scripts')
<script>
$(document).ready(function () {
    // Sync color inputs
    [['bg-color', 'appearance[background_color]'],
     ['text-color', 'appearance[text_color]'],
     ['primary-color', 'appearance[primary_color]']
    ].forEach(([id]) => {
        const colorInput = document.getElementById(`${id}-input`);
        const textDisplay = document.getElementById(`${id}-text`);
        if (colorInput && textDisplay) {
            colorInput.addEventListener('input', () => { textDisplay.value = colorInput.value; });
        }
    });

    // Border radius range output
    const rangeInput = document.getElementById('border-radius-range');
    const rangeOutput = document.getElementById('border-radius-output');
    if (rangeInput && rangeOutput) {
        rangeInput.addEventListener('input', () => { rangeOutput.textContent = rangeInput.value + 'px'; });
    }
});
</script>
@endpush

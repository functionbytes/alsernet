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
    <h6 class="fw-semibold mb-1">Colores</h6>
    <p class="text-muted small mb-3">Paleta de colores para el fondo, texto y elementos de acción</p>
    <div class="row g-3 mb-4">

        <div class="col-12 col-md-6 col-xl-4">
            <label class="form-label">Color de fondo</label>
            @include('core::components.color-field', [
                'name' => 'appearance[background_color]',
                'value' => $bgColor,
            ])
        </div>

        <div class="col-12 col-md-6 col-xl-4">
            <label class="form-label">Color de texto</label>
            @include('core::components.color-field', [
                'name' => 'appearance[text_color]',
                'value' => $textColor,
            ])
        </div>

        <div class="col-12 col-md-6 col-xl-4">
            <label class="form-label">Color primario (botones)</label>
            @include('core::components.color-field', [
                'name' => 'appearance[primary_color]',
                'value' => $primaryColor,
            ])
        </div>

    </div>

    {{-- Tipografía --}}
    <h6 class="fw-semibold mb-1">Tipografía</h6>
    <p class="text-muted small mb-3">Tamaño y familia de fuente del contenido</p>
    <div class="row g-3 mb-4">

        <div class="col-12 col-md-6">
            <label class="form-label">Tamaño de fuente</label>
            <select name="appearance[font_size]" class="form-select">
                <option value="small" {{ $fontSize === 'small' ? 'selected' : '' }}>Pequeña (12px)</option>
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
    <h6 class="fw-semibold mb-1">Posicionamiento</h6>
    <p class="text-muted small mb-3">Ubicación y tamaño del contenedor de la campaña</p>
    <div class="row g-3 mb-4">

        <div class="col-12 col-md-6">
            <label class="form-label">Posición en pantalla</label>
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
            <label class="form-label">Ancho máximo</label>
            <div class="input-group">
                <input type="number" name="appearance[max_width]" class="form-control"
                       value="{{ $appearance['max_width'] ?? 600 }}" min="300" max="1200" step="50">
                <span class="input-group-text">px</span>
            </div>
            <small class="text-muted">Entre 300 y 1200 px</small>
        </div>

    </div>

    {{-- Bordes y espaciado --}}
    <h6 class="fw-semibold mb-1">Bordes y espaciado</h6>
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
<script src="{{ asset('modules/helpdeskcampaigns/js/campaign-form-appearance.js') }}?v={{ @filemtime(public_path('modules/helpdeskcampaigns/js/campaign-form-appearance.js')) }}" defer></script>
@endpush

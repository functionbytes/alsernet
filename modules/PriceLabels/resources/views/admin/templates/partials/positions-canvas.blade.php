@if($showVertical)
<div class="col-12" id="section-vertical">
    <div class="card">
        <div class="card-header border-bottom p-3">
            <h5 class="mb-0 fw-bold">Previsualizacion vertical</h5>
            <small class="text-muted">Arrastra cada campo a su posicion ({{ $template->vertical_rows * $template->vertical_columns }} etiquetas por hoja)</small>
        </div>
        <div class="card-body">
            @if(! $template->image_vertical)
                <div class="alert alert-warning mb-0">Sube primero una imagen base vertical y guarda.</div>
            @else
                <div class="pricelabels-zoom mb-2">
                    <label class="form-label small mb-0 me-2">Zoom</label>
                    <input type="range" class="form-range" id="zoom-v" min="50" max="150" value="100">
                    <span class="small text-muted" id="zoom-v-value">100%</span>
                    <button type="button" id="zoom-v-reset" class="btn btn-sm btn-light">Restablecer</button>
                </div>
                <div id="canvas-outer-v" class="pricelabels-canvas-outer">
                    <div id="canvas-v" class="pricelabels-canvas"
                         data-bg="{{ $imageVerticalUrl }}"></div>
                </div>
                <button type="button" id="save-positions-v" class="btn btn-outline-primary mt-3">
                    Guardar posiciones (vertical)
                </button>
                <button type="button" class="btn btn-light mt-3 preview-pdf-btn" data-orientation="vertical">
                    Ver PDF de prueba
                </button>
                @if($showHorizontal && $template->image_horizontal)
                    <button type="button" id="copy-positions-to-h" class="btn btn-light mt-3">
                        Copiar posiciones a horizontal
                    </button>
                @endif
            @endif
        </div>
    </div>
</div>
@endif

@if($showHorizontal)
<div class="col-12" id="section-horizontal">
    <div class="card">
        <div class="card-header border-bottom p-3">
            <h5 class="mb-0 fw-bold">Previsualizacion horizontal</h5>
            <small class="text-muted">Arrastra cada campo a su posicion ({{ $template->horizontal_rows * $template->horizontal_columns }} etiquetas por hoja)</small>
        </div>
        <div class="card-body">
            @if(! $template->image_horizontal)
                <div class="alert alert-warning mb-0">Sube primero una imagen base horizontal y guarda.</div>
            @else
                <div class="pricelabels-zoom mb-2">
                    <label class="form-label small mb-0 me-2">Zoom</label>
                    <input type="range" class="form-range" id="zoom-h" min="50" max="150" value="100">
                    <span class="small text-muted" id="zoom-h-value">100%</span>
                    <button type="button" id="zoom-h-reset" class="btn btn-sm btn-light">Restablecer</button>
                </div>
                <div id="canvas-outer-h" class="pricelabels-canvas-outer">
                    <div id="canvas-h" class="pricelabels-canvas"
                         data-bg="{{ $imageHorizontalUrl }}"></div>
                </div>
                <button type="button" id="save-positions-h" class="btn btn-outline-primary mt-3">
                    Guardar posiciones (horizontal)
                </button>
                <button type="button" class="btn btn-light mt-3 preview-pdf-btn" data-orientation="horizontal">
                    Ver PDF de prueba
                </button>
                @if($showVertical && $template->image_vertical)
                    <button type="button" id="copy-positions-to-v" class="btn btn-light mt-3">
                        Copiar posiciones a vertical
                    </button>
                @endif
            @endif
        </div>
    </div>
</div>
@endif

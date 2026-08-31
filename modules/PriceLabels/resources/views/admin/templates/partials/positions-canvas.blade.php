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
                    <div class="form-check form-switch ms-3 mb-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="grid-overlay-v" checked>
                        <label class="form-check-label small" for="grid-overlay-v">Ver divisiones de la cuadricula</label>
                    </div>
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
                @if(($template->vertical_rows * $template->vertical_columns) > 1)
                    <button type="button" id="apply-grid-v" class="btn btn-light mt-3"
                            title="Toma la posicion de la etiqueta #1 y la recalcula en el resto de la cuadricula">
                        Aplicar cuadricula desde la etiqueta #1
                    </button>
                @endif
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
                    <div class="form-check form-switch ms-3 mb-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="grid-overlay-h" checked>
                        <label class="form-check-label small" for="grid-overlay-h">Ver divisiones de la cuadricula</label>
                    </div>
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
                @if(($template->horizontal_rows * $template->horizontal_columns) > 1)
                    <button type="button" id="apply-grid-h" class="btn btn-light mt-3"
                            title="Toma la posicion de la etiqueta #1 y la recalcula en el resto de la cuadricula">
                        Aplicar cuadricula desde la etiqueta #1
                    </button>
                @endif
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

@if(($showVertical && $template->image_vertical) || ($showHorizontal && $template->image_horizontal))
{{-- Toolbar flotante: aparece al pulsar (sin arrastrar) un campo del lienzo,
     justo encima de el. Reusa los inputs de la tabla "Estilo por campo" como
     unica fuente de verdad; ver bindFloatToolbar() en editor.js. --}}
<div id="pricelabels-float-tb" role="toolbar" aria-label="Acciones rapidas del campo">
    <select class="pricelabels-ftb-select" data-action="font-family" title="Fuente"></select>
    <input type="number" class="pricelabels-ftb-size" data-action="font-size" min="6" max="72" title="Tamano de fuente">
    <div class="pricelabels-ftb-sep"></div>
    <button type="button" class="pricelabels-ftb-btn" data-action="bold" title="Negrita">
        <i class="fas fa-bold"></i>
    </button>
    <button type="button" class="pricelabels-ftb-btn" data-action="italic" title="Cursiva">
        <i class="fas fa-italic"></i>
    </button>
    <button type="button" class="pricelabels-ftb-btn" data-action="align" data-align="left" title="Alinear a la izquierda">
        <i class="fas fa-align-left"></i>
    </button>
    <button type="button" class="pricelabels-ftb-btn" data-action="align" data-align="center" title="Centrar texto">
        <i class="fas fa-align-center"></i>
    </button>
    <button type="button" class="pricelabels-ftb-btn" data-action="align" data-align="right" title="Alinear a la derecha">
        <i class="fas fa-align-right"></i>
    </button>
    <button type="button" class="pricelabels-ftb-btn" data-action="color" title="Color de texto">
        <span class="pricelabels-ftb-color-swatch"></span>
    </button>
    <div class="pricelabels-ftb-sep"></div>
    <button type="button" class="pricelabels-ftb-btn" data-action="front" title="Traer al frente (si otro campo lo tapa)">
        <i class="fas fa-layer-group"></i>
    </button>
    <button type="button" class="pricelabels-ftb-btn" data-action="center-x" title="Centrar horizontalmente en su celda">
        <i class="fas fa-arrows-left-right"></i>
    </button>
    <button type="button" class="pricelabels-ftb-btn" data-action="center-y" title="Centrar verticalmente en su celda">
        <i class="fas fa-arrows-up-down"></i>
    </button>
    <button type="button" class="pricelabels-ftb-btn" data-action="grid" title="Aplicar esta posicion a toda la cuadricula">
        <i class="fas fa-table-cells"></i>
    </button>
    <div class="pricelabels-ftb-sep"></div>
    <button type="button" class="pricelabels-ftb-btn" data-action="settings" title="Mas ajustes: ancho y alto de la caja">
        <i class="fas fa-gear"></i>
    </button>
    <button type="button" class="pricelabels-ftb-btn" data-action="close" title="Cerrar">
        <i class="fas fa-xmark"></i>
    </button>
</div>
@endif

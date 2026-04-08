{{-- Trend chart — full width like analytics "Sesiones y vistas de página" --}}
<div class="row mb-4 g-3">
    <div class="col-12">
        <div class="card w-100">
            <div class="card-header">
                <h4 class="card-title fw-semibold mb-0">Tendencia últimos 7 días</h4>
                <p class="card-subtitle mt-1">Evolución de PQRSF recibidas</p>
            </div>
            <div class="card-body">
                <div id="chart-trend-7days" ></div>
            </div>
        </div>
    </div>
</div>

{{-- Donut + OS lists --}}
<div class="row mb-4 g-3">
    <div class="col-md-4">
        <div class="card w-100 h-100">
            <div class="card-header">
                <h4 class="card-title fw-semibold mb-0">Distribución por estado</h4>
                <p class="card-subtitle mt-1">Atenciones por estado actual</p>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <div id="chart-by-status" style="min-height:260px; width:100%;"></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card w-100 h-100">
            <div class="card-header">
                <h4 class="card-title fw-semibold mb-0">Por tipo de atención</h4>
                <p class="card-subtitle mt-1">Distribución por categoría de PQRSF</p>
            </div>
            <div class="card-body">
                <div id="list-by-type"></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card w-100 h-100">
            <div class="card-header">
                <h4 class="card-title fw-semibold mb-0">Por departamento</h4>
                <p class="card-subtitle mt-1">Distribución por área de gestión</p>
            </div>
            <div class="card-body">
                <div id="list-by-department"></div>
            </div>
        </div>
    </div>
</div>

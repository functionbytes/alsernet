{{-- Map + Countries --}}
<div class="row mb-4 g-3">
    <div class="col-12">
        <div class="card w-100">
            <div class="card-header">
                <h4 class="card-title fw-semibold mb-0">Visitas por país</h4>
                <p class="card-subtitle mt-1">Distribución geográfica de sesiones</p>
            </div>
            <div class="card-body p-0">
                <div class="row g-0">
                    <div class="col-lg-8 position-relative">
                        <div id="visits-map" style="height:400px;width:100%;"></div>
                        <button class="btn btn-sm btn-light border map-reset-btn shadow-sm" id="mapResetBtn" title="Vista mundial">
                            <i class="fas fa-compress-alt text-muted"></i>
                        </button>
                    </div>
                    <div class="col-lg-4 ">
                        <div id="countries-list" style="max-height:400px;overflow-y:auto;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Devices + Browsers + OS + Traffic Sources --}}
<div class="row mb-4 g-3">
    <div class="col-lg-6">
        <div class="card w-100 h-100">
            <div class="card-header">
                <h4 class="card-title fw-semibold mb-0">Dispositivos</h4>
                <p class="card-subtitle mt-1">Por sesiones</p>
            </div>
            <div class="card-body">
                <div id="devices-chart" class="mb-4" style="height:200px;"></div>
                <hr>
                <div id="devices-list"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card w-100 h-100">
            <div class="card-header">
                <h4 class="card-title fw-semibold mb-0">Navegadores</h4>
                <p class="card-subtitle mt-1">Por sesiones</p>
            </div>
            <div class="card-body">
                <div id="browser-chart" class="mb-4" style="height:200px;"></div>
                <hr>
                <div id="browser-list"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card w-100 h-100">
            <div class="card-header">
                <h4 class="card-title fw-semibold mb-0">Sistemas operativos</h4>
                <p class="card-subtitle mt-1">Por sesiones</p>
            </div>
            <div class="card-body">
                <div id="os-list"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card w-100 h-100">
            <div class="card-header">
                <h4 class="card-title fw-semibold mb-0">Fuentes de tráfico</h4>
                <p class="card-subtitle mt-1">Origen del tráfico por sesiones</p>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <div id="traffic-sources-list"></div>
                </div>
                <div id="traffic-sources-pager" class="mt-2"></div>
            </div>
        </div>
    </div>
</div>

{{-- Pages (tabs) --}}
<div class="row g-3">
    <div class="col-12">
        <div class="card w-100">
            <div class="card-header d-md-flex align-items-center">
                <div>
                    <h4 class="card-title fw-semibold mb-0">Páginas</h4>
                    <p class="card-subtitle mt-1">Análisis de páginas visitadas</p>
                </div>
                <div class="ms-auto mt-3 mt-md-0">
                    <ul class="nav nav-tabs border-0" id="pagesTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link rounded active" data-bs-toggle="tab" href="#tab-top-pages" role="tab" aria-selected="true">
                                Más visitadas
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link rounded" data-bs-toggle="tab" href="#tab-landing-pages" role="tab" aria-selected="false" tabindex="-1">
                                Entrada
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link rounded" data-bs-toggle="tab" href="#tab-exit-pages" role="tab" aria-selected="false" tabindex="-1">
                                Salida
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tab-top-pages" role="tabpanel">
                        <div class="table-responsive">
                            <div id="pages-list"></div>
                        </div>
                        <div id="pages-pager" class="px-1 pt-2"></div>
                    </div>
                    <div class="tab-pane fade" id="tab-landing-pages" role="tabpanel">
                        <div class="table-responsive">
                            <div id="landing-pages-list"></div>
                        </div>
                        <div id="landing-pages-pager" class="px-1 pt-2"></div>
                    </div>
                    <div class="tab-pane fade" id="tab-exit-pages" role="tabpanel">
                        <div class="table-responsive">
                            <div id="exit-pages-list"></div>
                        </div>
                        <div id="exit-pages-pager" class="px-1 pt-2"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Referrers --}}
<div class="row mt-4 g-3">
    <div class="col-12">
        <div class="card w-100">
            <div class="card-header">
                <h4 class="card-title fw-semibold mb-0">Fuentes principales</h4>
                <p class="card-subtitle mt-1">Origen del tráfico por sesiones</p>
            </div>
            <div class="card-body">
                <div id="referrers-list"></div>
                <div id="referrers-pager" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

{{-- Search Terms --}}
<div class="row mt-4 g-3">
    <div class="col-12">
        <div class="card w-100">
            <div class="card-header">
                <h4 class="card-title fw-semibold mb-0">Términos de búsqueda</h4>
                <p class="card-subtitle mt-1">Búsquedas internas del sitio</p>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <div id="search-terms-list"></div>
                </div>
                <div id="search-terms-pager" class="mt-2"></div>
            </div>
        </div>
    </div>
</div>

{{-- User Flow --}}
<div class="row mt-4 g-3">
    <div class="col-12">
        <div class="card w-100">
            <div class="card-header">
                <h4 class="card-title fw-semibold mb-0">Flujo de usuarios</h4>
                <p class="card-subtitle mt-1">Páginas de entrada → salida más comunes</p>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <div id="user-flow-list"></div>
                </div>
                <div id="user-flow-pager" class="mt-2"></div>
            </div>
        </div>
    </div>
</div>

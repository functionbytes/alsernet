{{-- KPI Cards — Row 1 --}}
<div class="row mb-4 g-3">

    <div class="col-lg-4 col-md-6">
        <div class="card w-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-8">
                        <h5 class="card-title fw-semibold mb-3">Total</h5>
                        <h4 class="fw-semibold mb-2" id="kpi-total">
                            <span class="spinner-border spinner-border-sm text-muted"></span>
                        </h4>
                        <div id="cmp-total"></div>
                    </div>
                    <div class="col-4">
                        <div class="d-flex justify-content-center">
                            <div id="spark-total"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-6">
        <div class="card w-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-8">
                        <h5 class="card-title fw-semibold mb-3">Recibidos</h5>
                        <h4 class="fw-semibold mb-2" id="kpi-pending">
                            <span class="spinner-border spinner-border-sm text-muted"></span>
                        </h4>
                        <div id="cmp-pending"></div>
                    </div>
                    <div class="col-4">
                        <div class="d-flex justify-content-center">
                            <div id="spark-pending"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-6">
        <div class="card w-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-8">
                        <h5 class="card-title fw-semibold mb-3">En proceso</h5>
                        <h4 class="fw-semibold mb-2" id="kpi-in-process">
                            <span class="spinner-border spinner-border-sm text-muted"></span>
                        </h4>
                        <div id="cmp-in-process"></div>
                    </div>
                    <div class="col-4">
                        <div class="d-flex justify-content-center">
                            <div id="spark-in-process"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- KPI Cards — Row 2 --}}
<div class="row mb-4 g-3">

    <div class="col-lg-4 col-md-6">
        <div class="card w-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-8">
                        <h5 class="card-title fw-semibold mb-3">Tiempo prom. resolución</h5>
                        <h4 class="fw-semibold mb-2" id="kpi-avg-resolution">
                            <span class="spinner-border spinner-border-sm text-muted"></span>
                        </h4>
                        <p class="fs-3 mb-0 text-muted">horas promedio</p>
                    </div>
                    <div class="col-4">
                        <div class="d-flex justify-content-end">
                            <span class="rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center"
                                  style="width:44px;height:44px;">
                                <i class="fas fa-hourglass-half text-primary"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-6">
        <div class="card w-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-8">
                        <h5 class="card-title fw-semibold mb-3">Cumplimiento SLA</h5>
                        <h4 class="fw-semibold mb-2" id="kpi-sla">
                            <span class="spinner-border spinner-border-sm text-muted"></span>
                        </h4>
                        <p class="fs-3 mb-0 text-muted">dentro del plazo</p>
                    </div>
                    <div class="col-4">
                        <div class="d-flex justify-content-end">
                            <span class="rounded-circle bg-success-subtle d-flex align-items-center justify-content-center"
                                  style="width:44px;height:44px;">
                                <i class="fas fa-shield-check text-success"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-6">
        <div class="card w-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-8">
                        <h5 class="card-title fw-semibold mb-3">Satisfacción promedio</h5>
                        <h4 class="fw-semibold mb-2" id="kpi-satisfaction">
                            <span class="spinner-border spinner-border-sm text-muted"></span>
                        </h4>
                        <p class="fs-3 mb-0 text-muted">calificación ciudadanos</p>
                    </div>
                    <div class="col-4">
                        <div class="d-flex justify-content-end">
                            <span class="rounded-circle bg-warning-subtle d-flex align-items-center justify-content-center"
                                  style="width:44px;height:44px;">
                                <i class="fas fa-star text-warning"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

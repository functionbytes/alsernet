<div class="row g-3">
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded d-flex align-items-center justify-content-center flex-shrink-0 bg-success bg-opacity-10" style="width:48px;height:48px">
                        <i class="fas fa-dollar-sign fa-lg text-success"></i>
                    </div>
                    <div>
                        <div class="fs-5 fw-bold">${{ number_format($totalRevenue, 2) }}</div>
                        <div class="small text-muted">Ingresos totales</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded d-flex align-items-center justify-content-center flex-shrink-0 bg-primary bg-opacity-10" style="width:48px;height:48px">
                        <i class="fas fa-receipt fa-lg text-primary"></i>
                    </div>
                    <div>
                        <div class="fs-5 fw-bold">{{ number_format($totalOrders) }}</div>
                        <div class="small text-muted">Total ordenes</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded d-flex align-items-center justify-content-center flex-shrink-0 bg-info bg-opacity-10" style="width:48px;height:48px">
                        <i class="fas fa-users fa-lg text-info"></i>
                    </div>
                    <div>
                        <div class="fs-5 fw-bold">{{ number_format($totalCustomers) }}</div>
                        <div class="small text-muted">Total clientes</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded d-flex align-items-center justify-content-center flex-shrink-0 bg-warning bg-opacity-10" style="width:48px;height:48px">
                        <i class="fas fa-user-plus fa-lg text-warning"></i>
                    </div>
                    <div>
                        <div class="fs-5 fw-bold">{{ number_format($newCustomers) }}</div>
                        <div class="small text-muted">Nuevos clientes</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

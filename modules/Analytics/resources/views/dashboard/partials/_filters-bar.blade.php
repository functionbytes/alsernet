{{-- Filters Bar --}}
<div class="card card-body mb-4 border-0 shadow-sm" >
    <div class="row align-items-center g-3">

        {{-- Realtime indicator --}}
        <div class="col-md-auto">
            <div class="d-flex align-items-center gap-3 px-3 py-2 rounded-1 p-2 bg-danger-subtle text-white" >
                <div class="position-relative d-flex align-items-center justify-content-center rounded-circle">
                    <span class="position-absolute top-0 end-0 rounded-circle bg-success border border-white"></span>
                </div>
                <div>
                    <div class="d-flex align-items-baseline gap-1 lh-1 mb-1">
                        <span class="fw-bold " id="realtime-count">
                            <span class="spinner-border" ></span>
                        </span>
                        <span class="small fw-semibold">en línea</span>
                    </div>
                    <div class="lh-1" id="realtime-updated"></div>
                </div>
            </div>
        </div>

        {{-- Divider --}}
        <div class="col-md-auto d-none d-md-block">
            <div style="width:1px;height:36px;background:#e9ecef;"></div>
        </div>

        {{-- Period nav-pills --}}
        <div class="col-md">
            <ul class="nav nav-pills gap-1 flex-nowrap overflow-auto" id="rangePills">
                <li class="nav-item flex-shrink-0">
                    <a class="nav-link py-1 px-3 small fw-semibold {{ $range === 'today'        ? 'active' : 'text-muted' }}"
                       href="#" data-range="today">Hoy</a>
                </li>
                <li class="nav-item flex-shrink-0">
                    <a class="nav-link py-1 px-3 small fw-semibold {{ $range === 'last_7_days'  ? 'active' : 'text-muted' }}"
                       href="#" data-range="last_7_days">7 días</a>
                </li>
                <li class="nav-item flex-shrink-0">
                    <a class="nav-link py-1 px-3 small fw-semibold {{ $range === 'last_30_days' ? 'active' : 'text-muted' }}"
                       href="#" data-range="last_30_days">30 días</a>
                </li>
                <li class="nav-item flex-shrink-0">
                    <a class="nav-link py-1 px-3 small fw-semibold {{ $range === 'this_month'   ? 'active' : 'text-muted' }}"
                       href="#" data-range="this_month">Este mes</a>
                </li>
                <li class="nav-item flex-shrink-0">
                    <a class="nav-link py-1 px-3 small fw-semibold {{ $range === 'last_month'   ? 'active' : 'text-muted' }}"
                       href="#" data-range="last_month">Mes anterior</a>
                </li>
                <li class="nav-item flex-shrink-0">
                    <a class="nav-link py-1 px-3 small fw-semibold {{ $range === 'this_year'    ? 'active' : 'text-muted' }}"
                       href="#" data-range="this_year">Este año</a>
                </li>
            </ul>
        </div>

        {{-- Actions --}}
        <div class="col-md-auto d-flex align-items-center gap-2">
            <div class="dropdown">
                <a href="javascript:void(0)" class="d-flex align-items-center justify-content-center rounded-circle text-muted"
                   style="width:30px;height:30px;background:#f5f6f8;"
                   data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-ellipsis-vertical"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="javascript:void(0)" id="refreshBtn2">Actualizar datos</a>
                    </li>
                    @can('analytics.dashboard.export')
                    <li>
                        <a class="dropdown-item" href="javascript:void(0)"
                           data-bs-toggle="modal" data-bs-target="#exportModal">Exportar datos</a>
                    </li>
                    @endcan
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="javascript:void(0)" onclick="window.print()">Imprimir</a>
                    </li>
                </ul>
            </div>
        </div>

    </div>
</div>

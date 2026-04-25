<?php
use Illuminate\Contracts\Auth\Access\Gate;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

$__env->startSection('content'); ?>

    <?php echo $__env->make('core::components.card', ['title' => 'Dashboard'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="widget-content">

        
        <div class="card card-body mb-4 border-0 shadow-sm">
            <div class="row align-items-center g-3">
                <div class="col-md-auto">
                    <div class="d-flex align-items-center gap-3 px-3 py-2 rounded-1 p-2 bg-danger-subtle text-white">
                        <div class="position-relative d-flex align-items-center justify-content-center rounded-circle" style="width:24px;height:24px;">
                            <span class="position-absolute top-0 end-0 rounded-circle bg-success border border-white pulse-dot"></span>
                        </div>
                        <div>
                            <div class="d-flex align-items-baseline gap-1 lh-1 mb-1">
                                <span class="fw-bold" id="health-status-label">—</span>
                                <span class="small fw-semibold">sistema</span>
                            </div>
                            <div class="lh-1" id="health-updated" style="font-size:0.7rem;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-auto d-none d-md-block">
                    <div style="width:1px;height:36px;background:#e9ecef;"></div>
                </div>
                <div class="col-md">
                    <ul class="nav nav-pills gap-1 flex-nowrap overflow-auto" id="rangePills">
                        <li class="nav-item flex-shrink-0">
                            <a class="nav-link py-1 px-3 small fw-semibold active" href="#" data-days="7">7 días</a>
                        </li>
                        <li class="nav-item flex-shrink-0">
                            <a class="nav-link py-1 px-3 small fw-semibold text-muted" href="#" data-days="14">14 días</a>
                        </li>
                        <li class="nav-item flex-shrink-0">
                            <a class="nav-link py-1 px-3 small fw-semibold text-muted" href="#" data-days="30">30 días</a>
                        </li>
                    </ul>
                </div>
                <div class="col-md-auto d-flex align-items-center gap-2">
                    <span class="text-muted" style="font-size:0.72rem;" id="global-last-updated"></span>
                    <button class="btn btn-sm btn-light" id="btn-refresh-all" title="Actualizar (R)">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <div class="dropdown">
                        <a href="javascript:void(0)" class="d-flex align-items-center justify-content-center rounded-circle text-muted"
                           style="width:30px;height:30px;background:#f5f6f8;"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-ellipsis-vertical"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="window.print()">Imprimir</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        
        <div id="alert-banner" class="d-none mb-4"></div>

        
        <div class="row mb-4 g-3" id="dashboard-kpis">

            <div class="col-lg-3 col-md-6">
                <div class="card w-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h5 class="card-title fw-semibold mb-3">Reseñas totales</h5>
                                <h4 class="fw-semibold mb-2 kpi-value" id="kpi-reviews-total"><div class="skeleton skeleton-title"></div></h4>
                                <div id="cmp-reviews"></div>
                            </div>
                            <div class="col-4">
                                <div class="d-flex justify-content-center"><div id="spark-reviews"></div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card w-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h5 class="card-title fw-semibold mb-3">PQRSF pendientes</h5>
                                <h4 class="fw-semibold mb-2 kpi-value" id="kpi-attention-pending"><div class="skeleton skeleton-title"></div></h4>
                                <div id="cmp-attention"></div>
                            </div>
                            <div class="col-4">
                                <div class="d-flex justify-content-center"><div id="spark-attention"></div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card w-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h5 class="card-title fw-semibold mb-3">Formularios hoy</h5>
                                <h4 class="fw-semibold mb-2 kpi-value" id="kpi-forms-today"><div class="skeleton skeleton-title"></div></h4>
                                <div id="cmp-forms"></div>
                            </div>
                            <div class="col-4">
                                <div class="d-flex justify-content-center"><div id="spark-forms"></div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card w-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h5 class="card-title fw-semibold mb-3">Calificación promedio</h5>
                                <h4 class="fw-semibold mb-2 kpi-value" id="kpi-reviews-avg"><div class="skeleton skeleton-title"></div></h4>
                                <p class="fs-3 mb-0 text-muted">de 5 estrellas</p>
                            </div>
                            <div class="col-4">
                                <div class="d-flex justify-content-end">
                                    <span class="rounded-circle bg-warning-subtle d-flex align-items-center justify-content-center" id="icon-reviews-avg" style="width:44px;height:44px;">
                                        <i class="fas fa-star text-warning" id="icon-reviews-avg-i"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card w-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h5 class="card-title fw-semibold mb-3">Sin leer</h5>
                                <h4 class="fw-semibold mb-2 kpi-value" id="kpi-forms-unread"><div class="skeleton skeleton-title"></div></h4>
                                <p class="fs-3 mb-0 text-muted">formularios</p>
                            </div>
                            <div class="col-4">
                                <div class="d-flex justify-content-end">
                                    <span class="rounded-circle bg-danger-subtle d-flex align-items-center justify-content-center" id="icon-forms-unread" style="width:44px;height:44px;">
                                        <i class="fas fa-envelope text-danger" id="icon-forms-unread-i"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card w-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h5 class="card-title fw-semibold mb-3">Tiempo promedio</h5>
                                <h4 class="fw-semibold mb-2 kpi-value" id="kpi-sla-avg"><div class="skeleton skeleton-title"></div></h4>
                                <p class="fs-3 mb-0 text-muted">horas resolución</p>
                            </div>
                            <div class="col-4">
                                <div class="d-flex justify-content-end">
                                    <span class="rounded-circle bg-info-subtle d-flex align-items-center justify-content-center" id="icon-sla-avg" style="width:44px;height:44px;">
                                        <i class="fas fa-clock text-info" id="icon-sla-avg-i"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card w-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h5 class="card-title fw-semibold mb-3">Cumplimiento SLA</h5>
                                <h4 class="fw-semibold mb-2 kpi-value" id="kpi-sla-pct"><div class="skeleton skeleton-title"></div></h4>
                                <p class="fs-3 mb-0 text-muted">últimos 30 días</p>
                            </div>
                            <div class="col-4">
                                <div class="d-flex justify-content-end">
                                    <span class="rounded-circle bg-success-subtle d-flex align-items-center justify-content-center" id="icon-sla-pct" style="width:44px;height:44px;">
                                        <i class="fas fa-check-circle text-success" id="icon-sla-pct-i"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card w-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h5 class="card-title fw-semibold mb-3">Estado del sistema</h5>
                                <h4 class="fw-semibold mb-2 kpi-value" id="kpi-system-status"><div class="skeleton skeleton-title"></div></h4>
                                <p class="fs-3 mb-0 text-muted" id="kpi-system-detail">Verificando...</p>
                            </div>
                            <div class="col-4">
                                <div class="d-flex justify-content-end">
                                    <span class="rounded-circle bg-success-subtle d-flex align-items-center justify-content-center" id="system-icon-bg" style="width:44px;height:44px;">
                                        <i class="fas fa-server text-success" id="system-icon"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        
        <div class="row mb-4 g-3">
            <div class="col-12">
                <div class="card w-100">
                    <div class="card-header">
                        <h4 class="card-title fw-semibold mb-0">Actividad del sistema</h4>
                        <p class="card-subtitle mt-1">Tendencia diaria según rango seleccionado</p>
                    </div>
                    <div class="card-body">
                        <div id="activity-chart" style="height:300px;">
                            <div class="d-flex align-items-center justify-content-center h-100">
                                <div class="spinner-border spinner-border-sm text-secondary"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="row mb-4 g-3">
            <div class="col-lg-5">
                <div class="card w-100 h-100">
                    <div class="card-header">
                        <h4 class="card-title fw-semibold mb-0">Distribución PQRSF</h4>
                        <p class="card-subtitle mt-1">Estado actual de peticiones</p>
                    </div>
                    <div class="card-body">
                        <div id="distribution-chart" style="height:260px;">
                            <div class="d-flex align-items-center justify-content-center h-100">
                                <div class="spinner-border spinner-border-sm text-secondary"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card w-100 h-100">
                    <div class="card-header d-md-flex align-items-center">
                        <div>
                            <h4 class="card-title fw-semibold mb-0">Últimas reseñas</h4>
                            <p class="card-subtitle mt-1">Con calificación y estado de respuesta</p>
                        </div>
                        <div class="ms-auto mt-3 mt-md-0">
                            <a href="<?php echo e(route('reviews.index')); ?>" class="btn btn-sm btn-outline-primary">Ver todas</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="latest-reviews-list">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="skeleton skeleton-circle me-3"></div>
                                    <div>
                                        <div class="skeleton skeleton-text mb-1" style="width:180px;"></div>
                                        <div class="skeleton skeleton-text" style="width:120px;"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="skeleton skeleton-circle me-3"></div>
                                    <div>
                                        <div class="skeleton skeleton-text mb-1" style="width:160px;"></div>
                                        <div class="skeleton skeleton-text" style="width:100px;"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="skeleton skeleton-circle me-3"></div>
                                    <div>
                                        <div class="skeleton skeleton-text mb-1" style="width:200px;"></div>
                                        <div class="skeleton skeleton-text" style="width:140px;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="row mb-4 g-3" id="queue-stats-section">
            <div class="col-12">
                <div class="card w-100">
                    <div class="card-header d-md-flex align-items-center">
                        <div>
                            <h4 class="card-title fw-semibold mb-0">Cola de trabajos</h4>
                            <p class="card-subtitle mt-1">Estado de Horizon y trabajos pendientes</p>
                        </div>
                        <div class="ms-auto mt-3 mt-md-0 d-flex align-items-center gap-2">
                            <span class="text-muted" style="font-size:.72rem;" id="queue-last-updated"></span>
                            <a href="/horizon" target="_blank" class="btn btn-sm btn-light" title="Abrir Horizon"><i class="fas fa-external-link-alt"></i></a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3 col-sm-6">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="p-2 bg-primary-subtle rounded-2 d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                                        <i class="fas fa-circle text-primary" id="horizon-icon" style="font-size:.65rem;"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-semibold" id="horizon-label">—</h6>
                                        <p class="fs-3 mb-0 text-muted">Horizon</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="p-2 bg-primary-subtle rounded-2 d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                                        <i class="fas fa-exclamation-triangle text-primary"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-semibold" id="queue-failed">—</h6>
                                        <p class="fs-3 mb-0 text-muted">Fallidos</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="p-2 bg-primary-subtle rounded-2 d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                                        <i class="fas fa-clock text-primary"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-semibold" id="queue-pending">—</h6>
                                        <p class="fs-3 mb-0 text-muted">Pendientes</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="text-muted mb-2" style="font-size:0.82rem;">Por cola</div>
                                <div id="queue-per-queue" class="d-flex flex-wrap gap-1"><span class="text-muted">—</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="card w-100">
                    <div class="card-header d-md-flex align-items-center">
                        <div>
                            <h4 class="card-title fw-semibold mb-0">Actividad reciente</h4>
                            <p class="card-subtitle mt-1">Últimas acciones en el sistema</p>
                        </div>
                        <div class="ms-auto mt-3 mt-md-0">
                            <button class="btn btn-sm btn-light" id="btn-refresh-activity" title="Actualizar"><i class="fas fa-sync-alt"></i></button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="recent-activity-list">
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div class="d-flex align-items-center">
                                    <div class="skeleton skeleton-circle me-3"></div>
                                    <div><div class="skeleton skeleton-text mb-1" style="width:180px;"></div><div class="skeleton skeleton-text" style="width:120px;"></div></div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div class="d-flex align-items-center">
                                    <div class="skeleton skeleton-circle me-3"></div>
                                    <div><div class="skeleton skeleton-text mb-1" style="width:160px;"></div><div class="skeleton skeleton-text" style="width:100px;"></div></div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="skeleton skeleton-circle me-3"></div>
                                    <div><div class="skeleton skeleton-text mb-1" style="width:200px;"></div><div class="skeleton skeleton-text" style="width:140px;"></div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card w-100 h-100">
                    <div class="card-header">
                        <h4 class="card-title fw-semibold mb-0">Accesos rápidos</h4>
                        <p class="card-subtitle mt-1">Navegación rápida por módulos</p>
                    </div>
                    <div class="card-body">
                        <div id="quick-links-list">
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (auth()->user()?->hasAnyRole(['admin', 'super-admin', 'super-settings', 'settings', 'managers'])) { ?>
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (Route::has('attention.index')) { ?>
                                <a href="<?php echo e(route('attention.index')); ?>" class="d-flex align-items-center justify-content-between mb-4 text-decoration-none text-dark">
                                    <div class="d-flex align-items-center">
                                        <div class="p-2 bg-primary-subtle rounded-2 d-flex align-items-center justify-content-center me-3" style="width:36px;height:36px;"><i class="fas fa-clipboard-list text-primary"></i></div>
                                        <div><h6 class="mb-0 fw-semibold">PQRSF</h6><p class="fs-3 mb-0 text-muted">Gestiona peticiones</p></div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2"><span class="badge bg-warning text-dark d-none" id="badge-attention">0</span><i class="fas fa-chevron-right text-muted"></i></div>
                                </a>
                                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (Route::has('reviews.index')) { ?>
                                <a href="<?php echo e(route('reviews.index')); ?>" class="d-flex align-items-center justify-content-between mb-4 text-decoration-none text-dark">
                                    <div class="d-flex align-items-center">
                                        <div class="p-2 bg-primary-subtle rounded-2 d-flex align-items-center justify-content-center me-3" style="width:36px;height:36px;"><i class="fas fa-star text-primary"></i></div>
                                        <div><h6 class="mb-0 fw-semibold">Reseñas</h6><p class="fs-3 mb-0 text-muted">Ver calificaciones</p></div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2"><span class="badge bg-danger d-none" id="badge-reviews">0</span><i class="fas fa-chevron-right text-muted"></i></div>
                                </a>
                                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (Route::has('forms.index')) { ?>
                                <a href="<?php echo e(route('forms.index')); ?>" class="d-flex align-items-center justify-content-between mb-4 text-decoration-none text-dark">
                                    <div class="d-flex align-items-center">
                                        <div class="p-2 bg-primary-subtle rounded-2 d-flex align-items-center justify-content-center me-3" style="width:36px;height:36px;"><i class="fas fa-wpforms text-primary"></i></div>
                                        <div><h6 class="mb-0 fw-semibold">Formularios</h6><p class="fs-3 mb-0 text-muted">Ver submissions</p></div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2"><span class="badge bg-success d-none" id="badge-forms">0</span><i class="fas fa-chevron-right text-muted"></i></div>
                                </a>
                                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (Route::has('analytics.dashboard')) { ?>
                                <a href="<?php echo e(route('analytics.dashboard')); ?>" class="d-flex align-items-center justify-content-between text-decoration-none text-dark">
                                    <div class="d-flex align-items-center">
                                        <div class="p-2 bg-primary-subtle rounded-2 d-flex align-items-center justify-content-center me-3" style="width:36px;height:36px;"><i class="fas fa-chart-line text-primary"></i></div>
                                        <div><h6 class="mb-0 fw-semibold">Analytics</h6><p class="fs-3 mb-0 text-muted">Tráfico web</p></div>
                                    </div>
                                    <i class="fas fa-chevron-right text-muted"></i>
                                </a>
                                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (auth()->user()?->hasAnyRole(['callcenters', 'supports'])) { ?>
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (Route::has('attention.index')) { ?>
                                <a href="<?php echo e(route('attention.index')); ?>" class="d-flex align-items-center justify-content-between text-decoration-none text-dark">
                                    <div class="d-flex align-items-center">
                                        <div class="p-2 bg-primary-subtle rounded-2 d-flex align-items-center justify-content-center me-3" style="width:36px;height:36px;"><i class="fas fa-clipboard-list text-primary"></i></div>
                                        <div><h6 class="mb-0 fw-semibold">PQRSF</h6><p class="fs-3 mb-0 text-muted">Gestiona peticiones</p></div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2"><span class="badge bg-warning text-dark d-none" id="badge-attention-cc">0</span><i class="fas fa-chevron-right text-muted"></i></div>
                                </a>
                                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <?php if (app(Gate::class)->check('settings.view')) { ?>
        <div class="row g-3 mb-4" id="security-section">
            <div class="col-12">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-shield-halved text-primary me-2"></i>Seguridad del sistema</h6>
                    <a href="<?php echo e(route('settings.auth.audit.login-attempts')); ?>" class="btn btn-sm btn-link">Ver auditoría completa <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card w-100">
                    <div class="card-body text-center">
                        <h5 class="card-title fw-semibold mb-3">Security Score</h5>
                        <div id="security-score-chart" style="height:140px;"></div>
                        <p class="fs-3 mb-0 text-muted" id="security-score-label">Calculando...</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card w-100">
                    <div class="card-body text-center">
                        <h5 class="card-title fw-semibold mb-3">2FA Activado</h5>
                        <div id="twofa-chart" style="height:140px;"></div>
                        <p class="fs-3 mb-0 text-muted" id="twofa-label">Calculando...</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card w-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h5 class="card-title fw-semibold mb-3">Sesiones activas</h5>
                                <h4 class="fw-semibold mb-2" id="kpi-active-sessions"><div class="skeleton skeleton-title"></div></h4>
                                <p class="fs-3 mb-0 text-muted">últimos 15 min</p>
                            </div>
                            <div class="col-4">
                                <div class="d-flex justify-content-end">
                                    <span class="rounded-circle bg-info-subtle d-flex align-items-center justify-content-center" style="width:44px;height:44px;"><i class="fas fa-users text-info"></i></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card w-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h5 class="card-title fw-semibold mb-3">Fallos 24h</h5>
                                <h4 class="fw-semibold mb-2" id="kpi-failed-24h"><div class="skeleton skeleton-title"></div></h4>
                                <p class="fs-3 mb-0 text-muted">intentos de login</p>
                            </div>
                            <div class="col-4">
                                <div class="d-flex justify-content-end">
                                    <span class="rounded-circle bg-danger-subtle d-flex align-items-center justify-content-center" style="width:44px;height:44px;"><i class="fas fa-ban text-danger"></i></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card w-100">
                    <div class="card-header">
                        <h4 class="card-title fw-semibold mb-0">Intentos de login</h4>
                        <p class="card-subtitle mt-1">Últimas 24 horas</p>
                    </div>
                    <div class="card-body">
                        <div id="login-timeline-chart" style="height:260px;"></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card w-100 h-100">
                    <div class="card-header">
                        <h4 class="card-title fw-semibold mb-0">IPs sospechosas</h4>
                        <p class="card-subtitle mt-1">Top fallos últimas 24h</p>
                    </div>
                    <div class="card-body">
                        <div id="top-failed-ips-list">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="skeleton skeleton-circle me-3"></div>
                                    <div><div class="skeleton skeleton-text mb-1" style="width:120px;"></div><div class="skeleton skeleton-text" style="width:80px;"></div></div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="skeleton skeleton-circle me-3"></div>
                                    <div><div class="skeleton skeleton-text mb-1" style="width:140px;"></div><div class="skeleton skeleton-text" style="width:90px;"></div></div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="skeleton skeleton-circle me-3"></div>
                                    <div><div class="skeleton skeleton-text mb-1" style="width:110px;"></div><div class="skeleton skeleton-text" style="width:70px;"></div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php } ?>

        
        <?php echo $__env->make('analytics::components.dashboard-widget', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        
        <?php echo $__env->make('cookie::components.consent-widget', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    </div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('css'); ?>
<style>
    .skeleton {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: skeleton-loading 1.5s infinite;
        border-radius: 4px;
    }
    @keyframes skeleton-loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
    .skeleton-text { height: 12px; }
    .skeleton-title { height: 32px; width: 80px; }
    .skeleton-circle { width: 36px; height: 36px; border-radius: 8px; }
    .pulse-dot {
        width: 10px; height: 10px;
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.4; }
    }
    .alert-banner-item { transition: all 0.2s ease; }
    .alert-banner-item:hover { transform: translateX(4px); }
    .review-stars { letter-spacing: 2px; }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.54.1/dist/apexcharts.min.js"></script>
<script>
(function () {
    'use strict';

    const fmt = n => new Intl.NumberFormat('es-ES').format(n);
    const emptyState = msg => `<div class="text-center py-4 text-muted"><i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i><small>${msg}</small></div>`;

    let activityChart = null, distChart = null, securityScoreChart = null, twofaChart = null, loginTimelineChart = null;
    let sparkCharts = {};
    let lastUpdated = null;
    let selectedDays = 7;

    const subjectRoutes = {
        'Attention': '<?php echo e(Route::has('attention.index') ? route('attention.index') : ''); ?>',
        'Review': '<?php echo e(Route::has('reviews.index') ? route('reviews.index') : ''); ?>',
        'FormSubmission': '<?php echo e(Route::has('forms.index') ? route('forms.index') : ''); ?>',
        'User': '<?php echo e(Route::has('users.index') ? route('users.index') : ''); ?>',
    };

    function cmpBadge(current, previous, invert) {
        if (!previous || previous === 0) return '';
        const pct  = ((current - previous) / previous * 100).toFixed(1);
        const up   = invert ? parseFloat(pct) < 0 : parseFloat(pct) > 0;
        const icon = up ? 'fa-arrow-up' : 'fa-arrow-down';
        const bg   = up ? 'bg-success-subtle' : 'bg-danger-subtle';
        const txt  = up ? 'text-success'      : 'text-danger';
        const sign = parseFloat(pct) > 0 ? '+' : '';
        return `<div class="d-flex align-items-center">
            <span class="me-1 rounded-circle ${bg} d-flex align-items-center justify-content-center" style="width:20px;height:20px;">
                <i class="fas ${icon} ${txt}" style="font-size:0.6rem;"></i>
            </span>
            <p class="text-dark me-1 fs-3 mb-0">${sign}${Math.abs(pct)}%</p>
            <p class="fs-3 mb-0 text-muted">vs anterior</p>
        </div>`;
    }

    function starRating(rating) {
        const map = {'ONE':1,'TWO':2,'THREE':3,'FOUR':4,'FIVE':5};
        const n = map[rating] || 0;
        let html = '<span class="review-stars">';
        for (let i = 1; i <= 5; i++) {
            html += i <= n ? '<i class="fas fa-star text-warning"></i>' : '<i class="far fa-star text-muted"></i>';
        }
        html += '</span>';
        return html;
    }

    // ─── Timestamp global ────────────────────────────────────────────────
    function updateGlobalTimestamp() {
        if (!lastUpdated) return;
        const diff = Math.floor((Date.now() - lastUpdated) / 60000);
        $('#global-last-updated').text(diff === 0 ? 'Actualizado ahora' : diff === 1 ? 'Hace 1 min' : 'Hace ' + diff + ' min');
    }
    setInterval(updateGlobalTimestamp, 60000);

    // ─── Alerts ──────────────────────────────────────────────────────────
    function loadAlerts() {
        return $.getJSON('<?php echo e(route('core.dashboard.alerts')); ?>')
            .done(function (data) {
                var alerts = data.alerts || [];
                var $banner = $('#alert-banner');
                if (!alerts.length) { $banner.addClass('d-none').empty(); return; }
                var html = '<div class="d-flex flex-column gap-2">';
                alerts.forEach(function (a) {
                    var bg = a.severity === 'danger' ? 'bg-danger-subtle border-danger' : 'bg-warning-subtle border-warning';
                    var txt = a.severity === 'danger' ? 'text-danger' : 'text-warning';
                    var icon = a.severity === 'danger' ? 'fa-exclamation-circle' : 'fa-exclamation-triangle';
                    html += `<a href="${a.link}" class="alert-banner-item d-flex align-items-center gap-3 p-3 rounded-3 border-start border-4 ${bg} text-decoration-none">
                        <div class="p-2 bg-primary-subtle rounded-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width:36px;height:36px;">
                            <i class="fas ${icon} text-primary"></i>
                        </div>
                        <div class="flex-grow-1"><span class="fw-semibold small ${txt}">${escHtml(a.message)}</span></div>
                        <i class="fas fa-arrow-right ${txt} flex-shrink-0"></i>
                    </a>`;
                });
                html += '</div>';
                $banner.html(html).removeClass('d-none');
            })
            .fail(function () { $('#alert-banner').addClass('d-none').empty(); });
    }

    // ─── KPIs ────────────────────────────────────────────────────────────
    function loadKpis() {
        return $.getJSON('<?php echo e(route('core.dashboard.kpis')); ?>')
            .done(function (data) {
                lastUpdated = Date.now();
                updateGlobalTimestamp();
                var reviews = data.reviews || {};
                var attention = data.attention || {};
                var forms = data.forms || {};

                $('#kpi-reviews-total').text(fmt(reviews.total ?? 0));
                $('#cmp-reviews').html(cmpBadge(reviews.total ?? 0, reviews.total_yesterday ?? 0));

                var pending = attention.pending ?? 0;
                $('#kpi-attention-pending').text(fmt(pending)).toggleClass('text-danger', pending > 5);
                $('#cmp-attention').html(cmpBadge(pending, attention.pending_yesterday ?? 0));

                var todayForms = forms.submissions_today ?? 0;
                var projected = forms.projected_today ?? 0;
                var formsHtml = fmt(todayForms);
                if (projected > 0 && todayForms < projected) {
                    formsHtml += ` <small class="text-muted">/ ~${fmt(projected)}</small>`;
                }
                $('#kpi-forms-today').html(formsHtml);
                $('#cmp-forms').html(cmpBadge(todayForms, forms.submissions_yesterday ?? 0));

                var avg = reviews.avg_rating ? Number(reviews.avg_rating).toFixed(1) : '—';
                $('#kpi-reviews-avg').text(avg + ' ★');
                var criticalReviews = (reviews.one_star_unreplied ?? 0) + (reviews.two_star_unreplied ?? 0);
                if (criticalReviews > 0) {
                    $('#icon-reviews-avg').attr('class', 'rounded-circle bg-danger-subtle d-flex align-items-center justify-content-center');
                    $('#icon-reviews-avg-i').attr('class', 'fas fa-star text-danger');
                }

                var unread = forms.unread ?? 0;
                $('#kpi-forms-unread').text(fmt(unread)).toggleClass('text-danger', unread > 0);

                // SLA
                var slaAvg = attention.avg_resolution_hours ?? 0;
                $('#kpi-sla-avg').text(slaAvg + 'h');
                var slaColor = slaAvg < 48 ? 'success' : slaAvg < 120 ? 'warning' : 'danger';
                $('#icon-sla-avg').attr('class', 'rounded-circle bg-' + slaColor + '-subtle d-flex align-items-center justify-content-center');
                $('#icon-sla-avg-i').attr('class', 'fas fa-clock text-' + slaColor);

                var slaPct = attention.sla_compliance_pct ?? 100;
                $('#kpi-sla-pct').text(slaPct + '%');
                var slaPctColor = slaPct >= 80 ? 'success' : slaPct >= 50 ? 'warning' : 'danger';
                $('#icon-sla-pct').attr('class', 'rounded-circle bg-' + slaPctColor + '-subtle d-flex align-items-center justify-content-center');
                $('#icon-sla-pct-i').attr('class', 'fas fa-check-circle text-' + slaPctColor);

                // Badges
                $('#badge-attention, #badge-attention-cc').toggleClass('d-none', pending === 0).text(pending);
                $('#badge-reviews').toggleClass('d-none', criticalReviews === 0).text(criticalReviews);
                $('#badge-forms').toggleClass('d-none', unread === 0).text(unread);
            })
            .fail(function () {
                $('.kpi-value').text('—');
                toastr.error('No se pudieron cargar los KPIs');
            });
    }

    // ─── Trends ──────────────────────────────────────────────────────────
    function loadTrends() {
        return $.getJSON('<?php echo e(route('core.dashboard.trends')); ?>', { days: selectedDays })
            .done(function (data) {
                var labels = data.labels || [];
                renderSparklines(labels, data.reviews || [], data.attention || [], data.forms || []);
                renderActivityChart(labels, data.reviews || [], data.attention || [], data.forms || []);
            })
            .fail(function () {
                $('#activity-chart').html(emptyState('Sin datos de actividad'));
            });
    }

    function sparkCfg(data, color, type) {
        return {
            series: [{ data }],
            chart: { type, height: 70, width: 70, sparkline: { enabled: true }, animations: { enabled: false }, fontFamily: 'inherit' },
            colors: [color],
            stroke: { curve: 'smooth', width: 2 },
            fill: type === 'area' ? { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.02 } } : { type: 'solid' },
            tooltip: { fixed: { enabled: false }, x: { show: false }, y: { title: { formatter: () => '' } } },
            plotOptions: { bar: { borderRadius: 2, columnWidth: '60%' } },
        };
    }

    function renderSparklines(labels, reviews, attention, forms) {
        const sparks = {
            '#spark-reviews':   { data: reviews,   color: '#b10100', type: 'area' },
            '#spark-attention': { data: attention, color: '#333333', type: 'bar'  },
            '#spark-forms':     { data: forms,     color: '#7b0000', type: 'area' },
        };
        Object.entries(sparks).forEach(([sel, cfg]) => {
            const el = document.querySelector(sel);
            if (!el) return;
            if (sparkCharts[sel]) { sparkCharts[sel].destroy(); }
            sparkCharts[sel] = new ApexCharts(el, sparkCfg(cfg.data, cfg.color, cfg.type));
            sparkCharts[sel].render();
        });
    }

    function renderActivityChart(labels, reviews, attention, forms) {
        if (activityChart) { activityChart.destroy(); activityChart = null; }
        $('#activity-chart').html('');
        if (!labels.length) { $('#activity-chart').html(emptyState('Sin datos')); return; }
        activityChart = new ApexCharts(document.querySelector('#activity-chart'), {
            series: [{ name: 'Reseñas', data: reviews }, { name: 'PQRSF', data: attention }, { name: 'Formularios', data: forms }],
            chart: { type: 'area', height: 295, toolbar: { show: false }, zoom: { enabled: false }, fontFamily: 'inherit' },
            colors: ['#b10100', '#333333', '#7b0000'],
            stroke: { curve: 'smooth', width: 2 },
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.15, opacityTo: 0.02, stops: [0, 100] } },
            xaxis: { categories: labels, labels: { style: { fontSize: '11px', colors: '#adb5bd' } }, axisBorder: { show: false }, axisTicks: { show: false } },
            yaxis: { labels: { style: { fontSize: '11px', colors: '#adb5bd' }, formatter: v => Math.round(v) } },
            grid: { borderColor: '#f0f0f0', strokeDashArray: 4 },
            tooltip: { theme: 'light', shared: true, intersect: false },
            legend: { position: 'top', horizontalAlign: 'right', fontFamily: 'inherit' },
            markers: { size: 0 }
        });
        activityChart.render();
    }

    // ─── Distribution (Donut) ────────────────────────────────────────────
    function loadDistribution() {
        return $.getJSON('<?php echo e(route('core.dashboard.distribution')); ?>')
            .done(function (data) {
                if (!data.labels.length) {
                    $('#distribution-chart').html(emptyState('Sin datos'));
                    return;
                }
                if (distChart) { distChart.destroy(); }
                $('#distribution-chart').html('');
                distChart = new ApexCharts(document.querySelector('#distribution-chart'), {
                    series: data.series,
                    labels: data.labels,
                    chart: { type: 'donut', height: 200, fontFamily: 'inherit' },
                    colors: ['#b10100', '#333333', '#7b0000', '#555555'],
                    legend: { show: false },
                    dataLabels: { enabled: false },
                    tooltip: { y: { formatter: v => fmt(v) } },
                    plotOptions: { pie: { donut: { size: '75%' } } }
                });
                distChart.render();
            })
            .fail(function () {
                $('#distribution-chart').html(emptyState('Sin datos'));
            });
    }

    // ─── Latest Reviews ──────────────────────────────────────────────────
    function loadLatestReviews() {
        return $.getJSON('<?php echo e(route('core.dashboard.latest-reviews')); ?>')
            .done(function (data) {
                var list = data.reviews || [];
                if (!list.length) { $('#latest-reviews-list').html(emptyState('Sin reseñas recientes')); return; }
                var html = list.map(function (r, idx) {
                    var isLast = idx === list.length - 1;
                    var replyBadge = r.replied
                        ? '<span class="badge bg-success-subtle text-success rounded-pill"><i class="fas fa-check me-1"></i>Respondida</span>'
                        : '<span class="badge bg-warning-subtle text-warning rounded-pill"><i class="fas fa-clock me-1"></i>Pendiente</span>';
                    return `<div class="d-flex align-items-center justify-content-between ${isLast ? '' : 'mb-4'}">
                        <div class="d-flex align-items-center">
                            <div class="p-2 bg-primary-subtle rounded-2 d-flex align-items-center justify-content-center me-3" style="width:36px;height:36px;">
                                <i class="fas fa-user text-primary"></i>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <h6 class="mb-0 fw-semibold">${escHtml(r.author)}</h6>
                                    ${starRating(r.stars)}
                                </div>
                                <p class="fs-3 mb-0 text-muted text-truncate" style="max-width:280px;">${escHtml(r.comment)}</p>
                            </div>
                        </div>
                        <div class="text-end flex-shrink-0 ms-2">
                            <div class="mb-1">${replyBadge}</div>
                            <small class="text-muted">${escHtml(r.date)}</small>
                        </div>
                    </div>`;
                }).join('');
                $('#latest-reviews-list').html(html);
            })
            .fail(function () {
                $('#latest-reviews-list').html(emptyState('No se pudieron cargar las reseñas'));
            });
    }

    // ─── Activity ────────────────────────────────────────────────────────
    const activityIconMap = {
        'created': { icon: 'fas fa-plus-circle' },
        'updated': { icon: 'fas fa-pencil-alt'  },
        'deleted': { icon: 'fas fa-trash-alt'   },
        'login':   { icon: 'fas fa-sign-in-alt' },
    };
    function activityStyle(description) {
        var d = (description || '').toLowerCase();
        if (d.includes('created')) return activityIconMap.created;
        if (d.includes('updated')) return activityIconMap.updated;
        if (d.includes('deleted')) return activityIconMap.deleted;
        if (d.includes('login'))   return activityIconMap.login;
        return { icon: 'fas fa-history' };
    }
    function loadActivity() {
        return $.getJSON('<?php echo e(route('core.dashboard.activity')); ?>', function (data) {
            var list = data.activities || [];
            if (!list.length) { $('#recent-activity-list').html(emptyState('Sin actividad reciente')); return; }
            var html = list.map(function (a, idx) {
                var style = activityStyle(a.description);
                var isLast = idx === list.length - 1;
                var route = subjectRoutes[a.subject_type] || '';
                var tag = route ? 'a' : 'div';
                var attrs = route ? `href="${route}" class="d-flex align-items-center justify-content-between ${isLast ? '' : 'mb-4'} text-decoration-none text-dark"` : `class="d-flex align-items-center justify-content-between ${isLast ? '' : 'mb-4'}"`;
                return `<${tag} ${attrs}>
                    <div class="d-flex align-items-center">
                        <div class="p-2 bg-primary-subtle rounded-2 d-flex align-items-center justify-content-center me-3" style="width:36px;height:36px;">
                            <i class="${style.icon} text-primary"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-semibold">${escHtml(a.description)}</h6>
                            <p class="fs-3 mb-0 text-muted">${escHtml(a.causer)} · ${escHtml(a.created_at)}</p>
                        </div>
                    </div>
                </${tag}>`;
            }).join('');
            $('#recent-activity-list').html(html);
        }).fail(function () {
            $('#recent-activity-list').html(emptyState('No se pudo cargar la actividad'));
        });
    }

    // ─── Queue ───────────────────────────────────────────────────────────
    function loadQueueStats() {
        return $.getJSON('<?php echo e(route('core.dashboard.queue-stats')); ?>', function (data) {
            var failed = data.failed_jobs || 0;
            var total = data.total_pending || 0;
            var queues = data.pending_by_queue || {};
            var status = data.horizon_status || 'inactive';
            $('#queue-failed').text(fmt(failed)).toggleClass('text-danger', failed > 0);
            $('#queue-pending').text(fmt(total));
            var statusMap = { running: { cls: 'text-success', label: 'Activo' }, paused: { cls: 'text-warning', label: 'Pausado' }, inactive: { cls: 'text-muted', label: 'Inactivo' } };
            var s = statusMap[status] || statusMap.inactive;
            $('#horizon-icon').attr('class', 'fas fa-circle ' + s.cls);
            $('#horizon-label').text(s.label);
            var knownQueues = ['default', 'reviews.sync', 'exports', 'backups', 'notifications', 'google-sync'];
            var $perQueue = $('#queue-per-queue').empty();
            var allQueues = knownQueues.concat(Object.keys(queues).filter(function (q) { return knownQueues.indexOf(q) === -1; }));
            allQueues.forEach(function (queue) {
                var count = queues[queue] || 0;
                var cls = count > 0 ? 'bg-warning text-dark' : 'bg-light text-muted';
                $perQueue.append('<span class="badge ' + cls + '" style="font-size:.7rem">' + queue + ': ' + count + '</span>');
            });
            $('#queue-last-updated').text('Actualizado ' + new Date().toLocaleTimeString('es', { hour: '2-digit', minute: '2-digit' }));
        }).fail(function () {
            $('#queue-failed, #queue-pending').text('—');
        });
    }

    // ─── Health ──────────────────────────────────────────────────────────
    function loadHealth() {
        return $.getJSON('<?php echo e(route('core.dashboard.health')); ?>', function (data) {
            var ok = data.status === 'operational';
            $('#health-status-label').text(ok ? 'OK' : 'Alerta');
            $('#health-updated').text('Verificado ' + new Date().toLocaleTimeString('es', { hour: '2-digit', minute: '2-digit' }));
            $('#kpi-system-status').text(ok ? 'Operativo' : 'Degradado').toggleClass('text-success', ok).toggleClass('text-danger', !ok);
            $('#kpi-system-detail').text(ok ? 'Todos los servicios activos' : 'Revisar servicios');
            $('#system-icon').attr('class', ok ? 'fas fa-server text-success' : 'fas fa-server text-danger');
            $('#system-icon-bg').attr('class', ok ? 'rounded-circle bg-success-subtle d-flex align-items-center justify-content-center' : 'rounded-circle bg-danger-subtle d-flex align-items-center justify-content-center');
        }).fail(function () {
            $('#kpi-system-status').text('Desconocido').addClass('text-muted');
            $('#kpi-system-detail').text('No se pudo verificar');
        });
    }

    // ─── Helpers ─────────────────────────────────────────────────────────
    function escHtml(t) {
        const d = document.createElement('div');
        d.textContent = t;
        return d.innerHTML;
    }

    // ─── Range pills ─────────────────────────────────────────────────────
    $('#rangePills').on('click', 'a[data-days]', function (e) {
        e.preventDefault();
        $('#rangePills a').removeClass('active').addClass('text-muted');
        $(this).addClass('active').removeClass('text-muted');
        selectedDays = parseInt($(this).data('days'));
        var $icon = $('#btn-refresh-all i');
        $icon.addClass('fa-spin');
        loadTrends().always(function () { $icon.removeClass('fa-spin'); });
    });

    // ─── Refresh all ─────────────────────────────────────────────────────
    function renderSecurityScore(score) {
        if (securityScoreChart) { securityScoreChart.destroy(); }
        $('#security-score-chart').html('');
        var color = score >= 80 ? '#13C672' : score >= 50 ? '#FEC90F' : '#b10100';
        securityScoreChart = new ApexCharts(document.querySelector('#security-score-chart'), {
            series: [score],
            chart: { type: 'radialBar', height: 140, fontFamily: 'inherit' },
            plotOptions: { radialBar: { hollow: { size: '55%' }, dataLabels: { show: true, name: { show: false }, value: { fontSize: '22px', fontWeight: 600, color: color, formatter: v => v } } } },
            colors: [color],
            stroke: { lineCap: 'round' },
        });
        securityScoreChart.render();
        var label = score >= 80 ? 'Excelente' : score >= 50 ? 'Regular' : 'Crítico';
        $('#security-score-label').text(label).attr('class', 'fs-3 mb-0 ' + (score >= 80 ? 'text-success' : score >= 50 ? 'text-warning' : 'text-danger'));
    }

    function render2faChart(pct) {
        if (twofaChart) { twofaChart.destroy(); }
        $('#twofa-chart').html('');
        twofaChart = new ApexCharts(document.querySelector('#twofa-chart'), {
            series: [pct],
            chart: { type: 'radialBar', height: 140, fontFamily: 'inherit' },
            plotOptions: { radialBar: { hollow: { size: '55%' }, dataLabels: { show: true, name: { show: false }, value: { fontSize: '22px', fontWeight: 600, color: '#333333', formatter: v => v + '%' } } } },
            colors: ['#b10100'],
            stroke: { lineCap: 'round' },
        });
        twofaChart.render();
        $('#twofa-label').text(pct + '% de usuarios protegidos');
    }

    function loadSecurityMetrics() {
        return $.getJSON('<?php echo e(route('core.dashboard.security-metrics')); ?>')
            .done(function (data) {
                renderSecurityScore(data.security_score ?? 0);
                render2faChart(Math.round(data.two_fa_adoption_pct ?? 0));
                $('#kpi-active-sessions').text(fmt(data.active_sessions ?? 0));
                $('#kpi-failed-24h').text(fmt(data.failed_logins_24h ?? 0)).toggleClass('text-danger', (data.failed_logins_24h ?? 0) > 20);
            })
            .fail(function () {
                $('#security-score-label').text('No disponible');
                $('#twofa-label').text('No disponible');
            });
    }

    function loadLoginTimeline() {
        return $.getJSON('<?php echo e(route('core.dashboard.login-timeline')); ?>')
            .done(function (data) {
                if (loginTimelineChart) { loginTimelineChart.destroy(); }
                $('#login-timeline-chart').html('');
                loginTimelineChart = new ApexCharts(document.querySelector('#login-timeline-chart'), {
                    series: [
                        { name: 'Exitosos', data: data.success || [] },
                        { name: 'Fallidos', data: data.failed || [] },
                        { name: 'Bloqueos', data: data.lockout || [] }
                    ],
                    chart: { type: 'bar', height: 260, toolbar: { show: false }, zoom: { enabled: false }, fontFamily: 'inherit', stacked: true },
                    colors: ['#13C672', '#b10100', '#555555'],
                    plotOptions: { bar: { borderRadius: 2, columnWidth: '70%' } },
                    xaxis: { categories: data.labels || [], labels: { style: { fontSize: '11px', colors: '#adb5bd' } }, axisBorder: { show: false }, axisTicks: { show: false } },
                    yaxis: { labels: { style: { fontSize: '11px', colors: '#adb5bd' }, formatter: v => Math.round(v) } },
                    grid: { borderColor: '#f0f0f0', strokeDashArray: 4 },
                    tooltip: { theme: 'light', shared: true, intersect: false },
                    legend: { position: 'top', horizontalAlign: 'right', fontFamily: 'inherit' },
                });
                loginTimelineChart.render();
            })
            .fail(function () {
                $('#login-timeline-chart').html(emptyState('Sin datos de login'));
            });
    }

    function loadTopFailedIps() {
        return $.getJSON('<?php echo e(route('core.dashboard.top-failed-ips')); ?>')
            .done(function (data) {
                var list = data.ips || [];
                if (!list.length) { $('#top-failed-ips-list').html(emptyState('Sin IPs sospechosas')); return; }
                var html = list.map(function (item, idx) {
                    var isLast = idx === list.length - 1;
                    return `<div class="d-flex align-items-center justify-content-between ${isLast ? '' : 'mb-4'}">
                        <div class="d-flex align-items-center">
                            <div class="p-2 bg-primary-subtle rounded-2 d-flex align-items-center justify-content-center me-3" style="width:36px;height:36px;">
                                <i class="fas fa-network-wired text-primary"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-semibold font-monospace">${escHtml(item.ip)}</h6>
                                <p class="fs-3 mb-0 text-muted">${escHtml(item.last_attempt)}</p>
                            </div>
                        </div>
                        <h6 class="mb-0 fw-semibold text-danger">${fmt(item.attempts)}</h6>
                    </div>`;
                }).join('');
                $('#top-failed-ips-list').html(html);
            })
            .fail(function () {
                $('#top-failed-ips-list').html(emptyState('No se pudieron cargar las IPs'));
            });
    }

    function refreshAll(showToast) {
        var $icon = $('#btn-refresh-all i');
        $icon.addClass('fa-spin');
        var completed = 0, total = 11;
        function checkDone() {
            completed++;
            if (completed >= total) { $icon.removeClass('fa-spin'); if (showToast) toastr.success('Dashboard actualizado'); }
        }
        loadKpis().always(checkDone);
        loadTrends().always(checkDone);
        loadDistribution().always(checkDone);
        loadLatestReviews().always(checkDone);
        loadActivity().always(checkDone);
        loadQueueStats().always(checkDone);
        loadHealth().always(checkDone);
        loadAlerts().always(checkDone);
        loadSecurityMetrics().always(checkDone);
        loadLoginTimeline().always(checkDone);
        loadTopFailedIps().always(checkDone);
    }

    // ─── Init ────────────────────────────────────────────────────────────
    refreshAll(false);

    // Auto-refresh
    setInterval(function () { loadKpis(); loadAlerts(); }, 60000);
    setInterval(loadQueueStats, 30000);

    // Manual refresh
    $('#btn-refresh-all, #btn-refresh-activity').on('click', function () { refreshAll(true); });

    // Keyboard shortcut: R
    $(document).on('keydown', function (e) {
        if (e.key === 'r' || e.key === 'R') {
            if ($(e.target).is('input, textarea, select')) return;
            e.preventDefault();
            refreshAll(true);
        }
    });
}());
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.theme', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/developerts/Herd/system/modules/Core/resources/views/dashboard/index.blade.php ENDPATH**/ ?>
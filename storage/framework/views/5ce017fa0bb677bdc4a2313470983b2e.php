<?php
use App\Models\User;
use Modules\Auth\Models\ImpersonationLog;
use Modules\Auth\Models\LoginAttempt;
use Modules\Auth\Models\TrustedDevice;

$since = now()->subDay();

$stats = [
    'failed_24h' => LoginAttempt::where('status', LoginAttempt::STATUS_FAILED)
        ->where('attempted_at', '>=', $since)->count(),
    'success_24h' => LoginAttempt::where('status', LoginAttempt::STATUS_SUCCESS)
        ->where('attempted_at', '>=', $since)->count(),
    'locked_now' => User::whereNotNull('locked_until')
        ->where('locked_until', '>', now())->count(),
    'new_devices_24h' => TrustedDevice::where('first_seen_at', '>=', $since)->count(),
    'active_impersonations' => ImpersonationLog::whereNull('ended_at')->count(),
];
?>

<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="mb-0 fw-bold">
                <i class="fas fa-shield-halved text-primary me-2"></i>Seguridad · últimas 24h
            </h6>
            <a href="<?php echo e(route('settings.auth.audit.login-attempts')); ?>" class="btn btn-sm btn-link">
                Ver auditoría completa <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-light-success p-3">
                        <i class="fas fa-right-to-bracket text-success fs-4"></i>
                    </div>
                    <div>
                        <h3 class="mb-0 fw-bold"><?php echo e(number_format($stats['success_24h'])); ?></h3>
                        <small class="text-muted">Logins exitosos</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-light-danger p-3">
                        <i class="fas fa-xmark text-danger fs-4"></i>
                    </div>
                    <div>
                        <h3 class="mb-0 fw-bold <?php echo e($stats['failed_24h'] > 20 ? 'text-danger' : ''); ?>">
                            <?php echo e(number_format($stats['failed_24h'])); ?>

                        </h3>
                        <small class="text-muted">Intentos fallidos</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-light-warning p-3">
                        <i class="fas fa-lock text-warning fs-4"></i>
                    </div>
                    <div>
                        <h3 class="mb-0 fw-bold <?php echo e($stats['locked_now'] > 0 ? 'text-warning' : ''); ?>">
                            <?php echo e(number_format($stats['locked_now'])); ?>

                        </h3>
                        <small class="text-muted">Cuentas bloqueadas</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-light-info p-3">
                        <i class="fas fa-user-secret text-info fs-4"></i>
                    </div>
                    <div>
                        <h3 class="mb-0 fw-bold <?php echo e($stats['active_impersonations'] > 0 ? 'text-info' : ''); ?>">
                            <?php echo e(number_format($stats['active_impersonations'])); ?>

                        </h3>
                        <small class="text-muted">Impersonaciones activas</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php /**PATH /Users/developerts/Herd/system/modules/Auth/resources/views/components/security-widget.blade.php ENDPATH**/ ?>
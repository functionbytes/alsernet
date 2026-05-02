<?php $__env->startSection('title', 'Visitantes en vivo'); ?>

<?php $__env->startPush('css'); ?>
<style>
    .lc-header { background: linear-gradient(135deg, #b10100 0%, #7b0000 100%); color: white; padding: 1.5rem 2rem; border-radius: 12px; margin-bottom: 1.5rem; }
    .lc-header h4 { font-weight: 700; margin: 0 0 .25rem; }
    .visitor-card { padding: .75rem 1rem; border-left: 3px solid #b10100; background: #fafafa; margin-bottom: .5rem; border-radius: 0 8px 8px 0; }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid py-4">
    <div class="lc-header">
        <h4><i class="fas fa-eye me-2"></i>Visitantes en vivo</h4>
        <p>Personas navegando ahora mismo (últimos 5 minutos)</p>
    </div>
    <div class="row g-3 mb-3">
        <div class="col-md-4"><div class="card kpi-card p-3"><small class="text-muted">Total</small><div class="fs-3 fw-bold" id="kpiTotal">—</div></div></div>
        <div class="col-md-4"><div class="card kpi-card p-3"><small class="text-muted">Identificados</small><div class="fs-3 fw-bold text-success" id="kpiKnown">—</div></div></div>
        <div class="col-md-4"><div class="card kpi-card p-3"><small class="text-muted">Anónimos</small><div class="fs-3 fw-bold text-secondary" id="kpiAnon">—</div></div></div>
    </div>
    <div class="card"><div class="card-body" id="visitorsList"></div></div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('js'); ?>
<script>
$(function() {
    function refresh() {
        $.get('<?php echo e(route('manager.engagement.live-visitors.data')); ?>').done(r => {
            $('#kpiTotal').text(r.total);
            $('#kpiKnown').text(r.known);
            $('#kpiAnon').text(r.anonymous);
            $('#visitorsList').html(r.sessions.length === 0
                ? '<p class="text-muted text-center py-4">No hay visitantes activos.</p>'
                : r.sessions.map(s => `
                    <div class="visitor-card">
                        <div class="d-flex justify-content-between">
                            <div>
                                <strong>${s.customer?.name ?? 'Anónimo'}</strong>
                                <small class="text-muted ms-2">${s.country_code ?? ''}</small>
                                <div class="small text-muted">${s.current_url ?? ''}</div>
                            </div>
                            <small class="text-muted">${s.last_activity_at}</small>
                        </div>
                    </div>`).join(''));
        });
    }
    refresh();
    setInterval(refresh, 10000);
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.theme', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/developerts/Herd/system/modules/Engagement/resources/views/managers/live-visitors/index.blade.php ENDPATH**/ ?>
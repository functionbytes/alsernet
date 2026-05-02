<?php
use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

$__env->startSection('title', 'Analytics — LiveChat'); ?>

<?php $__env->startPush('css'); ?>
<style>
    .lc-header {
        background: linear-gradient(135deg, #b10100 0%, #7b0000 100%);
        color: white; padding: 1.5rem 2rem; border-radius: 12px; margin-bottom: 1.5rem;
    }
    .lc-header h4 { font-weight: 700; margin: 0 0 .25rem; }
    .lc-header p  { opacity: .9; margin: 0; font-size: .9rem; }
    .kpi-card { padding: 1.25rem; border-radius: 12px; }
    .kpi-card .kpi-value { font-size: 2rem; font-weight: 700; line-height: 1; }
    .kpi-card .kpi-label { font-size: .75rem; text-transform: uppercase; color: #888; letter-spacing: .5px; }
    .kpi-cold { color: #6c757d; }
    .kpi-warm { color: #ffa500; }
    .kpi-hot  { color: #b10100; }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid py-4">

    <div class="lc-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4><i class="fas fa-chart-line me-2"></i>Analytics LiveChat</h4>
                <p>Eventos, scores, segmentos y rendimiento de triggers</p>
            </div>
        </div>
    </div>

    
    <div class="card mb-3">
        <div class="card-body d-flex align-items-center gap-3 flex-wrap">
            <label class="fw-semibold mb-0">Inbox:</label>
            <select id="inboxFilter" class="form-select w-auto">
                <option value="">Todos</option>
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $inboxes;
$__env->addLoop($__currentLoopData);
foreach ($__currentLoopData as $inbox) {
    $__env->incrementLoopIndices();
    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                    <option value="<?php echo e($inbox->id); ?>"><?php echo e($inbox->name); ?></option>
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
$loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
            </select>
            <label class="fw-semibold mb-0 ms-3">Período:</label>
            <select id="daysFilter" class="form-select w-auto">
                <option value="7">7 días</option>
                <option value="30" selected>30 días</option>
                <option value="90">90 días</option>
            </select>
            <button class="btn btn-primary ms-auto" id="btnRefresh">
                <i class="fas fa-rotate-right me-1"></i>Actualizar
            </button>
        </div>
    </div>

    
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card kpi-card">
                <div class="kpi-label">Visitantes únicos</div>
                <div class="kpi-value" id="kpiVisitors">—</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card kpi-card">
                <div class="kpi-label">Eventos totales</div>
                <div class="kpi-value" id="kpiEvents">—</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card kpi-card">
                <div class="kpi-label">Score medio</div>
                <div class="kpi-value" id="kpiAvgScore">—</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card kpi-card">
                <div class="kpi-label">% Hot visitors</div>
                <div class="kpi-value kpi-hot" id="kpiHotPct">—</div>
            </div>
        </div>
    </div>

    <div class="row g-3">

        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3"><i class="fas fa-chart-area me-1"></i>Eventos por día</h6>
                    <div id="chartEventsByDay" style="height:300px"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3"><i class="fas fa-chart-pie me-1"></i>Distribución de segmentos</h6>
                    <div id="chartSegments" style="height:300px"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3"><i class="fas fa-list-ol me-1"></i>Top eventos</h6>
                    <div id="topEventsTable"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3"><i class="fas fa-bolt me-1"></i>Rendimiento de triggers</h6>
                    <div id="triggerPerfTable"></div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-semibold mb-0"><i class="fas fa-filter me-1"></i>Funnel de conversión</h6>
                        <select id="funnelGoalSelector" class="form-select form-select-sm w-auto">
                            <option value="">Selecciona un objetivo…</option>
                        </select>
                    </div>
                    <div id="chartFunnel" style="height:300px"></div>
                </div>
            </div>
        </div>

    </div>

</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('js'); ?>
<script>
$(function () {
    const routes = {
        overview:    '<?php echo e(route('manager.engagement.analytics.overview')); ?>',
        eventsByDay: '<?php echo e(route('manager.engagement.analytics.events-by-day')); ?>',
        segments:    '<?php echo e(route('manager.engagement.analytics.segments')); ?>',
        topEvents:   '<?php echo e(route('manager.engagement.analytics.top-events')); ?>',
        triggers:    '<?php echo e(route('manager.engagement.analytics.triggers')); ?>',
    };

    function params() {
        return {
            inbox_id: $('#inboxFilter').val(),
            days: $('#daysFilter').val(),
        };
    }

    let chartByDay = null;
    let chartSegments = null;

    async function refresh() {
        const p = params();

        // KPIs
        const ovr = await $.get(routes.overview, p);
        $('#kpiVisitors').text(ovr.data.unique_visitors.toLocaleString());
        $('#kpiEvents').text(ovr.data.total_events.toLocaleString());
        $('#kpiAvgScore').text(ovr.data.avg_score);
        $('#kpiHotPct').text(ovr.data.hot_pct + '%');

        // Events by day chart
        const byDay = await $.get(routes.eventsByDay, p);
        if (chartByDay) chartByDay.dispose();
        chartByDay = $('#chartEventsByDay').dxChart({
            dataSource: byDay.data,
            commonSeriesSettings: { argumentField: 'day', type: 'spline' },
            series: [{ valueField: 'count', name: 'Eventos', color: '#b10100' }],
            argumentAxis: { label: { format: 'shortDate' } },
            tooltip: { enabled: true },
            legend: { visible: false },
        }).dxChart('instance');

        // Segments
        const seg = await $.get(routes.segments, p);
        if (chartSegments) chartSegments.dispose();
        chartSegments = $('#chartSegments').dxPieChart({
            dataSource: [
                { segment: 'Cold', count: seg.data.cold, color: '#6c757d' },
                { segment: 'Warm', count: seg.data.warm, color: '#ffa500' },
                { segment: 'Hot',  count: seg.data.hot,  color: '#b10100' },
            ],
            series: [{ argumentField: 'segment', valueField: 'count', label: { visible: true, format: { type: 'percent', precision: 1 } } }],
            palette: ['#6c757d', '#ffa500', '#b10100'],
            legend: { visible: true, horizontalAlignment: 'center', verticalAlignment: 'bottom' },
        }).dxPieChart('instance');

        // Top events table
        const top = await $.get(routes.topEvents, p);
        $('#topEventsTable').html(buildTable(['Evento', 'Total'], top.data.map(r => [r.event_name, r.count])));

        // Trigger performance
        const trg = await $.get(routes.triggers, p);
        $('#triggerPerfTable').html(buildTable(['Regla', 'Disparos', 'Estado'], trg.data.map(r => [
            r.name, r.fires, r.is_active ? '<span class="badge bg-success">Activa</span>' : '<span class="badge bg-secondary">Inactiva</span>',
        ])));

        loadFunnelGoals();
    }

    let chartFunnel = null;

    async function loadFunnelGoals() {
        const inboxId = $('#inboxFilter').val();
        if (!inboxId) {
            $('#funnelGoalSelector').html('<option value="">Selecciona un inbox para ver objetivos…</option>');
            return;
        }
        try {
            const goalsRes = await $.get('<?php echo e(route('settings.engagement.goals.index')); ?>', { inbox_id: inboxId });
            const opts = '<option value="">Selecciona un objetivo…</option>' +
                goalsRes.data.filter(g => g.funnel_steps && g.funnel_steps.length).map(g =>
                    `<option value="${g.id}">${g.name}</option>`
                ).join('');
            $('#funnelGoalSelector').html(opts);
        } catch (e) { /* ignore */ }
    }

    async function renderFunnel(goalId) {
        if (!goalId) return;
        const url = '<?php echo e(route('settings.engagement.goals.funnel', ['conversionGoal' => '__ID__'])); ?>'.replace('__ID__', goalId);
        const r = await $.get(url, { days: $('#daysFilter').val() });

        if (chartFunnel) chartFunnel.dispose();
        chartFunnel = $('#chartFunnel').dxChart({
            dataSource: r.data,
            commonSeriesSettings: { argumentField: 'step', type: 'bar' },
            series: [{ valueField: 'visitors', name: 'Visitantes', color: '#b10100' }],
            rotated: true,
            legend: { visible: false },
            tooltip: { enabled: true, format: 'fixedPoint' },
        }).dxChart('instance');
    }

    $('#funnelGoalSelector').on('change', function () {
        renderFunnel($(this).val());
    });

    function buildTable(headers, rows) {
        const head = headers.map(h => `<th>${h}</th>`).join('');
        const body = rows.length
            ? rows.map(r => `<tr>${r.map(c => `<td>${c ?? '—'}</td>`).join('')}</tr>`).join('')
            : `<tr><td colspan="${headers.length}" class="text-center text-muted py-3">Sin datos</td></tr>`;
        return `<table class="table table-sm mb-0"><thead><tr>${head}</tr></thead><tbody>${body}</tbody></table>`;
    }

    $('#btnRefresh').on('click', refresh);
    $('#inboxFilter, #daysFilter').on('change', refresh);

    refresh();
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.theme', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/developerts/Herd/system/modules/Engagement/resources/views/managers/analytics/index.blade.php ENDPATH**/ ?>
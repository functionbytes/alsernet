<?php
use Carbon\Carbon;
use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

$__env->startSection('title', 'Inbox formularios'); ?>

<?php $__env->startSection('content'); ?>

    <?php echo $__env->make('core::components.card', ['title' => 'Inbox formularios'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="widget-content searchable-container list">
        <?php echo $__env->make('core::components.alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <div class="card">
            
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Inbox formularios</h5>
                        <p class="small mb-0 text-muted">Gestiona todas las respuestas recibidas en los formularios</p>
                    </div>
                    <div class="ms-auto">
                        <div class="btn-group">
                            <button type="button" class="btn bg-primary-subtle text-primary dropdown-toggle"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Acciones
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="<?php echo e(route('forms.inbox.dashboard')); ?>">Dashboard</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold"><?php echo e($stats['total'] ?? 0); ?></h4>
                                <small class="text-muted">Todas las submissions</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Sin leer</h6>
                                <h4 class="mb-1 fw-bold"><?php echo e($stats['unread'] ?? 0); ?></h4>
                                <small class="text-muted">Pendientes de revisión</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Spam</h6>
                                <h4 class="mb-1 fw-bold"><?php echo e($stats['spam'] ?? 0); ?></h4>
                                <small class="text-muted">Marcadas como spam</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Con estrella</h6>
                                <h4 class="mb-1 fw-bold"><?php echo e($stats['starred'] ?? 0); ?></h4>
                                <small class="text-muted">Destacadas</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="card-body border-bottom">
                <?php
                    $extraFiltersCount = collect([$formId, $statusFilter, $assignedTo, $dateFrom])
                        ->filter()->count()
                        + (($isSpam ?? '0') !== '0' ? 1 : 0);
?>
                <form class="form-search" action="<?php echo e(route('forms.inbox.index')); ?>" method="GET" id="inbox-filter-form">
                    
                    <input type="hidden" name="form_id"        id="h_form_id"        value="<?php echo e($formId ?? ''); ?>">
                    <input type="hidden" name="status"         id="h_status"         value="<?php echo e($statusFilter ?? ''); ?>">
                    <input type="hidden" name="assigned_to"    id="h_assigned_to"    value="<?php echo e($assignedTo ?? ''); ?>">
                    <input type="hidden" name="source_page_id" id="h_source_page_id" value="<?php echo e($sourcePageId ?? ''); ?>">
                    <input type="hidden" name="is_spam"        id="h_is_spam"        value="<?php echo e($isSpam ?? '0'); ?>">
                    <input type="hidden" name="date_from"      id="date_from"        value="<?php echo e($dateFrom ?? ''); ?>">
                    <input type="hidden" name="date_to"        id="date_to"          value="<?php echo e($dateTo ?? ''); ?>">

                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        
                        <div class="flex-fill">
                            <div class="input-group h-100">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input class="form-control border-start-0 ps-0" type="text" name="search"
                                       placeholder="Buscar en respuestas..."
                                       value="<?php echo e($search ?? ''); ?>">
                            </div>
                        </div>

                        
                        <div class="flex-shrink-0">
                            <button type="button" class="btn btn-info position-relative"
                                    data-bs-toggle="modal" data-bs-target="#inbox-more-filters-modal"
                                    title="Más filtros">
                                <i class="fas fa-sliders"></i>
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($extraFiltersCount > 0) { ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                                        <?php echo e($extraFiltersCount); ?>

                                    </span>
                                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                            </button>
                        </div>

                        
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($search || $extraFiltersCount) { ?>
                                <a href="<?php echo e(route('forms.inbox.index')); ?>" class="btn btn-outline-secondary" title="Limpiar filtros">
                                    <i class="fas fa-times"></i>
                                </a>
                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                        </div>
                    </div>
                </form>
            </div>

            
            <div class="card-body">
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (isset($submissions) && $submissions->isNotEmpty()) { ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>Radicado</th>
                                    <th>Tipo</th>
                                    <th>Ciudadano</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $submissions;
                    $__env->addLoop($__currentLoopData);
                    foreach ($__currentLoopData as $submission) {
                        $__env->incrementLoopIndices();
                        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                    <?php
                                $statusMap = [
                                    'new' => ['label' => 'Nuevo',       'class' => 'bg-primary'],
                                    'in_review' => ['label' => 'En revisión', 'class' => 'bg-success'],
                                    'resolved' => ['label' => 'Resuelto',    'class' => 'bg-success'],
                                    'rejected' => ['label' => 'Rechazado',   'class' => 'bg-danger'],
                                ];
                        $st = $statusMap[$submission->status ?? 'new'] ?? $statusMap['new'];
                        $citizenName = $submission->getCitizenName();
                        $citizenEmail = $submission->getEmailValue();
                        ?>
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="<?php echo e($submission->id); ?>"></td>
                                        <td>
                                            <strong class="text-primary"><?php echo e($submission->getRadicado()); ?></strong>
                                        </td>
                                        <td>
                                            <span class="text-muted"><?php echo e($submission->form->name ?? '—'); ?></span>
                                        </td>
                                        <td>
                                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($citizenName) { ?>
                                                <span class="fw-semibold"><?php echo e($citizenName); ?></span><br>
                                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                            <small class="text-muted"><?php echo e($citizenEmail ?? '—'); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo e($st['class']); ?>"><?php echo e($st['label']); ?></span>
                                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($submission->is_spam) { ?>
                                                <span class="badge bg-danger ms-1">Spam</span>
                                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                        </td>
                                        <td>
                                            <span class="text-muted"><?php echo e($submission->created_at?->format('d/m/Y H:i')); ?></span>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown">
                                                    <i class="fa fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="<?php echo e(route('forms.inbox.manage', $submission)); ?>">
                                                            Gestionar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="<?php echo e(route('forms.inbox.show', $submission)); ?>">
                                                            Ver detalles
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="<?php echo e(route('settings.forms.edit', $submission->form)); ?>">
                                                            Configurar formulario
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item" href="<?php echo e(route('settings.forms.submissions.pdf', [$submission->form, $submission])); ?>"
                                                           target="_blank">
                                                            Descargar PDF
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
                    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                            </tbody>
                        </table>
                    </div>
                <?php } else { ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-4x text-muted opacity-50 mb-3"></i>
                        <h5 class="text-muted mb-2">No se encontraron submissions</h5>
                        <p class="text-muted mb-0">Ajusta los filtros o espera nuevas respuestas de los formularios</p>
                    </div>
                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
            </div>

            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (isset($submissions) && $submissions->hasPages()) { ?>
                <div class="card-footer"><?php echo e($submissions->withQueryString()->links()); ?></div>
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        </div>
    </div>

    
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" >
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar acción
        </button>
    </div>

    
    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Acción masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> registro(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            <option value="mark_read">Marcar como leído</option>
                            <option value="mark_unread">Marcar como no leído</option>
                            <option value="mark_spam">Marcar como spam</option>
                            <option value="unmark_spam">Quitar spam</option>
                            <option value="delete">Eliminar</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-1">Aplicar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    
    <div class="modal fade" id="inbox-more-filters-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Más filtros</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Formulario</label>
                        <select class="form-select select2-inbox-modal" id="m_form_id">
                            <option value="">Todos los formularios</option>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $forms ?? [];
$__env->addLoop($__currentLoopData);
foreach ($__currentLoopData as $form) {
    $__env->incrementLoopIndices();
    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                <option value="<?php echo e($form->id); ?>" <?php echo e(($formId ?? '') == $form->id ? 'selected' : ''); ?>>
                                    <?php echo e($form->name); ?>

                                </option>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
$loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Estado</label>
                        <select class="form-select select2-inbox-modal" id="m_status">
                            <option value="">Todos los estados</option>
                            <option value="new"       <?php echo e(($statusFilter ?? '') === 'new' ? 'selected' : ''); ?>>Nuevo</option>
                            <option value="in_review" <?php echo e(($statusFilter ?? '') === 'in_review' ? 'selected' : ''); ?>>En revisión</option>
                            <option value="resolved"  <?php echo e(($statusFilter ?? '') === 'resolved' ? 'selected' : ''); ?>>Resuelto</option>
                            <option value="rejected"  <?php echo e(($statusFilter ?? '') === 'rejected' ? 'selected' : ''); ?>>Rechazado</option>
                        </select>
                    </div>
                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($sourcePages) && count($sourcePages)) { ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Página de origen</label>
                        <select class="form-select select2-inbox-modal" id="m_source_page_id">
                            <option value="">Todas las páginas</option>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $sourcePages;
                        $__env->addLoop($__currentLoopData);
                        foreach ($__currentLoopData as $p) {
                            $__env->incrementLoopIndices();
                            $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                <option value="<?php echo e($p->id); ?>" <?php echo e(($sourcePageId ?? '') == $p->id ? 'selected' : ''); ?>>
                                    <?php echo e($p->title); ?> · <?php echo e($p->submissions_count); ?>

                                </option>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
                        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                        </select>
                    </div>
                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Asignado a</label>
                        <select class="form-select select2-inbox-modal" id="m_assigned_to">
                            <option value="">Todos los usuarios</option>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $users ?? [];
$__env->addLoop($__currentLoopData);
foreach ($__currentLoopData as $user) {
    $__env->incrementLoopIndices();
    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                <option value="<?php echo e($user->id); ?>" <?php echo e(($assignedTo ?? '') == $user->id ? 'selected' : ''); ?>>
                                    <?php echo e($user->full_name); ?>

                                </option>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
$loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Spam</label>
                        <select class="form-select select2-inbox-modal" id="m_is_spam">
                            <option value="0"  <?php echo e(($isSpam ?? '0') === '0' ? 'selected' : ''); ?>>No spam</option>
                            <option value="1"  <?php echo e(($isSpam ?? '') === '1' ? 'selected' : ''); ?>>Solo spam</option>
                            <option value=""   <?php echo e(($isSpam ?? '') === '' && request()->has('is_spam') ? 'selected' : ''); ?>>Todos</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Rango de fechas</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="m_inbox_daterange"
                                   placeholder="Seleccionar rango..."
                                   value="<?php echo e(($dateFrom && $dateTo) ? Carbon::parse($dateFrom)->format('d/m/Y').' - '.Carbon::parse($dateTo)->format('d/m/Y') : ''); ?>"
                                   readonly style="cursor:pointer;">
                            <span class="input-group-text bg-white" style="cursor:pointer;" id="m_inbox_daterange_icon">
                                <i class="fas fa-calendar-alt text-muted" style="font-size:0.8rem;"></i>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="inbox-filters-apply" class="btn btn-primary w-100 mb-1">
                        Aplicar filtros
                    </button>
                    <button type="button" id="inbox-filters-clear" class="btn btn-secondary w-100" data-bs-dismiss="modal">
                        Limpiar estos filtros
                    </button>
                </div>
            </div>
        </div>
    </div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
$(document).ready(function () {

    // ── More-filters modal: init select2 + daterangepicker (once) ────────
    $('#inbox-more-filters-modal').on('shown.bs.modal', function () {
        if (!$('#m_form_id').data('select2')) {
            $('.select2-inbox-modal').select2({ dropdownParent: $('#inbox-more-filters-modal'), width: '100%' });
        }
        if ($('#m_inbox_daterange').data('daterangepicker')) { return; }

        var drpOpts = {
            autoUpdateInput: !!$('#date_from').val(),
            locale: {
                cancelLabel: 'Limpiar', applyLabel: 'Aplicar', format: 'DD/MM/YYYY', separator: ' - ',
                daysOfWeek: ['Do','Lu','Ma','Mi','Ju','Vi','Sa'],
                monthNames: ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
                firstDay: 1,
            },
            ranges: {
                'Hoy':            [moment(), moment()],
                'Ayer':           [moment().subtract(1,'days'), moment().subtract(1,'days')],
                'Últimos 7 días': [moment().subtract(6,'days'), moment()],
                'Últimos 30 días':[moment().subtract(29,'days'), moment()],
                'Este mes':       [moment().startOf('month'), moment().endOf('month')],
                'Mes pasado':     [moment().subtract(1,'month').startOf('month'), moment().subtract(1,'month').endOf('month')],
            },
        };
        if ($('#date_from').val()) {
            drpOpts.startDate = moment($('#date_from').val(), 'YYYY-MM-DD');
            drpOpts.endDate   = moment($('#date_to').val(),   'YYYY-MM-DD');
        }

        $('#m_inbox_daterange').daterangepicker(drpOpts);

        $('#m_inbox_daterange').on('apply.daterangepicker', function (ev, picker) {
            $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
            $('#date_from').val(picker.startDate.format('YYYY-MM-DD'));
            $('#date_to').val(picker.endDate.format('YYYY-MM-DD'));
        });

        $('#m_inbox_daterange').on('cancel.daterangepicker', function () {
            $(this).val('');
            $('#date_from').val('');
            $('#date_to').val('');
        });

        $('#m_inbox_daterange_icon').on('click', function () { $('#m_inbox_daterange').trigger('click'); });
    });

    // ── Apply extra filters ───────────────────────────────────────────────
    $('#inbox-filters-apply').on('click', function () {
        $('#h_form_id').val($('#m_form_id').val());
        $('#h_status').val($('#m_status').val());
        $('#h_assigned_to').val($('#m_assigned_to').val());
        $('#h_source_page_id').val($('#m_source_page_id').val() || '');
        $('#h_is_spam').val($('#m_is_spam').val());
        $('#inbox-more-filters-modal').modal('hide');
        $('#inbox-filter-form').submit();
    });

    // ── Clear extra filters ───────────────────────────────────────────────
    $('#inbox-filters-clear').on('click', function () {
        $('#m_form_id, #m_status, #m_assigned_to, #m_source_page_id').val('').trigger('change');
        $('#m_is_spam').val('0').trigger('change');
        $('#m_inbox_daterange').val('');
        $('#h_form_id, #h_status, #h_assigned_to, #h_source_page_id, #date_from, #date_to').val('');
        $('#h_is_spam').val('0');
        $('#inbox-filter-form').submit();
    });

    // ── Bulk actions ──────────────────────────────────────────────────────
    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        const action = $('#bulk-action-select').val();
        const ids    = bulk.getIds();

        if (!action) { toastr.warning('Selecciona una acción.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos un registro.'); return; }
        if (action === 'delete' && !confirm('¿Eliminar los ' + ids.length + ' registro(s) seleccionados?')) { return; }

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '<?php echo e(route('forms.inbox.bulk-action')); ?>',
            method: 'POST',
            data: JSON.stringify({ action: action, ids: ids, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.message || res.count + ' registro(s) procesados.');
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.theme', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/developerts/Herd/system/modules/Forms/resources/views/inbox/index.blade.php ENDPATH**/ ?>
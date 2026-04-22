<?php
use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

$__env->startSection('title', 'Submission #'.$submission->id.' - '.$form->name); ?>

<?php $__env->startSection('content'); ?>

    <?php echo $__env->make('core::components.card', ['title' => 'Detalle de la submission'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php
        $statusMap = [
            'new' => ['label' => 'Nuevo',       'class' => 'bg-primary'],
            'in_review' => ['label' => 'En revisión', 'class' => 'bg-warning text-dark'],
            'resolved' => ['label' => 'Resuelto',    'class' => 'bg-success'],
            'rejected' => ['label' => 'Rechazado',   'class' => 'bg-danger'],
        ];
$st = $statusMap[$submission->status ?? 'new'] ?? $statusMap['new'];
?>

    <div class="row">
        <div class="col-lg-8">

            
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">Información general</h5>
                            <p class="mb-0 text-muted">Detalles de la submission enviada</p>
                        </div>
                        <span class="badge <?php echo e($st['class']); ?>" id="statusBadge"><?php echo e($st['label']); ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold text-muted">Formulario</label>
                            <p class="mb-0">
                                <span class="badge bg-info"><?php echo e($form->name); ?></span>
                            </p>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold text-muted">Fecha de envío</label>
                            <p class="mb-0"><?php echo e($submission->created_at?->format('d/m/Y H:i:s') ?? '—'); ?></p>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold text-muted">IP</label>
                            <p class="mb-0"><?php echo e($submission->ip_address ?? '—'); ?></p>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold text-muted">País / Ciudad</label>
                            <p class="mb-0"><?php echo e(implode(', ', array_filter([$submission->country, $submission->city])) ?: '—'); ?></p>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold text-muted">Leído</label>
                            <p class="mb-0">
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($submission->is_read) { ?>
                                    <span class="badge bg-success">Sí</span>
                                <?php } else { ?>
                                    <span class="badge bg-secondary">No</span>
                                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                            </p>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold text-muted">Spam</label>
                            <p class="mb-0">
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($submission->is_spam) { ?>
                                    <span class="badge bg-danger">Sí</span>
                                <?php } else { ?>
                                    <span class="badge bg-secondary">No</span>
                                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                            </p>
                        </div>
                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($submission->user) { ?>
                            <div class="col-sm-12 col-md-6">
                                <label class="form-label fw-semibold text-muted">Usuario</label>
                                <p class="mb-0"><?php echo e($submission->user->full_name); ?></p>
                            </div>
                        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                    </div>
                </div>
            </div>

            
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-1 fw-bold">Respuestas</h5>
                    <p class="mb-0 text-muted">Campos enviados por el usuario</p>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 35%;">Campo</th>
                                    <th>Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__empty_1 = true;
$__currentLoopData = $submission->values->reject(fn ($v) => $v->field_type === 'hidden');
$__env->addLoop($__currentLoopData);
foreach ($__currentLoopData as $value) {
    $__env->incrementLoopIndices();
    $loop = $__env->getLastLoop();
    $__empty_1 = false; ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                    <tr>
                                        <td class="text-muted fw-semibold">
                                            <?php echo e($value->field_label ?: $value->field_key); ?>

                                        </td>
                                        <td>
                                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($value->field_type === 'signature' && $value->value) { ?>
                                                <img src="<?php echo e($value->value); ?>" alt="Firma"
                                                     style="max-height: 60px; border: 1px solid #ddd; border-radius: 4px;">
                                            <?php } else { ?>
                                                <?php echo e($value->getDisplayValue() ?: '—'); ?>

                                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                        </td>
                                    </tr>
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
$loop = $__env->getLastLoop();
if ($__empty_1) { ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                                    <tr>
                                        <td colspan="2" class="text-center text-muted py-3">Sin respuestas registradas</td>
                                    </tr>
                                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($submission->utm_source || $submission->utm_medium || $submission->utm_campaign || $submission->utm_term || $submission->referrer_url || $submission->time_to_complete || $submission->user_agent) { ?>
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-1 fw-bold">Metadatos</h5>
                        <p class="mb-0 text-muted">Información técnica del envío</p>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($submission->utm_source) { ?>
                                <div class="col-sm-12 col-md-6">
                                    <label class="form-label fw-semibold text-muted">UTM Source</label>
                                    <p class="mb-0"><?php echo e($submission->utm_source); ?></p>
                                </div>
                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($submission->utm_medium) { ?>
                                <div class="col-sm-12 col-md-6">
                                    <label class="form-label fw-semibold text-muted">UTM Medium</label>
                                    <p class="mb-0"><?php echo e($submission->utm_medium); ?></p>
                                </div>
                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($submission->utm_campaign) { ?>
                                <div class="col-sm-12 col-md-6">
                                    <label class="form-label fw-semibold text-muted">UTM Campaign</label>
                                    <p class="mb-0"><?php echo e($submission->utm_campaign); ?></p>
                                </div>
                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($submission->utm_term) { ?>
                                <div class="col-sm-12 col-md-6">
                                    <label class="form-label fw-semibold text-muted">UTM Term</label>
                                    <p class="mb-0"><?php echo e($submission->utm_term); ?></p>
                                </div>
                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($submission->referrer_url) { ?>
                                <div class="col-12">
                                    <label class="form-label fw-semibold text-muted">Referrer URL</label>
                                    <p class="mb-0 text-truncate" title="<?php echo e($submission->referrer_url); ?>">
                                        <?php echo e($submission->referrer_url); ?>

                                    </p>
                                </div>
                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($submission->time_to_complete) { ?>
                                <div class="col-12">
                                    <label class="form-label fw-semibold text-muted">Tiempo de completado</label>
                                    <p class="mb-0"><?php echo e(gmdate('i:s', $submission->time_to_complete)); ?> min</p>
                                </div>
                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($submission->user_agent) { ?>
                                <div class="col-12">
                                    <label class="form-label fw-semibold text-muted">User Agent</label>
                                    <p class="mb-0 text-muted"><?php echo e($submission->user_agent); ?></p>
                                </div>
                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                        </div>
                    </div>
                </div>
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

        </div>

        
        <div class="col-lg-4">

            
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Estado y asignación</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted">Estado</label>
                        <select id="statusSelect" class="form-select">
                            <option value="new"       <?php echo e(($submission->status ?? 'new') === 'new' ? 'selected' : ''); ?>>Nuevo</option>
                            <option value="in_review" <?php echo e(($submission->status ?? 'new') === 'in_review' ? 'selected' : ''); ?>>En revisión</option>
                            <option value="resolved"  <?php echo e(($submission->status ?? 'new') === 'resolved' ? 'selected' : ''); ?>>Resuelto</option>
                            <option value="rejected"  <?php echo e(($submission->status ?? 'new') === 'rejected' ? 'selected' : ''); ?>>Rechazado</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label fw-semibold text-muted">Asignado a</label>
                        <select id="assignedToSelect" class="form-select">
                            <option value="">— Sin asignar —</option>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $users;
$__env->addLoop($__currentLoopData);
foreach ($__currentLoopData as $user) {
    $__env->incrementLoopIndices();
    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                                <option value="<?php echo e($user->id); ?>" <?php echo e($submission->assigned_to == $user->id ? 'selected' : ''); ?>>
                                    <?php echo e($user->firstname); ?> <?php echo e($user->lastname); ?>

                                </option>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
$loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                        </select>
                    </div>
                </div>
            </div>

            
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Acciones</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?php echo e(route('settings.forms.submissions.pdf', [$form, $submission])); ?>"
                           class="btn btn-primary" target="_blank">
                            Descargar PDF
                        </a>
                        <button type="button" class="btn btn-outline-info" id="resendEmailBtn" data-type="admin">
                            Reenviar email
                        </button>
                        <button type="button" class="btn <?php echo e($submission->is_spam ? 'btn-warning' : 'btn-outline-warning'); ?>" id="toggleSpamBtn">
                            <?php echo e($submission->is_spam ? 'Desmarcar spam' : 'Marcar spam'); ?>

                        </button>
                        <button type="button" class="btn btn-outline-danger" id="deleteSubmissionBtn">
                            Eliminar
                        </button>
                        <a href="<?php echo e(route('settings.forms.submissions.index', $form)); ?>" class="btn btn-outline-secondary">
                            Volver a submissions
                        </a>
                    </div>
                </div>
            </div>

            
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Notas internas</h5>
                </div>
                <div class="card-body">
                    <div id="notesList" class="mb-3">
                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__empty_1 = true;
$__currentLoopData = $submission->notes;
$__env->addLoop($__currentLoopData);
foreach ($__currentLoopData as $note) {
    $__env->incrementLoopIndices();
    $loop = $__env->getLastLoop();
    $__empty_1 = false; ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                            <div class="border-bottom pb-2 mb-2">
                                <strong><?php echo e($note->user ? $note->user->firstname.' '.$note->user->lastname : 'Sistema'); ?></strong>
                                <p class="mb-1"><?php echo e($note->note); ?></p>
                                <span class="text-muted"><?php echo e($note->created_at?->format('d/m/Y H:i')); ?></span>
                            </div>
                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
$loop = $__env->getLastLoop();
if ($__empty_1) { ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                            <p class="text-muted mb-0" id="emptyNotes">Sin notas aún.</p>
                        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                    </div>
                    <form id="addNoteForm">
                        <div class="mb-2">
                            <textarea id="noteText" class="form-control" rows="3"
                                      placeholder="Añadir nota interna..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            Guardar nota
                        </button>
                    </form>
                </div>
            </div>

            
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Estadísticas</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Notas internas:</span>
                            <span class="fw-bold"><?php echo e($submission->notes->count()); ?></span>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Valores registrados:</span>
                            <span class="fw-bold"><?php echo e($submission->values->count()); ?></span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    
    <form id="deleteForm" method="POST"
          action="<?php echo e(route('settings.forms.submissions.destroy', [$form, $submission])); ?>"
          class="d-none">
        <?php echo csrf_field(); ?>
        <?php echo method_field('DELETE'); ?>
    </form>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    const updateStatusUrl = '<?php echo e(route('settings.forms.submissions.status', [$form, $submission])); ?>';
    const assignUrl       = '<?php echo e(route('settings.forms.submissions.assign', [$form, $submission])); ?>';
    const addNoteUrl      = '<?php echo e(route('settings.forms.submissions.notes.add', [$form, $submission])); ?>';
    const toggleSpamUrl   = '<?php echo e(route('settings.forms.submissions.toggle-spam', [$form, $submission])); ?>';
    const resendEmailUrl  = '<?php echo e(route('settings.forms.submissions.resend-email', [$form, $submission])); ?>';

    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    $('#statusSelect').on('change', function () {
        $.ajax({
            url: updateStatusUrl,
            method: 'PATCH',
            data: { status: $(this).val() },
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function () { toastr.success('Estado actualizado'); }
        });
    });

    $('#assignedToSelect').on('change', function () {
        $.ajax({
            url: assignUrl,
            method: 'PATCH',
            data: { assigned_to: $(this).val() || null },
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function () { toastr.success('Asignado correctamente'); }
        });
    });

    $('#addNoteForm').on('submit', function (e) {
        e.preventDefault();
        const note = $('#noteText').val().trim();
        if (!note) { return; }
        $.ajax({
            url: addNoteUrl,
            method: 'POST',
            data: { note: note },
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (res) {
                $('#emptyNotes').remove();
                $('#notesList').prepend(
                    '<div class="border-bottom pb-2 mb-2">' +
                        '<strong>' + res.user + '</strong>' +
                        '<p class="mb-1">' + res.note + '</p>' +
                        '<span class="text-muted">' + res.created_at + '</span>' +
                    '</div>'
                );
                $('#noteText').val('');
                toastr.success('Nota añadida');
            },
            error: function () { toastr.error('Error al añadir nota'); }
        });
    });

    $('#toggleSpamBtn').on('click', function () {
        $.ajax({
            url: toggleSpamUrl,
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (res) {
                toastr.success(res.is_spam ? 'Marcado como spam' : 'Desmarcado como spam');
                location.reload();
            }
        });
    });

    $('#resendEmailBtn').on('click', function () {
        $.ajax({
            url: resendEmailUrl,
            method: 'POST',
            data: { type: $(this).data('type') || 'admin' },
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (res) { toastr.success(res.message); },
            error: function () { toastr.error('Error al reenviar el email'); }
        });
    });

    $('#deleteSubmissionBtn').on('click', function () {
        if (confirm('¿Eliminar esta submission? Esta acción no se puede deshacer.')) {
            $('#deleteForm').submit();
        }
    });
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.theme', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/developerts/Herd/system/modules/Forms/resources/views/settings/submissions/show.blade.php ENDPATH**/ ?>
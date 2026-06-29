{{-- Modal: Aprobar etapa actual --}}
<div class="modal fade" id="approveStageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <div>
                    <h5 class="modal-title fw-bold mb-1">
                        Aprobar etapa actual
                    </h5>
                    <p class="text-muted small mb-0">Confirma la aprobación para avanzar en el flujo</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{-- Current Stage Info --}}
                <div class="d-flex align-items-center gap-3 p-3 bg-light rounded mb-3">
                    <div class="d-flex align-items-center justify-content-center rounded-circle bg-primary-subtle" style="width: 48px; height: 48px;">
                        <i class="fas fa-layer-group text-primary fs-5"></i>
                    </div>
                    <div>
                        <p class="mb-0 fw-semibold">Etapa {{ $document->current_stage }} de {{ $document->total_stages }}</p>
                        <small class="text-muted">Grupo: {{ $document->getCurrentStageLabel() ?? 'Sin asignar' }}</small>
                    </div>
                </div>

                @if($document->current_stage < $document->total_stages)
                    {{-- Next Stage Preview - Cargado via AJAX --}}
                    <div class="alert bg-primary-subtle border-0 mb-3" id="nextStageInfo">
                        <div class="d-flex align-items-center justify-content-center">
                            <i class="fas fa-spinner fa-spin text-primary"></i>
                        </div>
                    </div>

                    {{-- Asignación de usuario - Cargado via AJAX --}}
                    <div class="mb-3" id="assignUserContainer" style="display: none;">
                        <label class="form-label fw-semibold small">
                            Asignar a usuario específico
                        </label>
                        <select class="form-select select2 form-select-sm" id="assignToUser">
                            <option value="">Cargando...</option>
                        </select>
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>Opcional: asigna a una persona o deja que todo el grupo lo vea
                        </small>
                    </div>
                @else
                    {{-- Final Stage --}}
                    <div class="alert bg-primary-subtle border-0 mb-3">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-check-double text-primary me-2 mt-1"></i>
                            <div>
                                <small class="fw-semibold d-block text-primary">Última etapa</small>
                                <small class="text-dark">El documento será marcado como completamente aprobado</small>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="mb-0">
                    <label class="form-label fw-semibold small">Comentarios <span class="text-muted fw-normal">(opcional)</span></label>
                    <textarea class="form-control form-control-sm" id="approveStageComments" rows="3"
                              placeholder="Agregar comentarios sobre la aprobación..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-primary w-100 mb-1" id="btnConfirmApproveStage">
                    Aprobar
                </button>
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        // Inicializar select2 dentro del modal con configuración específica
        function initializeModalSelect2() {
            $('#assignToUser').select2({
                dropdownParent: $('#approveStageModal'),
                width: '100%',
                minimumResultsForSearch: Infinity
            });
        }

        // Cargar información del siguiente grupo cuando se abre el modal
        $('#approveStageModal').on('show.bs.modal', function() {
            @if($document->current_stage < $document->total_stages)
            // Cargar datos del siguiente grupo via AJAX
            $.ajax({
                url: "{{ route('api.documents.next-stage-info', $document->uid) }}",
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        // Actualizar info del siguiente grupo
                        $('#nextStageInfo').html(`
                            <div class="d-flex align-items-start">
                                <i class="fas fa-arrow-right text-primary me-2 mt-1"></i>
                                <div>
                                    <small class="fw-semibold d-block text-primary">Siguiente etapa</small>
                                    <small class="text-dark">${response.next_group_label} - Se notificará al grupo o usuario asignado</small>
                                </div>
                            </div>
                        `);

                        // Cargar usuarios del siguiente grupo
                        if (response.users && response.users.length > 0) {
                            let options = `<option value="">Todo el grupo (${response.users.length} usuarios)</option>`;
                            response.users.forEach(user => {
                                const priority = user.is_primary ? ' (Primario)' : '';
                                options += `<option value="${user.id}">${user.name}${priority}</option>`;
                            });
                            $('#assignToUser').html(options);
                            $('#assignUserContainer').show();

                            // Inicializar select2 después de cargar las opciones
                            initializeModalSelect2();
                        }
                    }
                },
                error: function() {
                    $('#nextStageInfo').html(`
                        <div class="d-flex align-items-start">
                            <i class="fas fa-exclamation-triangle text-warning me-2 mt-1"></i>
                            <div>
                                <small class="text-muted">No se pudo cargar la información del siguiente grupo</small>
                            </div>
                        </div>
                    `);
                }
            });
            @endif
        });

        // ===== Handler: Aprobar Etapa Actual =====
        $('#btnConfirmApproveStage').on('click', function() {
            const $btn = $(this);
            const comments = $('#approveStageComments').val();
            const assignedUserId = $('#assignToUser').val();

            // Deshabilitar botón y mostrar loading
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Aprobando...');

            $.ajax({
                url: "{{ route('api.documents.approve-stage', $document->uid) }}",
                method: 'POST',
                data: {
                    comments: comments,
                    assigned_user_id: assignedUserId || null,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message, 'Éxito', {
                            closeButton: true,
                            progressBar: true,
                            positionClass: "toast-bottom-right"
                        });

                        // Cerrar modal y recargar página después de 1.5s
                        $('#approveStageModal').modal('hide');
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'Error al aprobar la etapa';
                    toastr.error(message, 'Error', {
                        closeButton: true,
                        progressBar: true,
                        positionClass: "toast-bottom-right"
                    });

                    // Restaurar botón
                    $btn.prop('disabled', false).html('<i class="fas fa-check-circle me-2"></i>Aprobar y Continuar');
                }
            });
        });

        // Limpiar modal al cerrar
        $('#approveStageModal').on('hidden.bs.modal', function() {
            $('#approveStageComments').val('');

            // Destruir select2 antes de limpiar
            if ($('#assignToUser').hasClass('select2-hidden-accessible')) {
                $('#assignToUser').select2('destroy');
            }

            $('#assignToUser').val('');
            $('#assignUserContainer').hide();

            $('#btnConfirmApproveStage')
                .prop('disabled', false)
                .html('<i class="fas fa-check-circle me-2"></i>Aprobar y Continuar');
        });
    });
</script>
@endpush

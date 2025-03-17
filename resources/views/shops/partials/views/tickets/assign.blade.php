<div class="card">
    <div class="card-body">
        <h4 class="card-title mb-4">Asignaciones</h4>
        <form action="" class="form-horizontal">
            <div class="row">
                    <div class="col-lg-12">

                        <div class="btn-group  w-100 mt-1 mb-1">
                                @php
                                    $isSelfAssigned = $ticket->selfassignuser_id != null;
                                    $hasMultipleAssignments = $ticket->assigns->isNotEmpty();
                                    $isSingleAssignment = $ticket->assigns->count() === 1;
                                @endphp

                                @if(!$hasMultipleAssignments && !$isSelfAssigned)
                                    <button type="button" class="btn btn-light dropdown-toggle text-left" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        Asignar
                                    </button>
                                    <ul class="dropdown-menu animated rubberBand">
                                        <li>
                                            <a class="dropdown-item" href="javascript:void(0);" id="selfassigid" data-uid="{{ $ticket->uid }}">
                                                Asignarmelo
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="javascript:void(0);" id="assigned" data-uid="{{ $ticket->uid }}">
                                                Otra asignacion
                                            </a>
                                        </li>
                                    </ul>
                                @elseif($isSelfAssigned)
                                    <button type="button" class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown" disabled>
                                        {{ $ticket->selfassign->name }} ({{ lang('Self') }})
                                    </button>
                                    <button type="button" class="btn btn-primary " data-uid="{{ $ticket->uid }}" id="btnremove" data-bs-toggle="tooltip" title="Desasignar">
                                        <i class="fa-duotone fa-close"></i>
                                    </button>
                                @elseif($hasMultipleAssignments)
                                    @if($isSingleAssignment)
                                        @php
                                            $assigneddata = $ticket->ticketassignmutliples->first();
                                            $assigneduserdata = \App\Models\User::find($assigneddata->toassignuser_id);
                                        @endphp
                                        <button type="button" class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown" disabled>
                                            {{ $assigneduserdata->name }} (Otra asignacion)
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown" disabled>
                                            Multple asignacion
                                        </button>
                                    @endif
                                    <button type="button" class="btn btn-primary " data-uid="{{ $ticket->uid }}" id="btnremove" data-bs-toggle="tooltip" title="Desasignar">
                                        <i class="fa-duotone fa-close"></i>
                                    </button>
                                @endif
                        </div>
                        <p class="fs-2 mb-0 mt-2">
                            Administre las asignaciones para este ticket, incluidas las asignaciones propias y múltiples.
                        </p>
                    </div>
                </div>
        </form>
    </div>
</div>


@include ('callcenters.partials.modals.tickets.assign')

@push('scripts')
    <script type="text/javascript">
        $(document).ready(function () {
            "use strict";

            $('#selfassigid').on('click', function (e) {

                e.preventDefault();

                const uid = $(this).data('uid');

                $.ajax({
                    method: 'POST',
                    url: '{{ route('callcenter.tickets.selfassign') }}',
                    data: {
                        uid: uid
                    },
                    success: function (data) {

                        toastr.success("El ticket se ha asignado correctamente.", "Operación fallida", {
                            closeButton: true,
                            progressBar: true,
                            positionClass: "toast-bottom-right"
                        });

                        location.reload();
                    },
                    error: function () {

                        toastr.warning("Ocurrió un error al intentar asignar el ticket.", "Operación fallida", {
                            closeButton: true,
                            progressBar: true,
                            positionClass: "toast-bottom-right"
                        });

                    }
                });
            });

            $('#assigned').on('click', function () {

                const uid = $(this).data('uid');

                $('.modalassign').select2({
                    dropdownParent: ".sprukosearch",
                    minimumResultsForSearch: '',
                    placeholder: "Buscar",
                    width: '100%'
                });

                $.ajax({
                    method: 'POST',
                    url: '{{ route('callcenter.tickets.ticketassigneds') }}',
                    data: {
                        uid: uid
                    },
                    success: function (data) {
                        if (data.success) {
                            // Rellenar el campo select con las opciones devueltas
                            $('#assigned_id').html(data.table_data);

                            // Actualizar título del modal y mostrarlo
                            $(".modal-title-assign").text('Asignar a un agente');
                            $('#addassigned').modal('show');
                        } else {
                            toastr.error("No se pudieron cargar los datos de asignación.", "Error", {
                                closeButton: true,
                                progressBar: true,
                                positionClass: "toast-bottom-right"
                            });
                        }
                    },
                    error: function () {
                        toastr.warning("Ocurrió un error al cargar los datos de asignación.", "Operación fallida", {
                            closeButton: true,
                            progressBar: true,
                            positionClass: "toast-bottom-right"
                        });
                    }
                });

            });

            $('#reopen').on('click', function () {
                const reopenId = $(this).data('id');

                $.ajax({
                    type: 'POST',
                    url: SITEURL + "/admin/ticket/reopen/" + reopenId,
                    data: { reopenid: reopenId },
                    success: function (data) {
                        toastr.success("El ticket se ha reabierto correctamente.");
                        location.reload();
                    },
                    error: function () {
                        toastr.error("Ocurrió un error al intentar reabrir el ticket.");
                    }
                });
            });

            $('#btnremove').on('click', function () {
                const uid = $(this).data('uid');

                toastr.warning(
                    `<div class="custom-toastr">
            <div class="custom-body">
                <h4>¿Está seguro que desea desasignar este agente?</h4>
                <p>Este agente ya no estará asignado a este ticket.</p>
                <div class="toastr-buttons">
                    <button class="btn btn-danger confirm-remove" data-uid="${uid}">Sí, desasignar</button>
                    <button class="btn btn-secondary cancel-remove">Cancelar</button>
                </div>
            </div>
        </div>`,
                    "", // Título vacío para centrar el contenido
                    {
                        closeButton: false, // Desactiva el botón de cerrar
                        tapToDismiss: false, // Evita cerrar con clic fuera
                        timeOut: 0, // Desactiva tiempo de auto cierre
                        extendedTimeOut: 0,
                        positionClass: "toast-center", // Clase personalizada para centrar
                    }
                );

                // Evento para confirmar la desasignación
                $('body').on('click', '.confirm-remove', function () {
                    const uid = $(this).data('uid'); // Recuperar el UID del botón

                    // Remover completamente el toastr actual
                    $('.custom-toastr').closest('.toast-warning').remove();

                    // Solicitud AJAX para desasignar el agente
                    $.ajax({
                        method: 'POST',
                        url: '{{ route('callcenter.tickets.ticketunassigns') }}',
                        data: {
                            uid: uid,
                        },
                        success: function (data) {
                            if (data.success) {
                                toastr.success("El agente fue desasignado correctamente.", "Operación exitosa", {
                                    closeButton: true,
                                    progressBar: true,
                                    positionClass: "toast-bottom-right",
                                });
                                location.reload(); // Recargar la página para reflejar los cambios
                            } else {
                                toastr.error("No se pudo desasignar el agente.", "Error", {
                                    closeButton: true,
                                    progressBar: true,
                                    positionClass: "toast-bottom-right",
                                });
                            }
                        },
                        error: function () {
                            toastr.error("Ocurrió un error al procesar la solicitud.", "Operación fallida", {
                                closeButton: true,
                                progressBar: true,
                                positionClass: "toast-bottom-right",
                            });
                        },
                    });
                });

                // Evento para cancelar la operación
                $('body').on('click', '.cancel-remove', function () {
                    // Remover completamente el toastr actual
                    $('.custom-toastr').closest('.toast-warning').remove();

                    // Mostrar mensaje de cancelación sin interferir con otros toastr
                    toastr.info("Operación cancelada.", "Cancelado", {
                        closeButton: true,
                        progressBar: true,
                        positionClass: "toast-bottom-right",
                    });
                });
            });


        });
    </script>
@endpush

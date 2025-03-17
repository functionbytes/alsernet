@extends('layouts.shops')

@section('content')

    @include('managers.includes.card', ['title' => 'Historial de ' . Str::upper($subscriber->firstname . ' ' . $subscriber->lastname)])

    <div class="widget-content searchable-container list">
        <div class="card card-body">
            <div class="table-responsive">
                <table class="table search-table align-middle text-nowrap">
                    <thead class="header-item">
                    <tr>
                        <th>Acción</th>
                        <th>Usuario</th>
                        <th>Fecha</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($logs as $log)
                        <tr class="search-items view-log" data-id="{{ $log->id }}" data-properties="{{ json_encode($log->properties) }}"  data-user="{{ json_encode($log->user_properties) }}"  data-causer="{{ json_encode($log->causer->getFullNameAttribute()) }}">
                            <td>
                                <span class="badge bg-light-secondary rounded-3 py-2 text-primary fw-semibold fs-2 d-inline-flex align-items-center gap-1">
                                      {{ $log->log_name }}
                                </span>
                            </td>
                            <td>{{ $log->causer ? $log->causer->getFullNameAttribute() : 'Sistema' }}</td>
                            <td>{{ $log->created_at->format('Y-m-d H:i') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="result-body">
                <span>Mostrando {{ $logs->firstItem() }}-{{ $logs->lastItem() }} de {{ $logs->total() }} resultados</span>
                <nav>{{ $logs->appends(request()->input())->links() }}</nav>
            </div>
        </div>
    </div>


    <!-- Modal para Detalles del Log -->
    <div class="modal fade logs-container" id="logDetailsModal" tabindex="-1" aria-labelledby="logDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="logDetailsModalLabel">Detalles del Log</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">


                    <h6><strong>ID Log:</strong> <span id="logAction"></span></h6>
                    <h6><strong>Causer:</strong> <span id="logCauser"></span></h6>
                    <h6><strong>Fecha:</strong> <span id="logDate"></span></h6>
                    <h6><strong>Usuario:</strong> <span id="logUser"></span></h6>
                    <h6><strong>Detalles:</strong></h6>


                    <div class="highlight">
                        <pre>
                            <code id="logPropertiesOld"></code>
                        </pre>
                    </div>

                    <div class="highlight mt-2">
                        <pre>
                            <code id="logPropertiesNew"></code>
                        </pre>
                    </div>

                </div>
            </div>
        </div>
    </div>


@endsection
@push('scripts')
    <script>
        $(document).ready(function () {
            $(".view-log").on("click", function () {
                let logId = $(this).data("id");
                let properties = $(this).data("properties") ? JSON.parse($(this).data("properties")) : {};
                let user = $(this).data("user") ? JSON.parse($(this).data("user")) : { name: "Sistema" };
                let causer = $(this).data("causer") ? $(this).data("causer") : "Sistema";

                // Extraer old_value y new_value si existen
                let oldValue = properties.old_value ? JSON.stringify(properties.old_value, null, 4) : "Sin datos previos";
                let newValue = properties.new_value ? JSON.stringify(properties.new_value, null, 4) : "Sin cambios";

                // Insertar datos en el modal
                $("#logAction").text(logId);
                $("#logDate").text(new Date().toLocaleString());
                $("#logUser").text(user.name || "Sistema");
                $("#logCauser").text(causer || "Sistema");

                // Insertar valores formateados en dos bloques separados
                $("#logPropertiesOld").html(oldValue);
                $("#logPropertiesNew").html(newValue);


                // Mostrar el modal
                $("#logDetailsModal").modal("show");
            });
        });

    </script>
@endpush


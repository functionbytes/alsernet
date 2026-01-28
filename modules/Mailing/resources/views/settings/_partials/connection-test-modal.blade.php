{{-- Connection Test Modal Component
    Parameters:
    - modalId: string (default: 'connectionTestModal')
    - testRoute: string (route for testing connection)
    - label: string (label for the modal)
--}}

@php
    $modalId = $modalId ?? 'connectionTestModal';
    $testRoute = $testRoute ?? '#';
    $label = $label ?? 'Prueba de conexión';
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            {{-- Modal Header --}}
            <div class="modal-header border-bottom">
                <h5 class="modal-title" id="{{ $modalId }}Label">
                    <i class="fas fa-wifi me-2"></i>{{ $label }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            {{-- Modal Body --}}
            <div class="modal-body">
                {{-- Initial State - Test Button --}}
                <div id="{{ $modalId }}InitialState" class="text-center py-3">
                    <i class="fas fa-plug fa-3x mb-3 text-muted opacity-50"></i>
                    <p class="text-muted mb-0">
                        Haz clic en "Iniciar prueba" para verificar la conexión con el servidor.
                    </p>
                </div>

                {{-- Loading State --}}
                <div id="{{ $modalId }}LoadingState" style="display: none;" class="text-center py-4">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Probando conexión...</span>
                    </div>
                    <p class="text-muted">Probando conexión...</p>
                </div>

                {{-- Success State --}}
                <div id="{{ $modalId }}SuccessState" style="display: none;">
                    <div class="alert alert-success border-0 mb-3" role="alert">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-check-circle text-success fs-5 me-3 mt-1"></i>
                            <div>
                                <h6 class="alert-heading fw-bold mb-2">¡Conexión exitosa!</h6>
                                <p class="mb-0 small">El servidor respondió correctamente.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Success Details --}}
                    <div class="card bg-light border-0 mb-3">
                        <div class="card-body">
                            <div class="row g-2 small">
                                <div class="col-6">
                                    <div class="mb-2">
                                        <label class="fw-semibold text-muted d-block">Estado</label>
                                        <span id="{{ $modalId }}StatusValue" class="badge bg-success">Conectado</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-2">
                                        <label class="fw-semibold text-muted d-block">Latencia</label>
                                        <span id="{{ $modalId }}LatencyValue" class="text-dark fw-bold">-- ms</span>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div>
                                        <label class="fw-semibold text-muted d-block">Información</label>
                                        <small id="{{ $modalId }}InfoValue" class="text-dark d-block">Credenciales verificadas</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Error State --}}
                <div id="{{ $modalId }}ErrorState" style="display: none;">
                    <div class="alert alert-danger border-0 mb-3" role="alert">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-exclamation-circle text-danger fs-5 me-3 mt-1"></i>
                            <div>
                                <h6 class="alert-heading fw-bold mb-2">¡Error de conexión!</h6>
                                <p id="{{ $modalId }}ErrorMessage" class="mb-0 small">
                                    No se pudo conectar con el servidor.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Error Details --}}
                    <div class="card bg-light border-0 border-danger border-start border-4 mb-3">
                        <div class="card-body">
                            <h6 class="fw-bold text-danger mb-2">
                                <i class="fas fa-triangle-exclamation me-2"></i>Detalles del error
                            </h6>
                            <pre id="{{ $modalId }}ErrorDetails" class="small mb-0 text-break" style="overflow-x: auto;">Error desconocido</pre>
                        </div>
                    </div>

                    {{-- Troubleshooting Tips --}}
                    <div class="card bg-info-subtle border-0 mb-3">
                        <div class="card-body">
                            <h6 class="fw-bold text-info mb-2">
                                <i class="fas fa-lightbulb me-2"></i>Sugerencias
                            </h6>
                            <ul class="small mb-0 ps-3">
                                <li>Verifica que la configuración sea correcta</li>
                                <li>Comprueba la conexión de red</li>
                                <li>Asegúrate de que las credenciales sean válidas</li>
                                <li>Revisa si el firewall permite la conexión</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="modal-footer border-top">
                <button type="button"
                        id="{{ $modalId }}RetryBtn"
                        class="btn btn-primary"
                        onclick="testConnection('{{ $testRoute }}', '{{ $modalId }}')">
                    <i class="fas fa-sync-alt me-2"></i>Iniciar prueba
                </button>
                <button type="button"
                        id="{{ $modalId }}CloseBtn"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- JavaScript for Connection Testing --}}
@push('scripts')
<script>
    function testConnection(testRoute, modalId) {
        const modal = {
            id: modalId,
            initialState: document.getElementById(modalId + 'InitialState'),
            loadingState: document.getElementById(modalId + 'LoadingState'),
            successState: document.getElementById(modalId + 'SuccessState'),
            errorState: document.getElementById(modalId + 'ErrorState'),
            retryBtn: document.getElementById(modalId + 'RetryBtn'),
            closeBtn: document.getElementById(modalId + 'CloseBtn'),
            statusValue: document.getElementById(modalId + 'StatusValue'),
            latencyValue: document.getElementById(modalId + 'LatencyValue'),
            infoValue: document.getElementById(modalId + 'InfoValue'),
            errorMessage: document.getElementById(modalId + 'ErrorMessage'),
            errorDetails: document.getElementById(modalId + 'ErrorDetails')
        };

        // Show loading state
        modal.initialState.style.display = 'none';
        modal.successState.style.display = 'none';
        modal.errorState.style.display = 'none';
        modal.loadingState.style.display = 'block';
        modal.retryBtn.disabled = true;

        // Get form data
        const formData = new FormData();
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

        // Get all form inputs and add them to formData
        const form = document.querySelector('[id^="form"]');
        if (form) {
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                if (input.name && input.value) {
                    formData.append(input.name, input.value);
                }
            });
        }

        const startTime = Date.now();

        // Send test request
        fetch(testRoute, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const latency = Date.now() - startTime;

            if (data.success) {
                // Show success state
                modal.loadingState.style.display = 'none';
                modal.successState.style.display = 'block';
                modal.statusValue.textContent = 'Conectado';
                modal.latencyValue.textContent = latency + ' ms';
                modal.infoValue.textContent = data.message || 'Credenciales verificadas';
            } else {
                // Show error state
                modal.loadingState.style.display = 'none';
                modal.errorState.style.display = 'block';
                modal.errorMessage.textContent = data.message || 'Error desconocido';
                modal.errorDetails.textContent = data.details || 'Sin detalles adicionales';
            }
        })
        .catch(error => {
            // Show error state
            modal.loadingState.style.display = 'none';
            modal.errorState.style.display = 'block';
            modal.errorMessage.textContent = 'Error en la solicitud';
            modal.errorDetails.textContent = error.message;
        })
        .finally(() => {
            modal.retryBtn.disabled = false;
        });
    }

    // Reset modal when it's shown
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('{{ $modalId }}');
        if (modal) {
            modal.addEventListener('show.bs.modal', function() {
                document.getElementById('{{ $modalId }}InitialState').style.display = 'block';
                document.getElementById('{{ $modalId }}LoadingState').style.display = 'none';
                document.getElementById('{{ $modalId }}SuccessState').style.display = 'none';
                document.getElementById('{{ $modalId }}ErrorState').style.display = 'none';
                document.getElementById('{{ $modalId }}RetryBtn').disabled = false;
            });
        }
    });
</script>
@endpush

@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Configuración de API de Mailrelay'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <form method="POST" action="{{ route('settings.mailrelay.api.update') }}" id="formMailrelayApi">
            @method('PATCH')
            @csrf

            <!-- Credenciales de API Card -->
            <div class="card mb-3">
                <div class="card-header p-4 border-bottom border-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 fw-bold">Credenciales de API</h6>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="alert alert-info border-0 bg-info-subtle mb-4">
                        <div class="d-flex align-items-start gap-2">
                            <i class="fas fa-shield-halved text-info fs-5"></i>
                            <div>
                                <small class="fw-semibold">Las credenciales se almacenan de forma segura</small>
                                <small class="d-block">Tus credenciales están cifradas y protegidas en la base de datos.</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- API Key -->
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label for="apiKey" class="form-label">
                                    API Key <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="password"
                                           class="form-control @error('api_key') is-invalid @enderror"
                                           id="apiKey"
                                           name="api_key"
                                           value="{{ old('api_key', $apiSettings['api_key'] ?? '') }}"
                                           placeholder="Ingrese su API Key de Mailrelay"
                                           required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggleApiKey">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    @error('api_key')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                                <small class="text-muted">Disponible en tu panel de Mailrelay</small>
                            </div>
                        </div>

                        <!-- Host/URL -->
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label for="apiUrl" class="form-label">
                                    Host/URL de Mailrelay <span class="text-danger">*</span>
                                </label>
                                <input type="url"
                                       class="form-control @error('api_url') is-invalid @enderror"
                                       id="apiUrl"
                                       name="api_url"
                                       value="{{ old('api_url', $apiSettings['api_url'] ?? '') }}"
                                       placeholder="https://api.mailrelay.com"
                                       required>
                                @error('api_url')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                                <small class="text-muted">Ejemplo: https://api.mailrelay.com</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Prueba de Conexión Card -->
            <div class="card mb-3">
                <div class="card-header p-4 border-bottom border-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 fw-bold">Prueba de conexión</h6>
                        </div>
                        <button type="button" class="btn btn-info" id="testConnectionBtn">
                            <i class="fas fa-plug me-1"></i> Probar conexión
                        </button>
                    </div>
                </div>

                <!-- Connection Test Result (Hidden by default) -->
                <div class="card-body border-bottom" id="connectionStatusContainer" style="display: none;">
                    <div id="connectionStatusContent"></div>
                </div>
            </div>

            <!-- Información de la Cuenta Card -->
            @if(isset($accountInfo) && $accountInfo)
            <div class="card mb-3">
                <div class="card-header p-4 border-bottom border-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 fw-bold">Información de la cuenta</h6>
                        </div>
                        @if(isset($accountInfo['status']))
                        <span class="badge {{ $accountInfo['status'] === 'active' ? 'bg-success' : 'bg-warning' }}">
                            {{ $accountInfo['status'] === 'active' ? 'Conectado' : 'Desconectado' }}
                        </span>
                        @endif
                    </div>
                </div>

                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-3">
                            <div class="card bg-light-secondary stat-card h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div>
                                            <h6 class="card-title text-primary mb-2">
                                                Nombre de cuenta
                                            </h6>
                                            <h5 class="mb-0 fw-bold">{{ $accountInfo['account_name'] ?? 'N/A' }}</h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-3">
                            <div class="card bg-light-secondary stat-card h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div>
                                            <h6 class="card-title text-info mb-2">
                                                Plan contratado
                                            </h6>
                                            <h5 class="mb-0 fw-bold">{{ $accountInfo['plan'] ?? 'N/A' }}</h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-3">
                            <div class="card bg-light-secondary stat-card h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div>
                                            <h6 class="card-title text-success mb-2">
                                                Créditos disponibles
                                            </h6>
                                            <h5 class="mb-0 fw-bold">{{ number_format($accountInfo['credits'] ?? 0) }}</h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-3">
                            <div class="card bg-light-secondary stat-card h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div>
                                            <h6 class="card-title mb-2">
                                                Estado de conexión
                                            </h6>
                                            <span class="badge {{ isset($accountInfo['status']) && $accountInfo['status'] === 'active' ? 'bg-success' : 'bg-danger' }} fs-6">
                                                {{ isset($accountInfo['status']) && $accountInfo['status'] === 'active' ? 'Activa' : 'Inactiva' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Opciones de API Card -->
            <div class="card mb-3">
                <div class="card-header p-4 border-bottom border-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 fw-bold">Opciones de API</h6>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <!-- Cachear respuestas -->
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           role="switch"
                                           id="cacheEnabled"
                                           name="cache_enabled"
                                           value="1"
                                           {{ old('cache_enabled', $apiSettings['cache_enabled'] ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="cacheEnabled">
                                        Cachear respuestas API
                                    </label>
                                </div>
                                <small class="text-muted">Mejora el rendimiento almacenando temporalmente las respuestas</small>
                            </div>
                        </div>

                        <!-- Tiempo de caché -->
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label for="cacheTtl" class="form-label">
                                    Tiempo de caché (minutos)
                                </label>
                                <input type="number"
                                       class="form-control @error('cache_ttl') is-invalid @enderror"
                                       id="cacheTtl"
                                       name="cache_ttl"
                                       value="{{ old('cache_ttl', $apiSettings['cache_ttl'] ?? 60) }}"
                                       min="1"
                                       max="1440"
                                       placeholder="60">
                                @error('cache_ttl')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                                <small class="text-muted">Mínimo: 1 minuto, Máximo: 1440 minutos (24 horas)</small>
                            </div>
                        </div>

                        <!-- Retry automático -->
                        <div class="col-12">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           role="switch"
                                           id="retryEnabled"
                                           name="retry_enabled"
                                           value="1"
                                           {{ old('retry_enabled', $apiSettings['retry_enabled'] ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="retryEnabled">
                                        Retry automático en errores
                                    </label>
                                </div>
                                <small class="text-muted">Reintenta automáticamente las peticiones fallidas a la API</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('settings.mailrelay.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Guardar configuración
                        </button>
                    </div>
                </div>
            </div>

        </form>

    </div>

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Toggle API Key visibility
        $('#toggleApiKey').on('click', function() {
            var input = $('#apiKey');
            var icon = $(this).find('i');

            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                input.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });

        // Enable/disable cache duration based on cache_enabled
        $('#cacheEnabled').on('change', function() {
            $('#cacheDuration').prop('disabled', !this.checked);
        });

        // Initialize cache duration state
        $('#cacheDuration').prop('disabled', !$('#cacheEnabled').is(':checked'));

        // Test API Connection
        $('#testConnectionBtn').on('click', function() {
            var btn = $(this);
            var apiKey = $('#apiKey').val();
            var apiUrl = $('#apiUrl').val();

            if (!apiKey || !apiUrl) {
                toastr.warning('Por favor, ingrese la API Key y el URL antes de probar la conexión', 'Advertencia');
                return;
            }

            btn.prop('disabled', true);
            btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Probando...');

            $.ajax({
                url: '{{ route("settings.mailrelay.api.test-connection") }}',
                type: 'POST',
                dataType: 'json',
                data: {
                    api_key: apiKey,
                    api_url: apiUrl
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(data) {
                    if (data.success) {
                        // Show success message
                        var accountInfo = data.account_info ?
                            ' (Cuenta: ' + data.account_info.account_name + ')' : '';
                        toastr.success('Conexión establecida correctamente' + accountInfo, 'API de Mailrelay');

                        $('#connectionStatusContainer').show();
                        $('#connectionStatusContent').html(
                            '<div class="alert alert-success border-0 bg-success-subtle">' +
                            '    <div class="d-flex align-items-start gap-2">' +
                            '        <i class="fas fa-circle-check text-success fs-5"></i>' +
                            '        <div>' +
                            '            <strong class="text-success">Conexión exitosa</strong>' +
                            '            <small class="d-block">' + data.message + '</small>' +
                            (data.account_info ?
                            '            <small class="d-block mt-2"><strong>Cuenta:</strong> ' + data.account_info.account_name + '</small>' +
                            '            <small class="d-block"><strong>Plan:</strong> ' + (data.account_info.plan || 'N/A') + '</small>' +
                            '            <small class="d-block"><strong>Créditos:</strong> ' + (data.account_info.credits || 0) + '</small>' : '') +
                            '        </div>' +
                            '    </div>' +
                            '</div>'
                        );
                    } else {
                        // Show error message
                        $('#connectionStatusContainer').show();
                        $('#connectionStatusContent').html(
                            '<div class="alert alert-danger border-0 bg-danger-subtle">' +
                            '    <div class="d-flex align-items-start gap-2">' +
                            '        <i class="fas fa-circle-xmark text-danger fs-5"></i>' +
                            '        <div>' +
                            '            <strong class="text-danger">Error de conexión</strong>' +
                            '            <small class="d-block">' + data.message + '</small>' +
                            '        </div>' +
                            '    </div>' +
                            '</div>'
                        );
                        toastr.error('No se pudo conectar a la API de Mailrelay', 'Error');
                    }
                },
                error: function(xhr, status, error) {
                    $('#connectionStatusContainer').show();
                    var errorMessage = 'Error desconocido';

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (error) {
                        errorMessage = error;
                    }

                    $('#connectionStatusContent').html(
                        '<div class="alert alert-danger border-0 bg-danger-subtle">' +
                        '    <div class="d-flex align-items-start gap-2">' +
                        '        <i class="fas fa-circle-xmark text-danger fs-5"></i>' +
                        '        <div>' +
                        '            <strong class="text-danger">Error en la solicitud</strong>' +
                        '            <small class="d-block">' + errorMessage + '</small>' +
                        '        </div>' +
                        '    </div>' +
                        '</div>'
                    );
                    toastr.error('Error al probar la conexión', 'Error');
                },
                complete: function() {
                    btn.prop('disabled', false);
                    btn.html('<i class="fas fa-plug me-1"></i> Probar conexión');
                }
            });
        });

        // Form validation
        $('#formMailrelayApi').validate({
            rules: {
                api_key: {
                    required: true,
                    minlength: 10
                },
                api_url: {
                    required: true,
                    url: true
                },
                cache_ttl: {
                    number: true,
                    min: 1,
                    max: 1440
                }
            },
            messages: {
                api_key: {
                    required: 'La API Key es obligatoria',
                    minlength: 'La API Key debe tener al menos 10 caracteres'
                },
                api_url: {
                    required: 'El URL de la API es obligatorio',
                    url: 'Por favor, ingrese una URL válida'
                },
                cache_ttl: {
                    number: 'Debe ser un número válido',
                    min: 'Mínimo 1 minuto',
                    max: 'Máximo 1440 minutos (24 horas)'
                }
            },
            errorPlacement: function(error, element) {
                error.addClass('field-validation-error');

                if (element.parent('.input-group').length) {
                    error.insertAfter(element.parent('.input-group'));
                } else {
                    error.insertAfter(element);
                }
            }
        });
    });
</script>
@endpush

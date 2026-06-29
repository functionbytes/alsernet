{{-- SLA Policies Configuration Section --}}
<div class="sla-settings">

    <!-- Section Header -->
    <div class="mb-4">
        <h5 class="fw-bold mb-2">
            <i class="ti ti-clock text-primary me-2"></i>
            Politicas SLA
        </h5>
        <p class="text-muted mb-0">Configura los acuerdos de nivel de servicio para la gestion de documentos, incluyendo tiempos limite y escalamientos.</p>
    </div>

    <!-- SLA Health Overview -->
    <div class="row g-3 mb-4">
        <div class="col-md-8">
            <div class="card border h-100">
                <div class="card-header bg-light py-3">
                    <h6 class="fw-semibold mb-0">
                        <i class="ti ti-chart-bar me-2"></i>
                        Estado Actual del SLA
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Compliance Rate Progress -->
                        <div class="col-md-6">
                            <div class="text-center mb-3">
                                <div class="position-relative d-inline-block">
                                    <div class="progress-circle" data-value="{{ $slaStats['compliance_rate'] ?? 0 }}">
                                        <svg viewBox="0 0 100 100" width="120" height="120">
                                            <circle cx="50" cy="50" r="45" fill="none" stroke="#e9ecef" stroke-width="8"/>
                                            <circle cx="50" cy="50" r="45" fill="none"
                                                    stroke="{{ ($slaStats['compliance_rate'] ?? 0) >= 90 ? '#13C672' : (($slaStats['compliance_rate'] ?? 0) >= 70 ? '#FEC90F' : '#FA896B') }}"
                                                    stroke-width="8"
                                                    stroke-dasharray="{{ 2 * 3.14159 * 45 * ($slaStats['compliance_rate'] ?? 0) / 100 }} {{ 2 * 3.14159 * 45 }}"
                                                    stroke-linecap="round"
                                                    transform="rotate(-90 50 50)"/>
                                        </svg>
                                        <div class="position-absolute top-50 start-50 translate-middle">
                                            <span class="fs-4 fw-bold">{{ number_format($slaStats['compliance_rate'] ?? 0, 0) }}%</span>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-muted mb-0 mt-2">Tasa de Cumplimiento</p>
                            </div>
                        </div>

                        <!-- SLA Stats -->
                        <div class="col-md-6">
                            <div class="d-flex flex-column gap-3">
                                <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                                    <span class="text-muted">Dentro de SLA</span>
                                    <span class="badge bg-success-subtle text-success">{{ $slaStats['within_sla'] ?? 0 }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                                    <span class="text-muted">Por vencer (24h)</span>
                                    <span class="badge bg-warning-subtle text-warning">{{ $slaStats['expiring_soon'] ?? 0 }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                                    <span class="text-muted">Fuera de SLA</span>
                                    <span class="badge bg-danger-subtle text-danger">{{ $slaStats['breached'] ?? 0 }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                                    <span class="text-muted">Escalados</span>
                                    <span class="badge bg-secondary-subtle text-secondary">{{ $slaStats['escalated'] ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-md-4">
            <div class="card border h-100">
                <div class="card-header bg-light py-3">
                    <h6 class="fw-semibold mb-0">
                        <i class="ti ti-bolt me-2"></i>
                        Acciones Rapidas
                    </h6>
                </div>
                <div class="card-body d-flex flex-column gap-2">
                    <a href="{{ route('settings.documents.sla-policies.index') }}" class="btn btn-outline-primary w-100">
                        <i class="ti ti-list me-2"></i> Ver Todas las Politicas
                    </a>
                    <a href="{{ route('settings.documents.sla-policies.create') }}" class="btn btn-primary w-100">
                        <i class="ti ti-plus me-2"></i> Crear Nueva Politica
                    </a>
                    <hr class="my-2">
                    <button type="button" class="btn btn-outline-secondary w-100" id="btnRecalculateSla">
                        <i class="ti ti-refresh me-2"></i> Recalcular SLA
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- SLA Monitoring Section -->
    <div class="setting-section mb-4">
        <h6 class="fw-semibold mb-3 text-uppercase text-muted small">
            <i class="ti ti-eye me-1"></i> Monitoreo SLA
        </h6>

        <div class="row g-3">
            <!-- Enable SLA Monitoring -->
            <div class="col-12">
                <div class="p-3 bg-light rounded">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <label class="form-label fw-semibold mb-1">
                                <i class="ti ti-activity me-1 text-muted"></i>
                                Habilitar Monitoreo SLA
                            </label>
                            <p class="text-muted small mb-0">Activa el seguimiento de tiempos de respuesta y cumplimiento de SLA para documentos.</p>
                        </div>
                        <div class="form-check form-switch ms-3">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="sla_monitoring_enabled"
                                   id="slaMonitoringEnabled"
                                   value="1"
                                   {{ ($settings['sla']['monitoring_enabled'] ?? true) ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Default Time Limits Section -->
    <div class="setting-section mb-4 sla-options {{ ($settings['sla']['monitoring_enabled'] ?? true) ? '' : 'd-none' }}">
        <h6 class="fw-semibold mb-3 text-uppercase text-muted small">
            <i class="ti ti-clock me-1"></i> Tiempos Limite Por defecto
        </h6>

        <div class="alert alert-info mb-3">
            <i class="ti ti-info-circle me-2"></i>
            Estos valores se usan cuando no hay una politica SLA especifica asignada. Se pueden sobrescribir en cada politica individual.
        </div>

        <div class="row g-3">
            <!-- Upload Time Limit -->
            <div class="col-md-4">
                <div class="p-3 bg-light rounded h-100">
                    <label class="form-label fw-semibold mb-1">
                        <i class="ti ti-upload me-1 text-primary"></i>
                        Tiempo para Carga
                    </label>
                    <p class="text-muted small mb-2">Tiempo maximo para que el cliente cargue los documentos.</p>
                    <div class="input-group">
                        <input type="number"
                               class="form-control"
                               name="sla_default_upload_time"
                               value="{{ $settings['sla']['default_upload_time'] ?? 4320 }}"
                               min="60"
                               max="43200">
                        <span class="input-group-text">min</span>
                    </div>
                    <small class="text-muted">= {{ floor(($settings['sla']['default_upload_time'] ?? 4320) / 60) }} horas ({{ floor(($settings['sla']['default_upload_time'] ?? 4320) / 1440) }} dias)</small>
                </div>
            </div>

            <!-- Review Time Limit -->
            <div class="col-md-4">
                <div class="p-3 bg-light rounded h-100">
                    <label class="form-label fw-semibold mb-1">
                        <i class="ti ti-eye-check me-1 text-info"></i>
                        Tiempo para Revision
                    </label>
                    <p class="text-muted small mb-2">Tiempo maximo para revisar un documento cargado.</p>
                    <div class="input-group">
                        <input type="number"
                               class="form-control"
                               name="sla_default_review_time"
                               value="{{ $settings['sla']['default_review_time'] ?? 1440 }}"
                               min="30"
                               max="10080">
                        <span class="input-group-text">min</span>
                    </div>
                    <small class="text-muted">= {{ floor(($settings['sla']['default_review_time'] ?? 1440) / 60) }} horas</small>
                </div>
            </div>

            <!-- Approval Time Limit -->
            <div class="col-md-4">
                <div class="p-3 bg-light rounded h-100">
                    <label class="form-label fw-semibold mb-1">
                        <i class="ti ti-check me-1 text-success"></i>
                        Tiempo para Aprobacion
                    </label>
                    <p class="text-muted small mb-2">Tiempo maximo para aprobar/rechazar un documento.</p>
                    <div class="input-group">
                        <input type="number"
                               class="form-control"
                               name="sla_default_approval_time"
                               value="{{ $settings['sla']['default_approval_time'] ?? 2880 }}"
                               min="60"
                               max="14400">
                        <span class="input-group-text">min</span>
                    </div>
                    <small class="text-muted">= {{ floor(($settings['sla']['default_approval_time'] ?? 2880) / 60) }} horas</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Business Hours Section -->
    <div class="setting-section mb-4 sla-options {{ ($settings['sla']['monitoring_enabled'] ?? true) ? '' : 'd-none' }}">
        <h6 class="fw-semibold mb-3 text-uppercase text-muted small">
            <i class="ti ti-calendar me-1"></i> Horario Comercial
        </h6>

        <div class="row g-3">
            <!-- Use Business Hours -->
            <div class="col-12">
                <div class="p-3 bg-light rounded">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="flex-grow-1">
                            <label class="form-label fw-semibold mb-1">
                                <i class="ti ti-clock-hour-9 me-1 text-muted"></i>
                                Calcular SLA Solo en Horario Comercial
                            </label>
                            <p class="text-muted small mb-0">El tiempo SLA se pausa fuera del horario comercial configurado.</p>
                        </div>
                        <div class="form-check form-switch ms-3">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="sla_business_hours_only"
                                   id="slaBusinessHoursOnly"
                                   value="1"
                                   {{ ($settings['sla']['business_hours_only'] ?? false) ? 'checked' : '' }}>
                        </div>
                    </div>

                    <div class="business-hours-config {{ ($settings['sla']['business_hours_only'] ?? false) ? '' : 'd-none' }}">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small">Hora de Inicio</label>
                                <input type="time"
                                       class="form-control"
                                       name="sla_business_start"
                                       value="{{ $settings['sla']['business_start'] ?? '09:00' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Hora de Fin</label>
                                <input type="time"
                                       class="form-control"
                                       name="sla_business_end"
                                       value="{{ $settings['sla']['business_end'] ?? '18:00' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Zona Horaria</label>
                                <select class="form-select select2" name="sla_timezone">
                                    <option value="Europe/Madrid" {{ ($settings['sla']['timezone'] ?? 'Europe/Madrid') == 'Europe/Madrid' ? 'selected' : '' }}>
                                        Europe/Madrid
                                    </option>
                                    <option value="America/Mexico_City" {{ ($settings['sla']['timezone'] ?? 'Europe/Madrid') == 'America/Mexico_City' ? 'selected' : '' }}>
                                        America/Mexico_City
                                    </option>
                                    <option value="America/Bogota" {{ ($settings['sla']['timezone'] ?? 'Europe/Madrid') == 'America/Bogota' ? 'selected' : '' }}>
                                        America/Bogota
                                    </option>
                                    <option value="America/Buenos_Aires" {{ ($settings['sla']['timezone'] ?? 'Europe/Madrid') == 'America/Buenos_Aires' ? 'selected' : '' }}>
                                        America/Buenos_Aires
                                    </option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Dias Laborables</label>
                                <div class="d-flex flex-wrap gap-2">
                                    @php
                                        $workDays = $settings['sla']['work_days'] ?? [1,2,3,4,5];
                                        $dayNames = ['Domingo', 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'];
                                    @endphp
                                    @foreach($dayNames as $index => $dayName)
                                        <div class="form-check">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   name="sla_work_days[]"
                                                   value="{{ $index }}"
                                                   id="workDay{{ $index }}"
                                                   {{ in_array($index, $workDays) ? 'checked' : '' }}>
                                            <label class="form-check-label small" for="workDay{{ $index }}">
                                                {{ $dayName }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Exclude Holidays -->
            <div class="col-12">
                <div class="p-3 bg-light rounded">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="flex-grow-1">
                            <label class="form-label fw-semibold mb-1">
                                <i class="ti ti-calendar-off me-1 text-muted"></i>
                                Excluir Dias Festivos
                            </label>
                            <p class="text-muted small mb-0">Pausar SLA durante dias festivos configurados.</p>
                        </div>
                        <div class="form-check form-switch ms-3">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="sla_exclude_holidays"
                                   id="slaExcludeHolidays"
                                   value="1"
                                   {{ ($settings['sla']['exclude_holidays'] ?? false) ? 'checked' : '' }}>
                        </div>
                    </div>
                    <div class="holidays-config {{ ($settings['sla']['exclude_holidays'] ?? false) ? '' : 'd-none' }}">
                        <label class="form-label small">Dias Festivos (formato: DD/MM, separados por coma)</label>
                        <input type="text"
                               class="form-control"
                               name="sla_holidays"
                               value="{{ $settings['sla']['holidays'] ?? '01/01, 06/01, 25/12, 26/12' }}"
                               placeholder="01/01, 06/01, 25/12">
                        <small class="text-muted">Ejemplo: 01/01, 06/01, 01/05, 25/12</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Escalation Section -->
    <div class="setting-section mb-4 sla-options {{ ($settings['sla']['monitoring_enabled'] ?? true) ? '' : 'd-none' }}">
        <h6 class="fw-semibold mb-3 text-uppercase text-muted small">
            <i class="ti ti-arrow-up me-1"></i> Configuracion de Escalamiento
        </h6>

        <div class="row g-3">
            <!-- Enable Escalation -->
            <div class="col-12">
                <div class="p-3 bg-light rounded">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="flex-grow-1">
                            <label class="form-label fw-semibold mb-1">
                                <i class="ti ti-alert-octagon me-1 text-danger"></i>
                                Habilitar Escalamiento Automatico
                            </label>
                            <p class="text-muted small mb-0">Escala automaticamente los documentos que excedan el tiempo SLA.</p>
                        </div>
                        <div class="form-check form-switch ms-3">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="sla_enable_escalation"
                                   id="slaEnableEscalation"
                                   value="1"
                                   {{ ($settings['sla']['enable_escalation'] ?? true) ? 'checked' : '' }}>
                        </div>
                    </div>

                    <div class="escalation-config {{ ($settings['sla']['enable_escalation'] ?? true) ? '' : 'd-none' }}">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small">Porcentaje de SLA para Alerta</label>
                                <div class="input-group">
                                    <input type="number"
                                           class="form-control"
                                           name="sla_warning_threshold"
                                           value="{{ $settings['sla']['warning_threshold'] ?? 75 }}"
                                           min="50"
                                           max="95">
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="text-muted">Alerta cuando se alcanza este % del tiempo SLA</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Escalar Despues de</label>
                                <div class="input-group">
                                    <input type="number"
                                           class="form-control"
                                           name="sla_escalation_after"
                                           value="{{ $settings['sla']['escalation_after'] ?? 100 }}"
                                           min="100"
                                           max="200">
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="text-muted">% del tiempo SLA para escalar</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Escalation Recipients -->
            <div class="col-12 escalation-config {{ ($settings['sla']['enable_escalation'] ?? true) ? '' : 'd-none' }}">
                <div class="p-3 bg-light rounded">
                    <label class="form-label fw-semibold mb-1">
                        Destinatarios de Escalamiento
                    </label>
                    <p class="text-muted small mb-2">Emails que recibiran notificaciones de escalamiento (separados por coma).</p>
                    <input type="text"
                           class="form-control"
                           name="sla_escalation_recipients"
                           value="{{ $settings['sla']['escalation_recipients'] ?? '' }}"
                           placeholder="supervisor@empresa.com, gerente@empresa.com">
                    <small class="text-muted">Deja vacio para usar los destinatarios de admin Por defecto.</small>
                </div>
            </div>

            <!-- Escalation Actions -->
            <div class="col-12 escalation-config {{ ($settings['sla']['enable_escalation'] ?? true) ? '' : 'd-none' }}">
                <div class="p-3 bg-light rounded">
                    <label class="form-label fw-semibold mb-2">
                        Acciones de Escalamiento
                    </label>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input"
                                       type="checkbox"
                                       name="sla_action_email"
                                       id="slaActionEmail"
                                       value="1"
                                       {{ ($settings['sla']['actions']['email'] ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="slaActionEmail">
                                    <i class="ti ti-mail me-1"></i> Enviar Email
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input"
                                       type="checkbox"
                                       name="sla_action_notification"
                                       id="slaActionNotification"
                                       value="1"
                                       {{ ($settings['sla']['actions']['notification'] ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="slaActionNotification">
                                    <i class="ti ti-bell me-1"></i> Notificacion Sistema
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input"
                                       type="checkbox"
                                       name="sla_action_priority"
                                       id="slaActionPriority"
                                       value="1"
                                       {{ ($settings['sla']['actions']['priority'] ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label" for="slaActionPriority">
                                    <i class="ti ti-arrow-up me-1"></i> Aumentar Prioridad
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Warning Indicators Section -->
    <div class="setting-section sla-options {{ ($settings['sla']['monitoring_enabled'] ?? true) ? '' : 'd-none' }}">
        <h6 class="fw-semibold mb-3 text-uppercase text-muted small">
            <i class="ti ti-alert-circle me-1"></i> Indicadores de Alerta
        </h6>

        <div class="row g-3">
            <!-- Breached SLA Warning -->
            @if(($slaStats['breached'] ?? 0) > 0)
            <div class="col-12">
                <div class="alert alert-danger mb-0">
                    <div class="d-flex align-items-center">
                        <i class="ti ti-alert-triangle fs-4 me-3"></i>
                        <div class="flex-grow-1">
                            <h6 class="mb-1 fw-semibold">Documentos Fuera de SLA</h6>
                            <p class="mb-0">Hay <strong>{{ $slaStats['breached'] }}</strong> documentos que han excedido el tiempo SLA establecido.</p>
                        </div>
                        <a href="{{ route('settings.documents.configurations') }}?filter=breached" class="btn btn-danger btn-sm">
                            <i class="ti ti-eye me-1"></i> Ver Documentos
                        </a>
                    </div>
                </div>
            </div>
            @endif

            <!-- Expiring Soon Warning -->
            @if(($slaStats['expiring_soon'] ?? 0) > 0)
            <div class="col-12">
                <div class="alert alert-warning mb-0">
                    <div class="d-flex align-items-center">
                        <i class="ti ti-clock fs-4 me-3"></i>
                        <div class="flex-grow-1">
                            <h6 class="mb-1 fw-semibold">Documentos por Vencer</h6>
                            <p class="mb-0">Hay <strong>{{ $slaStats['expiring_soon'] }}</strong> documentos que venceran en las proximas 24 horas.</p>
                        </div>
                        <a href="{{ route('settings.documents.configurations') }}?filter=expiring" class="btn btn-warning btn-sm">
                            <i class="ti ti-eye me-1"></i> Ver Documentos
                        </a>
                    </div>
                </div>
            </div>
            @endif

            <!-- All Clear -->
            @if(($slaStats['breached'] ?? 0) == 0 && ($slaStats['expiring_soon'] ?? 0) == 0)
            <div class="col-12">
                <div class="alert alert-success mb-0">
                    <div class="d-flex align-items-center">
                        <i class="ti ti-check-circle fs-4 me-3"></i>
                        <div>
                            <h6 class="mb-1 fw-semibold">Todo en Orden</h6>
                            <p class="mb-0">No hay documentos fuera de SLA ni por vencer proximamente.</p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- SLA Usage Progress Bars -->
            <div class="col-12">
                <div class="card border">
                    <div class="card-header bg-light py-2">
                        <h6 class="fw-semibold mb-0 small">Distribucion de Cumplimiento SLA</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="small text-muted">Dentro de SLA (0-75%)</span>
                                <span class="small fw-semibold text-success">{{ $slaStats['distribution']['green'] ?? 0 }}%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success" style="width: {{ $slaStats['distribution']['green'] ?? 0 }}%"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="small text-muted">En riesgo (75-100%)</span>
                                <span class="small fw-semibold text-warning">{{ $slaStats['distribution']['yellow'] ?? 0 }}%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-warning" style="width: {{ $slaStats['distribution']['yellow'] ?? 0 }}%"></div>
                            </div>
                        </div>
                        <div class="mb-0">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="small text-muted">Fuera de SLA (>100%)</span>
                                <span class="small fw-semibold text-danger">{{ $slaStats['distribution']['red'] ?? 0 }}%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-danger" style="width: {{ $slaStats['distribution']['red'] ?? 0 }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Policies Reference -->
    <div class="setting-section sla-options {{ ($settings['sla']['monitoring_enabled'] ?? true) ? '' : 'd-none' }}">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-semibold mb-0 text-uppercase text-muted small">
                <i class="ti ti-list me-1"></i> Politicas SLA Activas
            </h6>
            <a href="{{ route('settings.documents.sla-policies.index') }}" class="btn btn-sm btn-outline-primary">
                Ver Todas
            </a>
        </div>

        @if(isset($slaPolicies) && count($slaPolicies) > 0)
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nombre</th>
                        <th class="text-center">Revision</th>
                        <th class="text-center">Aprobacion</th>
                        <th class="text-center">Horario</th>
                        <th class="text-center">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($slaPolicies->take(5) as $policy)
                    <tr>
                        <td>
                            <strong>{{ $policy->name }}</strong>
                            @if($policy->is_default)
                                <span class="badge bg-primary-subtle text-primary ms-1">Por defecto</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-info-subtle text-info">{{ $policy->review_time ?? '-' }} min</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-warning-subtle text-warning">{{ $policy->approval_time }} min</span>
                        </td>
                        <td class="text-center">
                            @if($policy->business_hours_only)
                                <i class="ti ti-clock text-primary" title="Solo horario comercial"></i>
                            @else
                                <i class="ti ti-24-hours text-muted" title="24/7"></i>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($policy->active)
                                <span class="badge bg-success-subtle text-success">Activa</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Inactiva</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-4 bg-light rounded">
            <i class="ti ti-clock-off fs-1 text-muted mb-2"></i>
            <p class="text-muted mb-2">No hay politicas SLA configuradas</p>
            <a href="{{ route('settings.documents.sla-policies.create') }}" class="btn btn-sm btn-primary">
                <i class="ti ti-plus me-1"></i> Crear Primera Politica
            </a>
        </div>
        @endif
    </div>

</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Toggle SLA monitoring options
    $('#slaMonitoringEnabled').on('change', function() {
        $('.sla-options').toggleClass('d-none', !this.checked);
    });

    // Toggle business hours config
    $('#slaBusinessHoursOnly').on('change', function() {
        $('.business-hours-config').toggleClass('d-none', !this.checked);
    });

    // Toggle holidays config
    $('#slaExcludeHolidays').on('change', function() {
        $('.holidays-config').toggleClass('d-none', !this.checked);
    });

    // Toggle escalation config
    $('#slaEnableEscalation').on('change', function() {
        $('.escalation-config').toggleClass('d-none', !this.checked);
    });

    // Recalculate SLA button
    $('#btnRecalculateSla').on('click', function() {
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="ti ti-loader ti-spin me-2"></i> Recalculando...');

        $.ajax({
            url: '{{ route("settings.documents.settings.update") }}',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success('SLA recalculado correctamente', 'Exito');
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    toastr.error(response.message || 'Error al recalcular SLA', 'Error');
                    btn.prop('disabled', false).html('<i class="ti ti-refresh me-2"></i> Recalcular SLA');
                }
            },
            error: function(xhr) {
                toastr.error('Error al recalcular SLA', 'Error');
                btn.prop('disabled', false).html('<i class="ti ti-refresh me-2"></i> Recalcular SLA');
            }
        });
    });

    // Time conversion helper
    function updateTimeDisplay() {
        $('input[name^="sla_default_"]').each(function() {
            const minutes = parseInt($(this).val()) || 0;
            const hours = Math.floor(minutes / 60);
            const days = Math.floor(minutes / 1440);

            let displayText = hours + ' horas';
            if (days > 0) {
                displayText += ' (' + days + ' dias)';
            }

            $(this).closest('.p-3').find('small.text-muted').last().text('= ' + displayText);
        });
    }

    $('input[name^="sla_default_"]').on('change', updateTimeDisplay);
});
</script>
@endpush

@push('styles')
<style>
    .progress-circle svg {
        display: block;
    }
    .round-40 {
        width: 40px;
        height: 40px;
    }
</style>
@endpush

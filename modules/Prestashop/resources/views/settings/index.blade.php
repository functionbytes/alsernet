@extends('layouts.theme')

@section('content')

  @include('core::components.card', ['title' => 'Configuración PrestaShop'])

  <div class="widget-content searchable-container list">

    <!-- Main Card -->
    <div class="card">
      <!-- Header Section -->
      <div class="card-header p-4 border-bottom border-light">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-1 fw-bold">Configuración PrestaShop</h5>
            <p class="small mb-0 text-muted">Gestiona la sincronización y conexión con PrestaShop</p>
          </div>
          <div class="d-flex gap-2">
            <a href="{{ route('settings.prestashop.edit') }}" class="btn btn-primary">
              <i class="fas fa-cog me-1"></i> Configurar
            </a>
          </div>
        </div>
      </div>

      <!-- Success/Error Messages -->
      @if(session('success'))
        <div class="card-body border-bottom">
          <div class="alert alert-success alert-dismissible fade show mb-0" role="alert">
            <div class="d-flex align-items-center">
              <i class="fas fa-check-circle fs-4 me-2"></i>
              <div>{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        </div>
      @endif

      <!-- Stats Section -->
      <div class="card-body border-bottom">
        <div class="row">
          <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
            <div class="card bg-light-secondary">
              <div class="card-body">
                <div class="d-flex align-items-center">
                  <div class="me-3">
                    <i class="fas fa-power-off fs-4 text-primary"></i>
                  </div>
                  <div>
                    <p class="mb-1 text-muted small">Estado</p>
                    <h5 class="mb-0" id="serviceStatus">
                      @if($settings['prestashop_enabled'] === 'yes')
                        <span class="badge bg-success">Habilitado</span>
                      @else
                        <span class="badge bg-danger">Deshabilitado</span>
                      @endif
                    </h5>
                  </div>
                </div>
                <div class="mt-3">
                  <button type="button" class="btn btn-sm btn-outline-dark w-100" id="toggleServiceBtn">
                    {{ $settings['prestashop_enabled'] === 'yes' ? 'Deshabilitar' : 'Habilitar' }}
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
            <div class="card bg-light-secondary">
              <div class="card-body">
                <div class="d-flex align-items-center">
                  <div class="me-3">
                    <i class="fas fa-database fs-4 text-primary"></i>
                  </div>
                  <div>
                    <p class="mb-1 text-muted small">Conexión DB</p>
                    <h5 class="mb-0" id="connectionStatus">
                      @if($stats['last_sync_status'] === 'online')
                        <span class="badge bg-success">Online</span>
                      @elseif($stats['last_sync_status'] === 'offline')
                        <span class="badge bg-danger">Offline</span>
                      @else
                        <span class="badge bg-warning">Sin verificar</span>
                      @endif
                    </h5>
                  </div>
                </div>
                <div class="mt-3">
                  <button type="button" class="btn btn-sm btn-outline-dark w-100" id="checkConnectionBtn">
                    <span class="btn-text">Verificar</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
            <div class="card bg-light-secondary">
              <div class="card-body">
                <div class="d-flex align-items-center">
                  <div class="me-3">
                    <i class="fas fa-sync fs-4 text-primary"></i>
                  </div>
                  <div>
                    <p class="mb-1 text-muted small">Sincronizaciones</p>
                    <h5 class="mb-0" id="totalSyncs">
                      {{ number_format((int)$stats['total_syncs']) }}
                    </h5>
                    <p class="text-xs mb-0">
                      <span class="text-muted" id="failedSyncs">{{ (int)$stats['failed_syncs'] }}</span> errores
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-3 col-md-6">
            <div class="card bg-light-secondary">
              <div class="card-body">
                <div class="d-flex align-items-center">
                  <div class="me-3">
                    <i class="fas fa-check-circle fs-4 text-primary"></i>
                  </div>
                  <div>
                    <p class="mb-1 text-muted small">Tasa de éxito</p>
                    <h5 class="mb-0" id="successRate">
                      {{ number_format((float)$stats['success_rate'], 2) }}%
                    </h5>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Actions Section -->
      <div class="card-body border-bottom bg-light">
        <h6 class="fw-bold mb-3">Acciones del sistema</h6>
        <div class="row g-2">
          <div class="col-md-4">
            <button type="button" class="btn btn-outline-primary w-100" id="refreshBtn">
              <i class="fas fa-sync me-1"></i> Actualizar estadísticas
            </button>
          </div>
          <div class="col-md-4">
            <button type="button" class="btn btn-outline-primary w-100" id="testSyncBtn">
              <i class="fas fa-play-circle me-1"></i> Sincronizar ahora
            </button>
          </div>
          <div class="col-md-4">
            <button type="button" class="btn btn-outline-danger w-100" id="resetStatsBtn">
              <i class="fas fa-rotate-left me-1"></i> Restablecer estadísticas
            </button>
          </div>
        </div>
      </div>

      <!-- Configuration Section -->
      <div class="card-body">
        <h6 class="fw-bold mb-3">Información de configuración</h6>
        <div class="row">
          <div class="col-md-6 mb-3 mb-md-0">
            <div class="card bg-light h-100">
              <div class="card-body">
                <h6 class="mb-3">Configuración base de datos</h6>
                <div class="mb-3">
                  <label class="form-label fw-semibold text-primary small mb-1">Host:</label>
                  <p class="text-muted mb-0">{{ $settings['prestashop_db_host'] }}</p>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold text-primary small mb-1">Puerto:</label>
                  <p class="text-muted mb-0">{{ $settings['prestashop_db_port'] }}</p>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold text-primary small mb-1">Base de datos:</label>
                  <p class="text-muted mb-0">{{ $settings['prestashop_db_database'] }}</p>
                </div>
                <div class="mb-0">
                  <label class="form-label fw-semibold text-primary small mb-1">URL PrestaShop:</label>
                  <p class="text-muted mb-0">{{ $settings['prestashop_url'] }}</p>
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="card bg-light h-100">
              <div class="card-body">
                <h6 class="mb-3">Parámetros de sincronización</h6>
                <div class="mb-3">
                  <label class="form-label fw-semibold text-primary small mb-1">Timeout:</label>
                  <p class="text-muted mb-0">{{ $settings['prestashop_timeout'] }} segundos</p>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold text-primary small mb-1">Timeout conexión:</label>
                  <p class="text-muted mb-0">{{ $settings['prestashop_connect_timeout'] }} segundos</p>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold text-primary small mb-1">Sincronización habilitada:</label>
                  <p class="text-muted mb-0">
                    {{ $settings['prestashop_sync_enabled'] === 'yes' ? 'Sí' : 'No' }}
                  </p>
                </div>
                <div class="mb-0">
                  <label class="form-label fw-semibold text-primary small mb-1">Última sincronización:</label>
                  <p class="text-muted mb-0">
                    @if($stats['last_sync_check'])
                      {{ \Carbon\Carbon::parse($stats['last_sync_check'])->diffForHumans() }}
                    @else
                      Nunca
                    @endif
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>

  </div>

@push('scripts')
<script>
$(document).ready(function() {
    // Verificar conexión
    $('#checkConnectionBtn').on('click', function() {
        const btn = $(this);
        const btnText = btn.find('.btn-text');
        const spinner = btn.find('.spinner-border');

        btn.prop('disabled', true);
        btnText.addClass('d-none');
        spinner.removeClass('d-none');

        $.ajax({
            url: '{{ route("settings.prestashop.check-connection") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    $('#connectionStatus').html('<span class="badge bg-success">Online</span>');
                    toastr.success(response.message, 'PrestaShop');
                } else {
                    $('#connectionStatus').html('<span class="badge bg-danger">Offline</span>');
                    toastr.error(response.message, 'PrestaShop');
                }
            },
            error: function(xhr) {
                $('#connectionStatus').html('<span class="badge bg-danger">Error</span>');
                toastr.error('Error al verificar conexión', 'PrestaShop');
            },
            complete: function() {
                btn.prop('disabled', false);
                btnText.removeClass('d-none');
                spinner.addClass('d-none');
            }
        });
    });

    // Toggle servicio
    $('#toggleServiceBtn').on('click', function() {
        $.ajax({
            url: '{{ route("settings.prestashop.toggle-active") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    if (response.enabled) {
                        $('#serviceStatus').html('<span class="badge bg-success">Habilitado</span>');
                        $('#toggleServiceBtn').text('Deshabilitar');
                    } else {
                        $('#serviceStatus').html('<span class="badge bg-danger">Deshabilitado</span>');
                        $('#toggleServiceBtn').text('Habilitar');
                    }
                    toastr.success(response.message, 'PrestaShop');
                }
            },
            error: function() {
                toastr.error('Error al cambiar estado', 'PrestaShop');
            }
        });
    });

    // Resetear estadísticas
    $('#resetStatsBtn').on('click', function() {
        if (!confirm('¿Estás seguro de que deseas resetear las estadísticas?')) {
            return;
        }

        $.ajax({
            url: '{{ route("settings.prestashop.reset-stats") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    $('#totalSyncs').text('0');
                    $('#failedSyncs').text('0');
                    $('#successRate').text('100.00%');
                    toastr.success(response.message, 'PrestaShop');
                }
            },
            error: function() {
                toastr.error('Error al resetear estadísticas', 'PrestaShop');
            }
        });
    });

    // Test sincronización
    $('#testSyncBtn').on('click', function() {
        const btn = $(this);
        btn.prop('disabled', true);

        $.ajax({
            url: '{{ route("settings.prestashop.test-sync") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message + ' (' + response.pending_orders + ' órdenes pendientes)', 'PrestaShop');
                } else {
                    toastr.error(response.message, 'PrestaShop');
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Error en test de sincronización';
                toastr.error(message, 'PrestaShop');
            },
            complete: function() {
                btn.prop('disabled', false);
            }
        });
    });

    // Refrescar estadísticas
    $('#refreshBtn').on('click', function() {
        $.ajax({
            url: '{{ route("settings.prestashop.stats") }}',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    $('#totalSyncs').text(data.total_syncs.toLocaleString());
                    $('#failedSyncs').text(data.failed_syncs);
                    $('#successRate').text(data.success_rate.toFixed(2) + '%');

                    if (data.enabled) {
                        $('#serviceStatus').html('<span class="badge bg-success">Habilitado</span>');
                    } else {
                        $('#serviceStatus').html('<span class="badge bg-danger">Deshabilitado</span>');
                    }

                    toastr.info('Estadísticas actualizadas', 'PrestaShop');
                }
            }
        });
    });



});
</script>
@endpush
@endsection

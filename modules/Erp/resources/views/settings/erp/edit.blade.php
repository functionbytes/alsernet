@extends('layouts.theme')

@section('page_header')
    @include('core::components.card', ['title' => 'Editar Configuración ERP'])
@endsection

@section('content')
  <div class="widget-content searchable-container list">
      <form action="{{ route('settings.erp.update') }}" method="POST">

        <div class="card">
              <div class="card-body">

              @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                  <strong>¡Error!</strong> Por favor corrige los siguientes errores:
                  <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                      <li>{{ $error }}</li>
                    @endforeach
                  </ul>
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
              @endif

                @csrf
                @method('PUT')

                <!-- URLs de Configuración -->
                <div class="mb-4">
                  <p class="text-muted mb-3">Configura las URLs de los diferentes servicios del ERP</p>

                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="form-label">URL API REST <span class="text-danger">*</span></label>
                      <input type="url" name="erp_api_url" class="form-control @error('erp_api_url') is-invalid @enderror"
                             value="{{ old('erp_api_url', $settings['erp_api_url']) }}" required>
                      <small class="form-text text-muted">Base URL de la API REST de Gestión</small>
                      @error('erp_api_url')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                      <label class="form-label">URL sincronización <span class="text-danger">*</span></label>
                      <input type="url" name="erp_sync_url" class="form-control @error('erp_sync_url') is-invalid @enderror"
                             value="{{ old('erp_sync_url', $settings['erp_sync_url']) }}" required>
                      <small class="form-text text-muted">URL del servicio de sincronización de tablas</small>
                      @error('erp_sync_url')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                      <label class="form-label">URL XML-RPC <span class="text-danger">*</span></label>
                      <input type="url" name="erp_xmlrpc_url" class="form-control @error('erp_xmlrpc_url') is-invalid @enderror"
                             value="{{ old('erp_xmlrpc_url', $settings['erp_xmlrpc_url']) }}" required>
                      <small class="form-text text-muted">URL del servicio WebAlvarez (XML-RPC)</small>
                      @error('erp_xmlrpc_url')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                      <label class="form-label">URL SMS <span class="text-danger">*</span></label>
                      <input type="url" name="erp_sms_url" class="form-control @error('erp_sms_url') is-invalid @enderror"
                             value="{{ old('erp_sms_url', $settings['erp_sms_url']) }}" required>
                      <small class="form-text text-muted">URL del servicio SMSServer</small>
                      @error('erp_sms_url')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>
                  </div>
                </div>

                <hr class="my-4">

                <!-- Configuración Oracle Database -->
                <div class="mb-4">
                  <h6 class="mb-0">Configuración Oracle Database</h6>
                  <p class="text-muted mb-3">Parámetros de conexión a la base de datos Oracle</p>

                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Host <span class="text-danger">*</span></label>
                      <input type="text" name="oracle_host" class="form-control @error('oracle_host') is-invalid @enderror"
                             value="{{ old('oracle_host', config('database.connections.oracle.host') ?? env('ORACLE_HOST')) }}" required>
                      <small class="form-text text-muted">IP o hostname del servidor Oracle</small>
                      @error('oracle_host')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                      <label class="form-label">Puerto <span class="text-danger">*</span></label>
                      <input type="number" name="oracle_port" class="form-control @error('oracle_port') is-invalid @enderror"
                             value="{{ old('oracle_port', config('database.connections.oracle.port') ?? env('ORACLE_PORT', 1521)) }}" min="1" max="65535" required>
                      <small class="form-text text-muted">Puerto de escucha (default: 1521)</small>
                      @error('oracle_port')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                      <label class="form-label">Base de datos <span class="text-danger">*</span></label>
                      <input type="text" name="oracle_database" class="form-control @error('oracle_database') is-invalid @enderror"
                             value="{{ old('oracle_database', config('database.connections.oracle.database') ?? env('ORACLE_DATABASE', 'GESTCENT')) }}" required>
                      <small class="form-text text-muted">Nombre de la base de datos</small>
                      @error('oracle_database')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                      <label class="form-label">Service Name <span class="text-danger">*</span></label>
                      <input type="text" name="oracle_service_name" class="form-control @error('oracle_service_name') is-invalid @enderror"
                             value="{{ old('oracle_service_name', config('database.connections.oracle.service_name') ?? env('ORACLE_SERVICE_NAME', 'GESTCENT')) }}" required>
                      <small class="form-text text-muted">Nombre del servicio Oracle</small>
                      @error('oracle_service_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                      <label class="form-label">Usuario <span class="text-danger">*</span></label>
                      <input type="text" name="oracle_username" class="form-control @error('oracle_username') is-invalid @enderror"
                             value="{{ old('oracle_username', config('database.connections.oracle.username') ?? env('ORACLE_USERNAME')) }}" required>
                      <small class="form-text text-muted">Usuario para conectar a Oracle</small>
                      @error('oracle_username')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                      <label class="form-label">Contraseña <span class="text-danger">*</span></label>
                      <input type="password" name="oracle_password" class="form-control @error('oracle_password') is-invalid @enderror"
                             value="{{ old('oracle_password', config('database.connections.oracle.password') ?? env('ORACLE_PASSWORD')) }}" required autocomplete="new-password">
                      <small class="form-text text-muted">Contraseña del usuario Oracle</small>
                      @error('oracle_password')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                      <label class="form-label">Schema <span class="text-danger">*</span></label>
                      <input type="text" name="oracle_schema" class="form-control @error('oracle_schema') is-invalid @enderror"
                             value="{{ old('oracle_schema', config('database.connections.oracle.prefix_schema') ?? env('ORACLE_SCHEMA', 'DEVELOPER')) }}" required>
                      <small class="form-text text-muted">Schema por defecto (prefix_schema)</small>
                      @error('oracle_schema')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                      <label class="form-label">Charset</label>
                      <input type="text" name="oracle_charset" class="form-control @error('oracle_charset') is-invalid @enderror"
                             value="{{ old('oracle_charset', config('database.connections.oracle.charset') ?? env('ORACLE_CHARSET', 'AL32UTF8')) }}">
                      <small class="form-text text-muted">Charset (default: AL32UTF8)</small>
                      @error('oracle_charset')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>
                  </div>
                </div>

                <hr class="my-4">

                <!-- Parámetros de Conexión -->
                <div class="mb-4">
                  <h6 class="mb-0">Parámetros de conexión</h6>
                  <p class="text-muted mb-3">Configura timeouts y reintentos</p>

                  <div class="row">
                    <div class="col-md-4 mb-3">
                      <label class="form-label">Timeout (segundos) <span class="text-danger">*</span></label>
                      <input type="number" name="erp_timeout" class="form-control @error('erp_timeout') is-invalid @enderror"
                             value="{{ old('erp_timeout', $settings['erp_timeout']) }}" min="1" max="300" required>
                      <small class="form-text text-muted">Tiempo máximo de espera por petición</small>
                      @error('erp_timeout')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                      <label class="form-label">Timeout conexión (segundos) <span class="text-danger">*</span></label>
                      <input type="number" name="erp_connect_timeout" class="form-control @error('erp_connect_timeout') is-invalid @enderror"
                             value="{{ old('erp_connect_timeout', $settings['erp_connect_timeout']) }}" min="1" max="60" required>
                      <small class="form-text text-muted">Tiempo máximo para establecer conexión</small>
                      @error('erp_connect_timeout')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                      <label class="form-label">Reintentos <span class="text-danger">*</span></label>
                      <input type="number" name="erp_retry_attempts" class="form-control @error('erp_retry_attempts') is-invalid @enderror"
                             value="{{ old('erp_retry_attempts', $settings['erp_retry_attempts']) }}" min="0" max="10" required>
                      <small class="form-text text-muted">Número de reintentos en caso de error</small>
                      @error('erp_retry_attempts')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>
                  </div>
                </div>

                <hr class="my-4">

                <!-- Configuración de Sincronización -->
                <div class="mb-4">
                  <h6 class="mb-0">Configuración de sincronización</h6>

                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="form-label">ID destino <span class="text-danger">*</span></label>
                      <input type="number" name="erp_sync_destination_id" class="form-control @error('erp_sync_destination_id') is-invalid @enderror"
                             value="{{ old('erp_sync_destination_id', $settings['erp_sync_destination_id']) }}" min="1" required>
                      <small class="form-text text-muted">ID de destino para sincronización de tablas</small>
                      @error('erp_sync_destination_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                      <label class="form-label">Tamaño de lote <span class="text-danger">*</span></label>
                      <input type="number" name="erp_sync_batch_size" class="form-control @error('erp_sync_batch_size') is-invalid @enderror"
                             value="{{ old('erp_sync_batch_size', $settings['erp_sync_batch_size']) }}" min="1" max="1000" required>
                      <small class="form-text text-muted">Número de registros por lote de sincronización</small>
                      @error('erp_sync_batch_size')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>
                  </div>
                </div>

                <hr class="my-4">

                <!-- Configuración de TPV -->
                <div class="mb-4">
                  <h6 class="mb-0">Configuración de TPV</h6>
                  <p class="text-muted mb-3">IDs de terminales virtuales para métodos de pago</p>

                  <div class="row">
                    <div class="col-md-4 mb-3">
                      <label class="form-label">ID TPV Bizum</label>
                      <input type="number" name="erp_bizum_tpv_id" class="form-control @error('erp_bizum_tpv_id') is-invalid @enderror"
                             value="{{ old('erp_bizum_tpv_id', $settings['erp_bizum_tpv_id']) }}">
                      @error('erp_bizum_tpv_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                      <label class="form-label">ID TPV Google Pay</label>
                      <input type="number" name="erp_google_tpv_id" class="form-control @error('erp_google_tpv_id') is-invalid @enderror"
                             value="{{ old('erp_google_tpv_id', $settings['erp_google_tpv_id']) }}">
                      @error('erp_google_tpv_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                      <label class="form-label">ID TPV Apple Pay</label>
                      <input type="number" name="erp_apple_tpv_id" class="form-control @error('erp_apple_tpv_id') is-invalid @enderror"
                             value="{{ old('erp_apple_tpv_id', $settings['erp_apple_tpv_id']) }}">
                      @error('erp_apple_tpv_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>
                  </div>
                </div>

                <hr class="my-4">

                <!-- Configuración de Cache y Logs -->
                <div class="mb-4">
                  <h6 class="mb-3">Cache y Logs</h6>

                  <div class="row ">
                    <div class="col-md-4 mb-3">
                      <div class="form-check form-switch">
                        <input type="hidden" name="erp_enable_cache" value="no">
                        <input class="form-check-input" type="checkbox" name="erp_enable_cache" id="enableCache"
                               value="yes" {{ old('erp_enable_cache', $settings['erp_enable_cache']) === 'yes' ? 'checked' : '' }}>
                        <label class="form-check-label" for="enableCache">
                          Habilitar Cache
                        </label>
                      </div>
                      <small class="form-text text-muted">Cachear respuestas del ERP</small>
                    </div>

                    <div class="col-md-4 mb-3">
                      <label class="form-label">TTL Cache (segundos) <span class="text-danger">*</span></label>
                      <input type="number" name="erp_cache_ttl" class="form-control @error('erp_cache_ttl') is-invalid @enderror"
                             value="{{ old('erp_cache_ttl', $settings['erp_cache_ttl']) }}" min="60" max="86400" required>
                      <small class="form-text text-muted">Tiempo de vida del cache</small>
                      @error('erp_cache_ttl')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                      <div class="form-check form-switch">
                        <input type="hidden" name="erp_enable_debug_logs" value="no">
                        <input class="form-check-input" type="checkbox" name="erp_enable_debug_logs" id="enableDebugLogs"
                               value="yes" {{ old('erp_enable_debug_logs', $settings['erp_enable_debug_logs']) === 'yes' ? 'checked' : '' }}>
                        <label class="form-check-label" for="enableDebugLogs">
                          Habilitar Logs de Debug
                        </label>
                      </div>
                      <small class="form-text text-muted">Guardar logs detallados de peticiones</small>
                    </div>
                  </div>
                </div>

                <hr class="my-4">

                <!-- Credenciales SMS -->
                <div class="mb-4">
                  <h6 class="mb-0 mb-3">Credenciales SMS</h6>

                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Usuario SMS</label>
                      <input type="text" name="erp_sms_username" class="form-control @error('erp_sms_username') is-invalid @enderror"
                             value="{{ old('erp_sms_username', $settings['erp_sms_username']) }}">
                      @error('erp_sms_username')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                      <label class="form-label">Contraseña SMS</label>
                      <input type="password" name="erp_sms_password" class="form-control @error('erp_sms_password') is-invalid @enderror"
                             value="{{ old('erp_sms_password', $settings['erp_sms_password']) }}" autocomplete="new-password">
                      @error('erp_sms_password')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>
                  </div>
                </div>

                <hr class="my-4">

                <!-- Estado del Servicio -->
                <div class="mb-4">
                  <h6 class="mb-3">Estado del servicio</h6>

                  <div class="form-check form-switch">
                    <input type="hidden" name="erp_is_active" value="no">
                    <input class="form-check-input" type="checkbox" name="erp_is_active" id="isActive"
                           value="yes" {{ old('erp_is_active', $settings['erp_is_active']) === 'yes' ? 'checked' : '' }}>
                    <label class="form-check-label" for="isActive">
                     Servicio erp activo
                    </label>
                  </div>
                  <small class="form-text text-muted">Si está desactivado, no se realizarán peticiones al ERP</small>
                </div>
            </div>

              <div class="card-footer">
                      <button type="submit" class="btn btn-info px-4 waves-effect waves-light mt-2 w-100">
                          Guardar
                      </button>
                      <a href="{{ route('settings.erp.index') }}" class="btn btn-secondary px-4 waves-effect waves-light mt-2 w-100">
                          Volver
                      </a>
              </div>
        </div>
      </form>
  </div>
@endsection


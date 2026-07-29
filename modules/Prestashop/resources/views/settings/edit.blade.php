@extends('layouts.theme')

@section('content')

  @include('core::components.card', ['title' => 'Editar Configuración PrestaShop'])

  <div class="widget-content searchable-container list">

      <form action="{{ route('settings.prestashop.update') }}" method="POST">

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

                    <!-- Configuración Base de Datos -->
                    <div class="mb-4">
                      <h6 class="mb-1">Configuración base de datos</h6>
                      <p class="text-muted mb-3">Datos de conexión a la base de datos de PrestaShop</p>

                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Host <span class="text-danger">*</span></label>
                          <input type="text" name="prestashop_db_host" class="form-control @error('prestashop_db_host') is-invalid @enderror"
                                 value="{{ old('prestashop_db_host', $settings['prestashop_db_host']) }}" required>
                          <small class="form-text text-muted">IP o dominio del servidor</small>
                          @error('prestashop_db_host')
                            <div class="invalid-feedback">{{ $message }}</div>
                          @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                          <label class="form-label">Puerto <span class="text-danger">*</span></label>
                          <input type="number" name="prestashop_db_port" class="form-control @error('prestashop_db_port') is-invalid @enderror"
                                 value="{{ old('prestashop_db_port', $settings['prestashop_db_port']) }}" min="1" max="65535" required>
                          <small class="form-text text-muted">Puerto MySQL (Por defecto 3306)</small>
                          @error('prestashop_db_port')
                            <div class="invalid-feedback">{{ $message }}</div>
                          @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                          <label class="form-label">Base de datos <span class="text-danger">*</span></label>
                          <input type="text" name="prestashop_db_database" class="form-control @error('prestashop_db_database') is-invalid @enderror"
                                 value="{{ old('prestashop_db_database', $settings['prestashop_db_database']) }}" required>
                          <small class="form-text text-muted">Nombre de la base de datos</small>
                          @error('prestashop_db_database')
                            <div class="invalid-feedback">{{ $message }}</div>
                          @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                          <label class="form-label">Usuario <span class="text-danger">*</span></label>
                          <input type="text" name="prestashop_db_username" class="form-control @error('prestashop_db_username') is-invalid @enderror"
                                 value="{{ old('prestashop_db_username', $settings['prestashop_db_username']) }}" required>
                          <small class="form-text text-muted">Usuario MySQL</small>
                          @error('prestashop_db_username')
                            <div class="invalid-feedback">{{ $message }}</div>
                          @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                          <label class="form-label">Contraseña</label>
                          <input type="password" name="prestashop_db_password" class="form-control @error('prestashop_db_password') is-invalid @enderror"
                                 value="{{ old('prestashop_db_password', $settings['prestashop_db_password']) }}" autocomplete="new-password">
                          <small class="form-text text-muted">Contraseña MySQL (opcional si está vacía)</small>
                          @error('prestashop_db_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                          @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                          <label class="form-label">URL prestaShop <span class="text-danger">*</span></label>
                          <input type="url" name="prestashop_url" class="form-control @error('prestashop_url') is-invalid @enderror"
                                 value="{{ old('prestashop_url', $settings['prestashop_url']) }}" required>
                          <small class="form-text text-muted">URL base de PrestaShop (ej: https://www.a-alvarez.com)</small>
                          @error('prestashop_url')
                            <div class="invalid-feedback">{{ $message }}</div>
                          @enderror
                        </div>
                      </div>
                    </div>

                    <hr class="my-4">

                    <!-- Parámetros de Conexión -->
                    <div class="mb-4">
                      <h6 class="mb-1">Parámetros de conexión</h6>
                      <p class="text-muted mb-3">Configura timeouts y reintentos</p>

                      <div class="row">
                        <div class="col-md-4 mb-3">
                          <label class="form-label">Timeout (segundos) <span class="text-danger">*</span></label>
                          <input type="number" name="prestashop_timeout" class="form-control @error('prestashop_timeout') is-invalid @enderror"
                                 value="{{ old('prestashop_timeout', $settings['prestashop_timeout']) }}" min="1" max="300" required>
                          <small class="form-text text-muted">Tiempo máximo de espera</small>
                          @error('prestashop_timeout')
                            <div class="invalid-feedback">{{ $message }}</div>
                          @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                          <label class="form-label">Timeout conexión (segundos) <span class="text-danger">*</span></label>
                          <input type="number" name="prestashop_connect_timeout" class="form-control @error('prestashop_connect_timeout') is-invalid @enderror"
                                 value="{{ old('prestashop_connect_timeout', $settings['prestashop_connect_timeout']) }}" min="1" max="60" required>
                          <small class="form-text text-muted">Tiempo para establecer conexión</small>
                          @error('prestashop_connect_timeout')
                            <div class="invalid-feedback">{{ $message }}</div>
                          @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                          <label class="form-label">API Key</label>
                          <input type="text" name="prestashop_api_key" class="form-control @error('prestashop_api_key') is-invalid @enderror"
                                 value="{{ old('prestashop_api_key', $settings['prestashop_api_key']) }}">
                          <small class="form-text text-muted">Clave API (opcional)</small>
                          @error('prestashop_api_key')
                            <div class="invalid-feedback">{{ $message }}</div>
                          @enderror
                        </div>
                      </div>
                    </div>

                    <hr class="my-4">

                    <!-- Opciones de Sincronización -->
                    <div class="mb-4">
                      <h6 class="mb-3">Opciones de sincronización</h6>

                      <div class="row">
                        <div class="col-md-3 mb-3">
                          <div class="form-check form-switch">
                            <input type="hidden" name="prestashop_sync_enabled" value="no">
                            <input class="form-check-input" type="checkbox" name="prestashop_sync_enabled" id="syncEnabled"
                                   value="yes" {{ old('prestashop_sync_enabled', $settings['prestashop_sync_enabled']) === 'yes' ? 'checked' : '' }}>
                            <label class="form-check-label" for="syncEnabled">
                              Habilitar sincronización
                            </label>
                          </div>
                        </div>

                        <div class="col-md-3 mb-3">
                          <div class="form-check form-switch">
                            <input type="hidden" name="prestashop_sync_products" value="no">
                            <input class="form-check-input" type="checkbox" name="prestashop_sync_products" id="syncProducts"
                                   value="yes" {{ old('prestashop_sync_products', $settings['prestashop_sync_products']) === 'yes' ? 'checked' : '' }}>
                            <label class="form-check-label" for="syncProducts">
                              Sincronizar productos
                            </label>
                          </div>
                        </div>

                        <div class="col-md-3 mb-3">
                          <div class="form-check form-switch">
                            <input type="hidden" name="prestashop_sync_orders" value="no">
                            <input class="form-check-input" type="checkbox" name="prestashop_sync_orders" id="syncOrders"
                                   value="yes" {{ old('prestashop_sync_orders', $settings['prestashop_sync_orders']) === 'yes' ? 'checked' : '' }}>
                            <label class="form-check-label" for="syncOrders">
                              Sincronizar órdenes
                            </label>
                          </div>
                        </div>

                        <div class="col-md-3 mb-3">
                          <div class="form-check form-switch">
                            <input type="hidden" name="prestashop_sync_customers" value="no">
                            <input class="form-check-input" type="checkbox" name="prestashop_sync_customers" id="syncCustomers"
                                   value="yes" {{ old('prestashop_sync_customers', $settings['prestashop_sync_customers']) === 'yes' ? 'checked' : '' }}>
                            <label class="form-check-label" for="syncCustomers">
                              Sincronizar clientes
                            </label>
                          </div>
                        </div>
                      </div>
                    </div>

                    <hr class="my-4">

                    <!-- Configuración de Documentos -->
                    <div class="mb-4">
                      <h6 class="mb-3">Configuración de documentos</h6>

                      <div class="row">
                        <div class="col-md-12 mb-3">
                          <label class="form-label">URL portal de Ddocumentos</label>
                          <input type="url" name="prestashop_documents_portal_url" class="form-control @error('prestashop_documents_portal_url') is-invalid @enderror"
                                 value="{{ old('prestashop_documents_portal_url', $settings['prestashop_documents_portal_url']) }}">
                          <small class="form-text text-muted">URL del portal de carga de documentos (ej: https://www.a-alvarez.com/solicitud-documentos?token={uid})</small>
                          @error('prestashop_documents_portal_url')
                            <div class="invalid-feedback">{{ $message }}</div>
                          @enderror
                        </div>

                        <div class="col-md-12 mb-3">
                          <label class="form-label">IDs de estados pagados</label>
                          <input type="text" name="prestashop_documents_paid_status_ids" class="form-control @error('prestashop_documents_paid_status_ids') is-invalid @enderror"
                                 value="{{ old('prestashop_documents_paid_status_ids', $settings['prestashop_documents_paid_status_ids']) }}">
                          <small class="form-text text-muted">IDs de estados de pago separados por coma (ej: 2,3,4)</small>
                          @error('prestashop_documents_paid_status_ids')
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
                        <input type="hidden" name="prestashop_enabled" value="no">
                        <input class="form-check-input" type="checkbox" name="prestashop_enabled" id="isEnabled"
                               value="yes" {{ old('prestashop_enabled', $settings['prestashop_enabled']) === 'yes' ? 'checked' : '' }}>
                        <label class="form-check-label" for="isEnabled">
                          Servicio PrestaShop habilitado
                        </label>
                      </div>
                      <small class="form-text text-muted">Si está deshabilitado, no se realizarán sincronizaciones</small>
                    </div>

                </div>

                  <div class="card-footer">
                      <button type="submit" class="btn btn-info px-4 waves-effect waves-light mt-2 w-100">
                          Guardar
                      </button>
                      <a href="{{ route('settings.prestashop.index') }}" class="btn btn-secondary px-4 waves-effect waves-light mt-2 w-100">
                          Volver
                      </a>
                  </div>
          </div>
      </form>
  </div>
@endsection

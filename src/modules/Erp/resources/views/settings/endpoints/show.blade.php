@extends('layouts.theme')

@section('title', $endpoint->name)

@section('content')

    @include('core::components.card', ['title' => $endpoint->name])

    @include('core::components.alerts')

    <div class="row">
        <div class="col-lg-8">
            {{-- Basic Information --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-1 fw-bold">Información básica</h5>
                    <p class="small mb-0 text-muted">Detalles generales del endpoint</p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold">Nombre</label>
                            <input type="text" class="form-control" value="{{ $endpoint->name }}" disabled>
                        </div>

                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold">Slug</label>
                            <input type="text" class="form-control" value="{{ $endpoint->slug }}" disabled>
                        </div>

                        @if($endpoint->description)
                            <div class="col-sm-12">
                                <label class="form-label fw-semibold">Descripción</label>
                                <textarea class="form-control" rows="2" disabled>{{ $endpoint->description }}</textarea>
                            </div>
                        @endif

                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold">Estado</label>
                            <input type="text" class="form-control" value="{{ $endpoint->is_active ? 'Activo' : 'Inactivo' }}" disabled>
                        </div>

                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold">Creado</label>
                            <input type="text" class="form-control" value="{{ $endpoint->created_at->format('d/m/Y H:i') }}" disabled>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Connection Configuration --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-1 fw-bold">Configuración de conexión</h5>
                    <p class="small mb-0 text-muted">Parámetros de la conexión HTTP</p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-12">
                            <label class="form-label fw-semibold">URL del endpoint</label>
                            <input type="text" class="form-control" value="{{ $endpoint->url }}" disabled>
                        </div>

                        <div class="col-sm-12 col-md-4">
                            <label class="form-label fw-semibold">Método HTTP</label>
                            <input type="text" class="form-control" value="{{ $endpoint->method }}" disabled>
                        </div>

                        @if($endpoint->content_type)
                            <div class="col-sm-12 col-md-8">
                                <label class="form-label fw-semibold">Content-Type</label>
                                <input type="text" class="form-control" value="{{ $endpoint->content_type }}" disabled>
                            </div>
                        @endif

                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold">Timeout</label>
                            <input type="text" class="form-control" value="{{ $endpoint->timeout ?? 30 }} segundos" disabled>
                        </div>

                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold">Reintentos</label>
                            <input type="text" class="form-control" value="{{ $endpoint->retry_attempts ?? 0 }}" disabled>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Authentication --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-1 fw-bold">Autenticación</h5>
                    <p class="small mb-0 text-muted">Credenciales y método de autenticación</p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @if($endpoint->credential)
                            <div class="col-sm-12">
                                <label class="form-label fw-semibold">Credencial asignada</label>
                                @php
                                    $authTypes = [
                                        'none' => 'Sin autenticación',
                                        'basic' => 'Basic Auth',
                                        'bearer' => 'Bearer Token',
                                        'api_key' => 'API Key',
                                        'custom' => 'Custom Headers',
                                    ];
                                    $authType = $authTypes[$endpoint->credential->auth_type] ?? $endpoint->credential->auth_type;
                                @endphp
                                <input type="text" class="form-control" value="{{ $endpoint->credential->name }} - {{ $authType }}" disabled>
                            </div>

                            @if($endpoint->credential->description)
                                <div class="col-sm-12">
                                    <label class="form-label fw-semibold">Descripción de la credencial</label>
                                    <textarea class="form-control" rows="2" disabled>{{ $endpoint->credential->description }}</textarea>
                                </div>
                            @endif
                        @else
                            <div class="col-sm-12">
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-info-circle me-2"></i>
                                    No hay credencial asignada. La autenticación debe configurarse manualmente.
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Public Tokens Section --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="mb-1 fw-bold">Tokens de acceso público</h5>
                            <p class="small mb-0 text-muted">Gestiona tokens para acceso sin autenticación</p>
                        </div>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#tokenModal">
                            <i class="fa fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @if($endpoint->tokens && $endpoint->tokens->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="20%">Nombre</th>
                                        <th width="15%">Creado</th>
                                        <th width="15%">Expira</th>
                                        <th width="12%">Uso</th>
                                        <th width="12%">Estado</th>
                                        <th width="26%" class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($endpoint->tokens as $token)
                                        <tr>
                                            <td>
                                                <strong>{{ $token->name }}</strong>
                                                @if($token->description)
                                                    <br><small class="text-muted">{{ Str::limit($token->description, 40) }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <small class="text-muted">{{ $token->created_at->format('d/m/Y') }}</small>
                                            </td>
                                            <td>
                                                @if($token->expires_at)
                                                    <small class="text-muted">{{ $token->expires_at->format('d/m/Y') }}</small>
                                                    @if($token->isExpired())
                                                        <br><span class="badge bg-danger-subtle text-danger text-lowercase">Expirado</span>
                                                    @endif
                                                @else
                                                    <small class="text-muted">Sin límite</small>
                                                @endif
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    @if($token->usage_limit)
                                                        {{ $token->usage_count }} / {{ $token->usage_limit }}
                                                    @else
                                                        {{ $token->usage_count }}
                                                    @endif
                                                </small>
                                            </td>
                                            <td>
                                                @if($token->is_active && !$token->isExpired())
                                                    <span class="badge bg-success-subtle text-success">Activo</span>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger">Inactivo</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-outline-primary copy-token-btn"
                                                        data-token="{{ $token->token }}"
                                                        data-slug="{{ $endpoint->slug }}"
                                                        title="Copiar token al portapapeles">
                                                    <i class="fa fa-copy"></i>
                                                </button>
                                                <form action="{{ route('settings.erp.endpoints.tokens.revoke', $token) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                                            onclick="return confirm('¿Revocar este token?')">
                                                        <i class="fa fa-times"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fa fa-key mb-2 text-muted"></i>
                            <p class="text-muted mb-3">No hay tokens generados aún</p>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#tokenModal">
                                <i class="fa fa-plus"></i>
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Statistics --}}
            @if($endpoint->last_used_at || $endpoint->success_count || $endpoint->failure_count)
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-1 fw-bold">Estadísticas de uso</h5>
                        <p class="small mb-0 text-muted">Historial de llamadas y rendimiento</p>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @if($endpoint->last_used_at)
                                <div class="col-sm-12 col-md-6">
                                    <label class="form-label fw-semibold">Último uso</label>
                                    <input type="text" class="form-control" value="{{ $endpoint->last_used_at->diffForHumans() }}" disabled>
                                </div>
                            @endif

                            <div class="col-sm-12 col-md-6">
                                <label class="form-label fw-semibold">Llamadas exitosas</label>
                                <input type="text" class="form-control" value="{{ $endpoint->success_count ?? 0 }}" disabled>
                            </div>

                            <div class="col-sm-12 col-md-6">
                                <label class="form-label fw-semibold">Llamadas fallidas</label>
                                <input type="text" class="form-control" value="{{ $endpoint->failure_count ?? 0 }}" disabled>
                            </div>

                            @if(($endpoint->success_count + $endpoint->failure_count) > 0)
                                <div class="col-sm-12 col-md-6">
                                    <label class="form-label fw-semibold">Tasa de éxito</label>
                                    <input type="text" class="form-control"
                                           value="{{ number_format(($endpoint->success_count / ($endpoint->success_count + $endpoint->failure_count)) * 100, 2) }}%"
                                           disabled>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            {{-- Instructions Card --}}
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="mb-3">
                        Cómo usar
                    </h5>

                    <ol class="mb-3 ps-3">
                        <li class="mb-2">Revisa la <strong>configuración de conexión</strong> y las <strong>credenciales</strong> asignadas</li>
                        <li class="mb-2">Usa el botón <strong>"Probar conexión"</strong> para verificar que el endpoint responde correctamente</li>
                        <li class="mb-2">Genera <strong>tokens de acceso público</strong> si necesitas compartir el endpoint sin autenticación</li>
                        <li class="mb-2">Monitorea las <strong>estadísticas de uso</strong> para identificar problemas de rendimiento</li>
                        <li class="mb-2">Activa/desactiva el endpoint temporalmente sin perder la configuración</li>
                    </ol>

                    <div class="alert alert-info mb-0">
                        <div class="d-flex">
                            <div>
                                <strong>Consejo:</strong> Los tokens públicos permiten acceder al endpoint sin credenciales.
                                Son útiles para integraciones externas, pero asegúrate de configurar límites de uso y
                                restricciones de IP para mayor seguridad.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Actions Card --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-1 fw-bold">Acciones</h5>
                    <p class="small mb-0 text-muted">Operaciones disponibles</p>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <form action="{{ route('settings.erp.endpoints.test', $endpoint) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary w-100">
                                Probar conexión
                            </button>
                        </form>

                        <a href="{{ route('settings.erp.endpoints.edit', $endpoint) }}" class="btn btn-outline-primary w-100">
                            Editar
                        </a>

                        <form action="{{ route('settings.erp.endpoints.toggle', $endpoint) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary w-100">
                                {{ $endpoint->is_active ? 'Desactivar' : 'Activar' }}
                            </button>
                        </form>

                        <hr class="my-2">

                        <form action="{{ route('settings.erp.endpoints.destroy', $endpoint) }}" method="POST"
                              onsubmit="return confirm('¿Estás seguro de eliminar este endpoint? Esta acción no se puede deshacer.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-primary w-100">
                                Eliminar
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Information Card --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-1 fw-bold">Información adicional</h5>
                    <p class="small mb-0 text-muted">Metadatos del endpoint</p>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">ID</label>
                        <input type="text" class="form-control" value="#{{ $endpoint->id }}" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Última actualización</label>
                        <input type="text" class="form-control" value="{{ $endpoint->updated_at->format('d/m/Y H:i') }}" disabled>
                    </div>

                    @if($endpoint->account_id)
                        <div class="mb-0">
                            <label class="form-label fw-semibold">Cuenta</label>
                            <input type="text" class="form-control" value="#{{ $endpoint->account_id }}" disabled>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Generate Token Modal --}}
    <div class="modal fade" id="tokenModal" tabindex="-1" aria-labelledby="tokenModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tokenModalLabel">Generar nuevo token</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('settings.erp.endpoints.tokens.generate', $endpoint) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="tokenName" class="form-label fw-semibold">Nombre del token</label>
                            <input type="text" class="form-control" id="tokenName" name="name" required
                                   placeholder="Ej: Integración Web App">
                            <small class="text-muted">Nombre descriptivo para identificar el propósito del token</small>
                        </div>

                        <div class="mb-3">
                            <label for="tokenDescription" class="form-label fw-semibold">Descripción (opcional)</label>
                            <textarea class="form-control" id="tokenDescription" name="description" rows="2"
                                      placeholder="Detalles adicionales sobre el uso de este token"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="tokenExpires" class="form-label fw-semibold">Fecha de expiración (opcional)</label>
                            <input type="datetime-local" class="form-control" id="tokenExpires" name="expires_at">
                            <small class="text-muted">Dejar vacío para tokens sin fecha de expiración</small>
                        </div>

                        <div class="mb-3">
                            <label for="tokenLimit" class="form-label fw-semibold">Límite de uso (opcional)</label>
                            <input type="number" class="form-control" id="tokenLimit" name="usage_limit"
                                   placeholder="Ej: 10000" min="1">
                            <small class="text-muted">Número máximo de llamadas permitidas (sin límite si está vacío)</small>
                        </div>

                        <div class="mb-0">
                            <label for="tokenIps" class="form-label fw-semibold">IPs permitidas (opcional)</label>
                            <textarea class="form-control" id="tokenIps" name="allowed_ips" rows="2"
                                      placeholder='[&#34;192.168.1.1&#34;, &#34;10.0.0.0/8&#34;]'></textarea>
                            <small class="text-muted">JSON array de IPs o rangos CIDR permitidas (vacío = todas las IPs)</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary  w-100 "> Generar token</button>
                        <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Copy token to clipboard
    document.querySelectorAll('.copy-token-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const token = this.dataset.token;
            const slug = this.dataset.slug;
            const url = `/api/erp/public/${token}/${slug}`;

            const tempInput = document.createElement('input');
            tempInput.value = url;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);

            Swal.fire({
                title: '¡Copiado!',
                text: 'URL del token copiada al portapapeles',
                icon: 'success',
                timer: 2000
            });
        });
    });
});
</script>
@endpush

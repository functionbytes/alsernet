@extends('layouts.theme')

@section('title', 'Configuracion de pagos Wompi')

@section('content')
    @include('core::components.alerts')

    <div class="card">
        <div class="card-header p-4 border-bottom border-light">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1 fw-bold">Wompi</h5>
                    <p class="small mb-0 text-muted">Paga con tarjeta, PSE, Nequi y más mediante Wompi</p>
                </div>
                <a href="{{ route('ecommerce-payment.methods.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Métodos de pago
                </a>
            </div>
        </div>

        <div class="card-body p-4">
            <form action="{{ route('ecommerce-payment.settings.update') }}" method="POST">
                @csrf
                @method('PUT')

                {{-- Estado y modo --}}
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Estado <span class="text-danger">*</span></label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="1" {{ ($settings['status'] ?? '') == '1' ? 'selected' : '' }}>Activo</option>
                            <option value="0" {{ ($settings['status'] ?? '') != '1' ? 'selected' : '' }}>Inactivo</option>
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Modo <span class="text-danger">*</span></label>
                        <select name="mode" class="form-select @error('mode') is-invalid @enderror">
                            <option value="sandbox" {{ ($settings['mode'] ?? '') == 'sandbox' ? 'selected' : '' }}>Sandbox</option>
                            <option value="production" {{ ($settings['mode'] ?? '') == 'production' ? 'selected' : '' }}>Produccion</option>
                        </select>
                        @error('mode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Nombre del método</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $settings['name'] ?? 'Wompi') }}"
                               placeholder="Wompi">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Descripcion corta</label>
                        <input type="text" name="description" class="form-control @error('description') is-invalid @enderror"
                               value="{{ old('description', $settings['description'] ?? '') }}"
                               placeholder="Texto visible en el checkout">
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <hr class="my-4">
                <h6 class="fw-bold mb-3">Credenciales de API</h6>

                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Public Key <span class="text-danger">*</span></label>
                        <input type="text" name="public_key" class="form-control @error('public_key') is-invalid @enderror"
                               value="{{ old('public_key', $settings['public_key'] ?? '') }}"
                               placeholder="pub_test_...">
                        @error('public_key')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Private Key <span class="text-danger">*</span></label>
                        <input type="text" name="private_key" class="form-control @error('private_key') is-invalid @enderror"
                               value="{{ old('private_key', $settings['private_key'] ?? '') }}"
                               placeholder="prv_test_...">
                        @error('private_key')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Integrity Secret <span class="text-danger">*</span></label>
                        <input type="text" name="integrity_secret" class="form-control @error('integrity_secret') is-invalid @enderror"
                               value="{{ old('integrity_secret', $settings['integrity_secret'] ?? '') }}"
                               placeholder="test_integrity_...">
                        @error('integrity_secret')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Event Secret <span class="text-danger">*</span></label>
                        <input type="text" name="event_secret" class="form-control @error('event_secret') is-invalid @enderror"
                               value="{{ old('event_secret', $settings['event_secret'] ?? '') }}"
                               placeholder="test_event_...">
                        <div class="form-text">Requerido para validar webhooks de Wompi.</div>
                        @error('event_secret')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <hr class="my-4">
                <h6 class="fw-bold mb-3">Notificaciones</h6>

                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Email de notificacion</label>
                        <input type="email" name="notification_email" class="form-control @error('notification_email') is-invalid @enderror"
                               value="{{ old('notification_email', $settings['notification_email'] ?? '') }}"
                               placeholder="admin@ejemplo.com">
                        <div class="form-text">Email donde se notificaran los pagos fallidos. Si esta vacio, se usa el email configurado en la aplicacion.</div>
                        @error('notification_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <hr class="my-4">
                <h6 class="fw-bold mb-3">Comision de pago (opcional)</h6>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Habilitar comision</label>
                        <select name="fee_enabled" class="form-select @error('fee_enabled') is-invalid @enderror">
                            <option value="1" {{ ($settings['fee_enabled'] ?? '') == '1' ? 'selected' : '' }}>Si</option>
                            <option value="0" {{ ($settings['fee_enabled'] ?? '') != '1' ? 'selected' : '' }}>No</option>
                        </select>
                        @error('fee_enabled')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Tipo</label>
                        <select name="fee_type" class="form-select @error('fee_type') is-invalid @enderror">
                            <option value="fixed" {{ ($settings['fee_type'] ?? 'fixed') == 'fixed' ? 'selected' : '' }}>Fijo</option>
                            <option value="percentage" {{ ($settings['fee_type'] ?? '') == 'percentage' ? 'selected' : '' }}>Porcentaje</option>
                        </select>
                        @error('fee_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Valor</label>
                        <input type="number" step="0.01" min="0" name="fee_value"
                               class="form-control @error('fee_value') is-invalid @enderror"
                               value="{{ old('fee_value', $settings['fee_value'] ?? '0') }}">
                        @error('fee_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                @if (!empty($configErrors) || !empty($configWarnings))
                    <div class="mb-4">
                        @foreach ($configErrors ?? [] as $error)
                            <div class="alert alert-danger mb-2">
                                <i class="fas fa-times-circle me-2"></i>{{ $error }}
                            </div>
                        @endforeach
                        @foreach ($configWarnings ?? [] as $warning)
                            <div class="alert alert-warning mb-2">
                                <i class="fas fa-exclamation-triangle me-2"></i>{{ $warning }}
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="d-flex align-items-center gap-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Guardar configuracion
                    </button>

                    @if (!empty($configErrors))
                        <span class="badge bg-danger">{{ count($configErrors) }} errores</span>
                    @elseif (!empty($configWarnings))
                        <span class="badge bg-warning text-dark">{{ count($configWarnings) }} advertencias</span>
                    @else
                        <span class="badge bg-success">Configuracion valida</span>
                    @endif
                </div>
            </form>
        </div>
    </div>
@endsection

@extends('layouts.theme')

@section('title', 'Configuracion de pagos Wompi')

@section('page_header')
    @include('core::components.card', ['title' => 'Configuracion de pagos Wompi'])
@endsection

@section('content')
    @include('core::components.alerts')

    <div class="card">
        <div class="card-body">
            <form action="{{ route('ecommerce-payment.settings.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('Estado') }} <span class="text-danger">*</span></label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="1" {{ $settings['status'] == '1' ? 'selected' : '' }}>{{ __('Activo') }}</option>
                            <option value="0" {{ $settings['status'] == '0' ? 'selected' : '' }}>{{ __('Inactivo') }}</option>
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('Modo') }} <span class="text-danger">*</span></label>
                        <select name="mode" class="form-select @error('mode') is-invalid @enderror">
                            <option value="sandbox" {{ $settings['mode'] == 'sandbox' ? 'selected' : '' }}>{{ __('Sandbox') }}</option>
                            <option value="production" {{ $settings['mode'] == 'production' ? 'selected' : '' }}>{{ __('Produccion') }}</option>
                        </select>
                        @error('mode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('Nombre del metodo') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ $settings['name'] }}">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('Descripcion') }}</label>
                        <input type="text" name="description" class="form-control @error('description') is-invalid @enderror" value="{{ $settings['description'] }}">
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ __('Public Key') }} <span class="text-danger">*</span></label>
                    <input type="text" name="public_key" class="form-control @error('public_key') is-invalid @enderror" value="{{ $settings['public_key'] }}">
                    @error('public_key')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ __('Private Key') }} <span class="text-danger">*</span></label>
                    <input type="text" name="private_key" class="form-control @error('private_key') is-invalid @enderror" value="{{ $settings['private_key'] }}">
                    @error('private_key')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ __('Integrity Secret') }} <span class="text-danger">*</span></label>
                    <input type="text" name="integrity_secret" class="form-control @error('integrity_secret') is-invalid @enderror" value="{{ $settings['integrity_secret'] }}">
                    @error('integrity_secret')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ __('Event Secret') }} <span class="text-danger">*</span></label>
                    <input type="text" name="event_secret" class="form-control @error('event_secret') is-invalid @enderror" value="{{ $settings['event_secret'] }}">
                    <small class="text-muted">{{ __('Requerido para validar webhooks de Wompi.') }}</small>
                    @error('event_secret')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ __('Email de notificacion') }}</label>
                    <input type="email" name="notification_email" class="form-control @error('notification_email') is-invalid @enderror" value="{{ $settings['notification_email'] }}">
                    <small class="text-muted">{{ __('Email donde se notificaran los pagos fallidos. Si esta vacio, se usa el email configurado en la aplicacion.') }}</small>
                    @error('notification_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <hr class="my-4">
                <h6 class="mb-3">{{ __('Comision de pago') }}</h6>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('Habilitar comision') }}</label>
                        <select name="fee_enabled" class="form-select @error('fee_enabled') is-invalid @enderror">
                            <option value="1" {{ $settings['fee_enabled'] == '1' ? 'selected' : '' }}>{{ __('Si') }}</option>
                            <option value="0" {{ $settings['fee_enabled'] != '1' ? 'selected' : '' }}>{{ __('No') }}</option>
                        </select>
                        @error('fee_enabled')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('Tipo') }}</label>
                        <select name="fee_type" class="form-select @error('fee_type') is-invalid @enderror">
                            <option value="fixed" {{ $settings['fee_type'] == 'fixed' ? 'selected' : '' }}>{{ __('Fijo') }}</option>
                            <option value="percentage" {{ $settings['fee_type'] == 'percentage' ? 'selected' : '' }}>{{ __('Porcentaje') }}</option>
                        </select>
                        @error('fee_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('Valor') }}</label>
                        <input type="number" step="0.01" name="fee_value" class="form-control @error('fee_value') is-invalid @enderror" value="{{ $settings['fee_value'] }}">
                        @error('fee_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>{{ __('Guardar configuracion') }}
                    </button>

                    @if(!empty($configErrors))
                        <span class="badge bg-danger">{{ count($configErrors) }} errores</span>
                    @elseif(!empty($configWarnings))
                        <span class="badge bg-warning text-dark">{{ count($configWarnings) }} advertencias</span>
                    @else
                        <span class="badge bg-success">{{ __('Configuracion valida') }}</span>
                    @endif
                </div>
            </form>
        </div>
    </div>

    @if(!empty($configErrors) || !empty($configWarnings))
    <div class="card mt-4">
        <div class="card-header">
            <h6 class="mb-0">{{ __('Diagnostico de configuracion') }}</h6>
        </div>
        <div class="card-body">
            @foreach($configErrors as $error)
                <div class="alert alert-danger mb-2"><i class="fas fa-times-circle me-2"></i>{{ $error }}</div>
            @endforeach
            @foreach($configWarnings as $warning)
                <div class="alert alert-warning mb-2"><i class="fas fa-exclamation-triangle me-2"></i>{{ $warning }}</div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection

@extends('layouts.theme')

@section('title', 'Transferencia bancaria')

@section('content')
    @include('core::components.alerts')

    <div class="card">
        <div class="card-header p-4 border-bottom border-light">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1 fw-bold">Transferencia bancaria</h5>
                    <p class="small mb-0 text-muted">El cliente transfiere el dinero a la cuenta bancaria configurada</p>
                </div>
                <a href="{{ route('ecommerce-payment.methods.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Métodos de pago
                </a>
            </div>
        </div>

        <div class="card-body p-4">
            <form action="{{ route('ecommerce-payment.bank-transfer.settings.update') }}" method="POST">
                @csrf
                @method('PUT')

                {{-- Estado y nombre --}}
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Estado</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="1" {{ $settings['status'] == '1' ? 'selected' : '' }}>Activo</option>
                            <option value="0" {{ $settings['status'] == '0' ? 'selected' : '' }}>Inactivo</option>
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Nombre del método</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $settings['name']) }}"
                               placeholder="Transferencia bancaria">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Descripcion corta</label>
                        <input type="text" name="description" class="form-control @error('description') is-invalid @enderror"
                               value="{{ old('description', $settings['description']) }}"
                               placeholder="Texto visible en el checkout">
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <hr class="my-4">
                <h6 class="fw-bold mb-3">Datos bancarios</h6>
                <p class="small text-muted mb-3">Esta información se mostrará al cliente en la página de instrucciones después de confirmar la orden.</p>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Banco</label>
                        <input type="text" name="bank_name" class="form-control @error('bank_name') is-invalid @enderror"
                               value="{{ old('bank_name', $settings['bank_name']) }}"
                               placeholder="Bancolombia, Davivienda, Nequi…">
                        @error('bank_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tipo de cuenta</label>
                        <input type="text" name="account_type" class="form-control @error('account_type') is-invalid @enderror"
                               value="{{ old('account_type', $settings['account_type']) }}"
                               placeholder="Ahorros, Corriente…">
                        @error('account_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Número de cuenta</label>
                        <input type="text" name="account_number" class="form-control @error('account_number') is-invalid @enderror"
                               value="{{ old('account_number', $settings['account_number']) }}"
                               placeholder="000-000000-00">
                        @error('account_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Titular de la cuenta</label>
                        <input type="text" name="account_holder" class="form-control @error('account_holder') is-invalid @enderror"
                               value="{{ old('account_holder', $settings['account_holder']) }}"
                               placeholder="Nombre o razón social">
                        @error('account_holder')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Tipo de documento</label>
                        <input type="text" name="document_type" class="form-control @error('document_type') is-invalid @enderror"
                               value="{{ old('document_type', $settings['document_type']) }}"
                               placeholder="NIT, CC…">
                        @error('document_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Número de documento</label>
                        <input type="text" name="document_number" class="form-control @error('document_number') is-invalid @enderror"
                               value="{{ old('document_number', $settings['document_number']) }}"
                               placeholder="900.000.000-0">
                        @error('document_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Instrucciones adicionales</label>
                        <textarea name="instructions" rows="4"
                                  class="form-control @error('instructions') is-invalid @enderror"
                                  placeholder="Instrucciones para el cliente…">{{ old('instructions', $settings['instructions']) }}</textarea>
                        <div class="form-text">Por ejemplo: "Envíanos el comprobante al WhatsApp o email para confirmar tu pedido."</div>
                        @error('instructions')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Guardar configuracion
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

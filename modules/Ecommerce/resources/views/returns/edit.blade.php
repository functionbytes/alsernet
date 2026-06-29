@extends('layouts.theme')

@section('title', 'Editar devolución')

@section('content')

    <div class="row g-3">

        {{-- Formulario --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('ecommerce.returns.update', $return) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Editar devolución</h5>
                        <small class="text-muted">Orden {{ $return->order?->code ?? '—' }}</small>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Orden</label>
                                <input type="text" class="form-control" value="{{ $return->order?->code ?? '—' }}" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Cliente</label>
                                <input type="text" class="form-control" value="{{ $return->customer?->name ?? '—' }}" disabled>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Estado de devolución <span class="text-danger">*</span></label>
                            <select name="return_status" class="form-select @error('return_status') is-invalid @enderror" required>
                                <option value="pending" {{ old('return_status', $return->return_status) === 'pending' ? 'selected' : '' }}>Pendiente</option>
                                <option value="processing" {{ old('return_status', $return->return_status) === 'processing' ? 'selected' : '' }}>Procesando</option>
                                <option value="completed" {{ old('return_status', $return->return_status) === 'completed' ? 'selected' : '' }}>Completado</option>
                                <option value="cancelled" {{ old('return_status', $return->return_status) === 'cancelled' ? 'selected' : '' }}>Cancelado</option>
                            </select>
                            @error('return_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Motivo de devolución</label>
                            <textarea name="reason" class="form-control @error('reason') is-invalid @enderror" rows="3">{{ old('reason', $return->reason) }}</textarea>
                            @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-0">
                            <label class="form-label">Estado de orden relacionada</label>
                            <input type="text" name="order_status" class="form-control @error('order_status') is-invalid @enderror" value="{{ old('order_status', $return->order_status) }}">
                            <small class="form-text text-muted">Estado en el que queda la orden tras la devolución</small>
                            @error('order_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Actualizar devolución</button>
                        <a href="{{ route('ecommerce.returns.show', $return) }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Panel informativo --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-2">Estados de devolución</h6>
                    <ul class="list-unstyled small text-muted mb-0">
                        <li class="mb-1"><strong>Pendiente:</strong> esperando aprobación.</li>
                        <li class="mb-1"><strong>Procesando:</strong> aprobada, en revisión del producto.</li>
                        <li class="mb-1"><strong>Completado:</strong> reembolso emitido.</li>
                        <li><strong>Cancelado:</strong> rechazado o desistido.</li>
                    </ul>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-2">Reembolso</h6>
                    <p class="card-text text-muted mb-0">
                        Marcar como completado no genera el reembolso automáticamente. Procésalo desde la pasarela de pago.
                    </p>
                </div>
            </div>
        </div>

    </div>

@endsection

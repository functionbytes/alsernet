@extends('layouts.theme')

@section('title', 'Editar devolucion')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Editar devolucion'])
@endsection

@section('content')
    @include('core::components.alerts')

    <div class="card">
        <div class="card-body">
            <form action="{{ route('ecommerce.returns.update', $return) }}" method="POST">
                @csrf @method('PUT')

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Orden</label>
                        <input type="text" class="form-control" value="{{ $return->order?->code ?? '—' }}" disabled>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Cliente</label>
                        <input type="text" class="form-control" value="{{ $return->customer?->name ?? '—' }}" disabled>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Estado de devolucion <span class="text-danger">*</span></label>
                    <select name="return_status" class="form-select @error('return_status') is-invalid @enderror" required>
                        <option value="pending" {{ old('return_status', $return->return_status) === 'pending' ? 'selected' : '' }}>Pendiente</option>
                        <option value="processing" {{ old('return_status', $return->return_status) === 'processing' ? 'selected' : '' }}>Procesando</option>
                        <option value="completed" {{ old('return_status', $return->return_status) === 'completed' ? 'selected' : '' }}>Completado</option>
                        <option value="cancelled" {{ old('return_status', $return->return_status) === 'cancelled' ? 'selected' : '' }}>Cancelado</option>
                    </select>
                    @error('return_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Motivo de devolucion</label>
                    <textarea name="reason" class="form-control @error('reason') is-invalid @enderror" rows="3">{{ old('reason', $return->reason) }}</textarea>
                    @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Estado de orden relacionada</label>
                    <input type="text" name="order_status" class="form-control @error('order_status') is-invalid @enderror" value="{{ old('order_status', $return->order_status) }}">
                    @error('order_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('ecommerce.returns.show', $return) }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Actualizar devolucion</button>
                </div>
            </form>
        </div>
    </div>
@endsection

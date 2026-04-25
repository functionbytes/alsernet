@extends('layouts.theme')

@section('title', 'Detalle de devolucion')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Detalle de devolucion'])

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Devolucion #{{ $return->id }}</h5>
            <span class="badge bg-{{ $return->return_status === 'completed' ? 'success' : ($return->return_status === 'pending' ? 'warning' : 'info') }}">{{ $return->return_status }}</span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Informacion de orden</h6>
                    <p class="mb-1"><strong>Orden:</strong> {{ $return->order?->code ?? '—' }}</p>
                    <p class="mb-1"><strong>Cliente:</strong> {{ $return->customer?->name ?? '—' }}</p>
                </div>
                <div class="col-md-6">
                    <h6>Informacion de devolucion</h6>
                    <p class="mb-1"><strong>Motivo:</strong> {{ $return->reason ?? '—' }}</p>
                    <p class="mb-1"><strong>Estado orden:</strong> {{ $return->order_status ?? '—' }}</p>
                </div>
            </div>

            <hr>

            <form action="{{ route('ecommerce.returns.status', $return) }}" method="POST" class="mt-3">
                @csrf
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Actualizar estado</label>
                        <select name="return_status" class="form-select">
                            <option value="pending" {{ $return->return_status === 'pending' ? 'selected' : '' }}>Pendiente</option>
                            <option value="processing" {{ $return->return_status === 'processing' ? 'selected' : '' }}>Procesando</option>
                            <option value="completed" {{ $return->return_status === 'completed' ? 'selected' : '' }}>Completado</option>
                            <option value="cancelled" {{ $return->return_status === 'cancelled' ? 'selected' : '' }}>Cancelado</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary">Actualizar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@extends('layouts.theme')

@section('title', 'Webhook')

@section('content')

    <form action="{{ route('settings.ecommerce.webhook.update') }}" method="POST">
        @csrf
        @method('PATCH')

        <div class="card">
            <div class="card-header border-bottom p-3">
                <h5 class="mb-0 fw-bold">Webhook de pedidos</h5>
                <small class="text-muted">Recibe notificaciones cuando se realiza un pedido</small>
            </div>
            <div class="card-body">
                @include('core::components.alerts')

                <label for="order_placed_webhook_url" class="form-label fw-semibold">URL del webhook (método: POST)</label>
                <input type="text" name="order_placed_webhook_url" id="order_placed_webhook_url"
                    class="form-control @error('order_placed_webhook_url') is-invalid @enderror"
                    placeholder="https://..."
                    value="{{ old('order_placed_webhook_url', $settings['order_placed_webhook_url'] ?? '') }}">
                @error('order_placed_webhook_url')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text mt-1 text-muted">Para recibir notificaciones cuando se realiza un pedido, configura una URL externa. Déjala vacía para deshabilitar.</div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Guardar ajustes
                </button>
            </div>
        </div>

    </form>

@endsection

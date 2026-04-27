@extends('layouts.theme')

@section('title', 'Webhook')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Webhook'])
    @include('core::components.alerts')

    <form action="{{ route('settings.ecommerce.webhook.update') }}" method="POST">
        @csrf
        @method('PATCH')

        <div class="mb-3">
            <h5 class="fw-bold mb-1">Gancho web</h5>
            <div class="text-muted small">Enviar webhook cuando se realiza el pedido</div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <label for="order_placed_webhook_url" class="form-label fw-semibold">URL del webhook del pedido realizado (método: POST)</label>
                <input type="text" name="order_placed_webhook_url" id="order_placed_webhook_url"
                    class="form-control @error('order_placed_webhook_url') is-invalid @enderror"
                    placeholder="https://..."
                    value="{{ old('order_placed_webhook_url', $settings['order_placed_webhook_url'] ?? '') }}">
                @error('order_placed_webhook_url')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text mt-1">Para recibir notificaciones cuando se realiza un pedido, puede configurar una URL de webhook externo. Si tiene una URL de webhook externo, puede ingresar esta URL o simplemente dejarla vacía.</div>
            </div>
        </div>

        <div class="mb-4">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Guardar ajustes
            </button>
        </div>

    </form>
@endsection

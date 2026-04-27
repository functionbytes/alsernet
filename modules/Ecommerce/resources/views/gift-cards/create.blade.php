@extends('layouts.theme')

@section('title', 'Nueva gift card')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Nueva gift card'])

    <form action="{{ route('ecommerce.gift-cards.store') }}" method="POST">
        @csrf
        <div class="row g-4 align-items-start">

            <div class="col-12 col-lg-8">
                <div class="card">
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Datos de la gift card</h5>
                        <small class="text-muted">Complete los campos para generar la gift card.</small>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Monto <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" name="amount" step="0.01" min="1"
                                        class="form-control @error('amount') is-invalid @enderror"
                                        value="{{ old('amount') }}" required>
                                </div>
                                @error('amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fecha de vencimiento</label>
                                <input type="date" name="expires_at"
                                    class="form-control @error('expires_at') is-invalid @enderror"
                                    value="{{ old('expires_at') }}"
                                    min="{{ now()->addDay()->toDateString() }}">
                                @error('expires_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nombre del destinatario</label>
                                <input type="text" name="recipient_name"
                                    class="form-control @error('recipient_name') is-invalid @enderror"
                                    value="{{ old('recipient_name') }}">
                                @error('recipient_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email del destinatario</label>
                                <input type="email" name="recipient_email"
                                    class="form-control @error('recipient_email') is-invalid @enderror"
                                    value="{{ old('recipient_email') }}">
                                @error('recipient_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Mensaje personalizado</label>
                                <textarea name="message" rows="3"
                                    class="form-control @error('message') is-invalid @enderror"
                                    placeholder="Mensaje opcional para incluir en la gift card...">{{ old('message') }}</textarea>
                                @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Generar gift card</button>
                        <a href="{{ route('ecommerce.gift-cards.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title mb-3">Sobre las gift cards</h6>
                        <p class="card-text text-muted small">Se generará un código único automáticamente. El código podrá aplicarse en el checkout para descontar el monto del saldo disponible.</p>
                        <hr>
                        <p class="card-text text-muted small mb-0">Si no se establece vencimiento, la gift card no caducará.</p>
                    </div>
                </div>
            </div>

        </div>
    </form>
@endsection

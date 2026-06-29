@extends('ecommerce::layouts.shop-wowy')

@section('title', 'Cancelar suscripcion')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5 text-center">
                <div class="mb-4">
                    <i class="fas fa-envelope-open fa-4x text-muted opacity-75"></i>
                </div>
                <h4 class="fw-bold mb-2">Suscripcion cancelada</h4>
                <p class="text-muted mb-4">
                    Has cancelado tu suscripcion al newsletter. Ya no recibiras correos de nuestra parte.
                </p>
                <a href="{{ route('shop.index') }}" class="btn btn-primary">
                    Volver a la tienda
                </a>
            </div>
        </div>
    </div>
@endsection

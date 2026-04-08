@extends('layouts.theme')

@section('title', 'Nueva campaña')

@section('content')

    @include('core::components.card', ['title' => 'Nueva campaña'])

    <div class="row g-3">

        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('reviews.campaigns.store') }}" method="POST">
                    @csrf
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nueva campaña</h5>
                        <small class="text-muted">Configura una campaña para solicitar reseñas a tus clientes por correo.</small>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')
                        @include('reviews::campaigns._form', ['campaign' => null])
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Crear campaña</button>
                        <a href="{{ route('reviews.campaigns.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Personalizacion del mensaje</h6>
                    <p class="card-text text-muted mb-0">
                        Usa la variable <code>{customer_name}</code> en el mensaje para sustituirla automáticamente con el nombre del cliente al momento del envío.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">URL de reseña</h6>
                    <p class="card-text text-muted mb-0">
                        Puedes obtener el enlace directo a tu perfil de Google Business desde Google Maps buscando tu negocio y seleccionando <em>Compartir &rsaquo; Copiar enlace</em>.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Envio programado</h6>
                    <p class="card-text text-muted mb-0">
                        Si dejas el campo de programación vacío, la campaña se creará como borrador y podrás enviar manualmente cuando quieras.
                    </p>
                </div>
            </div>
        </div>

    </div>

@endsection

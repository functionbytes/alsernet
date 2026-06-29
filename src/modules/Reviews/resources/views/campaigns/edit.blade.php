@extends('layouts.theme')

@section('title', 'Editar campaña')

@section('page_header')
    @include('core::components.card', ['title' => 'Editar campaña'])
@endsection

@section('content')

    <div class="row g-3">

        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('reviews.campaigns.update', $campaign) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Editar campaña</h5>
                        <small class="text-muted">Modifica los datos de la campaña.</small>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')
                        @include('reviews::campaigns._form', ['campaign' => $campaign])
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar cambios</button>
                        <a href="{{ route('reviews.campaigns.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Ubicacion</h6>
                    <p class="card-text text-muted mb-0">
                        La ubicacion no puede cambiarse una vez creada la campaña. Si necesitas otra ubicacion, crea una nueva campaña.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Estadísticas de envio</h6>
                    <p class="card-text text-muted mb-0">
                        Esta campaña ha enviado <strong>{{ $campaign->sends()->sent()->count() }}</strong> correo(s) en total.
                        @if($campaign->sends()->whereNotNull('opened_at')->count() > 0)
                            {{ $campaign->sends()->whereNotNull('opened_at')->count() }} abierto(s).
                        @endif
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Personalizacion del mensaje</h6>
                    <p class="card-text text-muted mb-0">
                        Usa <code>{customer_name}</code> en el mensaje para personalizarlo con el nombre del cliente.
                    </p>
                </div>
            </div>
        </div>

    </div>

@endsection

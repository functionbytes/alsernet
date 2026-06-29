@extends('template::layouts.full-width')

@section('title', 'Política de cookies')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body p-4 p-lg-5">

                        <h1 class="h3 fw-bold mb-3">Política de cookies</h1>

                        <p class="text-muted mb-4">
                            En cumplimiento con el Reglamento General de Protección de Datos (RGPD) y la normativa
                            sobre servicios de la sociedad de la información, te informamos sobre el uso de cookies
                            en este sitio web.
                        </p>

                        <p class="mb-4">
                            Al acceder a este sitio web mostramos un banner de consentimiento que te permite
                            aceptar, rechazar o personalizar el uso de cookies. Puedes retirar o modificar tu
                            consentimiento en cualquier momento pulsando el siguiente botón:
                        </p>

                        <p class="mb-5">
                            <button type="button"
                                    class="btn btn-outline-secondary btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#cookie-preferences-modal">
                                Gestionar mis preferencias
                            </button>
                        </p>

                        <hr class="mb-5">

                        @foreach ($categories as $key => $category)
                            <section class="mb-5">
                                <h3 class="h5 fw-bold mb-2">{{ __($category['name']) }}</h3>

                                <p class="text-muted mb-3">{{ __($category['description']) }}</p>

                                @if ($inventory->has($key) && $inventory[$key]->isNotEmpty())
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Nombre</th>
                                                    <th>Proveedor</th>
                                                    <th>Propósito</th>
                                                    <th>Duración</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($inventory[$key] as $cookie)
                                                    <tr>
                                                        <td class="fw-semibold">{{ $cookie->name }}</td>
                                                        <td>{{ $cookie->provider }}</td>
                                                        <td>{{ $cookie->purpose }}</td>
                                                        <td>{{ $cookie->duration }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <p class="text-muted fst-italic">No se han registrado cookies en esta categoría.</p>
                                @endif
                            </section>
                        @endforeach

                        <hr class="mb-4">

                        <section>
                            <h3 class="h5 fw-bold mb-2">Más información</h3>
                            <p class="text-muted mb-0">
                                Si tienes cualquier duda sobre el uso de cookies en este sitio web,
                                puedes contactarnos a través de los canales habituales de atención al cliente.
                            </p>
                            <p class="text-muted mt-2 mb-0">
                                <small>Última actualización: {{ date('d/m/Y') }}</small>
                            </p>
                        </section>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

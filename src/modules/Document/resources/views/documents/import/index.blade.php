@extends('layouts.theme')

@section('content')



    @include('core::components.card', ['title' => 'Importar Documentos'])

    @if ($message = session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check me-2"></i> {{ $message }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($message = session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-circle-exclamation me-2"></i> {{ $message }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-3">

        <!-- Opción 1: Importar desde PrestaShop (API) -->
        <div class="col-md-6">
            <div class="card h-100 shadow-sm">
                <div class="card-header border-bottom py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <h6 class="mb-0 fw-bold text-dark">
                            Prestashop
                        </h6>
                        <span class="badge bg-primary">API</span>
                    </div>
                </div>

                <div class="card-body pb-0">
                    <p class="text-muted mb-3">
                        Importa órdenes directamente desde <strong>Prestashop</strong> utilizando el ID de la orden.
                        Los datos del cliente y productos se sincronizan automáticamente.
                    </p>

                    <div class="alert alert-info alert-sm py-2 px-3 mb-3" role="alert">
                        <strong>¿Qué se importa?</strong>
                    </div>

                    <ul class="list-unstyled ms-3 mb-4">
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Datos del cliente</strong>
                            <br>
                            <small class="text-muted">Nombre, email, teléfono, DNI desde la dirección de envío.</small>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Productos del carrito</strong>
                            <br>
                            <small class="text-muted">Lista de productos con cantidades y precios.</small>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Tipo de documento</strong>
                            <br>
                            <small class="text-muted">Detecta automáticamente el tipo según los productos.</small>
                        </li>
                    </ul>

                    <p class="text-muted small border-top pt-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Ideal para órdenes realizadas a través de la tienda online.
                    </p>
                </div>

                <div class="card-footer border-top">
                        @if(auth()->user()->canDocument('route-sync-api-documents'))
                        <a href="{{ route('documents.import.api') }}" class="btn btn-primary w-100">
                            Importar desde prestashop
                        </a>
                    @else
                        <button type="button" class="btn btn-primary w-100 disabled" disabled>
                            Permiso requerido
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Opción 2: Importar desde ERP (Gestión) -->
        <div class="col-md-6">
            <div class="card h-100 shadow-sm">
                <div class="card-header border-bottom py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <h6 class="mb-0 fw-bold text-dark">
                            Gestión
                        </h6>
                        <span class="badge bg-success">ERP</span>
                    </div>
                </div>

                <div class="card-body pb-0">
                    <p class="text-muted mb-3">
                        Importa pedidos desde el <strong>Gestión</strong> utilizando la serie y número de pedido.
                        Sincroniza datos de clientes y líneas del pedido.
                    </p>

                    <div class="alert alert-info alert-sm py-2 px-3 mb-3" role="alert">
                        <strong>¿Qué se importa?</strong>
                    </div>

                    <ul class="list-unstyled ms-3 mb-4">
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Datos del cliente</strong>
                            <br>
                            <small class="text-muted">Nombre, email, teléfono, DNI/CIF desde Gestión.</small>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Líneas del pedido</strong>
                            <br>
                            <small class="text-muted">Artículos con descripción, cantidad y precio.</small>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Referencia ERP</strong>
                            <br>
                            <small class="text-muted">Serie/Número como identificador único del pedido.</small>
                        </li>
                    </ul>

                    <p class="text-muted small border-top pt-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Ideal para pedidos gestionados directamente en el ERP.
                    </p>
                </div>

                <div class="card-footer border-top">
                    @if(auth()->user()->canDocument('route-sync-erp-documents'))
                        <a href="{{ route('documents.import.erp') }}" class="btn btn-primary w-100">
                            Importar desde gestion
                        </a>
                    @else
                        <button type="button" class="btn btn-primary w-100 disabled" disabled>
                            Permiso requerido
                        </button>
                    @endif
                </div>
            </div>
        </div>

    </div>

    @if(auth()->user()->canDocument('route-sync-documents'))
        <!-- Información adicional -->
        <div class="card mt-4 shadow-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h6 class="fw-bold mb-2">
                            <i class="fas fa-lightbulb text-warning me-2"></i>
                            ¿Cuándo usar cada opción?
                        </h6>
                        <p class="text-muted mb-0 ">
                            <strong>PrestaShop:</strong> Cuando la orden fue realizada en la tienda online y necesitas crear el documento de solicitud de documentación.
                        </p>
                        <p class="text-muted mb-0 ">
                            <strong>Gestión ERP:</strong> Cuando el pedido fue registrado manualmente en Gestión y necesitas vincular la documentación.
                        </p>

                    </div>
                    <div class="col-md-4 text-end">
                        <a href="{{ route('documents.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Volver a documentos
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif


@endsection

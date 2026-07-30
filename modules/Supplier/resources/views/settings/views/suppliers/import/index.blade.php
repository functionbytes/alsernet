@extends('layouts.theme')

@section('title', 'Importar proveedores')

@section('page_header')
    @include('core::components.card', ['title' => 'Proveedores'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">


        <div class="row g-3">

            <!-- Opción: Importar desde ERP -->
            <div class="col-md-12">
                <div class="card h-100 shadow-sm">
                    <div class="card-header border-bottom py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <h6 class="mb-0 fw-bold text-dark">Gestión ERP</h6>
                            <span class="badge bg-success">ERP</span>
                        </div>
                    </div>

                    <div class="card-body pb-0">
                        <p class="text-muted mb-3">
                            Importa proveedores directamente desde el <strong>Gestión ERP</strong>.
                            Los datos se sincronizan automáticamente con la base de datos local.
                        </p>

                        <div class="alert alert-info alert-sm py-2 px-3 mb-3" role="alert">
                            <strong>¿Qué se importa?</strong>
                        </div>

                        <ul class="list-unstyled ms-3 mb-4">
                            <li class="mb-2">
                                <strong>Nombre y código</strong>
                                <br>
                                <small class="text-muted">Nombre y código identificador del proveedor.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Datos de contacto</strong>
                                <br>
                                <small class="text-muted">Email, teléfono y sitio web.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Estado y CIF</strong>
                                <br>
                                <small class="text-muted">Estado activo/inactivo y número CIF/NIF.</small>
                            </li>
                        </ul>

                        <p class="text-muted small border-top pt-3">
                            <i class="fas fa-info-circle me-1"></i>
                            Los proveedores ya existentes se actualizarán; los nuevos se crearán automáticamente.
                        </p>
                    </div>

                    <div class="card-footer border-top">
                        <a href="{{ route('settings.suppliers.import.erp') }}" class="btn btn-primary w-100">
                            Importar desde erp
                        </a>
                    </div>
                </div>
            </div>

        </div>

        <div class="card mt-4 shadow-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h6 class="fw-bold mb-2">
                            <i class="fas fa-lightbulb text-warning me-2"></i>
                            Sobre la importación
                        </h6>
                        <p class="text-muted mb-0">
                            Al importar desde el ERP se sincronizarán todos los proveedores disponibles.
                            Los proveedores que ya existen en el sistema se actualizarán con los datos más recientes del ERP.
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <a href="{{ route('settings.suppliers.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Volver a proveedores
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>{{-- widget-content --}}

@endsection

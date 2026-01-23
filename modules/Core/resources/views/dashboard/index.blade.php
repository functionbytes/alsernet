@extends('layouts.theme')

@section('content')
<div class="container-fluid">
    <!-- Page Title -->
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="mb-0">Dashboard</h1>
            <p class="text-muted">Bienvenido, {{ auth()->user()->firstname }}</p>
        </div>
    </div>

    <!-- Main Dashboard Grid -->
    <div class="row">
        <!-- Welcome Card -->
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Bienvenido al Panel de Administración</h5>
                    <p class="card-text">
                        Tu rol: <strong>{{ ucfirst(str_replace('-', ' ', $userRole ?? 'Sin rol')) }}</strong>
                    </p>
                </div>
            </div>
        </div>

        <!-- Dynamic Widgets based on Role -->
        @switch($userRole)
            @case('super-admin')
            @case('admin')
            @case('managers')
                <!-- Admin Widgets -->
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-2x text-primary mb-3"></i>
                            <h5>Usuarios</h5>
                            <p class="text-muted">Gestiona usuarios</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-file fa-2x text-success mb-3"></i>
                            <h5>Documentos</h5>
                            <p class="text-muted">Gestiona documentos</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-cog fa-2x text-warning mb-3"></i>
                            <h5>Configuración</h5>
                            <p class="text-muted">Ajustes del sistema</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-chart-bar fa-2x text-info mb-3"></i>
                            <h5>Reportes</h5>
                            <p class="text-muted">Ver análisis</p>
                        </div>
                    </div>
                </div>
                @break

            @case('callcenters')
                <!-- Call Center Widgets -->
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-headset fa-2x text-primary mb-3"></i>
                            <h5>Conversaciones</h5>
                            <p class="text-muted">Gestiona conversaciones</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-ticket-alt fa-2x text-success mb-3"></i>
                            <h5>Tickets</h5>
                            <p class="text-muted">Ver tickets</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-chart-line fa-2x text-warning mb-3"></i>
                            <h5>Estadísticas</h5>
                            <p class="text-muted">Ver desempeño</p>
                        </div>
                    </div>
                </div>
                @break

            @case('warehouses')
                <!-- Warehouse Widgets -->
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-warehouse fa-2x text-primary mb-3"></i>
                            <h5>Inventario</h5>
                            <p class="text-muted">Gestiona inventario</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-boxes fa-2x text-success mb-3"></i>
                            <h5>Productos</h5>
                            <p class="text-muted">Gestiona productos</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-exchange-alt fa-2x text-warning mb-3"></i>
                            <h5>Transferencias</h5>
                            <p class="text-muted">Movimientos</p>
                        </div>
                    </div>
                </div>
                @break

            @case('shops')
                <!-- Shop Widgets -->
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-shopping-cart fa-2x text-primary mb-3"></i>
                            <h5>Ventas</h5>
                            <p class="text-muted">Ver ventas</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-cube fa-2x text-success mb-3"></i>
                            <h5>Productos</h5>
                            <p class="text-muted">Gestiona productos</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-users-cog fa-2x text-warning mb-3"></i>
                            <h5>Clientes</h5>
                            <p class="text-muted">Gestiona clientes</p>
                        </div>
                    </div>
                </div>
                @break

            @case('administratives')
                <!-- Administrative Widgets -->
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-file-invoice fa-2x text-primary mb-3"></i>
                            <h5>Facturas</h5>
                            <p class="text-muted">Gestiona facturas</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-money-bill-wave fa-2x text-success mb-3"></i>
                            <h5>Pagos</h5>
                            <p class="text-muted">Ver pagos</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-chart-pie fa-2x text-warning mb-3"></i>
                            <h5>Reportes</h5>
                            <p class="text-muted">Ver reportes</p>
                        </div>
                    </div>
                </div>
                @break

            @case('supports')
                <!-- Support Widgets -->
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-life-ring fa-2x text-primary mb-3"></i>
                            <h5>Soporte</h5>
                            <p class="text-muted">Gestiona solicitudes</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-question-circle fa-2x text-success mb-3"></i>
                            <h5>FAQ</h5>
                            <p class="text-muted">Gestiona preguntas</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-comments fa-2x text-warning mb-3"></i>
                            <h5>Comentarios</h5>
                            <p class="text-muted">Ver comentarios</p>
                        </div>
                    </div>
                </div>
                @break

            @default
                <!-- Default Widgets -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-home fa-2x text-primary mb-3"></i>
                            <h5>Panel Principal</h5>
                            <p class="text-muted">Bienvenido al sistema</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-info-circle fa-2x text-info mb-3"></i>
                            <h5>Información</h5>
                            <p class="text-muted">Obtén ayuda</p>
                        </div>
                    </div>
                </div>
        @endswitch
    </div>
</div>
@endsection

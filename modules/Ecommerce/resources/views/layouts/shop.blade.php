<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Tienda') - {{ config('app.name', 'Alsernet') }}</title>

    {{-- Bootstrap 5.3 --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    {{-- Font Awesome 6 --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    {{-- Toastr --}}
    <link rel="stylesheet" href="{{ themeAsset('libs/toastr/toastr.css') }}">

    <style>
        :root {
            --bs-primary: #90bb13;
            --bs-primary-rgb: 144, 187, 19;
            --bs-success: #13C672;
            --bs-danger: #FA896B;
            --bs-warning: #FEC90F;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f8f9fa;
        }

        .shop-navbar {
            background: #fff;
            border-bottom: 1px solid #e9ecef;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
        }

        .shop-navbar .navbar-brand {
            font-weight: 700;
            color: #1a2030;
            font-size: 1.4rem;
        }

        .shop-navbar .navbar-brand i {
            color: #90bb13;
        }

        .shop-navbar .nav-link {
            color: #495057;
            font-weight: 500;
            transition: color 0.2s;
        }

        .shop-navbar .nav-link:hover,
        .shop-navbar .nav-link.active {
            color: #90bb13;
        }

        .shop-footer {
            background: #1a2030;
            color: rgba(255,255,255,0.6);
            padding: 2.5rem 0;
            margin-top: 4rem;
            font-size: 0.875rem;
        }

        .btn-primary {
            background-color: #90bb13;
            border-color: #90bb13;
            font-weight: 600;
        }

        .btn-primary:hover {
            background-color: #7da210;
            border-color: #7da210;
        }

        .btn-outline-primary {
            color: #90bb13;
            border-color: #90bb13;
            font-weight: 600;
        }

        .btn-outline-primary:hover {
            background-color: #90bb13;
            border-color: #90bb13;
            color: #fff;
        }

        .text-primary { color: #90bb13 !important; }

        .form-control:focus, .form-select:focus {
            border-color: #90bb13;
            box-shadow: 0 0 0 0.25rem rgba(144, 187, 19, 0.15);
        }

        .breadcrumb a { color: #90bb13; }
    </style>

    @stack('styles')
</head>
<body>

    {{-- Navbar --}}
    <nav class="navbar navbar-expand-lg shop-navbar py-3">
        <div class="container">
            <a class="navbar-brand" href="{{ route('shop.index') }}">
                <i class="fas fa-store me-2"></i>{{ config('app.name', 'Tienda') }}
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#shopNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="shopNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('shop.index') ? 'active' : '' }}" href="{{ route('shop.index') }}">
                            <i class="fas fa-home me-1"></i> Inicio
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('cart.index') ? 'active' : '' }}" href="{{ route('cart.index') }}">
                            <i class="fas fa-shopping-cart me-1"></i> Carrito
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('ecommerce.wishlist.index') ? 'active' : '' }}" href="{{ route('ecommerce.wishlist.index') }}">
                            <i class="fas fa-heart me-1"></i> Favoritos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('ecommerce.compare.index') ? 'active' : '' }}" href="{{ route('ecommerce.compare.index') }}">
                            <i class="fas fa-code-compare me-1"></i> Comparar
                        </a>
                    </li>
                </ul>

                <ul class="navbar-nav mb-2 mb-lg-0">
                    @guest('ecommerce')
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('ecommerce.login') }}">
                                <i class="fas fa-sign-in-alt me-1"></i> Iniciar sesion
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('ecommerce.register') }}">
                                <i class="fas fa-user-plus me-1"></i> Registrarse
                            </a>
                        </li>
                    @else
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-1"></i> {{ auth('ecommerce')->user()->name }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i> Mi cuenta</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-box me-2"></i> Mis pedidos</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="{{ route('ecommerce.logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        <i class="fas fa-sign-out-alt me-2"></i> Cerrar sesion
                                    </a>
                                    <form id="logout-form" action="{{ route('ecommerce.logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @endguest
                </ul>
            </div>
        </div>
    </nav>

    {{-- Main Content --}}
    <main class="py-4">
        <div class="container">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('info'))
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="fas fa-info-circle me-2"></i>{{ session('info') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    {{-- Footer --}}
    <footer class="shop-footer">
        <div class="container text-center">
            <p class="mb-1">&copy; {{ date('Y') }} {{ config('app.name', 'Alsernet') }}. Todos los derechos reservados.</p>
            <p class="mb-0"><small>Tienda en linea</small></p>
        </div>
    </footer>

    {{-- Scripts --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ themeAsset('libs/jquery/dist/jquery.min.js') }}"></script>
    <script src="{{ themeAsset('libs/toastr/toastr.min.js') }}"></script>
    <script>
        $(function () {
            $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
            toastr.options = {
                closeButton: true, progressBar: true, positionClass: "toast-bottom-right",
                timeOut: 5000, extendedTimeOut: 1000,
                showEasing: "swing", hideEasing: "linear",
                showMethod: "fadeIn", hideMethod: "fadeOut"
            };
        });
    </script>

    @stack('scripts')
</body>
</html>

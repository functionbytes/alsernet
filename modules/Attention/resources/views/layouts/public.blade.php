<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title>@yield('title', 'Sistema PQRSF') - {{ config('app.name', 'Alsernet') }}</title>

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Bootstrap 5.3 --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- Font Awesome 6 --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    {{-- Google reCAPTCHA preconnect --}}
    @include('captcha::header-meta')

    <style>
        :root {
            --bs-primary: #90bb13;
            --bs-primary-rgb: 144, 187, 19;
            --bs-success: #13C672;
            --bs-danger: #FA896B;
            --bs-warning: #FEC90F;
        }

        * {
            scroll-behavior: smooth;
        }

        body {
            background: linear-gradient(135deg, #f0f4f8 0%, #e8eef3 100%);
            font-family: 'Inter', 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
        }

        .public-header {
            background: #fff;
            border-bottom: 4px solid #90bb13;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .public-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #90bb13 0%, #13C672 50%, #90bb13 100%);
        }

        .public-header .container {
            padding-top: 1.25rem;
            padding-bottom: 1.25rem;
        }

        .public-header .brand-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a2030;
            letter-spacing: -0.5px;
        }

        .public-header .brand-name i {
            color: #90bb13;
        }

        .public-header .brand-subtitle {
            font-size: 0.85rem;
            color: #6c757d;
            font-weight: 500;
            margin-top: 0.25rem;
        }

        .public-header .btn-outline-light {
            color: #90bb13;
            border-color: #90bb13;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .public-header .btn-outline-light:hover {
            background-color: #90bb13;
            border-color: #90bb13;
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(144, 187, 19, 0.3);
        }

        .public-footer {
            background: linear-gradient(135deg, #2a3042 0%, #1a2030 100%);
            color: rgba(255, 255, 255, 0.7);
            padding: 2rem 0;
            margin-top: 4rem;
            font-size: 0.875rem;
            border-top: 3px solid #90bb13;
        }

        .public-footer p:last-child {
            color: rgba(255, 255, 255, 0.5);
        }

        .btn-primary {
            background-color: #90bb13;
            border-color: #90bb13;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #7da210;
            border-color: #7da210;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(144, 187, 19, 0.3);
        }

        .btn-outline-primary {
            color: #90bb13;
            border-color: #90bb13;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-outline-primary:hover {
            background-color: #90bb13;
            border-color: #90bb13;
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(144, 187, 19, 0.3);
        }

        .form-control:focus, .form-select:focus {
            border-color: #90bb13;
            box-shadow: 0 0 0 0.25rem rgba(144, 187, 19, 0.15);
        }

        .form-check-input:checked {
            background-color: #90bb13;
            border-color: #90bb13;
        }

        .form-check-input:focus {
            border-color: #90bb13;
            box-shadow: 0 0 0 0.25rem rgba(144, 187, 19, 0.15);
        }

        a {
            color: #90bb13;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        a:hover {
            color: #7da210;
        }

        .card {
            border: none;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
            border-radius: 12px;
            background: #fff;
        }

        .main-content {
            min-height: calc(100vh - 250px);
            padding: 2.5rem 0;
        }

        /* Step progress indicator styles */
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2.5rem;
            position: relative;
        }

        .step-indicator::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e0e0e0;
            z-index: 0;
        }

        .step-item {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #fff;
            border: 2px solid #e0e0e0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.875rem;
            color: #adb5bd;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }

        .step-item.active .step-circle {
            background: #90bb13;
            border-color: #90bb13;
            color: #fff;
        }

        .step-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: #6c757d;
            margin-top: 0.5rem;
        }

        .step-item.active .step-label {
            color: #90bb13;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .public-header .brand-name {
                font-size: 1.25rem;
            }

            .public-header .brand-subtitle {
                font-size: 0.75rem;
            }

            .step-indicator {
                display: none;
            }

            .main-content {
                padding: 1.5rem 0;
            }
        }
    </style>

    @stack('styles')
</head>
<body>

    {{-- Header --}}
    <header class="public-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="brand-name">
                        <i class="fas fa-building-shield me-2"></i>{{ config('app.name', 'Alsernet') }}
                    </div>
                    <div class="brand-subtitle">Sistema de Peticiones, Quejas, Reclamos, Sugerencias y Felicitaciones</div>
                </div>
                <div class="d-none d-md-flex gap-2">
                    <a href="{{ route('pqrsf.form') }}" class="btn btn-sm btn-outline-light">
                        <i class="fas fa-file-pen me-1"></i> Radicar PQRSF
                    </a>
                    <a href="{{ route('pqrsf.tracking') }}" class="btn btn-sm btn-outline-light">
                        <i class="fas fa-magnifying-glass me-1"></i> Consultar estado
                    </a>
                </div>
            </div>
        </div>
    </header>

    {{-- Content --}}
    <main class="main-content">
        <div class="container">
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    {{-- Footer --}}
    <footer class="public-footer">
        <div class="container text-center">
            <p class="mb-1">&copy; {{ date('Y') }} {{ config('app.name', 'Alsernet') }}. Todos los derechos reservados.</p>
            <p class="mb-0">
                <small>Sistema PQRSF - Conforme a la normatividad colombiana vigente</small>
            </p>
        </div>
    </footer>

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    @stack('scripts')
</body>
</html>

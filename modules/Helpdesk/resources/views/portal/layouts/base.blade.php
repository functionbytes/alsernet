<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portal de soporte')</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f5f6fa; }
        .portal-nav {
            background: #b10100;
            box-shadow: 0 2px 8px rgba(0,0,0,.15);
        }
        .portal-nav .navbar-brand { color: #fff; font-weight: 700; }
        .portal-nav .nav-link { color: rgba(255,255,255,.85); }
        .portal-nav .nav-link:hover,
        .portal-nav .nav-link.active { color: #fff; }
        .portal-nav .dropdown-toggle::after { display: none; }
        .btn-primary-portal {
            background-color: #b10100;
            border-color: #b10100;
            color: #fff;
        }
        .btn-primary-portal:hover {
            background-color: #900000;
            border-color: #900000;
            color: #fff;
        }
        .conversation-card {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            transition: box-shadow .15s ease, border-color .15s ease;
        }
        .conversation-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,.08);
            border-color: #b10100;
        }
        .message-bubble {
            max-width: 80%;
            border-radius: 12px;
            padding: .75rem 1rem;
        }
        .message-bubble.from-customer {
            background: #b10100;
            color: #fff;
            border-bottom-right-radius: 2px;
        }
        .message-bubble.from-agent {
            background: #fff;
            border: 1px solid #e9ecef;
            border-bottom-left-radius: 2px;
        }
        .message-bubble.event-item {
            background: #f8f9fa;
            border: 1px dashed #dee2e6;
            color: #6c757d;
            font-size: .85rem;
            max-width: 100%;
            text-align: center;
            border-radius: 20px;
            padding: .4rem 1rem;
        }
        .message-thread {
            max-height: 520px;
            overflow-y: auto;
        }
        .avatar-initials {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .75rem;
            font-weight: 700;
            flex-shrink: 0;
        }
        @yield('extra-styles')
    </style>
</head>
<body>
    {{-- Navigation --}}
    <nav class="portal-nav navbar navbar-expand-md py-2 mb-4">
        <div class="container" style="max-width: 860px;">
            <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('helpdesk.portal.dashboard') }}">
                <i class="fas fa-headset"></i>
                Portal de soporte
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#portalNav" aria-label="Menu">
                <i class="fas fa-bars text-white"></i>
            </button>

            <div class="collapse navbar-collapse" id="portalNav">
                <ul class="navbar-nav ms-auto align-items-md-center gap-1">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('helpdesk.portal.dashboard') ? 'active fw-semibold' : '' }}"
                           href="{{ route('helpdesk.portal.dashboard') }}">
                            <i class="far fa-comments me-1"></i>Mis conversaciones
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('helpdesk.portal.profile*') ? 'active fw-semibold' : '' }}"
                           href="{{ route('helpdesk.portal.profile') }}">
                            <i class="far fa-user me-1"></i>Mi perfil
                        </a>
                    </li>
                    <li class="nav-item ms-md-2">
                        <form method="POST" action="{{ route('helpdesk.portal.logout') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-light">
                                <i class="fas fa-right-from-bracket me-1"></i>Salir
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container pb-5" style="max-width: 860px;">
        {{-- Flash messages --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 mb-4" role="alert">
                <i class="fas fa-circle-check flex-shrink-0"></i>
                <span>{{ session('success') }}</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 mb-4" role="alert">
                <i class="fas fa-circle-exclamation flex-shrink-0"></i>
                <span>{{ session('error') }}</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        @endif

        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>

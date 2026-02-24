<!-- resources/views/layouts/app.blade.php -->

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'MailRelay Dashboard')</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        #sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #1e3a8a 0%, #1e40af 100%);
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            width: 250px;
            transition: all 0.3s;
        }

        #sidebar.collapsed {
            margin-left: -250px;
        }

        #sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1.25rem;
            margin: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            transition: all 0.2s;
        }

        #sidebar .nav-link:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
        }

        #sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.2);
            font-weight: 500;
        }

        #sidebar .nav-link i {
            width: 20px;
            margin-right: 0.5rem;
        }

        .sidebar-brand {
            padding: 1.5rem 1.25rem;
            color: #fff;
            font-size: 1.25rem;
            font-weight: 600;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Main Content Styles */
        #content {
            margin-left: 250px;
            transition: all 0.3s;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        #content.expanded {
            margin-left: 0;
        }

        /* Top Navbar */
        .top-navbar {
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            padding: 0.75rem 1.5rem;
        }

        .main-content {
            flex: 1;
            padding: 2rem;
            background-color: #f8f9fa;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            #sidebar {
                margin-left: -250px;
            }

            #sidebar.show {
                margin-left: 0;
            }

            #content {
                margin-left: 0;
            }

            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 999;
            }

            .sidebar-overlay.show {
                display: block;
            }
        }

        .dropdown-toggle::after {
            margin-left: 0.5rem;
        }
    </style>

    @yield('head')
</head>
<body>

<!-- Sidebar Overlay for Mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<nav id="sidebar">
    <div class="sidebar-brand">
        <i class="bi bi-envelope-fill"></i> MailRelay
    </div>
    <ul class="nav flex-column pt-3">
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('mailrelay.dashboard') ? 'active' : '' }}" href="{{ route('mailrelay.dashboard') }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('mailrelay.subscribers.*') ? 'active' : '' }}" href="{{ route('mailrelay.subscribers.index') }}">
                <i class="bi bi-people-fill"></i> Subscribers
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('mailrelay.imports.*') ? 'active' : '' }}" href="{{ route('mailrelay.imports.index') }}">
                <i class="bi bi-upload"></i> Imports
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('mailrelay.campaigns.*') ? 'active' : '' }}" href="{{ route('mailrelay.campaigns.index') }}">
                <i class="bi bi-megaphone-fill"></i> Campaigns
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('mailrelay.validation.*') ? 'active' : '' }}" href="{{ route('mailrelay.validation.test') }}">
                <i class="bi bi-check-circle-fill"></i> Validation Test
            </a>
        </li>
    </ul>
</nav>

<!-- Main Content -->
<div id="content">
    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg top-navbar">
        <div class="container-fluid">
            <button class="btn btn-link text-dark" id="sidebarToggle">
                <i class="bi bi-list fs-4"></i>
            </button>

            <div class="ms-auto d-flex align-items-center">
                @auth
                    <div class="dropdown">
                        <button class="btn btn-link text-decoration-none text-dark dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle fs-5"></i>
                            <span class="ms-2">{{ Auth::user()->name }}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li>
                                <span class="dropdown-item-text">
                                    <small class="text-muted">{{ Auth::user()->email }}</small>
                                </span>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item">
                                        <i class="bi bi-box-arrow-right"></i> Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline-primary me-2">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="btn btn-primary">
                            <i class="bi bi-person-plus"></i> Register
                        </a>
                    @endif
                @endauth
            </div>
        </div>
    </nav>

    <!-- Main Content Area -->
    <div class="main-content">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @yield('content')
    </div>

    <!-- Footer -->
    <footer class="bg-white border-top py-3 mt-auto">
        <div class="container-fluid px-4">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">&copy; {{ date('Y') }} MailRelay. All rights reserved.</small>
                <small class="text-muted">Version 1.0.0</small>
            </div>
        </div>
    </footer>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Sidebar Toggle Script -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const content = document.getElementById('content');
        const overlay = document.getElementById('sidebarOverlay');

        sidebarToggle.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');
                content.classList.toggle('expanded');
            }
        });

        overlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            }
        });
    });
</script>

@yield('scripts')
</body>
</html>

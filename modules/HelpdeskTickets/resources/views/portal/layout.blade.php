<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Support Portal</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .navbar-brand { font-weight: 600; }
        .ticket-message { border-left: 4px solid #dee2e6; padding-left: 1rem; margin-bottom: 1rem; }
        .ticket-message.from-customer { border-color: #b10100; }
        .ticket-message.from-agent { border-color: #0d6efd; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="{{ route('portal.index') }}">
                <i class="fas fa-headset me-2"></i>Support Portal
            </a>
            <div class="ms-auto d-flex align-items-center gap-2">
                @if (session('portal_customer_id'))
                    <span class="text-light small me-2">
                        <i class="fas fa-user me-1"></i>
                        {{ \Modules\Helpdesk\Models\Customer::find(session('portal_customer_id'))?->name }}
                    </span>
                    <a href="{{ route('portal.tickets') }}" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-ticket-alt me-1"></i>My Tickets
                    </a>
                    <a href="{{ route('portal.account') }}" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="fas fa-user"></i> Account
                    </a>
                    <form action="{{ route('portal.logout') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-sign-out-alt me-1"></i>Logout
                        </button>
                    </form>
                @else
                    <a href="{{ route('portal.login') }}" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-sign-in-alt me-1"></i>Login
                    </a>
                @endif
            </div>
        </div>
    </nav>

    <div class="container pb-5">
        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

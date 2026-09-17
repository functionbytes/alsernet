<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Support Portal</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('modules/helpdesktickets/css/portal.css') }}">
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
                        {{-- De la sesión, no de la base de datos: esto se pinta en
                             todas las páginas del portal y hacía un Customer::find()
                             dentro de la vista en cada carga. El fallback cubre las
                             sesiones abiertas antes de guardar el nombre. --}}
                        {{ session('portal_customer_name') ?: \Modules\Helpdesk\Models\Customer::find(session('portal_customer_id'))?->name }}
                    </span>
                    <a href="{{ route('portal.tickets') }}" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-ticket-alt me-1"></i>Mis tickets
                    </a>
                    @if(\Illuminate\Support\Facades\Route::has('helpdesk.portal.conversations'))
                    <a href="{{ route('helpdesk.portal.conversations') }}" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-comments me-1"></i>Conversaciones
                    </a>
                    @endif
                    <a href="{{ route('portal.account') }}" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="fas fa-user"></i> Cuenta
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

    {{-- jQuery: lo asume el JS propio de las pantallas del portal (p.ej.
         portal-ticket-create-form.js), pero nunca se cargaba aquí — bug
         preexistente, no introducido en esta limpieza. --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    {{-- Sin este stack, cualquier @push('scripts') de las vistas del portal
         (p.ej. portal/tickets/create.blade.php) se descartaba en silencio:
         bug preexistente que dejaba la deflexión de KB del formulario de
         "nuevo ticket" sin ejecutarse nunca. --}}
    @stack('scripts')
</body>
</html>

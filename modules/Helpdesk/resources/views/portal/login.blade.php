<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de cliente — Acceso</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background: #f5f6fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .portal-card {
            width: 100%;
            max-width: 440px;
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 24px rgba(0, 0, 0, .08);
        }
        .portal-brand-icon {
            width: 64px;
            height: 64px;
            background: #b10100;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }
        .btn-primary {
            background-color: #b10100;
            border-color: #b10100;
        }
        .btn-primary:hover {
            background-color: #900000;
            border-color: #900000;
        }
    </style>
</head>
<body>
    <div class="portal-card card mx-3">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <div class="portal-brand-icon">
                    <i class="fas fa-headset text-white fa-xl"></i>
                </div>
                <h4 class="fw-bold mb-1">Portal de soporte</h4>
                <p class="text-muted small mb-0">Ingresa tu email y te enviaremos un enlace de acceso</p>
            </div>

            @if (session('success'))
                <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
                    <i class="fas fa-circle-check"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error') || $errors->any())
                <div class="alert alert-danger d-flex align-items-center gap-2 mb-4">
                    <i class="fas fa-circle-exclamation"></i>
                    <span>{{ session('error') ?? $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('helpdesk.portal.send-link') }}" novalidate>
                @csrf
                <div class="mb-4">
                    <label for="email" class="form-label fw-semibold">Correo electrónico</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white">
                            <i class="far fa-envelope text-muted"></i>
                        </span>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email') }}"
                            placeholder="tucorreo@empresa.com"
                            required
                            autofocus
                        >
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2">
                    <i class="far fa-paper-plane me-2"></i>Enviar enlace de acceso
                </button>
            </form>
        </div>
    </div>
</body>
</html>

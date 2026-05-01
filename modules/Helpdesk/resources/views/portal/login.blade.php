<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de cliente</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background:#f7f7fa; min-height:100vh; display:flex; align-items:center; justify-content:center; }
        .card-portal { max-width:420px; border-radius:14px; box-shadow:0 8px 24px rgba(0,0,0,.08); }
    </style>
</head>
<body>
    <div class="card card-portal mx-3">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <i class="fas fa-circle-user text-primary" style="font-size:3rem"></i>
                <h3 class="mt-3 mb-1">Portal de cliente</h3>
                <p class="text-muted mb-0">Te enviaremos un enlace de acceso a tu correo</p>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error') || $errors->any())
                <div class="alert alert-danger">{{ session('error') ?? $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('helpdesk.portal.send-link') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Tu email</label>
                    <input type="email" name="email" class="form-control" required autofocus placeholder="tucorreo@empresa.com">
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="far fa-paper-plane me-2"></i>Enviar enlace
                </button>
            </form>
        </div>
    </div>
</body>
</html>

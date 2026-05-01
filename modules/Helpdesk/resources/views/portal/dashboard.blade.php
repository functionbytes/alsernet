<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis conversaciones</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>body { background:#f7f7fa; }</style>
</head>
<body class="py-4">
    <div class="container" style="max-width:780px">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3><i class="far fa-comments text-primary"></i> Mis conversaciones</h3>
            <form method="POST" action="{{ route('helpdesk.portal.logout') }}">
                @csrf
                <button class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </button>
            </form>
        </div>

        <div class="bg-white border rounded p-3">
            @forelse($conversations ?? [] as $conv)
                <a href="{{ route('helpdesk.portal.conversations.show', $conv->id) }}"
                   class="d-flex justify-content-between align-items-center py-3 border-bottom text-decoration-none text-dark">
                    <div>
                        <strong>{{ $conv->subject ?? 'Conversación #'.$conv->id }}</strong>
                        <div class="text-muted small">{{ $conv->channel }} · {{ $conv->last_message_at?->diffForHumans() }}</div>
                    </div>
                    <span class="badge bg-{{ $conv->status?->is_open ? 'success' : 'secondary' }}">
                        {{ $conv->status?->name }}
                    </span>
                </a>
            @empty
                <div class="text-center text-muted py-5">
                    <i class="far fa-folder-open" style="font-size:2.5rem"></i>
                    <p class="mt-3 mb-0">No tienes conversaciones aún</p>
                </div>
            @endforelse
        </div>
    </div>
</body>
</html>

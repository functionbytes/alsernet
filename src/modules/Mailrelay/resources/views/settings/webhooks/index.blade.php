@extends('layouts.theme')

@section('title', 'Webhooks de Mailrelay')

@section('content')
<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Webhooks de Mailrelay</h5>
            <a href="{{ route('settings.mailrelay.webhooks.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i> Nuevo Webhook
            </a>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if($webhooks->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-plug fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No hay webhooks configurados</p>
                    <a href="{{ route('settings.mailrelay.webhooks.create') }}" class="btn btn-outline-primary">
                        <i class="fas fa-plus me-1"></i> Crear primer webhook
                    </a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>URL</th>
                                <th>Evento</th>
                                <th>Estado</th>
                                <th>Última llamada</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($webhooks as $webhook)
                                <tr>
                                    <td>
                                        <strong>{{ $webhook->name }}</strong>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $webhook->url }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $webhook->event_type_formatted }}</span>
                                    </td>
                                    <td>
                                        @if($webhook->active)
                                            @if($webhook->failed_at)
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-exclamation-circle"></i> Fallido
                                                </span>
                                            @else
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check-circle"></i> Activo
                                                </span>
                                            @endif
                                        @else
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-pause-circle"></i> Inactivo
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($webhook->last_called_at)
                                            <small class="text-muted">
                                                {{ $webhook->last_called_at->diffForHumans() }}
                                            </small>
                                        @else
                                            <small class="text-muted">Nunca</small>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline-primary"
                                                    onclick="testWebhook({{ $webhook->id }})"
                                                    title="Probar webhook">
                                                <i class="fas fa-play"></i>
                                            </button>
                                            <a href="{{ route('settings.mailrelay.webhooks.edit', $webhook->id) }}"
                                               class="btn btn-outline-secondary"
                                               title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('settings.mailrelay.webhooks.destroy', $webhook->id) }}"
                                                  method="POST"
                                                  class="d-inline"
                                                  class="needs-confirm" data-confirm-msg="¿Eliminar este webhook?">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
function testWebhook(id) {
    if (!confirm('¿Probar este webhook?')) return;

    fetch(`/settings/mailrelay/webhooks/${id}/test`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✓ Webhook probado correctamente\nEstado: ' + data.status);
        } else {
            alert('✗ Error al probar webhook\n' + data.message);
        }
    })
    .catch(error => {
        alert('✗ Error: ' + error.message);
    });
}
</script>
@endpush
@endsection

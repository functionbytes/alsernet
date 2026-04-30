@extends('layouts.theme')

@section('title', 'Webhook Log Detail')

@section('content')
<div class="container-fluid">
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-semibold mb-2">Webhook Log #{{ $log->id }}</h4>
                    <p class="text-muted mb-0">{{ $log->created_at->format('d/m/Y H:i:s') }}</p>
                </div>
                <div>
                    <a href="{{ route('settings.chat.channels.whatsapps.logs.index', $whatsapp) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Volver a logs
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Payload -->
            <div class="card mb-4">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-code me-2"></i> Payload JSON</h5>
                </div>
                <div class="card-body">
                    <pre class="bg-light p-3 rounded" style="max-height: 600px; overflow-y: auto;"><code>{{ json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Info Card -->
            <div class="card mb-4">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-info-circle me-2"></i> Información</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="fw-bold text-muted">Tipo de evento</label>
                        <div>
                            <span class="badge bg-primary">{{ $log->event_type ?? 'N/A' }}</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="fw-bold text-muted">Número de teléfono</label>
                        <div>
                            <code>{{ $log->phone_number ?? 'N/A' }}</code>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="fw-bold text-muted">Estado</label>
                        <div>
                            @if ($log->error)
                            <span class="badge bg-danger">Error</span>
                            @elseif (!$log->processed)
                            <span class="badge bg-warning">No procesado</span>
                            @else
                            <span class="badge bg-success">Procesado</span>
                            @endif
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="fw-bold text-muted">Fecha y hora</label>
                        <div>
                            {{ $log->created_at->format('d/m/Y H:i:s') }}
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="fw-bold text-muted">Dirección IP</label>
                        <div>
                            <code>{{ $log->ip_address ?? 'N/A' }}</code>
                        </div>
                    </div>

                    @if ($log->error)
                    <div class="mb-3">
                        <label class="fw-bold text-muted">Error</label>
                        <div class="alert alert-danger">
                            <small>{{ $log->error }}</small>
                        </div>
                    </div>
                    @endif

                    <hr class="my-3">

                    <button type="button" class="btn btn-sm btn-outline-primary w-100 mb-2"
                            onclick="copyPayload()">
                        <i class="fas fa-clipboard me-1"></i> Copiar JSON
                    </button>

                    <button type="button" class="btn btn-sm btn-outline-danger w-100"
                            onclick="deleteLog()">
                        <i class="fas fa-trash me-1"></i> Eliminar
                    </button>

                    <form id="delete-form" method="POST"
                          action="{{ route('settings.chat.channels.whatsapps.logs.destroy', [$whatsapp, $log]) }}"
                          class="d-none">
                        @csrf
                        @method('DELETE')
                    </form>
                </div>
            </div>

            <!-- Payload Size -->
            <div class="card">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-chart-bar me-2"></i> Estadísticas</h5>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <label class="text-muted">Tamaño del payload</label>
                        <div>
                            <strong>{{ number_format(strlen(json_encode($log->payload))) }} bytes</strong>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="text-muted">Total de eventos</label>
                        <div>
                            <strong>{{ count($log->payload['entry'] ?? []) }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function copyPayload() {
    const payload = @json(json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    navigator.clipboard.writeText(payload).then(function() {
        toastr.success('JSON copiado al portapapeles');
    });
}

function deleteLog() {
    if (confirm('¿Estás seguro de que deseas eliminar este log?')) {
        document.getElementById('delete-form').submit();
    }
}
</script>
@endpush

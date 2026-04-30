@extends('layouts.theme')

@section('title', 'WhatsApp Webhook Logs')

@section('content')
<div class="container-fluid">
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-semibold mb-2">Webhook Logs - {{ $whatsapp->inbox->name }}</h4>
                    <p class="text-muted mb-0">Todos los webhooks recibidos desde WhatsApp</p>
                </div>
                <div>
                    <a href="{{ route('settings.chat.channels.whatsapps.show', $whatsapp) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    @include('core::components.alerts')

    <div class="card mb-4">
        <div class="card-header border-bottom p-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><i class="fas fa-list me-2"></i> Logs ({{ $logs->total() }} total)</h5>
            <div>
                <button class="btn btn-sm btn-info me-2" onclick="refreshLogs()">
                    <i class="fas fa-sync me-1"></i> Actualizar
                </button>
                @if ($logs->total() > 0)
                <button class="btn btn-sm btn-danger" onclick="clearAllLogs()">
                    <i class="fas fa-trash me-1"></i> Limpiar todos
                </button>
                @endif
            </div>
        </div>

        <div class="card-body p-0">
            <!-- Filters -->
            <div class="p-3 border-bottom bg-light">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Estado</label>
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            <option value="unprocessed" @selected(request('status') === 'unprocessed')>No procesados</option>
                            <option value="error" @selected(request('status') === 'error')>Con errores</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tipo de evento</label>
                        <select name="event_type" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            @foreach ($eventTypes as $type)
                            <option value="{{ $type }}" @selected(request('event_type') === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <button type="reset" class="btn btn-sm btn-outline-secondary w-100" onclick="location.href='{{ route('settings.chat.channels.whatsapps.logs.index', $whatsapp) }}'">
                            <i class="fas fa-times me-1"></i> Limpiar filtros
                        </button>
                    </div>
                </form>
            </div>

            @if ($logs->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="15%">Hora</th>
                            <th width="15%">Tipo de evento</th>
                            <th width="15%">Teléfono</th>
                            <th width="20%">Estado</th>
                            <th width="20%">Errores</th>
                            <th width="15%">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="logs-tbody">
                        @foreach ($logs as $log)
                        <tr>
                            <td>
                                <small class="text-muted">{{ $log->created_at->format('d/m/Y H:i:s') }}</small>
                            </td>
                            <td>
                                <span class="badge bg-primary">{{ $log->event_type ?? 'N/A' }}</span>
                            </td>
                            <td>
                                <code>{{ $log->phone_number ?? 'N/A' }}</code>
                            </td>
                            <td>
                                @if ($log->error)
                                <span class="badge bg-danger">Error</span>
                                @elseif (!$log->processed)
                                <span class="badge bg-warning">No procesado</span>
                                @else
                                <span class="badge bg-success">Procesado</span>
                                @endif
                            </td>
                            <td>
                                @if ($log->error)
                                <small class="text-danger">{{ Str::limit($log->error, 50) }}</small>
                                @else
                                <small class="text-success">-</small>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('settings.chat.channels.whatsapps.logs.show', [$whatsapp, $log]) }}"
                                   class="btn btn-xs btn-info" title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button type="button" class="btn btn-xs btn-danger" title="Eliminar"
                                        onclick="deleteLog({{ $log->id }})">
                                    <i class="fas fa-trash"></i>
                                </button>

                                <form id="delete-log-{{ $log->id }}" method="POST"
                                      action="{{ route('settings.chat.channels.whatsapps.logs.destroy', [$whatsapp, $log]) }}"
                                      class="d-none">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-3 border-top">
                {{ $logs->appends(request()->query())->links() }}
            </div>
            @else
            <div class="p-5 text-center">
                <i class="fas fa-inbox text-muted" style="font-size: 3em;"></i>
                <p class="mt-3 text-muted">No hay webhooks aún</p>
            </div>
            @endif
        </div>
    </div>
</div>

<form id="clear-logs-form" method="POST" action="{{ route('settings.chat.channels.whatsapps.logs.clear', $whatsapp) }}" class="d-none">
    @csrf
</form>

@endsection

@push('scripts')
<script>
function deleteLog(logId) {
    if (confirm('¿Estás seguro de que deseas eliminar este log?')) {
        document.getElementById('delete-log-' + logId).submit();
    }
}

function clearAllLogs() {
    if (confirm('¿Estás seguro de que deseas eliminar TODOS los logs? Esta acción no se puede deshacer.')) {
        document.getElementById('clear-logs-form').submit();
    }
}

function refreshLogs() {
    const status = new URLSearchParams(location.search).get('status') || '';
    const eventType = new URLSearchParams(location.search).get('event_type') || '';

    $.ajax({
        url: '{{ route('settings.chat.channels.whatsapps.logs.list', $whatsapp) }}',
        type: 'GET',
        data: {
            status: status,
            event_type: eventType
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                toastr.success('Actualizado: ' + response.logs.length + ' logs cargados');
                location.reload();
            }
        },
        error: function() {
            toastr.error('Error al actualizar los logs');
        }
    });
}

// Auto-refresh every 10 seconds
setInterval(function() {
    $.ajax({
        url: '{{ route('settings.chat.channels.whatsapps.logs.list', $whatsapp) }}',
        type: 'GET',
        data: {
            status: new URLSearchParams(location.search).get('status') || '',
            event_type: new URLSearchParams(location.search).get('event_type') || ''
        },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.logs.length > 0) {
                // Update badge or do nothing if using auto-refresh
            }
        }
    });
}, 10000);
</script>
@endpush

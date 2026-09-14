@php
    $statusMap = [
        'completed' => ['label' => 'Completado',  'color' => 'success'],
        'running'   => ['label' => 'En progreso',  'color' => 'warning'],
        'failed'    => ['label' => 'Fallido',      'color' => 'danger'],
        'cancelled' => ['label' => 'Cancelado',    'color' => 'secondary'],
        'pending'   => ['label' => 'Pendiente',    'color' => 'info'],
    ];
    $typeColors = ['model' => 'primary', 'provider' => 'success', 'product' => 'info', 'category' => 'warning'];
    $typeLabels = ['model' => 'Modelos', 'provider' => 'Proveedores', 'product' => 'Productos', 'category' => 'Categorías'];
@endphp

{{-- Bulk action bar --}}
<div id="bulk-bar" class="d-none mb-3 p-2 bg-light border rounded d-flex align-items-center gap-2 flex-wrap">
    <span class="small fw-semibold text-muted me-1">
        <span id="bulk-count">0</span> seleccionados
    </span>
    <button type="button" class="btn btn-sm btn-outline-primary" id="bulk-btn-retry">
        <i class="fas fa-rotate-right me-1"></i>Reintentar
    </button>
    <button type="button" class="btn btn-sm btn-outline-secondary" id="bulk-btn-cancel">
        <i class="fas fa-stop me-1"></i>Cancelar
    </button>
    <button type="button" class="btn btn-sm btn-outline-danger" id="bulk-btn-delete">
        <i class="fas fa-trash me-1"></i>Eliminar
    </button>
    <button type="button" class="btn btn-sm btn-link text-muted ms-auto" id="bulk-btn-clear">
        Limpiar selección
    </button>
</div>

<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th style="width:36px;">
                    <input type="checkbox" class="form-check-input" id="check-all" title="Seleccionar todo">
                </th>
                <th>Nombre / ID</th>
                <th class="text-center" style="width:110px;">Tipo</th>
                <th class="text-center" style="width:110px;">Estado</th>
                <th class="text-center" style="width:140px;">Items</th>
                <th style="width:120px;">Iniciado</th>
                <th style="width:90px;">Duración</th>
                <th class="text-center" style="width:48px;"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($batches as $batch)
                @php
                    $sm       = $statusMap[$batch->status] ?? ['label' => ucfirst($batch->status), 'color' => 'secondary'];
                    $typeColor = $typeColors[$batch->sync_type] ?? 'secondary';
                    $typeLabel = $typeLabels[$batch->sync_type] ?? $batch->sync_type;

                    $duration = null;
                    if ($batch->started_at && $batch->completed_at) {
                        $secs = $batch->started_at->diffInSeconds($batch->completed_at);
                        $duration = $secs >= 60 ? gmdate('i\m s\s', $secs) : "{$secs}s";
                    } elseif ($batch->started_at && $batch->status === 'running') {
                        $duration = 'En curso';
                    }
                @endphp
                <tr>
                    <td>
                        <input type="checkbox" class="form-check-input batch-check" value="{{ $batch->id }}">
                    </td>
                    <td>
                        <span class="fw-semibold small">{{ $batch->batch_name }}</span>
                        <small class="d-block text-muted">#{{ $batch->id }}</small>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-{{ $typeColor }}-subtle text-{{ $typeColor }}">{{ $typeLabel }}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-{{ $sm['color'] }}-subtle text-{{ $sm['color'] }}">{{ $sm['label'] }}</span>
                    </td>
                    <td class="text-center small">
                        <span class="text-success fw-semibold">{{ $batch->processed_items }}</span>
                        @if($batch->failed_items > 0)
                            <span class="text-muted mx-1">/</span>
                            <span class="text-danger fw-semibold">{{ $batch->failed_items }} err</span>
                        @endif
                        @if($batch->total_items)
                            @if($batch->exceeds_estimated_total)
                                <span class="text-warning d-block" style="font-size:.7rem;" title="El total estimado ({{ $batch->total_items }}) quedó desactualizado">de ~{{ $batch->total_items }} ⚠</span>
                            @else
                                <span class="text-muted d-block" style="font-size:.7rem;">de {{ $batch->total_items }}</span>
                            @endif
                        @endif
                    </td>
                    <td class="small text-muted text-nowrap">{{ $batch->created_at->format('d/m H:i') }}</td>
                    <td class="small text-muted text-nowrap">{{ $duration ?? '—' }}</td>
                    <td class="text-center">
                        <a href="{{ route('settings.suppliers.sync.show', $batch->id) }}"
                           class="text-muted" title="Ver detalle">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const routes = @json($routes ?? []);

    function selectedIds() {
        return [...document.querySelectorAll('.batch-check:checked')].map(el => parseInt(el.value));
    }

    function updateBulkBar() {
        const ids = selectedIds();
        const bar = document.getElementById('bulk-bar');
        document.getElementById('bulk-count').textContent = ids.length;
        if (ids.length > 0) {
            bar.classList.remove('d-none');
            bar.classList.add('d-flex');
        } else {
            bar.classList.add('d-none');
            bar.classList.remove('d-flex');
        }
    }

    document.getElementById('check-all').addEventListener('change', function () {
        document.querySelectorAll('.batch-check').forEach(cb => cb.checked = this.checked);
        updateBulkBar();
    });

    document.querySelectorAll('.batch-check').forEach(cb => {
        cb.addEventListener('change', function () {
            const all = document.querySelectorAll('.batch-check');
            document.getElementById('check-all').checked = [...all].every(c => c.checked);
            updateBulkBar();
        });
    });

    document.getElementById('bulk-btn-clear').addEventListener('click', function () {
        document.querySelectorAll('.batch-check, #check-all').forEach(cb => cb.checked = false);
        updateBulkBar();
    });

    function bulkRequest(url, ids, successMsg, method = 'POST') {
        fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({ ids }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                toastr.success(data.message || successMsg);
                setTimeout(() => location.reload(), 800);
            } else {
                toastr.error(data.message || 'Error');
            }
        })
        .catch(e => toastr.error('Error: ' + e.message));
    }

    document.getElementById('bulk-btn-delete').addEventListener('click', function () {
        const ids = selectedIds();
        if (!ids.length) return;
        if (!confirm('¿Eliminar ' + ids.length + ' ejecución(es)?')) return;
        bulkRequest(routes.bulk_delete, ids, 'Eliminadas correctamente', 'DELETE');
    });

    document.getElementById('bulk-btn-retry').addEventListener('click', function () {
        const ids = selectedIds();
        if (!ids.length) return;
        bulkRequest(routes.bulk_retry, ids, 'Reintentos iniciados');
    });

    document.getElementById('bulk-btn-cancel').addEventListener('click', function () {
        const ids = selectedIds();
        if (!ids.length) return;
        bulkRequest(routes.bulk_cancel, ids, 'Canceladas correctamente');
    });
})();
</script>

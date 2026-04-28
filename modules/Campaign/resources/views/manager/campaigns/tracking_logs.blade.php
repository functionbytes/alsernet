@extends('theme::layouts.manager')

@section('title', 'Log de envíos')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="mb-0">Log de envíos</h2>
            <p class="text-muted mb-0">{{ $campaign->name }}</p>
        </div>
        <a href="{{ route('manager.campaigns.show', $campaign->uid) }}" class="btn btn-outline-secondary">← Volver</a>
    </div>

    <form method="get" class="mb-3">
        <select name="status" class="form-select" style="max-width:240px;display:inline-block" onchange="this.form.submit()">
            <option value="">Todos los estados</option>
            @foreach (['sent' => 'Enviado', 'failed' => 'Fallido', 'bounced' => 'Bounce', 'feedback' => 'Feedback', 'skipped' => 'Saltado'] as $k => $label)
                <option value="{{ $k }}" @selected(request('status') === $k)>{{ $label }}</option>
            @endforeach
        </select>
    </form>

    <table class="table table-sm">
        <thead><tr>
            <th>Email</th><th>Estado</th><th>Servidor</th><th>Message-Id</th><th>Fecha</th><th>Error</th>
        </tr></thead>
        <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td>{{ $log->email }}</td>
                    <td><span class="badge bg-{{ $log->status === 'sent' ? 'success' : ($log->status === 'bounced' ? 'warning' : ($log->status === 'failed' ? 'danger' : 'secondary')) }}">{{ $log->status }}</span></td>
                    <td>{{ optional($log->sendingServer)->name ?: '—' }}</td>
                    <td><code class="small">{{ Str::limit($log->message_id, 30) }}</code></td>
                    <td>{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                    <td class="text-danger small">{{ Str::limit($log->error, 80) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">Sin envíos registrados.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $logs->links() }}
</div>
@endsection

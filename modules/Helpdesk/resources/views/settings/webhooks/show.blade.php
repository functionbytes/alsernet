@extends('layouts.theme')

@section('title', 'Historial del webhook')

@section('page_header')
    @include('core::components.card', ['title' => 'Historial del webhook'])
@endsection

@section('content')
    @include('core::components.alerts')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-1 fw-bold">{{ $webhook->name }}</h5>
            <code>{{ $webhook->url }}</code>
        </div>
        <a href="{{ route('settings.helpdesk.webhooks.index') }}" class="btn btn-outline-secondary">Volver</a>
    </div>

    <div class="card">
        <div class="card-header border-bottom">
            <h6 class="mb-0 fw-bold">Últimas entregas</h6>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Evento</th>
                        <th>HTTP</th>
                        <th>Duración</th>
                        <th class="text-end">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deliveries as $delivery)
                        <tr>
                            <td><small>{{ $delivery->created_at?->format('d/m/Y H:i:s') }}</small></td>
                            <td><code>{{ $delivery->event }}</code></td>
                            <td>
                                <span class="badge bg-{{ $delivery->response_status >= 200 && $delivery->response_status < 300 ? 'success' : 'danger' }}">
                                    {{ $delivery->response_status ?? 'Error' }}
                                </span>
                            </td>
                            <td>{{ $delivery->duration_ms !== null ? $delivery->duration_ms.' ms' : '—' }}</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('settings.helpdesk.webhooks.deliveries.replay', [$webhook, $delivery]) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Reintentar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Todavía no hay entregas registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($deliveries->hasPages())
            <div class="card-footer">{{ $deliveries->links() }}</div>
        @endif
    </div>
@endsection

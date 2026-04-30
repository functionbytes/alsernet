@extends('layouts.theme')

@section('title', 'Logs de webhooks')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Logs de webhooks'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Logs de webhooks</h5>
                        <p class="small mb-0 text-muted">Historial de eventos enviados a sistemas externos</p>
                    </div>
                </div>
                <form method="GET" class="row g-2 mt-3">
                    <div class="col-md-3">
                        <select name="event" class="form-select form-select-sm">
                            <option value="">Todos los eventos</option>
                            <option value="order.created" @selected(request('event') === 'order.created')>order.created</option>
                            <option value="order.paid" @selected(request('event') === 'order.paid')>order.paid</option>
                            <option value="order.shipped" @selected(request('event') === 'order.shipped')>order.shipped</option>
                            <option value="order.completed" @selected(request('event') === 'order.completed')>order.completed</option>
                            <option value="order.cancelled" @selected(request('event') === 'order.cancelled')>order.cancelled</option>
                            <option value="order.returned" @selected(request('event') === 'order.returned')>order.returned</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select form-select-sm">
                            <option value="">Todos los estados</option>
                            <option value="success" @selected(request('status') === 'success')>Exitosos</option>
                            <option value="failed" @selected(request('status') === 'failed')>Fallidos</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-primary w-100">Filtrar</button>
                    </div>
                </form>
            </div>

            <div class="card-body">
                @if($logs->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Evento</th>
                                    <th>URL</th>
                                    <th>Estado</th>
                                    <th>Status HTTP</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($logs as $log)
                                    <tr>
                                        <td class="small">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                                        <td><code class="small">{{ $log->event }}</code></td>
                                        <td class="small text-muted text-truncate" style="max-width: 250px;">{{ $log->url }}</td>
                                        <td>
                                            @if($log->success)
                                                <span class="badge bg-success">Enviado</span>
                                            @else
                                                <span class="badge bg-danger">Fallido</span>
                                            @endif
                                        </td>
                                        <td class="small">{{ $log->response_status ?? '—' }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('ecommerce.webhook-logs.show', $log) }}" class="btn btn-sm btn-outline-secondary">
                                                Ver detalle
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">{{ $logs->links() }}</div>
                @else
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-satellite-dish fa-3x mb-3 opacity-25"></i>
                        <p class="mb-0">No hay logs de webhooks registrados.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

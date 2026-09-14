@extends('layouts.theme')

@push('css')
    <link rel="stylesheet" href="{{ asset('modules/helpdesktickets/css/helpdesktickets-ui.css') }}?v={{ @filemtime(public_path('modules/helpdesktickets/css/helpdesktickets-ui.css')) }}">
@endpush

@section('title', 'Cuarentena de correo')

@section('page_header')
    @include('core::components.card', ['title' => 'Cuarentena de correo'])
@endsection

@section('content')
<div class="widget-content searchable-container list">
    @include('core::components.alerts')

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="fw-bold mb-1">Cuarentena de correo</h5>
                    <small class="text-muted">
                        Correos retenidos por el clasificador de spam. Nada se descarta:
                        lo que aquí se libera se convierte en ticket normalmente.
                    </small>
                </div>
                <div class="btn-group">
                    <a href="{{ route('manager.helpdesk.settings.quarantine.index', ['status' => 'pending']) }}"
                        class="btn btn-sm {{ $status === 'pending' ? 'btn-primary' : 'btn-light' }}">
                        Pendientes ({{ $pendingCount }})
                    </a>
                    <a href="{{ route('manager.helpdesk.settings.quarantine.index', ['status' => 'all']) }}"
                        class="btn btn-sm {{ $status === 'all' ? 'btn-primary' : 'btn-light' }}">Todos</a>
                </div>
            </div>

            @if($items->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-shield-halved fs-1 text-muted mb-3 d-block"></i>
                    <p class="text-muted mb-0">No hay correos retenidos.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Remitente</th>
                                <th>Asunto</th>
                                <th class="text-nowrap">Certeza</th>
                                <th>Motivo</th>
                                <th class="text-nowrap">Recibido</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $item)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $item->from_name ?: $item->from_email }}</div>
                                        @if($item->from_name)
                                            <small class="text-muted">{{ $item->from_email }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <div>{{ Str::limit($item->subject ?: '(sin asunto)', 70) }}</div>
                                        <small class="text-muted">{{ Str::limit(strip_tags($item->body_text ?: $item->body_html), 110) }}</small>
                                    </td>
                                    <td class="text-nowrap">{{ round($item->spam_score * 100) }}%</td>
                                    <td><small class="text-muted">{{ Str::limit($item->reason, 80) }}</small></td>
                                    <td class="text-nowrap"><small>{{ $item->created_at?->format('d/m/Y H:i') }}</small></td>
                                    <td class="text-end">
                                        @if($item->status === \Modules\HelpdeskTickets\Models\TicketQuarantine::STATUS_PENDING)
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <form method="POST" action="{{ route('manager.helpdesk.settings.quarantine.release', $item) }}">
                                                            @csrf
                                                            <button type="submit" class="dropdown-item">No es spam, crear ticket</button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <form method="POST" action="{{ route('manager.helpdesk.settings.quarantine.confirm', $item) }}">
                                                            @csrf
                                                            <button type="submit" class="dropdown-item">Es spam, bloquear remitente</button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        @elseif($item->status === \Modules\HelpdeskTickets\Models\TicketQuarantine::STATUS_RELEASED)
                                            <span class="badge bg-success-subtle text-success">Liberado</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Spam</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{ $items->links() }}
            @endif
        </div>
    </div>
</div>
@endsection

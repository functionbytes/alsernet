@extends('layouts.theme')

@section('title', 'Perfil de cliente')

@section('page_header')
    @include('core::components.card', ['title' => 'Perfil de cliente'])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="row g-3">

        {{-- Left: profile --}}
        <div class="col-12 col-lg-4">

            <div class="card mb-3">
                <div class="card-body text-center pt-4">
                    <div class="rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center mx-auto mb-3"
                         style="width:64px;height:64px">
                        <span class="fw-bold text-primary fs-4">{{ strtoupper(substr($customer->first_name ?: $customer->email, 0, 1)) }}</span>
                    </div>
                    <h5 class="fw-bold mb-1">{{ trim($customer->first_name . ' ' . $customer->last_name) ?: '(Sin nombre)' }}</h5>
                    <p class="text-muted small mb-2">{{ $customer->email }}</p>

                    @if($customer->status === 'subscribed')
                        <span class="badge bg-success-subtle text-success">Suscrito</span>
                    @elseif($customer->status === 'unsubscribed')
                        <span class="badge bg-secondary-subtle text-secondary">Dado de baja</span>
                    @else
                        <span class="badge bg-danger-subtle text-danger">{{ $customer->status }}</span>
                    @endif
                </div>
                <div class="card-body border-top">
                    <dl class="row small mb-0">
                        <dt class="col-5 text-muted">País</dt>
                        <dd class="col-7">{{ $customer->country ?: '—' }}</dd>
                        <dt class="col-5 text-muted">Teléfono</dt>
                        <dd class="col-7">{{ $customer->phone ?: '—' }}</dd>
                        <dt class="col-5 text-muted">Tienda</dt>
                        <dd class="col-7">{{ $customer->store?->name ?? '—' }}</dd>
                        <dt class="col-5 text-muted">RFM</dt>
                        <dd class="col-7">{{ $customer->rfm_score ? ucfirst($customer->rfm_score) : '—' }}</dd>
                        <dt class="col-5 text-muted">Total gastado</dt>
                        <dd class="col-7 fw-semibold">{{ $customer->total_spent ? number_format($customer->total_spent, 2) . ' €' : '—' }}</dd>
                        <dt class="col-5 text-muted">Última compra</dt>
                        <dd class="col-7">{{ $customer->last_order_at?->format('d/m/Y') ?? '—' }}</dd>
                        <dt class="col-5 text-muted">Creado</dt>
                        <dd class="col-7">{{ $customer->created_at->format('d/m/Y') }}</dd>
                    </dl>
                </div>
            </div>

            {{-- Consent --}}
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Consentimiento</h6>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small">Email marketing</span>
                        @if($customer->consent_marketing)
                            <span class="badge bg-success-subtle text-success">Otorgado</span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary">No otorgado</span>
                        @endif
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small">Double opt-in</span>
                        @if($customer->consent_confirmed_at)
                            <span class="badge bg-success-subtle text-success">Confirmado</span>
                        @else
                            <span class="badge bg-warning-subtle text-warning">Pendiente</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Tags --}}
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Etiquetas</h6>
                    @if($customer->tags && count($customer->tags) > 0)
                        <div class="d-flex flex-wrap gap-1">
                            @foreach($customer->tags as $tag)
                                <span class="badge bg-light text-dark border">{{ $tag }}</span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted small mb-0">Sin etiquetas</p>
                    @endif
                </div>
            </div>

            {{-- DSR action --}}
            <div class="card ">
                <div class="card-body">
                    <h6 class="fw-bold text-danger mb-2">Zona de peligro</h6>
                    <p class="small text-muted mb-3">Eliminar todos los datos del cliente (DSR / GDPR). Esta acción es irreversible.</p>
                    <button type="button" class="btn btn-outline-danger btn-sm w-100" data-bs-toggle="modal" data-bs-target="#dsrModal">
                        <i class="fas fa-trash-alt me-1"></i> Eliminar datos (DSR)
                    </button>
                </div>
            </div>

        </div>

        {{-- Right: tabs --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header border-bottom p-0">
                    <ul class="nav nav-tabs border-0 px-3" id="customerTabs">
                        <li class="nav-item">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-timeline">Timeline</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-events">Eventos</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-orders">Pedidos</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-messages">Mensajes</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-consent">Historial consent</button>
                        </li>
                    </ul>
                </div>
                <div class="tab-content card-body">

                    {{-- Unified timeline --}}
                    <div class="tab-pane fade show active" id="tab-timeline">
                        @if(isset($timeline) && count($timeline) > 0)
                            <div class="customer-timeline">
                                @foreach($timeline as $item)
                                    <div class="d-flex gap-3 mb-3 timeline-item">
                                        <div class="rounded-circle bg-{{ $item['tone'] }}-subtle d-flex align-items-center justify-content-center flex-shrink-0" style="width:36px;height:36px">
                                            <i class="fas {{ $item['icon'] }} text-{{ $item['tone'] }} small"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start gap-2">
                                                <div class="fw-semibold small">{{ $item['title'] }}</div>
                                                <span class="badge bg-light text-muted small text-uppercase">{{ $item['kind'] }}</span>
                                            </div>
                                            @if($item['subtitle'])
                                                <div class="text-muted small mt-1">{{ $item['subtitle'] }}</div>
                                            @endif
                                            <div class="text-muted small">{{ $item['date']?->format('d/m/Y H:i') ?? '—' }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted text-center py-4 mb-0">Sin actividad registrada</p>
                        @endif
                    </div>

                    {{-- Events timeline --}}
                    <div class="tab-pane fade" id="tab-events">
                        @if(isset($events) && $events->count() > 0)
                            <div class="timeline">
                                @foreach($events as $event)
                                    <div class="d-flex gap-3 mb-3">
                                        <div class="rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center flex-shrink-0" style="width:36px;height:36px">
                                            <i class="fas fa-circle-dot text-primary small"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold small">{{ $event->type }}</div>
                                            <div class="text-muted small">{{ $event->occurred_at->format('d/m/Y H:i') }}</div>
                                            @if($event->properties)
                                                <div class="text-muted small mt-1">
                                                    @foreach(array_slice((array)$event->properties, 0, 3) as $k => $v)
                                                        <span>{{ $k }}: {{ is_array($v) ? json_encode($v) : $v }}</span>
                                                        @if(!$loop->last) &nbsp;·&nbsp; @endif
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted text-center py-4 mb-0">Sin eventos registrados</p>
                        @endif
                    </div>

                    {{-- Orders --}}
                    <div class="tab-pane fade" id="tab-orders">
                        @if(isset($orders) && $orders->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Orden</th>
                                            <th>Estado</th>
                                            <th>Total</th>
                                            <th>Fecha</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($orders as $order)
                                            <tr>
                                                <td class="small fw-semibold">{{ $order->order_number ?: '#' . $order->id }}</td>
                                                <td><span class="badge bg-primary-subtle text-primary">{{ $order->status }}</span></td>
                                                <td class="small">{{ number_format($order->total, 2) }} {{ $order->currency }}</td>
                                                <td class="small text-muted">{{ $order->placed_at->format('d/m/Y') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted text-center py-4 mb-0">Sin pedidos registrados</p>
                        @endif
                    </div>

                    {{-- Messages --}}
                    <div class="tab-pane fade" id="tab-messages">
                        @if(isset($messages) && $messages->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Asunto</th>
                                            <th>Estado</th>
                                            <th>Enviado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($messages as $msg)
                                            <tr>
                                                <td class="small">{{ $msg->subject }}</td>
                                                <td><span class="badge bg-secondary-subtle text-secondary">{{ $msg->status }}</span></td>
                                                <td class="small text-muted">{{ $msg->sent_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted text-center py-4 mb-0">Sin mensajes enviados</p>
                        @endif
                    </div>

                    {{-- Consent log --}}
                    <div class="tab-pane fade" id="tab-consent">
                        @if(isset($consentEvents) && $consentEvents->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Evento</th>
                                            <th>Fuente</th>
                                            <th>IP</th>
                                            <th>Fecha</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($consentEvents as $ce)
                                            <tr>
                                                <td>
                                                    @if(in_array($ce->event_type, ['granted', 'confirmed']))
                                                        <span class="badge bg-success-subtle text-success">{{ $ce->event_type }}</span>
                                                    @else
                                                        <span class="badge bg-danger-subtle text-danger">{{ $ce->event_type }}</span>
                                                    @endif
                                                </td>
                                                <td class="small">{{ $ce->source }}</td>
                                                <td class="small text-muted">{{ $ce->ip ?? '—' }}</td>
                                                <td class="small text-muted">{{ $ce->occurred_at->format('d/m/Y H:i') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted text-center py-4 mb-0">Sin eventos de consentimiento</p>
                        @endif
                    </div>

                </div>
            </div>
        </div>

    </div>

    {{-- DSR Delete modal --}}
    <div class="modal fade" id="dsrModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title">Eliminar datos del cliente (DSR)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Esta acción eliminará permanentemente todos los datos personales de <strong>{{ $customer->email }}</strong>. El historial de consentimiento se conservará por obligación legal.
                    </div>
                    <p class="mb-0 text-muted small">Esta operación es irreversible y cumple con los derechos de supresión del GDPR/LGPD.</p>
                </div>
                <div class="modal-footer flex-column">
                    <form action="{{ route('remarketing.customers.destroy', $customer) }}" method="POST" class="w-100">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100 mb-2">Confirmar eliminación DSR</button>
                    </form>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

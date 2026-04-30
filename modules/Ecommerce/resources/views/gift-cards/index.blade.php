@extends('layouts.theme')

@section('title', 'Gift cards')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Gift cards'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Gift cards</h5>
                        <p class="small mb-0 text-muted">Tarjetas de regalo emitidas</p>
                    </div>
                    <a href="{{ route('ecommerce.gift-cards.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nueva gift card
                    </a>
                </div>
            </div>

            <div class="card-body border-bottom py-3">
                <form method="GET" class="d-flex gap-2 flex-wrap">
                    <input type="text" name="search" value="{{ request('search') }}"
                        class="form-control" placeholder="Buscar codigo o email..." style="max-width: 280px;">
                    <select name="status" class="form-select" style="max-width: 160px;">
                        <option value="">Todos los estados</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activa</option>
                        <option value="used" {{ request('status') === 'used' ? 'selected' : '' }}>Usada</option>
                        <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expirada</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelada</option>
                    </select>
                    <button type="submit" class="btn btn-outline-secondary">Filtrar</button>
                    @if(request('search') || request('status'))
                        <a href="{{ route('ecommerce.gift-cards.index') }}" class="btn btn-light">Limpiar</a>
                    @endif
                </form>
            </div>

            <div class="card-body p-0">
                @if($giftCards->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Codigo</th>
                                    <th>Monto</th>
                                    <th>Saldo</th>
                                    <th>Destinatario</th>
                                    <th>Estado</th>
                                    <th>Vencimiento</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($giftCards as $giftCard)
                                    <tr>
                                        <td class="ps-4">
                                            <a href="{{ route('ecommerce.gift-cards.show', $giftCard) }}"
                                                class="text-decoration-none fw-semibold font-monospace">{{ $giftCard->code }}</a>
                                        </td>
                                        <td>${{ number_format($giftCard->amount, 2) }}</td>
                                        <td>
                                            <span class="{{ (float) $giftCard->balance > 0 ? 'text-success' : 'text-muted' }} fw-semibold">
                                                ${{ number_format($giftCard->balance, 2) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($giftCard->recipient_email)
                                                <div>{{ $giftCard->recipient_name ?? '—' }}</div>
                                                <small class="text-muted">{{ $giftCard->recipient_email }}</small>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $statusMap = [
                                                    'active' => ['bg-success', 'Activa'],
                                                    'used' => ['bg-secondary', 'Usada'],
                                                    'expired' => ['bg-warning', 'Expirada'],
                                                    'cancelled' => ['bg-danger', 'Cancelada'],
                                                ];
                                                [$bg, $label] = $statusMap[$giftCard->status] ?? ['bg-secondary', $giftCard->status];
                                            @endphp
                                            <span class="badge {{ $bg }}">{{ $label }}</span>
                                        </td>
                                        <td>
                                            @if($giftCard->expires_at)
                                                <small class="{{ $giftCard->expires_at->isPast() ? 'text-danger' : 'text-muted' }}">
                                                    {{ $giftCard->expires_at->format('d/m/Y') }}
                                                </small>
                                            @else
                                                <small class="text-muted">Sin venc.</small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button type="button" class="btn btn-sm btn-light"
                                                    data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item"
                                                            href="{{ route('ecommerce.gift-cards.show', $giftCard) }}">Ver detalle</a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form action="{{ route('ecommerce.gift-cards.destroy', $giftCard) }}"
                                                            method="POST">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="dropdown-item"
                                                                onclick="return confirm('¿Eliminar esta gift card?')">
                                                                Eliminar
                                                            </button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-gift fa-4x text-muted opacity-50 mb-4"></i>
                        <h5 class="text-muted mb-2">No hay gift cards</h5>
                        <a href="{{ route('ecommerce.gift-cards.create') }}" class="btn btn-primary">
                            Crear primera gift card
                        </a>
                    </div>
                @endif
            </div>

            @if($giftCards->hasPages())
                <div class="card-footer">{{ $giftCards->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
@endsection

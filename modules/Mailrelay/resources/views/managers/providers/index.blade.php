@extends('layouts.theme')

@section('title', 'Proveedores de email')

@section('content')
<div class="widget-content searchable-container list">

    @include('core::components.alerts')

    {{-- Main Card --}}
    <div class="card">
        {{-- Header Section --}}
        <div class="card-header p-4 border-bottom border-light">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Proveedores de email</h5>
                    <p class="small mb-0 text-muted">Gestiona los proveedores de envío de email (Mailrelay, Mailtrap, SendGrid, etc.)</p>
                </div>
                <a href="{{ route('managers.mailrelay.providers.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-2"></i>Agregar provider
                </a>
            </div>
        </div>

        {{-- Providers List --}}
        <div class="card-body">
            @if($providers->isEmpty())
                <div class="text-center py-5">
                    <div class="d-flex flex-column align-items-center">
                        <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                            <i class="fas fa-server fs-7"></i>
                        </div>
                        <h6 class="mb-1">No hay providers configurados</h6>
                        <p class="text-muted mb-3">Agrega tu primer proveedor de email para comenzar a enviar campaigns</p>
                        <a href="{{ route('managers.mailrelay.providers.create') }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus"></i> Agregar primer provider
                        </a>
                    </div>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Provider</th>
                                <th>Tipo</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Predeterminado</th>
                                <th class="text-center">Prioridad</th>
                                <th class="text-center">Última prueba</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($providers as $provider)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($provider->driver === 'mailrelay')
                                            <div class="round-40 rounded-circle bg-primary-subtle text-primary me-3 d-flex align-items-center justify-content-center">
                                                <i class="fas fa-paper-plane"></i>
                                            </div>
                                        @elseif($provider->driver === 'mailtrap')
                                            <div class="round-40 rounded-circle bg-warning-subtle text-warning me-3 d-flex align-items-center justify-content-center">
                                                <i class="fas fa-flask"></i>
                                            </div>
                                        @elseif($provider->driver === 'sendgrid')
                                            <div class="round-40 rounded-circle bg-info-subtle text-info me-3 d-flex align-items-center justify-content-center">
                                                <i class="fas fa-cloud"></i>
                                            </div>
                                        @else
                                            <div class="round-40 rounded-circle bg-secondary-subtle text-secondary me-3 d-flex align-items-center justify-content-center">
                                                <i class="fas fa-envelope"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <h6 class="mb-0 fw-semibold">{{ $provider->name }}</h6>
                                            <small class="text-muted">{{ $provider->credentials['from_email'] ?? 'N/A' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark rounded-3 py-1 px-3">
                                        {{ strtoupper($provider->driver) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($provider->is_active)
                                        <span class="badge bg-success-subtle text-success rounded-3 py-2 fw-semibold">
                                            <i class="fas fa-check-circle"></i> Activo
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary rounded-3 py-2 fw-semibold">
                                            <i class="fas fa-pause-circle"></i> Inactivo
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($provider->is_default)
                                        <span class="badge bg-primary-subtle text-primary rounded-3 py-2 fw-semibold">
                                            <i class="fas fa-star"></i> Default
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark rounded-pill px-3">
                                        {{ $provider->priority }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($provider->last_tested_at)
                                        <small class="text-muted">
                                            {{ $provider->last_tested_at->diffForHumans() }}
                                            @if($provider->connection_ok)
                                                <i class="fas fa-check text-success ms-1"></i>
                                            @else
                                                <i class="fas fa-times text-danger ms-1"></i>
                                            @endif
                                        </small>
                                    @else
                                        <small class="text-muted">Nunca</small>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light-subtle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('managers.mailrelay.providers.edit', $provider->id) }}">
                                                    <i class="fas fa-edit me-2"></i>Editar
                                                </a>
                                            </li>
                                            <li>
                                                <form action="{{ route('managers.mailrelay.providers.test', $provider->id) }}" method="POST" class="test-provider-form">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="fas fa-vial me-2"></i>Probar conexión
                                                    </button>
                                                </form>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            @if(!$provider->is_default)
                                            <li>
                                                <form action="{{ route('managers.mailrelay.providers.set-default', $provider->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="fas fa-star me-2"></i>Marcar como default
                                                    </button>
                                                </form>
                                            </li>
                                            @endif
                                            <li>
                                                <form action="{{ route('managers.mailrelay.providers.toggle-active', $provider->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item">
                                                        @if($provider->is_active)
                                                            <i class="fas fa-pause-circle me-2"></i>Desactivar
                                                        @else
                                                            <i class="fas fa-play-circle me-2"></i>Activar
                                                        @endif
                                                    </button>
                                                </form>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('managers.mailrelay.providers.destroy', $provider->id) }}" method="POST" class="delete-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="fas fa-trash me-2"></i>Eliminar
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

                {{-- Info Box --}}
                <div class="alert alert-light border mt-4" role="alert">
                    <div class="d-flex align-items-start">
                        <i class="fas fa-info-circle text-primary me-3 mt-1"></i>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">Sobre los providers de email</h6>
                            <p class="mb-2 small">Los providers son servicios externos que se encargan del envío masivo de emails. Puedes configurar múltiples providers con diferentes prioridades para failover automático.</p>
                            <ul class="small mb-0">
                                <li><strong>Activo:</strong> El provider está habilitado y puede usarse para enviar campaigns</li>
                                <li><strong>Predeterminado:</strong> El provider que se seleccionará automáticamente al crear nuevas campaigns</li>
                                <li><strong>Prioridad:</strong> En caso de fallos, se intentará con el provider de mayor prioridad disponible</li>
                            </ul>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
// Confirmar eliminación
document.querySelectorAll('.delete-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        if (!confirm('¿Estás seguro de eliminar este provider? Esta acción no se puede deshacer.')) {
            e.preventDefault();
        }
    });
});

// Test de provider con AJAX
document.querySelectorAll('.test-provider-form').forEach(form => {
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = this.querySelector('button');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Probando...';
        btn.disabled = true;

        try {
            const response = await fetch(this.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            const data = await response.json();

            if (data.success) {
                alert('✓ Conexión exitosa: ' + data.message);
                window.location.reload();
            } else {
                alert('✗ Error de conexión: ' + data.message);
            }
        } catch (error) {
            alert('✗ Error al probar conexión: ' + error.message);
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    });
});
</script>
@endpush
@endsection

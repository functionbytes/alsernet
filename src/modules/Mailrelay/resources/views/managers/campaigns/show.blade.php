@extends('layouts.theme')

@section('title', $campaign->name)

@section('content')
<div class="widget-content">

    @include('core::components.alerts')

    {{-- Breadcrumb --}}
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('managers.mailrelay.campaigns.index') }}">Campaigns</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $campaign->name }}</li>
            </ol>
        </nav>
    </div>

    <div class="row">
        {{-- Main Content --}}
        <div class="col-lg-8">

            {{-- Header --}}
            <div class="card mb-3">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h3 class="mb-2 fw-bold">{{ $campaign->name }}</h3>
                            <p class="text-muted mb-0">
                                <i class="fas fa-envelope me-2"></i>{{ $campaign->subject }}
                            </p>
                        </div>
                        @switch($campaign->status->value)
                            @case('draft')
                                <span class="badge bg-secondary-subtle text-secondary rounded-3 py-2 px-3 fs-5">
                                    <i class="fas fa-pencil"></i> Borrador
                                </span>
                                @break
                            @case('scheduled')
                                <span class="badge bg-info-subtle text-info rounded-3 py-2 px-3 fs-5">
                                    <i class="fas fa-clock"></i> Programada
                                </span>
                                @break
                            @case('sending')
                                <span class="badge bg-warning-subtle text-warning rounded-3 py-2 px-3 fs-5">
                                    <i class="fas fa-hourglass-half"></i> Enviando
                                </span>
                                @break
                            @case('sent')
                                <span class="badge bg-success-subtle text-success rounded-3 py-2 px-3 fs-5">
                                    <i class="fas fa-check-circle"></i> Enviada
                                </span>
                                @break
                            @case('failed')
                                <span class="badge bg-danger-subtle text-danger rounded-3 py-2 px-3 fs-5">
                                    <i class="fas fa-times-circle"></i> Fallida
                                </span>
                                @break
                        @endswitch
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <small class="text-muted d-block">Creada</small>
                            <strong>{{ $campaign->created_at->format('d/m/Y H:i') }}</strong>
                        </div>
                        @if($campaign->scheduled_at)
                        <div class="col-md-4">
                            <small class="text-muted d-block">Programada para</small>
                            <strong>{{ $campaign->scheduled_at->format('d/m/Y H:i') }}</strong>
                        </div>
                        @endif
                        @if($campaign->sent_at)
                        <div class="col-md-4">
                            <small class="text-muted d-block">Enviada</small>
                            <strong>{{ $campaign->sent_at->format('d/m/Y H:i') }}</strong>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Integration Info --}}
            <div class="card mb-3">
                <div class="card-header p-3 border-bottom">
                    <h6 class="mb-0 fw-semibold">Configuración</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="fw-semibold mb-3 border-bottom pb-2">
                                <i class="fas fa-palette me-2"></i>Plantilla
                            </h6>
                            @if($campaign->mailerTemplate)
                                <div class="d-flex align-items-center">
                                    <div class="round-40 rounded-circle bg-primary-subtle text-primary me-3 d-flex align-items-center justify-content-center">
                                        <i class="fas fa-palette"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">{{ $campaign->mailerTemplate->name }}</div>
                                        <small class="text-muted">
                                            @if($campaign->mailerTemplate->module)
                                                Módulo: {{ $campaign->mailerTemplate->module }}
                                            @endif
                                        </small>
                                    </div>
                                </div>
                                @if($campaign->language)
                                    <div class="mt-3">
                                        <small class="text-muted">Idioma:</small>
                                        <span class="badge bg-light text-dark ms-2">{{ $campaign->language->name }}</span>
                                    </div>
                                @endif
                            @else
                                <div class="d-flex align-items-center">
                                    <div class="round-40 rounded-circle bg-secondary-subtle text-secondary me-3 d-flex align-items-center justify-content-center">
                                        <i class="fas fa-code"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">HTML personalizado</div>
                                        <small class="text-muted">Sin plantilla Mailer</small>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <h6 class="fw-semibold mb-3 border-bottom pb-2">
                                <i class="fas fa-server me-2"></i>Provider
                            </h6>
                            @if($campaign->mailProvider)
                                <div class="d-flex align-items-center">
                                    @if($campaign->mailProvider->driver === 'mailrelay')
                                        <div class="round-40 rounded-circle bg-primary-subtle text-primary me-3 d-flex align-items-center justify-content-center">
                                            <i class="fas fa-paper-plane"></i>
                                        </div>
                                    @elseif($campaign->mailProvider->driver === 'mailtrap')
                                        <div class="round-40 rounded-circle bg-warning-subtle text-warning me-3 d-flex align-items-center justify-content-center">
                                            <i class="fas fa-flask"></i>
                                        </div>
                                    @else
                                        <div class="round-40 rounded-circle bg-info-subtle text-info me-3 d-flex align-items-center justify-content-center">
                                            <i class="fas fa-cloud"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="fw-semibold">{{ $campaign->mailProvider->name }}</div>
                                        <small class="text-muted">{{ strtoupper($campaign->mailProvider->driver) }}</small>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Tracking --}}
                    <div class="mt-4">
                        <h6 class="fw-semibold mb-3 border-bottom pb-2">
                            <i class="fas fa-chart-line me-2"></i>Seguimiento
                        </h6>
                        <div class="d-flex gap-3">
                            @if($campaign->track_opens)
                                <span class="badge bg-success-subtle text-success rounded-pill px-3 py-2">
                                    <i class="fas fa-eye me-1"></i>Rastreando aperturas
                                </span>
                            @else
                                <span class="badge bg-light text-dark rounded-pill px-3 py-2">
                                    <i class="fas fa-eye-slash me-1"></i>Sin tracking de aperturas
                                </span>
                            @endif

                            @if($campaign->track_clicks)
                                <span class="badge bg-success-subtle text-success rounded-pill px-3 py-2">
                                    <i class="fas fa-mouse-pointer me-1"></i>Rastreando clicks
                                </span>
                            @else
                                <span class="badge bg-light text-dark rounded-pill px-3 py-2">
                                    <i class="fas fa-ban me-1"></i>Sin tracking de clicks
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Variables personalizadas --}}
            @if($campaign->template_variables && count($campaign->template_variables) > 0)
            <div class="card mb-3">
                <div class="card-header p-3 border-bottom">
                    <h6 class="mb-0 fw-semibold">
                        <i class="fas fa-code me-2"></i>Variables personalizadas
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Variable</th>
                                    <th>Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($campaign->template_variables as $key => $value)
                                <tr>
                                    <td><code>{{{ $key }}}</code></td>
                                    <td>{{ $value }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            {{-- Estadísticas --}}
            @if($stats)
            <div class="card">
                <div class="card-header p-3 border-bottom">
                    <h6 class="mb-0 fw-semibold">
                        <i class="fas fa-chart-bar me-2"></i>Estadísticas
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded">
                                <div class="fs-2 fw-bold text-primary">{{ $stats['sent'] ?? 0 }}</div>
                                <small class="text-muted">Enviados</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded">
                                <div class="fs-2 fw-bold text-success">{{ $stats['delivered'] ?? 0 }}</div>
                                <small class="text-muted">Entregados</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded">
                                <div class="fs-2 fw-bold text-info">{{ $stats['opened'] ?? 0 }}</div>
                                <small class="text-muted">Abiertos</small>
                                <div class=" text-muted mt-1">{{ number_format($stats['open_rate'] ?? 0, 2) }}%</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded">
                                <div class="fs-2 fw-bold text-warning">{{ $stats['clicked'] ?? 0 }}</div>
                                <small class="text-muted">Clicks</small>
                                <div class=" text-muted mt-1">{{ number_format($stats['click_rate'] ?? 0, 2) }}%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">
            {{-- Actions --}}
            <div class="card mb-3">
                <div class="card-header p-3 border-bottom">
                    <h6 class="mb-0 fw-semibold">Acciones</h6>
                </div>
                <div class="card-body p-3">
                    <div class="d-grid gap-2">
                        <a href="{{ route('managers.mailrelay.campaigns.preview', $campaign->id) }}"
                           class="btn btn-primary" target="_blank">
                            <i class="fas fa-eye me-2"></i>Ver preview
                        </a>

                        @if(in_array($campaign->status->value, ['draft', 'failed']))
                            <a href="{{ route('managers.mailrelay.campaigns.edit', $campaign->id) }}"
                               class="btn btn-light">
                                <i class="fas fa-edit me-2"></i>Editar
                            </a>
                        @endif

                        <button type="button" class="btn btn-light" onclick="showSendTestModal()">
                            <i class="fas fa-flask me-2"></i>Enviar prueba
                        </button>

                        <hr>

                        <form action="{{ route('managers.mailrelay.campaigns.duplicate', $campaign->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-light w-100">
                                <i class="fas fa-copy me-2"></i>Duplicar
                            </button>
                        </form>

                        @if(in_array($campaign->status->value, ['draft', 'failed']))
                            <form action="{{ route('managers.mailrelay.campaigns.destroy', $campaign->id) }}" method="POST" class="delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger w-100">
                                    <i class="fas fa-trash me-2"></i>Eliminar
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Provider Info --}}
            @if($campaign->provider_campaign_id)
            <div class="card">
                <div class="card-header p-3 border-bottom">
                    <h6 class="mb-0 fw-semibold">
                        <i class="fas fa-info-circle me-2"></i>Info del provider
                    </h6>
                </div>
                <div class="card-body p-3">
                    <small class="text-muted d-block mb-1">ID externo:</small>
                    <code class="small">{{ $campaign->provider_campaign_id }}</code>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Test Email Modal --}}
<div class="modal fade" id="sendTestModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Enviar email de prueba</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="sendTestForm">
                    <div class="mb-3">
                        <label for="testEmail" class="form-label">Email de destino</label>
                        <input type="email" class="form-control" id="testEmail" name="test_email" required
                               placeholder="test@example.com">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="submitTestEmail()">
                    <i class="fas fa-paper-plane me-2"></i>Enviar
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const sendTestModal = new bootstrap.Modal(document.getElementById('sendTestModal'));

function showSendTestModal() {
    sendTestModal.show();
}

async function submitTestEmail() {
    const testEmail = document.getElementById('testEmail').value;

    if (!testEmail) {
        alert('Por favor ingresa un email válido');
        return;
    }

    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enviando...';
    btn.disabled = true;

    try {
        const response = await fetch('{{ route("managers.mailrelay.campaigns.send-test", $campaign->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ test_email: testEmail })
        });

        const data = await response.json();

        if (data.success) {
            alert('✓ Email de prueba enviado exitosamente');
            sendTestModal.hide();
        } else {
            alert('✗ Error: ' + data.error);
        }
    } catch (error) {
        alert('✗ Error al enviar email de prueba: ' + error.message);
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

// Confirmar eliminación
document.querySelector('.delete-form')?.addEventListener('submit', function(e) {
    if (!confirm('¿Estás seguro de eliminar esta campaign? Esta acción no se puede deshacer.')) {
        e.preventDefault();
    }
});
</script>
@endpush
@endsection

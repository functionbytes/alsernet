@extends('layouts.theme')

@section('title', 'Campaigns de email')

@section('content')
<div class="widget-content searchable-container list">

    @include('core::components.alerts')

    {{-- Main Card --}}
    <div class="card">
        {{-- Header Section --}}
        <div class="card-header p-4 border-bottom border-light">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Campaigns de email</h5>
                    <p class="small mb-0 text-muted">Gestiona campaigns con integración de plantillas Mailer y multi-provider</p>
                </div>
                <a href="{{ route('managers.mailrelay.campaigns.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-2"></i>Nueva campaign
                </a>
            </div>
        </div>

        {{-- Filter Tabs --}}
        <div class="card-body border-bottom">
            <ul class="nav nav-pills">
                <li class="nav-item">
                    <a class="nav-link {{ request('status') == '' ? 'active' : '' }}"
                       href="{{ route('managers.mailrelay.campaigns.index') }}">
                        Todas
                    </a>
                </li>
                @foreach($statuses as $statusValue => $statusLabel)
                    <li class="nav-item">
                        <a class="nav-link {{ request('status') == $statusValue ? 'active' : '' }}"
                           href="{{ route('managers.mailrelay.campaigns.index', ['status' => $statusValue]) }}">
                            {{ $statusLabel }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Campaigns Grid --}}
        <div class="card-body">
            @if($campaigns->isEmpty())
                <div class="text-center py-5">
                    <div class="d-flex flex-column align-items-center">
                        <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                            <i class="fas fa-inbox fs-7"></i>
                        </div>
                        <h6 class="mb-1">No hay campaigns todavía</h6>
                        <p class="text-muted mb-3">Crea tu primera campaign de email usando plantillas Mailer</p>
                        <a href="{{ route('managers.mailrelay.campaigns.create') }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus"></i> Crear primera campaign
                        </a>
                    </div>
                </div>
            @else
                <div class="row">
                    @foreach($campaigns as $campaign)
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 border shadow-sm">
                            <div class="card-body">
                                {{-- Status Badge --}}
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="card-title mb-0 fw-bold text-truncate me-2" style="flex: 1;">
                                        {{ $campaign->name }}
                                    </h5>
                                    @switch($campaign->status->value)
                                        @case('draft')
                                            <span class="badge bg-secondary-subtle text-secondary rounded-3 py-2 fw-semibold fs-2">
                                                <i class="fas fa-pencil"></i> Borrador
                                            </span>
                                            @break
                                        @case('scheduled')
                                            <span class="badge bg-info-subtle text-info rounded-3 py-2 fw-semibold fs-2">
                                                <i class="fas fa-clock"></i> Programada
                                            </span>
                                            @break
                                        @case('sending')
                                            <span class="badge bg-warning-subtle text-warning rounded-3 py-2 fw-semibold fs-2">
                                                <i class="fas fa-hourglass-half"></i> Enviando
                                            </span>
                                            @break
                                        @case('sent')
                                            <span class="badge bg-success-subtle text-success rounded-3 py-2 fw-semibold fs-2">
                                                <i class="fas fa-check-circle"></i> Enviada
                                            </span>
                                            @break
                                        @case('failed')
                                            <span class="badge bg-danger-subtle text-danger rounded-3 py-2 fw-semibold fs-2">
                                                <i class="fas fa-times-circle"></i> Fallida
                                            </span>
                                            @break
                                    @endswitch
                                </div>

                                {{-- Subject --}}
                                <p class="card-text text-muted small mb-3">
                                    <i class="fas fa-envelope me-1"></i>
                                    {{ Str::limit($campaign->subject, 60) }}
                                </p>

                                {{-- Integration Info --}}
                                <div class="mb-3">
                                    @if($campaign->mailerTemplate)
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1">
                                                <i class="fas fa-palette me-1"></i>
                                                {{ $campaign->mailerTemplate->name }}
                                            </span>
                                        </div>
                                    @else
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="badge bg-light text-dark rounded-pill px-3 py-1">
                                                <i class="fas fa-code me-1"></i>
                                                HTML personalizado
                                            </span>
                                        </div>
                                    @endif

                                    @if($campaign->mailProvider)
                                        <div class="d-flex align-items-center">
                                            <small class="text-muted">
                                                <i class="fas fa-server me-1"></i>
                                                {{ $campaign->mailProvider->name }}
                                            </small>
                                        </div>
                                    @endif
                                </div>

                                {{-- Tracking --}}
                                <div class="d-flex gap-2 mb-3">
                                    @if($campaign->track_opens)
                                        <span class="badge bg-light text-dark rounded-pill px-2 py-1">
                                            <i class="fas fa-eye"></i> Opens
                                        </span>
                                    @endif
                                    @if($campaign->track_clicks)
                                        <span class="badge bg-light text-dark rounded-pill px-2 py-1">
                                            <i class="fas fa-mouse-pointer"></i> Clicks
                                        </span>
                                    @endif
                                </div>

                                {{-- Timestamp --}}
                                <div class="border-top pt-3">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        Creada {{ $campaign->created_at->diffForHumans() }}
                                    </small>
                                </div>

                                {{-- Actions --}}
                                <div class="d-flex gap-2 mt-3">
                                    <a href="{{ route('managers.mailrelay.campaigns.show', $campaign->id) }}"
                                       class="btn btn-sm btn-light flex-grow-1">
                                        <i class="fas fa-eye"></i> Ver
                                    </a>

                                    @if(in_array($campaign->status->value, ['draft', 'failed']))
                                        <a href="{{ route('managers.mailrelay.campaigns.edit', $campaign->id) }}"
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endif

                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('managers.mailrelay.campaigns.preview', $campaign->id) }}" target="_blank">
                                                    <i class="fas fa-eye me-2"></i>Preview
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="#" onclick="sendTest({{ $campaign->id }})">
                                                    <i class="fas fa-flask me-2"></i>Enviar prueba
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('managers.mailrelay.campaigns.duplicate', $campaign->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="fas fa-copy me-2"></i>Duplicar
                                                    </button>
                                                </form>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('managers.mailrelay.campaigns.destroy', $campaign->id) }}" method="POST" class="delete-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="fas fa-trash me-2"></i>Eliminar
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                @if($campaigns->hasPages())
                    <div class="d-flex justify-content-center mt-4">
                        {{ $campaigns->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

{{-- Test Email Modal --}}
<div class="modal fade" id="sendTestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Enviar email de prueba</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="sendTestForm">
                    <input type="hidden" id="campaignId" name="campaign_id">
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

function sendTest(campaignId) {
    document.getElementById('campaignId').value = campaignId;
    sendTestModal.show();
}

async function submitTestEmail() {
    const campaignId = document.getElementById('campaignId').value;
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
        const response = await fetch(`/managers/mailrelay/campaigns/${campaignId}/send-test`, {
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
document.querySelectorAll('.delete-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        if (!confirm('¿Estás seguro de eliminar esta campaign? Esta acción no se puede deshacer.')) {
            e.preventDefault();
        }
    });
});
</script>
@endpush
@endsection

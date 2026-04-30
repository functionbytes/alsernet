@extends('layouts.theme')

@section('title', 'Vista previa de plantilla')

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">Vista previa de plantilla</h5>
                            <p class="mb-0 text-muted small">{{ $emailTemplate->name }}</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('settings.chat.email.edit', $emailTemplate) }}"
                               class="btn btn-secondary">
                                <i class="fa fa-edit"></i> Editar plantilla
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="alert alert-info mb-4">
                        <i class="fa fa-circle-info"></i>
                        Esta vista previa usa datos de ejemplo. El email real utilizará datos reales al ser enviado.
                    </div>

                    <div class="row">
                        <!-- Email Preview -->
                        <div class="col-lg-8">
                            <div class="card mb-3">
                                <div class="card-header bg-primary text-white border-bottom">
                                    <h6 class="mb-0 fw-semibold">
                                        <i class="fa fa-envelope"></i> Vista previa del email
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <!-- Email Headers -->
                                    <div class="mb-3 pb-3 border-bottom">
                                        <div class="row mb-2">
                                            <div class="col-2 text-muted"><strong>De:</strong></div>
                                            <div class="col-10">{{ config('mail.from.name') }} &lt;{{ config('mail.from.address') }}&gt;</div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-2 text-muted"><strong>Para:</strong></div>
                                            <div class="col-10">destinatario@ejemplo.com</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-2 text-muted"><strong>Asunto:</strong></div>
                                            <div class="col-10"><strong>{{ $renderedSubject }}</strong></div>
                                        </div>
                                    </div>

                                    <!-- Email Body -->
                                    <div class="email-preview-body">
                                        {!! $renderedHtml !!}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar -->
                        <div class="col-lg-4">
                            <!-- Template Info -->
                            <div class="card mb-3">
                                <div class="card-header border-bottom">
                                    <h6 class="mb-0 fw-semibold">Información de la plantilla</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="text-muted small">Nombre</label>
                                        <p class="mb-0">{{ $emailTemplate->name }}</p>
                                    </div>

                                    <div class="mb-3">
                                        <label class="text-muted small">Tipo de evento</label>
                                        <p class="mb-0">
                                            <span class="badge bg-info-subtle text-info">
                                                {{ \Modules\Chat\Services\EmailTemplateVariableService::getEventTypes()[$emailTemplate->event_type] ?? $emailTemplate->event_type }}
                                            </span>
                                        </p>
                                    </div>

                                    <div class="mb-3">
                                        <label class="text-muted small">Estado</label>
                                        <p class="mb-0">
                                            @if($emailTemplate->active)
                                                <span class="badge bg-success-subtle text-success">Activa</span>
                                            @else
                                                <span class="badge bg-info-subtle text-info">Inactiva</span>
                                            @endif
                                        </p>
                                    </div>

                                    <div class="mb-3">
                                        <label class="text-muted small">Creado</label>
                                        <p class="mb-0">{{ $emailTemplate->created_at->format('d/m/Y H:i') }}</p>
                                    </div>

                                    @if($emailTemplate->updated_at != $emailTemplate->created_at)
                                        <div class="mb-0">
                                            <label class="text-muted small">Actualizado</label>
                                            <p class="mb-0">{{ $emailTemplate->updated_at->format('d/m/Y H:i') }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            @if($emailTemplate->description)
                                <div class="card mb-3">
                                    <div class="card-header border-bottom">
                                        <h6 class="mb-0 fw-semibold">Descripción</h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-0">{{ $emailTemplate->description }}</p>
                                    </div>
                                </div>
                            @endif

                            <!-- Actions -->
                            <div class="card">
                                <div class="card-header border-bottom">
                                    <h6 class="mb-0 fw-semibold">Opciones de vista previa</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <button type="button" class="btn btn-outline-primary" onclick="toggleViewMode()">
                                            <i class="fa fa-code"></i> <span id="view-mode-text">Ver código HTML</span>
                                        </button>
                                        <button type="button" class="btn btn-secondary" onclick="window.print()">
                                            <i class="fa fa-print"></i> Imprimir vista previa
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<style>
.email-preview-body {
    min-height: 300px;
    border: 1px solid #dee2e6;
    padding: 20px;
    background: #fff;
    border-radius: 4px;
}

.email-preview-body img {
    max-width: 100%;
    height: auto;
}

@media print {
    .card-header, .btn, nav {
        display: none !important;
    }
}
</style>

<script>
let viewingSource = false;
const emailBody = document.querySelector('.email-preview-body');
const originalHtml = emailBody.innerHTML;

function toggleViewMode() {
    viewingSource = !viewingSource;
    const btn = document.getElementById('view-mode-text');

    if (viewingSource) {
        // Show HTML source
        const escaped = originalHtml
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
        emailBody.innerHTML = `<pre class="mb-0"><code>${escaped}</code></pre>`;
        btn.textContent = 'Ver renderizado';
    } else {
        // Show rendered HTML
        emailBody.innerHTML = originalHtml;
        btn.textContent = 'Ver código HTML';
    }
}
</script>
@endpush

@extends('layouts.theme')

@section('title', 'Detalle de plantilla de email')

@section('content')
    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">
            <div class="card w-100">
                <div class="card-header border-bottom p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">{{ $emailTemplate->name }}</h5>
                            <p class="mb-0 text-muted small">Detalle de la plantilla de email</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('settings.chat.email.preview', $emailTemplate) }}"
                               class="btn btn-info" target="_blank">
                                <i class="fa fa-eye"></i> Vista previa
                            </a>
                            <a href="{{ route('settings.chat.email.edit', $emailTemplate) }}"
                               class="btn btn-primary">
                                <i class="fa fa-edit"></i> Editar
                            </a>
                            <a href="{{ route('settings.chat.email.index') }}"
                               class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    @include('core::components.alerts')

                    <div class="row">
                        <!-- Main Information -->
                        <div class="col-lg-8">
                            <!-- Basic Info -->
                            <div class="mb-4">
                                <h6 class="fw-bold mb-3 border-bottom pb-2">Información básica</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="text-muted small">Nombre</label>
                                        <p class="mb-0 fw-semibold">{{ $emailTemplate->name }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="text-muted small">Tipo de evento</label>
                                        <p class="mb-0">
                                            <span class="badge bg-info-subtle text-info">
                                                {{ \Modules\Chat\Services\EmailTemplateVariableService::getEventTypes()[$emailTemplate->event_type] ?? $emailTemplate->event_type }}
                                            </span>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="text-muted small">Estado</label>
                                        <p class="mb-0">
                                            @if($emailTemplate->active)
                                                <span class="badge bg-success-subtle text-success">Activa</span>
                                            @else
                                                <span class="badge bg-info-subtle text-info">Inactiva</span>
                                            @endif
                                        </p>
                                    </div>
                                    @if($emailTemplate->description)
                                        <div class="col-12">
                                            <label class="text-muted small">Descripción</label>
                                            <p class="mb-0">{{ $emailTemplate->description }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Email Content -->
                            <div class="mb-4">
                                <h6 class="fw-bold mb-3 border-bottom pb-2">Contenido del email</h6>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="text-muted small">Asunto</label>
                                        <div class="card bg-light border-0">
                                            <div class="card-body">
                                                {{ $emailTemplate->subject ?? 'N/A' }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label class="text-muted small">Cuerpo HTML</label>
                                        <div class="card bg-light border-0">
                                            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                                <pre class="mb-0"><code>{{ $emailTemplate->body_html ?? 'N/A' }}</code></pre>
                                            </div>
                                        </div>
                                    </div>

                                    @if($emailTemplate->body_text)
                                        <div class="col-12">
                                            <label class="text-muted small">Cuerpo texto plano</label>
                                            <div class="card bg-light border-0">
                                                <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                                                    <pre class="mb-0">{{ $emailTemplate->body_text }}</pre>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar -->
                        <div class="col-lg-4">
                            <!-- Metadata -->
                            <div class="card mb-3">
                                <div class="card-header border-bottom">
                                    <h6 class="mb-0 fw-semibold">Metadatos</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="text-muted small">Creado</label>
                                        <p class="mb-0">{{ $emailTemplate->created_at->format('d/m/Y H:i') }}</p>
                                    </div>
                                    @if($emailTemplate->updated_at != $emailTemplate->created_at)
                                        <div class="mb-0">
                                            <label class="text-muted small">Última actualización</label>
                                            <p class="mb-0">{{ $emailTemplate->updated_at->format('d/m/Y H:i') }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

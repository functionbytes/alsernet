@extends('layouts.theme')

@section('title', 'Crear Workflow de Automatización')

@section('content')

    @include('core::components.card', ['title' => 'Crear Workflow de Automatización'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            <form id="formWorkflow" method="POST" action="{{ route('settings.suppliers.automation.workflows.store') }}">

                {{ csrf_field() }}

                <div class="card-header p-4 border-bottom border-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">Crear workflow de automatización</h5>
                            <p class="small mb-0 text-muted">Configure un nuevo flujo de trabajo para automatizar procesos de proveedores</p>
                        </div>
                        <a href="{{ route('settings.suppliers.automation.index') }}" class="btn btn-secondary">
                            Volver
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">

                        <!-- Basic Information -->
                        <div class="col-12">
                            <h6 class="fw-bold mb-0 ">
                                Información básica
                            </h6>
                            <p class="text-muted small mb-3">
                                Datos generales del workflow como nombre, tipo y descripción para identificarlo fácilmente en el sistema
                            </p>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    Nombre del workflow
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name') }}" required placeholder="Ej: Importación de productos diaria">
                                <small class="form-text text-muted">Nombre descriptivo para identificar el workflow</small>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    Tipo de workflow
                                    <span class="text-danger">*</span>
                                </label>
                                <select name="workflow_type" class="form-control select2 @error('workflow_type') is-invalid @enderror" required>
                                    <option value="">Seleccionar tipo</option>
                                    <option value="n8n" {{ old('workflow_type') == 'n8n' ? 'selected' : '' }}>n8n</option>
                                    <option value="make" {{ old('workflow_type') == 'make' ? 'selected' : '' }}>Make (Integromat)</option>
                                    <option value="zapier" {{ old('workflow_type') == 'zapier' ? 'selected' : '' }}>Zapier</option>
                                    <option value="webhook" {{ old('workflow_type') == 'webhook' ? 'selected' : '' }}>Webhook personalizado</option>
                                    <option value="internal" {{ old('workflow_type') == 'internal' ? 'selected' : '' }}>Interno (Laravel)</option>
                                </select>
                                @error('workflow_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Descripción</label>
                                <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                                          rows="3" placeholder="Descripción detallada del workflow...">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Configuration -->
                        <div class="col-12">
                            <h6 class="fw-bold mt-3 mb-0">
                                Configuración
                            </h6>
                            <p class="text-muted small mb-3">
                                Parámetros técnicos para la integración con plataformas externas y configuración de webhooks
                            </p>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">ID del workflow externo</label>
                                <input type="text" name="external_workflow_id" class="form-control @error('external_workflow_id') is-invalid @enderror"
                                       value="{{ old('external_workflow_id') }}" placeholder="Ej: wf_abc123">
                                <small class="form-text text-muted">ID del workflow en la plataforma externa (n8n, Make, etc.)</small>
                                @error('external_workflow_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Prioridad</label>
                                <input type="number" name="priority" class="form-control @error('priority') is-invalid @enderror"
                                       value="{{ old('priority', 5) }}" min="1" max="10">
                                <small class="form-text text-muted">Prioridad de ejecución (1-10, mayor número = mayor prioridad)</small>
                                @error('priority')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">URL del Webhook</label>
                                <input type="url" name="webhook_url" class="form-control @error('webhook_url') is-invalid @enderror"
                                       value="{{ old('webhook_url') }}" placeholder="https://...">
                                <small class="form-text text-muted">URL para activar el workflow mediante webhook</small>
                                @error('webhook_url')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">URL de Callback</label>
                                <input type="url" name="callback_url" class="form-control @error('callback_url') is-invalid @enderror"
                                       value="{{ old('callback_url') }}" placeholder="https://...">
                                <small class="form-text text-muted">URL donde el workflow enviará resultados</small>
                                @error('callback_url')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Execution Settings -->
                        <div class="col-12">
                            <h6 class="fw-bold mt-3 mb-0">
                                Configuración de ejecución
                            </h6>
                            <p class="text-muted small mb-3">
                                Ajustes de tiempo de espera, reintentos y estado inicial del workflow
                            </p>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Timeout (segundos)</label>
                                <input type="number" name="timeout_seconds" class="form-control @error('timeout_seconds') is-invalid @enderror"
                                       value="{{ old('timeout_seconds', 300) }}" min="0">
                                <small class="form-text text-muted">Tiempo máximo de ejecución (0 = sin límite)</small>
                                @error('timeout_seconds')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Reintentos máximos</label>
                                <input type="number" name="max_retries" class="form-control @error('max_retries') is-invalid @enderror"
                                       value="{{ old('max_retries', 3) }}" min="0" max="10">
                                <small class="form-text text-muted">Número de reintentos en caso de fallo</small>
                                @error('max_retries')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Estado inicial</label>
                                <select name="is_active" class="form-control select2 @error('is_active') is-invalid @enderror">
                                    <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Activo</option>
                                    <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactivo</option>
                                </select>
                                @error('is_active')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                    </div>
                </div>

                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('settings.suppliers.automation.index') }}" class="btn btn-secondary">
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            Guardar workflow
                        </button>
                    </div>
                </div>

            </form>

        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Form validation
    $('#formWorkflow').on('submit', function(e) {
        const name = $('input[name="name"]').val();
        const workflowType = $('select[name="workflow_type"]').val();

        if (!name || !workflowType) {
            e.preventDefault();
            toastr.error('Por favor complete los campos obligatorios', 'Error');
            return false;
        }
    });

    // Show/hide fields based on workflow type
    $('select[name="workflow_type"]').on('change', function() {
        const type = $(this).val();

        if (type === 'internal') {
            $('input[name="webhook_url"]').closest('.mb-3').hide();
            $('input[name="external_workflow_id"]').closest('.mb-3').hide();
        } else {
            $('input[name="webhook_url"]').closest('.mb-3').show();
            $('input[name="external_workflow_id"]').closest('.mb-3').show();
        }
    }).trigger('change');
});
</script>
@endpush

@extends('layouts.theme')

@section('title', 'Crear Conversación - Helpdesk')

@section('page_header')
    @include('core::components.card', ['title' => 'Crear Conversación'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <form method="POST" action="{{ route('manager.helpdesk.conversations.store') }}">
            @csrf

            <!-- Main Card -->
            <div class="card">
                <!-- Header Section -->
                <div class="card-header p-4 border-bottom border-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">Crear nueva conversación</h5>
                            <p class="small mb-0 text-muted">Inicia una nueva conversación de soporte con un cliente</p>
                        </div>
                    </div>
                </div>

                <!-- Conversation Details -->
                <div class="card-body border-bottom">
                    <div class="mb-3">
                        <h6 class="mb-1 fw-bold">Detalles de la conversación</h6>
                        <p class="text-muted mb-0">Información básica de la conversación</p>
                    </div>

                    <div class="row g-3">
                        <!-- Customer Selection -->
                        <div class="col-md-6">
                            <label for="customer_id" class="form-label fw-semibold">
                                Cliente <span class="text-danger">*</span>
                            </label>
                            @if($customer)
                                <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                                <div class="form-control-plaintext">
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ $customer->getAvatarUrl() }}" class="rounded-circle" width="32" height="32" alt="{{ $customer->name }}">
                                        <div>
                                            <div class="fw-semibold">{{ $customer->name }}</div>
                                            <small class="text-muted">{{ $customer->email }}</small>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <select id="customer_id"
                                        name="customer_id"
                                        class="form-select select2 @error('customer_id') is-invalid @enderror"
                                        required>
                                    <option value="">— Seleccionar cliente —</option>
                                    @foreach(\Modules\Helpdesk\Models\Customer::orderBy('name')->get() as $customerOption)
                                        <option value="{{ $customerOption->id }}" {{ old('customer_id') == $customerOption->id ? 'selected' : '' }}>
                                            {{ $customerOption->name }} ({{ $customerOption->email }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('customer_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    ¿No encuentras el cliente?
                                    <a href="{{ route('manager.helpdesk.customers.create') }}" target="_blank">Crear nuevo cliente</a>
                                </small>
                            @endif
                        </div>

                        <!-- Status -->
                        <div class="col-md-6">
                            <label for="status_id" class="form-label fw-semibold">
                                Estado inicial <span class="text-danger">*</span>
                            </label>
                            <select id="status_id"
                                    name="status_id"
                                    class="form-select @error('status_id') is-invalid @enderror"
                                    required>
                                <option value="">— Seleccionar estado —</option>
                                @foreach($statuses as $status)
                                    <option value="{{ $status->id }}"
                                            {{ old('status_id', $status->is_default ? $status->id : '') == $status->id ? 'selected' : '' }}>
                                        {{ $status->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Subject -->
                        <div class="col-12">
                            <label for="subject" class="form-label fw-semibold">
                                Asunto <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   id="subject"
                                   name="subject"
                                   class="form-control @error('subject') is-invalid @enderror"
                                   placeholder="Ej: Consulta sobre facturación"
                                   value="{{ old('subject') }}"
                                   required
                                   autofocus>
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Resumen breve del tema de la conversación
                            </small>
                        </div>

                        <!-- Priority -->
                        <div class="col-md-6">
                            <label for="priority" class="form-label fw-semibold">
                                Prioridad <span class="text-danger">*</span>
                            </label>
                            <select id="priority"
                                    name="priority"
                                    class="form-select @error('priority') is-invalid @enderror"
                                    required>
                                <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>
                                    🟢 Baja
                                </option>
                                <option value="normal" {{ old('priority', 'normal') === 'normal' ? 'selected' : '' }}>
                                    🔵 Normal
                                </option>
                                <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>
                                    🟡 Alta
                                </option>
                                <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>
                                    🔴 Urgente
                                </option>
                            </select>
                            @error('priority')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="card-footer d-flex justify-content-between align-items-center p-4">
                    <a href="{{ route('manager.helpdesk.conversations.index') }}" class="btn btn-light">
                        <i class="fa-solid fa-arrow-left me-1"></i> Cancelar
                    </a>

                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-check me-1"></i> Crear conversación
                    </button>
                </div>
            </div>
        </form>
    </div>

@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Initialize Select2 for customer dropdown
        $('.select2').select2({
            placeholder: '— Seleccionar cliente —',
            allowClear: true
        });
    });
</script>
@endsection

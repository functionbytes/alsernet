@extends('layouts.theme')


@push('css')
    <link rel="stylesheet" href="{{ asset('modules/helpdesktickets/css/helpdesktickets-ui.css') }}?v={{ @filemtime(public_path('modules/helpdesktickets/css/helpdesktickets-ui.css')) }}">
@endpush
@section('title', 'Nuevo ticket')

@section('page_header')
    @include('core::components.card', ['title' => 'Nuevo ticket'])
@endsection

@section('content')
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('agent.helpdesk.tickets.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h5 class="mb-0 fw-bold flex-grow-1">Nuevo ticket</h5>
        <a href="{{ route('manager.helpdesk.ticket-templates.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-file-lines me-1"></i> Mis plantillas
        </a>
    </div>

    <div class="card shadow-sm hdt-form-narrow">
        <div class="card-body">
            <form action="{{ route('agent.helpdesk.tickets.store') }}" method="POST">
                @csrf

                @if($templates->isNotEmpty())
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Usar plantilla <span class="text-muted fw-normal">(opcional)</span></label>
                        <select id="templateSelect" class="form-select select2">
                            <option value="">Sin plantilla — empezar en blanco</option>
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}">{{ $template->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Autorrellena asunto, descripcion, categoria y prioridad. Variables como @{{ticket_number}} o @{{customer_name}} se rellenan solas al crear el ticket.</small>
                    </div>
                @endif

                <div class="mb-3">
                    <label class="form-label fw-semibold">Asunto <span class="text-brand">*</span></label>
                    <input type="text" name="subject" class="form-control @error('subject') is-invalid @enderror"
                        value="{{ old('subject') }}" required>
                    @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Descripción <span class="text-brand">*</span></label>
                    <textarea name="description" rows="5"
                        class="form-control @error('description') is-invalid @enderror" required>{{ old('description') }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Categoría <span class="text-brand">*</span></label>
                        <select name="category_id" id="categorySelect" class="form-select select2 @error('category_id') is-invalid @enderror" required>
                            <option value="">Seleccionar...</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" @selected(old('category_id') == $cat->id)>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Prioridad</label>
                        <select name="priority" id="prioritySelect" class="form-select select2">
                            @foreach(['low' => 'Baja', 'normal' => 'Normal', 'high' => 'Alta', 'urgent' => 'Urgente'] as $val => $label)
                                <option value="{{ $val }}" @selected(old('priority', 'normal') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Cliente</label>
                    <select name="customer_id" class="form-select select2">
                        <option value="">Sin asignar</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>
                                {{ $customer->name }} ({{ $customer->email }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Crear ticket
                </button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
{{-- Solo datos: las plantillas disponibles. La lógica entera vive en
     agent-ticket-create-form.js. --}}
<script>
window.hdtAgentTicketCreateConfig = {
    templates: @json($templates->keyBy('id')),
};
</script>
<script src="{{ asset('modules/helpdesktickets/js/agent-ticket-create-form.js') }}?v={{ @filemtime(public_path('modules/helpdesktickets/js/agent-ticket-create-form.js')) }}"></script>
@endpush

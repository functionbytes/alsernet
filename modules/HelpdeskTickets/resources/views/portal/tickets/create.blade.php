@extends('helpdesktickets::portal.layout')

@section('content')
    <div class="mb-3">
        <a href="{{ route('portal.tickets') }}" class="text-muted">
            <i class="fas fa-arrow-left me-1"></i>Volver a mis tickets
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h5 class="card-title mb-4">
                        <i class="fas fa-plus-circle me-2 text-primary"></i>Nuevo ticket de soporte
                    </h5>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('portal.tickets.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label for="subject" class="form-label">Asunto <span class="text-brand">*</span></label>
                            <input
                                type="text"
                                id="subject"
                                name="subject"
                                class="form-control @error('subject') is-invalid @enderror"
                                value="{{ old('subject') }}"
                                maxlength="255"
                                required
                                placeholder="Breve descripción de tu problema"
                            >
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div id="kb-suggestions" class="mt-2"></div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Descripción <span class="text-brand">*</span></label>
                            <textarea
                                id="description"
                                name="description"
                                class="form-control @error('description') is-invalid @enderror"
                                rows="6"
                                maxlength="5000"
                                required
                                placeholder="Da todos los detalles posibles..."
                            >{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if ($categories->isNotEmpty())
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Categoría</label>
                                <select id="category_id" name="category_id" class="form-select">
                                    <option value="">— Selecciona una categoría —</option>
                                    @foreach ($categories as $category)
                                        <option
                                            value="{{ $category->id }}"
                                            {{ old('category_id') == $category->id ? 'selected' : '' }}
                                        >{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        @if ($attachmentSettings['user_upload_enabled'] ?? true)
                            @php
                                $attachmentMaxMb = ($attachmentSettings['max_kilobytes'] ?? 25600) / 1024;
                                $attachmentAccept = collect($attachmentSettings['extensions'] ?? [])->map(fn ($extension) => '.'.$extension)->implode(',');
                            @endphp
                            <div class="mb-3">
                                <label class="form-label">Adjuntos <span class="text-muted">(opcional, máx. {{ rtrim(rtrim(number_format($attachmentMaxMb, 2, '.', ''), '0'), '.') }}MB cada uno)</span></label>
                                <input type="file" name="attachments[]" class="form-control @error('attachments.*') is-invalid @enderror" multiple accept="{{ $attachmentAccept }}">
                                <div class="form-text">Permitidos: {{ strtoupper(implode(', ', $attachmentSettings['extensions'] ?? [])) }}. Máx. {{ rtrim(rtrim(number_format($attachmentMaxMb, 2, '.', ''), '0'), '.') }}MB por archivo.</div>
                                @error('attachments.*')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif

                        <div class="mb-3">
                            <label for="cc" class="form-label">Enviar copia a <span class="text-muted">(opcional)</span></label>
                            <input type="text" id="cc" name="cc" class="form-control @error('cc') is-invalid @enderror"
                                   value="{{ old('cc') }}" maxlength="500" placeholder="compañero@empresa.com, otro@empresa.com">
                            <div class="form-text">Hasta 5 correos separados por coma. Recibirán también nuestras respuestas.</div>
                            @error('cc')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="priority" class="form-label">Prioridad</label>
                            <select id="priority" name="priority" class="form-select">
                                <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Baja</option>
                                <option value="normal" {{ old('priority', 'normal') === 'normal' ? 'selected' : '' }}>Normal</option>
                                <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>Alta</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            Enviar ticket
                        </button>
                        <a href="{{ route('portal.tickets') }}" class="btn btn-outline-secondary ms-2">Cancelar</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection


@push('scripts')
{{-- Solo datos: la URL del endpoint de sugerencias. La lógica entera vive en
     portal-ticket-create-form.js. --}}
<script>
window.hdtPortalTicketCreateConfig = {
    suggestUrl: @json(route('portal.tickets.suggest-articles')),
    clickUrl: @json(route('portal.tickets.suggest-articles.click')),
};
</script>
<script src="{{ asset('modules/helpdesktickets/js/portal-ticket-create-form.js') }}"></script>
@endpush

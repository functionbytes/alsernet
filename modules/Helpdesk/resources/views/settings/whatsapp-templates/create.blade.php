@extends('layouts.theme')

@section('title', 'Nueva plantilla de WhatsApp')

@section('page_header')
    @include('core::components.card', ['title' => 'Nueva plantilla de WhatsApp'])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="card">

        <div class="card-header p-4 border-bottom border-light">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Nueva plantilla de WhatsApp</h5>
                    <p class="small mb-0 text-muted">Se envía directamente a Meta para revisión — no hace falta entrar a WhatsApp Business Manager</p>
                </div>
                <a href="{{ route('settings.helpdesk.whatsapp-templates.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>

        <div class="card-body p-4">
            <div class="alert alert-info d-flex gap-2">
                <i class="fas fa-circle-info mt-1"></i>
                <div>
                    Meta revisa cada plantilla antes de aprobarla (puede tardar de minutos a días). Quedará como <strong>"Pendiente"</strong> hasta entonces — usa "Sincronizar desde Meta" en el listado para ver el estado actualizado.
                </div>
            </div>

            <form method="POST" action="{{ route('settings.helpdesk.whatsapp-templates.store') }}">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label fw-semibold">Nombre técnico <span class="text-danger">*</span></label>
                    <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name') }}" placeholder="bienvenida_cliente_v2" required>
                    <small class="text-muted">Solo minúsculas, números y guion bajo. Es el identificador que Meta usa para enviar la plantilla.</small>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="language" class="form-label fw-semibold">Idioma <span class="text-danger">*</span></label>
                        <select id="language" name="language" class="form-select @error('language') is-invalid @enderror" required>
                            @foreach(['es' => 'Español', 'en' => 'Inglés', 'en_US' => 'Inglés (EE. UU.)', 'en_GB' => 'Inglés (Reino Unido)', 'pt_BR' => 'Portugués (Brasil)', 'pt_PT' => 'Portugués (Portugal)', 'fr' => 'Francés', 'de' => 'Alemán', 'it' => 'Italiano'] as $code => $label)
                                <option value="{{ $code }}" @selected(old('language', 'es') === $code)>{{ $label }} ({{ $code }})</option>
                            @endforeach
                        </select>
                        @error('language')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="category" class="form-label fw-semibold">Categoría <span class="text-danger">*</span></label>
                        <select id="category" name="category" class="form-select @error('category') is-invalid @enderror" required>
                            <option value="utility" @selected(old('category') === 'utility')>Utilidad</option>
                            <option value="marketing" @selected(old('category') === 'marketing')>Marketing</option>
                            <option value="authentication" @selected(old('category') === 'authentication')>Autenticación</option>
                        </select>
                        @error('category')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="body" class="form-label fw-semibold">Cuerpo del mensaje <span class="text-danger">*</span></label>
                    <textarea id="body" name="body" class="form-control @error('body') is-invalid @enderror"
                        rows="5" maxlength="1024" placeholder="Hola @{{1}}, gracias por contactarnos. Tu numero de caso es @{{2}}.">{{ old('body') }}</textarea>
                    <small class="text-muted">Usa <code>@{{1}}</code>, <code>@{{2}}</code>... para variables, en orden. Máximo 1024 caracteres.</small>
                    @error('body')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="body_examples" class="form-label fw-semibold">Ejemplos de las variables</label>
                    <input type="text" id="body_examples" name="body_examples" class="form-control @error('body_examples') is-invalid @enderror"
                        value="{{ old('body_examples') }}" placeholder="Ana, CASE-1234">
                    <small class="text-muted">Un valor de ejemplo por cada variable del cuerpo, separados por coma, en el mismo orden. Obligatorio si el cuerpo tiene variables — Meta lo exige para aprobar la plantilla.</small>
                    @error('body_examples')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fab fa-whatsapp"></i> Enviar a Meta para revisión
                </button>
            </form>
        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif

    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush

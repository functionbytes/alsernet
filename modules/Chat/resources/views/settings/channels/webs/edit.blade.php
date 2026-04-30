@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Editar widget web'])

    @include('core::components.alerts')

    <div class="widget-content searchable-container list">

        <div class="row g-4 align-items-start">

            {{-- Columna izquierda --}}
            <div class="col-lg-8">
                <form action="{{ route('settings.chat.channels.webs.update', $webWidget) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card">

                        {{-- Información del canal --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Información del canal</h6>
                            <p class="text-muted mb-3">Actualiza el nombre y la URL del sitio web donde se muestra el widget.</p>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                           id="name" name="name" value="{{ old('name', $webWidget->inbox->name) }}" required>
                                    @error('name')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="website_url" class="form-label fw-semibold">URL del sitio web <span class="text-danger">*</span></label>
                                    <input type="url" class="form-control @error('website_url') is-invalid @enderror"
                                           id="website_url" name="website_url" value="{{ old('website_url', $webWidget->website_url) }}"
                                           placeholder="https://ejemplo.com" required>
                                    @error('website_url')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="widget_color" class="form-label fw-semibold">Color del widget</label>
                                    <input type="color" class="form-control form-control-color @error('widget_color') is-invalid @enderror"
                                           id="widget_color" name="widget_color" value="{{ old('widget_color', $webWidget->widget_color) }}">
                                    @error('widget_color')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="widget_position" class="form-label fw-semibold">Posición</label>
                                    <select class="form-control select2 @error('widget_position') is-invalid @enderror"
                                            id="widget_position" name="widget_position">
                                        <option value="right" {{ old('widget_position', $webWidget->widget_position ?? 'right') === 'right' ? 'selected' : '' }}>Derecha</option>
                                        <option value="left" {{ old('widget_position', $webWidget->widget_position) === 'left' ? 'selected' : '' }}>Izquierda</option>
                                    </select>
                                    @error('widget_position')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr class="my-0">

                        {{-- Apariencia --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Apariencia</h6>
                            <p class="text-muted mb-3">Personaliza los textos que verán los visitantes al interactuar con el widget.</p>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="welcome_title" class="form-label fw-semibold">Título de bienvenida</label>
                                    <input type="text" class="form-control @error('welcome_title') is-invalid @enderror"
                                           id="welcome_title" name="welcome_title" value="{{ old('welcome_title', $webWidget->welcome_title) }}"
                                           placeholder="¡Hola!">
                                    @error('welcome_title')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="welcome_tagline" class="form-label fw-semibold">Subtítulo de bienvenida</label>
                                    <input type="text" class="form-control @error('welcome_tagline') is-invalid @enderror"
                                           id="welcome_tagline" name="welcome_tagline" value="{{ old('welcome_tagline', $webWidget->welcome_tagline) }}"
                                           placeholder="¿En qué podemos ayudarte?">
                                    @error('welcome_tagline')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="widget_bubble_launcher_title" class="form-label fw-semibold">Título del botón</label>
                                    <input type="text" class="form-control @error('widget_bubble_launcher_title') is-invalid @enderror"
                                           id="widget_bubble_launcher_title" name="widget_bubble_launcher_title"
                                           value="{{ old('widget_bubble_launcher_title', $webWidget->widget_bubble_launcher_title) }}"
                                           placeholder="Chatea con nosotros">
                                    @error('widget_bubble_launcher_title')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="show_powered_by"
                                               name="show_powered_by" value="1"
                                               {{ old('show_powered_by', $webWidget->show_powered_by ?? true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="show_powered_by">
                                            Mostrar marca "Powered by"
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-0">

                        {{-- Formulario previo --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Formulario previo al chat</h6>
                            <p class="text-muted mb-3">Configura qué información recopilar del visitante antes de iniciar la conversación.</p>

                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="pre_chat_form_enabled"
                                               name="pre_chat_form_enabled" value="1"
                                               {{ old('pre_chat_form_enabled', $webWidget->pre_chat_form_enabled) ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="pre_chat_form_enabled">
                                            Habilitar formulario previo
                                        </label>
                                    </div>
                                    <small class="text-muted d-block mb-3">Recopilar nombre, email u otros datos antes de iniciar la conversación.</small>
                                </div>

                                <div class="col-12">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="offline_message_enabled"
                                               name="offline_message_enabled" value="1"
                                               {{ old('offline_message_enabled', $webWidget->offline_message_enabled ?? true) ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="offline_message_enabled">
                                            Habilitar mensajes sin conexión
                                        </label>
                                    </div>
                                    <small class="text-muted d-block">Permitir que los visitantes dejen mensajes cuando no hay agentes disponibles.</small>
                                </div>

                                <div class="col-12" id="offline-message-wrapper"
                                     class="{{ old('offline_message_enabled', $webWidget->offline_message_enabled ?? true) ? '' : 'd-none' }}">
                                    <label for="offline_message" class="form-label fw-semibold">Mensaje sin conexión</label>
                                    <textarea class="form-control @error('offline_message') is-invalid @enderror"
                                              id="offline_message" name="offline_message" rows="3"
                                              placeholder="Actualmente no estamos disponibles. Déjanos un mensaje.">{{ old('offline_message', $webWidget->offline_message) }}</textarea>
                                    @error('offline_message')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary w-100">
                                Guardar cambios
                            </button>
                            <a href="{{ route('settings.chat.channels.webs.index') }}" class="btn btn-outline-secondary w-100 mt-2">
                                Cancelar
                            </a>
                        </div>

                    </div>
                </form>
            </div>

            {{-- Columna derecha --}}
            <div class="col-lg-4">

                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Estado del widget</h6>
                        <p class="text-muted mb-3">Información actual del canal configurado.</p>
                        <ul class="text-muted ps-3 mb-0">
                            <li class="mb-1">Nombre: <strong>{{ $webWidget->inbox->name }}</strong></li>
                            <li class="mb-1">Sitio web: <strong>{{ Str::limit($webWidget->website_url, 40) }}</strong></li>
                            <li>Posición: <strong>{{ $webWidget->widget_position === 'left' ? 'Izquierda' : 'Derecha' }}</strong></li>
                        </ul>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Información importante</h6>
                        <p class="text-muted mb-3">Los cambios se aplican inmediatamente tras guardar.</p>
                        <ul class="text-muted ps-3 mb-3">
                            <li class="mb-1">El código de instalación <strong>no cambia</strong> al editar</li>
                            <li>Puedes cambiar color y posición en cualquier momento</li>
                        </ul>
                        <div class="alert alert-info border-0 mb-0">
                            <small>Prueba el widget en tu sitio web después de guardar para verificar los cambios.</small>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-bold">Código de instalación</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-2">Pega este código antes de <code>&lt;/body&gt;</code> en tu sitio web.</p>
                        <div class="d-flex align-items-start gap-2">
                            <code id="install-code" class="flex-grow-1 p-2 bg-light border rounded d-block" style="font-size:.75rem;word-break:break-all">
                                &lt;script src="{{ url('/widget.js') }}" data-inbox="{{ $webWidget->inbox->uuid ?? '' }}"&gt;&lt;/script&gt;
                            </code>
                            <button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0" onclick="copyInstallCode()">
                                Copiar
                            </button>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    function toggleOfflineMessage() {
        $('#offline-message-wrapper').toggle($('#offline_message_enabled').is(':checked'));
    }

    $('#offline_message_enabled').on('change', toggleOfflineMessage);

    @if(session('success'))
        toastr.success('{{ session('success') }}');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}');
    @endif
});

function copyInstallCode() {
    const text = document.getElementById('install-code').innerText.trim();
    navigator.clipboard.writeText(text).then(() => toastr.success('Código copiado'));
}
</script>
@endpush

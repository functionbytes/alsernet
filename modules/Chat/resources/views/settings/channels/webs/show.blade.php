@extends('layouts.theme')

@section('content')
@include('core::components.alerts')

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0">{{ $webWidget->inbox?->name ?? 'Widget Web #' . $webWidget->id }}</h5>
            <div class="d-flex gap-2">
                <a href="{{ route('settings.chat.channels.webs.edit', $webWidget) }}" class="btn btn-warning">
                    Editar
                </a>
                <a href="{{ route('settings.chat.channels.webs.index') }}" class="btn btn-secondary">
                    Volver
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Código de instalación -->
        <div class="card mb-4">
            <div class="card-header bg-info-subtle">
                <h6 class="mb-0">Código de instalación</h6>
            </div>
            <div class="card-body">
                <p class="mb-3">
                    Copia y pega el siguiente código justo antes de la etiqueta de cierre <code>&lt;/body&gt;</code>
                    en cada página donde desees que aparezca el widget de chat.
                </p>

                <div class="bg-dark text-white p-3 rounded position-relative">
                    <button class="btn btn-sm btn-light position-absolute top-0 end-0 m-2 copy-embed-btn">
                        <i class="fas fa-copy"></i> Copiar
                    </button>
                    <pre class="mb-0 text-white"><code id="embed-code">&lt;script src="{{ route('widget.script', $webWidget->website_token) }}"&gt;&lt;/script&gt;</code></pre>
                </div>

                <div class="alert alert-info mt-3 mb-0">
                    <i class="fas fa-info-circle"></i>
                    El widget se cargará automáticamente con todas tus configuraciones personalizadas.
                </div>
            </div>
        </div>

        <!-- Configuración del widget -->
        <div class="card mb-4">
            <div class="card-header bg-info-subtle">
                <h6 class="mb-0">Configuración del widget</h6>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs mb-3" id="configTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic" type="button">
                            General
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="appearance-tab" data-bs-toggle="tab" data-bs-target="#appearance" type="button">
                            Apariencia
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="features-tab" data-bs-toggle="tab" data-bs-target="#features" type="button">
                            Características
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- General -->
                    <div class="tab-pane fade show active" id="basic" role="tabpanel">
                        <table class="table table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <td class="fw-bold" width="200">URL del sitio:</td>
                                    <td>
                                        <a href="{{ $webWidget->website_url }}" target="_blank" class="text-decoration-none">
                                            {{ $webWidget->website_url }}
                                            <i class="fas fa-external-link-alt small"></i>
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Website Token:</td>
                                    <td>
                                        <code>{{ $webWidget->website_token }}</code>
                                        <button class="btn btn-sm btn-outline-secondary ms-2 copy-token-btn"
                                                data-copy="{{ $webWidget->website_token }}">
                                            <i class="fas fa-copy"></i> Copiar
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Creado:</td>
                                    <td>{{ $webWidget->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Apariencia -->
                    <div class="tab-pane fade" id="appearance" role="tabpanel">
                        <table class="table table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <td class="fw-bold" width="200">Color del widget:</td>
                                    <td>
                                        <span class="d-inline-block rounded" style="width: 30px; height: 20px; background-color: {{ $webWidget->widget_color }}; border: 1px solid #ddd;"></span>
                                        <code class="ms-1">{{ $webWidget->widget_color }}</code>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Posición:</td>
                                    <td>
                                        @if(($webWidget->widget_position ?? 'right') === 'right')
                                            <span class="badge bg-success-subtle text-success">Derecha</span>
                                        @else
                                            <span class="badge bg-info-subtle text-info">Izquierda</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Título de bienvenida:</td>
                                    <td>{{ $webWidget->welcome_title ?? 'No configurado' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Subtítulo:</td>
                                    <td>{{ $webWidget->welcome_tagline ?? 'No configurado' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Título del botón:</td>
                                    <td>{{ $webWidget->widget_bubble_launcher_title ?? 'No configurado' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Mostrar marca:</td>
                                    <td>
                                        @if($webWidget->show_powered_by ?? true)
                                            <span class="badge bg-success-subtle text-success">Sí</span>
                                        @else
                                            <span class="badge bg-secondary">No</span>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Características -->
                    <div class="tab-pane fade" id="features" role="tabpanel">
                        <table class="table table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <td class="fw-bold" width="200">Formulario previo:</td>
                                    <td>
                                        @if($webWidget->pre_chat_form_enabled)
                                            <span class="badge bg-success-subtle text-success">Habilitado</span>
                                        @else
                                            <span class="badge bg-secondary">Deshabilitado</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Mensajes sin conexión:</td>
                                    <td>
                                        @if($webWidget->offline_message_enabled ?? true)
                                            <span class="badge bg-success-subtle text-success">Habilitado</span>
                                        @else
                                            <span class="badge bg-secondary">Deshabilitado</span>
                                        @endif
                                    </td>
                                </tr>
                                @if($webWidget->offline_message_enabled && $webWidget->offline_message)
                                <tr>
                                    <td class="fw-bold">Mensaje sin conexión:</td>
                                    <td>{{ $webWidget->offline_message }}</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Vista previa -->
        <div class="card mb-3 sticky-top" style="top: 20px;">
            <div class="card-header bg-info-subtle">
                <h6 class="mb-0">Vista previa</h6>
            </div>
            <div class="card-body position-relative" style="min-height: 350px; background: #f0f0f0;">
                <div class="position-relative" style="height: 300px;">
                    <div class="position-absolute bottom-0" style="{{ ($webWidget->widget_position ?? 'right') === 'left' ? 'left: 20px;' : 'right: 20px;' }}">
                        @if($webWidget->widget_bubble_launcher_title)
                        <div class="small mb-2 {{ ($webWidget->widget_position ?? 'right') === 'left' ? 'text-start' : 'text-end' }}">
                            {{ $webWidget->widget_bubble_launcher_title }}
                        </div>
                        @endif
                        <div class="rounded-circle d-flex align-items-center justify-content-center shadow"
                             style="width: 60px; height: 60px; background-color: {{ $webWidget->widget_color }}; color: white; font-size: 24px;">
                            <i class="fas fa-comments"></i>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <small class="text-muted">Así aparecerá el widget en tu sitio web</small>
                </div>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="card">
            <div class="card-header bg-info-subtle">
                <h6 class="mb-0">Estadísticas</h6>
            </div>
            <div class="card-body">
                @if($webWidget->inbox)
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total conversaciones:</span>
                        <strong>{{ $webWidget->inbox->conversations()->count() }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Conversaciones abiertas:</span>
                        <strong>{{ $webWidget->inbox->conversations()->where('status', 'open')->count() }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Total mensajes:</span>
                        <strong>{{ $webWidget->inbox->messages()->count() }}</strong>
                    </div>
                @else
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Este widget no tiene un inbox asociado. Por favor, contacta al administrador.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.copy-embed-btn').on('click', function() {
        var text = $('#embed-code').text().trim();
        copyToClipboard(text, $(this));
    });

    $('.copy-token-btn').on('click', function() {
        var text = $(this).data('copy');
        copyToClipboard(text, $(this));
    });

    function copyToClipboard(text, $button) {
        var $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(text).select();

        try {
            document.execCommand('copy');
            var originalHtml = $button.html();
            $button.html('<i class="fas fa-check"></i> ¡Copiado!');
            $button.addClass('btn-success').removeClass('btn-light btn-outline-secondary');

            setTimeout(function() {
                $button.html(originalHtml);
                $button.removeClass('btn-success').addClass('btn-light btn-outline-secondary');
            }, 2000);
        } catch (err) {
            alert('Error al copiar. Por favor, copia manualmente.');
        }

        $temp.remove();
    }
});
</script>
@endpush

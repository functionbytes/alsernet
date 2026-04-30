@extends('layouts.theme')

@section('title', 'Ver plantilla de mensaje')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">{{ $template->name }}</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('settings.chat.message.edit', $template) }}" class="btn btn-primary">
                <i class="fas fa-edit me-1"></i> Editar
            </a>
            <a href="{{ route('settings.chat.message.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
        </div>
    </div>

    @include('core::components.alerts')

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">Detalles de la plantilla</h6>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="col-form-label fw-bold">Categoria</label>
                            <div>
                                @if($template->category)
                                    <span class="badge bg-secondary">{{ $categories[$template->category] ?? $template->category }}</span>
                                @else
                                    <span class="text-muted">Sin categoria</span>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="col-form-label fw-bold">Atajo</label>
                            <div>
                                @if($template->shortcut)
                                    <code class="bg-light px-2 py-1 rounded">/{{ $template->shortcut }}</code>
                                @else
                                    <span class="text-muted">Sin atajo</span>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="col-form-label fw-bold">Tipo</label>
                            <div>
                                @if($template->is_public)
                                    <span class="badge bg-success">Publica</span>
                                    <small class="text-muted d-block mt-1">Disponible para todos los miembros del equipo</small>
                                @else
                                    <span class="badge bg-info">Privada</span>
                                    <small class="text-muted d-block mt-1">Solo visible para ti</small>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="col-form-label fw-bold">Creado por</label>
                            <div>
                                @if($template->user)
                                    {{ $template->user->name }}
                                @else
                                    <span class="text-muted">Desconocido</span>
                                @endif
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="col-form-label fw-bold">Contenido de la plantilla</label>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <pre class="mb-0" style="white-space: pre-wrap; font-family: 'Courier New', monospace;">{{ $template->content }}</pre>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="col-form-label fw-bold">Vista previa con datos de ejemplo</label>
                            <div class="card border-primary">
                                <div class="card-body" id="preview-content">
                                    <!-- Populated by JavaScript -->
                                </div>
                            </div>
                        </div>

                        @if(isset($usedVariables) && $usedVariables->isNotEmpty())
                        <div class="col-12">
                            <label class="col-form-label fw-bold">Variables usadas en esta plantilla</label>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($usedVariables as $variable)
                                    <span class="badge bg-light text-dark border">
                                        <code>{{ '{' . '{' . $variable . '}' . '}' }}</code>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">Estadisticas de uso</h6>

                    <div class="row text-center g-3">
                        <div class="col-md-4">
                            <div>
                                <h3 class="text-primary mb-1">{{ number_format($template->usage_count) }}</h3>
                                <small class="text-muted">Veces utilizada</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div>
                                @if($template->last_used_at)
                                    <h6 class="mb-1">{{ $template->last_used_at->format('d/m/Y') }}</h6>
                                    <small class="text-muted d-block">{{ $template->last_used_at->format('H:i') }}</small>
                                @else
                                    <span class="badge bg-secondary">Nunca usada</span>
                                @endif
                                <small class="text-muted d-block mt-1">Ultimo uso</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div>
                                <h6 class="mb-1">{{ $template->created_at->format('d/m/Y') }}</h6>
                                <small class="text-muted d-block">{{ $template->created_at->diffForHumans() }}</small>
                                <small class="text-muted d-block mt-1">Fecha de creacion</small>
                            </div>
                        </div>
                    </div>

                    @if($template->usage_count > 0)
                        <div class="alert alert-success mt-3 mb-0">
                            <i class="fas fa-check-circle me-1"></i>
                            <strong>¡Plantilla popular!</strong>
                            Esta plantilla ha sido usada {{ number_format($template->usage_count) }} vez/veces.
                        </div>
                    @else
                        <div class="alert alert-info mt-3 mb-0">
                            <i class="fas fa-info-circle me-1"></i>
                            Esta plantilla aun no ha sido usada. ¡Pruebala en una conversacion!
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="fas fa-bolt me-1"></i> Acciones rapidas</h6>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-primary test-template">
                            <i class="fas fa-play-circle me-1"></i> Probar plantilla
                        </button>
                        <a href="{{ route('settings.chat.message.edit', $template) }}" class="btn btn-outline-primary">
                            <i class="fas fa-edit me-1"></i> Editar plantilla
                        </a>
                        <button type="button" class="btn btn-secondary copy-content">
                            <i class="fas fa-clipboard me-1"></i> Copiar contenido
                        </button>
                        <button type="button" class="btn btn-outline-info duplicate-template">
                            <i class="fas fa-copy me-1"></i> Duplicar
                        </button>
                        <hr class="my-2">
                        <button type="button" class="btn btn-outline-danger delete-btn"
                                data-url="{{ route('settings.chat.message.destroy', $template) }}">
                            <i class="fas fa-trash me-1"></i> Eliminar plantilla
                        </button>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="fas fa-question-circle me-1"></i> Como usar</h6>
                    <ol class="small mb-0 ps-3">
                        <li class="mb-2">Abre cualquier conversacion</li>
                        <li class="mb-2">
                            @if($template->shortcut)
                                Escribe <code>/{{ $template->shortcut }}</code> en el cuadro de mensaje
                            @else
                                Busca "{{ $template->name }}" en las plantillas
                            @endif
                        </li>
                        <li class="mb-2">La plantilla sera insertada con las variables reemplazadas</li>
                        <li>Revisa y envia el mensaje</li>
                    </ol>

                    <div class="alert alert-info small mb-0 mt-3">
                        <i class="fas fa-lightbulb me-1"></i>
                        <strong>Consejo:</strong> Las variables como <code>@{{contact.name}}</code> seran reemplazadas automaticamente con datos reales.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar eliminacion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                ¿Estas seguro de que deseas eliminar esta plantilla de mensaje? Esta accion no se puede deshacer.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="delete-form" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="testModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Probar plantilla</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">Asi es como se vera la plantilla con datos de ejemplo:</p>
                <div class="card bg-light">
                    <div class="card-body" id="test-preview-content">
                        <!-- Populated by JavaScript -->
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const templateContent = @json($template->content);

    function generatePreview() {
        // Escape HTML to prevent XSS
        const escapeHtml = (text) => {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        };

        // Escape template content first
        let content = escapeHtml(templateContent);

        // Now replace variables with example values (safe since content is already escaped)
        content = content
            .replace(/\{\{contact\.name\}\}/g, '<span class="badge bg-primary">John Doe</span>')
            .replace(/\{\{contact\.email\}\}/g, '<span class="badge bg-primary">john@example.com</span>')
            .replace(/\{\{contact\.phone\}\}/g, '<span class="badge bg-primary">+1234567890</span>')
            .replace(/\{\{agent\.name\}\}/g, '<span class="badge bg-success">Agente de Soporte</span>')
            .replace(/\{\{agent\.email\}\}/g, '<span class="badge bg-success">agente@empresa.com</span>')
            .replace(/\{\{conversation\.id\}\}/g, '<span class="badge bg-info">12345</span>')
            .replace(/\{\{conversation\.status\}\}/g, '<span class="badge bg-info">abierto</span>')
            .replace(/\{\{account\.name\}\}/g, '<span class="badge bg-warning text-dark">Nombre Empresa</span>')
            .replace(/\{\{system\.date\}\}/g, '<span class="badge bg-secondary">2024-01-15</span>')
            .replace(/\{\{system\.time\}\}/g, '<span class="badge bg-secondary">14:30:00</span>')
            .replace(/\{\{system\.datetime\}\}/g, '<span class="badge bg-secondary">2024-01-15 14:30:00</span>')
            .replace(/\n/g, '<br>');

        return content;
    }

    $('#preview-content').html(generatePreview());

    $('.test-template').on('click', function() {
        $('#test-preview-content').html(generatePreview());
        new bootstrap.Modal($('#testModal')).show();
    });

    $('.copy-content').on('click', function() {
        const $btn = $(this);
        const originalHtml = $btn.html();

        navigator.clipboard.writeText(templateContent).then(function() {
            $btn.html('<i class="fas fa-check-circle me-1"></i> ¡Copiado!');
            $btn.removeClass('btn-outline-secondary').addClass('btn-success');

            setTimeout(function() {
                $btn.html(originalHtml);
                $btn.removeClass('btn-success').addClass('btn-outline-secondary');
            }, 2000);
        }).catch(function(err) {
            console.error('Error al copiar:', err);
            toastr.error('No se pudo copiar el contenido. Intenta nuevamente.');
        });
    });

    $('.duplicate-template').on('click', function() {
        if (confirm('¿Crear una copia de esta plantilla?')) {
            const params = new URLSearchParams({
                name: '{{ $template->name }} (Copia)',
                content: templateContent,
                category: '{{ $template->category }}',
                is_public: '{{ $template->is_public ? '1' : '0' }}'
            });
            window.location.href = '{{ route("settings.chat.message.create") }}?' + params.toString();
        }
    });

    $('.delete-btn').on('click', function() {
        const url = $(this).data('url');
        $('#delete-form').attr('action', url);
        new bootstrap.Modal($('#deleteModal')).show();
    });
});
</script>
@endpush

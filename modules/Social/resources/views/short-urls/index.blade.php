@extends('layouts.theme')

@section('title', 'URLs Acortadas')

@section('content')

    <div class="widget-content searchable-container list">

        <!-- Alerts -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Main Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">
                            <i class="fas fa-link me-2"></i>URLs Acortadas
                        </h5>
                        <p class="small mb-0 text-muted">Gestiona tus enlaces acortados con seguimiento de clics</p>
                    </div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUrlModal">
                        <i class="fas fa-plus me-1"></i> Acortar URL
                    </button>
                </div>
            </div>

            <!-- Short URLs Table -->
            <div class="card-body">
                @if($shortUrls->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>URL Original</th>
                                    <th>URL Corta</th>
                                    <th>Clics</th>
                                    <th>Creada</th>
                                    <th width="100">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($shortUrls as $shortUrl)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-start gap-2">
                                                <i class="fas fa-link text-primary mt-1"></i>
                                                <div style="max-width: 400px;">
                                                    <a href="{{ $shortUrl->original_url }}"
                                                       target="_blank"
                                                       class="text-decoration-none"
                                                       title="{{ $shortUrl->original_url }}">
                                                        {{ Str::limit($shortUrl->original_url, 60) }}
                                                    </a>
                                                    @if($shortUrl->utm_parameters && count($shortUrl->utm_parameters) > 0)
                                                        <div class="mt-1">
                                                            @foreach($shortUrl->utm_parameters as $key => $value)
                                                                <span class="badge bg-light text-dark me-1">
                                                                    <small>{{ $key }}: {{ $value }}</small>
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <code class="bg-light p-2 rounded">
                                                    {{ url('/s/' . ($shortUrl->custom_alias ?? $shortUrl->short_code)) }}
                                                </code>
                                                <button type="button"
                                                        class="btn btn-sm btn-light copy-url"
                                                        data-url="{{ url('/s/' . ($shortUrl->custom_alias ?? $shortUrl->short_code)) }}"
                                                        data-bs-toggle="tooltip"
                                                        title="Copiar URL">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge bg-{{ $shortUrl->clicks > 0 ? 'success' : 'secondary' }} fs-6">
                                                    {{ number_format($shortUrl->clicks) }}
                                                </span>
                                                @if($shortUrl->last_clicked_at)
                                                    <small class="text-muted">
                                                        <i class="fas fa-clock me-1"></i>
                                                        {{ $shortUrl->last_clicked_at->diffForHumans() }}
                                                    </small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $shortUrl->created_at->format('d/m/Y H:i') }}
                                            </small>
                                        </td>
                                        <td>
                                            <form action="{{ route('admin.social.short-urls.destroy', $shortUrl) }}"
                                                  method="POST"
                                                  onsubmit="return confirm('¿Eliminar esta URL?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="btn btn-sm btn-light-danger"
                                                        data-bs-toggle="tooltip"
                                                        title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($shortUrls->hasPages())
                        <div class="mt-4">
                            {{ $shortUrls->links() }}
                        </div>
                    @endif
                @else
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-link fs-1 text-muted"></i>
                        </div>
                        <h5 class="text-muted mb-2">No hay URLs acortadas</h5>
                        <p class="text-muted mb-4">Acorta tus URLs para compartir enlaces más limpios y hacer seguimiento de clics</p>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUrlModal">
                            <i class="fas fa-plus me-1"></i> Acortar Primera URL
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Create URL Modal -->
    <div class="modal fade" id="createUrlModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('admin.social.short-urls.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-link me-2"></i>Acortar URL
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Original URL -->
                        <div class="mb-3">
                            <label for="original_url" class="form-label fw-semibold">
                                URL Original <span class="text-danger">*</span>
                            </label>
                            <input type="url"
                                   class="form-control"
                                   id="original_url"
                                   name="original_url"
                                   placeholder="https://ejemplo.com/mi-articulo-muy-largo"
                                   required>
                            <small class="text-muted">La URL completa que deseas acortar</small>
                        </div>

                        <!-- Custom Alias -->
                        <div class="mb-3">
                            <label for="custom_alias" class="form-label fw-semibold">
                                Alias Personalizado (Opcional)
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">{{ url('/s/') }}/</span>
                                <input type="text"
                                       class="form-control"
                                       id="custom_alias"
                                       name="custom_alias"
                                       placeholder="mi-enlace"
                                       pattern="[a-zA-Z0-9-_]+"
                                       maxlength="20">
                            </div>
                            <small class="text-muted">Solo letras, números, guiones y guiones bajos. Deja vacío para generar automáticamente.</small>
                        </div>

                        <!-- UTM Parameters -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Parámetros UTM (Opcional)</label>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <input type="text"
                                           class="form-control form-control-sm"
                                           name="utm_source"
                                           placeholder="utm_source (ej: facebook)">
                                </div>
                                <div class="col-md-6">
                                    <input type="text"
                                           class="form-control form-control-sm"
                                           name="utm_medium"
                                           placeholder="utm_medium (ej: social)">
                                </div>
                                <div class="col-md-6">
                                    <input type="text"
                                           class="form-control form-control-sm"
                                           name="utm_campaign"
                                           placeholder="utm_campaign (ej: lanzamiento)">
                                </div>
                                <div class="col-md-6">
                                    <input type="text"
                                           class="form-control form-control-sm"
                                           name="utm_content"
                                           placeholder="utm_content (ej: banner)">
                                </div>
                            </div>
                            <small class="text-muted">Para seguimiento en Google Analytics</small>
                        </div>

                        <div class="alert alert-info d-flex align-items-start mb-0" role="alert">
                            <i class="fas fa-info-circle me-2 mt-1"></i>
                            <small>
                                Las URLs acortadas son perfectas para compartir en redes sociales.
                                Incluye parámetros UTM para hacer seguimiento del origen del tráfico.
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check me-1"></i> Acortar URL
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    // Copy URL to clipboard
    document.querySelectorAll('.copy-url').forEach(button => {
        button.addEventListener('click', function() {
            const url = this.getAttribute('data-url');
            navigator.clipboard.writeText(url).then(() => {
                const icon = this.querySelector('i');
                const originalClass = icon.className;

                icon.className = 'fas fa-check';
                this.classList.add('btn-success');
                this.classList.remove('btn-light');

                setTimeout(() => {
                    icon.className = originalClass;
                    this.classList.remove('btn-success');
                    this.classList.add('btn-light');
                }, 2000);
            });
        });
    });

    // Show modal with errors if validation fails
    @if($errors->any())
        var createUrlModal = new bootstrap.Modal(document.getElementById('createUrlModal'));
        createUrlModal.show();
    @endif
</script>
@endpush

@extends('layouts.admin')

@section('title', 'Nuevo Feed RSS')

@section('content')

    <div class="widget-content">

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.social.rss-feeds.index') }}">Feeds RSS</a></li>
                <li class="breadcrumb-item active">Nuevo Feed</li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-rss me-2"></i>Nuevo Feed RSS
                        </h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.social.rss-feeds.store') }}" method="POST">
                            @csrf

                            <!-- Name -->
                            <div class="mb-3">
                                <label for="name" class="form-label fw-semibold">
                                    Nombre <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control @error('name') is-invalid @enderror"
                                       id="name"
                                       name="name"
                                       value="{{ old('name') }}"
                                       placeholder="Ej: Blog de Marketing"
                                       required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Feed URL -->
                            <div class="mb-3">
                                <label for="feed_url" class="form-label fw-semibold">
                                    URL del Feed RSS <span class="text-danger">*</span>
                                </label>
                                <input type="url"
                                       class="form-control @error('feed_url') is-invalid @enderror"
                                       id="feed_url"
                                       name="feed_url"
                                       value="{{ old('feed_url') }}"
                                       placeholder="https://ejemplo.com/feed.xml"
                                       required>
                                @error('feed_url')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">La URL del feed RSS o Atom</small>
                            </div>

                            <!-- Social Account -->
                            <div class="mb-3">
                                <label for="social_account_id" class="form-label fw-semibold">
                                    Cuenta Social
                                </label>
                                <select class="form-select @error('social_account_id') is-invalid @enderror"
                                        id="social_account_id"
                                        name="social_account_id">
                                    <option value="">Todas las cuentas</option>
                                    @foreach($socialAccounts as $account)
                                        <option value="{{ $account->id }}"
                                                {{ old('social_account_id') == $account->id ? 'selected' : '' }}>
                                            {{ $account->network->label() }} - {{ $account->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('social_account_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Deja vacío para publicar en todas las cuentas</small>
                            </div>

                            <!-- Post Template -->
                            <div class="mb-3">
                                <label for="post_template" class="form-label fw-semibold">
                                    Plantilla de Publicación
                                </label>
                                <textarea class="form-control @error('post_template') is-invalid @enderror"
                                          id="post_template"
                                          name="post_template"
                                          rows="5"
                                          placeholder="Usa {title}, {description}, {link} para contenido dinámico">{{ old('post_template', "{title}\n\n{description}\n\n{link}") }}</textarea>
                                @error('post_template')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">
                                    Variables disponibles: <code>{title}</code>, <code>{description}</code>, <code>{link}</code>
                                </small>
                            </div>

                            <!-- Hashtags -->
                            <div class="mb-3">
                                <label for="hashtags" class="form-label fw-semibold">
                                    Hashtags
                                </label>
                                <input type="text"
                                       class="form-control @error('hashtags') is-invalid @enderror"
                                       id="hashtags"
                                       name="hashtags"
                                       value="{{ old('hashtags') }}"
                                       placeholder="#blog #noticias #marketing">
                                @error('hashtags')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Hashtags que se añadirán a cada publicación</small>
                            </div>

                            <!-- Fetch Interval -->
                            <div class="mb-3">
                                <label for="fetch_interval" class="form-label fw-semibold">
                                    Frecuencia de Lectura (minutos) <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('fetch_interval') is-invalid @enderror"
                                        id="fetch_interval"
                                        name="fetch_interval">
                                    <option value="15" {{ old('fetch_interval', 60) == 15 ? 'selected' : '' }}>Cada 15 minutos</option>
                                    <option value="30" {{ old('fetch_interval', 60) == 30 ? 'selected' : '' }}>Cada 30 minutos</option>
                                    <option value="60" {{ old('fetch_interval', 60) == 60 ? 'selected' : '' }}>Cada 1 hora</option>
                                    <option value="120" {{ old('fetch_interval', 60) == 120 ? 'selected' : '' }}>Cada 2 horas</option>
                                    <option value="360" {{ old('fetch_interval', 60) == 360 ? 'selected' : '' }}>Cada 6 horas</option>
                                    <option value="720" {{ old('fetch_interval', 60) == 720 ? 'selected' : '' }}>Cada 12 horas</option>
                                    <option value="1440" {{ old('fetch_interval', 60) == 1440 ? 'selected' : '' }}>Cada 24 horas</option>
                                </select>
                                @error('fetch_interval')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Auto Publish -->
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           id="auto_publish"
                                           name="auto_publish"
                                           value="1"
                                           {{ old('auto_publish') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="auto_publish">
                                        Publicar automáticamente
                                    </label>
                                </div>
                                <small class="text-muted">
                                    Si está activado, las nuevas entradas se publicarán automáticamente.
                                    Si no, se crearán como borradores.
                                </small>
                            </div>

                            <!-- Active -->
                            <div class="mb-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           id="active"
                                           name="active"
                                           value="1"
                                           {{ old('active', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="active">
                                        Feed activo
                                    </label>
                                </div>
                                <small class="text-muted">
                                    Solo los feeds activos serán procesados automáticamente
                                </small>
                            </div>

                            <!-- Info Alert -->
                            <div class="alert alert-info d-flex align-items-start mb-4" role="alert">
                                <i class="fas fa-lightbulb me-2 mt-1"></i>
                                <div>
                                    <strong>Consejo:</strong> Los feeds RSS te permiten importar automáticamente contenido
                                    de blogs, sitios de noticias o cualquier fuente RSS. Configura la frecuencia según
                                    la actualización del feed fuente.
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="d-flex gap-2 justify-content-end">
                                <a href="{{ route('admin.social.rss-feeds.index') }}" class="btn btn-light">
                                    <i class="fas fa-times me-1"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-check me-1"></i> Crear Feed
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    // Preview template
    const templateTextarea = document.getElementById('post_template');
    const exampleData = {
        title: 'Título del Artículo',
        description: 'Breve descripción del contenido...',
        link: 'https://ejemplo.com/articulo'
    };

    templateTextarea.addEventListener('focus', function() {
        if (!this.value) {
            this.value = "{title}\n\n{description}\n\n{link}";
        }
    });
</script>
@endpush

@extends('layouts.theme')

@section('title', 'Editar Keyword - Social Listening')

@section('content')

    <div class="row">
        <div class="col-12">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('admin.social.listening.index') }}">Social Listening</a>
                    </li>
                    <li class="breadcrumb-item active">Editar Keyword</li>
                </ol>
            </nav>

            <form action="{{ route('admin.social.listening.update', $keyword) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <!-- Main Content -->
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-search me-2"></i>Configuración de Keyword
                                </h5>
                            </div>
                            <div class="card-body">
                                <!-- Keyword Name -->
                                <div class="mb-4">
                                    <label for="name" class="form-label fw-semibold">
                                        Keyword o frase <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                           class="form-control @error('name') is-invalid @enderror"
                                           id="name"
                                           name="name"
                                           value="{{ old('name', $keyword->name) }}"
                                           placeholder="Ej: iPhone 15, #BlackFriday, @competidor"
                                           required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">
                                        Puedes incluir hashtags (#) o mentions (@) directamente
                                    </small>
                                </div>

                                <!-- Type -->
                                <div class="mb-4">
                                    <label class="form-label fw-semibold">
                                        Tipo de búsqueda <span class="text-danger">*</span>
                                    </label>
                                    <div class="row g-2">
                                        <div class="col-md-3">
                                            <input type="radio"
                                                   class="btn-check"
                                                   name="type"
                                                   id="type_keyword"
                                                   value="keyword"
                                                   {{ old('type', $keyword->type) === 'keyword' ? 'checked' : '' }}
                                                   required>
                                            <label class="btn btn-outline-primary w-100" for="type_keyword">
                                                <i class="fas fa-search d-block mb-1 fs-3"></i>
                                                Palabra Clave
                                            </label>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="radio"
                                                   class="btn-check"
                                                   name="type"
                                                   id="type_hashtag"
                                                   value="hashtag"
                                                   {{ old('type', $keyword->type) === 'hashtag' ? 'checked' : '' }}>
                                            <label class="btn btn-outline-primary w-100" for="type_hashtag">
                                                <i class="fas fa-hashtag d-block mb-1 fs-3"></i>
                                                Hashtag
                                            </label>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="radio"
                                                   class="btn-check"
                                                   name="type"
                                                   id="type_mention"
                                                   value="mention"
                                                   {{ old('type', $keyword->type) === 'mention' ? 'checked' : '' }}>
                                            <label class="btn btn-outline-primary w-100" for="type_mention">
                                                <i class="fas fa-at d-block mb-1 fs-3"></i>
                                                Mención
                                            </label>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="radio"
                                                   class="btn-check"
                                                   name="type"
                                                   id="type_competitor"
                                                   value="competitor"
                                                   {{ old('type', $keyword->type) === 'competitor' ? 'checked' : '' }}>
                                            <label class="btn btn-outline-primary w-100" for="type_competitor">
                                                <i class="fas fa-trophy d-block mb-1 fs-3"></i>
                                                Competidor
                                            </label>
                                        </div>
                                    </div>
                                    @error('type')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Networks -->
                                <div class="mb-4">
                                    <label class="form-label fw-semibold">
                                        Redes sociales a monitorear <span class="text-danger">*</span>
                                    </label>
                                    <div class="row g-2">
                                        <div class="col-md-3">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input"
                                                       type="checkbox"
                                                       name="networks[]"
                                                       id="network_facebook"
                                                       value="facebook"
                                                       {{ in_array('facebook', old('networks', $keyword->networks)) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="network_facebook">
                                                    <i class="fab fa-facebook text-primary"></i>
                                                    Facebook
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input"
                                                       type="checkbox"
                                                       name="networks[]"
                                                       id="network_instagram"
                                                       value="instagram"
                                                       {{ in_array('instagram', old('networks', $keyword->networks)) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="network_instagram">
                                                    <i class="fab fa-instagram text-danger"></i>
                                                    Instagram
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input"
                                                       type="checkbox"
                                                       name="networks[]"
                                                       id="network_twitter"
                                                       value="twitter"
                                                       {{ in_array('twitter', old('networks', $keyword->networks)) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="network_twitter">
                                                    <i class="fab fa-x-twitter text-dark"></i>
                                                    Twitter
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input"
                                                       type="checkbox"
                                                       name="networks[]"
                                                       id="network_linkedin"
                                                       value="linkedin"
                                                       {{ in_array('linkedin', old('networks', $keyword->networks)) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="network_linkedin">
                                                    <i class="fab fa-linkedin text-info"></i>
                                                    LinkedIn
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    @error('networks')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Selecciona al menos una red social</small>
                                </div>

                                <!-- Notes -->
                                <div class="mb-0">
                                    <label for="notes" class="form-label fw-semibold">Notas (opcional)</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror"
                                              id="notes"
                                              name="notes"
                                              rows="3"
                                              placeholder="Ej: Campaña Q1 2024, Monitoreo de competencia">{{ old('notes', $keyword->notes) }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Stats Card -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-chart-bar me-2"></i>Estadísticas
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <p class="text-muted mb-1">Total Menciones</p>
                                        <h4>{{ number_format($keyword->mentions_count) }}</h4>
                                    </div>
                                    <div class="col-md-4">
                                        <p class="text-muted mb-1">Esta Semana</p>
                                        <h4 class="text-success">+{{ $keyword->recent_mentions }}</h4>
                                    </div>
                                    <div class="col-md-4">
                                        <p class="text-muted mb-1">Última Búsqueda</p>
                                        <small class="text-muted">
                                            @if($keyword->last_scanned_at)
                                                {{ $keyword->last_scanned_at->diffForHumans() }}
                                            @else
                                                Nunca
                                            @endif
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="col-lg-4">
                        <!-- Settings -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-cog me-2"></i>Configuración
                                </h6>
                            </div>
                            <div class="card-body">
                                <!-- Sentiment Filter -->
                                <div class="mb-3">
                                    <label for="sentiment_filter" class="form-label fw-semibold">
                                        Filtro de sentiment
                                    </label>
                                    <select class="form-select @error('sentiment_filter') is-invalid @enderror"
                                            id="sentiment_filter"
                                            name="sentiment_filter">
                                        <option value="all" {{ old('sentiment_filter', $keyword->sentiment_filter) === 'all' ? 'selected' : '' }}>
                                            Todos los sentiments
                                        </option>
                                        <option value="positive" {{ old('sentiment_filter', $keyword->sentiment_filter) === 'positive' ? 'selected' : '' }}>
                                            Solo positivo
                                        </option>
                                        <option value="negative" {{ old('sentiment_filter', $keyword->sentiment_filter) === 'negative' ? 'selected' : '' }}>
                                            Solo negativo
                                        </option>
                                        <option value="neutral" {{ old('sentiment_filter', $keyword->sentiment_filter) === 'neutral' ? 'selected' : '' }}>
                                            Solo neutral
                                        </option>
                                    </select>
                                    @error('sentiment_filter')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">
                                        Filtra menciones por análisis de sentiment
                                    </small>
                                </div>

                                <!-- Notifications -->
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               id="notify_on_match"
                                               name="notify_on_match"
                                               value="1"
                                               {{ old('notify_on_match', $keyword->notify_on_match) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="notify_on_match">
                                            <i class="fas fa-bell me-1"></i>
                                            Notificar cuando se encuentre
                                        </label>
                                    </div>
                                    <small class="text-muted">
                                        Recibirás una notificación cada vez que se encuentre esta keyword
                                    </small>
                                </div>

                                <!-- Color -->
                                <div class="mb-0">
                                    <label for="color" class="form-label fw-semibold">Color de etiqueta</label>
                                    <div class="d-flex gap-2">
                                        @foreach(['#3498db', '#2ecc71', '#f39c12', '#e74c3c', '#9b59b6', '#1abc9c', '#34495e', '#e67e22'] as $colorOption)
                                            <div class="form-check">
                                                <input class="form-check-input color-radio"
                                                       type="radio"
                                                       name="color"
                                                       id="color_{{ $loop->index }}"
                                                       value="{{ $colorOption }}"
                                                       {{ old('color', $keyword->color) === $colorOption ? 'checked' : '' }}>
                                                <label class="form-check-label"
                                                       for="color_{{ $loop->index }}"
                                                       style="background-color: {{ $colorOption }}; width: 30px; height: 30px; border-radius: 50%; display: inline-block; cursor: pointer;">
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="card">
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Guardar Cambios
                                    </button>
                                    <a href="{{ route('admin.social.listening.index') }}" class="btn btn-light">
                                        <i class="fas fa-times me-1"></i> Cancelar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    // Hide color input visually (keep the radio buttons)
    document.querySelectorAll('.color-radio').forEach(radio => {
        radio.style.display = 'none';
    });
</script>
@endpush

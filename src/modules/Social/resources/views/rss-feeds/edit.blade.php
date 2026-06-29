@extends('layouts.theme')

@section('title', 'Editar Feed RSS')

@section('content')

    <div class="widget-content">

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.social.rss-feeds.index') }}">Feeds RSS</a></li>
                <li class="breadcrumb-item active">Editar Feed</li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-rss me-2"></i>Editar Feed: {{ $rssFeed->name }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.social.rss-feeds.update', $rssFeed) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <!-- Name -->
                            <div class="mb-3">
                                <label for="name" class="form-label fw-semibold">
                                    Nombre <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control @error('name') is-invalid @enderror"
                                       id="name"
                                       name="name"
                                       value="{{ old('name', $rssFeed->name) }}"
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
                                       value="{{ old('feed_url', $rssFeed->feed_url) }}"
                                       required>
                                @error('feed_url')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
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
                                                {{ old('social_account_id', $rssFeed->social_account_id) == $account->id ? 'selected' : '' }}>
                                            {{ $account->network->label() }} - {{ $account->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('social_account_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Post Template -->
                            <div class="mb-3">
                                <label for="post_template" class="form-label fw-semibold">
                                    Plantilla de Publicación
                                </label>
                                <textarea class="form-control @error('post_template') is-invalid @enderror"
                                          id="post_template"
                                          name="post_template"
                                          rows="5">{{ old('post_template', $rssFeed->post_template) }}</textarea>
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
                                       value="{{ old('hashtags', is_array($rssFeed->hashtags) ? implode(' ', $rssFeed->hashtags) : '') }}">
                                @error('hashtags')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Fetch Interval -->
                            <div class="mb-3">
                                <label for="fetch_interval" class="form-label fw-semibold">
                                    Frecuencia de Lectura (minutos) <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('fetch_interval') is-invalid @enderror"
                                        id="fetch_interval"
                                        name="fetch_interval">
                                    <option value="15" {{ old('fetch_interval', $rssFeed->fetch_interval) == 15 ? 'selected' : '' }}>Cada 15 minutos</option>
                                    <option value="30" {{ old('fetch_interval', $rssFeed->fetch_interval) == 30 ? 'selected' : '' }}>Cada 30 minutos</option>
                                    <option value="60" {{ old('fetch_interval', $rssFeed->fetch_interval) == 60 ? 'selected' : '' }}>Cada 1 hora</option>
                                    <option value="120" {{ old('fetch_interval', $rssFeed->fetch_interval) == 120 ? 'selected' : '' }}>Cada 2 horas</option>
                                    <option value="360" {{ old('fetch_interval', $rssFeed->fetch_interval) == 360 ? 'selected' : '' }}>Cada 6 horas</option>
                                    <option value="720" {{ old('fetch_interval', $rssFeed->fetch_interval) == 720 ? 'selected' : '' }}>Cada 12 horas</option>
                                    <option value="1440" {{ old('fetch_interval', $rssFeed->fetch_interval) == 1440 ? 'selected' : '' }}>Cada 24 horas</option>
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
                                           {{ old('auto_publish', $rssFeed->auto_publish) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="auto_publish">
                                        Publicar automáticamente
                                    </label>
                                </div>
                            </div>

                            <!-- Active -->
                            <div class="mb-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           id="active"
                                           name="active"
                                           value="1"
                                           {{ old('active', $rssFeed->active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="active">
                                        Feed activo
                                    </label>
                                </div>
                            </div>

                            <!-- Stats -->
                            @if($rssFeed->last_fetched_at || $rssFeed->next_fetch_at)
                                <div class="alert alert-light border" role="alert">
                                    <div class="d-flex align-items-center gap-3">
                                        <i class="fas fa-clock fs-4 text-primary"></i>
                                        <div>
                                            <div class="fw-semibold">Información de sincronización</div>
                                            <small class="text-muted">
                                                @if($rssFeed->last_fetched_at)
                                                    Última lectura: {{ $rssFeed->last_fetched_at->diffForHumans() }}<br>
                                                @endif
                                                @if($rssFeed->next_fetch_at)
                                                    Próxima lectura: {{ $rssFeed->next_fetch_at->diffForHumans() }}
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Actions -->
                            <div class="d-flex gap-2 justify-content-end">
                                <a href="{{ route('admin.social.rss-feeds.index') }}" class="btn btn-light">
                                    <i class="fas fa-times me-1"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-check me-1"></i> Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

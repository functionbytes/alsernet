@extends('layouts.admin')

@section('title', 'Detalles del Feed RSS')

@section('content')

    <div class="widget-content">

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.social.rss-feeds.index') }}">Feeds RSS</a></li>
                <li class="breadcrumb-item active">{{ $rssFeed->name }}</li>
            </ol>
        </nav>

        <div class="row">
            <!-- Feed Info Card -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-rss me-2"></i>{{ $rssFeed->name }}
                            </h5>
                            <div>
                                <span class="badge bg-{{ $rssFeed->active ? 'success' : 'secondary' }}">
                                    {{ $rssFeed->active ? 'Activo' : 'Inactivo' }}
                                </span>
                                @if($rssFeed->auto_publish)
                                    <span class="badge bg-info">
                                        <i class="fas fa-paper-plane me-1"></i>Auto-publicación
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Feed URL -->
                        <div class="mb-4">
                            <label class="fw-semibold text-muted small">URL del Feed:</label>
                            <div class="d-flex gap-2 align-items-center">
                                <code class="bg-light p-2 rounded flex-fill">{{ $rssFeed->feed_url }}</code>
                                <a href="{{ $rssFeed->feed_url }}" target="_blank" class="btn btn-sm btn-light">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Social Account -->
                        <div class="mb-4">
                            <label class="fw-semibold text-muted small">Cuenta Social:</label>
                            @if($rssFeed->socialAccount)
                                <div class="d-flex align-items-center gap-2">
                                    <i class="{{ $rssFeed->socialAccount->network->icon() }}"
                                       style="color: {{ $rssFeed->socialAccount->network->color() }}"></i>
                                    <span>{{ $rssFeed->socialAccount->name }}</span>
                                </div>
                            @else
                                <p class="text-muted mb-0">Todas las cuentas</p>
                            @endif
                        </div>

                        <!-- Post Template -->
                        @if($rssFeed->post_template)
                            <div class="mb-4">
                                <label class="fw-semibold text-muted small">Plantilla de Publicación:</label>
                                <div class="p-3 bg-light rounded">
                                    <pre class="mb-0"><code>{{ $rssFeed->post_template }}</code></pre>
                                </div>
                            </div>
                        @endif

                        <!-- Hashtags -->
                        @if($rssFeed->hashtags && count($rssFeed->hashtags) > 0)
                            <div class="mb-4">
                                <label class="fw-semibold text-muted small">Hashtags:</label>
                                <div>
                                    @foreach($rssFeed->hashtags as $hashtag)
                                        <span class="badge bg-primary me-1">{{ $hashtag }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Fetch Interval -->
                        <div class="mb-4">
                            <label class="fw-semibold text-muted small">Frecuencia de Lectura:</label>
                            <p class="mb-0">Cada {{ $rssFeed->fetch_interval }} minutos</p>
                        </div>

                        <!-- Actions -->
                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.social.rss-feeds.edit', $rssFeed) }}" class="btn btn-primary">
                                <i class="fas fa-edit me-1"></i> Editar Feed
                            </a>
                            <form action="{{ route('admin.social.rss-feeds.toggle', $rssFeed) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-{{ $rssFeed->active ? 'secondary' : 'success' }}">
                                    <i class="fas fa-{{ $rssFeed->active ? 'pause' : 'play' }} me-1"></i>
                                    {{ $rssFeed->active ? 'Desactivar' : 'Activar' }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Card -->
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-chart-bar me-2"></i>Estadísticas
                        </h6>
                    </div>
                    <div class="card-body">
                        <!-- Last Fetched -->
                        @if($rssFeed->last_fetched_at)
                            <div class="mb-3">
                                <small class="text-muted d-block">Última lectura:</small>
                                <p class="mb-0 fw-semibold">
                                    {{ $rssFeed->last_fetched_at->format('d/m/Y H:i') }}
                                </p>
                                <small class="text-muted">{{ $rssFeed->last_fetched_at->diffForHumans() }}</small>
                            </div>
                        @endif

                        <!-- Next Fetch -->
                        @if($rssFeed->next_fetch_at)
                            <div class="mb-3">
                                <small class="text-muted d-block">Próxima lectura:</small>
                                @if($rssFeed->next_fetch_at->isPast())
                                    <span class="badge bg-warning">Pendiente de ejecución</span>
                                @else
                                    <p class="mb-0 fw-semibold">
                                        {{ $rssFeed->next_fetch_at->format('d/m/Y H:i') }}
                                    </p>
                                    <small class="text-muted">{{ $rssFeed->next_fetch_at->diffForHumans() }}</small>
                                @endif
                            </div>
                        @endif

                        <!-- Posts Created -->
                        <div class="mb-3">
                            <small class="text-muted d-block">Publicaciones importadas:</small>
                            <p class="mb-0 fs-3 fw-bold text-primary">
                                {{ $rssFeed->posts()->count() }}
                            </p>
                        </div>

                        <!-- Created -->
                        <div class="pt-3 border-top">
                            <small class="text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                Creado: {{ $rssFeed->created_at->format('d/m/Y') }}
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Info Alert -->
                <div class="alert alert-info" role="alert">
                    <small>
                        <i class="fas fa-info-circle me-1"></i>
                        <strong>Consejo:</strong> El feed se procesará automáticamente según la frecuencia configurada.
                        Las nuevas entradas se importarán {{ $rssFeed->auto_publish ? 'y publicarán' : 'como borradores' }} automáticamente.
                    </small>
                </div>
            </div>
        </div>

        <!-- Recent Posts from Feed -->
        @if($recentPosts->count() > 0)
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-list me-2"></i>Últimas Publicaciones Importadas
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Contenido</th>
                                    <th>Estado</th>
                                    <th>Importado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentPosts as $post)
                                    <tr>
                                        <td>{{ Str::limit($post->content, 100) }}</td>
                                        <td>
                                            <span class="badge bg-{{ $post->status->color() }}">
                                                {{ $post->status->label() }}
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $post->created_at->diffForHumans() }}
                                            </small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <!-- Back Button -->
        <div class="mt-4">
            <a href="{{ route('admin.social.rss-feeds.index') }}" class="btn btn-light">
                <i class="fas fa-arrow-left me-1"></i> Volver a Feeds
            </a>
            <form action="{{ route('admin.social.rss-feeds.destroy', $rssFeed) }}"
                  method="POST"
                  class="d-inline"
                  onsubmit="return confirm('¿Eliminar este feed?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-light-danger">
                    <i class="fas fa-trash me-1"></i> Eliminar Feed
                </button>
            </form>
        </div>
    </div>

@endsection

@extends('layouts.admin')

@section('title', 'Feeds RSS')

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
                            <i class="fas fa-rss me-2"></i>Feeds RSS
                        </h5>
                        <p class="small mb-0 text-muted">Automatiza publicaciones desde feeds RSS</p>
                    </div>
                    <a href="{{ route('admin.social.rss-feeds.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nuevo Feed
                    </a>
                </div>
            </div>

            <!-- RSS Feeds Table -->
            <div class="card-body">
                @if($rssFeeds->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Feed</th>
                                    <th>Cuenta</th>
                                    <th>Frecuencia</th>
                                    <th>Próxima Lectura</th>
                                    <th>Estado</th>
                                    <th width="150">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rssFeeds as $feed)
                                    <tr>
                                        <td>
                                            <div>
                                                <div class="fw-semibold mb-1">{{ $feed->name }}</div>
                                                <small class="text-muted d-block" style="max-width: 400px;">
                                                    <i class="fas fa-link me-1"></i>
                                                    {{ Str::limit($feed->feed_url, 50) }}
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            @if($feed->socialAccount)
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="{{ $feed->socialAccount->network->icon() }}"
                                                       style="color: {{ $feed->socialAccount->network->color() }}"></i>
                                                    <span>{{ $feed->socialAccount->name }}</span>
                                                </div>
                                            @else
                                                <span class="text-muted">Todas</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-light-primary text-primary">
                                                Cada {{ $feed->fetch_interval }} min
                                            </span>
                                        </td>
                                        <td>
                                            @if($feed->next_fetch_at)
                                                <small class="text-muted">
                                                    @if($feed->next_fetch_at->isPast())
                                                        <span class="badge bg-warning">Pendiente</span>
                                                    @else
                                                        <i class="fas fa-clock me-1"></i>
                                                        {{ $feed->next_fetch_at->diffForHumans() }}
                                                    @endif
                                                </small>
                                            @else
                                                <span class="text-muted">No programado</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input toggle-feed"
                                                           type="checkbox"
                                                           data-feed-id="{{ $feed->id }}"
                                                           {{ $feed->active ? 'checked' : '' }}>
                                                </div>
                                                <span class="badge bg-{{ $feed->active ? 'success' : 'secondary' }}">
                                                    {{ $feed->active ? 'Activo' : 'Inactivo' }}
                                                </span>
                                                @if($feed->auto_publish)
                                                    <span class="badge bg-info">
                                                        <i class="fas fa-paper-plane me-1"></i>Auto
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="{{ route('admin.social.rss-feeds.show', $feed) }}"
                                                   class="btn btn-sm btn-light"
                                                   data-bs-toggle="tooltip"
                                                   title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.social.rss-feeds.edit', $feed) }}"
                                                   class="btn btn-sm btn-light"
                                                   data-bs-toggle="tooltip"
                                                   title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('admin.social.rss-feeds.destroy', $feed) }}"
                                                      method="POST"
                                                      onsubmit="return confirm('¿Eliminar este feed?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="btn btn-sm btn-light-danger"
                                                            data-bs-toggle="tooltip"
                                                            title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($rssFeeds->hasPages())
                        <div class="mt-4">
                            {{ $rssFeeds->links() }}
                        </div>
                    @endif
                @else
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-rss fs-1 text-muted"></i>
                        </div>
                        <h5 class="text-muted mb-2">No hay feeds RSS configurados</h5>
                        <p class="text-muted mb-4">Automatiza tus publicaciones importando contenido desde feeds RSS</p>
                        <a href="{{ route('admin.social.rss-feeds.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Configurar Primer Feed
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    // Toggle feed active status
    document.querySelectorAll('.toggle-feed').forEach(toggle => {
        toggle.addEventListener('change', function() {
            const feedId = this.getAttribute('data-feed-id');
            const isActive = this.checked;

            fetch('{{ route('admin.social.rss-feeds.toggle', ':id') }}'.replace(':id', feedId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ active: isActive })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error al actualizar el feed');
                    this.checked = !isActive;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al actualizar el feed');
                this.checked = !isActive;
            });
        });
    });
</script>
@endpush

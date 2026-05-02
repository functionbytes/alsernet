@extends('layouts.admin')

@section('title', 'Publicaciones')

@section('content')

    <div class="widget-content searchable-container list">

        <!-- Alerts -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Main Card -->
        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">
                            <i class="fas fa-paper-plane me-2"></i>Publicaciones
                        </h5>
                        <p class="small mb-0 text-muted">Gestiona y programa contenido para tus redes sociales</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.social.publishing.calendar') }}" class="btn btn-secondary">
                            <i class="fas fa-calendar me-1"></i> Calendario
                        </a>
                        <a href="{{ route('admin.social.publishing.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nueva Publicación
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-primary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-primary mb-2">Total</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                                        <small class="text-muted">Publicaciones</small>
                                    </div>
                                    <i class="fas fa-file-alt fs-1 text-primary opacity-25"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-warning stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-warning mb-2">Borradores</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['draft'] }}</h4>
                                        <small class="text-muted">Sin publicar</small>
                                    </div>
                                    <i class="fas fa-pencil fs-1 text-warning opacity-25"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-info stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-info mb-2">Programadas</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['scheduled'] }}</h4>
                                        <small class="text-muted">Por publicar</small>
                                    </div>
                                    <i class="fas fa-clock fs-1 text-info opacity-25"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-success stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-success mb-2">Publicadas</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['published'] }}</h4>
                                        <small class="text-muted">Activas</small>
                                    </div>
                                    <i class="fas fa-check-circle fs-1 text-success opacity-25"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Posts Table -->
            <div class="card-body">
                @if($posts->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Contenido</th>
                                    <th>Red Social</th>
                                    <th>Campaña</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th width="200">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($posts as $post)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-start gap-2">
                                                @if($post->type->value === 'media' && is_array($post->media) && isset($post->media[0]) && is_string($post->media[0]))
                                                    <img src="{{ $post->media[0] }}"
                                                         class="rounded"
                                                         style="width: 40px; height: 40px; object-fit: cover;"
                                                         onerror="this.style.display='none'">
                                                @elseif($post->type->value === 'link')
                                                    <div class="rounded bg-light p-2">
                                                        <i class="fas fa-link"></i>
                                                    </div>
                                                @endif
                                                <div class="flex-fill">
                                                    <p class="mb-1 text-truncate" style="max-width: 300px;">
                                                        {{ $post->content }}
                                                    </p>
                                                    <div class="d-flex gap-1">
                                                        @foreach($post->labels as $label)
                                                            <span class="badge badge-sm"
                                                                  style="background-color: {{ $label->color }};">
                                                                {{ $label->name }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($post->socialAccount)
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="{{ $post->socialAccount->network->icon() }}"
                                                       style="color: {{ $post->socialAccount->network->color() }}"></i>
                                                    <span>{{ $post->socialAccount->username }}</span>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($post->campaign)
                                                <span class="badge"
                                                      style="background-color: {{ $post->campaign->color }};">
                                                    {{ $post->campaign->name }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $post->status->color() }}">
                                                <i class="{{ $post->status->icon() }} me-1"></i>
                                                {{ $post->status->label() }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($post->scheduled_at)
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    {{ $post->scheduled_at->format('d/m/Y H:i') }}
                                                </small>
                                            @elseif($post->published_at)
                                                <small class="text-muted">
                                                    <i class="fas fa-check me-1"></i>
                                                    {{ $post->published_at->diffForHumans() }}
                                                </small>
                                            @else
                                                <small class="text-muted">-</small>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                @if($post->canEdit())
                                                    <a href="{{ route('admin.social.publishing.edit', $post) }}"
                                                       class="btn btn-sm btn-light"
                                                       data-bs-toggle="tooltip"
                                                       title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                @endif

                                                @if($post->status->value === 'draft' || $post->status->value === 'failed')
                                                    <form action="{{ route('admin.social.publishing.publish', $post) }}"
                                                          method="POST">
                                                        @csrf
                                                        <button type="submit"
                                                                class="btn btn-sm btn-success"
                                                                data-bs-toggle="tooltip"
                                                                title="Publicar ahora">
                                                            <i class="fas fa-paper-plane"></i>
                                                        </button>
                                                    </form>
                                                @endif

                                                <form action="{{ route('admin.social.publishing.duplicate', $post) }}"
                                                      method="POST">
                                                    @csrf
                                                    <button type="submit"
                                                            class="btn btn-sm btn-light"
                                                            data-bs-toggle="tooltip"
                                                            title="Duplicar">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </form>

                                                <form action="{{ route('admin.social.publishing.destroy', $post) }}"
                                                      method="POST"
                                                      onsubmit="return confirm('¿Eliminar esta publicación?')">
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
                    <div class="mt-4">
                        {{ $posts->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-file-alt fs-1 text-muted"></i>
                        </div>
                        <h5 class="text-muted mb-2">No hay publicaciones</h5>
                        <p class="text-muted mb-4">Crea tu primera publicación para tus redes sociales</p>
                        <a href="{{ route('admin.social.publishing.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nueva Publicación
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection

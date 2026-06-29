@extends('layouts.theme')

@section('title', $campaign->name)

@section('content')

    <div class="widget-content">

        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.social.campaigns.index') }}">Campañas</a></li>
                <li class="breadcrumb-item active">{{ $campaign->name }}</li>
            </ol>
        </nav>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Campaign Header -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="rounded-circle" style="width: 50px; height: 50px; background-color: {{ $campaign->color }};"></div>
                            <div>
                                <h4 class="mb-1 fw-bold">{{ $campaign->name }}</h4>
                                <div class="d-flex gap-2 align-items-center">
                                    <span class="badge bg-{{ $campaign->status ? 'success' : 'secondary' }}">
                                        <i class="fas fa-{{ $campaign->status ? 'check' : 'x' }} me-1"></i>
                                        {{ $campaign->status ? 'Activa' : 'Inactiva' }}
                                    </span>
                                    @if($campaign->start_date || $campaign->end_date)
                                        <span class="text-muted small">
                                            <i class="fas fa-calendar me-1"></i>
                                            @if($campaign->start_date && $campaign->end_date)
                                                {{ $campaign->start_date->format('d/m/Y') }} - {{ $campaign->end_date->format('d/m/Y') }}
                                            @elseif($campaign->start_date)
                                                Desde {{ $campaign->start_date->format('d/m/Y') }}
                                            @else
                                                Hasta {{ $campaign->end_date->format('d/m/Y') }}
                                            @endif
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if($campaign->description)
                            <p class="text-muted mb-0">{{ $campaign->description }}</p>
                        @endif
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.social.campaigns.edit', $campaign) }}" class="btn btn-light">
                            <i class="fas fa-edit me-1"></i> Editar
                        </a>
                        <form action="{{ route('admin.social.campaigns.destroy', $campaign) }}"
                              method="POST"
                              onsubmit="return confirm('¿Eliminar esta campaña? Las publicaciones asociadas no se eliminarán.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-light-danger">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-2 col-6">
                <div class="card border-bottom border-primary border-3">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-light-primary p-2 me-2">
                                <i class="fas fa-file fs-5 text-primary"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">{{ $campaign->posts_count ?? 0 }}</h6>
                                <small class="text-muted">Total</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-2 col-6">
                <div class="card border-bottom border-success border-3">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-light-success p-2 me-2">
                                <i class="fas fa-check fs-5 text-success"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">{{ $campaign->published_posts_count ?? 0 }}</h6>
                                <small class="text-muted">Publicadas</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-2 col-6">
                <div class="card border-bottom border-info border-3">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-light-info p-2 me-2">
                                <i class="fas fa-clock fs-5 text-info"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">{{ $campaign->scheduled_posts_count ?? 0 }}</h6>
                                <small class="text-muted">Programadas</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-2 col-6">
                <div class="card border-bottom border-warning border-3">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-light-warning p-2 me-2">
                                <i class="fas fa-file-alt fs-5 text-warning"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">{{ $campaign->draft_posts_count ?? 0 }}</h6>
                                <small class="text-muted">Borradores</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-2 col-6">
                <div class="card border-bottom  border-3">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-light-danger p-2 me-2">
                                <i class="fas fa-times fs-5 text-danger"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">{{ $campaign->failed_posts_count ?? 0 }}</h6>
                                <small class="text-muted">Fallidas</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-2 col-6">
                <div class="card border-bottom border-secondary border-3">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-light-secondary p-2 me-2">
                                <i class="fas fa-heart fs-5 text-secondary"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">{{ number_format($campaign->total_engagement ?? 0) }}</h6>
                                <small class="text-muted">Interacciones</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Campaign Posts -->
        <div class="card">
            <div class="card-header p-4 border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">
                            <i class="fas fa-file-alt me-2"></i>Publicaciones de la Campaña
                        </h5>
                        <p class="small mb-0 text-muted">Todas las publicaciones asociadas a esta campaña</p>
                    </div>
                    <a href="{{ route('admin.social.publishing.create', ['campaign_id' => $campaign->id]) }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nueva Publicación
                    </a>
                </div>
            </div>

            <div class="card-body">
                @if($campaign->posts && $campaign->posts->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Contenido</th>
                                    <th>Red Social</th>
                                    <th>Estado</th>
                                    <th>Programada</th>
                                    <th>Interacciones</th>
                                    <th width="100">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($campaign->posts as $post)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-start gap-2">
                                                <i class="{{ $post->type->icon() }} text-muted"></i>
                                                <div>
                                                    <div class="fw-semibold">{{ Str::limit($post->content, 80) }}</div>
                                                    @if($post->labels->count() > 0)
                                                        <div class="mt-1">
                                                            @foreach($post->labels->take(3) as $label)
                                                                <span class="badge me-1" style="background-color: {{ $label->color }}; font-size: 0.7rem;">
                                                                    {{ $label->name }}
                                                                </span>
                                                            @endforeach
                                                            @if($post->labels->count() > 3)
                                                                <span class="badge bg-light text-dark" style="font-size: 0.7rem;">
                                                                    +{{ $post->labels->count() - 3 }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($post->socialAccount)
                                                <span class="badge" style="background-color: {{ $post->socialAccount->network->color() }};">
                                                    <i class="{{ $post->socialAccount->network->icon() }} me-1"></i>
                                                    {{ $post->socialAccount->network->label() }}
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
                                                    <i class="fas fa-calendar-alt me-1"></i>
                                                    {{ $post->scheduled_at->format('d/m/Y H:i') }}
                                                </small>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <i class="fas fa-heart me-1"></i>{{ number_format($post->likes_count) }}
                                                <i class="fas fa-comment ms-2 me-1"></i>{{ number_format($post->comments_count) }}
                                            </small>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.social.publishing.edit', $post) }}"
                                               class="btn btn-sm btn-light">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-file-alt fs-1 text-muted mb-3 d-block"></i>
                        <h5 class="text-muted mb-2">No hay publicaciones en esta campaña</h5>
                        <p class="text-muted mb-4">Crea tu primera publicación para esta campaña</p>
                        <a href="{{ route('admin.social.publishing.create', ['campaign_id' => $campaign->id]) }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nueva Publicación
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection

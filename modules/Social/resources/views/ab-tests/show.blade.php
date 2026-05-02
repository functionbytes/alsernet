@extends('layouts.admin')

@section('title', 'Detalles del Test A/B')

@section('content')

    <div class="widget-content">

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.social.ab-tests.index') }}">Tests A/B</a></li>
                <li class="breadcrumb-item active">Test #{{ $abTest->id }}</li>
            </ol>
        </nav>

        <!-- Status Card -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle p-3 bg-primary-subtle">
                                <i class="fas fa-flask fs-2 text-primary"></i>
                            </div>
                            <div>
                                <h5 class="mb-1">Test A/B #{{ $abTest->id }}</h5>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-calendar me-1"></i>{{ $abTest->created_at->format('d/m/Y H:i') }} -
                                    <span class="badge bg-light-primary text-primary ms-2">{{ ucfirst($abTest->metric) }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        @if($abTest->winner_post_id)
                            <span class="badge bg-success fs-6 px-3 py-2">
                                <i class="fas fa-trophy me-1"></i> Test Completado
                            </span>
                        @else
                            <form action="{{ route('admin.social.ab-tests.complete', $abTest) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-check me-1"></i> Finalizar Test
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Variant A Card -->
            <div class="col-md-6">
                <div class="card h-100 border-primary">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-comment me-2"></i>Variante A
                            @if($abTest->winner_post_id === $abTest->variant_a_post_id)
                                <i class="fas fa-trophy ms-2"></i>
                            @endif
                        </h6>
                    </div>
                    <div class="card-body">
                        <!-- Content -->
                        <div class="mb-3">
                            <label class="fw-semibold text-muted small">Contenido:</label>
                            <p class="mb-0">{{ $abTest->variantA->content }}</p>
                        </div>

                        <!-- Post Info -->
                        <div class="mb-3">
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="p-2 bg-light rounded">
                                        <small class="text-muted d-block">Estado</small>
                                        <span class="badge bg-{{ $abTest->variantA->status->color() }}">
                                            {{ $abTest->variantA->status->label() }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 bg-light rounded">
                                        <small class="text-muted d-block">Publicado</small>
                                        @if($abTest->variantA->published_at)
                                            <small>{{ $abTest->variantA->published_at->format('d/m/Y') }}</small>
                                        @else
                                            <small class="text-muted">No publicado</small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Metrics -->
                        <div class="mb-3">
                            <label class="fw-semibold text-muted small mb-2">Métricas:</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="text-center p-3 bg-light rounded">
                                        <i class="fas fa-heart text-danger d-block mb-1"></i>
                                        <div class="fs-4 fw-bold">{{ $abTest->variantA->likes_count ?? 0 }}</div>
                                        <small class="text-muted">Likes</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-3 bg-light rounded">
                                        <i class="fas fa-comment text-primary d-block mb-1"></i>
                                        <div class="fs-4 fw-bold">{{ $abTest->variantA->comments_count ?? 0 }}</div>
                                        <small class="text-muted">Comentarios</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-3 bg-light rounded">
                                        <i class="fas fa-share text-success d-block mb-1"></i>
                                        <div class="fs-4 fw-bold">{{ $abTest->variantA->shares_count ?? 0 }}</div>
                                        <small class="text-muted">Compartidos</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-3 bg-light rounded">
                                        <i class="fas fa-mouse-pointer text-info d-block mb-1"></i>
                                        <div class="fs-4 fw-bold">{{ $abTest->variantA->clicks_count ?? 0 }}</div>
                                        <small class="text-muted">Clics</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Score -->
                        <div class="alert alert-primary mb-0" role="alert">
                            <div class="text-center">
                                <div class="fs-1 fw-bold">{{ $abTest->variant_a_score }}</div>
                                <small>Puntuación de {{ ucfirst($abTest->metric) }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Variant B Card -->
            <div class="col-md-6">
                <div class="card h-100 border-info">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-comment me-2"></i>Variante B
                            @if($abTest->winner_post_id === $abTest->variant_b_post_id)
                                <i class="fas fa-trophy ms-2"></i>
                            @endif
                        </h6>
                    </div>
                    <div class="card-body">
                        <!-- Content -->
                        <div class="mb-3">
                            <label class="fw-semibold text-muted small">Contenido:</label>
                            <p class="mb-0">{{ $abTest->variantB->content }}</p>
                        </div>

                        <!-- Post Info -->
                        <div class="mb-3">
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="p-2 bg-light rounded">
                                        <small class="text-muted d-block">Estado</small>
                                        <span class="badge bg-{{ $abTest->variantB->status->color() }}">
                                            {{ $abTest->variantB->status->label() }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 bg-light rounded">
                                        <small class="text-muted d-block">Publicado</small>
                                        @if($abTest->variantB->published_at)
                                            <small>{{ $abTest->variantB->published_at->format('d/m/Y') }}</small>
                                        @else
                                            <small class="text-muted">No publicado</small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Metrics -->
                        <div class="mb-3">
                            <label class="fw-semibold text-muted small mb-2">Métricas:</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="text-center p-3 bg-light rounded">
                                        <i class="fas fa-heart text-danger d-block mb-1"></i>
                                        <div class="fs-4 fw-bold">{{ $abTest->variantB->likes_count ?? 0 }}</div>
                                        <small class="text-muted">Likes</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-3 bg-light rounded">
                                        <i class="fas fa-comment text-primary d-block mb-1"></i>
                                        <div class="fs-4 fw-bold">{{ $abTest->variantB->comments_count ?? 0 }}</div>
                                        <small class="text-muted">Comentarios</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-3 bg-light rounded">
                                        <i class="fas fa-share text-success d-block mb-1"></i>
                                        <div class="fs-4 fw-bold">{{ $abTest->variantB->shares_count ?? 0 }}</div>
                                        <small class="text-muted">Compartidos</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-3 bg-light rounded">
                                        <i class="fas fa-mouse-pointer text-info d-block mb-1"></i>
                                        <div class="fs-4 fw-bold">{{ $abTest->variantB->clicks_count ?? 0 }}</div>
                                        <small class="text-muted">Clics</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Score -->
                        <div class="alert alert-info mb-0" role="alert">
                            <div class="text-center">
                                <div class="fs-1 fw-bold">{{ $abTest->variant_b_score }}</div>
                                <small>Puntuación de {{ ucfirst($abTest->metric) }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Winner Card -->
        @if($abTest->winner_post_id)
            <div class="card mt-4 border-success">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle p-3 bg-success-subtle">
                                    <i class="fas fa-trophy fs-2 text-success"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1 text-success">
                                        Ganadora: Variante {{ $abTest->winner_post_id === $abTest->variant_a_post_id ? 'A' : 'B' }}
                                    </h5>
                                    <p class="text-muted mb-0">
                                        Con {{ $abTest->winner_post_id === $abTest->variant_a_post_id ? $abTest->variant_a_score : $abTest->variant_b_score }} puntos
                                        de {{ $abTest->metric }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-md-end">
                            @php
                                $winnerScore = $abTest->winner_post_id === $abTest->variant_a_post_id ? $abTest->variant_a_score : $abTest->variant_b_score;
                                $loserScore = $abTest->winner_post_id === $abTest->variant_a_post_id ? $abTest->variant_b_score : $abTest->variant_a_score;
                                $improvement = $loserScore > 0 ? round((($winnerScore - $loserScore) / $loserScore) * 100, 1) : 0;
                            @endphp
                            <div class="badge bg-success fs-5 px-3 py-2">
                                +{{ $improvement }}% mejor
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Actions -->
        <div class="mt-4">
            <a href="{{ route('admin.social.ab-tests.index') }}" class="btn btn-light">
                <i class="fas fa-arrow-left me-1"></i> Volver a Tests A/B
            </a>
            <a href="{{ route('admin.social.publishing.index') }}" class="btn btn-primary">
                <i class="fas fa-eye me-1"></i> Ver Publicaciones
            </a>
            <form action="{{ route('admin.social.ab-tests.destroy', $abTest) }}"
                  method="POST"
                  class="d-inline"
                  onsubmit="return confirm('¿Eliminar este test?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-light-danger">
                    <i class="fas fa-trash me-1"></i> Eliminar
                </button>
            </form>
        </div>
    </div>

@endsection

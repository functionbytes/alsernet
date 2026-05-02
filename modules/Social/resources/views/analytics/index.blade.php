@extends('layouts.admin')

@section('title', 'Dashboard Analytics')

@section('content')

    <div class="widget-content">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1">
                    <i class="fas fa-chart-bar me-2"></i>Dashboard Analytics
                </h4>
                <p class="text-muted mb-0">Análisis de rendimiento de tus publicaciones sociales</p>
            </div>
            <div>
                <select class="form-select" id="timeRange">
                    <option value="7">Últimos 7 días</option>
                    <option value="30" selected>Últimos 30 días</option>
                    <option value="90">Últimos 90 días</option>
                </select>
            </div>
        </div>

        <!-- Overview Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-white-50 mb-1">Total Publicaciones</h6>
                                <h3 class="mb-0 fw-bold">{{ $totalPosts }}</h3>
                                <small class="text-white-75">
                                    @if($postsGrowth >= 0)
                                        <i class="fas fa-arrow-trend-up me-1"></i>+{{ $postsGrowth }}%
                                    @else
                                        <i class="fas fa-arrow-trend-down me-1"></i>{{ $postsGrowth }}%
                                    @endif
                                    vs período anterior
                                </small>
                            </div>
                            <i class="fas fa-file-alt fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-white-50 mb-1">Engagement Total</h6>
                                <h3 class="mb-0 fw-bold">{{ number_format($totalEngagement) }}</h3>
                                <small class="text-white-75">
                                    @if($engagementGrowth >= 0)
                                        <i class="fas fa-arrow-trend-up me-1"></i>+{{ $engagementGrowth }}%
                                    @else
                                        <i class="fas fa-arrow-trend-down me-1"></i>{{ $engagementGrowth }}%
                                    @endif
                                    vs período anterior
                                </small>
                            </div>
                            <i class="fas fa-heart fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-white-50 mb-1">Tasa de Engagement</h6>
                                <h3 class="mb-0 fw-bold">{{ $engagementRate }}%</h3>
                                <small class="text-white-75">Promedio del período</small>
                            </div>
                            <i class="fas fa-arrow-trend-up fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-white-50 mb-1">Mejor Publicación</h6>
                                <h3 class="mb-0 fw-bold">{{ $bestPostEngagement }}</h3>
                                <small class="text-white-75">
                                    <i class="fas fa-trophy me-1"></i>Engagement récord
                                </small>
                            </div>
                            <i class="fas fa-star fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Engagement by Network -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-chart-line me-2"></i>Engagement por Red Social
                        </h6>
                    </div>
                    <div class="card-body">
                        <canvas id="networkEngagementChart" height="100"></canvas>
                    </div>
                </div>
            </div>

            <!-- Post Type Distribution -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-chart-pie me-2"></i>Tipos de Contenido
                        </h6>
                    </div>
                    <div class="card-body">
                        <canvas id="postTypeChart" height="200"></canvas>
                    </div>
                </div>
            </div>

            <!-- Best Time to Post -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-clock me-2"></i>Mejores Horarios para Publicar
                        </h6>
                    </div>
                    <div class="card-body">
                        @if(count($bestPostingTimes) > 0)
                            <div class="list-group list-group-flush">
                                @foreach($bestPostingTimes as $hour => $avgEngagement)
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <div>
                                            <i class="fas fa-clock text-primary me-2"></i>
                                            <strong>{{ $hour }}:00</strong>
                                        </div>
                                        <div>
                                            <span class="badge bg-success">{{ round($avgEngagement, 1) }} engagement promedio</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted text-center py-4">
                                <i class="fas fa-info-circle me-2"></i>
                                Necesitas más publicaciones para calcular los mejores horarios
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Top Performing Posts -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-trophy me-2"></i>Publicaciones Destacadas
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($topPosts->count() > 0)
                            <div class="list-group list-group-flush">
                                @foreach($topPosts as $post)
                                    <div class="list-group-item px-0">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-fill">
                                                <p class="mb-1">{{ Str::limit($post->content, 60) }}</p>
                                                <small class="text-muted">
                                                    {{ $post->published_at?->format('d/m/Y H:i') ?? 'No publicado' }}
                                                </small>
                                            </div>
                                            <div class="text-end ms-3">
                                                <div class="badge bg-primary">
                                                    {{ ($post->likes_count ?? 0) + ($post->comments_count ?? 0) + ($post->shares_count ?? 0) }}
                                                </div>
                                                <small class="text-muted d-block">engagement</small>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted text-center py-4">
                                <i class="fas fa-info-circle me-2"></i>
                                No hay publicaciones publicadas aún
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Campaign Performance -->
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-bullseye me-2"></i>Rendimiento por Campaña
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($campaignStats->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Campaña</th>
                                            <th>Publicaciones</th>
                                            <th>Likes</th>
                                            <th>Comentarios</th>
                                            <th>Compartidos</th>
                                            <th>Total Engagement</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($campaignStats as $stat)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="rounded" style="width: 4px; height: 30px; background-color: {{ $stat->campaign->color }};"></div>
                                                        <span class="fw-semibold">{{ $stat->campaign->name }}</span>
                                                    </div>
                                                </td>
                                                <td>{{ $stat->posts_count }}</td>
                                                <td>{{ number_format($stat->total_likes ?? 0) }}</td>
                                                <td>{{ number_format($stat->total_comments ?? 0) }}</td>
                                                <td>{{ number_format($stat->total_shares ?? 0) }}</td>
                                                <td>
                                                    <span class="badge bg-success fs-6">
                                                        {{ number_format(($stat->total_likes ?? 0) + ($stat->total_comments ?? 0) + ($stat->total_shares ?? 0)) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted text-center py-4">
                                <i class="fas fa-info-circle me-2"></i>
                                No hay campañas con publicaciones aún
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<style>
    .text-white-50 { opacity: 0.5; }
    .text-white-75 { opacity: 0.75; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    // Network Engagement Chart
    const networkEngagementCtx = document.getElementById('networkEngagementChart').getContext('2d');
    new Chart(networkEngagementCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($networkEngagementData['labels']) !!},
            datasets: [{
                label: 'Engagement',
                data: {!! json_encode($networkEngagementData['data']) !!},
                backgroundColor: [
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)',
                ],
                borderColor: [
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 99, 132, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Engagement: ' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Post Type Chart
    const postTypeCtx = document.getElementById('postTypeChart').getContext('2d');
    new Chart(postTypeCtx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($postTypeData['labels']) !!},
            datasets: [{
                data: {!! json_encode($postTypeData['data']) !!},
                backgroundColor: [
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
</script>
@endpush

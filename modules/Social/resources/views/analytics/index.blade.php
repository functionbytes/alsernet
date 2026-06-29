@extends('layouts.theme')

@section('title', 'Dashboard Analytics')

@section('content')

    {{-- Filters bar --}}
    <div class="card card-body mb-4 border-0 shadow-sm">
        <div class="row align-items-center g-3">
            <div class="col-md">
                <ul class="nav nav-pills gap-1 flex-nowrap overflow-auto">
                    @foreach (['7' => 'Últimos 7 días', '30' => 'Últimos 30 días', '90' => 'Últimos 90 días'] as $value => $label)
                        <li class="nav-item flex-shrink-0">
                            <a class="nav-link py-1 px-3 small fw-semibold time-range-pill {{ ($currentRange ?? '30') == $value ? 'active' : 'text-muted' }}"
                               href="?range={{ $value }}" data-range="{{ $value }}">
                                {{ $label }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="col-md-auto d-flex align-items-center gap-2">
                <div class="dropdown">
                    <a href="javascript:void(0)" class="d-flex align-items-center justify-content-center rounded-circle text-muted btn-icon-sm"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-ellipsis-vertical"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('admin.social.export.analytics.pdf') }}">Exportar PDF</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="window.print()">Imprimir</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats cards --}}
    <div class="row g-3 mb-4">

        {{-- Total Publicaciones --}}
        <div class="col-md-6 col-xl-3">
            <div class="card w-100 h-100">
                <div class="card-body">
                    <h5 class="card-title fw-semibold mb-3">Total Publicaciones</h5>
                    <h4 class="fw-semibold mb-2">{{ number_format($totalPosts) }}</h4>
                    <div class="d-flex align-items-center">
                        <span class="me-1 rounded-circle d-flex align-items-center justify-content-center spark-arrow {{ $postsGrowth >= 0 ? 'bg-success-subtle' : 'bg-danger-subtle' }}">
                            <i class="fas {{ $postsGrowth >= 0 ? 'fa-arrow-up text-success' : 'fa-arrow-down text-danger' }}"></i>
                        </span>
                        <p class="text-dark me-1 fs-3 mb-0">{{ $postsGrowth >= 0 ? '+' : '' }}{{ $postsGrowth }}%</p>
                        <p class="fs-3 mb-0 text-muted">vs anterior</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Engagement Total --}}
        <div class="col-md-6 col-xl-3">
            <div class="card w-100 h-100">
                <div class="card-body">
                    <h5 class="card-title fw-semibold mb-3">Engagement Total</h5>
                    <h4 class="fw-semibold mb-2">{{ number_format($totalEngagement) }}</h4>
                    <div class="d-flex align-items-center">
                        <span class="me-1 rounded-circle d-flex align-items-center justify-content-center spark-arrow {{ $engagementGrowth >= 0 ? 'bg-success-subtle' : 'bg-danger-subtle' }}">
                            <i class="fas {{ $engagementGrowth >= 0 ? 'fa-arrow-up text-success' : 'fa-arrow-down text-danger' }}"></i>
                        </span>
                        <p class="text-dark me-1 fs-3 mb-0">{{ $engagementGrowth >= 0 ? '+' : '' }}{{ $engagementGrowth }}%</p>
                        <p class="fs-3 mb-0 text-muted">vs anterior</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tasa de Engagement --}}
        <div class="col-md-6 col-xl-3">
            <div class="card w-100 h-100">
                <div class="card-body">
                    <h5 class="card-title fw-semibold mb-3">Tasa de Engagement</h5>
                    <h4 class="fw-semibold mb-2">{{ $engagementRate }}%</h4>
                    <p class="fs-3 mb-0 text-muted">Promedio del período</p>
                </div>
            </div>
        </div>

        {{-- Mejor Publicación --}}
        <div class="col-md-6 col-xl-3">
            <div class="card w-100 h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h5 class="card-title fw-semibold mb-3">Mejor Publicación</h5>
                            <h4 class="fw-semibold mb-2">{{ number_format($bestPostEngagement) }}</h4>
                            <p class="fs-3 mb-0 text-muted">Engagement récord</p>
                        </div>
                        <div class="col-4">
                            <div class="d-flex justify-content-end">
                                <span class="rounded-circle d-flex align-items-center justify-content-center best-post-icon">
                                    <i class="fas fa-trophy"></i>
                                </span>
                            </div>
                        </div>
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

@push('css')
<style>
    .btn-icon-sm { width: 30px; height: 30px; background: #f5f6f8; }
    .spark-arrow { width: 20px; height: 20px; }
    .best-post-icon { width: 44px; height: 44px; background: #fce8e8; color: #90bb13; }
    .nav-pills .nav-link.active { background-color: #90bb13; color: #fff !important; }
</style>
@endpush

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

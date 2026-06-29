@extends('layouts.theme')

@section('title', 'Mejores Momentos para Publicar')

@section('content')

    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1">
                        <i class="fas fa-chart-line me-2"></i>Mejores Momentos para Publicar
                    </h2>
                    <p class="text-muted mb-0">Análisis con IA de tus mejores horarios basado en engagement histórico</p>
                </div>
            </div>

            @if(empty($analysis))
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-chart-scatter fs-1 text-muted mb-3 d-block"></i>
                        <h5>No hay suficientes datos</h5>
                        <p class="text-muted">Publica al menos 10 posts con engagement para obtener recomendaciones personalizadas.</p>
                    </div>
                </div>
            @else
                <!-- Network Tabs -->
                <ul class="nav nav-tabs mb-4" role="tablist">
                    @foreach($analysis as $network => $data)
                        <li class="nav-item">
                            <a class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" href="#{{ $network }}-tab">
                                @php
                                    $iconClass = match($network) {
                                        'facebook' => 'ti-brand-facebook text-primary',
                                        'instagram' => 'ti-brand-instagram text-danger',
                                        'twitter' => 'ti-brand-x text-dark',
                                        'linkedin' => 'ti-brand-linkedin text-info',
                                        default => 'ti-share'
                                    };
                                @endphp
                                <i class="ti {{ $iconClass }} me-2"></i>{{ ucfirst($network) }}
                                <span class="badge bg-light-secondary text-secondary ms-2">{{ $data['data_points'] }} posts</span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                <!-- Tab Content -->
                <div class="tab-content">
                    @foreach($analysis as $network => $data)
                        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="{{ $network }}-tab">

                            <!-- Recommendations Cards -->
                            @if(!empty($data['recommendations']))
                                <div class="row g-3 mb-4">
                                    @foreach($data['recommendations'] as $recommendation)
                                        @php
                                            $cardClass = match($recommendation['type']) {
                                                'primary' => 'border-success',
                                                'best_hour' => 'border-info',
                                                'best_day' => 'border-warning',
                                                'avoid' => '',
                                                default => 'border-secondary'
                                            };
                                            $iconClass = match($recommendation['type']) {
                                                'primary' => 'ti-star text-success',
                                                'best_hour' => 'ti-clock text-info',
                                                'best_day' => 'ti-calendar text-warning',
                                                'avoid' => 'ti-alert-triangle text-danger',
                                                default => 'ti-info-circle'
                                            };
                                        @endphp
                                        <div class="col-md-6 col-lg-3">
                                            <div class="card h-100 {{ $cardClass }}">
                                                <div class="card-body">
                                                    <div class="d-flex align-items-start mb-3">
                                                        <i class="ti {{ $iconClass }} fs-2 me-2"></i>
                                                        <div class="flex-grow-1">
                                                            <h6 class="mb-0">{{ $recommendation['title'] }}</h6>
                                                        </div>
                                                    </div>
                                                    <p class="small mb-2">{{ $recommendation['description'] }}</p>
                                                    @if(isset($recommendation['confidence']))
                                                        <div class="mt-2">
                                                            <span class="badge bg-light-{{ $recommendation['confidence'] === 'high' ? 'success' : ($recommendation['confidence'] === 'medium' ? 'warning' : 'danger') }} text-{{ $recommendation['confidence'] === 'high' ? 'success' : ($recommendation['confidence'] === 'medium' ? 'warning' : 'danger') }}">
                                                                Confianza: {{ ucfirst($recommendation['confidence']) }}
                                                            </span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <div class="row">
                                <!-- Best Hours Chart -->
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Top 5 Mejores Horas</h5>
                                        </div>
                                        <div class="card-body">
                                            @if(!empty($data['best_hours']))
                                                <div class="table-responsive">
                                                    <table class="table table-sm">
                                                        <thead>
                                                            <tr>
                                                                <th>Hora</th>
                                                                <th>Posts</th>
                                                                <th>Engagement</th>
                                                                <th></th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach(array_slice($data['best_hours'], 0, 5) as $hour)
                                                                <tr>
                                                                    <td><strong>{{ sprintf('%02d:00', $hour['hour']) }}</strong></td>
                                                                    <td>{{ $hour['posts_count'] }}</td>
                                                                    <td>{{ round($hour['avg_engagement'], 1) }}%</td>
                                                                    <td>
                                                                        <div class="progress" style="height: 5px;">
                                                                            <div class="progress-bar bg-success" style="width: {{ min(100, $hour['avg_engagement'] * 10) }}%"></div>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Best Days Chart -->
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h5 class="mb-0"><i class="fas fa-calendar me-2"></i>Top 3 Mejores Días</h5>
                                        </div>
                                        <div class="card-body">
                                            @if(!empty($data['best_days']))
                                                <div class="table-responsive">
                                                    <table class="table table-sm">
                                                        <thead>
                                                            <tr>
                                                                <th>Día</th>
                                                                <th>Posts</th>
                                                                <th>Engagement</th>
                                                                <th></th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach(array_slice($data['best_days'], 0, 3) as $day)
                                                                <tr>
                                                                    <td><strong>{{ $day['day_name'] }}</strong></td>
                                                                    <td>{{ $day['posts_count'] }}</td>
                                                                    <td>{{ round($day['avg_engagement'], 1) }}%</td>
                                                                    <td>
                                                                        <div class="progress" style="height: 5px;">
                                                                            <div class="progress-bar bg-info" style="width: {{ min(100, $day['avg_engagement'] * 10) }}%"></div>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Best Specific Times -->
                                <div class="col-12 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0"><i class="fas fa-bullseye me-2"></i>Top 10 Mejores Momentos Específicos</h5>
                                        </div>
                                        <div class="card-body">
                                            @if(!empty($data['best_times']))
                                                <div class="row g-2">
                                                    @foreach(array_slice($data['best_times'], 0, 10) as $index => $time)
                                                        <div class="col-md-6 col-lg-4">
                                                            <div class="card {{ $index === 0 ? 'border-success' : 'border-light' }}">
                                                                <div class="card-body p-3">
                                                                    <div class="d-flex justify-content-between align-items-start">
                                                                        <div>
                                                                            @if($index === 0)
                                                                                <i class="fas fa-crown text-warning mb-2" style="font-size: 1.5rem;"></i>
                                                                            @endif
                                                                            <h6 class="mb-1">{{ $time['day_name'] }}</h6>
                                                                            <p class="text-muted small mb-2">{{ sprintf('%02d:00', $time['hour']) }}</p>
                                                                        </div>
                                                                        <div class="text-end">
                                                                            <span class="badge bg-success">{{ round($time['avg_engagement'], 1) }}%</span>
                                                                            <small class="d-block text-muted mt-1">{{ $time['posts_count'] }} posts</small>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Hourly Heatmap -->
                                @if(!empty($data['engagement_by_hour']))
                                    <div class="col-12 mb-4">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Engagement por Hora del Día</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="d-flex flex-wrap gap-2">
                                                    @foreach($data['engagement_by_hour'] as $hour => $engagement)
                                                        @php
                                                            $maxEngagement = max($data['engagement_by_hour']);
                                                            $intensity = $maxEngagement > 0 ? ($engagement / $maxEngagement) : 0;
                                                            $opacity = 0.2 + ($intensity * 0.8);
                                                        @endphp
                                                        <div class="text-center" style="flex: 1 1 60px;">
                                                            <div class="p-2 rounded mb-1" style="background-color: rgba(40, 167, 69, {{ $opacity }}); min-height: 50px; display: flex; align-items: center; justify-content: center;">
                                                                <small class="fw-bold {{ $intensity > 0.5 ? 'text-white' : '' }}">
                                                                    {{ round($engagement, 1) }}
                                                                </small>
                                                            </div>
                                                            <small class="text-muted">{{ sprintf('%02d', $hour) }}</small>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <!-- Stats Summary -->
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Análisis basado en {{ $data['data_points'] }} posts</strong> publicados en los últimos {{ $data['period'] }} días
                                        @if(isset($data['accounts_analyzed']))
                                            de {{ $data['accounts_analyzed'] }} cuenta(s) de {{ ucfirst($network) }}.
                                        @endif
                                    </div>
                                </div>
                            </div>

                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

@endsection

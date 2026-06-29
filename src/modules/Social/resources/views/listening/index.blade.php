@extends('layouts.theme')

@section('title', 'Social Listening')

@section('content')

    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1">
                        <i class="fas fa-ear-listen me-2"></i>Social Listening
                    </h2>
                    <p class="text-muted mb-0">Monitorea keywords, hashtags y menciones en tiempo real</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" id="scanAllBtn">
                        <i class="fas fa-sync-alt"></i> Escanear Todo
                    </button>
                    <a href="{{ route('admin.social.listening.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nueva Keyword
                    </a>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card  border-primary border-3">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-muted mb-1">Keywords Activas</p>
                                    <h3 class="mb-0">{{ $stats['active_keywords'] }}</h3>
                                    <small class="text-muted">de {{ $stats['total_keywords'] }} total</small>
                                </div>
                                <div class="flex-shrink-0">
                                    <i class="fas fa-search fs-1 text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card  border-info border-3">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-muted mb-1">Menciones Totales</p>
                                    <h3 class="mb-0">{{ number_format($stats['total_mentions']) }}</h3>
                                    <small class="text-success">+{{ $stats['recent_mentions'] }} esta semana</small>
                                </div>
                                <div class="flex-shrink-0">
                                    <i class="fas fa-comments fs-1 text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card  border-success border-3">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-muted mb-1">Sentiment Positivo</p>
                                    <h3 class="mb-0">{{ $stats['sentiment_distribution']['positive'] }}</h3>
                                    @php
                                        $total = array_sum($stats['sentiment_distribution']);
                                        $percentage = $total > 0 ? round(($stats['sentiment_distribution']['positive'] / $total) * 100) : 0;
                                    @endphp
                                    <small class="text-muted">{{ $percentage }}% del total</small>
                                </div>
                                <div class="flex-shrink-0">
                                    <i class="fas fa-smile fs-1 text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card   border-3">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-muted mb-1">Sentiment Negativo</p>
                                    <h3 class="mb-0">{{ $stats['sentiment_distribution']['negative'] }}</h3>
                                    @php
                                        $negPercentage = $total > 0 ? round(($stats['sentiment_distribution']['negative'] / $total) * 100) : 0;
                                    @endphp
                                    <small class="text-muted">{{ $negPercentage }}% del total</small>
                                </div>
                                <div class="flex-shrink-0">
                                    <i class="fas fa-frown fs-1 text-danger"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Keywords List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Keywords Monitoreadas
                    </h5>
                </div>
                <div class="card-body">
                    @if($keywords->isEmpty())
                        <div class="text-center py-5">
                            <i class="fas fa-search fs-1 text-muted mb-3 d-block"></i>
                            <h5>No hay keywords configuradas</h5>
                            <p class="text-muted">Crea tu primera keyword para comenzar a monitorear redes sociales</p>
                            <a href="{{ route('admin.social.listening.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Crear Keyword
                            </a>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Keyword</th>
                                        <th>Tipo</th>
                                        <th>Redes</th>
                                        <th>Menciones</th>
                                        <th>Sentiment</th>
                                        <th>Última Búsqueda</th>
                                        <th>Estado</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($keywords as $keyword)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="badge me-2" style="background-color: {{ $keyword->color }}; width: 4px; height: 24px;"></span>
                                                    <div>
                                                        <strong>{{ $keyword->formatted_name }}</strong>
                                                        @if($keyword->notes)
                                                            <br><small class="text-muted">{{ Str::limit($keyword->notes, 50) }}</small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light-secondary text-secondary">
                                                    <i class="ti {{ $keyword->type_icon }} me-1"></i>
                                                    {{ $keyword->type_label }}
                                                </span>
                                            </td>
                                            <td>
                                                @foreach($keyword->networks as $network)
                                                    @php
                                                        $iconClass = match($network) {
                                                            'facebook' => 'ti-brand-facebook text-primary',
                                                            'instagram' => 'ti-brand-instagram text-danger',
                                                            'twitter' => 'ti-brand-x text-dark',
                                                            'linkedin' => 'ti-brand-linkedin text-info',
                                                            default => 'ti-share'
                                                        };
                                                    @endphp
                                                    <i class="ti {{ $iconClass }} me-1"></i>
                                                @endforeach
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.social.listening.mentions', $keyword) }}" class="text-decoration-none">
                                                    <strong>{{ number_format($keyword->mentions_count) }}</strong>
                                                    @if($keyword->recent_mentions > 0)
                                                        <span class="badge bg-success ms-1">+{{ $keyword->recent_mentions }}</span>
                                                    @endif
                                                </a>
                                            </td>
                                            <td>
                                                @php
                                                    $sentiment = $keyword->getSentimentDistribution();
                                                    $total = array_sum($sentiment);
                                                @endphp
                                                @if($total > 0)
                                                    <div class="d-flex gap-1">
                                                        <span class="badge bg-success" title="Positivo">{{ $sentiment['positive'] }}</span>
                                                        <span class="badge bg-secondary" title="Neutral">{{ $sentiment['neutral'] }}</span>
                                                        <span class="badge bg-danger" title="Negativo">{{ $sentiment['negative'] }}</span>
                                                    </div>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($keyword->last_scanned_at)
                                                    <small class="text-muted">{{ $keyword->last_scanned_at->diffForHumans() }}</small>
                                                @else
                                                    <span class="text-muted">Nunca</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input keyword-toggle"
                                                           type="checkbox"
                                                           data-keyword-id="{{ $keyword->id }}"
                                                           {{ $keyword->is_active ? 'checked' : '' }}>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary scan-keyword-btn"
                                                            data-keyword-id="{{ $keyword->id }}"
                                                            title="Escanear ahora">
                                                        <i class="fas fa-sync-alt"></i>
                                                    </button>
                                                    <a href="{{ route('admin.social.listening.mentions', $keyword) }}"
                                                       class="btn btn-outline-info"
                                                       title="Ver menciones">
                                                        <i class="fas fa-comments"></i>
                                                    </a>
                                                    <a href="{{ route('admin.social.listening.edit', $keyword) }}"
                                                       class="btn btn-outline-secondary"
                                                       title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('admin.social.listening.destroy', $keyword) }}"
                                                          method="POST"
                                                          class="d-inline"
                                                          onsubmit="return confirm('¿Eliminar esta keyword?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                class="btn btn-outline-danger"
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
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    // Scan All Keywords
    const scanAllBtn = document.getElementById('scanAllBtn');
    if (scanAllBtn) {
        scanAllBtn.addEventListener('click', async function() {
            if (!confirm('¿Escanear todas las keywords activas? Esto puede tomar varios minutos.')) {
                return;
            }

            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Escaneando...';

            try {
                const response = await fetch('{{ route('admin.social.listening.scan-all') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    alert('✅ ' + data.message);
                    window.location.reload();
                } else {
                    alert('❌ ' + data.message);
                }
            } catch (error) {
                alert('Error: ' + error.message);
            } finally {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-sync-alt"></i> Escanear Todo';
            }
        });
    }

    // Toggle Keyword Status
    document.querySelectorAll('.keyword-toggle').forEach(toggle => {
        toggle.addEventListener('change', async function() {
            const keywordId = this.dataset.keywordId;
            const originalState = this.checked;

            try {
                const response = await fetch('{{ route('admin.social.listening.toggle', ':id') }}'.replace(':id', keywordId), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (!data.success) {
                    this.checked = !originalState;
                    alert('Error al cambiar estado');
                }
            } catch (error) {
                this.checked = !originalState;
                alert('Error: ' + error.message);
            }
        });
    });

    // Scan Individual Keyword
    document.querySelectorAll('.scan-keyword-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const keywordId = this.dataset.keywordId;

            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            try {
                const response = await fetch('{{ route('admin.social.listening.scan', ':id') }}'.replace(':id', keywordId), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    alert('✅ ' + data.message);
                    window.location.reload();
                } else {
                    alert('❌ ' + data.message);
                }
            } catch (error) {
                alert('Error: ' + error.message);
            } finally {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-sync-alt"></i>';
            }
        });
    });
</script>
@endpush

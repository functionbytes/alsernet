@extends('layouts.admin')

@section('title', 'Tests A/B')

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
                            <i class="fas fa-flask me-2"></i>Tests A/B
                        </h5>
                        <p class="small mb-0 text-muted">Compara el rendimiento entre dos versiones de publicación</p>
                    </div>
                    <a href="{{ route('admin.social.ab-tests.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nuevo Test A/B
                    </a>
                </div>
            </div>

            <!-- A/B Tests Table -->
            <div class="card-body">
                @if($abTests->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Test</th>
                                    <th>Variantes</th>
                                    <th>Métrica</th>
                                    <th>Resultados</th>
                                    <th>Ganador</th>
                                    <th width="120">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($abTests as $test)
                                    <tr>
                                        <td>
                                            <div>
                                                <div class="fw-semibold">Test #{{ $test->id }}</div>
                                                <small class="text-muted">
                                                    {{ $test->created_at->format('d/m/Y H:i') }}
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column gap-1">
                                                <small class="text-muted">
                                                    <strong class="text-primary">Variante A:</strong>
                                                    {{ Str::limit($test->variantA->content, 40) }}
                                                </small>
                                                <small class="text-muted">
                                                    <strong class="text-info">Variante B:</strong>
                                                    {{ Str::limit($test->variantB->content, 40) }}
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light-primary text-primary">
                                                {{ ucfirst($test->metric) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-3">
                                                <div>
                                                    <div class="fw-bold text-primary">{{ $test->variant_a_score }}</div>
                                                    <small class="text-muted">Variante A</small>
                                                </div>
                                                <div class="align-self-center">
                                                    <i class="fas fa-code-compare text-muted"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-info">{{ $test->variant_b_score }}</div>
                                                    <small class="text-muted">Variante B</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($test->winner_post_id)
                                                @php
                                                    $isVariantA = $test->winner_post_id === $test->variant_a_post_id;
                                                @endphp
                                                <span class="badge bg-{{ $isVariantA ? 'primary' : 'info' }} fs-6">
                                                    <i class="fas fa-trophy me-1"></i>
                                                    Variante {{ $isVariantA ? 'A' : 'B' }}
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">
                                                    En progreso
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="{{ route('admin.social.ab-tests.show', $test) }}"
                                                   class="btn btn-sm btn-light"
                                                   data-bs-toggle="tooltip"
                                                   title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @if(!$test->winner_post_id)
                                                    <form action="{{ route('admin.social.ab-tests.complete', $test) }}"
                                                          method="POST">
                                                        @csrf
                                                        <button type="submit"
                                                                class="btn btn-sm btn-success"
                                                                data-bs-toggle="tooltip"
                                                                title="Finalizar test">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                <form action="{{ route('admin.social.ab-tests.destroy', $test) }}"
                                                      method="POST"
                                                      onsubmit="return confirm('¿Eliminar este test?')">
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
                    @if($abTests->hasPages())
                        <div class="mt-4">
                            {{ $abTests->links() }}
                        </div>
                    @endif
                @else
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-flask fs-1 text-muted"></i>
                        </div>
                        <h5 class="text-muted mb-2">No hay tests A/B</h5>
                        <p class="text-muted mb-4">Crea tests A/B para comparar el rendimiento de diferentes versiones</p>
                        <a href="{{ route('admin.social.ab-tests.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Crear Primer Test A/B
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection

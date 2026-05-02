@extends('layouts.admin')

@section('title', 'Campañas')

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
                            <i class="fas fa-bullseye me-2"></i>Campañas
                        </h5>
                        <p class="small mb-0 text-muted">Organiza tus publicaciones por campañas de marketing</p>
                    </div>
                    <a href="{{ route('admin.social.campaigns.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nueva Campaña
                    </a>
                </div>
            </div>

            <!-- Campaigns Table -->
            <div class="card-body">
                @if($campaigns->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Campaña</th>
                                    <th>Fechas</th>
                                    <th>Publicaciones</th>
                                    <th>Estado</th>
                                    <th width="150">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($campaigns as $campaign)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded" style="width: 4px; height: 40px; background-color: {{ $campaign->color }};"></div>
                                                <div>
                                                    <div class="fw-semibold">{{ $campaign->name }}</div>
                                                    @if($campaign->description)
                                                        <small class="text-muted">{{ Str::limit($campaign->description, 50) }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                @if($campaign->start_date)
                                                    <i class="fas fa-calendar me-1"></i>
                                                    {{ $campaign->start_date->format('d/m/Y') }}
                                                    @if($campaign->end_date)
                                                        - {{ $campaign->end_date->format('d/m/Y') }}
                                                    @endif
                                                @else
                                                    Sin fechas
                                                @endif
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light-primary text-primary">
                                                {{ $campaign->posts_count }} publicaciones
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $campaign->isActive() ? 'success' : 'secondary' }}">
                                                {{ $campaign->isActive() ? 'Activa' : 'Inactiva' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="{{ route('admin.social.campaigns.show', $campaign) }}"
                                                   class="btn btn-sm btn-light"
                                                   data-bs-toggle="tooltip"
                                                   title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.social.campaigns.edit', $campaign) }}"
                                                   class="btn btn-sm btn-light"
                                                   data-bs-toggle="tooltip"
                                                   title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('admin.social.campaigns.destroy', $campaign) }}"
                                                      method="POST"
                                                      onsubmit="return confirm('¿Eliminar esta campaña?')">
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
                @else
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-bullseye fs-1 text-muted"></i>
                        </div>
                        <h5 class="text-muted mb-2">No hay campañas creadas</h5>
                        <p class="text-muted mb-4">Crea tu primera campaña para organizar tus publicaciones</p>
                        <a href="{{ route('admin.social.campaigns.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nueva Campaña
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection

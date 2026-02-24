@extends('layouts.theme')

@section('title', 'Detalle de redireccion')

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-bottom p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">Detalle de redireccion</h5>
                            <p class="mb-0 text-muted small">Informacion completa de la redireccion configurada.</p>
                        </div>
                        <a href="{{ route('setting.seo.redirects.edit', $redirect) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-edit me-1"></i> Editar
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label  text-muted">Ruta origen</label>
                            <div class="form-control bg-light">
                                <code class="text-primary">{{ $redirect->source_path }}</code>
                            </div>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label  text-muted">Ruta destino</label>
                            <div class="form-control bg-light">
                                <code class="text-muted">{{ $redirect->target_path }}</code>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label  text-muted">Tipo de redireccion</label>
                            <div>
                                <span class="badge {{ $redirect->isPermanent() ? 'bg-success-subtle text-success' : 'bg-info-subtle text-info' }} fs-6">
                                    {{ $redirect->status_code }} - {{ $redirect->isPermanent() ? 'Permanente' : 'Temporal' }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label  text-muted">Estado</label>
                            <div>
                                <span class="badge {{ $redirect->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }} fs-6">
                                    {{ $redirect->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-1 fw-bold"><i class="fas fa-chart-line me-1"></i> Estadisticas</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <h3 class="mb-0 fw-bold">{{ number_format($redirect->hits_count) }}</h3>
                            <small class="text-muted">Total visitas</small>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Creado</small><br>
                            <strong>{{ $redirect->created_at->format('d/m/Y H:i') }}</strong>
                            <br><small class="text-muted">{{ $redirect->created_at->diffForHumans() }}</small>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Ultima actualizacion</small><br>
                            <strong>{{ $redirect->updated_at->format('d/m/Y H:i') }}</strong>
                            <br><small class="text-muted">{{ $redirect->updated_at->diffForHumans() }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-1 fw-bold"><i class="fas fa-cogs me-1"></i> Acciones</h5>
                </div>
                <div class="card-body d-grid gap-2">
                    <a href="{{ route('setting.seo.redirects.edit', $redirect) }}" class="btn btn-outline-primary">
                        <i class="fas fa-edit me-1"></i> Editar redireccion
                    </a>
                    <form action="{{ route('setting.seo.redirects.toggle-active', $redirect) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-outline-{{ $redirect->is_active ? 'warning' : 'success' }} w-100">
                            <i class="fas fa-{{ $redirect->is_active ? 'pause' : 'play' }} me-1"></i>
                            {{ $redirect->is_active ? 'Desactivar' : 'Activar' }}
                        </button>
                    </form>
                    <a href="{{ route('setting.seo.redirects.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Volver al listado
                    </a>
                    <hr class="my-1">
                    <button type="button" class="btn btn-outline-danger delete-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#delete-modal"
                            data-url="{{ route('setting.seo.redirects.destroy', $redirect) }}"
                            data-title="Eliminar: {{ $redirect->source_path }}">
                        <i class="fas fa-trash me-1"></i> Eliminar redireccion
                    </button>
                </div>
            </div>
        </div>
    </div>

    @include('core::components.delete')
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.delete-btn').on('click', function() {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });
});
</script>
@endpush

@extends('layouts.theme')

@section('title', 'Detalle de etiqueta')

@section('content')
<div class="container-fluid">
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-semibold mb-2">Detalle de etiqueta</h4>
                    <p class="text-muted mb-0">Información completa de la etiqueta</p>
                </div>
                <div>
                    <a href="{{ route('settings.chat.labels.edit', $label) }}" class="btn btn-primary me-1">
                        <i class="fas fa-edit me-1"></i> Editar
                    </a>
                    <a href="{{ route('settings.chat.labels.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    @include('core::components.alerts')

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold">Información general</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Título</label>
                            <p class="fw-semibold mb-0">{{ $label->name }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Color</label>
                            <div class="d-flex align-items-center gap-2">
                                <div style="width: 24px; height: 24px; background-color: {{ $label->color ?? '#6c757d' }}; border-radius: 4px; border: 1px solid rgba(0,0,0,0.1);"></div>
                                <span class="fw-semibold">{{ $label->color ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>

                    @if($label->description)
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Descripción</label>
                        <p class="mb-0">{{ $label->description }}</p>
                    </div>
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Mostrar en barra lateral</label>
                            <p class="mb-0">
                                @if($label->show_on_sidebar)
                                    <span class="badge bg-success-subtle text-success">Sí</span>
                                @else
                                    <span class="badge bg-info-subtle text-info">No</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold">Vista previa</h5>
                </div>
                <div class="card-body p-4 text-center">
                    <span class="badge fs-5 px-3 py-2" style="background-color: {{ $label->color ?? '#6c757d' }}; color: #fff;">
                        {{ $label->name }}
                    </span>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold">Fechas</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Creado</label>
                        <p class="mb-0">{{ $label->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div>
                        <label class="text-muted small mb-1">Última actualización</label>
                        <p class="mb-0">{{ $label->updated_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

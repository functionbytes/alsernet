@extends('layouts.theme')

@section('title', 'Editar respuesta predefinida')


@section('page_header')
    @include('core::components.card', ['title' => 'Editar respuesta predefinida'])
@endsection

@section('content')

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('settings.helpdesk.canned-replies.update', $cannedReply) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Editar respuesta predefinida</h5>
                        <small class="text-muted">{{ $cannedReply->title }}</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')
                        @include('helpdesk::settings.canned-replies._form')
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-save"></i> Guardar cambios
                        </button>
                        <a href="{{ route('settings.helpdesk.canned-replies.index') }}" class="btn btn-light w-100">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Estadisticas de uso</h6>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Veces usada</span>
                        <span class="fw-semibold">{{ number_format($cannedReply->usage_count) }}</span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Alcance</span>
                        <span>
                            @if($cannedReply->is_global)
                                <span class="badge bg-success-subtle text-success">Global</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Personal</span>
                            @endif
                        </span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Creada</span>
                        <span class="small">{{ $cannedReply->created_at->diffForHumans() }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted small">Ultima actualizacion</span>
                        <span class="small">{{ $cannedReply->updated_at->diffForHumans() }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

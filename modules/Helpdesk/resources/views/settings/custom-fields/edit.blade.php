@extends('layouts.theme')

@section('title', 'Editar campo personalizado')

@section('page_header')
    @include('core::components.card', ['title' => 'Editar campo personalizado'])
@endsection

@section('content')

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('settings.helpdesk.custom-fields.update', $field) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Editar campo personalizado</h5>
                        <small class="text-muted">{{ $field->label }}</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')
                        @include('helpdesk::settings.custom-fields._form')
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-save"></i> Guardar cambios
                        </button>
                        <a href="{{ route('settings.helpdesk.custom-fields.index') }}" class="btn btn-light w-100">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Informacion del campo</h6>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Clave</span>
                        <code>{{ $field->key }}</code>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Valores registrados</span>
                        <span class="fw-semibold">{{ $field->values()->count() }}</span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Creado</span>
                        <span class="small">{{ $field->created_at->format('d/m/Y') }}</span>
                    </div>
                    @if($field->values()->count() > 0)
                        <hr>
                        <p class="small text-warning mb-0">
                            <i class="fas fa-triangle-exclamation"></i>
                            Cambiar el tipo puede afectar los valores ya guardados.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection

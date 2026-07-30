@extends('layouts.theme')

@section('title', 'Editar skill')

@section('page_header')
    @include('core::components.card', ['title' => 'Editar skill'])
@endsection

@section('content')

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('settings.helpdesk.skills.update', $skill) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Editar skill</h5>
                        <small class="text-muted">{{ $skill->name }}</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')
                        @include('helpdesk::settings.skills._form', compact('skill'))
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-save"></i> Guardar cambios
                        </button>
                        <a href="{{ route('settings.helpdesk.skills.index') }}" class="btn btn-light w-100">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Informacion del skill</h6>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Agentes asignados</span>
                        <span class="fw-semibold">{{ number_format($skill->users_count) }}</span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Slug</span>
                        <code class="small">{{ $skill->slug }}</code>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Creado</span>
                        <span class="small">{{ $skill->created_at->diffForHumans() }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted small">Ultima actualizacion</span>
                        <span class="small">{{ $skill->updated_at->diffForHumans() }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

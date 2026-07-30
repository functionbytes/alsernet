@extends('layouts.theme')

@section('title', 'Editar empresa')

@section('content')

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('settings.helpdesk.companies.update', $company) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Editar empresa</h5>
                        <small class="text-muted">{{ $company->name }}</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')
                        @include('helpdesk::settings.companies._form', ['company' => $company])
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-save"></i> Guardar cambios
                        </button>
                        <a href="{{ route('settings.helpdesk.companies.index') }}" class="btn btn-light w-100">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Informacion de la empresa</h6>
                    <ul class="list-unstyled text-muted small mb-0">
                        <li class="mb-2">
                            <strong>Clientes:</strong> {{ $company->customers()->count() }}
                        </li>
                        <li class="mb-2">
                            <strong>Estado:</strong>
                            <span class="badge bg-{{ $company->getHealthColor() }}-subtle text-{{ $company->getHealthColor() }}">
                                {{ $company->getHealthLabel() }}
                            </span>
                        </li>
                        <li>
                            <strong>Creada:</strong> {{ $company->created_at->format('d/m/Y') }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

@endsection

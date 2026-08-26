@extends('layouts.theme')

@section('title', 'Comparar roles')

@section('page_header')
    @include('core::components.card', ['title' => 'Comparar roles'])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="card">

        {{-- Header --}}
        <div class="card-header p-4 border-bottom border-light">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Comparar roles</h5>
                    <p class="small mb-0 text-muted">Visualiza las diferencias de permisos entre dos roles</p>
                </div>
                <a href="{{ route('settings.roles.index') }}" class="btn btn-outline-secondary btn-sm">
                    Volver al listado
                </a>
            </div>
        </div>

        {{-- Form --}}
        <form action="{{ route('settings.roles.compare') }}" method="GET" class="card-body border-bottom">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Rol A</label>
                    <select name="role_a" class="form-select">
                        <option value="">Selecciona rol A...</option>
                        @foreach($roles as $r)
                            <option value="{{ $r->id }}" {{ request('role_a') == $r->id ? 'selected' : '' }}>
                                {{ $r->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Rol B</label>
                    <select name="role_b" class="form-select">
                        <option value="">Selecciona rol B...</option>
                        @foreach($roles as $r)
                            <option value="{{ $r->id }}" {{ request('role_b') == $r->id ? 'selected' : '' }}>
                                {{ $r->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Comparar</button>
                </div>
            </div>
        </form>

        <div class="card-body">
            @if($roleA && $roleB)

                {{-- Stats --}}
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card bg-danger-subtle h-100">
                            <div class="card-body">
                                <h6 class="card-title text-danger mb-2">Solo en {{ $roleA->name }}</h6>
                                <h4 class="mb-1 fw-bold">{{ $onlyInA->count() }}</h4>
                                <small class="text-muted">Permisos exclusivos</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success-subtle h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">En ambos</h6>
                                <h4 class="mb-1 fw-bold">{{ $inBoth->count() }}</h4>
                                <small class="text-muted">Permisos compartidos</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info-subtle h-100">
                            <div class="card-body">
                                <h6 class="card-title  mb-2">Solo en {{ $roleB->name }}</h6>
                                <h4 class="mb-1 fw-bold">{{ $onlyInB->count() }}</h4>
                                <small class="text-muted">Permisos exclusivos</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total únicos</h6>
                                <h4 class="mb-1 fw-bold">{{ $onlyInA->count() + $onlyInB->count() + $inBoth->count() }}</h4>
                                <small class="text-muted">Permisos distintos</small>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Comparison columns --}}
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card border h-100">
                            <div class="card-header bg-danger-subtle border-0">
                                <h6 class="mb-0 fw-bold text-danger">Solo en {{ $roleA->name }}</h6>
                            </div>
                            <div class="card-body">
                                @forelse($onlyInA as $perm)
                                    <span class="badge bg-danger-subtle text-danger me-1 mb-2">{{ $perm }}</span>
                                @empty
                                    <p class="text-muted mb-0 small">Sin permisos exclusivos</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border h-100">
                            <div class="card-header bg-success-subtle border-0">
                                <h6 class="mb-0 fw-bold">En ambos roles</h6>
                            </div>
                            <div class="card-body">
                                @forelse($inBoth as $perm)
                                    <span class="badge bg-success-subtle text-success me-1 mb-2">{{ $perm }}</span>
                                @empty
                                    <p class="text-muted mb-0 small">Sin permisos compartidos</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border h-100">
                            <div class="card-header bg-info-subtle border-0">
                                <h6 class="mb-0 fw-bold">Solo en {{ $roleB->name }}</h6>
                            </div>
                            <div class="card-body">
                                @forelse($onlyInB as $perm)
                                    <span class="badge bg-info-subtle text-info me-1 mb-2">{{ $perm }}</span>
                                @empty
                                    <p class="text-muted mb-0 small">Sin permisos exclusivos</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

            @else
                <div class="alert alert-info border-0">
                    <i class="fas fa-circle-info me-2"></i>
                    Selecciona dos roles para comparar sus permisos.
                </div>
            @endif
        </div>

    </div>

@endsection

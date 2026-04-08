@extends('layouts.theme')

@section('title', 'Nueva Campaña')

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <h4 class="fw-semibold mb-3">Nueva Campaña</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('manager.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('manager.helpdesk.campaigns.index') }}">Campañas</a></li>
                    <li class="breadcrumb-item active">Nueva</li>
                </ol>
            </nav>
        </div>
    </div>

    {{-- Basic Info Card --}}
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Información Básica</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('manager.helpdesk.campaigns.store') }}">
                @csrf

                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label class="form-label">Nombre de Campaña</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Tipo de Campaña</label>
                            <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                                <option value="">Seleccionar tipo...</option>
                                <option value="popup" {{ old('type') === 'popup' ? 'selected' : '' }}>Pop-up</option>
                                <option value="banner" {{ old('type') === 'banner' ? 'selected' : '' }}>Banner</option>
                                <option value="slide-in" {{ old('type') === 'slide-in' ? 'selected' : '' }}>Slide-in</option>
                                <option value="full-screen" {{ old('type') === 'full-screen' ? 'selected' : '' }}>Pantalla Completa</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Estado</label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                <option value="draft" {{ old('status', 'draft') === 'draft' ? 'selected' : '' }}>Borrador</option>
                                <option value="scheduled" {{ old('status') === 'scheduled' ? 'selected' : '' }}>Programada</option>
                                <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Activa</option>
                                <option value="paused" {{ old('status') === 'paused' ? 'selected' : '' }}>Pausada</option>
                                <option value="ended" {{ old('status') === 'ended' ? 'selected' : '' }}>Finalizada</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('manager.helpdesk.campaigns.index') }}" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> Volver
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-plus"></i> Crear Campaña
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

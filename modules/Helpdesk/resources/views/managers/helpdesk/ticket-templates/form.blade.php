@extends('layouts.helpdesk')

@section('title', ($template ? 'Editar' : 'Nueva') . ' plantilla - Helpdesk')

@section('content')
    {{-- Header --}}
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-semibold mb-3">
                        {{ $template ? 'Editar plantilla' : 'Nueva plantilla' }}
                    </h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="{{ route('manager.dashboard') }}">Dashboard</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="{{ route('ticket-templates.index') }}">Plantillas de ticket</a>
                            </li>
                            <li class="breadcrumb-item active">
                                {{ $template ? 'Editar' : 'Nueva' }}
                            </li>
                        </ol>
                    </nav>
                </div>
                <a href="{{ route('ticket-templates.index') }}" class="btn btn-light">
                    <i class="fas fa-arrow-left me-2"></i>Volver
                </a>
            </div>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ $template ? route('ticket-templates.update', $template) : route('ticket-templates.store') }}"
          method="POST">
        @csrf
        @if($template)
            @method('PUT')
        @endif

        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-file-alt me-2"></i>Contenido de la plantilla
                        </h5>
                    </div>
                    <div class="card-body">
                        {{-- Name --}}
                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">
                                Nombre <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name"
                                   value="{{ old('name', $template?->name) }}"
                                   placeholder="Ej. Soporte técnico general" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Description --}}
                        <div class="mb-3">
                            <label for="description" class="form-label fw-semibold">Descripción</label>
                            <input type="text" class="form-control @error('description') is-invalid @enderror"
                                   id="description" name="description"
                                   value="{{ old('description', $template?->description) }}"
                                   placeholder="Breve descripción del uso de esta plantilla">
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Subject --}}
                        <div class="mb-3">
                            <label for="subject" class="form-label fw-semibold">
                                Asunto <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('subject') is-invalid @enderror"
                                   id="subject" name="subject"
                                   value="{{ old('subject', $template?->subject) }}"
                                   placeholder="Asunto del ticket" required>
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Body --}}
                        <div class="mb-3">
                            <label for="body" class="form-label fw-semibold">
                                Cuerpo <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control @error('body') is-invalid @enderror"
                                      id="body" name="body" rows="8"
                                      placeholder="Contenido de la plantilla..." required>{{ old('body', $template?->body) }}</textarea>
                            @error('body')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-cog me-2"></i>Configuración
                        </h5>
                    </div>
                    <div class="card-body">
                        {{-- Category --}}
                        <div class="mb-3">
                            <label for="category_id" class="form-label fw-semibold">Categoría</label>
                            <select class="form-select @error('category_id') is-invalid @enderror"
                                    id="category_id" name="category_id">
                                <option value="">Sin categoría</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}"
                                        {{ old('category_id', $template?->category_id) == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Priority --}}
                        <div class="mb-3">
                            <label for="priority_id" class="form-label fw-semibold">Prioridad</label>
                            <select class="form-select @error('priority_id') is-invalid @enderror"
                                    id="priority_id" name="priority_id">
                                <option value="">Sin prioridad</option>
                                @foreach($priorities as $priority)
                                    <option value="{{ $priority->id }}"
                                        {{ old('priority_id', $template?->priority_id) == $priority->id ? 'selected' : '' }}>
                                        {{ $priority->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('priority_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Active --}}
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox"
                                       id="is_active" name="is_active" value="1"
                                       {{ old('is_active', $template?->is_active ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Plantilla activa</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        {{ $template ? 'Actualizar plantilla' : 'Crear plantilla' }}
                    </button>
                    <a href="{{ route('ticket-templates.index') }}" class="btn btn-outline-secondary">
                        Cancelar
                    </a>
                </div>
            </div>
        </div>
    </form>
@endsection

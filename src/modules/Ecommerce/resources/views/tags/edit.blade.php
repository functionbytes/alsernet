@extends('layouts.theme')

@section('title', 'Editar etiqueta')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Editar etiqueta'])
@endsection

@section('content')
    @include('core::components.alerts')

    <form action="{{ route('ecommerce.tags.update', $tag) }}" method="POST">
        @csrf @method('PUT')

        <div class="row g-4 align-items-start">

            {{-- Columna principal --}}
            <div class="col-lg-8">

                {{-- Información --}}
                <div class="card mb-4">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Información de la etiqueta</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="tag-name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $tag->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Enlace permanente --}}
                        @if($tag->slug)
                            <div class="mb-3 p-3 bg-light rounded border">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted fw-semibold text-uppercase" style="letter-spacing:.4px">Enlace permanente</small>
                                    <a href="{{ url('tags/' . $tag->slug) }}" target="_blank" class="small text-decoration-none">
                                        Vista <i class="fas fa-external-link-alt ms-1"></i>
                                    </a>
                                </div>
                                <div class="mt-1 small text-break">
                                    <span class="text-muted">{{ rtrim(url('/'), '/') }}/tags/</span><strong>{{ $tag->slug }}</strong>
                                </div>
                            </div>
                        @endif

                        <div class="mb-0">
                            <label class="form-label">Descripción</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                                rows="4">{{ old('description', $tag->description) }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                {{-- SEO --}}
                <div class="card mb-4">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Optimizar para motores de búsqueda</h6>
                    </div>
                    <div class="card-body">
                        @if($tag->slug)
                            <div class="border rounded p-3 bg-light mb-4">
                                <div class="small text-muted mb-2 fw-semibold">Vista previa en buscador</div>
                                <div class="text-primary fw-semibold" style="font-size:.95rem">
                                    {{ $tag->name }} — {{ config('app.name') }}
                                </div>
                                <div class="small text-success">{{ url('tags/' . $tag->slug) }}</div>
                                <div class="small text-muted mt-1">
                                    {{ $tag->description ? Str::limit($tag->description, 160) : 'Sin descripción.' }}
                                </div>
                            </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label">Título SEO</label>
                            <input type="text" name="meta_title" class="form-control"
                                value="{{ old('meta_title', $tag->meta_title ?? '') }}"
                                placeholder="{{ $tag->name }}" maxlength="255">
                            <div class="form-text">Recomendado: 50–60 caracteres.</div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Descripción SEO</label>
                            <textarea name="meta_description" class="form-control" rows="3"
                                placeholder="Descripción para motores de búsqueda...">{{ old('meta_description', $tag->meta_description ?? '') }}</textarea>
                            <div class="form-text">Recomendado: 150–160 caracteres.</div>
                        </div>
                    </div>
                </div>

            </div>{{-- /col-lg-8 --}}

            {{-- Sidebar --}}
            <div class="col-lg-4">
                <div class="sticky-top" style="top:80px">

                    {{-- Publicar --}}
                    <div class="card mb-4">
                        <div class="card-body">
                            <button type="submit" class="btn btn-primary w-100 mb-2">Actualizar etiqueta</button>
                            <a href="{{ route('ecommerce.tags.index') }}" class="btn btn-outline-secondary w-100">Cancelar</a>
                        </div>
                    </div>

                    {{-- Estado --}}
                    <div class="card mb-4">
                        <div class="card-header p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">Estado</h6>
                        </div>
                        <div class="card-body">
                            <select name="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="published" {{ old('status', $tag->status) === 'published' ? 'selected' : '' }}>Publicado</option>
                                <option value="draft" {{ old('status', $tag->status) === 'draft' ? 'selected' : '' }}>Borrador</option>
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    {{-- Info --}}
                    <div class="card mb-4">
                        <div class="card-header p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">Detalles</h6>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item px-3 py-2 d-flex justify-content-between small">
                                    <span class="text-muted">Creado</span>
                                    <span>{{ $tag->created_at->translatedFormat('j M, Y') }}</span>
                                </li>
                                <li class="list-group-item px-3 py-2 d-flex justify-content-between small">
                                    <span class="text-muted">Actualizado</span>
                                    <span>{{ $tag->updated_at->translatedFormat('j M, Y') }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                </div>
            </div>{{-- /col-lg-4 --}}

        </div>
    </form>
@endsection

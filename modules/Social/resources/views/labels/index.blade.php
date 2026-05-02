@extends('layouts.admin')

@section('title', 'Etiquetas')

@section('content')

    <div class="widget-content">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card">
            <div class="card-header p-4 border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">
                            <i class="fas fa-tag me-2"></i>Etiquetas
                        </h5>
                        <p class="small mb-0 text-muted">Organiza tus publicaciones con etiquetas personalizadas</p>
                    </div>
                    <a href="{{ route('admin.social.labels.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nueva Etiqueta
                    </a>
                </div>
            </div>

            <div class="card-body">
                @if($labels->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Etiqueta</th>
                                    <th>Descripción</th>
                                    <th>Publicaciones</th>
                                    <th width="150">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($labels as $label)
                                    <tr>
                                        <td>
                                            <span class="badge px-3 py-2" style="background-color: {{ $label->color }};">
                                                <i class="fas fa-tag me-1"></i>{{ $label->name }}
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $label->description ?? '-' }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light-primary text-primary">
                                                {{ $label->posts_count }} posts
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="{{ route('admin.social.labels.edit', $label) }}"
                                                   class="btn btn-sm btn-light">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('admin.social.labels.destroy', $label) }}"
                                                      method="POST"
                                                      onsubmit="return confirm('¿Eliminar esta etiqueta?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-light-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-tag fs-1 text-muted mb-3 d-block"></i>
                        <h5 class="text-muted mb-2">No hay etiquetas</h5>
                        <p class="text-muted mb-4">Crea etiquetas para organizar tus publicaciones</p>
                        <a href="{{ route('admin.social.labels.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nueva Etiqueta
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection

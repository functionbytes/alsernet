@extends('layouts.helpdesk')

@section('title', 'Plantillas de ticket - Helpdesk')

@section('content')
    {{-- Header --}}
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-semibold mb-3">Plantillas de ticket</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="{{ route('manager.dashboard') }}">Dashboard</a>
                            </li>
                            <li class="breadcrumb-item active">Plantillas de ticket</li>
                        </ol>
                    </nav>
                </div>
                <a href="{{ route('ticket-templates.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Nueva plantilla
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Asunto</th>
                            <th>Categoría</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($templates as $template)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $template->name }}</div>
                                    @if($template->description)
                                        <small class="text-muted">{{ $template->description }}</small>
                                    @endif
                                </td>
                                <td>{{ $template->subject }}</td>
                                <td>
                                    @if($template->category)
                                        <span class="badge bg-light text-dark border">
                                            {{ $template->category->name }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($template->is_active)
                                        <span class="badge bg-success">Activa</span>
                                    @else
                                        <span class="badge bg-secondary">Inactiva</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('ticket-templates.edit', $template) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('ticket-templates.destroy', $template) }}"
                                          method="POST" class="d-inline"
                                          onsubmit="return confirm('¿Eliminar esta plantilla?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="fas fa-file-alt fa-2x mb-2 d-block"></i>
                                    No hay plantillas creadas aún.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($templates->hasPages())
            <div class="card-footer">
                {{ $templates->links() }}
            </div>
        @endif
    </div>
@endsection

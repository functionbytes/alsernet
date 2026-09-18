@extends('layouts.theme')

@section('title', 'Versiones — ' . $chatFlow->name)

@section('page_header')
    @include('core::components.card', ['title' => 'Versiones del flow'])
@endsection

@section('content')

    @include('core::components.alerts')

    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('chatflow.index') }}">Chat flows</a></li>
            <li class="breadcrumb-item"><a href="{{ route('chatflow.edit', $chatFlow) }}">{{ $chatFlow->name }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Versiones</li>
        </ol>
    </nav>

    <div class="card">
        <div class="card-header p-4 border-bottom border-light d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-1 fw-bold">Versiones publicadas</h6>
                <p class="small mb-0 text-muted">Cada vez que publicas el flow se guarda una versión que puedes restaurar</p>
            </div>
            <a href="{{ route('chatflow.export', $chatFlow) }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-download me-1"></i> Exportar JSON
            </a>
        </div>
        <div class="card-body">
            @if($versions->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Versión</th>
                                <th>Nombre</th>
                                <th>Pasos</th>
                                <th>Publicada</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($versions as $version)
                                <tr>
                                    <td class="fw-semibold">#{{ $version->id }}</td>
                                    <td>{{ $version->name }}</td>
                                    <td><span class="text-muted small">{{ count($version->nodes ?? []) }} nodos</span></td>
                                    <td><span class="text-muted small">{{ $version->created_at->diffForHumans() }}</span></td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                                <i class="fas fa-ellipsis-vertical"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <form action="{{ route('chatflow.versions.restore', [$chatFlow, $version->id]) }}" method="POST"
                                                          onsubmit="return confirm('¿Restaurar esta versión? Sobrescribe el flow actual.');">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item">Restaurar</button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-clock-rotate-left fa-3x mb-3 text-muted opacity-50"></i>
                    <h6 class="fw-bold mb-2">Sin versiones todavía</h6>
                    <p class="text-muted mb-0">Cuando publiques el flow se guardará una versión que podrás restaurar.</p>
                </div>
            @endif
        </div>
        @if($versions->hasPages())
            <div class="card-footer bg-white border-top">
                {{ $versions->links() }}
            </div>
        @endif
    </div>

@endsection

@extends('layouts.theme')

@section('title', 'Respuestas predefinidas')

@section('content')

    @include('core::components.alerts')

    <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Respuestas predefinidas</h5>
                        <p class="small mb-0 text-muted">Textos de respuesta rapida que los agentes pueden insertar durante una conversacion</p>
                    </div>
                    @can('helpdesk.canned-replies.create')
                        <a href="{{ route('settings.helpdesk.canned-replies.create') }}" class="btn btn-primary">
                            Nueva respuesta predefinida
                        </a>
                    @endcan
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title text-primary mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                                <small class="text-muted">Respuestas configuradas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title text-success mb-2">Globales</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['global'] }}</h4>
                                <small class="text-muted">Disponibles para todos los agentes</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title text-warning mb-2">Personales</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['personal'] }}</h4>
                                <small class="text-muted">Privadas por agente</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.helpdesk.canned-replies.index') }}">
                    <div class="row align-items-center g-2">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="search" name="search" class="form-control"
                                    placeholder="Buscar por titulo, cuerpo o categoria..."
                                    value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="category" class="form-select">
                                <option value="">Todas las categorias</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="scope" class="form-select">
                                <option value="">Cualquier alcance</option>
                                <option value="global" @selected(request('scope') === 'global')>Globales</option>
                                <option value="personal" @selected(request('scope') === 'personal')>Personales</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($cannedReplies->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Titulo</th>
                                    <th>Atajo</th>
                                    <th>Categoria</th>
                                    <th class="text-center">Alcance</th>
                                    <th class="text-center">Uso</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cannedReplies as $cannedReply)
                                    <tr>
                                        <td>
                                            <strong>{{ $cannedReply->title }}</strong>
                                            @if($cannedReply->body)
                                                <div>
                                                    <small class="text-muted">{{ Str::limit(strip_tags($cannedReply->body), 60) }}</small>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($cannedReply->shortcut)
                                                <code class="small">/{{ $cannedReply->shortcut }}</code>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($cannedReply->category)
                                                <span class="badge bg-secondary-subtle text-secondary">{{ $cannedReply->category }}</span>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($cannedReply->is_global)
                                                <span class="badge bg-success-subtle text-success">Global</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Personal</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="fw-semibold">{{ number_format($cannedReply->usage_count) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    @can('helpdesk.canned-replies.update')
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('settings.helpdesk.canned-replies.edit', $cannedReply) }}">
                                                                Editar
                                                            </a>
                                                        </li>
                                                    @endcan
                                                    @can('helpdesk.canned-replies.delete')
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <button class="dropdown-item btn-delete"
                                                                data-id="{{ $cannedReply->id }}"
                                                                data-url="{{ route('settings.helpdesk.canned-replies.destroy', $cannedReply) }}"
                                                                data-name="{{ $cannedReply->title }}">
                                                                Eliminar
                                                            </button>
                                                        </li>
                                                    @endcan
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
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-comment-dots fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay respuestas predefinidas</h6>
                            <p class="text-muted mb-3">
                                @if(request('search') || request('category') || request('scope'))
                                    No se encontraron resultados para los filtros aplicados
                                @else
                                    Crea tu primera respuesta predefinida para agilizar las conversaciones
                                @endif
                            </p>
                            @if(! request('search') && ! request('category') && ! request('scope'))
                                @can('helpdesk.canned-replies.create')
                                    <a href="{{ route('settings.helpdesk.canned-replies.create') }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-plus"></i> Crear primera respuesta
                                    </a>
                                @endcan
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            @if($cannedReplies->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando <strong>{{ $cannedReplies->firstItem() }}</strong> a <strong>{{ $cannedReplies->lastItem() }}</strong>
                            de <strong>{{ $cannedReplies->total() }}</strong> respuestas
                        </div>
                        {{ $cannedReplies->appends(request()->input())->links() }}
                    </div>
                </div>
            @endif

    </div>

    @include('core::components.delete')

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $(document).on('click', '.btn-delete', function () {
        const url = $(this).data('url');
        const name = $(this).data('name');
        $('#deleteForm').attr('action', url);
        $('#deleteItemName').text(name);
        $('#deleteModal').modal('show');
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif

    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush

@extends('layouts.theme')

@section('title', 'Historial de lista negra')

@section('page_header')
    @include('core::components.card', ['title' => 'Historial de lista negra'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Historial de correos bloqueados</h5>
                        <p class="small mb-0 text-muted">Cada fila es un correo entrante descartado por una regla de la lista negra, con su remitente exacto y asunto</p>
                    </div>
                    <div class="ms-auto">
                        <a href="{{ route('manager.helpdesk.settings.ticket-blacklist.index') }}" class="btn btn-outline-secondary">
                            Volver a la lista negra
                        </a>
                    </div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('manager.helpdesk.settings.ticket-blacklist.history') }}" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Regla</label>
                        <select name="blacklist_id" class="form-select select2">
                            <option value="">Todas las reglas</option>
                            @foreach($rules as $rule)
                                <option value="{{ $rule->id }}" {{ (string) request('blacklist_id') === (string) $rule->id ? 'selected' : '' }}>
                                    {{ $rule->type === 'domain' ? 'Dominio' : 'Email' }}: {{ $rule->value }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Desde</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Hasta</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">Filtrar</button>
                    </div>
                    <div class="col-12">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-1">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="search" name="search" class="form-control ps-0"
                                   placeholder="Buscar por remitente o asunto..."
                                   value="{{ request('search') }}">
                        </div>
                    </div>
                    @if(request()->hasAny(['blacklist_id', 'date_from', 'date_to', 'search']))
                        <div class="col-12">
                            <a href="{{ route('manager.helpdesk.settings.ticket-blacklist.history') }}" class="small">Limpiar filtros</a>
                        </div>
                    @endif
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($hits->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Remitente bloqueado</th>
                                    <th>Asunto</th>
                                    <th>Regla que lo bloqueó</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($hits as $hit)
                                    <tr>
                                        <td><small class="text-muted">{{ $hit->created_at->format('d/m/Y H:i') }}</small></td>
                                        <td><span class="fw-semibold">{{ $hit->from_email }}</span></td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $hit->subject ? Str::limit($hit->subject, 60) : '—' }}
                                            </small>
                                        </td>
                                        <td>
                                            @if($hit->blacklist)
                                                <span class="badge bg-secondary-subtle text-secondary">
                                                    {{ $hit->blacklist->type === 'domain' ? 'Dominio' : 'Email' }}
                                                </span>
                                                {{ $hit->blacklist->value }}
                                            @else
                                                <span class="text-muted">Regla eliminada</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('manager.helpdesk.settings.ticket-blacklist.history.preview', $hit->id) }}" class="btn btn-sm btn-outline-secondary">
                                                Vista previa
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x mb-3 text-muted opacity-50"></i>
                        <h5 class="fw-bold mb-2">
                            @if(request()->hasAny(['blacklist_id', 'date_from', 'date_to', 'search']))
                                No se encontraron resultados
                            @else
                                Todavía no se ha bloqueado ningún correo
                            @endif
                        </h5>
                        <p class="text-muted mb-0">
                            @if(request()->hasAny(['blacklist_id', 'date_from', 'date_to', 'search']))
                                No hay coincidencias con los filtros aplicados
                            @else
                                Aquí aparecerá cada correo entrante que la lista negra descarte
                            @endif
                        </p>
                    </div>
                @endif
            </div>

            {{-- Pagination --}}
            @if($hits->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando {{ $hits->firstItem() }} - {{ $hits->lastItem() }} de {{ $hits->total() }}
                        </div>
                        <div>
                            {{ $hits->appends(request()->input())->links() }}
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('.select2').select2({ width: '100%' });
});
</script>
@endpush

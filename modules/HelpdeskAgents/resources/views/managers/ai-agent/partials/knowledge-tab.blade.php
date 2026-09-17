{{-- Knowledge tab partial - loaded via AJAX into #knowledge-container --}}

@php
    $total = $knowledge->total();
    $active = $knowledge->where('is_active', true)->count();
    $inactive = $total - $active;
@endphp

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-1 fw-bold">Base de conocimiento</h5>
        <p class="text-muted mb-0 small">Documentos que el agente utiliza para generar respuestas precisas</p>
    </div>
    <button type="button" class="btn btn-primary" id="btn-new-knowledge">
        Nuevo documento
    </button>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="card bg-light-secondary h-100">
            <div class="card-body">
                <h6 class="card-title mb-2">Total</h6>
                <h4 class="mb-1 fw-bold">{{ number_format($total) }}</h4>
                <small class="text-muted">Documentos registrados</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card bg-light-secondary h-100">
            <div class="card-body">
                <h6 class="card-title mb-2">Activos</h6>
                <h4 class="mb-1 fw-bold">{{ number_format($active) }}</h4>
                <small class="text-muted">Indexados por el agente</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card bg-light-secondary h-100">
            <div class="card-body">
                <h6 class="card-title mb-2">Inactivos</h6>
                <h4 class="mb-1 fw-bold">{{ number_format($inactive) }}</h4>
                <small class="text-muted">No usados por el agente</small>
            </div>
        </div>
    </div>
</div>

{{-- Table --}}
@if($knowledge->isEmpty())
    <div class="text-center py-5">
        <i class="fas fa-brain fa-3x mb-3 text-muted opacity-50"></i>
        <h5 class="fw-bold mb-2">No hay documentos en la base de conocimiento</h5>
        <p class="text-muted mb-4">Agrega documentos, FAQs o artículos para que el agente responda con mas precision.</p>
        <button type="button" class="btn btn-primary" id="btn-new-knowledge-empty">
            Nuevo documento
        </button>
    </div>
@else
    <div class="table-responsive">
        <table class="table table-hover align-middle text-nowrap">
            <thead class="table-light">
                <tr>
                    <th>Título</th>
                    <th>Tipo</th>
                    <th>Usos</th>
                    <th>Embedding</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($knowledge as $item)
                    @php
                        $typeLabels = ['document' => 'Documento', 'faq' => 'FAQ', 'article' => 'Articulo', 'manual' => 'Manual', 'url' => 'URL'];
                    @endphp
                    <tr data-count-item>
                        <td>
                            <span class="fw-semibold">{{ Str::limit($item->title, 50) }}</span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark">{{ $typeLabels[$item->type] ?? $item->type }}</span>
                        </td>
                        <td>
                            <small class="text-muted">{{ $item->usage_count ?? 0 }} veces</small>
                        </td>
                        <td>
                            @if($item->embedding_model)
                                <span class="badge bg-success-subtle text-success">Generado</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Sin embedding</span>
                            @endif
                        </td>
                        <td>
                            @if($item->is_active)
                                <span class="badge bg-success-subtle text-success">Activo</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Inactivo</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="dropdown">
                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                    <i class="fas fa-ellipsis-vertical"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item knowledge-edit-btn" href="#" data-id="{{ $item->id }}">
                                            Editar
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item knowledge-toggle-btn" href="#"
                                           data-id="{{ $item->id }}"
                                           data-active="{{ $item->is_active ? '1' : '0' }}">
                                            {{ $item->is_active ? 'Desactivar' : 'Activar' }}
                                        </a>
                                    </li>
                                    @unless($item->embedding_model)
                                        <li>
                                            <a class="dropdown-item knowledge-embedding-btn" href="#"
                                               data-id="{{ $item->id }}">
                                                Generar embedding
                                            </a>
                                        </li>
                                    @endunless
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item knowledge-delete-btn" href="#"
                                           data-id="{{ $item->id }}"
                                           data-name="{{ $item->title }}">
                                            Eliminar
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($knowledge->hasPages())
        <div class="d-flex justify-content-end mt-3" data-ajax-pagination>
            {{ $knowledge->links() }}
        </div>
    @endif
@endif

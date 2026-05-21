@extends('layouts.theme')

@section('title', 'Supresiones — Remarketing')

@section('page_header')
    @include('core::components.card', ['title' => 'Supresiones de email'])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title fw-semibold mb-0">Lista de supresiones</h5>
                <p class="card-subtitle mb-0 small text-muted">Emails que no recibirán envíos.</p>
            </div>
            @can('remarketing.suppressions.manage')
                <div class="d-flex gap-2">
                    <a href="{{ route('settings.remarketing.suppressions.export') }}"
                       class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-download me-1"></i> Exportar CSV
                    </a>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalImportCsv">
                        <i class="fas fa-upload me-1"></i> Importar CSV
                    </button>
                </div>
            @endcan
        </div>
        <div class="card-body">
            @if($suppressions->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Email</th>
                                <th>Tienda</th>
                                <th>Motivo</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($suppressions as $sup)
                                <tr>
                                    <td class="fw-semibold small">{{ $sup->email }}</td>
                                    <td class="small">{{ $sup->store?->name ?? '—' }}</td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-secondary">{{ $sup->reason }}</span>
                                    </td>
                                    <td class="small text-muted">{{ $sup->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $suppressions->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-shield-halved text-muted fs-1 mb-3"></i>
                    <h6 class="text-muted">No hay supresiones registradas</h6>
                    <p class="small text-muted mb-0">Los emails suprimidos aparecerán aquí automáticamente.</p>
                </div>
            @endif
        </div>
    </div>

    @can('remarketing.suppressions.manage')
        <div class="modal fade" id="modalImportCsv" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST"
                      action="{{ route('settings.remarketing.suppressions.import') }}"
                      enctype="multipart/form-data">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Importar supresiones desde CSV</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted small">
                                El CSV debe tener una columna <code>email</code> obligatoria. Opcionales: <code>reason</code>, <code>notes</code>.
                                Emails duplicados se omiten.
                            </p>

                            <div class="mb-3">
                                <label class="form-label">Tienda destino <span class="text-danger">*</span></label>
                                <select name="store_id" class="form-select" required>
                                    <option value="">Selecciona una tienda</option>
                                    @foreach($stores as $store)
                                        <option value="{{ $store->id }}">{{ $store->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Archivo CSV <span class="text-danger">*</span></label>
                                <input type="file" name="file" class="form-control" accept=".csv,.txt" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Motivo por defecto</label>
                                <select name="reason" class="form-select">
                                    <option value="manual">manual</option>
                                    <option value="bounce">bounce</option>
                                    <option value="complaint">complaint</option>
                                    <option value="unsubscribe">unsubscribe</option>
                                </select>
                                <div class="form-text">Se aplica solo si el CSV no incluye columna reason.</div>
                            </div>
                        </div>
                        <div class="modal-footer flex-column gap-0">
                            <button type="submit" class="btn btn-primary w-100 mb-2">
                                <i class="fas fa-upload me-1"></i> Importar
                            </button>
                            <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endcan

@endsection

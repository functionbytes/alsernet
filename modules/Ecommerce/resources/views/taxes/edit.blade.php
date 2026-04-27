@extends('layouts.theme')

@section('title', 'Editar impuesto')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Editar impuesto'])
    @include('core::components.alerts')

    <form action="{{ route('ecommerce.taxes.update', $tax) }}" method="POST">
        @csrf @method('PUT')
        <div class="row g-4 align-items-start mb-4">

            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Informacion del impuesto</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Titulo</label>
                            <input type="text" name="title"
                                class="form-control @error('title') is-invalid @enderror"
                                value="{{ old('title', $tax->title) }}">
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Porcentaje <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="percentage" step="0.01" min="0" max="100"
                                    class="form-control @error('percentage') is-invalid @enderror"
                                    value="{{ old('percentage', $tax->percentage) }}" required>
                                <span class="input-group-text">%</span>
                            </div>
                            @error('percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-0">
                            <label class="form-label">Prioridad</label>
                            <input type="number" name="priority" min="0"
                                class="form-control @error('priority') is-invalid @enderror"
                                value="{{ old('priority', $tax->priority) }}">
                            <div class="form-text">Menor numero = mayor prioridad al aplicar impuestos.</div>
                            @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="sticky-top" style="top:80px">

                    <div class="card mb-4">
                        <div class="card-header p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">Publicar</h6>
                        </div>
                        <div class="card-body">
                            <button type="submit" class="btn btn-primary w-100 mb-2">Actualizar impuesto</button>
                            <a href="{{ route('ecommerce.taxes.index') }}" class="btn btn-outline-secondary w-100">Cancelar</a>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">Estado</h6>
                        </div>
                        <div class="card-body">
                            <select name="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="published" {{ old('status', $tax->status) === 'published' ? 'selected' : '' }}>Publicado</option>
                                <option value="draft" {{ old('status', $tax->status) === 'draft' ? 'selected' : '' }}>Borrador</option>
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">Detalles</h6>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0 small">
                                <dt class="col-5 text-muted fw-normal">Creado</dt>
                                <dd class="col-7 mb-2">{{ $tax->created_at->translatedFormat('j M, Y') }}</dd>
                                <dt class="col-5 text-muted fw-normal">Actualizado</dt>
                                <dd class="col-7 mb-0">{{ $tax->updated_at->translatedFormat('j M, Y') }}</dd>
                            </dl>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </form>

    {{-- Reglas de impuesto --}}
    <div class="card">
        <div class="card-header p-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">Reglas de impuesto</h6>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-rule">
                    <i class="fas fa-plus me-1"></i> Nueva regla
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            @if($tax->rules->count() > 0)
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Base</th>
                            <th>Desde</th>
                            <th>Hasta</th>
                            <th>%</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tax->rules as $rule)
                            <tr>
                                <td>{{ $rule->name }}</td>
                                <td><span class="badge bg-secondary">{{ $rule->basis === 'price' ? 'Precio' : 'Peso' }}</span></td>
                                <td>{{ number_format($rule->price_from, 2) }}</td>
                                <td>{{ $rule->price_to ? number_format($rule->price_to, 2) : '—' }}</td>
                                <td>{{ $rule->percentage }}%</td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-vertical"></i></button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <button type="button" class="dropdown-item btn-edit-rule"
                                                    data-id="{{ $rule->id }}"
                                                    data-name="{{ $rule->name }}"
                                                    data-basis="{{ $rule->basis }}"
                                                    data-from="{{ $rule->price_from }}"
                                                    data-to="{{ $rule->price_to }}"
                                                    data-pct="{{ $rule->percentage }}"
                                                    data-order="{{ $rule->order }}">Editar</button>
                                            </li>
                                            <li>
                                                <form action="{{ route('ecommerce.taxes.rules.destroy', [$tax, $rule]) }}" method="POST">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="dropdown-item" onclick="return confirm('¿Eliminar esta regla?')">Eliminar</button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="text-center py-4 text-muted small">No hay reglas de impuesto aun.</div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="modal-add-rule" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="rule-form" action="{{ route('ecommerce.taxes.rules.store', $tax) }}" method="POST">
                    @csrf
                    <span id="rule-method-field"></span>
                    <div class="modal-header border-bottom">
                        <h6 class="modal-title fw-bold">Regla de impuesto</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="rule-name" class="form-control" required>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label">Base</label>
                                <select name="basis" id="rule-basis" class="form-select">
                                    <option value="price">Precio</option>
                                    <option value="weight">Peso</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Porcentaje <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="percentage" id="rule-pct" class="form-control" required min="0" max="100">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Desde</label>
                                <input type="number" step="0.01" name="price_from" id="rule-from" class="form-control" value="0" min="0">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Hasta (vacio = sin limite)</label>
                                <input type="number" step="0.01" name="price_to" id="rule-to" class="form-control">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Orden</label>
                                <input type="number" name="order" id="rule-order" class="form-control" value="0" min="0">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top flex-column">
                        <button type="submit" class="btn btn-primary w-100 mb-2">Guardar regla</button>
                        <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.btn-edit-rule').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var form = document.getElementById('rule-form');
        var id = btn.dataset.id;
        var baseUrl = '{{ route("ecommerce.taxes.rules.store", $tax) }}';
        form.action = baseUrl + '/' + id;
        document.getElementById('rule-method-field').innerHTML = '<input type="hidden" name="_method" value="PUT">';
        document.getElementById('rule-name').value = btn.dataset.name;
        document.getElementById('rule-basis').value = btn.dataset.basis;
        document.getElementById('rule-from').value = btn.dataset.from;
        document.getElementById('rule-to').value = btn.dataset.to || '';
        document.getElementById('rule-pct').value = btn.dataset.pct;
        document.getElementById('rule-order').value = btn.dataset.order;
        new bootstrap.Modal(document.getElementById('modal-add-rule')).show();
    });
});

document.getElementById('modal-add-rule').addEventListener('hidden.bs.modal', function () {
    var form = document.getElementById('rule-form');
    form.action = '{{ route("ecommerce.taxes.rules.store", $tax) }}';
    document.getElementById('rule-method-field').innerHTML = '';
    form.reset();
});
</script>
@endpush

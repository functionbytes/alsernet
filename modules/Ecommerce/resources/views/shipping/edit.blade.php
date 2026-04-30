@extends('layouts.theme')

@section('title', 'Editar metodo de envio')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Editar metodo de envio'])
@endsection

@section('content')
    @include('core::components.alerts')

    <form action="{{ route('ecommerce.shipping.update', $shipping) }}" method="POST">
        @csrf @method('PUT')
        <div class="row g-4 align-items-start mb-4">

            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Informacion del metodo</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Titulo <span class="text-danger">*</span></label>
                            <input type="text" name="title"
                                class="form-control @error('title') is-invalid @enderror"
                                value="{{ old('title', $shipping->title) }}" required>
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-0">
                            <label class="form-label">Pais</label>
                            <input type="text" name="country"
                                class="form-control @error('country') is-invalid @enderror"
                                value="{{ old('country', $shipping->country) }}"
                                placeholder="Dejar vacio para aplicar a todos">
                            <div class="form-text">Dejar en blanco para aplicar globalmente.</div>
                            @error('country')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                            <button type="submit" class="btn btn-primary w-100 mb-2">Actualizar metodo</button>
                            <a href="{{ route('ecommerce.shipping.index') }}" class="btn btn-outline-secondary w-100">Cancelar</a>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">Estado</h6>
                        </div>
                        <div class="card-body">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                    id="is_active" {{ old('is_active', $shipping->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Metodo activo</label>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">Detalles</h6>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0 small">
                                <dt class="col-5 text-muted fw-normal">Creado</dt>
                                <dd class="col-7 mb-2">{{ $shipping->created_at->translatedFormat('j M, Y') }}</dd>
                                <dt class="col-5 text-muted fw-normal">Actualizado</dt>
                                <dd class="col-7 mb-0">{{ $shipping->updated_at->translatedFormat('j M, Y') }}</dd>
                            </dl>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </form>

    {{-- Reglas de envio --}}
    <div class="card">
        <div class="card-header p-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0 fw-bold">Reglas de envio</h6>
                    <small class="text-muted">Define el precio segun peso, precio del pedido o zona</small>
                </div>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#ruleModal">
                    <i class="fas fa-plus me-1"></i> Agregar regla
                </button>
            </div>
        </div>

        @if($shipping->rules->count() > 0)
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nombre</th>
                                <th>Tipo</th>
                                <th>Desde</th>
                                <th>Hasta</th>
                                <th>Precio</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($shipping->rules as $rule)
                                <tr>
                                    <td class="fw-semibold">{{ $rule->name }}</td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            {{ match($rule->type) {
                                                'based_on_price'   => 'Por precio',
                                                'based_on_weight'  => 'Por peso',
                                                'based_on_zipcode' => 'Por zona',
                                                default            => $rule->type
                                            } }}
                                        </span>
                                    </td>
                                    <td>{{ $rule->from ?? '—' }}</td>
                                    <td>{{ $rule->to ?? '—' }}</td>
                                    <td class="fw-semibold">${{ number_format($rule->price, 2) }}</td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <button type="button" class="dropdown-item btn-edit-rule"
                                                        data-id="{{ $rule->id }}"
                                                        data-name="{{ $rule->name }}"
                                                        data-type="{{ $rule->type }}"
                                                        data-from="{{ $rule->from }}"
                                                        data-to="{{ $rule->to }}"
                                                        data-price="{{ $rule->price }}">
                                                        Editar
                                                    </button>
                                                </li>
                                                <li>
                                                    <form action="{{ route('ecommerce.shipping.rules.destroy', [$shipping, $rule]) }}" method="POST">
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
                </div>
            </div>
        @else
            <div class="card-body text-center py-4">
                <i class="fas fa-truck fa-2x text-muted opacity-50 mb-2"></i>
                <p class="text-muted mb-0 small">No hay reglas de envio. Agrega una para configurar los costos.</p>
            </div>
        @endif
    </div>

    <div class="modal fade" id="ruleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="ruleForm" action="{{ route('ecommerce.shipping.rules.store', $shipping) }}" method="POST">
                    @csrf
                    <span id="ruleMethodField"></span>
                    <div class="modal-header border-bottom">
                        <h6 class="modal-title fw-bold" id="ruleModalTitle">Agregar regla de envio</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="ruleName" class="form-control" required placeholder="Ej: Envio estandar">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipo <span class="text-danger">*</span></label>
                            <select name="type" id="ruleType" class="form-select" required>
                                <option value="based_on_price">Por precio del pedido</option>
                                <option value="based_on_weight">Por peso</option>
                                <option value="based_on_zipcode">Por zona / codigo postal</option>
                            </select>
                        </div>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label">Desde</label>
                                <input type="number" name="from" id="ruleFrom" class="form-control" step="0.01" min="0" placeholder="0.00">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Hasta</label>
                                <input type="number" name="to" id="ruleTo" class="form-control" step="0.01" min="0" placeholder="Sin limite">
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label">Precio <span class="text-danger">*</span></label>
                            <input type="number" name="price" id="rulePrice" class="form-control" step="0.01" min="0" required placeholder="0.00">
                        </div>
                    </div>
                    <div class="modal-footer flex-column border-top">
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
$(function () {
    var storeUrl = '{{ route('ecommerce.shipping.rules.store', $shipping) }}';

    $(document).on('click', '.btn-edit-rule', function () {
        var $btn = $(this);
        var ruleId = $btn.data('id');

        $('#ruleModalTitle').text('Editar regla de envio');
        $('#ruleForm').attr('action', storeUrl + '/' + ruleId);
        $('#ruleMethodField').html('<input type="hidden" name="_method" value="PUT">');
        $('#ruleName').val($btn.data('name'));
        $('#ruleType').val($btn.data('type'));
        $('#ruleFrom').val($btn.data('from') || '');
        $('#ruleTo').val($btn.data('to') || '');
        $('#rulePrice').val($btn.data('price'));
        $('#ruleModal').modal('show');
    });

    $('#ruleModal').on('hidden.bs.modal', function () {
        $('#ruleModalTitle').text('Agregar regla de envio');
        $('#ruleForm').attr('action', storeUrl);
        $('#ruleMethodField').html('');
        document.getElementById('ruleForm').reset();
    });
});
</script>
@endpush

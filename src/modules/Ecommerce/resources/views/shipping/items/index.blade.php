@extends('layouts.theme')

@section('title', 'Items de regla de envio')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Items de regla de envio'])
@endsection

@section('content')
    @include('core::components.alerts')

    <div class="card">
        <div class="card-header p-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0 fw-bold">Items de la regla {{ $rule->name }}</h6>
                    <small class="text-muted">Metodo: {{ $shipping->title }}</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('ecommerce.shipping.edit', $shipping) }}" class="btn btn-outline-secondary btn-sm">
                        Volver
                    </a>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#itemModal">
                        <i class="fas fa-plus me-1"></i> Agregar item
                    </button>
                </div>
            </div>
        </div>

        @if($items->count() > 0)
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Ciudad / Zona</th>
                                <th>Codigo postal</th>
                                <th>Precio</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $item)
                                <tr>
                                    <td class="fw-semibold">{{ $item->city ?? '—' }}</td>
                                    <td><small class="text-muted">{{ $item->zip_code ?? '—' }}</small></td>
                                    <td class="fw-semibold">${{ number_format($item->price, 2) }}</td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm btn-light" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <button type="button" class="dropdown-item btn-edit-item"
                                                        data-id="{{ $item->id }}"
                                                        data-city="{{ $item->city }}"
                                                        data-zip="{{ $item->zip_code }}"
                                                        data-price="{{ $item->price }}">
                                                        Editar
                                                    </button>
                                                </li>
                                                <li>
                                                    <form action="{{ route('ecommerce.shipping.rules.items.destroy', [$shipping, $rule, $item]) }}" method="POST">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="dropdown-item" onclick="return confirm('¿Eliminar este item?')">
                                                            Eliminar
                                                        </button>
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

            @if($items->hasPages())
                <div class="card-footer bg-white border-top">
                    {{ $items->withQueryString()->links() }}
                </div>
            @endif
        @else
            <div class="card-body text-center py-5">
                <i class="fas fa-map-marker-alt fa-3x text-muted opacity-50 mb-3"></i>
                <h5 class="text-muted mb-1">No hay items en esta regla</h5>
                <p class="text-muted small mb-0">Agrega zonas o codigos postales para definir los costos de envio.</p>
            </div>
        @endif
    </div>

    {{-- Modal agregar / editar item --}}
    <div class="modal fade" id="itemModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="itemForm" action="{{ route('ecommerce.shipping.rules.items.store', [$shipping, $rule]) }}" method="POST">
                    @csrf
                    <span id="itemMethodField"></span>
                    <div class="modal-header border-bottom">
                        <h6 class="modal-title fw-bold" id="itemModalTitle">Agregar item</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Ciudad / Zona</label>
                            <input type="text" name="city" id="itemCity" class="form-control" placeholder="Ej: Ciudad de Mexico">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Codigo postal</label>
                            <input type="text" name="zip_code" id="itemZip" class="form-control" placeholder="Ej: 06600">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Precio <span class="text-danger">*</span></label>
                            <input type="number" name="price" id="itemPrice" class="form-control" step="0.01" min="0" required placeholder="0.00">
                        </div>
                    </div>
                    <div class="modal-footer flex-column border-top">
                        <button type="submit" class="btn btn-primary w-100 mb-2">Guardar item</button>
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
    var storeUrl = '{{ route('ecommerce.shipping.rules.items.store', [$shipping, $rule]) }}';

    $(document).on('click', '.btn-edit-item', function () {
        var $btn = $(this);
        var itemId = $btn.data('id');

        $('#itemModalTitle').text('Editar item');
        $('#itemForm').attr('action', storeUrl + '/' + itemId);
        $('#itemMethodField').html('<input type="hidden" name="_method" value="PUT">');
        $('#itemCity').val($btn.data('city') || '');
        $('#itemZip').val($btn.data('zip') || '');
        $('#itemPrice').val($btn.data('price'));
        $('#itemModal').modal('show');
    });

    $('#itemModal').on('hidden.bs.modal', function () {
        $('#itemModalTitle').text('Agregar item');
        $('#itemForm').attr('action', storeUrl);
        $('#itemMethodField').html('');
        document.getElementById('itemForm').reset();
    });
});
</script>
@endpush

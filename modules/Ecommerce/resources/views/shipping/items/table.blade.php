<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>Zona / Ciudad</th>
                <th>Precio</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
                @include('ecommerce::shipping.items.table-item', compact('item', 'shipping', 'rule'))
            @empty
                <tr>
                    <td colspan="3" class="text-center py-3">
                        <small class="text-muted">Sin items definidos</small>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

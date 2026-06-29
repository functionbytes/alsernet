@if($products->isNotEmpty())
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Producto</th>
                    <th class="text-center">Vistas</th>
                    <th class="text-end">Precio</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $product)
                    <tr>
                        <td>
                            <span class="fw-semibold">{{ $product->name }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border">{{ number_format($product->views) }}</span>
                        </td>
                        <td class="text-end">
                            <small class="text-muted">${{ number_format($product->price, 2) }}</small>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="text-center py-4">
        <p class="text-muted mb-0 small">No hay datos disponibles</p>
    </div>
@endif

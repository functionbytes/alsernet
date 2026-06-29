<tr>
    <td class="fw-semibold small">{{ $item->zipcode ?? $item->city ?? $item->zone ?? '—' }}</td>
    <td class="fw-semibold small">${{ number_format($item->price, 2) }}</td>
    <td class="text-center">
        <div class="dropdown">
            <button type="button" class="btn btn-sm btn-light" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-ellipsis-vertical"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <button type="button" class="dropdown-item btn-edit-item"
                        data-id="{{ $item->id }}"
                        data-zipcode="{{ $item->zipcode ?? $item->city ?? $item->zone }}"
                        data-price="{{ $item->price }}">
                        Editar
                    </button>
                </li>
                <li>
                    <form action="{{ route('ecommerce.shipping.rules.items.destroy', [$shipping, $rule, $item]) }}" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit" class="dropdown-item" onclick="return confirm('Eliminar este item?')">
                            Eliminar
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </td>
</tr>

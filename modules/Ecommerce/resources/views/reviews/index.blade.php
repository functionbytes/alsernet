@extends('layouts.theme')

@section('title', 'Resenas')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Resenas'])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Resenas</h5>
                        <p class="small mb-0 text-muted">Moderacion de resenas de productos</p>
                    </div>
                </div>
            </div>

            <div class="card-body">
                @if($reviews->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Producto</th>
                                    <th>Cliente</th>
                                    <th>Estrellas</th>
                                    <th>Comentario</th>
                                    <th>Respuesta</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reviews as $review)
                                    <tr>
                                        <td><a href="{{ route('ecommerce.products.edit', $review->product) }}" class="fw-semibold text-decoration-none">{{ $review->product->name }}</a></td>
                                        <td><small class="text-muted">{{ $review->customer->name ?? $review->customer_name }}</small></td>
                                        <td>
                                            @for($i = 1; $i <= 5; $i++)
                                                <i class="fas fa-star {{ $i <= $review->star ? 'text-warning' : 'text-muted' }}"></i>
                                            @endfor
                                        </td>
                                        <td><small class="text-muted">{{ Str::limit($review->comment, 60) }}</small></td>
                                        <td>
                                            @if($review->reply)
                                                <small class="text-success">{{ Str::limit($review->reply, 40) }}</small>
                                            @else
                                                <small class="text-muted">—</small>
                                            @endif
                                        </td>
                                        <td><span class="badge bg-{{ $review->status === 'approved' ? 'success' : 'warning' }}">{{ $review->status }}</span></td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('ecommerce.reviews.show', $review) }}">Ver</a>
                                                    </li>
                                                    <li>
                                                        <button type="button" class="dropdown-item btn-reply"
                                                            data-id="{{ $review->id }}"
                                                            data-comment="{{ $review->comment }}"
                                                            data-customer="{{ $review->customer->name ?? $review->customer_name }}"
                                                            data-reply="{{ $review->reply }}"
                                                            data-url="{{ url('panel/ecommerce/reviews/' . $review->id . '/reply') }}">
                                                            Responder
                                                        </button>
                                                    </li>
                                                    @if($review->status !== 'approved')
                                                        <li>
                                                            <form action="{{ route('ecommerce.reviews.approve', $review) }}" method="POST">
                                                                @csrf
                                                                <button type="submit" class="dropdown-item">Aprobar</button>
                                                            </form>
                                                        </li>
                                                    @endif
                                                    <li>
                                                        <button type="button" class="dropdown-item btn-toggle-publish"
                                                            data-id="{{ $review->id }}"
                                                            data-url="{{ route('ecommerce.reviews.toggle-publish', $review) }}">
                                                            {{ $review->status === 'approved' ? 'Despublicar' : 'Publicar' }}
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <form action="{{ route('ecommerce.reviews.destroy', $review) }}" method="POST" onsubmit="return confirm('Eliminar esta resena?')">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="dropdown-item">Eliminar</button>
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
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-comment fa-4x text-muted opacity-50 mb-4"></i>
                        <h5 class="text-muted mb-2">No hay resenas</h5>
                    </div>
                @endif
            </div>

            @if($reviews->hasPages())
                <div class="card-footer">{{ $reviews->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>

    {{-- Modal responder resena --}}
    <div class="modal fade" id="modal-reply" tabindex="-1" aria-labelledby="modal-reply-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="reply-form" method="POST">
                    @csrf
                    <input type="hidden" name="_method" value="POST">
                    <div class="modal-header border-bottom">
                        <h6 class="modal-title fw-bold" id="modal-reply-label">Responder resena</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3 p-3 bg-light rounded">
                            <small class="text-muted d-block mb-1 fw-semibold" id="reply-customer-name"></small>
                            <p class="mb-0 small" id="reply-original-comment"></p>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Tu respuesta</label>
                            <textarea name="reply" id="reply-text" class="form-control" rows="4" placeholder="Escribe tu respuesta al cliente..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top flex-column">
                        <button type="submit" class="btn btn-primary w-100 mb-2">Guardar respuesta</button>
                        <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $(document).on('click', '.btn-toggle-publish', function () {
        var btn = $(this);
        var row = btn.closest('tr');

        $.ajax({
            url: btn.data('url'),
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                var badge = row.find('.badge');
                if (response.status === 'approved') {
                    badge.removeClass('bg-warning').addClass('bg-success').text('approved');
                    btn.text('Despublicar');
                } else {
                    badge.removeClass('bg-success').addClass('bg-warning').text(response.status);
                    btn.text('Publicar');
                }
                toastr.success(response.message || 'Estado actualizado');
            },
            error: function () {
                toastr.error('No se pudo actualizar el estado');
            }
        });
    });

    $(document).on('click', '.btn-reply', function () {
        var btn = $(this);
        $('#reply-form').attr('action', btn.data('url'));
        $('#reply-customer-name').text(btn.data('customer'));
        $('#reply-original-comment').text(btn.data('comment'));
        $('#reply-text').val(btn.data('reply') || '');
        new bootstrap.Modal(document.getElementById('modal-reply')).show();
    });

    $('#modal-reply').on('hidden.bs.modal', function () {
        $('#reply-form')[0].reset();
    });
});
</script>
@endpush

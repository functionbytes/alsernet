@extends('layouts.theme')

@section('title', 'Detalle de resena')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Detalle de resena'])
@endsection

@section('content')
    @include('core::components.alerts')

    <div class="row g-3">
        <div class="col-lg-8">
            {{-- Resena --}}
            <div class="card">
                <div class="card-header p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0 fw-bold">Resena del cliente</h5>
                        </div>
                        <a href="{{ route('ecommerce.reviews.index') }}" class="btn btn-sm btn-outline-secondary">
                            Volver al listado
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-3 mb-3">
                        <div class="flex-grow-1">
                            <p class="mb-1 fw-semibold">{{ $review->customer->name ?? $review->customer_name ?? 'Cliente' }}</p>
                            <div class="mb-2">
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="fas fa-star {{ $i <= $review->star ? 'text-warning' : 'text-muted' }}"></i>
                                @endfor
                                <span class="ms-1 text-muted small">{{ $review->star }}/5</span>
                            </div>
                            <p class="mb-1 text-muted small">
                                Producto:
                                <a href="{{ route('ecommerce.products.edit', $review->product) }}" class="text-decoration-none">
                                    {{ $review->product->name }}
                                </a>
                            </p>
                            <p class="mb-0 text-muted small">{{ $review->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <div>
                            <span class="badge bg-{{ $review->status === 'approved' ? 'success' : 'warning' }}">
                                {{ $review->status }}
                            </span>
                        </div>
                    </div>

                    @if($review->comment)
                        <div class="p-3 bg-light rounded">
                            <p class="mb-0">{{ $review->comment }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Respuesta --}}
            <div class="card mt-3">
                <div class="card-header p-3 border-bottom">
                    <h6 class="mb-0 fw-bold">Respuesta de la tienda</h6>
                </div>
                <div class="card-body">
                    @if($review->reply)
                        <div id="reply-display">
                            <div class="p-3 bg-light rounded mb-3">
                                <p class="mb-1">{{ $review->reply }}</p>
                                <small class="text-muted">
                                    Respondido el {{ $review->replied_at?->format('d/m/Y H:i') }}
                                </small>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="btn-edit-reply">
                                    Editar respuesta
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" id="btn-delete-reply">
                                    Eliminar respuesta
                                </button>
                            </div>
                        </div>

                        <div id="reply-edit-form" class="d-none">
                            <div class="mb-3">
                                <label class="form-label">Editar respuesta</label>
                                <textarea id="reply-edit-text" class="form-control" rows="4">{{ $review->reply }}</textarea>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary btn-sm" id="btn-save-edit">
                                    Guardar cambios
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-cancel-edit">
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    @else
                        <div id="reply-new-form">
                            <div class="mb-3">
                                <label for="reply-new-text" class="form-label">Escribe tu respuesta</label>
                                <textarea id="reply-new-text" class="form-control" rows="4"
                                          placeholder="Escribe una respuesta al cliente..."></textarea>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm" id="btn-save-reply">
                                Guardar respuesta
                            </button>
                        </div>

                        <div id="reply-saved" class="d-none">
                            <div class="p-3 bg-light rounded mb-3" id="reply-saved-text-wrapper">
                                <p class="mb-1" id="reply-saved-text"></p>
                                <small class="text-muted" id="reply-saved-date"></small>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="btn-edit-reply-new">
                                    Editar respuesta
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" id="btn-delete-reply-new">
                                    Eliminar respuesta
                                </button>
                            </div>
                            <div id="reply-edit-form-new" class="mt-3 d-none">
                                <div class="mb-3">
                                    <label class="form-label">Editar respuesta</label>
                                    <textarea id="reply-edit-text-new" class="form-control" rows="4"></textarea>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-primary btn-sm" id="btn-save-edit-new">
                                        Guardar cambios
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-cancel-edit-new">
                                        Cancelar
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sidebar con info adicional --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Informacion de la resena</h6>
                    <dl class="row mb-0">
                        <dt class="col-5 text-muted small fw-normal">ID</dt>
                        <dd class="col-7 small">#{{ $review->id }}</dd>

                        <dt class="col-5 text-muted small fw-normal">Cliente</dt>
                        <dd class="col-7 small">{{ $review->customer->name ?? $review->customer_name ?? '—' }}</dd>

                        <dt class="col-5 text-muted small fw-normal">Producto</dt>
                        <dd class="col-7 small">
                            <a href="{{ route('ecommerce.products.edit', $review->product) }}" class="text-decoration-none">
                                {{ $review->product->name }}
                            </a>
                        </dd>

                        <dt class="col-5 text-muted small fw-normal">Estado</dt>
                        <dd class="col-7 small">
                            <span class="badge bg-{{ $review->status === 'approved' ? 'success' : 'warning' }}">
                                {{ $review->status }}
                            </span>
                        </dd>

                        <dt class="col-5 text-muted small fw-normal">Fecha</dt>
                        <dd class="col-7 small">{{ $review->created_at->format('d/m/Y') }}</dd>

                        @if($review->replied_at)
                            <dt class="col-5 text-muted small fw-normal">Respondida</dt>
                            <dd class="col-7 small">{{ $review->replied_at->format('d/m/Y') }}</dd>
                        @endif
                    </dl>
                </div>
            </div>

            @if($review->status !== 'approved')
                <div class="card mt-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-2">Acciones</h6>
                        <form action="{{ route('ecommerce.reviews.approve', $review) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success w-100 mb-2">Aprobar resena</button>
                        </form>
                        <form action="{{ route('ecommerce.reviews.destroy', $review) }}" method="POST"
                              onsubmit="return confirm('Eliminar esta resena?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-secondary w-100">Eliminar resena</button>
                        </form>
                    </div>
                </div>
            @else
                <div class="card mt-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-2">Acciones</h6>
                        <form action="{{ route('ecommerce.reviews.destroy', $review) }}" method="POST"
                              onsubmit="return confirm('Eliminar esta resena?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-secondary w-100">Eliminar resena</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    var csrfToken = $('meta[name="csrf-token"]').attr('content');
    var storeUrl = '{{ route('ecommerce.reviews.reply.store', $review) }}';
    var updateUrl = '{{ route('ecommerce.reviews.reply.update', $review) }}';
    var destroyUrl = '{{ route('ecommerce.reviews.reply.destroy', $review) }}';

    // --- Case: review HAS a reply ---

    $('#btn-edit-reply').on('click', function () {
        $('#reply-display').addClass('d-none');
        $('#reply-edit-form').removeClass('d-none');
    });

    $('#btn-cancel-edit').on('click', function () {
        $('#reply-edit-form').addClass('d-none');
        $('#reply-display').removeClass('d-none');
    });

    $('#btn-save-edit').on('click', function () {
        var reply = $('#reply-edit-text').val().trim();
        if (!reply) {
            toastr.warning('La respuesta no puede estar vacia.');
            return;
        }

        $.ajax({
            url: updateUrl,
            method: 'PUT',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: { reply: reply },
            success: function () {
                toastr.success('Respuesta actualizada.');
                setTimeout(function () { window.location.reload(); }, 800);
            },
            error: function () {
                toastr.error('No se pudo actualizar la respuesta.');
            }
        });
    });

    $('#btn-delete-reply').on('click', function () {
        if (!confirm('Eliminar la respuesta?')) {
            return;
        }

        $.ajax({
            url: destroyUrl,
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function () {
                toastr.success('Respuesta eliminada.');
                setTimeout(function () { window.location.reload(); }, 800);
            },
            error: function () {
                toastr.error('No se pudo eliminar la respuesta.');
            }
        });
    });

    // --- Case: review does NOT have a reply ---

    $('#btn-save-reply').on('click', function () {
        var reply = $('#reply-new-text').val().trim();
        if (!reply) {
            toastr.warning('La respuesta no puede estar vacia.');
            return;
        }

        $.ajax({
            url: storeUrl,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: { reply: reply },
            success: function (data) {
                toastr.success('Respuesta guardada.');
                $('#reply-new-form').addClass('d-none');
                $('#reply-saved-text').text(reply);
                $('#reply-saved-date').text('Respondido el ' + (data.replied_at || 'ahora'));
                $('#reply-edit-text-new').val(reply);
                $('#reply-saved').removeClass('d-none');
            },
            error: function () {
                toastr.error('No se pudo guardar la respuesta.');
            }
        });
    });

    $('#btn-edit-reply-new').on('click', function () {
        $('#reply-edit-form-new').removeClass('d-none');
    });

    $('#btn-cancel-edit-new').on('click', function () {
        $('#reply-edit-form-new').addClass('d-none');
    });

    $('#btn-save-edit-new').on('click', function () {
        var reply = $('#reply-edit-text-new').val().trim();
        if (!reply) {
            toastr.warning('La respuesta no puede estar vacia.');
            return;
        }

        $.ajax({
            url: updateUrl,
            method: 'PUT',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: { reply: reply },
            success: function () {
                toastr.success('Respuesta actualizada.');
                $('#reply-saved-text').text(reply);
                $('#reply-edit-form-new').addClass('d-none');
            },
            error: function () {
                toastr.error('No se pudo actualizar la respuesta.');
            }
        });
    });

    $('#btn-delete-reply-new').on('click', function () {
        if (!confirm('Eliminar la respuesta?')) {
            return;
        }

        $.ajax({
            url: destroyUrl,
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function () {
                toastr.success('Respuesta eliminada.');
                setTimeout(function () { window.location.reload(); }, 800);
            },
            error: function () {
                toastr.error('No se pudo eliminar la respuesta.');
            }
        });
    });
});
</script>
@endpush

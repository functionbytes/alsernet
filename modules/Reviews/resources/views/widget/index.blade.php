@extends('layouts.theme')

@section('title', 'Widgets de reseñas')

@section('content')

    @include('core::components.card', ['title' => 'Widgets embebibles'])

    <div class="widget-content">

        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Widgets embebibles</h5>
                        <p class="small mb-0 text-muted">Genera tokens para incrustar reseñas en sitios web externos.</p>
                    </div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-create-token">
                        <i class="fas fa-plus me-1"></i> Nuevo widget
                    </button>
                </div>
            </div>

            <div class="card-body p-0">
                @if ($tokens->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-code fa-2x mb-2"></i>
                        <p class="mb-0">No hay tokens de widget todavia.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Ubicacion</th>
                                    <th>Token</th>
                                    <th>Min. estrellas</th>
                                    <th>Max. reseñas</th>
                                    <th>Estado</th>
                                    <th>Creado</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($tokens as $token)
                                    <tr>
                                        <td>{{ $token->location->name ?? '—' }}</td>
                                        <td>
                                            <code class="small">{{ substr($token->token, 0, 12) }}…</code>
                                        </td>
                                        <td>{{ $token->min_rating }} <i class="fas fa-star text-warning"></i></td>
                                        <td>{{ $token->max_reviews }}</td>
                                        <td>
                                            @if ($token->is_active)
                                                <span class="badge bg-success">Activo</span>
                                            @else
                                                <span class="badge bg-secondary">Inactivo</span>
                                            @endif
                                        </td>
                                        <td>{{ $token->created_at->diffForHumans() }}</td>
                                        <td class="text-end">
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-secondary btn-copy-embed"
                                                    data-token="{{ $token->token }}"
                                                    title="Copiar codigo de integracion">
                                                <i class="fas fa-code"></i>
                                            </button>
                                            <a href="{{ route('reviews.widgets.preview', $token) }}"
                                               class="btn btn-sm btn-outline-primary" title="Vista previa" target="_blank">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#delete-modal"
                                                    data-url="{{ route('reviews.widgets.destroy', $token) }}"
                                                    data-title="Eliminar widget: {{ $token->location->name ?? $token->token }}"
                                                    title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="p-3">
                        {{ $tokens->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Modal: Create token --}}
    <div class="modal fade" id="modal-create-token" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo widget</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ubicacion <span class="text-danger">*</span></label>
                        <select class="form-select" id="new-token-location">
                            <option value="">Selecciona una ubicacion</option>
                            @foreach ($locations as $loc)
                                <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Calificacion minima</label>
                            <select class="form-select" id="new-token-min-rating">
                                @for ($i = 1; $i <= 5; $i++)
                                    <option value="{{ $i }}" @selected($i === 1)>{{ $i }} estrella{{ $i > 1 ? 's' : '' }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Maximo de reseñas</label>
                            <input type="number" class="form-control" id="new-token-max-reviews" value="5" min="1" max="10">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btn-create-token">Crear widget</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Embed code --}}
    <div class="modal fade" id="modal-embed-code" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Codigo de integracion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-2">Pega este codigo en cualquier pagina HTML donde quieras mostrar las reseñas:</p>
                    <div class="input-group">
                        <textarea class="form-control font-monospace" id="embed-code-text" rows="3" readonly></textarea>
                        <button class="btn btn-outline-secondary" type="button" id="btn-copy-embed-code" title="Copiar">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                    <div class="alert alert-info mt-3 small">
                        <i class="fas fa-info-circle me-1"></i>
                        El widget carga automaticamente las reseñas al iniciar la pagina. No requiere frameworks adicionales.
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(function () {
    var createUrl = '{{ route('reviews.widgets.store') }}';

    function buildEmbedCode(token) {
        var jsUrl = '{{ url('/api/reviews-widget') }}/' + token + '.js';
        return '<div data-reviews-widget></div>\n<script src="' + jsUrl + '"><\/script>';
    }

    // Create token
    $('#btn-create-token').on('click', function () {
        var locationId = $('#new-token-location').val();
        if (! locationId) {
            toastr.warning('Selecciona una ubicacion.');
            return;
        }

        $.ajax({
            url: createUrl,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: {
                location_id: locationId,
                min_rating: $('#new-token-min-rating').val(),
                max_reviews: $('#new-token-max-reviews').val(),
            },
            success: function () {
                toastr.success('Widget creado correctamente.');
                setTimeout(function () { window.location.reload(); }, 800);
            },
            error: function (xhr) {
                var msg = xhr.responseJSON?.message || 'Error al crear el widget.';
                toastr.error(msg);
            },
        });
    });

    // Show embed code modal
    $(document).on('click', '.btn-copy-embed', function () {
        var token = $(this).data('token');
        $('#embed-code-text').val(buildEmbedCode(token));
        new bootstrap.Modal(document.getElementById('modal-embed-code')).show();
    });

    // Copy embed code
    $('#btn-copy-embed-code').on('click', function () {
        var text = $('#embed-code-text').val();
        navigator.clipboard.writeText(text)
            .then(function () { toastr.success('Codigo copiado.'); })
            .catch(function () { toastr.error('No se pudo copiar.'); });
    });

    // Delete modal handler
    $('#delete-modal').on('show.bs.modal', function (e) {
        var $trigger = $(e.relatedTarget);
        $(this).find('.modal-title').text($trigger.data('title'));
        $('#delete-form').attr('action', $trigger.data('url'));
    });
});
</script>
@endpush

@include('core::components.delete')

@extends('layouts.theme')

@section('title', 'Respuestas programadas')

@section('page_header')
    @include('core::components.card', ['title' => 'Respuestas programadas'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Respuestas programadas</h5>
                        <p class="small mb-0 text-muted">Respuestas pendientes de publicacion automatica en la fecha indicada.</p>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                                <small class="text-muted">Respuestas programadas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Proximas 24h</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['next_24h'] }}</h4>
                                <small class="text-muted">Se publican en las proximas 24 horas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Esta semana</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['this_week'] }}</h4>
                                <small class="text-muted">Se publican en los proximos 7 dias</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Vencidas</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['overdue'] }}</h4>
                                <small class="text-muted">Fecha pasada sin publicar</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('reviews.replies.scheduled') }}">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        <div class="flex-fill">
                            <div class="input-group h-100">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control border-start-0 ps-0"
                                       placeholder="Buscar por texto de respuesta..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>
                            </button>
                            @if(request('search'))
                                <a href="{{ route('reviews.replies.scheduled') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($replies->isEmpty())
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-clock fs-7"></i>
                            </div>
                            <h6 class="mb-1">
                                @if(request('search'))
                                    No se encontraron respuestas
                                @else
                                    No hay respuestas programadas
                                @endif
                            </h6>
                            <p class="text-muted mb-0">
                                @if(request('search'))
                                    No hay resultados para los criterios de busqueda
                                @else
                                    No hay respuestas pendientes de publicacion automatica.
                                @endif
                            </p>
                        </div>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>Reseña</th>
                                    <th>Respuesta</th>
                                    <th>Programada para</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($replies as $reply)
                                    <tr data-id="{{ $reply->id }}">
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $reply->id }}"></td>
                                        <td style="min-width: 180px;">
                                            @if($reply->review)
                                                <div class="mb-1">
                                                    @for($i = 1; $i <= 5; $i++)
                                                        <i class="fas fa-star{{ $i <= ($reply->review->star_rating?->toNumeric() ?? 0) ? '' : '-o' }} text-warning small"></i>
                                                    @endfor
                                                </div>
                                                <small class="text-muted d-block">
                                                    {{ Str::limit($reply->review->comment ?? '(sin comentario)', 80) }}
                                                </small>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td style="min-width: 250px; max-width: 350px;">
                                            <small>{{ Str::limit($reply->reply_text, 150) }}</small>
                                        </td>
                                        <td>
                                            <span class="fw-semibold">{{ $reply->scheduled_at->format('d/m/Y H:i') }}</span>
                                            <small class="text-muted d-block">{{ $reply->scheduled_at->diffForHumans() }}</small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $reply->status->color() }}">{{ $reply->status->label() }}</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item btn-cancel-reply" href="javascript:void(0)"
                                                           data-id="{{ $reply->id }}"
                                                           data-url="{{ route('reviews.replies.destroy', $reply) }}">
                                                            Cancelar
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($replies->hasPages())
                        <div class="mt-3">
                            {{ $replies->links() }}
                        </div>
                    @endif
                @endif
            </div>

        </div>
    </div>

    {{-- Bulk Toolbar --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar acción
        </button>
    </div>

    {{-- Bulk Modal --}}
    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Acción masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> respuesta(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            <option value="cancel">Cancelar programación</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-1">Aplicar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(function () {
    var csrfToken = $('meta[name="csrf-token"]').attr('content');

    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        const ids = bulk.getIds();

        if (!ids.length) { toastr.warning('Selecciona al menos una respuesta.'); return; }
        

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route('reviews.replies.bulk-cancel') }}',
            method: 'DELETE',
            data: JSON.stringify({ ids: ids, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.count + ' respuesta(s) canceladas.');
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });

    $(document).on('click', '.btn-cancel-reply', function () {
        if (false) {
            return;
        }

        var $btn = $(this).prop('disabled', true);
        var url  = $(this).data('url');
        var id   = $(this).data('id');

        $.ajax({
            url:     url,
            method:  'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (res) {
                if (res.success) {
                    toastr.success('Respuesta programada cancelada correctamente.');
                    $('tr[data-id="' + id + '"]').fadeOut(300, function () { $(this).remove(); });
                } else {
                    toastr.error(res.message || 'Error al cancelar la respuesta.');
                    $btn.prop('disabled', false);
                }
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error al cancelar la respuesta programada.');
                $btn.prop('disabled', false);
            },
        });
    });
});
</script>
@endpush

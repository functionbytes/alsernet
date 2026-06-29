@extends('layouts.theme')

@section('title', 'Detalle de redireccion')

@section('page_header')
    @include('core::components.card', ['title' => 'Detalle de redireccion'])
@endsection

@section('content')
    <div class="widget-content">
        @include('core::components.alerts')

        <div class="row g-4">
            {{-- Left column: main info --}}
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header p-4 border-bottom border-light">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="mb-1 fw-bold text-truncate" style="max-width: 420px;">
                                    <code class="text-primary">{{ $redirect->source_path }}</code>
                                    <i class="fas fa-arrow-right mx-2 text-muted fs-6"></i>
                                    <code class="text-muted">{{ $redirect->target_path }}</code>
                                </h5>
                                <p class="mb-0 text-muted small">Redireccion {{ $redirect->status_code }}</p>
                            </div>
                            <div class="d-flex gap-2 flex-shrink-0">
                                <span class="badge {{ $redirect->is_active ? 'bg-success' : 'bg-secondary' }} text-white">
                                    {{ $redirect->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                                <span class="badge {{ $redirect->isPermanent() ? 'bg-success-subtle text-success' : 'bg-info-subtle text-info' }}">
                                    {{ $redirect->isPermanent() ? 'Permanente' : 'Temporal' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Section 1: URL info --}}
                    <div class="card-body border-bottom">
                        <div class="mb-3">
                            <label class="form-label text-muted">Ruta origen</label>
                            <div class="form-control bg-light">
                                <code class="text-primary">{{ $redirect->source_path }}</code>
                            </div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label text-muted">Ruta destino</label>
                            <div class="form-control bg-light">
                                <code class="text-muted">{{ $redirect->target_path }}</code>
                            </div>
                        </div>
                        @if($redirect->is_regex || $redirect->is_wildcard || $redirect->active_until)
                            <div class="mt-3 d-flex flex-wrap gap-2 align-items-center">
                                @if($redirect->is_regex)
                                    <span class="badge bg-info">Expresion regular</span>
                                @endif
                                @if($redirect->is_wildcard)
                                    <span class="badge bg-secondary">Wildcard</span>
                                @endif
                                @if($redirect->active_until)
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>Expira el {{ $redirect->active_until->format('d/m/Y H:i') }}
                                    </small>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Section 2: Stats cards --}}
                    <div class="card-body border-bottom">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="card bg-light-secondary h-100">
                                    <div class="card-body text-center">
                                        <h4 class="mb-1 fw-bold">{{ number_format($redirect->hits_count) }}</h4>
                                        <h6 class="card-title mb-1">Total visitas</h6>
                                        <small class="text-muted">Redirecciones procesadas</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light-secondary h-100">
                                    <div class="card-body text-center">
                                        <h4 class="mb-1 fw-bold">{{ $redirect->created_at->format('d/m/Y') }}</h4>
                                        <h6 class="card-title mb-1">Creado</h6>
                                        <small class="text-muted">{{ $redirect->created_at->diffForHumans() }}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light-secondary h-100">
                                    <div class="card-body text-center">
                                        <h4 class="mb-1 fw-bold">{{ $redirect->updated_at->format('d/m/Y') }}</h4>
                                        <h6 class="card-title mb-1">Actualizado</h6>
                                        <small class="text-muted">{{ $redirect->updated_at->diffForHumans() }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Section 3: Test redirect --}}
                    <div class="card-body">
                        <button type="button" class="btn btn-outline-info w-100" id="btn-test-redirect"
                                data-url="{{ route('settings.seo.redirects.test', $redirect) }}">
                            <i class="fas fa-vial me-1"></i> Probar redireccion
                        </button>
                        <div id="test-result" class="mt-3 d-none"></div>
                    </div>

                    @if($redirect->notes)
                        <div class="card-body border-top">
                            <h6 class="fw-bold mb-2">Notas</h6>
                            <p class="mb-0 text-muted">{{ $redirect->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Right column: actions sidebar --}}
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="fw-bold mb-0">Acciones</h6>
                    </div>
                    <div class="card-body d-grid gap-2">
                        <a href="{{ route('settings.seo.redirects.edit', $redirect) }}" class="btn btn-primary w-100">
                            Editar redireccion
                        </a>
                        <form action="{{ route('settings.seo.redirects.toggle-active', $redirect) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-outline-{{ $redirect->is_active ? 'warning' : 'success' }} w-100">
                                {{ $redirect->is_active ? 'Desactivar' : 'Activar' }}
                            </button>
                        </form>
                        <a href="{{ route('settings.seo.redirects.index') }}" class="btn btn-outline-secondary w-100">
                            Volver al listado
                        </a>
                        <hr class="my-1">
                        <button type="button" class="btn btn-outline-danger w-100 delete-btn"
                                data-bs-toggle="modal"
                                data-bs-target="#delete-modal"
                                data-url="{{ route('settings.seo.redirects.destroy', $redirect) }}"
                                data-title="Eliminar: {{ $redirect->source_path }}">
                            Eliminar redireccion
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('core::components.delete')
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('.delete-btn').on('click', function () {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });

    $('#btn-test-redirect').on('click', function () {
        var $btn = $(this);
        var url = $btn.data('url');

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Probando...');
        $('#test-result').addClass('d-none').empty();

        $.ajax({
            url: url,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (data) {
                var ok = data.matches;
                var alertClass = ok ? 'alert-success' : 'alert-danger';
                var icon = ok ? 'fa-check-circle' : 'fa-times-circle';
                var label = ok ? 'El redirect funciona correctamente' : 'El redirect no responde como se esperaba';

                var html = '<div class="card">'
                    + '<div class="card-body">'
                    + '<div class="alert ' + alertClass + ' mb-2">'
                    + '<i class="fas ' + icon + ' me-1"></i> <strong>' + label + '</strong>'
                    + '</div>'
                    + '<table class="table table-sm table-borderless mb-0 small">'
                    + '<tr><td class="text-muted">Status recibido</td><td><span class="badge bg-secondary">' + data.status + '</span></td></tr>'
                    + '<tr><td class="text-muted">Status esperado</td><td><span class="badge bg-secondary">' + data.expected + '</span></td></tr>'
                    + '<tr><td class="text-muted">Destino</td><td><code>' + data.target + '</code></td></tr>'
                    + '</table>'
                    + '</div></div>';

                $('#test-result').html(html).removeClass('d-none');
                toastr[ok ? 'success' : 'warning'](label);
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Error al probar el redirect.';
                $('#test-result').html(
                    '<div class="card"><div class="card-body">'
                    + '<div class="alert alert-danger mb-0"><i class="fas fa-exclamation-triangle me-1"></i> ' + msg + '</div>'
                    + '</div></div>'
                ).removeClass('d-none');
                toastr.error(msg);
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fas fa-vial me-1"></i> Probar redireccion');
            }
        });
    });
});
</script>
@endpush

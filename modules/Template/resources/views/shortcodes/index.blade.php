@extends('layouts.theme')

@section('page_title', 'Shortcodes')

@section('content')
    @include('core::components.card', ['title' => 'Shortcodes'])

    @include('core::components.alerts')

    <div class="card">
        {{-- Header --}}
        <div class="card-header p-4 border-bottom border-light">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Shortcodes</h5>
                    <p class="small mb-0 text-muted">Gestiona los componentes dinámicos disponibles en el editor de páginas</p>
                </div>
                <a href="{{ route('settings.shortcodes.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Nuevo shortcode
                </a>
            </div>
        </div>

        {{-- Stats --}}
        <div class="card-body border-bottom">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-primary mb-2">Total</h6>
                            <h4 class="mb-1 fw-bold">{{ $shortcodes->count() }}</h4>
                            <small class="text-muted">Shortcodes registrados</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-2"><i class="fas fa-toggle-on me-1"></i>Activos</h6>
                            <h4 class="mb-1 fw-bold">{{ $shortcodes->where('is_active', true)->count() }}</h4>
                            <small class="text-muted">Habilitados</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-warning mb-2"><i class="fas fa-toggle-off me-1"></i>Inactivos</h6>
                            <h4 class="mb-1 fw-bold">{{ $shortcodes->where('is_active', false)->count() }}</h4>
                            <small class="text-muted">Deshabilitados</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-info mb-2"><i class="fas fa-file-code me-1"></i>Con plantilla</h6>
                            <h4 class="mb-1 fw-bold">{{ $shortcodes->whereNotNull('render_template')->where('render_template', '!=', '')->count() }}</h4>
                            <small class="text-muted">Tienen render</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Grid --}}
        <div class="card-body">
            <div class="mb-3">
                <h6 class="mb-1 fw-bold">Listado de shortcodes</h6>
                <p class="text-muted small mb-0">Arrastra las tarjetas para reordenar</p>
            </div>

            @if($shortcodes->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-code fa-3x text-muted mb-3 opacity-50"></i>
                    <h5 class="text-muted">No hay shortcodes definidos</h5>
                    <a href="{{ route('settings.shortcodes.create') }}" class="btn btn-primary mt-2">
                        Crear primer shortcode
                    </a>
                </div>
            @else
                <div class="row g-3" id="shortcodesGrid">
                    @foreach($shortcodes as $shortcode)
                        <div class="col-xl-3 col-lg-4 col-sm-6" data-id="{{ $shortcode->id }}">
                            <div class="card h-100 {{ $shortcode->is_active ? '' : 'opacity-50' }}">
                                <div class="card-body text-center py-3">
                                    <div class="drag-handle text-muted mb-2" style="cursor:grab; font-size:.8rem;">
                                        <i class="fas fa-grip-vertical"></i>
                                    </div>
                                    <div class="mb-2" style="font-size:2rem; color:#adb5bd;">
                                        <i class="{{ $shortcode->icon ?: 'fas fa-code' }}"></i>
                                    </div>
                                    <h6 class="card-title mb-1 fw-semibold">{{ $shortcode->name }}</h6>
                                    <p class="card-text  text-muted mb-1">{{ $shortcode->description }}</p>
                                    <code class="small text-secondary">{{ $shortcode->key }}</code>
                                    @if($shortcode->render_template)
                                        <div class="mt-1">
                                            <span class="badge bg-success-subtle text-success" title="Tiene plantilla de renderizado">
                                                <i class="fas fa-check-circle me-1"></i>Render
                                            </span>
                                        </div>
                                    @endif
                                    @if(!$shortcode->is_active)
                                        <div class="mt-1">
                                            <span class="badge bg-secondary-subtle text-secondary">Inactivo</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="card-footer bg-white border-top-0 pt-0 d-flex gap-1 justify-content-center">
                                    <button type="button"
                                            class="btn btn-sm {{ $shortcode->is_active ? 'btn-success' : 'btn-outline-secondary' }} btn-toggle-active"
                                            data-id="{{ $shortcode->id }}"
                                            data-url="{{ route('settings.shortcodes.toggle', $shortcode->id) }}"
                                            title="{{ $shortcode->is_active ? 'Desactivar' : 'Activar' }}">
                                        <i class="fas {{ $shortcode->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                    </button>
                                    <a href="{{ route('settings.shortcodes.edit', $shortcode->id) }}"
                                       class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger btn-delete"
                                            data-id="{{ $shortcode->id }}"
                                            data-url="{{ route('settings.shortcodes.destroy', $shortcode->id) }}"
                                            data-name="{{ $shortcode->name }}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function () {
            var CSRF = $('meta[name="csrf-token"]').attr('content');

            // ── Drag & drop reorder ──────────────────────────────────────────
            if (typeof Sortable !== 'undefined') {
                Sortable.create(document.getElementById('shortcodesGrid'), {
                    handle: '.drag-handle',
                    animation: 150,
                    onEnd: function () {
                        var order = [];
                        $('#shortcodesGrid [data-id]').each(function () {
                            order.push($(this).data('id'));
                        });
                        $.ajax({
                            url: '{{ route('settings.shortcodes.order') }}',
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': CSRF },
                            data: { order: order },
                            success: function () {
                                toastr.success('Orden guardado.', 'Éxito');
                            },
                            error: function () {
                                toastr.error('No se pudo guardar el orden.', 'Error');
                            }
                        });
                    }
                });
            }

            // ── Toggle activo / inactivo ─────────────────────────────────────
            $(document).on('click', '.btn-toggle-active', function () {
                var $btn  = $(this);
                var $card = $btn.closest('.col-xl-3, .col-lg-4, .col-sm-6');

                $.ajax({
                    url: $btn.data('url'),
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF },
                    success: function (res) {
                        var active = res.is_active;
                        $btn.toggleClass('btn-success', active)
                            .toggleClass('btn-outline-secondary', !active)
                            .attr('title', active ? 'Desactivar' : 'Activar')
                            .find('i')
                            .toggleClass('fa-toggle-on', active)
                            .toggleClass('fa-toggle-off', !active);

                        $card.find('.card').toggleClass('opacity-50', !active);

                        // Mostrar/ocultar badge "Inactivo"
                        var $badge = $card.find('.badge.bg-secondary-subtle');
                        if (!active && $badge.length === 0) {
                            $card.find('[data-id]').length; // placeholder
                            $card.find('.card-body').append(
                                '<div class="mt-1"><span class="badge bg-secondary-subtle text-secondary">Inactivo</span></div>'
                            );
                        } else if (active) {
                            $badge.closest('div').remove();
                        }

                        toastr.success(active ? 'Shortcode activado.' : 'Shortcode desactivado.');
                    },
                    error: function () {
                        toastr.error('No se pudo cambiar el estado.', 'Error');
                    }
                });
            });

            // ── Eliminar con AJAX ────────────────────────────────────────────
            $(document).on('click', '.btn-delete', function () {
                var $btn  = $(this);
                var name  = $btn.data('name');
                var url   = $btn.data('url');
                var $col  = $btn.closest('[data-id]');

                if (!confirm('¿Eliminar el shortcode «' + name + '»?')) {
                    return;
                }

                $.ajax({
                    url: url,
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json',
                    },
                    success: function () {
                        $col.fadeOut(300, function () { $(this).remove(); });
                        toastr.success('Shortcode eliminado.', 'Éxito');
                    },
                    error: function () {
                        toastr.error('No se pudo eliminar el shortcode.', 'Error');
                    }
                });
            });

            @if(session('success'))
            toastr.success(@json(session('success')), 'Éxito');
            @endif
            @if(session('error'))
            toastr.error(@json(session('error')), 'Error');
            @endif
        });
    </script>
@endpush

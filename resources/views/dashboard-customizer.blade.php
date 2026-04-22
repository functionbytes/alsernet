@extends('layouts.theme')
@section('title', 'Dashboard personalizable')
@section('content')
    @include('core::components.card', ['title' => 'Dashboard personalizable'])

    <div class="widget-content">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="mb-0">Mi dashboard</h5>
                        <small class="text-muted">Arrastra y organiza los widgets</small>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-light" id="reset-dashboard">Restaurar defecto</button>
                        <button class="btn btn-sm btn-primary" id="save-dashboard">
                            <i class="fas fa-save me-1"></i>Guardar
                        </button>
                    </div>
                </div>

                <div class="row g-3" id="dashboard-grid">
                    @foreach($dashboard->widgets as $w)
                        <div class="col-md-{{ $w['w'] ?? 3 }}"
                             data-widget-id="{{ $w['id'] }}"
                             data-widget-type="{{ $w['type'] }}"
                             data-widget-w="{{ $w['w'] ?? 3 }}">
                            <div class="card bg-light h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <h6 class="text-muted mb-0">{{ $w['title'] ?? '' }}</h6>
                                        <button class="btn btn-sm btn-link p-0 remove-widget" type="button">
                                            <i class="fas fa-times text-muted"></i>
                                        </button>
                                    </div>
                                    <div class="widget-value" data-source="{{ $w['source'] ?? '' }}">
                                        @if(($w['type'] ?? '') === 'stat')
                                            <h3 class="fw-bold mb-0">—</h3>
                                        @else
                                            <p class="text-muted mb-0">{{ $w['title'] ?? '' }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
$(function () {
    const grid = document.getElementById('dashboard-grid');
    new Sortable(grid, { animation: 150, ghostClass: 'opacity-50' });

    // Load stat widget values
    $('.widget-value[data-source]').each(function () {
        const $el = $(this);
        const source = $el.data('source');

        if (! source) {
            return;
        }

        $.get(`/panel/dashboard/widget-data/${source}`)
            .done(function (res) {
                $el.find('h3').text(res.value ?? '—');
            })
            .fail(function () {
                $el.find('h3').text('—');
            });
    });

    // Remove widget
    $(document).on('click', '.remove-widget', function () {
        $(this).closest('[data-widget-id]').remove();
    });

    // Save layout
    $('#save-dashboard').on('click', function () {
        const widgets = [];

        $('[data-widget-id]').each(function (i) {
            const $col = $(this);
            widgets.push({
                id: $col.data('widget-id'),
                type: $col.data('widget-type'),
                title: $col.find('h6').text().trim(),
                source: $col.find('.widget-value').data('source') || null,
                x: i * 3,
                y: 0,
                w: parseInt($col.data('widget-w')) || 3,
                h: 2,
            });
        });

        $.ajax({
            url: '{{ route("dashboard.customize.save") }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: JSON.stringify({ widgets: widgets }),
            contentType: 'application/json',
        }).done(function () {
            toastr.success('Dashboard guardado');
        }).fail(function () {
            toastr.error('No se pudo guardar el dashboard');
        });
    });

    // Reset to defaults
    $('#reset-dashboard').on('click', function () {
        if (! confirm('¿Restaurar el dashboard por defecto?')) {
            return;
        }

        $.post(
            '{{ route("dashboard.customize.reset") }}',
            { _token: $('meta[name="csrf-token"]').attr('content') }
        ).done(function () {
            location.reload();
        }).fail(function () {
            toastr.error('No se pudo restaurar el dashboard');
        });
    });
});
</script>
@endpush

@extends('layouts.theme')

@section('title', 'Funcionalidades de menú')

@section('page_header')
    @include('core::components.card', ['title' => 'Funcionalidades de menú'])
@endsection

@section('content')

<div class="widget-content searchable-container list">

    @include('core::components.alerts')

    <div class="card">
        <div class="card-header p-4 border-bottom border-light">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Funcionalidades de menú</h5>
                    <p class="small mb-0 text-muted">
                        Activa o desactiva qué íconos, menús y accesos aparecen en la navegación del panel, por módulo.
                        <a href="{{ route('settings.modules.index') }}">Volver a módulos</a>
                    </p>
                </div>
                <div class="form-check form-switch mb-0 ms-4">
                    <input class="form-check-input fs-5" type="checkbox" id="master-toggle" role="switch">
                    <label class="visually-hidden" for="master-toggle">Activar todo</label>
                </div>
            </div>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('settings.modules.menu-features.update') }}">
                @csrf
                @method('PUT')

                @forelse($groups as $groupTitle => $items)

                <div class="d-flex justify-content-between align-items-start mb-2 {{ !$loop->first ? 'mt-1' : '' }}">
                    <div>
                        <h6 class="fw-semibold mb-0">{{ $groupTitle }}</h6>
                    </div>
                    <div class="d-flex align-items-center gap-2 ms-3 flex-shrink-0">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input section-toggle"
                                   type="checkbox"
                                   id="toggle-{{ \Illuminate\Support\Str::slug($groupTitle) }}"
                                   data-target="section-{{ \Illuminate\Support\Str::slug($groupTitle) }}"
                                   role="switch">
                            <label class="visually-hidden" for="toggle-{{ \Illuminate\Support\Str::slug($groupTitle) }}">Activar sección</label>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4" id="section-{{ \Illuminate\Support\Str::slug($groupTitle) }}">
                    @foreach($items as $item)
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input feature-toggle" type="checkbox"
                                   id="nav-item-{{ \Illuminate\Support\Str::slug($item['key']) }}"
                                   name="enabled[]" value="{{ $item['key'] }}"
                                   {{ nav_item_enabled($item['key']) ? 'checked' : '' }}>
                            <label class="form-check-label" for="nav-item-{{ \Illuminate\Support\Str::slug($item['key']) }}">
                                {{ $item['label'] }}
                            </label>
                        </div>
                    </div>
                    @endforeach
                </div>

                @if(!$loop->last)<hr>@endif

                @empty
                    <p class="text-muted small mb-0">No hay elementos de menú registrados todavía.</p>
                @endforelse

                <div class="d-flex justify-content-end mt-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Guardar configuracion
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
$(function () {

    function syncSectionToggle(sectionId) {
        var $inputs = $('#' + sectionId).find('.feature-toggle');
        var total   = $inputs.length;
        var checked = $inputs.filter(':checked').length;
        var $t = $('[data-target="' + sectionId + '"]');
        $t.prop('checked', checked === total);
        $t[0].indeterminate = checked > 0 && checked < total;
    }

    function syncMasterToggle() {
        var $all    = $('.feature-toggle');
        var total   = $all.length;
        var checked = $all.filter(':checked').length;
        var $m = $('#master-toggle');
        $m.prop('checked', checked === total);
        $m[0].indeterminate = checked > 0 && checked < total;
    }

    $('.section-toggle').each(function () {
        syncSectionToggle($(this).data('target'));
    });
    syncMasterToggle();

    $(document).on('change', '.section-toggle', function () {
        var target  = $(this).data('target');
        var checked = $(this).is(':checked');
        $('#' + target).find('.feature-toggle').prop('checked', checked);
        this.indeterminate = false;
        syncMasterToggle();
    });

    $('#master-toggle').on('change', function () {
        var checked = $(this).is(':checked');
        $('.feature-toggle').prop('checked', checked);
        $('.section-toggle').prop('checked', checked).each(function () {
            this.indeterminate = false;
        });
    });

    $(document).on('change', '.feature-toggle', function () {
        var sectionId = $(this).closest('[id^="section-"]').attr('id');
        if (sectionId) syncSectionToggle(sectionId);
        syncMasterToggle();
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush

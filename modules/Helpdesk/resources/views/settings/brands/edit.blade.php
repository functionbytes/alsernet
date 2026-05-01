@extends('layouts.theme')

@section('title', 'Editar marca')

@section('content')

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('settings.helpdesk.brands.update', $brand) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Editar marca</h5>
                        <small class="text-muted">{{ $brand->name }}</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')
                        @include('helpdesk::settings.brands._form')
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-save"></i> Guardar cambios
                        </button>
                        <a href="{{ route('settings.helpdesk.brands.index') }}" class="btn btn-light w-100">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Informacion de la marca</h6>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Slug</span>
                        <code class="small">{{ $brand->slug }}</code>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Inboxes</span>
                        <span class="fw-semibold">{{ $brand->inboxes()->count() }}</span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Conversaciones</span>
                        <span class="fw-semibold">{{ $brand->conversations()->count() }}</span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Estado</span>
                        <span>
                            @if($brand->is_active)
                                <span class="badge bg-success-subtle text-success">Activa</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Inactiva</span>
                            @endif
                        </span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Creada</span>
                        <span class="small">{{ $brand->created_at->diffForHumans() }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted small">Ultima actualizacion</span>
                        <span class="small">{{ $brand->updated_at->diffForHumans() }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('#primary_color').on('input', function () {
        $('#primary_color_text').val($(this).val());
    });

    $('#primary_color_text').on('input', function () {
        const val = $(this).val();
        if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
            $('#primary_color').val(val);
        }
    });

    $('.btn-copy-token').on('click', function () {
        const token = $(this).closest('.input-group').find('input').val();
        navigator.clipboard.writeText(token).then(function () {
            toastr.success('Token copiado al portapapeles');
        });
    });
});
</script>
@endpush

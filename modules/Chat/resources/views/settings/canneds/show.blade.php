@extends('layouts.theme')

@section('title', 'Ver respuesta predefinida')

@section('content')

<div class="row">
    <div class="col-lg-12 d-flex align-items-stretch">
        <div class="card w-100">
            <div class="card-header border-bottom p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Respuesta predefinida: {{ $canned->short_code }}</h5>
                        <p class="mb-0 text-muted small">Detalles de la respuesta predefinida.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.chat.canneds.edit', $canned) }}" class="btn btn-primary">
                            Editar
                        </a>
                        <a href="{{ route('settings.chat.canneds.index') }}" class="btn btn-secondary">
                            Volver
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body">
                @include('core::components.alerts')

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label fw-bold">Código</label>
                            <div>
                                <code class="bg-light px-2 py-1 rounded">{{ $canned->short_code }}</code>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label fw-bold">Fecha de creación</label>
                            <div>
                                {{ $canned->created_at->format('d/m/Y H:i') }}
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label fw-bold">Contenido</label>
                            <div class="card bg-light">
                                <div class="card-body">
                                    {!! nl2br(e($canned->content ?? '')) !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer border-top">
                <a href="{{ route('settings.chat.canneds.edit', $canned) }}" class="btn btn-primary w-100 mb-1">
                    Editar
                </a>
                <a href="{{ route('settings.chat.canneds.index') }}" class="btn btn-secondary w-100">
                    Volver al listado
                </a>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush

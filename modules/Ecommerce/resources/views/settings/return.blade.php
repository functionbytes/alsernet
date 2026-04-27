@extends('layouts.theme')

@section('title', 'Devoluciones')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Devoluciones'])
    @include('core::components.alerts')

    @php
        $returnsEnabled     = old('returns_enabled', $settings['returns_enabled'] ?? '') == '1';
        $customQtyEnabled   = old('returns_allow_custom_quantity', $settings['returns_allow_custom_quantity'] ?? '') == '1';
    @endphp

    <form action="{{ route('settings.ecommerce.return.update') }}" method="POST">
        @csrf
        @method('PATCH')

        <div class="row g-4 align-items-start">
            <div class="col-lg-8">

                <div class="card">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Devoluciones</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">

                            {{-- Habilitar devoluciones --}}
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="returns_enabled" value="1" id="returns_enabled"
                                        {{ $returnsEnabled ? 'checked' : '' }}>
                                    <label class="form-check-label" for="returns_enabled">Está habilitada la devolución de pedidos.</label>
                                </div>
                                @error('returns_enabled')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Campos condicionales --}}
                            <div id="returns-fields-block" style="{{ $returnsEnabled ? '' : 'display:none' }}">
                                <div class="row g-3">

                                    {{-- Días retornables --}}
                                    <div class="col-md-6">
                                        <label for="returnable_days" class="form-label">Días retornables</label>
                                        <input type="number" name="returnable_days" id="returnable_days" min="0"
                                            class="form-control @error('returnable_days') is-invalid @enderror"
                                            value="{{ old('returnable_days', $settings['returnable_days'] ?? '30') }}">
                                        @error('returnable_days')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- Cantidad personalizada --}}
                                    <div class="col-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="returns_allow_custom_quantity" value="1" id="returns_allow_custom_quantity"
                                                {{ $customQtyEnabled ? 'checked' : '' }}>
                                            <label class="form-check-label" for="returns_allow_custom_quantity">Puede personalizar la cantidad del producto de retorno.</label>
                                        </div>
                                        @error('returns_allow_custom_quantity')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>

            <div class="col-lg-4">
                <div class="sticky-top" style="top:80px">
                    <div class="card">
                        <div class="card-header p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">Guardar</h6>
                        </div>
                        <div class="card-body">
                            <button type="submit" class="btn btn-primary w-100">Guardar cambios</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
$(function () {
    $('#returns_enabled').on('change', function () {
        $('#returns-fields-block').slideToggle(150);
    });
});
</script>
@endpush

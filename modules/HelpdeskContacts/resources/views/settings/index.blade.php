@extends('layouts.theme')

@section('title', 'Estilo de la ficha de contacto')

@push('css')
    <link rel="stylesheet" href="{{ asset('modules/contacts/css/contacts.css') }}?v={{ filemtime(public_path('modules/contacts/css/contacts.css')) }}">
@endpush

@section('page_header')
    @include('core::components.card', ['title' => 'Estilo de la ficha de contacto'])
@endsection

@section('content')

    <div class="row g-3">

        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('contacts.settings.update') }}" method="POST" id="c3s-form">
                    @csrf
                    @method('PUT')

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Estilo de la ficha 360</h5>
                        <small class="text-muted">Cómo se organiza la ficha de un contacto para todo el equipo</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <label class="form-label" for="c3s-layout">Estilo</label>
                                <select name="detail_layout" id="c3s-layout" class="form-select select2 @error('detail_layout') is-invalid @enderror">
                                    @foreach($options as $key => $opt)
                                        <option value="{{ $key }}" {{ old('detail_layout', $current) === $key ? 'selected' : '' }}>{{ $opt['label'] }}</option>
                                    @endforeach
                                </select>
                                @error('detail_layout')
                                    <span class="field-validation-error">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <h6 class="fw-semibold mb-1">Los estilos</h6>
                        <p class="text-muted small mb-3">Todos muestran la misma información; cambia cómo se ordena. Pulsa una tarjeta para elegirla.</p>

                        <div class="c3s-grid">
                            @foreach($options as $key => $opt)
                                <div class="c3s-card{{ old('detail_layout', $current) === $key ? ' is-selected' : '' }}" data-c3s-pick="{{ $key }}" role="button" tabindex="0" aria-label="Elegir {{ $opt['label'] }}">
                                    <div class="c3s-thumb c3s-thumb--{{ $key }}" aria-hidden="true">
                                        <span class="a"></span><span class="b"></span><span class="c"></span><span class="d"></span>
                                    </div>
                                    <div class="c3s-card-body">
                                        <div class="c3s-card-head">
                                            <span class="c3s-card-title">{{ $opt['label'] }}</span>
                                            @if($current === $key)
                                                <span class="c3s-card-badge">En uso</span>
                                            @endif
                                        </div>
                                        <p class="c3s-card-desc">{{ $opt['desc'] }}</p>
                                        @if($sampleId)
                                            <a class="c3s-card-link" href="{{ route('contacts.show', ['customer' => $sampleId, 'layout' => $key]) }}" target="_blank" rel="noopener">Previsualizar</a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar configuración</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Sobre esta configuración</h6>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted">
                        El estilo se aplica a todos los agentes al abrir la ficha de un contacto desde
                        <a href="{{ route('contacts.index') }}">Contactos</a>.
                    </p>
                    <p class="card-text text-muted mb-0">
                        "Previsualizar" abre un contacto con ese estilo sin guardarlo.
                    </p>
                </div>
            </div>
            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Qué estilo elegir</h6>
                </div>
                <div class="card-body">
                    <ul class="text-muted mb-0">
                        <li class="mb-2">Atención al cliente con mucho volumen: Maestro-detalle o Qué hacer ahora</li>
                        <li class="mb-2">Revisar la historia de un cliente: Línea de vida</li>
                        <li class="mb-0">Cuentas grandes y clientes VIP: Valor del cliente</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
    (function ($) {
        'use strict';

        function pick(key) {
            var $select = $('#c3s-layout');
            $select.val(key).trigger('change');
            $('[data-c3s-pick]').removeClass('is-selected').filter('[data-c3s-pick="' + key + '"]').addClass('is-selected');
        }

        $(document).on('click', '[data-c3s-pick]', function (e) {
            if ($(e.target).closest('a').length) {
                return;
            }
            pick($(this).data('c3s-pick'));
        });

        $(document).on('keydown', '[data-c3s-pick]', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                pick($(this).data('c3s-pick'));
            }
        });

        $(document).on('change', '#c3s-layout', function () {
            var key = $(this).val();
            $('[data-c3s-pick]').removeClass('is-selected').filter('[data-c3s-pick="' + key + '"]').addClass('is-selected');
        });
    })(jQuery);
</script>
@endpush

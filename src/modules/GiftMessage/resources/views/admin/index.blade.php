@extends('layouts.theme')

@section('title', $pageTitle)

@push('styles')
<link rel="stylesheet" href="{{ asset('modules/giftmessage/css/editor.css') }}">
@endpush

@section('content')

    @include('core::components.card', ['title' => $pageTitle])

    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('giftmessage.history.index') }}" class="btn btn-light">
            <i class="fas fa-clock-rotate-left me-1"></i> Historial
        </a>
    </div>

    @include('core::components.alerts')

    <div class="row g-3">

        <div class="col-12">
            <div class="card">
                <form action="{{ route('giftmessage.images.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Imagenes base</h5>
                        <small class="text-muted">Sobre y tarjeta que se usan como fondo del PDF.</small>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Imagen del sobre</label>
                                <input type="file" class="form-control @error('envelope_image') is-invalid @enderror"
                                       name="envelope_image" accept="image/*">
                                @if($config->envelope_image)
                                    <small class="form-text text-muted">Actual: {{ basename($config->envelope_image) }}</small>
                                @endif
                                @error('envelope_image')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Imagen de la tarjeta</label>
                                <input type="file" class="form-control @error('card_image') is-invalid @enderror"
                                       name="card_image" accept="image/*">
                                @if($config->card_image)
                                    <small class="form-text text-muted">Actual: {{ basename($config->card_image) }}</small>
                                @endif
                                @error('card_image')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Guardar imagenes</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold">Posiciones de texto</h5>
                    <small class="text-muted">Arrastra los textos T1 y T2 sobre cada imagen y guarda su posicion.</small>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-12 col-lg-6">
                            <h6 class="fw-bold mb-2">Sobre</h6>
                            @if(! $config->envelope_image)
                                <div class="alert alert-warning mb-0">Sube primero la imagen del sobre y guarda para poder posicionar los textos.</div>
                            @else
                                <div class="giftmessage-canvas-outer">
                                    <div id="canvas-envelope" class="giftmessage-canvas giftmessage-canvas-envelope"
                                         data-bg="{{ asset('storage/'.$config->envelope_image) }}">
                                        <div class="giftmessage-drag" data-scope="envelope" data-slot="t1"
                                             data-x="{{ $config->env_t1_x }}" data-y="{{ $config->env_t1_y }}" tabindex="0">
                                            T1 &middot; Nombre
                                        </div>
                                        <div class="giftmessage-drag" data-scope="envelope" data-slot="t2"
                                             data-x="{{ $config->env_t2_x }}" data-y="{{ $config->env_t2_y }}" tabindex="0">
                                            T2 &middot; Gestion
                                        </div>
                                    </div>
                                </div>
                                <button type="button" id="save-positions-envelope" class="btn btn-outline-primary mt-3">
                                    Guardar posiciones (sobre)
                                </button>
                            @endif
                        </div>

                        <div class="col-12 col-lg-6">
                            <h6 class="fw-bold mb-2">Tarjeta</h6>
                            @if(! $config->card_image)
                                <div class="alert alert-warning mb-0">Sube primero la imagen de la tarjeta y guarda para poder posicionar los textos.</div>
                            @else
                                <div class="giftmessage-canvas-outer">
                                    <div id="canvas-card" class="giftmessage-canvas giftmessage-canvas-card"
                                         data-bg="{{ asset('storage/'.$config->card_image) }}">
                                        <div class="giftmessage-drag" data-scope="card" data-slot="t1"
                                             data-x="{{ $config->card_t1_x }}" data-y="{{ $config->card_t1_y }}" tabindex="0">
                                            T1 &middot; Mensaje
                                        </div>
                                        <div class="giftmessage-drag" data-scope="card" data-slot="t2"
                                             data-x="{{ $config->card_t2_x }}" data-y="{{ $config->card_t2_y }}" tabindex="0">
                                            T2 &middot; Gestion
                                        </div>
                                    </div>
                                </div>
                                <button type="button" id="save-positions-card" class="btn btn-outline-primary mt-3">
                                    Guardar posiciones (tarjeta)
                                </button>
                            @endif
                        </div>
                    </div>

                    <p class="small text-muted mt-3 mb-0">
                        El texto T1 (nombre o mensaje) siempre se centra horizontalmente en el PDF final; solo se respeta su
                        posicion vertical. El texto T2 (numero de gestion) respeta la posicion exacta guardada.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <form action="{{ route('giftmessage.fonts.update') }}" method="POST">
                    @csrf
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Fuentes y tamanos</h5>
                        <small class="text-muted">Tipografia y tamano de cada texto, por separado para sobre y tarjeta.</small>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-12 col-md-6">
                                <h6 class="fw-bold mb-3 border-bottom pb-2">Sobre</h6>
                                <div class="row g-3 mb-3">
                                    <div class="col-8">
                                        <label class="form-label">Texto 1 (nombre) &mdash; fuente</label>
                                        <select class="form-select select2" name="env_t1_font">
                                            @foreach($fonts as $code => $label)
                                                <option value="{{ $code }}" {{ $config->env_t1_font === $code ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label">Tamano</label>
                                        <input type="number" class="form-control" name="env_t1_size" min="6" max="72" value="{{ $config->env_t1_size }}">
                                    </div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-8">
                                        <label class="form-label">Texto 2 (gestion) &mdash; fuente</label>
                                        <select class="form-select select2" name="env_t2_font">
                                            @foreach($fonts as $code => $label)
                                                <option value="{{ $code }}" {{ $config->env_t2_font === $code ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label">Tamano</label>
                                        <input type="number" class="form-control" name="env_t2_size" min="6" max="72" value="{{ $config->env_t2_size }}">
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <h6 class="fw-bold mb-3 border-bottom pb-2">Tarjeta</h6>
                                <div class="row g-3 mb-3">
                                    <div class="col-8">
                                        <label class="form-label">Texto 1 (mensaje) &mdash; fuente</label>
                                        <select class="form-select select2" name="card_t1_font">
                                            @foreach($fonts as $code => $label)
                                                <option value="{{ $code }}" {{ $config->card_t1_font === $code ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label">Tamano</label>
                                        <input type="number" class="form-control" name="card_t1_size" min="6" max="72" value="{{ $config->card_t1_size }}">
                                    </div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-8">
                                        <label class="form-label">Texto 2 (gestion) &mdash; fuente</label>
                                        <select class="form-select select2" name="card_t2_font">
                                            @foreach($fonts as $code => $label)
                                                <option value="{{ $code }}" {{ $config->card_t2_font === $code ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label">Tamano</label>
                                        <input type="number" class="form-control" name="card_t2_size" min="6" max="72" value="{{ $config->card_t2_size }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Guardar fuentes</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold">Pedidos con mensaje regalo</h5>
                    <small class="text-muted">Busca por numero de gestion o usa el listado reciente para generar los PDF.</small>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-2 align-items-end mb-3">
                        <div class="flex-fill">
                            <label class="form-label">Numeros de gestion</label>
                            <input type="text" id="gestion-search" class="form-control" placeholder="Ej: 73230,73231">
                        </div>
                        <button type="button" id="gestion-search-btn" class="btn btn-primary">Buscar</button>
                        <button type="button" id="gestion-reset-btn" class="btn btn-light">Reset</button>
                    </div>

                    <form id="generate-form" action="{{ route('giftmessage.generate') }}" method="POST" target="_blank">
                        @csrf
                        <input type="hidden" name="type" id="generate-type" value="envelope">
                        <div id="generate-hidden-rows"></div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="orders-table">
                                <thead class="table-light">
                                    <tr>
                                        <th width="3%">Sel</th>
                                        <th>ID pedido</th>
                                        <th>ID gestion</th>
                                        <th>Nombre</th>
                                        <th>Mensaje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($orders as $order)
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="form-check-input order-checkbox"
                                                       data-id-order="{{ $order['id_order'] }}"
                                                       data-gift-message="{{ $order['gift_message'] }}"
                                                       data-firstname="{{ $order['firstname'] ?? '' }}"
                                                       data-lastname="{{ $order['lastname'] ?? '' }}"
                                                       data-id-gestion="{{ $order['id_gestion'] ?? '' }}">
                                            </td>
                                            <td>{{ $order['id_order'] }}</td>
                                            <td>{{ $order['id_gestion'] ?? '—' }}</td>
                                            <td>{{ trim(($order['firstname'] ?? '').' '.($order['lastname'] ?? '')) }}</td>
                                            <td>{{ $order['gift_message'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-3">No hay pedidos recientes con mensaje regalo.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div id="generate-actions" class="row g-2 d-none">
                            <div class="col-auto">
                                <button type="submit" class="btn btn-outline-primary" data-type="envelope">PDF sobre</button>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-outline-primary" data-type="card">PDF tarjeta</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <script>
        window.GIFTMESSAGE_EDITOR = {
            urls: {
                savePositions: "{{ route('giftmessage.positions.save') }}",
                ordersSearch: "{{ route('giftmessage.orders.search') }}",
            },
            fonts: {
                envelope: {
                    t1: { font: @json($config->env_t1_font), size: @json($config->env_t1_size) },
                    t2: { font: @json($config->env_t2_font), size: @json($config->env_t2_size) },
                },
                card: {
                    t1: { font: @json($config->card_t1_font), size: @json($config->card_t1_size) },
                    t2: { font: @json($config->card_t2_font), size: @json($config->card_t2_size) },
                },
            },
        };
    </script>

@endsection

@push('scripts')
<script src="{{ asset('modules/giftmessage/js/vendor/interact.min.js') }}"></script>
<script src="{{ asset('modules/giftmessage/js/editor.js') }}"></script>
<script>
$(document).ready(function () {
    $('.select2').select2({ width: '100%' });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif
});
</script>
@endpush

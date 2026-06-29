@extends('layouts.theme')

@section('title', 'Monedas')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Monedas'])
@endsection

@section('content')
    @include('core::components.alerts')

    <form action="{{ route('settings.ecommerce.currencies.update') }}" method="POST">
        @csrf
        @method('PATCH')

        <div class="row g-4 align-items-start">
            <div class="col-lg-8">

                <div class="card mb-4">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Formato de moneda</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="currency_auto_detect" value="1" id="currency_auto_detect"
                                        {{ old('currency_auto_detect', $settings['currency_auto_detect'] ?? '') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="currency_auto_detect">Detección automática de moneda del visitante</label>
                                </div>
                                <div class="form-text">Detecta la moneda del visitante según el idioma del navegador. Anulará la selección de moneda predeterminada.</div>
                            </div>

                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="currency_space_between" value="1" id="currency_space_between"
                                        {{ old('currency_space_between', $settings['currency_space_between'] ?? '') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="currency_space_between">Añade un espacio entre precio y símbolo</label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="currency_thousands_separator" class="form-label">Separador de miles</label>
                                <select name="currency_thousands_separator" id="currency_thousands_separator"
                                    class="form-select @error('currency_thousands_separator') is-invalid @enderror">
                                    <option value="," {{ old('currency_thousands_separator', $settings['currency_thousands_separator'] ?? '') == ',' ? 'selected' : '' }}>Coma (,)</option>
                                    <option value="." {{ old('currency_thousands_separator', $settings['currency_thousands_separator'] ?? '') == '.' ? 'selected' : '' }}>Período (.)</option>
                                    <option value=" " {{ old('currency_thousands_separator', $settings['currency_thousands_separator'] ?? '') == ' ' ? 'selected' : '' }}>Espacio</option>
                                    <option value="" {{ old('currency_thousands_separator', $settings['currency_thousands_separator'] ?? '') == '' ? 'selected' : '' }}>Ninguno</option>
                                </select>
                                @error('currency_thousands_separator')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="currency_decimal_separator" class="form-label">Separador decimal</label>
                                <select name="currency_decimal_separator" id="currency_decimal_separator"
                                    class="form-select @error('currency_decimal_separator') is-invalid @enderror">
                                    <option value="." {{ old('currency_decimal_separator', $settings['currency_decimal_separator'] ?? '') == '.' ? 'selected' : '' }}>Período (.)</option>
                                    <option value="," {{ old('currency_decimal_separator', $settings['currency_decimal_separator'] ?? '') == ',' ? 'selected' : '' }}>Coma (,)</option>
                                </select>
                                @error('currency_decimal_separator')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Proveedor de API</h6>
                    </div>
                    <div class="card-body">
                        @php $provider = old('currency_exchange_api_provider', $settings['currency_exchange_api_provider'] ?? 'none'); @endphp
                        <div class="row g-2 mb-3">
                            <div class="col-auto">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="currency_exchange_api_provider" id="api_none" value="none"
                                        {{ $provider == 'none' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="api_none">Ninguno</label>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="currency_exchange_api_provider" id="api_layer" value="layer"
                                        {{ $provider == 'layer' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="api_layer">Capa API</label>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="currency_exchange_api_provider" id="api_open" value="open_exchange"
                                        {{ $provider == 'open_exchange' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="api_open">Tipos de cambio abiertos</label>
                                </div>
                            </div>
                        </div>

                        <div id="api-layer-block" class="border rounded p-3 mb-3" style="{{ $provider == 'layer' ? '' : 'display:none' }}">
                            <label for="currency_exchange_api_key" class="form-label fw-semibold">Clave de tipos de cambio API</label>
                            <input type="password" name="currency_exchange_api_key" id="currency_exchange_api_key"
                                class="form-control @error('currency_exchange_api_key') is-invalid @enderror"
                                value="{{ old('currency_exchange_api_key', $settings['currency_exchange_api_key'] ?? '') }}">
                            <div class="form-text">Obtenga la clave API del tipo de cambio en: <a href="https://apilayer.com/marketplace/exchangerates_data-api" target="_blank">https://apilayer.com/marketplace/exchangerates_data-api</a></div>
                            @error('currency_exchange_api_key')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div id="api-open-block" class="border rounded p-3 mb-3" style="{{ $provider == 'open_exchange' ? '' : 'display:none' }}">
                            <label for="currency_open_exchange_app_id" class="form-label fw-semibold">Abrir ID de aplicación de tipos de cambio</label>
                            <input type="password" name="currency_open_exchange_app_id" id="currency_open_exchange_app_id"
                                class="form-control @error('currency_open_exchange_app_id') is-invalid @enderror"
                                value="{{ old('currency_open_exchange_app_id', $settings['currency_open_exchange_app_id'] ?? '') }}">
                            <div class="form-text">Obtenga la clave API del tipo de cambio en: <a href="https://openexchangerates.org/" target="_blank">https://openexchangerates.org/</a></div>
                            @error('currency_open_exchange_app_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="currency_use_api_exchange_rate" value="1" id="currency_use_api_exchange_rate"
                                {{ old('currency_use_api_exchange_rate', $settings['currency_use_api_exchange_rate'] ?? '') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="currency_use_api_exchange_rate">Utilice el tipo de cambio de API</label>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Monedas</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0" id="currencies-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Código</th>
                                        <th>Símbolo</th>
                                        <th>Decimales</th>
                                        <th>Tipo de cambio</th>
                                        <th>Posición</th>
                                        <th class="text-center">Default</th>
                                        <th class="text-center">Quitar</th>
                                    </tr>
                                </thead>
                                <tbody id="currencies-body">
                                    @php $currencies = $settings['currencies'] ?? [['code'=>'','symbol'=>'','decimals'=>'0','exchange_rate'=>'1','position'=>'before','is_default'=>true]]; @endphp
                                    @foreach($currencies as $i => $cur)
                                    <tr>
                                        <td><input type="text" name="currencies[{{ $i }}][code]" value="{{ $cur['code'] ?? '' }}" class="form-control form-control-sm" placeholder="USD" maxlength="10"></td>
                                        <td><input type="text" name="currencies[{{ $i }}][symbol]" value="{{ $cur['symbol'] ?? '' }}" class="form-control form-control-sm" placeholder="$" maxlength="10"></td>
                                        <td><input type="number" name="currencies[{{ $i }}][decimals]" value="{{ $cur['decimals'] ?? 0 }}" class="form-control form-control-sm" min="0" max="4"></td>
                                        <td><input type="number" name="currencies[{{ $i }}][exchange_rate]" value="{{ $cur['exchange_rate'] ?? 1 }}" class="form-control form-control-sm" min="0" step="0.000001"></td>
                                        <td>
                                            <select name="currencies[{{ $i }}][position]" class="form-select form-select-sm">
                                                <option value="before" {{ ($cur['position'] ?? 'before') == 'before' ? 'selected' : '' }}>Antes del número</option>
                                                <option value="after" {{ ($cur['position'] ?? '') == 'after' ? 'selected' : '' }}>Después del número</option>
                                            </select>
                                        </td>
                                        <td class="text-center">
                                            <input type="radio" name="currency_default_index" value="{{ $i }}" class="form-check-input" {{ !empty($cur['is_default']) ? 'checked' : '' }}>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-link text-danger p-0 remove-currency-row"><i class="fas fa-trash"></i></button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3 d-flex justify-content-between align-items-center">
                            <div class="alert alert-warning mb-0 py-1 px-2 small flex-grow-1 me-3">
                                <i class="fas fa-info-circle me-1"></i> Para la moneda predeterminada, el tipo de cambio debe ser 1.
                            </div>
                            <a href="#" id="add-currency-row" class="text-primary fw-semibold text-nowrap">Agregar moneda</a>
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
    function updateApiBlocks() {
        var val = $('input[name="currency_exchange_api_provider"]:checked').val();
        $('#api-layer-block').toggle(val === 'layer');
        $('#api-open-block').toggle(val === 'open_exchange');
    }

    $('input[name="currency_exchange_api_provider"]').on('change', updateApiBlocks);

    var rowIndex = {{ count($currencies ?? []) }};

    $('#add-currency-row').on('click', function (e) {
        e.preventDefault();
        var i = rowIndex++;
        var row = '<tr>' +
            '<td><input type="text" name="currencies[' + i + '][code]" value="" class="form-control form-control-sm" placeholder="USD" maxlength="10"></td>' +
            '<td><input type="text" name="currencies[' + i + '][symbol]" value="" class="form-control form-control-sm" placeholder="$" maxlength="10"></td>' +
            '<td><input type="number" name="currencies[' + i + '][decimals]" value="0" class="form-control form-control-sm" min="0" max="4"></td>' +
            '<td><input type="number" name="currencies[' + i + '][exchange_rate]" value="1" class="form-control form-control-sm" min="0" step="0.000001"></td>' +
            '<td><select name="currencies[' + i + '][position]" class="form-select form-select-sm">' +
                '<option value="before">Antes del número</option>' +
                '<option value="after">Después del número</option>' +
            '</select></td>' +
            '<td class="text-center"><input type="radio" name="currency_default_index" value="' + i + '" class="form-check-input"></td>' +
            '<td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger p-0 remove-currency-row"><i class="fas fa-trash"></i></button></td>' +
            '</tr>';
        $('#currencies-body').append(row);
    });

    $(document).on('click', '.remove-currency-row', function () {
        if ($('#currencies-body tr').length > 1) {
            $(this).closest('tr').remove();
        }
    });
});
</script>
@endpush

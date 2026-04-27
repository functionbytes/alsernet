@extends('layouts.theme')

@section('title', 'Facturas')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Facturas'])
    @include('core::components.alerts')

    @php
        $customFont    = old('invoice_custom_font_enabled', $settings['invoice_custom_font_enabled'] ?? '') == '1';
        $langSupport   = old('invoice_language_support', $settings['invoice_language_support'] ?? 'default');
        $pdfLibrary    = old('invoice_pdf_library', $settings['invoice_pdf_library'] ?? 'dompdf');
        $dateFormats   = [
            'M d, Y' => 'M d, Y',
            'F j, Y' => 'F j, Y',
            'F d, Y' => 'F d, Y',
            'Y-m-d'  => 'Y-m-d',
            'Y-M-d'  => 'Y-M-d',
            'd-m-Y'  => 'd-m-Y',
            'd-M-Y'  => 'd-M-Y',
            'm/d/Y'  => 'm/d/Y',
            'M/d/Y'  => 'M/d/Y',
            'd/m/Y'  => 'd/m/Y',
            'd/M/Y'  => 'd/M/Y',
        ];
    @endphp

    <form action="{{ route('settings.ecommerce.invoices.update') }}" method="POST">
        @csrf
        @method('PATCH')

        <div class="row g-4 align-items-start">
            <div class="col-lg-8">

                <div class="card">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Configuracion de la empresa</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">

                            {{-- Nombre --}}
                            <div class="col-12">
                                <label for="invoice_company_name" class="form-label">Nombre de empresa</label>
                                <input type="text" name="invoice_company_name" id="invoice_company_name"
                                    class="form-control @error('invoice_company_name') is-invalid @enderror"
                                    value="{{ old('invoice_company_name', $settings['invoice_company_name'] ?? '') }}">
                                @error('invoice_company_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Dirección --}}
                            <div class="col-12">
                                <label for="invoice_address" class="form-label">Dirección de la empresa</label>
                                <input type="text" name="invoice_address" id="invoice_address"
                                    class="form-control @error('invoice_address') is-invalid @enderror"
                                    value="{{ old('invoice_address', $settings['invoice_address'] ?? '') }}">
                                @error('invoice_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- País / Estado / Ciudad --}}
                            <div class="col-md-4">
                                <label for="invoice_country" class="form-label">País</label>
                                <input type="text" name="invoice_country" id="invoice_country"
                                    class="form-control @error('invoice_country') is-invalid @enderror"
                                    placeholder="Colombia"
                                    value="{{ old('invoice_country', $settings['invoice_country'] ?? '') }}">
                                @error('invoice_country')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="invoice_state" class="form-label">Estado o Provincia</label>
                                <input type="text" name="invoice_state" id="invoice_state"
                                    class="form-control @error('invoice_state') is-invalid @enderror"
                                    value="{{ old('invoice_state', $settings['invoice_state'] ?? '') }}">
                                @error('invoice_state')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="invoice_city" class="form-label">Ciudad</label>
                                <input type="text" name="invoice_city" id="invoice_city"
                                    class="form-control @error('invoice_city') is-invalid @enderror"
                                    value="{{ old('invoice_city', $settings['invoice_city'] ?? '') }}">
                                @error('invoice_city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Email / Teléfono --}}
                            <div class="col-md-6">
                                <label for="invoice_email" class="form-label">Correo electrónico de la empresa</label>
                                <input type="email" name="invoice_email" id="invoice_email"
                                    class="form-control @error('invoice_email') is-invalid @enderror"
                                    value="{{ old('invoice_email', $settings['invoice_email'] ?? '') }}">
                                @error('invoice_email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="invoice_phone" class="form-label">Teléfono de empresa</label>
                                <input type="text" name="invoice_phone" id="invoice_phone"
                                    class="form-control @error('invoice_phone') is-invalid @enderror"
                                    value="{{ old('invoice_phone', $settings['invoice_phone'] ?? '') }}">
                                @error('invoice_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- NIT/RFC --}}
                            <div class="col-12">
                                <label for="invoice_tax_id" class="form-label">Número de identificación fiscal de la empresa</label>
                                <input type="text" name="invoice_tax_id" id="invoice_tax_id"
                                    class="form-control @error('invoice_tax_id') is-invalid @enderror"
                                    value="{{ old('invoice_tax_id', $settings['invoice_tax_id'] ?? '') }}">
                                @error('invoice_tax_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Logo --}}
                            <div class="col-12">
                                <label for="invoice_logo" class="form-label">Logo de la compañía (URL)</label>
                                <input type="text" name="invoice_logo" id="invoice_logo"
                                    class="form-control @error('invoice_logo') is-invalid @enderror"
                                    placeholder="https://..."
                                    value="{{ old('invoice_logo', $settings['invoice_logo'] ?? '') }}">
                                @error('invoice_logo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12"><hr class="my-1"></div>

                            {{-- Fuente personalizada --}}
                            <div class="col-12">
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="checkbox" name="invoice_custom_font_enabled" value="1" id="invoice_custom_font_enabled"
                                        {{ $customFont ? 'checked' : '' }}>
                                    <label class="form-check-label" for="invoice_custom_font_enabled">Usar fuente personalizada para factura</label>
                                </div>
                                <div id="custom-font-block" style="{{ $customFont ? '' : 'display:none' }}">
                                    <div class="mt-2">
                                        <label for="invoice_custom_font" class="form-label">Familia de fuente</label>
                                        <input type="text" name="invoice_custom_font" id="invoice_custom_font"
                                            class="form-control @error('invoice_custom_font') is-invalid @enderror"
                                            placeholder="DejaVu Sans"
                                            value="{{ old('invoice_custom_font', $settings['invoice_custom_font'] ?? '') }}">
                                        @error('invoice_custom_font')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            {{-- Soporte de idioma --}}
                            <div class="col-12">
                                <div class="fw-semibold mb-2">Agregar soporte de idioma</div>
                                <div class="d-flex gap-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="invoice_language_support" id="invoice_lang_default" value="default"
                                            {{ $langSupport == 'default' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="invoice_lang_default">Solo lenguas latinas</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="invoice_language_support" id="invoice_lang_arabic" value="arabic"
                                            {{ $langSupport == 'arabic' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="invoice_lang_arabic">Arabic</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="invoice_language_support" id="invoice_lang_bangladesh" value="bangladesh"
                                            {{ $langSupport == 'bangladesh' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="invoice_lang_bangladesh">Bengali</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="invoice_language_support" id="invoice_lang_chinese" value="chinese"
                                            {{ $langSupport == 'chinese' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="invoice_lang_chinese">Chinese</label>
                                    </div>
                                </div>
                            </div>

                            {{-- Librería PDF --}}
                            <div class="col-12">
                                <div class="fw-semibold mb-2">Invoice processing library</div>
                                <div class="d-flex gap-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="invoice_pdf_library" id="invoice_pdf_dompdf" value="dompdf"
                                            {{ $pdfLibrary == 'dompdf' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="invoice_pdf_dompdf">DomPDF</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="invoice_pdf_library" id="invoice_pdf_mpdf" value="mpdf"
                                            {{ $pdfLibrary == 'mpdf' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="invoice_pdf_mpdf">mPDF</label>
                                    </div>
                                </div>
                                @error('invoice_pdf_library')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Sello --}}
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="invoice_seal_enabled" value="1" id="invoice_seal_enabled"
                                        {{ old('invoice_seal_enabled', $settings['invoice_seal_enabled'] ?? '') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="invoice_seal_enabled">Habilitar sello de factura</label>
                                </div>
                            </div>

                            {{-- Prefijo --}}
                            <div class="col-md-6">
                                <label for="invoice_code_prefix" class="form-label">Prefijo del código de factura</label>
                                <input type="text" name="invoice_code_prefix" id="invoice_code_prefix"
                                    class="form-control @error('invoice_code_prefix') is-invalid @enderror"
                                    placeholder="FAC-"
                                    value="{{ old('invoice_code_prefix', $settings['invoice_code_prefix'] ?? '') }}">
                                @error('invoice_code_prefix')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Formato de fecha --}}
                            <div class="col-md-6">
                                <label for="invoice_date_format" class="form-label">Formato de fecha</label>
                                <select name="invoice_date_format" id="invoice_date_format"
                                    class="form-select @error('invoice_date_format') is-invalid @enderror">
                                    @foreach($dateFormats as $value => $label)
                                        <option value="{{ $value }}" {{ old('invoice_date_format', $settings['invoice_date_format'] ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('invoice_date_format')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Desactivar hasta confirmar --}}
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="invoice_disable_until_confirmed" value="1" id="invoice_disable_until_confirmed"
                                        {{ old('invoice_disable_until_confirmed', $settings['invoice_disable_until_confirmed'] ?? '') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="invoice_disable_until_confirmed">Desactivar factura de pedido hasta que se confirme el pedido</label>
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
    $('#invoice_custom_font_enabled').on('change', function () {
        $('#custom-font-block').slideToggle(150);
    });
});
</script>
@endpush

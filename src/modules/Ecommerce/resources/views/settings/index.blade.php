@extends('layouts.theme')

@section('title', 'General')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - General'])
@endsection

@section('content')
    @include('core::components.alerts')

    <form action="{{ route('settings.ecommerce.update') }}" method="POST">
        @csrf
        @method('PATCH')

        <div class="row g-4 align-items-start">
            <div class="col-lg-8">

                <div class="card">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Informacion de la tienda</h6>
                        <div class="form-text mb-0">Esta dirección aparecerá en su factura y se utilizará para calcular el precio de envío.</div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="store_name" class="form-label">Nombre de la tienda</label>
                                <input type="text" name="store_name" id="store_name" maxlength="100"
                                    class="form-control @error('store_name') is-invalid @enderror"
                                    value="{{ old('store_name', $settings['store_name'] ?? '') }}">
                                @error('store_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="store_company" class="form-label">Compañía</label>
                                <input type="text" name="store_company" id="store_company" maxlength="100"
                                    class="form-control @error('store_company') is-invalid @enderror"
                                    value="{{ old('store_company', $settings['store_company'] ?? '') }}">
                                @error('store_company')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="store_phone" class="form-label">Teléfono</label>
                                <input type="text" name="store_phone" id="store_phone" maxlength="30"
                                    class="form-control @error('store_phone') is-invalid @enderror"
                                    value="{{ old('store_phone', $settings['store_phone'] ?? '') }}">
                                @error('store_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="store_email" class="form-label">Correo electrónico</label>
                                <input type="email" name="store_email" id="store_email" maxlength="100"
                                    class="form-control @error('store_email') is-invalid @enderror"
                                    value="{{ old('store_email', $settings['store_email'] ?? '') }}">
                                @error('store_email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="store_notification_email" class="form-label">Correo de notificaciones</label>
                                <input type="email" name="store_notification_email" id="store_notification_email" maxlength="100"
                                    class="form-control @error('store_notification_email') is-invalid @enderror"
                                    value="{{ old('store_notification_email', $settings['store_notification_email'] ?? '') }}">
                                <div class="form-text">Email donde se enviarán las notificaciones de nuevos pedidos (deja vacío para usar el email del administrador)</div>
                                @error('store_notification_email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="store_state" class="form-label">Estado o provincia</label>
                                <input type="text" name="store_state" id="store_state" maxlength="100"
                                    class="form-control @error('store_state') is-invalid @enderror"
                                    value="{{ old('store_state', $settings['store_state'] ?? '') }}">
                                @error('store_state')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="store_city" class="form-label">Ciudad</label>
                                <input type="text" name="store_city" id="store_city" maxlength="100"
                                    class="form-control @error('store_city') is-invalid @enderror"
                                    value="{{ old('store_city', $settings['store_city'] ?? '') }}">
                                @error('store_city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="store_tax_id" class="form-label">Identificación del impuesto</label>
                                <input type="text" name="store_tax_id" id="store_tax_id" maxlength="50"
                                    class="form-control @error('store_tax_id') is-invalid @enderror"
                                    value="{{ old('store_tax_id', $settings['store_tax_id'] ?? '') }}">
                                @error('store_tax_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="store_address" class="form-label">Dirección (Calle, Num. Ext, Num. Int y Colonia)</label>
                                <input type="text" name="store_address" id="store_address" maxlength="255"
                                    class="form-control @error('store_address') is-invalid @enderror"
                                    value="{{ old('store_address', $settings['store_address'] ?? '') }}">
                                @error('store_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
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

        <div class="row g-4 align-items-start mt-0">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">WhatsApp / Soporte</h6>
                        <div class="form-text mb-0">Boton flotante de WhatsApp visible en la tienda. Deja el numero vacio para ocultarlo.</div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="whatsapp_number" class="form-label">Numero de WhatsApp</label>
                                <input type="text" name="whatsapp_number" id="whatsapp_number"
                                    class="form-control @error('whatsapp_number') is-invalid @enderror"
                                    value="{{ old('whatsapp_number', $settings['whatsapp_number'] ?? '') }}"
                                    placeholder="573001234567 — solo digitos, con codigo de pais">
                                <div class="form-text">Incluye el codigo de pais sin +. Ej: 573001234567</div>
                                @error('whatsapp_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="whatsapp_message" class="form-label">Mensaje pre-rellenado</label>
                                <input type="text" name="whatsapp_message" id="whatsapp_message"
                                    class="form-control @error('whatsapp_message') is-invalid @enderror"
                                    value="{{ old('whatsapp_message', $settings['whatsapp_message'] ?? 'Hola, tengo una consulta sobre') }}"
                                    placeholder="Hola, tengo una consulta sobre"
                                    maxlength="255">
                                <div class="form-text">Se adjunta automaticamente la URL de la pagina actual.</div>
                                @error('whatsapp_message')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

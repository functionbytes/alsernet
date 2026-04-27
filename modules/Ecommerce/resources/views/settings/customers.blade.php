@extends('layouts.theme')

@section('title', 'Clientes')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Clientes'])
    @include('core::components.alerts')

    @php
        $registrationEnabled = old('enable_customer_registration', $settings['enable_customer_registration'] ?? '') == '1';
        $phoneFieldEnabled   = old('enabled_phone_field_in_registration_form', $settings['enabled_phone_field_in_registration_form'] ?? '') == '1';
        $loginOption         = old('login_option', $settings['login_option'] ?? 'email');
    @endphp

    <form action="{{ route('settings.ecommerce.customers.update') }}" method="POST">
        @csrf
        @method('PATCH')

        <div class="mb-3">
            <h5 class="fw-bold mb-1">Configuración del cliente</h5>
            <div class="text-muted small">Configurar los ajustes del cliente</div>
        </div>

        <div class="card mb-4">
            <div class="card-body">

                {{-- Habilitar registro --}}
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="enable_customer_registration" value="1"
                            id="enable_customer_registration"
                            {{ $registrationEnabled ? 'checked' : '' }}>
                        <label class="form-check-label" for="enable_customer_registration">Habilitar el registro de cliente</label>
                    </div>
                    <div class="form-text mt-1">Si está deshabilitada, los clientes no podrán registrar nuevas cuentas. Los clientes existentes podrán iniciar sesión.</div>
                </div>

                {{-- Campos condicionales al registro habilitado --}}
                <div id="registration-fields-block" class="ps-3 border-start" style="{{ $registrationEnabled ? '' : 'display:none' }}">

                    {{-- Verificar correo --}}
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="verify_customer_email" value="1"
                                id="verify_customer_email"
                                {{ old('verify_customer_email', $settings['verify_customer_email'] ?? '') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="verify_customer_email">Verificar el correo electrónico del cliente</label>
                        </div>
                        <div class="form-text mt-1">Cuando esté habilitado, se enviará un enlace de verificación al correo electrónico del cliente; los clientes deben hacer clic en este enlace para verificar su correo electrónico antes de poder iniciar sesión.</div>
                    </div>

                    {{-- Campo de teléfono en registro --}}
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="enabled_phone_field_in_registration_form" value="1"
                                id="enabled_phone_field_in_registration_form"
                                {{ $phoneFieldEnabled ? 'checked' : '' }}>
                            <label class="form-check-label" for="enabled_phone_field_in_registration_form">Habilitar el campo de teléfono en el formulario de registro</label>
                        </div>
                        <div class="form-text mt-1">Cuando esté habilitado, se agregará el campo de teléfono al formulario de registro.</div>
                    </div>

                    {{-- Teléfono obligatorio (nested) --}}
                    <div id="phone-required-block" class="ps-3 border-start" style="{{ $phoneFieldEnabled ? '' : 'display:none' }}">
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="make_customer_phone_number_required" value="1"
                                    id="make_customer_phone_number_required"
                                    {{ old('make_customer_phone_number_required', $settings['make_customer_phone_number_required'] ?? '') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="make_customer_phone_number_required">Hacer que el número de teléfono del cliente sea obligatorio</label>
                            </div>
                            <div class="form-text mt-1">Cuando está habilitado, los clientes deben ingresar su número de teléfono durante el registro. Siempre es obligatorio para quienes inician sesión con un número de teléfono.</div>
                        </div>
                    </div>

                </div>

                <hr class="my-3">

                {{-- Opción de inicio de sesión --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Opción de inicio de sesión</label>
                    <div class="d-flex flex-wrap gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="login_option" id="login_option_email" value="email"
                                {{ $loginOption === 'email' ? 'checked' : '' }}>
                            <label class="form-check-label" for="login_option_email">Iniciar sesión con correo electrónico</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="login_option" id="login_option_phone" value="phone"
                                {{ $loginOption === 'phone' ? 'checked' : '' }}>
                            <label class="form-check-label" for="login_option_phone">Iniciar sesión con teléfono</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="login_option" id="login_option_email_or_phone" value="email_or_phone"
                                {{ $loginOption === 'email_or_phone' ? 'checked' : '' }}>
                            <label class="form-check-label" for="login_option_email_or_phone">Iniciar sesión con correo electrónico o teléfono</label>
                        </div>
                    </div>
                </div>

                {{-- Mantener campo email (visible solo cuando login_option = phone) --}}
                <div id="keep-email-block" class="ps-3 border-start mb-3" style="{{ $loginOption === 'phone' ? '' : 'display:none' }}">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="keep_email_field_in_registration_form" value="1"
                            id="keep_email_field_in_registration_form"
                            {{ old('keep_email_field_in_registration_form', $settings['keep_email_field_in_registration_form'] ?? '') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label" for="keep_email_field_in_registration_form">Mantener el campo de correo electrónico en el formulario de registro</label>
                    </div>
                    <div class="form-text mt-1">Cuando está deshabilitado, el campo de correo electrónico se ocultará en el formulario de registro cuando la opción de inicio de sesión sea "Iniciar sesión con teléfono".</div>
                </div>

                <hr class="my-3">

                {{-- Fecha de nacimiento --}}
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="enabled_customer_dob_field" value="1"
                            id="enabled_customer_dob_field"
                            {{ old('enabled_customer_dob_field', $settings['enabled_customer_dob_field'] ?? '') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label" for="enabled_customer_dob_field">Habilitar el campo de fecha de nacimiento del cliente</label>
                    </div>
                    <div class="form-text mt-1">Cuando está habilitado, los clientes pueden ingresar su fecha de nacimiento en el panel del cliente → Configuración de la cuenta.</div>
                </div>

                {{-- Eliminación de cuenta --}}
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="enabled_customer_account_deletion" value="1"
                            id="enabled_customer_account_deletion"
                            {{ old('enabled_customer_account_deletion', $settings['enabled_customer_account_deletion'] ?? '') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label" for="enabled_customer_account_deletion">Habilitar la eliminación de la cuenta del cliente</label>
                    </div>
                    <div class="form-text mt-1">Cuando está habilitado, los clientes pueden eliminar su cuenta en el panel del cliente → Configuración de la cuenta.</div>
                </div>

                <hr class="my-3">

                {{-- Avatar predeterminado --}}
                <div class="mb-0">
                    <label for="customer_default_avatar" class="form-label fw-semibold">Avatar predeterminado</label>
                    <input type="text" name="customer_default_avatar" id="customer_default_avatar"
                        class="form-control @error('customer_default_avatar') is-invalid @enderror"
                        placeholder="URL de imagen"
                        value="{{ old('customer_default_avatar', $settings['customer_default_avatar'] ?? '') }}">
                    @error('customer_default_avatar')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text mt-1">Avatar predeterminado para el cliente cuando no tiene avatar. Si no selecciona ninguna imagen, se generará con su logotipo o la primera letra del nombre del cliente.</div>
                </div>

            </div>
        </div>

        <div class="mb-4">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Guardar ajustes
            </button>
        </div>

    </form>
@endsection

@push('scripts')
<script>
$(function () {
    $('#enable_customer_registration').on('change', function () {
        $('#registration-fields-block').slideToggle(150);
    });

    $('#enabled_phone_field_in_registration_form').on('change', function () {
        $('#phone-required-block').slideToggle(150);
    });

    $('input[name="login_option"]').on('change', function () {
        if ($(this).val() === 'phone') {
            $('#keep-email-block').slideDown(150);
        } else {
            $('#keep-email-block').slideUp(150);
        }
    });
});
</script>
@endpush

@extends('layouts.theme')

@section('page_title', 'Configuracion del newsletter')

@section('content')

    @include('core::components.card', ['title' => 'Configuracion del newsletter'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="row">
            <div class="col-lg-8">
                <form method="POST" action="{{ route('settings.newsletter.update') }}">
                    @csrf
                    @method('PATCH')

                    {{-- Card 1: Notificaciones --}}
                    <div class="card">
                        <div class="card-header p-3 bg-white border-bottom">
                            <h5 class="mb-0 fw-bold">Notificaciones</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="email_notifications"
                                       id="email_notifications" value="1"
                                       {{ old('email_notifications', $settings['email_notifications'] ?? '') === '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="email_notifications">
                                    Habilitar notificaciones por email
                                </label>
                            </div>
                            <p class="form-text mb-0">Se enviará un email al administrador cuando alguien se suscriba.</p>
                        </div>
                    </div>

                    {{-- Card 2: Popup del boletin --}}
                    <div class="card mt-3">
                        <div class="card-header p-3 bg-white border-bottom">
                            <h5 class="mb-0 fw-bold">Popup del boletin</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="popup_enabled"
                                           id="popup_enabled" value="1"
                                           {{ old('popup_enabled', $settings['popup_enabled'] ?? '') === '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="popup_enabled">
                                        Habilitar popup del boletin
                                    </label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="popup_title" class="form-label fw-semibold">Título del popup</label>
                                <input type="text" class="form-control @error('popup_title') is-invalid @enderror"
                                       id="popup_title" name="popup_title"
                                       value="{{ old('popup_title', $settings['popup_title'] ?? '') }}">
                                @error('popup_title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="popup_subtitle" class="form-label fw-semibold">Subtítulo del popup</label>
                                <input type="text" class="form-control @error('popup_subtitle') is-invalid @enderror"
                                       id="popup_subtitle" name="popup_subtitle"
                                       value="{{ old('popup_subtitle', $settings['popup_subtitle'] ?? '') }}">
                                @error('popup_subtitle')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="popup_description" class="form-label fw-semibold">Descripción del popup</label>
                                <textarea class="form-control @error('popup_description') is-invalid @enderror"
                                          id="popup_description" name="popup_description"
                                          rows="3">{{ old('popup_description', $settings['popup_description'] ?? '') }}</textarea>
                                @error('popup_description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-0">
                                <label for="popup_delay" class="form-label fw-semibold">Retraso del popup (segundos)</label>
                                <input type="number" class="form-control @error('popup_delay') is-invalid @enderror"
                                       id="popup_delay" name="popup_delay" min="0" max="60"
                                       value="{{ old('popup_delay', $settings['popup_delay'] ?? '0') }}">
                                <div class="form-text">0 = inmediato</div>
                                @error('popup_delay')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary w-100">Guardar</button>
                        </div>
                    </div>

                    {{-- Card 3: Integracion Mailjet --}}
                    <div class="card mt-3">
                        <div class="card-header p-3 bg-white border-bottom">
                            <h5 class="mb-0 fw-bold">Integracion Mailjet</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="mailjet_enabled"
                                           id="mailjet_enabled" value="1"
                                           {{ old('mailjet_enabled', $settings['mailjet_enabled'] ?? '') === '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="mailjet_enabled">
                                        Habilitar integracion con Mailjet
                                    </label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="mailjet_api_key" class="form-label fw-semibold">Clave API de Mailjet</label>
                                <input type="text" class="form-control @error('mailjet_api_key') is-invalid @enderror"
                                       id="mailjet_api_key" name="mailjet_api_key"
                                       value="{{ old('mailjet_api_key', $settings['mailjet_api_key'] ?? '') }}">
                                @error('mailjet_api_key')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="mailjet_api_secret" class="form-label fw-semibold">API Secret de Mailjet</label>
                                <input type="password" class="form-control @error('mailjet_api_secret') is-invalid @enderror"
                                       id="mailjet_api_secret" name="mailjet_api_secret"
                                       value="{{ old('mailjet_api_secret', $settings['mailjet_api_secret'] ?? '') }}">
                                @error('mailjet_api_secret')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="mailjet_list_id" class="form-label fw-semibold">ID de lista de Mailjet</label>
                                <input type="text" class="form-control @error('mailjet_list_id') is-invalid @enderror"
                                       id="mailjet_list_id" name="mailjet_list_id"
                                       value="{{ old('mailjet_list_id', $settings['mailjet_list_id'] ?? '') }}">
                                @error('mailjet_list_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="alert alert-info py-2 mb-0">
                                <small>Las credenciales se almacenan en la base de datos. Asegurate de usar variables de entorno en produccion.</small>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary w-100">Guardar</button>
                        </div>
                    </div>

                </form>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

});
</script>
@endpush

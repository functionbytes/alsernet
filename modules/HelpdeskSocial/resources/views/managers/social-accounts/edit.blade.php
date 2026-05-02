@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Editar cuenta social</h5>
        </div>
        <div class="card-body">
            <form action="/api/helpdesk/social/accounts/{{ $account->id }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="name" class="form-label">Nombre</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $account->name) }}" required>
                </div>

                <div class="mb-3">
                    <label for="platform" class="form-label">Plataforma</label>
                    <select name="platform" id="platform" class="form-select" required>
                        <option value="" disabled>Seleccionar plataforma</option>
                        <option value="facebook" @selected(old('platform', $account->platform) == 'facebook')>Facebook</option>
                        <option value="instagram" @selected(old('platform', $account->platform) == 'instagram')>Instagram</option>
                        <option value="whatsapp" @selected(old('platform', $account->platform) == 'whatsapp')>WhatsApp</option>
                        <option value="tiktok" @selected(old('platform', $account->platform) == 'tiktok')>TikTok</option>
                        <option value="x" @selected(old('platform', $account->platform) == 'x')>X</option>
                        <option value="linkedin" @selected(old('platform', $account->platform) == 'linkedin')>LinkedIn</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="account_type" class="form-label">Tipo de cuenta</label>
                    <select name="account_type" id="account_type" class="form-select" required>
                        <option value="" disabled>Seleccionar tipo</option>
                        <option value="page" @selected(old('account_type', $account->account_type) == 'page')>Página</option>
                        <option value="profile" @selected(old('account_type', $account->account_type) == 'profile')>Perfil</option>
                        <option value="business" @selected(old('account_type', $account->account_type) == 'business')>Negocio</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="external_id" class="form-label">ID externo</label>
                    <input type="text" name="external_id" id="external_id" class="form-control" value="{{ old('external_id', $account->external_id) }}">
                </div>

                <div class="mb-3">
                    <label for="username" class="form-label">Usuario</label>
                    <input type="text" name="username" id="username" class="form-control" value="{{ old('username', $account->username) }}">
                </div>

                <div class="mb-3">
                    <label for="profile_url" class="form-label">URL del perfil</label>
                    <input type="url" name="profile_url" id="profile_url" class="form-control" value="{{ old('profile_url', $account->profile_url) }}">
                </div>

                <div class="mb-3">
                    <label for="page_access_token" class="form-label">Token de acceso de página</label>
                    <input type="text" name="page_access_token" id="page_access_token" class="form-control" value="{{ old('page_access_token', $account->page_access_token) }}">
                </div>

                <div class="mb-3">
                    <label for="user_access_token" class="form-label">Token de acceso de usuario</label>
                    <input type="text" name="user_access_token" id="user_access_token" class="form-control" value="{{ old('user_access_token', $account->user_access_token) }}">
                </div>

                <div class="mb-3">
                    <label for="token_expires_at" class="form-label">Expiración del token</label>
                    <input type="date" name="token_expires_at" id="token_expires_at" class="form-control" value="{{ old('token_expires_at', optional($account->token_expires_at)->format('Y-m-d')) }}">
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="comments_enabled" id="comments_enabled" value="1" class="form-check-input" @checked(old('comments_enabled', $account->comments_enabled))>
                        <label for="comments_enabled" class="form-check-label">Comentarios habilitados</label>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="messages_enabled" id="messages_enabled" value="1" class="form-check-input" @checked(old('messages_enabled', $account->messages_enabled))>
                        <label for="messages_enabled" class="form-check-label">Mensajes habilitados</label>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="auto_reply_enabled" id="auto_reply_enabled" value="1" class="form-check-input" @checked(old('auto_reply_enabled', $account->auto_reply_enabled))>
                        <label for="auto_reply_enabled" class="form-check-label">Respuesta automática habilitada</label>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Guardar
                    </button>
                    <a href="{{ route('helpdesksocial.accounts.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

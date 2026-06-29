<div class="row g-4">

    {{-- Left sidebar: avatar + role info --}}
    <div class="col-12 col-lg-3">

        {{-- Avatar card --}}
        <div class="card border text-center p-4">
            <div class="mb-3">
                @if($user->user_img)
                    <img src="{{ asset('storage/' . $user->user_img) }}"
                         alt="Foto de perfil"
                         class="rounded-circle"
                         style="width:96px;height:96px;object-fit:cover;">
                @else
                    @php $initial = strtoupper(substr($user->firstname ?? 'U', 0, 1)); @endphp
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center text-white fw-bold fs-2"
                         style="width:96px;height:96px;background-color:#90bb13;">
                        {{ $initial }}
                    </div>
                @endif
            </div>

            <h6 class="fw-bold mb-0">{{ $user->firstname }} {{ $user->lastname }}</h6>
            <p class="text-muted mb-3">{{ $user->email }}</p>

            @php
                $roleName = $user->getRoleNames()->first() ?? 'Sin rol';
                $badgeClass = match($roleName) {
                    'super-settings' => 'bg-light-danger text-danger',
                    'manager'        => 'bg-light-success text-success',
                    'customer'       => 'bg-light-info text-info',
                    default          => 'bg-light-secondary text-dark',
                };
            @endphp
            <span class="badge {{ $badgeClass }}">
                {{ Str::title(str_replace('-', ' ', $roleName)) }}
            </span>

            <hr>

            {{-- Upload avatar --}}
            <form method="POST"
                  action="{{ route('settings.auth.profile.update-avatar') }}"
                  enctype="multipart/form-data"
                  id="formAvatar">
                @csrf
                <input type="file"
                       id="avatarInput"
                       name="avatar"
                       accept="image/jpg,image/jpeg,image/png,image/webp"
                       class="d-none">
                <button type="button"
                        class="btn btn-sm btn-outline-secondary w-100 mb-2"
                        onclick="document.getElementById('avatarInput').click()">
                    <i class="fas fa-camera me-1"></i>Cambiar foto
                </button>
            </form>

            @if($user->user_img)
                <form method="POST"
                      action="{{ route('settings.auth.profile.delete-avatar') }}"
                      class="needs-confirm" data-confirm-msg="¿Eliminar la foto de perfil?">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-link text-danger p-0">
                        <i class="fas fa-trash-alt me-1"></i>Eliminar foto
                    </button>
                </form>
            @endif
        </div>

        {{-- Account info card --}}
        <div class="card border mt-3">
            <div class="card-body">
                <h6 class="fw-bold mb-3 small text-muted text-uppercase">Información de la cuenta</h6>

                <div class="mb-2">
                    <small class="text-muted d-block">Miembro desde</small>
                    <span class="small">{{ $user->created_at->format('d/m/Y') }}</span>
                </div>

                @if($user->last_login_at)
                    <div class="mb-2">
                        <small class="text-muted d-block">Último acceso</small>
                        <span class="small">{{ $user->last_login_at->format('d/m/Y H:i') }}</span>
                    </div>
                @endif

                <div>
                    <small class="text-muted d-block">Estado</small>
                    <span class="badge bg-{{ $user->available ? 'success' : 'danger' }}">
                        {{ $user->available ? 'Activo' : 'Inactivo' }}
                    </span>
                </div>
            </div>
        </div>

    </div>

    {{-- Right column: edit form --}}
    <div class="col-12 col-lg-9">

        @if(session('success'))
            <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
                <i class="fas fa-check-circle"></i>
                {{ session('success') }}
            </div>
        @endif

        <div class="card border">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-1">Información personal</h6>
                <p class="text-muted mb-4">Actualiza tus datos de contacto y preferencias</p>

                <form method="POST" action="{{ route('settings.auth.profile.update-info') }}">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text"
                                   name="firstname"
                                   class="form-control @error('firstname') is-invalid @enderror"
                                   value="{{ old('firstname', $user->firstname) }}"
                                   placeholder="Ej: Juan"
                                   required>
                            @error('firstname')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label">Apellido <span class="text-danger">*</span></label>
                            <input type="text"
                                   name="lastname"
                                   class="form-control @error('lastname') is-invalid @enderror"
                                   value="{{ old('lastname', $user->lastname) }}"
                                   placeholder="Ej: Pérez"
                                   required>
                            @error('lastname')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label">Correo electrónico <span class="text-danger">*</span></label>
                            <input type="email"
                                   name="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $user->email) }}"
                                   placeholder="correo@ejemplo.com"
                                   required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="text"
                                   name="cellphone"
                                   class="form-control @error('cellphone') is-invalid @enderror"
                                   value="{{ old('cellphone', $user->cellphone) }}"
                                   placeholder="Ej: +34 600 123 456">
                            @error('cellphone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6 mb-4">
                            <label class="form-label">Idioma</label>
                            <select name="locale"
                                    class="form-select @error('locale') is-invalid @enderror">
                                <option value="">-- Seleccionar --</option>
                                <option value="es" {{ old('locale', $user->locale) === 'es' ? 'selected' : '' }}>Español</option>
                                <option value="en" {{ old('locale', $user->locale) === 'en' ? 'selected' : '' }}>English</option>
                            </select>
                            @error('locale')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <button type="submit"
                            class="btn px-4"
                            style="background-color:#90bb13;color:#fff;">
                        <i class="fas fa-save me-2"></i>Guardar cambios
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
$(document).ready(function () {
    // Auto-submit avatar form when a file is selected
    $('#avatarInput').on('change', function () {
        if (this.files && this.files[0]) {
            $('#formAvatar').submit();
        }
    });
});
</script>
@endpush

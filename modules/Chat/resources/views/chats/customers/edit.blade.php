@extends('layouts.theme')

@section('title', 'Editar Cliente - Groups')

@section('content')

    @include('core::components.card', ['title' => 'Editar Cliente'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <form method="POST" action="{{ route('chat.customers.update', $customer) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <!-- Main Card -->
            <div class="card">
                <!-- Header Section -->
                <div class="card-header p-4 border-bottom border-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">Editar Cliente: {{ $customer->name }}</h5>
                            <p class="small mb-0 text-muted">Modifica los datos del cliente en el sistema de chat</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('chat.customers.show', $customer) }}" class="btn btn-light">
                                <i class="fa fa-times me-1"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-check me-1"></i> Guardar Cambios
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Status Cards -->
                <div class="card-body border-bottom">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="card bg-light-secondary stat-card h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div>
                                            <h6 class="card-title text-{{ $customer->is_banned ? 'danger' : 'success' }} mb-2">
                                                <i class="fa fa-{{ $customer->is_banned ? 'ban' : 'check-circle' }} me-1"></i>
                                                Estado
                                            </h6>
                                            <h4 class="mb-1 fw-bold">{{ $customer->is_banned ? 'Suspendido' : 'Activo' }}</h4>
                                            <small class="text-muted">{{ $customer->is_banned && $customer->banned_at ? $customer->banned_at->format('d/m/Y') : 'Cliente activo' }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light-secondary stat-card h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div>
                                            <h6 class="card-title text-{{ $customer->email_verified_at ? 'success' : 'warning' }} mb-2">
                                                <i class="fa fa-envelope me-1"></i>
                                                Verificación
                                            </h6>
                                            <h4 class="mb-1 fw-bold">{{ $customer->email_verified_at ? 'Verificado' : 'Pendiente' }}</h4>
                                            <small class="text-muted">{{ $customer->email_verified_at ? $customer->email_verified_at->format('d/m/Y') : 'Email no confirmado' }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light-secondary stat-card h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div>
                                            <h6 class="card-title text-info mb-2">
                                                <i class="fa fa-comments me-1"></i>
                                                Conversaciones
                                            </h6>
                                            <h4 class="mb-1 fw-bold">{{ $customer->total_conversations ?? 0 }}</h4>
                                            <small class="text-muted">Total de chats</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light-secondary stat-card h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div>
                                            <h6 class="card-title text-primary mb-2">
                                                <i class="fa fa-eye me-1"></i>
                                                Páginas
                                            </h6>
                                            <h4 class="mb-1 fw-bold">{{ $customer->total_page_visits ?? 0 }}</h4>
                                            <small class="text-muted">Visitadas</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Personal Information -->
                <div class="card-body border-bottom">
                    <div class="mb-3">
                        <h6 class="mb-1 fw-bold">Información Personal</h6>
                        <p class="text-muted small mb-0">Datos básicos del cliente</p>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label fw-semibold">
                                Nombre <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   id="name"
                                   name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   placeholder="Ej: Juan"
                                   value="{{ old('name', $customer->name) }}"
                                   required
                                   autofocus>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="last_name" class="form-label fw-semibold">
                                Apellido
                            </label>
                            <input type="text"
                                   id="last_name"
                                   name="last_name"
                                   class="form-control @error('last_name') is-invalid @enderror"
                                   placeholder="Ej: Pérez García"
                                   value="{{ old('last_name', $customer->last_name) }}">
                            @error('last_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label fw-semibold">
                                Correo Electrónico <span class="text-danger">*</span>
                            </label>
                            <input type="email"
                                   id="email"
                                   name="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   placeholder="correo@ejemplo.com"
                                   value="{{ old('email', $customer->email) }}"
                                   required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">
                                @if($customer->email_verified_at)
                                    Verificado el {{ $customer->email_verified_at->format('d/m/Y H:i') }}
                                @else
                                    Email no verificado
                                @endif
                            </small>
                        </div>

                        <div class="col-md-6">
                            <label for="phone_number" class="form-label fw-semibold">
                                Teléfono
                            </label>
                            <input type="tel"
                                   id="phone_number"
                                   name="phone_number"
                                   class="form-control @error('phone_number') is-invalid @enderror"
                                   placeholder="+34 600 123 456"
                                   value="{{ old('phone_number', $customer->phone_number) }}">
                            @error('phone_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Formato internacional recomendado: +34...</small>
                        </div>

                        <div class="col-md-6">
                            <label for="language" class="form-label fw-semibold">
                                Idioma Preferido
                            </label>
                            <select id="language" name="language" class="form-control select2 select2 @error('language') is-invalid @enderror">
                                <option value="">— Seleccionar idioma —</option>
                                <option value="es" {{ old('language', $customer->language) === 'es' ? 'selected' : '' }}>🇪🇸 Español</option>
                                <option value="en" {{ old('language', $customer->language) === 'en' ? 'selected' : '' }}>🇬🇧 English</option>
                                <option value="fr" {{ old('language', $customer->language) === 'fr' ? 'selected' : '' }}>🇫🇷 Français</option>
                                <option value="pt" {{ old('language', $customer->language) === 'pt' ? 'selected' : '' }}>🇵🇹 Português</option>
                                <option value="de" {{ old('language', $customer->language) === 'de' ? 'selected' : '' }}>🇩🇪 Deutsch</option>
                                <option value="it" {{ old('language', $customer->language) === 'it' ? 'selected' : '' }}>🇮🇹 Italiano</option>
                            </select>
                            @error('language')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Location Information -->
                <div class="card-body border-bottom">
                    <div class="mb-3">
                        <h6 class="mb-1 fw-bold">Ubicación</h6>
                        <p class="text-muted small mb-0">Información geográfica del cliente</p>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="country" class="form-label fw-semibold">
                                País
                            </label>
                            <select id="country" name="country" class="form-control select2 select2 @error('country') is-invalid @enderror">
                                <option value="">— Seleccionar país —</option>
                                <optgroup label="Europa">
                                    <option value="ES" {{ old('country', $customer->country) === 'ES' ? 'selected' : '' }}>🇪🇸 España</option>
                                    <option value="FR" {{ old('country', $customer->country) === 'FR' ? 'selected' : '' }}>🇫🇷 Francia</option>
                                    <option value="DE" {{ old('country', $customer->country) === 'DE' ? 'selected' : '' }}>🇩🇪 Alemania</option>
                                    <option value="IT" {{ old('country', $customer->country) === 'IT' ? 'selected' : '' }}>🇮🇹 Italia</option>
                                    <option value="PT" {{ old('country', $customer->country) === 'PT' ? 'selected' : '' }}>🇵🇹 Portugal</option>
                                    <option value="GB" {{ old('country', $customer->country) === 'GB' ? 'selected' : '' }}>🇬🇧 Reino Unido</option>
                                </optgroup>
                                <optgroup label="América Latina">
                                    <option value="CO" {{ old('country', $customer->country) === 'CO' ? 'selected' : '' }}>🇨🇴 Colombia</option>
                                    <option value="MX" {{ old('country', $customer->country) === 'MX' ? 'selected' : '' }}>🇲🇽 México</option>
                                    <option value="AR" {{ old('country', $customer->country) === 'AR' ? 'selected' : '' }}>🇦🇷 Argentina</option>
                                    <option value="CL" {{ old('country', $customer->country) === 'CL' ? 'selected' : '' }}>🇨🇱 Chile</option>
                                    <option value="PE" {{ old('country', $customer->country) === 'PE' ? 'selected' : '' }}>🇵🇪 Perú</option>
                                    <option value="VE" {{ old('country', $customer->country) === 'VE' ? 'selected' : '' }}>🇻🇪 Venezuela</option>
                                    <option value="EC" {{ old('country', $customer->country) === 'EC' ? 'selected' : '' }}>🇪🇨 Ecuador</option>
                                </optgroup>
                                <optgroup label="América del Norte">
                                    <option value="US" {{ old('country', $customer->country) === 'US' ? 'selected' : '' }}>🇺🇸 Estados Unidos</option>
                                    <option value="CA" {{ old('country', $customer->country) === 'CA' ? 'selected' : '' }}>🇨🇦 Canadá</option>
                                </optgroup>
                                <optgroup label="Otros">
                                    <option value="BR" {{ old('country', $customer->country) === 'BR' ? 'selected' : '' }}>🇧🇷 Brasil</option>
                                </optgroup>
                            </select>
                            @error('country')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="state" class="form-label fw-semibold">
                                Estado/Región
                            </label>
                            <input type="text"
                                   id="state"
                                   name="state"
                                   class="form-control @error('state') is-invalid @enderror"
                                   placeholder="Madrid"
                                   value="{{ old('state', $customer->state) }}">
                            @error('state')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="city" class="form-label fw-semibold">
                                Ciudad
                            </label>
                            <input type="text"
                                   id="city"
                                   name="city"
                                   class="form-control @error('city') is-invalid @enderror"
                                   placeholder="Madrid"
                                   value="{{ old('city', $customer->city) }}">
                            @error('city')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Timezone -->
                <div class="card-body border-bottom">
                    <div class="mb-3">
                        <h6 class="mb-1 fw-bold">Configuración Regional</h6>
                        <p class="text-muted small mb-0">Zona horaria para fechas y notificaciones</p>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="timezone" class="form-label fw-semibold">
                                Zona Horaria
                            </label>
                            <select id="timezone" name="timezone" class="form-control select2 select2 @error('timezone') is-invalid @enderror">
                                <option value="">— Detección automática —</option>
                                <optgroup label="Europa">
                                    <option value="Europe/Madrid" {{ old('timezone', $customer->timezone) === 'Europe/Madrid' ? 'selected' : '' }}>
                                        🇪🇸 Madrid (UTC+1/+2)
                                    </option>
                                    <option value="Europe/London" {{ old('timezone', $customer->timezone) === 'Europe/London' ? 'selected' : '' }}>
                                        🇬🇧 London (UTC+0/+1)
                                    </option>
                                    <option value="Europe/Paris" {{ old('timezone', $customer->timezone) === 'Europe/Paris' ? 'selected' : '' }}>
                                        🇫🇷 Paris (UTC+1/+2)
                                    </option>
                                    <option value="Europe/Berlin" {{ old('timezone', $customer->timezone) === 'Europe/Berlin' ? 'selected' : '' }}>
                                        🇩🇪 Berlin (UTC+1/+2)
                                    </option>
                                </optgroup>
                                <optgroup label="América">
                                    <option value="America/New_York" {{ old('timezone', $customer->timezone) === 'America/New_York' ? 'selected' : '' }}>
                                        🇺🇸 New York (UTC-5/-4)
                                    </option>
                                    <option value="America/Los_Angeles" {{ old('timezone', $customer->timezone) === 'America/Los_Angeles' ? 'selected' : '' }}>
                                        🇺🇸 Los Angeles (UTC-8/-7)
                                    </option>
                                    <option value="America/Mexico_City" {{ old('timezone', $customer->timezone) === 'America/Mexico_City' ? 'selected' : '' }}>
                                        🇲🇽 Ciudad de México (UTC-6)
                                    </option>
                                    <option value="America/Buenos_Aires" {{ old('timezone', $customer->timezone) === 'America/Buenos_Aires' ? 'selected' : '' }}>
                                        🇦🇷 Buenos Aires (UTC-3)
                                    </option>
                                </optgroup>
                                <optgroup label="Asia">
                                    <option value="Asia/Tokyo" {{ old('timezone', $customer->timezone) === 'Asia/Tokyo' ? 'selected' : '' }}>
                                        🇯🇵 Tokyo (UTC+9)
                                    </option>
                                    <option value="Asia/Shanghai" {{ old('timezone', $customer->timezone) === 'Asia/Shanghai' ? 'selected' : '' }}>
                                        🇨🇳 Shanghai (UTC+8)
                                    </option>
                                    <option value="Asia/Dubai" {{ old('timezone', $customer->timezone) === 'Asia/Dubai' ? 'selected' : '' }}>
                                        🇦🇪 Dubai (UTC+4)
                                    </option>
                                </optgroup>
                            </select>
                            @error('timezone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Avatar -->
                <div class="card-body border-bottom">
                    <div class="mb-3">
                        <h6 class="mb-1 fw-bold">Foto de perfil</h6>
                        <p class="text-muted small mb-0">Imagen del cliente (opcional)</p>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center border"
                                     style="width: 80px; height: 80px; background-color: #f5f6f8; color: #081A28; font-weight: 600; font-size: 1.8rem;">
                                    @if($customer->avatar_url)
                                        <img src="{{ $customer->avatar_url }}"
                                             alt="{{ $customer->name }}"
                                             class="rounded-circle"
                                             style="width: 100%; height: 100%; object-fit: cover;">
                                    @else
                                        {{ strtoupper(substr($customer->name, 0, 2)) }}
                                    @endif
                                </div>
                                <div>
                                    <input type="file"
                                           id="avatar"
                                           name="avatar"
                                           class="form-control @error('avatar') is-invalid @enderror"
                                           accept="image/jpeg,image/png,image/jpg,image/gif">
                                    @error('avatar')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted d-block mt-1">JPG, PNG o GIF. Máximo 2MB.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Custom Attributes -->
                @if($customAttributes && $customAttributes->count() > 0)
                <div class="card-body border-bottom">
                    <div class="mb-3">
                        <h6 class="mb-1 fw-bold">Atributos personalizados</h6>
                        <p class="text-muted small mb-0">Campos adicionales configurados para clientes</p>
                    </div>

                    <div class="row g-3">
                        @foreach($customAttributes as $attribute)
                            <div class="col-md-6">
                                <label for="custom_attr_{{ $attribute->id }}" class="form-label fw-semibold">
                                    {{ $attribute->attribute_display_name }}
                                </label>

                                @if($attribute->attribute_display_type === 'text')
                                    <input type="text"
                                           id="custom_attr_{{ $attribute->id }}"
                                           name="custom_attributes[{{ $attribute->attribute_key }}]"
                                           class="form-control"
                                           value="{{ old('custom_attributes.'.$attribute->attribute_key, $customer->additional_attributes[$attribute->attribute_key] ?? '') }}">

                                @elseif($attribute->attribute_display_type === 'number')
                                    <input type="number"
                                           id="custom_attr_{{ $attribute->id }}"
                                           name="custom_attributes[{{ $attribute->attribute_key }}]"
                                           class="form-control"
                                           value="{{ old('custom_attributes.'.$attribute->attribute_key, $customer->additional_attributes[$attribute->attribute_key] ?? '') }}">

                                @elseif($attribute->attribute_display_type === 'checkbox')
                                    <div class="form-check form-switch">
                                        <input type="checkbox"
                                               id="custom_attr_{{ $attribute->id }}"
                                               name="custom_attributes[{{ $attribute->attribute_key }}]"
                                               class="form-check-input"
                                               value="1"
                                               {{ old('custom_attributes.'.$attribute->attribute_key, $customer->additional_attributes[$attribute->attribute_key] ?? false) ? 'checked' : '' }}>
                                    </div>

                                @elseif($attribute->attribute_display_type === 'list' && $attribute->attribute_values)
                                    <select id="custom_attr_{{ $attribute->id }}"
                                            name="custom_attributes[{{ $attribute->attribute_key }}]"
                                            class="form-control select2">
                                        <option value="">— Seleccionar —</option>
                                        @foreach($attribute->attribute_values as $value)
                                            <option value="{{ $value }}"
                                                {{ old('custom_attributes.'.$attribute->attribute_key, $customer->additional_attributes[$attribute->attribute_key] ?? '') == $value ? 'selected' : '' }}>
                                                {{ $value }}
                                            </option>
                                        @endforeach
                                    </select>

                                @else
                                    <textarea id="custom_attr_{{ $attribute->id }}"
                                              name="custom_attributes[{{ $attribute->attribute_key }}]"
                                              class="form-control"
                                              rows="2">{{ old('custom_attributes.'.$attribute->attribute_key, $customer->additional_attributes[$attribute->attribute_key] ?? '') }}</textarea>
                                @endif

                                @if($attribute->attribute_description)
                                    <small class="text-muted">{{ $attribute->attribute_description }}</small>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Internal Notes -->
                <div class="card-body border-bottom">
                    <div class="mb-3">
                        <h6 class="mb-1 fw-bold">Notas Internas</h6>
                        <p class="text-muted small mb-0">Información privada sobre este cliente</p>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="internal_notes" class="form-label fw-semibold">
                                Notas
                            </label>
                            <textarea id="internal_notes"
                                      name="internal_notes"
                                      class="form-control @error('internal_notes') is-invalid @enderror"
                                      rows="4"
                                      placeholder="Notas privadas sobre este cliente...">{{ old('internal_notes', $customer->internal_notes) }}</textarea>
                            @error('internal_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Metadata Section -->
                <div class="card-body border-bottom">
                    <div class="mb-3">
                        <h6 class="mb-1 fw-bold">Información del sistema</h6>
                        <p class="text-muted small mb-0">Fechas y datos de registro</p>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="card bg-light h-100">
                                <div class="card-body">
                                    <h6 class="mb-2 fw-semibold d-flex align-items-center gap-2">
                                        <i class="fa fa-calendar-plus text-primary"></i> Creado
                                    </h6>
                                    <p class="mb-0">{{ $customer->created_at->format('d/m/Y H:i') }}</p>
                                    <small class="text-muted">{{ $customer->created_at->diffForHumans() }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light h-100">
                                <div class="card-body">
                                    <h6 class="mb-2 fw-semibold d-flex align-items-center gap-2">
                                        <i class="fa fa-calendar-check text-success"></i> Actualizado
                                    </h6>
                                    <p class="mb-0">{{ $customer->updated_at->format('d/m/Y H:i') }}</p>
                                    <small class="text-muted">{{ $customer->updated_at->diffForHumans() }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light h-100">
                                <div class="card-body">
                                    <h6 class="mb-2 fw-semibold d-flex align-items-center gap-2">
                                        <i class="fa fa-clock text-info"></i> Última actividad
                                    </h6>
                                    @if($customer->last_seen_at)
                                        <p class="mb-0">{{ $customer->last_seen_at->format('d/m/Y H:i') }}</p>
                                        <small class="text-muted">{{ $customer->last_seen_at->diffForHumans() }}</small>
                                    @else
                                        <p class="mb-0 text-muted">No registrada</p>
                                        <small class="text-muted">Sin actividad reciente</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if($customer->is_banned && $customer->ban_reason)
                    <!-- Ban Warning -->
                    <div class="card-body">
                        <div class="alert alert-warning border-0 bg-warning-subtle mb-0">
                            <div class="d-flex align-items-start gap-2">
                                <i class="fa fa-exclamation-triangle fs-5"></i>
                                <div>
                                    <small class="fw-semibold">Cliente suspendido:</small>
                                    <p class="mb-0 mt-1 small">{{ $customer->ban_reason }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </form>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        allowClear: true,
        placeholder: function() {
            return $(this).find('option:first').text();
        },
        language: {
            noResults: function() {
                return 'Sin resultados';
            },
            searching: function() {
                return 'Buscando...';
            }
        }
    });

    // Auto-uppercase country code
    $('#country').on('input', function() {
        $(this).val($(this).val().toUpperCase());
    });

    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush

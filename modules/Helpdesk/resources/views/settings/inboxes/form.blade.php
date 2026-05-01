@extends('layouts.theme')

@php
    $isEdit = $inbox->exists;
    $title = $isEdit ? 'Editar bandeja: '.$inbox->name : 'Nueva bandeja';
    $action = $isEdit
        ? route('settings.helpdesk.inboxes.update', $inbox)
        : route('settings.helpdesk.inboxes.store');
@endphp

@section('title', $title)

@section('page_header')
    @include('core::components.card', ['title' => $title])
@endsection

@section('content')

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <form action="{{ $action }}" method="POST">
            @csrf
            @if($isEdit) @method('PUT') @endif

            <div class="row g-3">

                {{-- Columna principal --}}
                <div class="col-lg-8">

                    {{-- Información básica --}}
                    <div class="card mb-3">
                        <div class="card-header p-4 border-bottom border-light">
                            <h6 class="mb-1 fw-bold">
                                <i class="{{ $channelIcons[$inbox->channel_type] ?? 'fas fa-inbox' }} me-2"></i>
                                Información básica
                            </h6>
                            <p class="small mb-0 text-muted">Datos principales de la bandeja</p>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $inbox->name) }}" required maxlength="120"
                                       placeholder="Ej: WhatsApp Soporte General">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Canal <span class="text-danger">*</span></label>
                                    <select name="channel_type" class="form-select @error('channel_type') is-invalid @enderror"
                                            {{ $isEdit ? 'disabled' : '' }} required>
                                        @foreach($channelLabels as $key => $label)
                                            <option value="{{ $key }}" @selected(old('channel_type', $inbox->channel_type) === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @if($isEdit)
                                        <input type="hidden" name="channel_type" value="{{ $inbox->channel_type }}">
                                        <small class="text-muted">El canal no se puede cambiar tras crear la bandeja.</small>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Estado</label>
                                    <select name="is_active" class="form-select">
                                        <option value="1" @selected(old('is_active', $inbox->is_active))>Activa</option>
                                        <option value="0" @selected(! old('is_active', $inbox->is_active))>Inactiva</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-3">
                                <label class="form-label">Descripción</label>
                                <textarea name="description" class="form-control" rows="2"
                                          placeholder="Notas internas sobre esta bandeja">{{ old('description', $inbox->description) }}</textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Credenciales por canal --}}
                    @if(! empty($credentialFields))
                        <div class="card mb-3">
                            <div class="card-header p-4 border-bottom border-light">
                                <h6 class="mb-1 fw-bold"><i class="fas fa-key me-2"></i>Credenciales del canal</h6>
                                <p class="small mb-0 text-muted">Estos datos se almacenan encriptados en la base de datos</p>
                            </div>
                            <div class="card-body p-4">
                                @foreach($credentialFields as $field)
                                    @php
                                        $cur = old('credentials.'.$field['key'], $inbox->getCredential($field['key'], ''));
                                        $isPassword = ($field['type'] ?? 'text') === 'password';
                                    @endphp
                                    <div class="mb-3">
                                        <label class="form-label">
                                            {{ $field['label'] }}
                                            @if(! empty($field['required']))<span class="text-danger">*</span>@endif
                                        </label>
                                        <input type="{{ $isPassword ? 'password' : 'text' }}"
                                               name="credentials[{{ $field['key'] }}]"
                                               class="form-control"
                                               value="{{ $cur }}"
                                               autocomplete="off"
                                               @if(! empty($field['required'])) required @endif>
                                        @if(! empty($field['help']))
                                            <small class="text-muted">{{ $field['help'] }}</small>
                                        @endif
                                    </div>
                                @endforeach

                                {{-- Webhook URL para Meta/Email --}}
                                @if($isEdit && in_array($inbox->channel_type, ['whatsapp', 'facebook', 'instagram']))
                                    <div class="alert alert-light border mt-3 mb-0">
                                        <strong class="d-block mb-1">URL del webhook (pega esto en Meta Business Manager)</strong>
                                        <code class="small">{{ $inbox->webhookUrl() }}</code>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Mensaje de bienvenida + horario --}}
                    <div class="card mb-3">
                        <div class="card-header p-4 border-bottom border-light">
                            <h6 class="mb-1 fw-bold"><i class="far fa-comment-dots me-2"></i>Comportamiento</h6>
                            <p class="small mb-0 text-muted">Saludo automático y mensaje fuera de horario</p>
                        </div>
                        <div class="card-body p-4">
                            <div class="form-check form-switch mb-3">
                                <input type="hidden" name="greeting_enabled" value="0">
                                <input class="form-check-input" type="checkbox" id="greeting_enabled"
                                       name="greeting_enabled" value="1"
                                       @checked(old('greeting_enabled', $inbox->greeting_enabled))>
                                <label class="form-check-label" for="greeting_enabled">Enviar mensaje de bienvenida</label>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mensaje de bienvenida</label>
                                <textarea name="greeting_message" class="form-control" rows="2"
                                          placeholder="Hola, gracias por contactarnos…">{{ old('greeting_message', $inbox->greeting_message) }}</textarea>
                            </div>

                            <div class="form-check form-switch mb-3">
                                <input type="hidden" name="working_hours_enabled" value="0">
                                <input class="form-check-input" type="checkbox" id="working_hours_enabled"
                                       name="working_hours_enabled" value="1"
                                       @checked(old('working_hours_enabled', $inbox->working_hours_enabled))>
                                <label class="form-check-label" for="working_hours_enabled">Respuesta automática fuera de horario</label>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Mensaje fuera de horario</label>
                                <textarea name="out_of_office_message" class="form-control" rows="2"
                                          placeholder="Estamos fuera de horario. Te respondemos en cuanto volvamos.">{{ old('out_of_office_message', $inbox->out_of_office_message) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Columna lateral --}}
                <div class="col-lg-4">

                    <div class="card mb-3">
                        <div class="card-header p-4 border-bottom border-light">
                            <h6 class="mb-1 fw-bold"><i class="far fa-user me-2"></i>Asignación por defecto</h6>
                            <p class="small mb-0 text-muted">Quién recibe las conversaciones nuevas</p>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <label class="form-label">Agente</label>
                                <select name="default_assignee_id" class="form-select">
                                    <option value="">— Sin asignar —</option>
                                    @foreach($agents as $u)
                                        <option value="{{ $u->id }}" @selected(old('default_assignee_id', $inbox->default_assignee_id) == $u->id)>
                                            {{ trim(($u->firstname ?? '').' '.($u->lastname ?? '')) ?: $u->email }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Equipo</label>
                                <select name="default_group_id" class="form-select">
                                    <option value="">— Ninguno —</option>
                                    @foreach($groups as $g)
                                        <option value="{{ $g->id }}" @selected(old('default_group_id', $inbox->default_group_id) == $g->id)>
                                            {{ $g->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header p-4 border-bottom border-light">
                            <h6 class="mb-1 fw-bold"><i class="fas fa-palette me-2"></i>Identidad visual</h6>
                            <p class="small mb-0 text-muted">Cómo se ve esta bandeja en el inbox</p>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <label class="form-label">Color</label>
                                <input type="color" name="color" class="form-control form-control-color"
                                       value="{{ old('color', $inbox->color ?? '#b10100') }}">
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Icono (Font Awesome)</label>
                                <input type="text" name="icon" class="form-control"
                                       value="{{ old('icon', $inbox->icon) }}"
                                       placeholder="ej. fab fa-whatsapp">
                                <small class="text-muted">Si vacío, usa el icono por defecto del canal.</small>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Footer acciones --}}
            <div class="d-flex justify-content-between mt-3">
                <a href="{{ route('settings.helpdesk.inboxes.index') }}" class="btn btn-light">
                    <i class="fas fa-chevron-left me-1"></i> Volver
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check me-1"></i>
                    {{ $isEdit ? 'Guardar cambios' : 'Crear bandeja' }}
                </button>
            </div>
        </form>
    </div>

@endsection

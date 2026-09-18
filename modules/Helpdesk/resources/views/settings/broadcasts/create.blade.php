@extends('layouts.theme')

@section('title', 'Nuevo broadcast')

@section('page_header')
    @include('core::components.card', ['title' => 'Nuevo broadcast'])
@endsection

@section('content')

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('settings.helpdesk.broadcasts.store') }}" method="POST" id="broadcastForm">
                    @csrf

                    {{-- Stepper --}}
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-3 fw-bold">Nuevo broadcast</h5>
                        <div class="d-flex gap-2 flex-wrap" id="stepperNav">
                            @foreach(['1' => 'Segmento', '2' => 'Contenido', '3' => 'Revision', '4' => 'Confirmacion'] as $num => $label)
                                <div class="d-flex align-items-center gap-1 stepper-step @if($num == 1) active @endif" data-step="{{ $num }}">
                                    <span class="badge rounded-pill step-badge fs-6
                                        @if($num == 1) bg-primary @else bg-light text-muted @endif">
                                        {{ $num }}
                                    </span>
                                    <span class="small fw-semibold step-label
                                        @if($num == 1) text-primary @else text-muted @endif">
                                        {{ $label }}
                                    </span>
                                    @if($num < 4)
                                        <span class="text-muted mx-1">›</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        {{-- Paso 1: Segmento --}}
                        <div class="step-content" id="step-1">
                            <h6 class="fw-bold mb-1">Segmento y nombre</h6>
                            <p class="text-muted small mb-3">Define el nombre del broadcast y a qué audiencia va dirigido.</p>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">
                                        Nombre <span class="text-brand">*</span>
                                    </label>
                                    <input type="text" name="name"
                                        class="form-control @error('name') is-invalid @enderror"
                                        value="{{ old('name') }}"
                                        placeholder="Ej: Promocion de verano 2026">
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label class="form-label">
                                        Canal <span class="text-brand">*</span>
                                    </label>
                                    <select name="channel" class="form-select @error('channel') is-invalid @enderror">
                                        <option value="">Selecciona un canal...</option>
                                        <option value="whatsapp" @selected(old('channel') === 'whatsapp')>WhatsApp</option>
                                        <option value="facebook" @selected(old('channel') === 'facebook')>Facebook</option>
                                        <option value="instagram" @selected(old('channel') === 'instagram')>Instagram</option>
                                        <option value="email" @selected(old('channel') === 'email')>Email</option>
                                        <option value="web" @selected(old('channel') === 'web')>Web</option>
                                    </select>
                                    @error('channel')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <h6 class="fw-semibold mb-2">Filtros de audiencia</h6>
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <label class="form-label">Etiqueta</label>
                                            <input type="text" name="filters[tag]"
                                                class="form-control"
                                                value="{{ old('filters.tag') }}"
                                                placeholder="Ej: cliente-vip">
                                            <div class="form-text">Solo contactos con esta etiqueta</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Canal de contacto</label>
                                            <input type="text" name="filters[channel]"
                                                class="form-control"
                                                value="{{ old('filters.channel') }}"
                                                placeholder="Ej: whatsapp-principal">
                                            <div class="form-text">Inbox especifico de origen</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="alert alert-info d-flex align-items-center gap-2 mb-0">
                                        <i class="fas fa-info-circle"></i>
                                        <span class="small">Los destinatarios se calculan al momento del envio basandose en los filtros seleccionados.</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Paso 2: Contenido --}}
                        <div class="step-content d-none" id="step-2">
                            <h6 class="fw-bold mb-1">Contenido del mensaje</h6>
                            <p class="text-muted small mb-3">Define el mensaje que recibirán tus contactos.</p>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">
                                        Tipo de contenido <span class="text-brand">*</span>
                                    </label>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="template_type"
                                                id="type_text" value="text"
                                                @checked(old('template_type', 'text') === 'text')>
                                            <label class="form-check-label" for="type_text">
                                                Texto libre
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="template_type"
                                                id="type_hsm" value="hsm"
                                                @checked(old('template_type') === 'hsm')>
                                            <label class="form-check-label" for="type_hsm">
                                                Template HSM (WhatsApp)
                                            </label>
                                        </div>
                                    </div>
                                    @error('template_type')
                                        <div class="text-dark small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12" id="bodyField">
                                    <label class="form-label">
                                        Mensaje <span class="text-brand">*</span>
                                    </label>
                                    <textarea name="body" id="body" rows="6"
                                        class="form-control @error('body') is-invalid @enderror"
                                        placeholder="Escribe el mensaje que recibiran tus contactos...">{{ old('body') }}</textarea>
                                    <div class="d-flex justify-content-end mt-1">
                                        <small class="text-muted" id="charCount">0 / 4096 caracteres</small>
                                    </div>
                                    @error('body')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 d-none" id="templateField">
                                    <label class="form-label">
                                        ID del template HSM <span class="text-brand">*</span>
                                    </label>
                                    <input type="text" name="template_id"
                                        class="form-control @error('template_id') is-invalid @enderror"
                                        value="{{ old('template_id') }}"
                                        placeholder="Ej: bienvenida_v1">
                                    <div class="form-text">ID del template aprobado en Meta Business</div>
                                    @error('template_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12" id="previewArea">
                                    <label class="form-label">Vista previa</label>
                                    <div class="bg-light rounded p-3 broadcast-preview-box">
                                        <p class="text-muted small mb-0 fst-italic" id="previewText">
                                            El mensaje aparecera aqui mientras escribes...
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Paso 3: Revision --}}
                        <div class="step-content d-none" id="step-3">
                            <h6 class="fw-bold mb-1">Revision y programacion</h6>
                            <p class="text-muted small mb-3">Revisa la configuracion y elige si enviar ahora o programar el broadcast.</p>

                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="card bg-light-subtle border">
                                        <div class="card-body">
                                            <h6 class="fw-semibold mb-3">Resumen de configuracion</h6>
                                            <dl class="row mb-0 small">
                                                <dt class="col-sm-4 text-muted">Nombre</dt>
                                                <dd class="col-sm-8 fw-semibold" id="summary-name">—</dd>
                                                <dt class="col-sm-4 text-muted">Canal</dt>
                                                <dd class="col-sm-8" id="summary-channel">—</dd>
                                                <dt class="col-sm-4 text-muted">Tipo</dt>
                                                <dd class="col-sm-8" id="summary-type">—</dd>
                                                <dt class="col-sm-4 text-muted">Etiqueta</dt>
                                                <dd class="col-sm-8" id="summary-tag">—</dd>
                                                <dt class="col-sm-4 text-muted">Canal filtro</dt>
                                                <dd class="col-sm-8" id="summary-filter-channel">—</dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="card bg-light-subtle border">
                                        <div class="card-body">
                                            <h6 class="fw-semibold mb-2">Mensaje</h6>
                                            <p class="small mb-0 text-pre-wrap" id="summary-body">—</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Fecha de envio (opcional)</label>
                                    <input type="datetime-local" name="scheduled_at"
                                        class="form-control @error('scheduled_at') is-invalid @enderror"
                                        value="{{ old('scheduled_at') }}">
                                    <div class="form-text">Si no seleccionas fecha, el broadcast se guardara en borrador para enviar manualmente.</div>
                                    @error('scheduled_at')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Paso 4: Confirmacion --}}
                        <div class="step-content d-none" id="step-4">
                            <h6 class="fw-bold mb-1">Confirmacion</h6>
                            <p class="text-muted small mb-3">Confirma la creacion del broadcast. Quedara en estado borrador.</p>

                            <div class="text-center py-3">
                                <div class="d-flex flex-column align-items-center gap-3">
                                    <div class="round-64 rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center">
                                        <i class="fas fa-bullhorn fs-4"></i>
                                    </div>
                                    <div>
                                        <h5 class="fw-bold mb-1" id="confirm-name">Broadcast</h5>
                                        <p class="text-muted mb-0" id="confirm-summary">Canal: — | Tipo: —</p>
                                    </div>
                                    <div class="alert alert-warning d-flex align-items-start gap-2 text-start w-100">
                                        <i class="fas fa-triangle-exclamation mt-1"></i>
                                        <div class="small">
                                            El broadcast se creara en estado <strong>borrador</strong>. Podras revisarlo y enviarlo desde el listado de broadcasts.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Footer navigation --}}
                    <div class="card-footer">
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-light flex-shrink-0 d-none" id="btnPrev">
                                Anterior
                            </button>
                            <button type="button" class="btn btn-primary flex-grow-1" id="btnNext">
                                Continuar
                            </button>
                            <button type="submit" class="btn btn-primary flex-grow-1 d-none" id="btnSubmit">
                                Crear broadcast en borrador
                            </button>
                        </div>
                        <a href="{{ route('settings.helpdesk.broadcasts.index') }}" class="btn btn-light w-100 mt-2">
                            Cancelar
                        </a>
                    </div>

                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Sobre los broadcasts</h6>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted small">
                        Un broadcast permite enviar un mensaje masivo a un segmento de contactos en un solo canal.
                    </p>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Canales soportados</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled small text-muted mb-0">
                        <li class="mb-1"><strong>WhatsApp</strong> — soporta texto libre y templates HSM aprobados</li>
                        <li class="mb-1"><strong>Facebook / Instagram</strong> — solo texto libre dentro de ventana de 24h</li>
                        <li class="mb-1"><strong>Email</strong> — requiere template configurado</li>
                        <li class="mb-1"><strong>Web</strong> — notificacion interna via widget web</li>
                    </ul>
                </div>
            </div>
            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Templates HSM</h6>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted small mb-0">
                        Los templates HSM deben estar aprobados previamente en Meta Business. Usa el ID exacto del template aprobado.
                    </p>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
window.HdPageFlash = { success: @json(session('success')), error: @json(session('error')) };
window.HdBroadcastCreateConfig = {
    initialStep: @json($errors->any() ? (
        ($errors->has('name') || $errors->has('channel') || $errors->has('filters.*')) ? 1 : (
            ($errors->has('template_type') || $errors->has('body') || $errors->has('template_id')) ? 2 : (
                $errors->has('scheduled_at') ? 3 : null
            )
        )
    ) : null),
};
</script>
<script src="{{ asset('vendor/helpdesk/settings/settings-common.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/settings/settings-common.js')) }}" defer></script>
<script src="{{ asset('vendor/helpdesk/settings/broadcast-create.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/settings/broadcast-create.js')) }}" defer></script>
@endpush

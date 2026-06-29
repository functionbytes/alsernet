@extends('layouts.theme')

@section('title', 'Editar lista')

@section('content')

    <div class="row g-3">

        {{-- Formulario --}}
        <div class="col-12 col-lg-8">

            {{-- Datos principales --}}
            <div class="card mb-3">
                <form method="post" action="{{ route('manager.maillists.slim.update', $list->uid) }}">
                    @csrf @method('PUT')
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Editar lista</h5>
                        <small class="text-muted">{{ $list->name }}</small>
                    </div>
                    <div class="card-body">

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $e)
                                        <li>{{ $e }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="mb-3">
                            <label for="name" class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $list->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Descripción</label>
                            <textarea id="description" name="description"
                                      class="form-control" rows="2"
                                      placeholder="Descripción opcional">{{ old('description', $list->description) }}</textarea>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="from_email" class="form-label">From email</label>
                                <input type="email" id="from_email" name="from_email"
                                       class="form-control @error('from_email') is-invalid @enderror"
                                       value="{{ old('from_email', $list->from_email) }}"
                                       placeholder="noreply@empresa.com">
                                @error('from_email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="from_name" class="form-label">From name</label>
                                <input type="text" id="from_name" name="from_name"
                                       class="form-control @error('from_name') is-invalid @enderror"
                                       value="{{ old('from_name', $list->from_name) }}"
                                       placeholder="Mi empresa">
                                @error('from_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="default_subject" class="form-label">Asunto por defecto</label>
                            <input type="text" id="default_subject" name="default_subject"
                                   class="form-control"
                                   value="{{ old('default_subject', $list->default_subject) }}">
                            <small class="form-text text-muted">Asunto predefinido para nuevas campañas</small>
                        </div>

                        <hr class="my-3">

                        <h6 class="fw-semibold mb-3">Datos de contacto (footer legal)</h6>

                        <div class="mb-3">
                            <label for="contact_company" class="form-label">Empresa</label>
                            <input type="text" id="contact_company" name="contact_company"
                                   class="form-control"
                                   value="{{ old('contact_company', $list->contact_company) }}">
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-8">
                                <label for="contact_address_1" class="form-label">Dirección</label>
                                <input type="text" id="contact_address_1" name="contact_address_1"
                                       class="form-control"
                                       value="{{ old('contact_address_1', $list->contact_address_1) }}">
                            </div>
                            <div class="col-md-4">
                                <label for="contact_city" class="form-label">Ciudad</label>
                                <input type="text" id="contact_city" name="contact_city"
                                       class="form-control"
                                       value="{{ old('contact_city', $list->contact_city) }}">
                            </div>
                        </div>

                        <hr class="my-3">

                        <h6 class="fw-semibold mb-3">Política de suscripción</h6>

                        <div class="form-check mb-2">
                            <input type="checkbox" name="subscribe_confirmation" value="1"
                                   id="dco" class="form-check-input"
                                   @checked($list->subscribe_confirmation)>
                            <label for="dco" class="form-check-label">Doble opt-in</label>
                            <div class="form-text text-muted">Requiere confirmación por email antes de activar al suscriptor</div>
                        </div>

                        <div class="form-check mb-2">
                            <input type="checkbox" name="send_welcome_email" value="1"
                                   id="we" class="form-check-input"
                                   @checked($list->send_welcome_email)>
                            <label for="we" class="form-check-label">Email de bienvenida</label>
                            <div class="form-text text-muted">Se envía automáticamente al confirmar la suscripción</div>
                        </div>

                        <div class="form-check mb-2">
                            <input type="checkbox" name="unsubscribe_notification" value="1"
                                   id="un" class="form-check-input"
                                   @checked($list->unsubscribe_notification)>
                            <label for="un" class="form-check-label">Notificar desuscripciones al admin</label>
                            <div class="form-text text-muted">Recibirás un aviso cada vez que alguien se dé de baja</div>
                        </div>

                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">
                            Guardar cambios
                        </button>
                        <a href="{{ route('manager.maillists.show', $list->uid) }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>

        </div>

        {{-- Panel informativo --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="card-title mb-2">Accesos rápidos</h6>
                    <div class="d-grid gap-2">
                        <a href="{{ route('manager.maillists.subscribers.index', $list->uid) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-users me-1"></i> Ver suscriptores
                        </a>
                        <a href="{{ route('manager.maillists.fields', $list->uid) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-sliders-h me-1"></i> Campos personalizados
                        </a>
                        <a href="{{ route('manager.maillists.sending-servers', $list->uid) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-server me-1"></i> Servidores de envío
                        </a>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-2">Datos de contacto</h6>
                    <p class="card-text text-muted">
                        La empresa, dirección y ciudad se usan en el footer legal de los emails para cumplir con la normativa antispam.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-2">Política de suscripción</h6>
                    <p class="card-text text-muted mb-0">
                        El doble opt-in mejora la calidad de la lista y reduce las quejas de spam. Es altamente recomendable mantenerlo activo.
                    </p>
                </div>
            </div>
        </div>

    </div>

@endsection

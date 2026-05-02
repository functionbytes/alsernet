@extends('helpdesk::portal.layouts.base')

@section('title', 'Mi perfil')

@section('content')
    <div class="mb-4">
        <h4 class="fw-bold mb-0">Mi perfil</h4>
        <small class="text-muted">Actualiza tu información de contacto</small>
    </div>

    <div class="row g-4">
        {{-- Profile form --}}
        <div class="col-12 col-md-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="fw-bold mb-0">Datos personales</h6>
                </div>
                <form method="POST" action="{{ route('helpdesk.portal.profile.update') }}" novalidate>
                    @csrf
                    <div class="card-body">
                        {{-- Name --}}
                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">Nombre completo</label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $customer->name) }}"
                                required
                                maxlength="255"
                                placeholder="Tu nombre"
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Email (read-only) --}}
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">
                                Correo electrónico
                                <span class="text-muted fw-normal small">(no se puede cambiar)</span>
                            </label>
                            <input
                                type="email"
                                id="email"
                                class="form-control bg-light"
                                value="{{ $customer->email }}"
                                readonly
                                disabled
                            >
                            <div class="form-text">
                                <i class="fas fa-lock me-1 text-muted"></i>
                                Tu email es el identificador de tu cuenta.
                            </div>
                        </div>

                        {{-- Phone --}}
                        <div class="mb-3">
                            <label for="phone" class="form-label fw-semibold">Teléfono</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fas fa-phone text-muted"></i>
                                </span>
                                <input
                                    type="tel"
                                    id="phone"
                                    name="phone"
                                    class="form-control @error('phone') is-invalid @enderror"
                                    value="{{ old('phone', $customer->phone) }}"
                                    maxlength="30"
                                    placeholder="+52 55 1234 5678"
                                >
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top py-3">
                        <button type="submit" class="btn btn-primary-portal w-100">
                            <i class="fas fa-floppy-disk me-2"></i>Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Account summary --}}
        <div class="col-12 col-md-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="fw-bold mb-0">Resumen de cuenta</h6>
                </div>
                <div class="card-body">
                    {{-- Avatar --}}
                    <div class="text-center mb-4">
                        <img
                            src="{{ $customer->getAvatarUrl() }}"
                            alt="{{ $customer->name }}"
                            class="rounded-circle border"
                            width="72"
                            height="72"
                        >
                        <div class="mt-2 fw-semibold">{{ $customer->name }}</div>
                        <small class="text-muted">{{ $customer->email }}</small>
                    </div>

                    <hr>

                    <ul class="list-unstyled small">
                        <li class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">Conversaciones</span>
                            <span class="fw-semibold">{{ $customer->total_conversations ?? 0 }}</span>
                        </li>
                        <li class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">Miembro desde</span>
                            <span class="fw-semibold">{{ $customer->created_at->format('M Y') }}</span>
                        </li>
                        @if ($customer->last_seen_at)
                            <li class="d-flex justify-content-between py-2">
                                <span class="text-muted">Última actividad</span>
                                <span class="fw-semibold">{{ $customer->last_seen_at->diffForHumans() }}</span>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

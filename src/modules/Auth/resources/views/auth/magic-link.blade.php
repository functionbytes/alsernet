@extends('layouts.auth')

@section('title', 'Inicio de sesión por enlace')

@section('content')
<div class="bg--scroll login-section division">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="register-page-form mt-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-5">
                            <div class="text-center mb-4">
                                <i class="fas fa-envelope-circle-check fa-3x text-primary mb-3"></i>
                                <h4 class="fw-bold mb-2">Inicia sesión sin contraseña</h4>
                                <p class="text-muted mb-0">
                                    Ingresa tu correo y te enviaremos un enlace seguro para acceder.
                                </p>
                            </div>

                            @if (session('status'))
                                <div class="alert alert-success border-0">
                                    {{ session('status') }}
                                </div>
                            @endif

                            <form method="POST" action="{{ route('auth.magic-link.request') }}">
                                @csrf
                                <div class="mb-3">
                                    <label for="email" class="form-label">Correo electrónico</label>
                                    <input type="email" name="email" id="email"
                                           value="{{ old('email') }}"
                                           class="form-control form-control-lg @error('email') is-invalid @enderror"
                                           placeholder="tu@correo.com" required autofocus>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <button type="submit" class="btn btn-primary w-100 btn-lg mb-3">
                                    <i class="fas fa-paper-plane me-1"></i> Enviar enlace
                                </button>

                                <div class="text-center">
                                    <a href="{{ route('auth.login') }}" class="text-muted small">
                                        <i class="fas fa-arrow-left me-1"></i> Volver al inicio de sesión normal
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

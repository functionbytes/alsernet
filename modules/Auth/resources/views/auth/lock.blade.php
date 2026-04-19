@extends('layouts.auth')

@section('title', 'Sesión bloqueada')

@section('content')
<div class="bg--scroll login-section division">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="register-page-form mt-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-5 text-center">
                            <div class="mb-4">
                                <i class="fas fa-lock fa-3x text-primary"></i>
                            </div>

                            <h4 class="fw-bold mb-2">Sesión bloqueada</h4>
                            <p class="text-muted mb-4">
                                Hola <strong>{{ auth()->user()->firstname }}</strong>, por seguridad tu sesión está bloqueada.
                                Ingresa tu contraseña para continuar.
                            </p>

                            <form id="formUnlock" method="POST" action="{{ route('auth.lock.unlock') }}" class="text-start">
                                @csrf

                                <div class="mb-3">
                                    <label for="password" class="form-label">Contraseña</label>
                                    <input type="password" name="password" id="password"
                                        class="form-control form-control-lg @error('password') is-invalid @enderror"
                                        placeholder="Ingresa tu contraseña" required autofocus>
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <button type="submit" class="btn btn-primary w-100 btn-lg mb-2">
                                    <i class="fas fa-unlock me-1"></i> Desbloquear
                                </button>

                                <form action="{{ route('auth.logout') }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-link w-100 text-muted">
                                        Cerrar sesión
                                    </button>
                                </form>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

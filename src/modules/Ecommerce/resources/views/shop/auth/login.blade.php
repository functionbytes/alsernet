@extends('ecommerce::layouts.shop-wowy')

@section('title', 'Iniciar sesion')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header text-center">
                        <h5 class="mb-0">Iniciar sesion</h5>
                        <p class="small text-muted mb-0">Accede a tu cuenta de cliente</p>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')

                        <form action="{{ route('ecommerce.login') }}" method="POST">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required autofocus>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Contrasena <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3 d-flex justify-content-between align-items-center">
                                <div class="form-check">
                                    <input type="checkbox" name="remember" class="form-check-input" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="remember">Recordarme</label>
                                </div>
                                <a href="{{ route('ecommerce.password.request') }}" class="small text-decoration-none">Olvidaste tu contrasena?</a>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Iniciar sesion</button>
                        </form>

                        <div class="text-center my-3">
                            <span class="text-muted small">o continua con</span>
                        </div>
                        <div class="d-grid gap-2">
                            <a href="{{ route('ecommerce.social.redirect', 'google') }}" class="btn btn-outline-danger">
                                <i class="fab fa-google me-2"></i>Iniciar sesion con Google
                            </a>
                            <a href="{{ route('ecommerce.social.redirect', 'facebook') }}" class="btn btn-outline-primary">
                                <i class="fab fa-facebook-f me-2"></i>Iniciar sesion con Facebook
                            </a>
                        </div>
                    </div>
                    <div class="card-footer text-center">
                        <small class="text-muted">No tienes cuenta? <a href="{{ route('ecommerce.register') }}">Registrate</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

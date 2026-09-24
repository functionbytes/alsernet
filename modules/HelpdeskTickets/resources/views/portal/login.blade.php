@extends('helpdesktickets::portal.layout')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h4 class="card-title mb-1">
                        <i class="fas fa-sign-in-alt me-2 text-primary"></i>Acceder al portal de soporte
                    </h4>
                    <p class="text-muted mb-4">Escribe tu correo electrónico y te enviaremos un enlace de acceso.</p>

                    @if (session('status'))
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-1"></i>{{ session('status') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-1"></i>{{ session('error') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form action="{{ route('portal.login.submit') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="email" class="form-label">Correo electrónico</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                placeholder="tu@ejemplo.com"
                            >
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            Enviar enlace de acceso
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

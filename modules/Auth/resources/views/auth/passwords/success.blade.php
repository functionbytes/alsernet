@extends('layouts.auth')

@section('title', 'Ingresar')

@section('content')
    <section class="account pt-150 padding-bottom">
        <div class="px-3">
            <div class="account__wrapper aos-init aos-animate" data-aos="fade-up" data-aos-duration="800">
                <div class="row g-4">
                    <div class="col-lg-12">
                        <div class="account__content">
                            <!-- account tittle -->
                            <div class="account__header">
                                <h3>Correo recuperación</h3>
                                <p>Si existe una cuenta asociada a <u>{{ $email }}</u>, recibirás en unos minutos un correo con un enlace para restablecer la contraseña.</p>
                                <p>¿No recibiste el mensaje?</p>
                            </div>
                                <a href="{{ route('auth.password.request') }}" class="theme-btn w-100 mt-30">Enviar nuevamente</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection




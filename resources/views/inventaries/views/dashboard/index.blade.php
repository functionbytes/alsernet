@extends('layouts.inventaries')

@section('content')

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">
            <div class="card w-100 content-dashboard">
                <div class="position-relative">
                    <div class="row">
                        <div class="col-sm-7">
                            <div class="mb-7 mt-6">
                                <h2 class="fw-semibold mb-1 text-uppercase">Bienvenido!</h2>
                                <p class="text-black">El perfil de Administrador permite gestionar empresas, usuarios, inscripciones y reportes, ofreciendo control total sobre la plataforma para optimizar la experiencia educativa.</p>
                            </div>
                        </div>
                        <div class="col-sm-5">
                            <div class="welcome-bg-img mb-n7 text-end">
                                <img src="/customers/images/dashboard/dashboard.svg" alt="" class="img-fluid">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')



@endpush

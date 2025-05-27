@extends('layouts.callcenters')

@section('content')

            <div class="container-fluid">

                <div class="row justify-content-center navegation-content">
                    <div class="col-lg-12 text-center">
                        <span class="fw-bolder text-uppercase fs-2 d-block mb-1">EMPRESA</span>
                            <h3 class="fw-bolder mb-0 fs-8 lh-base">{{ $distributor->title }}</h3>
                    </div>
                </div>

                <div class="row justify-content-center mt--20">
                    <div class="col-sm-6 col-lg-3">
                        <a class="card" href="{{ route('callcenter.distributors.enterprises', $distributor->uid) }}">
                            <div class="card-body text-center">
                                <span class="fw-bolder text-uppercase fs-2 d-block mb-7">Opción</span>
                                <div class="my-4">
                                    <i class="font-navegation fa-sharp-duotone fa-solid fa-house-blank"></i>
                                </div>
                                <h4 class="fw-bolder  text-uppercase mb-3">Empresas</h4>
                            </div>
                        </a>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <a class="card" href="{{ route('callcenter.distributors.staffs', $distributor->uid) }}">
                            <div class="card-body text-center">
                                <span class="fw-bolder text-uppercase fs-2 d-block mb-7">Opción</span>
                                <div class="my-4">
                                    <i class="font-navegation fa-duotone fa-user-vneck-hair"></i>
                                </div>
                                <h4 class="fw-bolder  text-uppercase mb-3">Empleados</h4>
                            </div>
                        </a>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <a class="card" href="{{ route('callcenter.distributors.courses', $distributor->uid) }}">
                            <div class="card-body text-center">
                                <span class="fw-bolder text-uppercase fs-2 d-block mb-7">Opción</span>
                                <div class="my-4">
                                    <i class="font-navegation fa-duotone fa-ballot-check"></i>
                                </div>
                                <h4 class="fw-bolder  text-uppercase mb-3">Cursos</h4>
                            </div>
                        </a>
                    </div>
                    @if(count($distributor->rates) > 0)
                    <div class="col-sm-6 col-lg-3">
                        <a class="card" href="{{ route('callcenter.distributors.rates', $distributor->uid) }}">
                            <div class="card-body text-center">
                                <span class="fw-bolder text-uppercase fs-2 d-block mb-7">Opción</span>
                                <div class="my-4">
                                    <i class="font-navegation fa-duotone fa-circle-dollar"></i>
                                </div>
                                <h4 class="fw-bolder  text-uppercase mb-3">Tarifas</h4>
                            </div>
                        </a>
                    </div>
                    @endif


                    <div class="col-sm-6 col-lg-3">
                        <a class="card" href="{{ route('callcenter.distributors.registers', $distributor->uid) }}">
                            <div class="card-body text-center">
                                <span class="fw-bolder text-uppercase fs-2 d-block mb-7">Opción</span>
                                <div class="my-4">
                                    <i class="font-navegation fa-duotone fa-solid fa-inbox-full"></i>
                                </div>
                                <h4 class="fw-bolder  text-uppercase mb-3">Registro usuarios</h4>
                            </div>
                        </a>
                    </div>

                    <div class="col-sm-6 col-lg-3">
                        <a class="card" href="{{ route('callcenter.distributors.inscriptions', $distributor->uid) }}">
                            <div class="card-body text-center">
                                <span class="fw-bolder text-uppercase fs-2 d-block mb-7">Opción</span>
                                <div class="my-4">
                                    <i class="font-navegation fa-duotone fa-thin fa-clipboard-prescription"></i>
                                </div>
                                <h4 class="fw-bolder  text-uppercase mb-3">Inscripciones</h4>
                            </div>
                        </a>
                    </div>


                    <div class="col-sm-6 col-lg-3">
                        <a class="card" href="{{ route('callcenter.distributors.orders', $distributor->uid) }}">
                            <div class="card-body text-center">
                                <span class="fw-bolder text-uppercase fs-2 d-block mb-7">Opción</span>
                                <div class="my-4">
                                    <i class="font-navegation fa-duotone fa-regular fa-folders"></i>
                                </div>
                                <h4 class="fw-bolder  text-uppercase mb-3">Ordenes</h4>
                            </div>
                        </a>
                    </div>

                    <div class="col-sm-6 col-lg-3">
                        <a class="card" href="{{ route('callcenter.distributors.invoices', $distributor->uid) }}">
                            <div class="card-body text-center">
                                <span class="fw-bolder text-uppercase fs-2 d-block mb-7">Opción</span>
                                <div class="my-4">
                                    <i class="font-navegation fa-duotone fa-regular fa-bags-shopping"></i>
                                </div>
                                <h4 class="fw-bolder  text-uppercase mb-3">Facturas</h4>
                            </div>
                        </a>
                    </div>





                </div>
            </div>

@endsection


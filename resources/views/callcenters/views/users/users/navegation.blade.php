@extends('layouts.callcenters')

@section('content')

            <div class="container-fluid">

                <div class="row justify-content-center navegation-content">
                    <div class="col-lg-12 text-center">
                        <span class="fw-bolder text-uppercase fs-2 d-block mb-1">EMPRESA</span>
                            <h3 class="fw-bolder mb-0 fs-8 lh-base">{{ $user->title }}</h3>
                    </div>
                </div>


                <div class="row justify-content-center mt--20">
                    <div class="col-sm-6 col-lg-4">
                        <a class="card" href="{{ route('callcenter.users.edit', $user->uid) }}">
                            <div class="card-body text-center">
                                <span class="fw-bolder text-uppercase fs-2 d-block mb-7">Opción</span>
                                <div class="my-4">
                                    <i class="font-navegation fa-duotone fa-user-vneck-hair"></i>
                                </div>
                                <h4 class="fw-bolder  text-uppercase mb-3">Configuraciòn</h4>
                            </div>
                        </a>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <a class="card" href="{{ route('callcenter.users.activitys', $user->uid) }}">
                            <div class="card-body text-center">
                                <span class="fw-bolder text-uppercase fs-2 d-block mb-7">Opción</span>
                                <div class="my-4">
                                    <i class="font-navegation fa-duotone fa-ballot-check"></i>
                                </div>
                                <h4 class="fw-bolder  text-uppercase mb-3">Actividad</h4>
                            </div>
                        </a>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <a class="card" href="{{ route('callcenter.users.courses.index', $user->uid) }}">
                            <div class="card-body text-center">
                                <span class="fw-bolder text-uppercase fs-2 d-block mb-7">Opción</span>
                                <div class="my-4">
                                    <i class="font-navegation fa-duotone fa-file-chart-column"></i>
                                </div>
                                <h4 class="fw-bolder  text-uppercase mb-3">Courses</h4>
                            </div>
                        </a>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <a class="card" href="{{ route('callcenter.users.orders.index', $user->uid) }}">
                            <div class="card-body text-center">
                                <span class="fw-bolder text-uppercase fs-2 d-block mb-7">Opción</span>
                                <div class="my-4">
                                    <i class="font-navegation fa-duotone fa-credit-card-front"></i>
                                </div>
                                <h4 class="fw-bolder  text-uppercase mb-3">Ordenes</h4>
                            </div>
                        </a>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <a class="card" href="{{ route('callcenter.users.courses.certificates', $user->uid) }}">
                            <div class="card-body text-center">
                                <span class="fw-bolder text-uppercase fs-2 d-block mb-7">Opción</span>
                                <div class="my-4">
                                    <i class="font-navegation fa-duotone fa-ballot-check"></i>
                                </div>
                                <h4 class="fw-bolder  text-uppercase mb-3">Certificados</h4>
                            </div>
                        </a>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <a class="card" href="{{ route('callcenter.users.courses.results', $user->uid) }}">
                            <div class="card-body text-center">
                                <span class="fw-bolder text-uppercase fs-2 d-block mb-7">Opción</span>
                                <div class="my-4">
                                    <i class="font-navegation fa-duotone fa-ballot-check"></i>
                                </div>
                                <h4 class="fw-bolder  text-uppercase mb-3">Resultados</h4>
                            </div>
                        </a>
                    </div>

                </div>
            </div>

@endsection


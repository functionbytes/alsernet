@extends('layouts.callcenters')

@section('content')

            <div class="container-fluid">

                <div class="row justify-content-center navegation-content">
                    <div class="col-lg-12 text-center">
                        <span class="fw-bolder text-uppercase fs-2 d-block mb-1">EMPRESA</span>
                            <h3 class="fw-bolder mb-0 fs-8 lh-base">{{ $enterprise->title }}</h3>
                    </div>
                </div>


                <div class="row justify-content-center mt--20">
                    <div class="col-sm-6 col-lg-4">
                        <a class="card" href="{{ route('callcenter.enterprises.users', $enterprise->uid) }}">
                            <div class="card-body text-center">
                                <span class="fw-bolder text-uppercase fs-2 d-block mb-7">Opción</span>
                                <div class="my-4">
                                    <i class="font-navegation fa-duotone fa-user-vneck-hair"></i>
                                </div>
                                <h4 class="fw-bolder  text-uppercase mb-3">Usuarios</h4>
                            </div>
                        </a>
                    </div>
                        <div class="col-sm-6 col-lg-4">
                            <a class="card" href="{{ route('callcenter.enterprises.staffs', $enterprise->uid) }}">
                                <div class="card-body text-center">
                                    <span class="fw-bolder text-uppercase fs-2 d-block mb-7">Opción</span>
                                    <div class="my-4">
                                        <i class="font-navegation fa-duotone fa-solid fa-user-tie-hair"></i>
                                    </div>
                                    <h4 class="fw-bolder  text-uppercase mb-3">Empleados</h4>
                                </div>
                            </a>
                        </div>
                    <div class="col-sm-6 col-lg-4">
                        <a class="card" href="{{ route('callcenter.enterprises.courses', $enterprise->uid) }}">
                            <div class="card-body text-center">
                                <span class="fw-bolder text-uppercase fs-2 d-block mb-7">Opción</span>
                                <div class="my-4">
                                    <i class="font-navegation fa-duotone fa-ballot-check"></i>
                                </div>
                                <h4 class="fw-bolder  text-uppercase mb-3">Cursos</h4>
                            </div>
                        </a>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <a class="card" href="{{ route('callcenter.enterprises.users.income', $enterprise->uid) }}">
                            <div class="card-body text-center">
                                <span class="fw-bolder text-uppercase fs-2 d-block mb-7">Opción</span>
                                <div class="my-4">
                                    <i class="font-navegation fa-duotone fa-file-chart-column"></i>
                                </div>
                                <h4 class="fw-bolder  text-uppercase mb-3">Reporte</h4>
                            </div>
                        </a>
                    </div>

                    <div class="col-sm-6 col-lg-4">
                        <a class="card" href="{{ route('callcenter.enterprises.reassign', $enterprise->uid) }}">
                            <div class="card-body text-center">
                                <span class="fw-bolder text-uppercase fs-2 d-block mb-7">Opción</span>
                                <div class="my-4">
                                    <i class="font-navegation fa-duotone fa-credit-card-front"></i>
                                </div>
                                <h4 class="fw-bolder  text-uppercase mb-3">Reasignación de usuarios</h4>
                            </div>
                        </a>
                    </div>

                    <div class="col-sm-6 col-lg-4">
                        <a class="card" href="{{ route('callcenter.enterprises.orders', $enterprise->uid) }}">
                            <div class="card-body text-center">
                                <span class="fw-bolder text-uppercase fs-2 d-block mb-7">Opción</span>
                                <div class="my-4">
                                    <i class="font-navegation fa-duotone fa-credit-card-front"></i>
                                </div>
                                <h4 class="fw-bolder  text-uppercase mb-3">Ordenes</h4>
                            </div>
                        </a>
                    </div>

                </div>
            </div>

@endsection


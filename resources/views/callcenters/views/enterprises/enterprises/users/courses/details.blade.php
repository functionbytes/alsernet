@extends('layouts.callcenters')

@section('content')

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">

                            <h5 class="mb-0">Visualizar estado curso</h5>

                        </div>
                        <p class="card-subtitle mb-3 mt-3">
                            Este espacio ha sido diseñado para que puedas verificar el estado del curso que estás realizando..
                        </p>

                        <div class="row">

                            <div class="col-12">
                                <div class="mb-3">
                                        <label  class="control-label col-form-label">Curso</label>
                                        <input type="text" class="form-control" id="firstname"  name="firstname" value="{{ $course->title }}" placeholder="Ingresar nombres" disabled>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                        <label  class="control-label col-form-label">Fecha inicio</label>
                                        <input type="text" class="form-control" id="lastname"  name="lastname" value="{{ $order->enroll_start }}" placeholder="Ingresar apellido" disabled>
                                    </div>
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                        <label  class="control-label col-form-label">Fecha finalización</label>
                                        <input type="text" class="form-control" id="enroll_start"  name="enroll_start" value="{{ $order->enroll_expire  }}" disabled>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                        <label  class="control-label col-form-label">Estado curso</label>
                                        <input type="text" class="form-control" id="enroll_start"  name="enroll_start" value="{{ $user->culminate == 1 ? 'Finalizado' : 'Pendiente' }}" disabled>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>

            </div>

        </div>

    </div>

@endsection


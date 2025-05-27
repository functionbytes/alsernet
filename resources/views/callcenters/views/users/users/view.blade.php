@extends('layouts.callcenters')

@section('content')

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formUsers" enctype="multipart/form-data" role="form" onSubmit="return false">

                    {{ csrf_field() }}



                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">

                            <h5 class="mb-0">Visualizar
                                @if ($user->role == 'manager')
                                    administrador
                                @elseif($user->role == 'customer')
                                    cliente
                                @elseif($user->role == 'enterprises')
                                    empresa
                                @endif
                            </h5>

                        </div>
                        <p class="card-subtitle mb-3 mt-3">
                            Este espacio está diseñado para que puedas actualizar y modificar la información de manera eficiente y segura. A continuación, encontrarás diversos <mark><code>campos</code></mark> que corresponden a los datos previamente suministrados. Te invitamos a revisar y ajustar cualquier información que consideres necesario actualizar para mantener tus datos al día.
                        </p>

                                        <div class="row">
                                            <div class="col-6">
                                                <div class="mb-3">
                                                    <label  class="control-label col-form-label">Nombres</label>
                                                    <input type="text" class="form-control" id="firstname"  name="firstname" value="{{ $user->firstname }}" placeholder="Ingresar nombres" disabled>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="mb-3">
                                                    <label  class="control-label col-form-label">Apellidos</label>
                                                    <input type="text" class="form-control" id="lastname"  name="lastname" value="{{ $user->lastname }}" placeholder="Ingresar apellido" disabled>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="mb-3">
                                                    <label  class="control-label col-form-label">Identificación</label>
                                                    <input type="text" class="form-control" id="identification"  name="identification" value="{{ $user->identification }}" placeholder="Ingresar identificación" disabled>

                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="mb-3">
                                                    <label  class="control-label col-form-label">Correo electronico</label>
                                                    <input type="text" class="form-control" id="email"  name="email" value="{{ $user->email }}" placeholder="Ingresar correo electronico" disabled>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="mb-3">
                                                    <label  class="control-label col-form-label">Celular</label>
                                                    <input type="text" class="form-control" id="cellphone"  name="cellphone" value="{{ $user->cellphone }}" placeholder="Ingresar celular" disabled>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="mb-3">
                                                    <label  class="control-label col-form-label">Dirección</label>
                                                    <input type="text" class="form-control" id="address" name="address" value="{{ $user->address }}" placeholder="Ingresar dirección" disabled>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="mb-3">
                                                    <label  class="control-label col-form-label">Dirección</label>
                                                    <input type="text" class="form-control" id="address" name="address" value="{{ $user->available ? 'Activo' : 'Inactivo' }}" placeholder="Ingresar dirección" disabled>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="mb-3">
                                                    <label  class="control-label col-form-label">Perfil</label>
                                                    <input type="text" class="form-control" id="address" name="address" value="{{ $user->role ? $user->role : 'Sin rol' }}" placeholder="Ingresar dirección" disabled>
                                                </div>
                                            </div>



                                        </div>

                        </div>

                    </div>


                </form>
            </div>

        </div>

    </div>

@endsection



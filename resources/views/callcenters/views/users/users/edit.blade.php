@extends('layouts.callcenters')

@section('content')


    <div class="card card-custom">
        <ul class="nav nav-pills user-profile-tab" id="pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link position-relative rounded-0 active d-flex align-items-center justify-content-center bg-transparent fs-3 py-2" id="pills-account-tab" data-bs-toggle="pill" data-bs-target="#pills-account" type="button" role="tab" aria-controls="pills-account" aria-selected="true">
                    <i class="ti ti-user-circle me-2 fs-6"></i>
                    <span class="d-none d-md-block">Cuenta</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-2" id="pills-information-tab" data-bs-toggle="pill" data-bs-target="#pills-information" type="button" role="tab" aria-controls="pills-information" aria-selected="false">
                    <i class="ti ti-bell me-2 fs-6"></i>
                    <span class="d-none d-md-block">Información</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-2" id="pills-notifications-tab" data-bs-toggle="pill" data-bs-target="#pills-notifications" type="button" role="tab" aria-controls="pills-notifications" aria-selected="false">
                    <i class="ti ti-bell me-2 fs-6"></i>
                    <span class="d-none d-md-block">Notificaciones</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-2" id="pills-bills-tab" data-bs-toggle="pill" data-bs-target="#pills-bills" type="button" role="tab" aria-controls="pills-bills" aria-selected="false">
                    <i class="ti ti-article me-2 fs-6"></i>
                    <span class="d-none d-md-block">Pagos</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-2" id="pills-security-tab" data-bs-toggle="pill" data-bs-target="#pills-security" type="button" role="tab" aria-controls="pills-security" aria-selected="false">
                    <i class="ti ti-lock me-2 fs-6"></i>
                    <span class="d-none d-md-block">Seguridad</span>
                </button>
            </li>
        </ul>
        <div class="card-body">
            <div class="tab-content" id="pills-tabContent">
                <div class="tab-pane fade show active" id="pills-account" role="tabpanel" aria-labelledby="pills-account-tab" tabindex="0">
                    <div class="row">

                    </div>
                </div>
                <div class="tab-pane fade " id="pills-information" role="tabpanel" aria-labelledby="pills-information-tab" tabindex="0">
                    <div class="row">
                        <form id="formInformation" enctype="multipart/form-data" role="form" onSubmit="return false">

                            {{ csrf_field() }}

                            <input type="hidden" id="id" name="id" value="{{ $user->id }}">
                            <input type="hidden" id="slack" name="slack" value="{{ $user->slack }}">
                            <input type="hidden" id="edit" name="edit" value="true">

                            <div class="col-12 ">
                                    <div class="mb-1 mt-3">
                                            <div class="card-body p-4">
                                                    <h4 class="fw-semibold mb-3">Datos personales</h4>
                                                    <p class="card-subtitle mb-4">Para cambiar sus datos personales, edítelos y guárdelos desde aquí</p>
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
                                                                <input type="text" class="form-control" id="cellphone"  name="cellphone" value="{{ $user->cellphone }}" placeholder="Ingresar celular">
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="mb-3">
                                                                <label  class="control-label col-form-label">Dirección</label>
                                                                <input type="text" class="form-control" id="address" name="address" value="{{ $user->address }}" placeholder="Ingresar dirección">
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="mb-3">
                                                                <label class="control-label col-form-label">Estado</label>
                                                                <div class="input-group">
                                                                    {!! Form::select('available', $availables, $user->available , ['class' => 'select2 form-control','id' => 'available']) !!}
                                                                </div>
                                                                <label id="available-error" class="error d-none" for="available"></label>
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="mb-3">
                                                                <label class="control-label col-form-label">Perfil</label>
                                                                <div class="input-group">
                                                                    {!! Form::select('role', $roles, $user->role , ['class' => 'select2 form-control','id' => 'roles']) !!}
                                                                </div>
                                                                <label id="role-error" class="error d-none" for="role"></label>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-12">
                                                            <div class="errors d-none">
                                                            </div>
                                                        </div>


                                                    </div>
                                            </div>
                                    </div>
                           </div>

                            <div class="col-lg-12">
                                <div class="action-form border-top">
                                    <div class="text-center">
                                        <button type="submit" class="btn btn-info rounded-pill px-4 waves-effect waves-light">
                                            Guardar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="tab-pane fade" id="pills-notifications" role="tabpanel" aria-labelledby="pills-notifications-tab" tabindex="0">
                    <div class="row justify-content-center">
                        <form id="formNotification" enctype="multipart/form-data" role="form" onSubmit="return false">

                            {{ csrf_field() }}

                            <input type="hidden" id="id" name="id" value="{{ $user->id }}">
                            <input type="hidden" id="slack" name="slack" value="{{ $user->slack }}">
                            <input type="hidden" id="edit" name="edit" value="true">

                            <div class="col-12 ">
                                <div class="mb-3 ">
                                    <div class="card-body p-4">
                                        <h4 class="fw-semibold mb-3">Preferencias de notificaciones</h4>
                                        <p>
                                            Seleccione las notificaciones que desea recibir por correo electrónico. Tenga en cuenta que no puede optar por no recibir mensajes de servicio, como notificaciones de pago, seguridad o legales.
                                        </p>
                                        <div class="">
                                            <div class="d-flex align-items-center justify-content-between mb-4">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="bg-light rounded-1 p-6 d-flex align-items-center justify-content-center">
                                                        <i class="ti ti-article text-dark d-block fs-7" width="22" height="22"></i>
                                                    </div>
                                                    <div>
                                                        <h5 class="fs-4 fw-semibold">Nuestro boletín informativo</h5>
                                                        <p class="mb-0">Siempre te informaremos sobre cambios importantes.</p>
                                                    </div>
                                                </div>
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input" type="checkbox" role="switch" name="newsletter_notification" id="newsletter_notification"   @if($user->newsletter_notification==1 ) checked @endif>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between mb-4">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="bg-light rounded-1 p-6 d-flex align-items-center justify-content-center">
                                                        <i class="ti ti-checkbox text-dark d-block fs-7" width="22" height="22"></i>
                                                    </div>
                                                    <div>
                                                        <h5 class="fs-4 fw-semibold">Confirmación de pedido</h5>
                                                        <p class="mb-0">Se le notificará cuando el cliente solicite cualquier producto.</p>
                                                    </div>
                                                </div>
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input" type="checkbox" role="switch" name="order_notification" id="order_notification"   @if($user->order_notification==1 ) checked @endif>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between mb-4">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="bg-light rounded-1 p-6 d-flex align-items-center justify-content-center">
                                                        <i class="ti ti-clock-hour-4 text-dark d-block fs-7" width="22" height="22"></i>
                                                    </div>
                                                    <div>
                                                        <h5 class="fs-4 fw-semibold">Estado del pedido modificado</h5>
                                                        <p class="mb-0">Se le notificará cuando el cliente realice cambios en el pedido.</p>
                                                    </div>
                                                </div>
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input" type="checkbox" role="switch" name="status_notification" id="status_notification"   @if($user->status_notification==1 ) checked @endif>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="bg-light rounded-1 p-6 d-flex align-items-center justify-content-center">
                                                        <i class="ti ti-mail text-dark d-block fs-7" width="22" height="22"></i>
                                                    </div>
                                                    <div>
                                                        <h5 class="fs-4 fw-semibold">Notificación por correo electrónico</h5>
                                                        <p class="mb-0">Activa la notificación por correo electrónico para recibir actualizaciones por correo electrónico</p>
                                                    </div>
                                                </div>
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input" type="checkbox" role="switch" name="email_notification" id="email_notification"   @if($user->email_notification==1 ) checked @endif>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                            </div>
                            </div>
                            <div class="col-12 border-top">
                                <div class="mb-3 mt-3">
                                    <div class="card-body p-4">
                                        <h4 class="fw-semibold mb-3">Fecha y hora</h4>
                                        <p>Configuración de zonas horarias y visualización del calendario.</p>
                                        <div class="d-flex align-items-center justify-content-between mt-7">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="bg-light rounded-1 p-6 d-flex align-items-center justify-content-center">
                                                    <i class="ti ti-clock-hour-4 text-dark d-block fs-7" width="22" height="22"></i>
                                                </div>
                                                <div>
                                                    <p class="mb-0">Huso horario</p>
                                                    <h5 class="fs-4 fw-semibold">(UTC + 02:00) Athens, Bucharet</h5>
                                                </div>
                                            </div>
                                            <a class="text-dark fs-6 d-flex align-items-center justify-content-center bg-transparent p-2 fs-4 rounded-circle" href="javascript:void(0)" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Download">
                                                <i class="ti ti-download"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 border-top">
                                <div class="mb-3 mt-3">
                                    <div class="card-body p-4">
                                        <h4 class="fw-semibold mb-3">Ignorar seguimiento</h4>
                                        <div class="d-flex align-items-center justify-content-between mt-7">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="bg-light rounded-1 p-6 d-flex align-items-center justify-content-center">
                                                    <i class="ti ti-player-pause text-dark d-block fs-7" width="22" height="22"></i>
                                                </div>
                                                <div>
                                                    <h5 class="fs-4 fw-semibold">Ignorar el seguimiento del navegador</h5>
                                                    <p class="mb-0">Cookie del navegador</p>
                                                </div>
                                            </div>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" role="switch" name="cookies_notification" id="cookies_notification"   @if(setting('cookies_notification')==1 ) checked @endif>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-12">
                                <div class="action-form border-top">
                                    <div class="text-center">
                                        <button type="submit" class="btn btn-info rounded-pill px-4 waves-effect waves-light">
                                            Guardar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="tab-pane fade" id="pills-bills" role="tabpanel" aria-labelledby="pills-bills-tab" tabindex="0">
                    <div class="row justify-content-center">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-body p-4">
                                    <h4 class="fw-semibold mb-3">Billing Information</h4>
                                    <form>
                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div class="mb-4">
                                                    <label for="exampleInputPassword1" class="form-label fw-semibold">Business Name*</label>
                                                    <input type="text" class="form-control" id="exampleInputtext" placeholder="Visitor Analytics">
                                                </div>
                                                <div class="mb-4">
                                                    <label for="exampleInputPassword1" class="form-label fw-semibold">Business Address*</label>
                                                    <input type="text" class="form-control" id="exampleInputtext" placeholder="">
                                                </div>
                                                <div class="">
                                                    <label for="exampleInputPassword1" class="form-label fw-semibold">First Name*</label>
                                                    <input type="text" class="form-control" id="exampleInputtext" placeholder="">
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="mb-4">
                                                    <label for="exampleInputPassword1" class="form-label fw-semibold">Business Sector*</label>
                                                    <input type="text" class="form-control" id="exampleInputtext" placeholder="Arts, Media & Entertainment">
                                                </div>
                                                <div class="mb-4">
                                                    <label for="exampleInputPassword1" class="form-label fw-semibold">Country*</label>
                                                    <input type="text" class="form-control" id="exampleInputtext" placeholder="Romania">
                                                </div>
                                                <div class="">
                                                    <label for="exampleInputPassword1" class="form-label fw-semibold">Last Name*</label>
                                                    <input type="text" class="form-control" id="exampleInputtext" placeholder="">
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-body p-4">
                                    <h4 class="fw-semibold mb-3">Current Plan : <span class="text-success">Executive</span></h4>
                                    <p>Thanks for being a premium member and supporting our development.</p>
                                    <div class="d-flex align-items-center justify-content-between mt-7 mb-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="bg-light rounded-1 p-6 d-flex align-items-center justify-content-center">
                                                <i class="ti ti-package text-dark d-block fs-7" width="22" height="22"></i>
                                            </div>
                                            <div>
                                                <p class="mb-0">Current Plan</p>
                                                <h5 class="fs-4 fw-semibold">750.000 Monthly Visits</h5>
                                            </div>
                                        </div>
                                        <a class="text-dark fs-6 d-flex align-items-center justify-content-center bg-transparent p-2 fs-4 rounded-circle" href="javascript:void(0)" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Add">
                                            <i class="ti ti-circle-plus"></i>
                                        </a>
                                    </div>
                                    <div class="d-flex align-items-center gap-3">
                                        <button class="btn btn-primary">Change Plan</button>
                                        <button class="btn btn-outline-danger">Reset Plan</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-body p-4">
                                    <h4 class="fw-semibold mb-3">Payment Method</h4>
                                    <p>On 26 December, 2023</p>
                                    <div class="d-flex align-items-center justify-content-between mt-7">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="bg-light rounded-1 p-6 d-flex align-items-center justify-content-center">
                                                <i class="ti ti-credit-card text-dark d-block fs-7" width="22" height="22"></i>
                                            </div>
                                            <div>
                                                <h5 class="fs-4 fw-semibold">Visa</h5>
                                                <p class="mb-0 text-dark">*****2102</p>
                                            </div>
                                        </div>
                                        <a class="text-dark fs-6 d-flex align-items-center justify-content-center bg-transparent p-2 fs-4 rounded-circle" href="javascript:void(0)" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Edit">
                                            <i class="ti ti-pencil-minus"></i>
                                        </a>
                                    </div>
                                    <p class="my-2">If you updated your payment method, it will only be dislpayed here after your next billing cycle.</p>
                                    <div class="d-flex align-items-center gap-3">
                                        <button class="btn btn-outline-danger">Cancel Subscription</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="d-flex align-items-center justify-content-end gap-3">
                                <button class="btn btn-primary">Save</button>
                                <button class="btn btn-light-danger text-danger">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="pills-security" role="tabpanel" aria-labelledby="pills-security-tab" tabindex="0">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-body p-4">
                                    <h4 class="fw-semibold mb-0">Restablecer contraseña</h4>
                                    <div class="d-flex align-items-center justify-content-between pb-7">
                                        <p class="mb-0">Lorem ipsum, dolor sit amet consectetur adipisicing elit. Corporis sapiente sunt earum officiis laboriosam ut.</p>
                                        <button class="btn btn-light-primary text-primary" id="forgotPassword">Restablecer
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-body p-4">
                                    <h4 class="fw-semibold mb-3">Two-factor Authentication</h4>
                                    <div class="d-flex align-items-center justify-content-between pb-7">
                                        <p class="mb-0">Lorem ipsum, dolor sit amet consectetur adipisicing elit. Corporis sapiente sunt earum officiis laboriosam ut.</p>
                                        <button class="btn btn-primary">Enable</button>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between py-3 border-top">
                                        <div>
                                            <h5 class="fs-4 fw-semibold mb-0">Authentication App</h5>
                                            <p class="mb-0">Google auth app</p>
                                        </div>
                                        <button class="btn btn-light-primary text-primary">Setup</button>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between py-3 border-top">
                                        <div>
                                            <h5 class="fs-4 fw-semibold mb-0">Another e-mail</h5>
                                            <p class="mb-0">E-mail to send verification link</p>
                                        </div>
                                        <button class="btn btn-light-primary text-primary">Setup</button>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between py-3 border-top">
                                        <div>
                                            <h5 class="fs-4 fw-semibold mb-0">SMS Recovery</h5>
                                            <p class="mb-0">Your phone number or something</p>
                                        </div>
                                        <button class="btn btn-light-primary text-primary">Setup</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-body p-4">
                                    <div class="bg-light rounded-1 p-6 d-inline-flex align-items-center justify-content-center mb-3">
                                        <i class="ti ti-device-laptop text-primary d-block fs-7" width="22" height="22"></i>
                                    </div>
                                    <h5 class="fs-5 fw-semibold mb-0">Devices</h5>
                                    <p class="mb-3">Lorem ipsum dolor sit amet consectetur adipisicing elit Rem.</p>
                                    <button class="btn btn-primary mb-4">Sign out from all devices</button>
                                    <div class="d-flex align-items-center justify-content-between py-3 border-bottom">
                                        <div class="d-flex align-items-center gap-3">
                                            <i class="ti ti-device-mobile text-dark d-block fs-7" width="26" height="26"></i>
                                            <div>
                                                <h5 class="fs-4 fw-semibold mb-0">iPhone 14</h5>
                                                <p class="mb-0">London UK, Oct 23 at 1:15 AM</p>
                                            </div>
                                        </div>
                                        <a class="text-dark fs-6 d-flex align-items-center justify-content-center bg-transparent p-2 fs-4 rounded-circle" href="javascript:void(0)">
                                            <i class="ti ti-dots-vertical"></i>
                                        </a>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between py-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <i class="ti ti-device-laptop text-dark d-block fs-7" width="26" height="26"></i>
                                            <div>
                                                <h5 class="fs-4 fw-semibold mb-0">Macbook Air</h5>
                                                <p class="mb-0">Gujarat India, Oct 24 at 3:15 AM</p>
                                            </div>
                                        </div>
                                        <a class="text-dark fs-6 d-flex align-items-center justify-content-center bg-transparent p-2 fs-4 rounded-circle" href="javascript:void(0)">
                                            <i class="ti ti-dots-vertical"></i>
                                        </a>
                                    </div>
                                    <button class="btn btn-light-primary text-primary w-100 py-1">Need Help ?</button>
                                </div>
                            </div>
                        </div>
                </div>
            </div>
        </div>
    </div>


    </div>

@endsection



@push('scripts')

    <script type="text/javascript">
        Dropzone.autoDiscover = false;

        $(document).ready(function() {

            jQuery.validator.addMethod(
                'emailExt',
                function (value, element, param) {
                    return value.match(
                        /^(([^<>()[\]\.,;:\s@\"]+(\.[^<>()[\]\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\.,;:\s@\"]+\.)+[^<>()[\]\.,;:\s@\"]{2,})$/i,
                    )
                },
                'Porfavor ingrese email valido',
            );

            $("#formInformation").validate({
                submit: false,
                ignore: ".ignore",
                rules: {
                    firstname: {
                        required: true,
                        minlength: 3,
                        maxlength: 100,
                    },
                    lastname: {
                        required: true,
                        minlength: 3,
                        maxlength: 100,
                    },
                    identification: {
                        required: false,
                        minlength: 3,
                        maxlength: 100,
                    },
                    cellphone: {
                        required: false,
                        number: true,
                        minlength: 6,
                        maxlength: 10,
                    },
                    email: {
                        required: true,
                        email: true,
                        emailExt: true,
                    },
                    address: {
                        required: false,
                        minlength: 3,
                        maxlength: 100,
                    },
                    available: {
                        required: true,
                    },
                    role: {
                        required: true,
                    },
                },
                messages: {
                    firstname: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 3 caracter",
                        maxlength: "Debe contener al menos 100 caracter",
                    },
                    lastname: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 3 caracter",
                        maxlength: "Debe contener al menos 100 caracter",
                    },
                    identification: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 3 caracter",
                        maxlength: "Debe contener al menos 100 caracter",
                    },
                    cellphone: {
                        required: "El parametro es necesario.",
                        number: 'Solo se puede ingresar números.',
                        minlength: "Debe contener al menos 6 caracter",
                        maxlength: "Debe contener al menos 10 caracter",
                    },
                    email: {
                        required: 'Tu email ingresar correo electrónico es necesario.',
                        email: 'Por favor, introduce una dirección de correo electrónico válida.',
                    },
                    address: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 3 caracter",
                        maxlength: "Debe contener al menos 100 caracter",
                    },
                },
                submitHandler: function(form) {

                    var $form = $('#formInformation');
                    var formData = new FormData($form[0]);
                    var slack = $("#slack").val();
                    var firstname = $("#firstname").val();
                    var lastname = $("#lastname").val();
                    var identification = $("#identification").val();
                    var cellphone = $("#cellphone").val();
                    var email = $("#email").val();
                    var address = $("#address").val();
                    var available = $("#available").val();
                    var role = $("#roles").val();

                    formData.append('slack', slack);
                    formData.append('firstname', firstname);
                    formData.append('lastname', lastname);
                    formData.append('identification', identification);
                    formData.append('cellphone', cellphone);
                    formData.append('email', email);
                    formData.append('address', address);
                    formData.append('available', available);
                    formData.append('role', role);

                    $.ajax({
                        url: "{{ route('support.users.information') }}",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        type: "POST",
                        contentType: false,
                        processData: false,
                        data: formData,
                        success: function(response) {

                            if(response.success == true){

                                toastr.success("Se ha editado correctamente.", "Operación exitosa", {
                                    closeButton: true,
                                    progressBar: true,
                                    positionClass: "toast-bottom-right"
                                });

                                setTimeout(function() {
                                    window.location.href = "{{ route('support.users') }}";
                                }, 2000);

                            }else{

                                toastr.warning("Se ha generado un error.", "Operación fallida", {
                                    closeButton: true,
                                    progressBar: true,
                                    positionClass: "toast-bottom-right"
                                });

                                error = response.message;
                                $('.errors').removeClass('d-none');
                                $('.errors').html(error);
                            }

                        }
                    });

                }

            });

            $("#formNotification").validate({
                submit: false,
                ignore: ".ignore",
                rules: {
                    role: {
                        required: false,
                    },
                },
                messages: {
                    role: {
                        required: "El parametro es necesario.",
                    },
                },
                submitHandler: function(form) {

                    var $form = $('#formNotification');
                    var formData = new FormData($form[0]);
                    var slack = $("#slack").val();
                    var newsletter_notification = $("#newsletter_notification").is(':checked');
                    var order_notification = $("#order_notification").is(':checked');
                    var status_notification = $("#status_notification").is(':checked');
                    var email_notification = $("#email_notification").is(':checked');
                    var cookies_notification = $("#cookies_notification").is(':checked');

                    formData.append('slack', slack);
                    formData.append('newsletter_notification', newsletter_notification);
                    formData.append('order_notification', order_notification);
                    formData.append('status_notification', status_notification);
                    formData.append('email_notification', email_notification);
                    formData.append('cookies_notification', cookies_notification);

                    $.ajax({
                        url: "{{ route('support.users.notification') }}",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        type: "POST",
                        contentType: false,
                        processData: false,
                        data: formData,
                        success: function(response) {

                            if(response.success == true){

                                toastr.success("Se ha editado correctamente.", "Operación exitosa", {
                                    closeButton: true,
                                    progressBar: true,
                                    positionClass: "toast-bottom-right"
                                });

                                setTimeout(function() {
                                    window.location.href = "{{ route('support.users') }}";
                                }, 2000);

                            }else{

                                toastr.warning("Se ha generado un error.", "Operación fallida", {
                                    closeButton: true,
                                    progressBar: true,
                                    positionClass: "toast-bottom-right"
                                });

                                error = response.message;
                                $('.errors').removeClass('d-none');
                                $('.errors').html(error);
                            }

                        }
                    });

                }

            });

            $("#formPassword").validate({
                submit: false,
                ignore: ".ignore",
                rules: {
                    password: {
                        required: true,
                        minlength: 3
                    },
                    password_confirmation: {
                        required: true,
                        minlength: 3,
                        equalTo: "#password"
                    },
                },
                messages: {
                    password: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 6 caracter",
                        maxlength: "Debe contener al menos 10 caracter",
                    },password_confirmation: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 3 caracter",
                        maxlength: "Debe contener al menos 100 caracter",
                        equalTo: "Por favor, introduzca el mismo valor de nuevo."
                    },
                },
                submitHandler: function(form) {

                    var $form = $('#formPassword');
                    var formData = new FormData($form[0]);
                    var slack = $("#slack").val();
                    var password = $("#password").val();
                    var password_confirmation = $("#password_confirmation").val();

                    formData.append('slack', slack);
                    formData.append('password', password);
                    formData.append('password_confirmation', password_confirmation);

                    $.ajax({
                        url: "{{ route('support.users.resetpassword') }}",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        type: "POST",
                        contentType: false,
                        processData: false,
                        data: formData,
                        success: function(response) {

                            if(response.success == true){

                                toastr.success("Se ha editado correctamente.", "Operación exitosa", {
                                    closeButton: true,
                                    progressBar: true,
                                    positionClass: "toast-bottom-right"
                                });


                            }else{

                                toastr.warning("Se ha generado un error.", "Operación fallida", {
                                    closeButton: true,
                                    progressBar: true,
                                    positionClass: "toast-bottom-right"
                                });

                                error = response.message;
                                $('.errors').removeClass('d-none');
                                $('.errors').html(error);
                            }

                        }
                    });

                }

            });

            $("#forgotPassword").on('click', function() {
                var slack = $("#slack").val();

                $.ajax({
                    url: "{{ route('support.users.forgotpassword') }}",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    type: "POST",
                    data: {
                        slack: slack // Correctly passing data as an object
                    },
                    success: function(response) {

                        if (response.success === true) {

                            toastr.success("Se ha enviado la notificación correctamente.", "Operación exitosa", {
                                closeButton: true,
                                progressBar: true,
                                positionClass: "toast-bottom-right"
                            });




                        } else {

                            toastr.warning("Se ha generado un error.", "Operación fallida", {
                                closeButton: true,
                                progressBar: true,
                                positionClass: "toast-bottom-right"
                            });

                            let error = response.message;
                            $('.errors').removeClass('d-none');
                            $('.errors').html(error);
                        }
                    }
                });
            });

        });

    </script>

@endpush




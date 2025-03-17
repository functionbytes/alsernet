@extends('layouts.managers')

@section('content')

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formSSL" enctype="multipart/form-data" role="form" onSubmit="return false">

                    {{ csrf_field() }}

                    <div class="card-body border-top">
                        <div class="">
                            <h5 class="col-12 mb-0">Configuración de chats</h5>
                            <p class="col-12">(La sección "Configuración de datos SSL" facilita el establecimiento de una conexión segura entre la aplicación y el servidor del usuario a través de certificados SSL. Los administradores deben ingresar los datos del certificado SSL y la clave SSL obtenidos de su servidor en las áreas de texto designadas. Una vez que se proporcionan y guardan los datos, la sección pasa a un modo deshabilitado, lo que indica que la conexión se ha establecido correctamente. Esto garantiza la transmisión segura de datos entre la aplicación y el servidor, lo que mejora la protección e integridad de los datos.)</p>
                        </div>

                        <div class="row mt-20">

                            <div class="col-12 ">
                                <div class="mb-4 mt-3">
                                    <label  class="control-label col-form-label ">SSL Certificate</label>
                                    <textarea type="text" name="sslcertificate" rows="10" class="form-control @error('sslcertificate') is-invalid @enderror" placeholder="Enter SSL certificate" @if(setting('serverssldomainname') == $domainname) readonly @endif>{{setting('serversslcertificate')}}</textarea>
                                </div>
                            </div>

                            <div class="col-12 ">
                                <div class="mb-4 mt-3">
                                    <label  class="control-label col-form-label ">SSL Key</label>
                                    <textarea type="text" name="sslkey" rows="10" class="form-control @error('sslkey') is-invalid @enderror" placeholder="Enter SSL certificate" @if(setting('serverssldomainname') == $domainname) readonly @endif>{{setting('serversslkey')}}</textarea>
                                </div>
                            </div>

                            <div class="col-12">
                            <div class="border-top pt-1 mt-4">
                                <button type="submit" class="btn btn-info  px-4 waves-effect waves-light mt-2 w-100">
                                        Guardar
                                </button>
                            </div>
                        </div>

                        </div>

                    </div>
                    
                </form>
            </div>

        </div>
    </div>

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formTickets" enctype="multipart/form-data" role="form" onSubmit="return false">

                    {{ csrf_field() }}

                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0">Configuración chat live</h5>
                        </div>

                        <div class="row mt-20">

                            <div class="col-12">
                                <div class="mb-4 mt-3">
                                    <div class="row align-items-center">
                                        <div class=" col-sm-11 ">
                                            <label  class="control-label col-form-label ">Activar ticket de cierre automático</label>
                                            <p class="card-subtitle mb-3 mt-0">(Introduzca el número de puerto deseado que le envió su proveedor de alojamiento en el campo de entrada, por ejemplo, "8443" o "8445". Asegúrese de que el número de puerto proporcionado esté disponible y abierto en su servidor. Si el puerto no está abierto, póngase en contacto con su proveedor de alojamiento para que lo haga accesible en su servidor..)</p>
                                        </div>
                                        <div class="col-sm-1 justify-content-end d-flex align-items">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" name="liveChat_hidden" id="liveChat_hidden" class=" toggle-class onoffswitch2-checkbox" @if(setting('liveChatHidden') == 'false') checked="" @endif @if(setting('serverssldomainname') == null || setting('serverssldomainname') != $domainname) disabled=true; @endif/>
                                            </div>

                                        </div>
                                    </div>

                                    <div class="row align-items-center">
                                        <div class="col-sm-3">
                                            <input type="text" class="form-control" id="liveChatPort"  name="liveChatPort" value="{{ setting('auto_close_ticket_time') }}" value="{{ old('liveChatPort', setting('liveChatPort')) }}" @if(setting('serverssldomainname') == null || setting('serverssldomainname') != $domainname) readonly @endif>
                                        </div>
                                        <label for="liveChatPort" class="form-label fw-semibold col-sm-9 col-form-label">Introduzca el número de puerto para LiveChat</label>
                                    </div>

                                </div>
                            </div>


                            <div class="col-12">
                            <div class="border-top pt-1 mt-4">
                                <button type="submit" class="btn btn-info  px-4 waves-effect waves-light mt-2 w-100">
                                        Guardar
                                </button>
                            </div>
                        </div>

                        </div>

                    </div>

                </form>
            </div>

        </div>

    </div>

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">
                <form id="formSSL" enctype="multipart/form-data" role="form" onSubmit="return false">

                    {{ csrf_field() }}

                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0">Guión de chat en vivo</h5>
                        </div>

                        <div class="row mt-20">

                            <div class="col-12 ">
                                <div class="mb-4 mt-3">
                                    <p class="card-subtitle mb-3 mt-0">((Copie el enlace de la etiqueta de script provisto. Vaya a "Configuración de la aplicación->Chat externo" y pegue el enlace de la etiqueta de script copiado en el área de texto "Chat externo" y habilite el interruptor "Habilitar/Deshabilitar chat externo". Los administradores pueden integrar sin problemas el ícono de chat en la página de inicio de la aplicación, mejorando la participación del cliente y las capacidades de soporte. Además, también puede usar este enlace de etiqueta de script a continuación en cualquier otro sitio web, pero asegúrese de pegar este fragmento de código justo antes de la etiqueta </body>).)</p>
                                    <input type="text" class="form-control liveChatScriptLink" name="mail_username" readonly
                                           id="mail_username" value="<script src='{{ url('') }}/build/assets/plugins/livechat/liveChat.js' wsPort='{{ setting('liveChatPort') }}' domainName='{{ url('') }}' defer></script>" autocomplete="off">
                                </div>
                            </div>

                        </div>

                    </div>

                </form>
            </div>

        </div>

    </div>


    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formTickets" enctype="multipart/form-data" role="form" onSubmit="return false">

                    {{ csrf_field() }}

                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0">Configuración de archivo de chat en vivo del cliente</h5>
                        </div>

                        <div class="row mt-20">

                            <div class="col-12">
                                <div class="mb-4 mt-3">
                                    <div class="row align-items-center">
                                        <div class=" col-sm-11 ">
                                            <label  class="control-label col-form-label ">Carga de archivos de clientes</label>
                                            <p class="card-subtitle mb-3 mt-0">(Introduzca el número de puerto deseado que le envió su proveedor de alojamiento en el campo de entrada, por ejemplo, "8443" o "8445". Asegúrese de que el número de puerto proporcionado esté disponible y abierto en su servidor. Si el puerto no está abierto, póngase en contacto con su proveedor de alojamiento para que lo haga accesible en su servidor..)</p>
                                        </div>
                                        <div class="col-sm-1 justify-content-end d-flex align-items">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox"  @if (setting('liveChatFileUpload') == '1') checked="" @endif name="liveChatFileUpload" />
                                            </div>

                                        </div>
                                    </div>

                                    <div class="row align-items-center mb-3">
                                        <div class="col-sm-3">
                                            <input type="number" class="form-control"  maxlength="2" class="form-control @error('livechatMaxFileUpload') is-invalid @enderror"  name="livechatMaxFileUpload" value="{{ setting('livechatMaxFileUpload') }}">
                                        </div>
                                        <label for="liveChatPort" class="form-label fw-semibold col-sm-9 col-form-label">Carga máxima de archivos</label>
                                    </div>


                                    <div class="row align-items-center mb-3">
                                        <div class="col-sm-3">
                                            <input type="number" class="form-control" maxlength="2" class="form-control @error('livechatFileUploadMax') is-invalid @enderror"  name="livechatFileUploadMax" value="{{ setting('livechatFileUploadMax') }}">
                                        </div>
                                        <label for="liveChatPort" class="form-label fw-semibold col-sm-9 col-form-label">Tamaño máximo de carga de archivos del chat en vivo</label>
                                    </div>

                                    <div class="row align-items-center ">
                                        <div class="col-sm-3">
                                            <input type="text" class="form-control" id="tags" data-role="tagsinput" name="livechatFileUploadTypes" value="{{ setting('livechatFileUploadTypes') }}">
                                        </div>
                                        <label for="liveChatPort" class="form-label fw-semibold col-sm-9 col-form-label">Tipos de archivos de chat en vivo permitidos</label>
                                    </div>

                                </div>
                            </div>


                            <div class="col-12">
                            <div class="border-top pt-1 mt-4">
                                <button type="submit" class="btn btn-info  px-4 waves-effect waves-light mt-2 w-100">
                                        Guardar
                                </button>
                            </div>
                        </div>

                        </div>

                    </div>

                </form>
            </div>

        </div>

    </div>




    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formTickets" enctype="multipart/form-data" role="form" onSubmit="return false">

                    {{ csrf_field() }}

                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0">Configuración del archivo de chat en vivo del operador</h5>
                        </div>

                        <div class="row mt-20">

                            <div class="col-12">
                                <div class="mb-4 mt-3">
                                    <div class="row align-items-center">
                                        <div class=" col-sm-11 ">
                                            <label  class="control-label col-form-label ">Carga de archivos de clientes</label>
                                            <p class="card-subtitle mb-3 mt-0">(Introduzca el número de puerto deseado que le envió su proveedor de alojamiento en el campo de entrada, por ejemplo, "8443" o "8445". Asegúrese de que el número de puerto proporcionado esté disponible y abierto en su servidor. Si el puerto no está abierto, póngase en contacto con su proveedor de alojamiento para que lo haga accesible en su servidor..)</p>
                                        </div>
                                        <div class="col-sm-1 justify-content-end d-flex align-items">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox"  name="liveChatAgentFileUpload"  id="liveChatAgentFileUpload"  @if (setting('liveChatAgentFileUpload') == '1') checked="" @endif />
                                            </div>

                                        </div>
                                    </div>

                                    <div class="row align-items-center mb-3">
                                        <div class="col-sm-3">
                                            <input type="number" class="form-control"  type="number" maxlength="2"  name="AgentlivechatMaxFileUpload"  value="{{ setting('AgentlivechatMaxFileUpload') }}" >
                                        </div>
                                        <label for="liveChatPort" class="form-label fw-semibold col-sm-9 col-form-label">Carga máxima de archivos</label>
                                    </div>


                                    <div class="row align-items-center mb-3">
                                        <div class="col-sm-3">
                                            <input type="number" class="form-control" type="number" maxlength="2"  name="AgentlivechatMaxFileUpload" value="{{ setting('AgentlivechatMaxFileUpload') }}">
                                        </div>
                                        <label for="liveChatPort" class="form-label fw-semibold col-sm-9 col-form-label">Tamaño máximo de carga de archivos del chat en vivo</label>
                                    </div>

                                    <div class="row align-items-center ">
                                        <div class="col-sm-3">
                                            <input type="text" class="form-control" type="number" maxlength="2"  name="AgentlivechatFileUploadMax" value="{{ setting('AgentlivechatFileUploadMax') }}">
                                        </div>
                                        <label for="liveChatPort" class="form-label fw-semibold col-sm-9 col-form-label">Tipos de archivos de chat en vivo permitidos</label>
                                    </div>

                                </div>
                            </div>


                            <div class="col-12">
                            <div class="border-top pt-1 mt-4">
                                <button type="submit" class="btn btn-info  px-4 waves-effect waves-light mt-2 w-100">
                                        Guardar
                                </button>
                            </div>
                        </div>

                        </div>

                    </div>

                </form>
            </div>

        </div>

    </div>




    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formTickets" enctype="multipart/form-data" role="form" onSubmit="return false">

                    {{ csrf_field() }}

                    <div class="card-body border-top">
                        <div class="">
                            <h5 class="mb-0">Configuración del flujo de chat en vivo</h5>
                            <p class="card-subtitle mb-3 mt-2">(Si se selecciona "Para un solo usuario único", los clientes anteriores del chat en vivo no necesitarán pasar por el flujo del chat en vivo. Si se selecciona "Cada 24 horas", los clientes anteriores también tendrán que pasar por el flujo del chat en vivo).</p>
                        </div>

                        <div class="row mt-20">
                            <div class="col-12">
                                <div class="mb-4">
                                        <div class="row align-items-center">
                                            <div class="row justify-content-between g-2 ">
                                                    <div class="input-group">
                                                        <select name="liveChatFlowload" class="form-control select2-show-search select2">
                                                            <option value="every-24-hours" @if (setting('liveChatFlowload') == 'every-24-hours') selected="selected" @endif>Cada 24 horas </option>
                                                            <option value="for-a-single-unique-user"  @if (setting('liveChatFlowload') == 'for-a-single-unique-user') selected="selected" @endif>Para un único usuario</option>
                                                        </select>
                                                    </div>
                                            </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                            <div class="border-top pt-1 mt-4">
                                <button type="submit" class="btn btn-info  px-4 waves-effect waves-light mt-2 w-100">
                                        Guardar
                                </button>
                            </div>
                        </div>

                        </div>

                    </div>

                </form>
            </div>

        </div>

    </div>

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formTickets" enctype="multipart/form-data" role="form" onSubmit="return false">

                    {{ csrf_field() }}

                    <div class="card-body border-top">
                        <div class="">
                            <h5 class="mb-0">Tamaño del icono del chat en vivo</h5>
                            <p class="card-subtitle mb-3 mt-2">(Esta configuración determina el tamaño del ícono de chat que se muestra a los clientes: seleccionar "pequeño" muestra un ícono pequeño, mientras que "grande" muestra un ícono más grande)</p>
                        </div>

                        <div class="row mt-20">
                            <div class="col-12">
                                <div class="mb-4">
                                    <div class="row align-items-center">
                                        <div class="row justify-content-between g-2 ">
                                            <div class="input-group">
                                                <select name="livechatIconSize" id="livechatIconSize" class="form-control select2 select2-show-search" required>
                                                    <option value="small" @if (setting('livechatIconSize') == 'small') selected="selected" @endif> Pequeńo</option>
                                                    <option value="large" @if (setting('livechatIconSize') == 'large') selected="selected" @endif> Grande</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                            <div class="border-top pt-1 mt-4">
                                <button type="submit" class="btn btn-info  px-4 waves-effect waves-light mt-2 w-100">
                                        Guardar
                                </button>
                            </div>
                        </div>

                        </div>

                    </div>

                </form>
            </div>

        </div>

    </div>

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formTickets" enctype="multipart/form-data" role="form" onSubmit="return false">

                    {{ csrf_field() }}

                    <div class="card-body border-top">
                        <div class="">
                            <h5 class="mb-0">Posición en LiveChat</h5>
                            <p class="card-subtitle mb-3 mt-2">(Esta configuración determina la posición del icono de chat en el navegador).</p>
                        </div>

                        <div class="row mt-20">
                            <div class="col-12">
                                <div class="mb-4">
                                    <div class="row align-items-center">
                                        <div class="row justify-content-between g-2 ">
                                            <div class="input-group">
                                                <select name="livechatPosition" id="livechatPosition" class="form-control select2 select2-show-search" required>
                                                    <option value="right" @if (setting('livechatPosition') == 'right') selected="selected" @endif>Derecha</option>
                                                    <option value="left" @if (setting('livechatPosition') == 'left') selected="selected" @endif>Izquierda</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                            <div class="border-top pt-1 mt-4">
                                <button type="submit" class="btn btn-info  px-4 waves-effect waves-light mt-2 w-100">
                                        Guardar
                                </button>
                            </div>
                        </div>

                        </div>

                    </div>

                </form>
            </div>

        </div>

    </div>




    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formTickets" enctype="multipart/form-data" role="form" onSubmit="return false">

                    {{ csrf_field() }}

                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0">Configuración de chat en vivo en línea/fuera de línea</h5>
                        </div>


                        <div class="row mt-20 border-top mt-4">

                            <div class="col-12 ">
                                <div class="mb-4 mt-3">
                                    <label  class="control-label col-form-label ">Mensaje de estado en línea </label>
                                    <p class="card-subtitle mb-3 mt-0">(El mensaje en la entrada a continuación se enviará a los clientes como saludo cuando los operadores de chat en vivo estén en línea).</p>
                                    <input type="text" class="form-control"  name="OnlineStatusMessage" id="OnlineStatusMessage"  value="{{ setting('OnlineStatusMessage') }}" id="OnlineStatusMessage" autocomplete="off">
                                </div>
                            </div>

                        </div>


                        <div class="row border-top pt-4 align-items-center">

                            <div class=" col-sm-11 ">
                                <label  class="control-label col-form-label ">Mostrar chat cuando estés sin conexión</label>
                                <p class="card-subtitle mb-3 mt-0">(si está habilitada, esta configuración permite que los clientes vean el ícono de chat en vivo incluso durante las horas sin conexión).</p>
                            </div>
                            <div class="col-sm-1 justify-content-end d-flex align-items">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="offlineDisplayLiveChat" id="offlineDisplayLiveChat"  @if (setting('offlineDisplayLiveChat') == '1') checked="" @endif id="contact" />
                                </div>

                            </div>
                        </div>


                        <div class="row mt-20 border-top mt-4">

                                <div class="col-12 ">
                                    <div class="mb-4 mt-3">
                                        <label  class="control-label col-form-label">Mensaje de estado fuera de línea</label>
                                        <p class="card-subtitle mb-3 mt-0">(Este mensaje se enviará a los clientes como saludo cuando inicien un nuevo chat fuera del horario comercial o en días festivos).</p>
                                        <input type="text" class="form-control" name="OfflineStatusMessage" value="{{ setting('OfflineStatusMessage') }}" id="OfflineStatusMessage" autocomplete="off"/>
                                    </div>
                                </div>
                        </div>

                        <div class="row mt-20 border-top mt-4">

                                <div class="col-12 ">
                                    <div class="mb-4 mt-3">
                                        <label  class="control-label col-form-label ">Mensaje fuera de línea</label>
                                        <p class="card-subtitle mb-3 mt-0">(Este mensaje se enviará a los clientes como respuesta a su mensaje fuera del horario comercial o en días festivos).</p>
                                        <textarea type="text" class="form-control " placeholder="Subject" name="OfflineMessage" value="Consent" id="OfflineMessage" autocomplete="off" rows="3">{{ setting('OfflineMessage') }}</textarea>
                                    </div>
                                </div>
                        </div>

                                <div class="col-12">
                                    <div class="action-form border-top mt-4">
                                        <div class="text-center">
                                            <button type="submit" class="btn btn-info  px-4 waves-effect waves-light mt-2 w-100">
                                                Guardar
                                            </button>
                                        </div>
                                    </div>
                                </div>

                        </div>


                </form>
            </div>

        </div>

    </div>






    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formTickets" enctype="multipart/form-data" role="form" onSubmit="return false">

                    {{ csrf_field() }}

                    <div class="card-body border-top">

                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0">Resolución automática</h5>
                        </div>

                        <div class="row mt-20 border-top mb-4 mt-4 pt-4  align-items-center">

                            <div class=" col-sm-11 ">
                                <label  class="control-label col-form-label ">Habilitar resolución automática</label>
                                <p class="card-subtitle mb-3 mt-0">(Si activa esta función, se enviará una notificación al correo electrónico del cliente para informarle que se ha enviado una respuesta cuando no haya respuesta de su parte dentro del tiempo especificado en el primer cuadro, medido en minutos. El chat se cerrará automáticamente si el cliente no responde al correo electrónico anterior dentro del tiempo especificado en la segunda entrada).</p>

                            </div>
                            <div class="col-sm-1 justify-content-end d-flex align-items">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="enableAutoSlove"   name="enableAutoSlove"  @if (setting('enableAutoSlove') == '1') checked="" @endif/>
                                </div>

                            </div>
                        </div>


                        <div class="row mt-20">

                            <div class="col-12">
                                <div class="mb-4 mt-3">

                                    <div class="row align-items-center mb-3">
                                        <div class="col-sm-3">
                                            <input type="number" maxlength="2"
                                                   class="form-control @error('autoSloveEmailTimer') is-invalid @enderror"  name="autoSloveEmailTimer" value="{{ setting('autoSloveEmailTimer') }}">
                                        </div>
                                        <label for="liveChatPort" class="form-label fw-semibold col-sm-9 col-form-label">Temporizador sin respuesta del remitente del correo electrónico del cliente</label>
                                    </div>


                                    <div class="row align-items-center mb-3">
                                        <div class="col-sm-3">
                                            <input  type="number" maxlength="2"x class="form-control @error('autoSloveCloseTimer') is-invalid @enderror" name="autoSloveCloseTimer" value="{{ setting('autoSloveCloseTimer') }}">
                                        </div>
                                        <label for="liveChatPort" class="form-label fw-semibold col-sm-9 col-form-label">Después de enviar el correo electrónico, resuelva el temporizador.</label>
                                    </div>

                                </div>
                            </div>


                            <div class="col-12">
                            <div class="border-top pt-1 mt-4">
                                <button type="submit" class="btn btn-info  px-4 waves-effect waves-light mt-2 w-100">
                                        Guardar
                                </button>
                            </div>
                        </div>

                        </div>

                    </div>

                </form>
            </div>

        </div>

    </div>



    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formTickets" enctype="multipart/form-data" role="form" onSubmit="return false">

                    {{ csrf_field() }}

                    <div class="card-body border-top">

                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0">Eliminación automática del chat en vivo</h5>
                        </div>

                        <div class="row mt-20 border-top mb-4 mt-4 pt-4  align-items-center">

                            <div class=" col-sm-11 ">
                                <label  class="control-label col-form-label ">Eliminación automática del chat en vivo</label>
                                <p class="card-subtitle mb-3 mt-0">(si está habilitado, los chats con más años de antigüedad que la cantidad especificada de días se eliminarán automáticamente).</p>

                            </div>
                            <div class="col-sm-1 justify-content-end d-flex align-items">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="autodeletelivechat" name="AUTO_DELETE_LIVECHAT_ENABLE" value="on"  @if (setting('AUTO_DELETE_LIVECHAT_ENABLE') == 'on') checked="" @endif/>
                                </div>

                            </div>
                        </div>


                        <div class="row mt-20">

                            <div class="col-12">
                                <div class="mb-4 mt-3">

                                    <div class="row align-items-center mb-3">
                                        <div class="col-sm-3">
                                            <input  type="number" maxlength="2" class="form-control wd-5 w-lg-max-30 ms-2"
                                                    name="AUTO_DELETE_LIVECHAT_IN_MONTHS"
                                                    value="{{ old('AUTO_DELETE_LIVECHAT_IN_MONTHS', setting('AUTO_DELETE_LIVECHAT_IN_MONTHS')) }}"
                                                    min="0" oninput="validity.valid||(value='');">
                                        </div>
                                        <label for="liveChatPort" class="form-label fw-semibold col-sm-9 col-form-label">Eliminación automática de chats en vivo en cuestión de días</label>
                                    </div>


                                </div>
                            </div>


                            <div class="col-12">
                            <div class="border-top pt-1 mt-4">
                                <button type="submit" class="btn btn-info  px-4 waves-effect waves-light mt-2 w-100">
                                        Guardar
                                </button>
                            </div>
                        </div>

                        </div>

                    </div>

                </form>
            </div>

        </div>

    </div>





    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formTickets" enctype="multipart/form-data" role="form" onSubmit="return false">

                    {{ csrf_field() }}

                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0">Comentarios del chat en vivo</h5>
                        </div>


                        <div class="row mt-20 border-top mt-4">

                            <div class="col-12 ">
                                <div class="mb-4 mt-3">
                                    <label  class="control-label col-form-label ">Pregunta de retroalimentación </label>
                                    <p class="card-subtitle mb-3 mt-0">(Esta es la pregunta de retroalimentación para un cliente cuando está cerca del chat, se mostrará junto con la calificación).</p>
                                    <input type="text" class="form-control @error('LivechatCustFeedbackQuestion') is-invalid @enderror" placeholder="Enter Feedback question"
                                           name="LivechatCustFeedbackQuestion" value="{{ setting('LivechatCustFeedbackQuestion') }}"
                                           id="LivechatCustFeedbackQuestion" autocomplete="off">
                                </div>
                            </div>

                        </div>



                        <div class="row mt-20 border-top mt-4">

                            <div class="col-12 ">
                                <div class="mb-4 mt-3">
                                    <label  class="control-label col-form-label">Opciones de comentarios</label>
                                    <p class="card-subtitle mb-3 mt-0">(esto será visible para los clientes cuando intenten cerrar la conversación en el formulario de comentarios. Muestra una pregunta y las opciones correspondientes para que los clientes brinden sus comentarios).</p>
                                    <input type="text" class="form-control " id="tags" data-role="tagsinput" name="livechatFeedbackDropdown" value="{{ setting('livechatFeedbackDropdown') }}">
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="border-top pt-1 mt-4">
                                <button type="submit" class="btn btn-info  px-4 waves-effect waves-light mt-2 w-100">
                                        Guardar
                                </button>
                            </div>
                        </div>

                    </div>


                </form>
            </div>

        </div>

    </div>



    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formTickets" enctype="multipart/form-data" role="form" onSubmit="return false">

                    {{ csrf_field() }}

                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0">Establecer un texto cuando varios operadores están en línea</h5>
                        </div>


                        <div class="row mt-20 border-top mt-4">

                            <div class="col-12 ">
                                <div class="mb-4 mt-3">
                                    <label  class="control-label col-form-label ">Texto de varios operadores </label>
                                    <p class="card-subtitle mb-3 mt-0">(Cuando hay varios operadores de chat en vivo, los clientes en la ventana de chat en vivo verán el texto proporcionado en el campo de entrada a continuación).</p>
                                    <input  type="text" class="form-control @error('LivechatCustWelcomeMsg') is-invalid @enderror" placeholder="Enter Feedback question"
                                    name="LivechatCustWelcomeMsg" value="{{ setting('LivechatCustWelcomeMsg') }}"
                                    id="LivechatCustWelcomeMsg" autocomplete="off">
                                </div>
                            </div>

                        </div>


                        <div class="col-12">
                            <div class="border-top pt-1 mt-4">
                                <button type="submit" class="btn btn-info  px-4 waves-effect waves-light mt-2 w-100">
                                        Guardar
                                </button>
                            </div>
                        </div>

                    </div>


                </form>
            </div>

        </div>

    </div>






@endsection



@push('scripts')

    <script>
        if (Notification.permission == 'denied' || Notification.permission == 'default') {
            document.querySelectorAll(".notificationButton").forEach((ele) => {
                ele.classList.remove("d-none")
                ele.onclick = () => {
                    Notification.requestPermission().then(function(permission) {
                        if (permission === "granted") {
                            toastr.success("Notification permission granted!")
                            document.querySelectorAll(".notificationButton").forEach((ele) => {
                                ele.classList.add("d-none")
                            })
                        }
                    });
                }
            })
        }

        // To copy in the Click Bord
        document.querySelector(".liveChatScriptLinkCopyBtn").onclick = () => {
            var copyText = document.querySelector(".liveChatScriptLink");

            if (navigator.clipboard) {
                copyText.select();
                copyText.setSelectionRange(0, 99999);

                navigator.clipboard.writeText(copyText.value)
                    .then(() => {
                        console.log('Text successfully copied to clipboard');
                    })
                    .catch(err => {
                        console.error('Unable to copy text to clipboard', err);
                    });
            } else {
                console.warn('Clipboard API not supported, copying to clipboard may not work.');
            }
        }

        // For The Notifications sound

        let currentAudio;
        document.querySelectorAll(".allSoundsList").forEach((element) => {
            element.onchange = (ele) => {
                // Stop the current audio if it exists
                if (currentAudio) {
                    currentAudio.pause();
                    currentAudio.currentTime = 0;
                }

                // Create a new audio element
                let audioElement = document.createElement('audio');
                audioElement.id = "audioPlayer";
                audioElement.innerHTML = `
                    <source src="{{ url('') }}/public/uploads/livechatsounds/${ele.target.value}">
                `;

                // Play the new audio
                audioElement.play();

                // Set the new audio as the current audio
                currentAudio = audioElement;
            };
        })

        // Notifications sound check Input Logic
        document.querySelector('[name="notificationsSounds"]').onclick = () => {
            if (document.querySelector('[name="notificationsSounds"]').checked) {
                document.querySelector("[name='newMessageWebNot']").disabled = false
                document.querySelector("[name='newChatRequestWebNot']").disabled = false
            } else {
                document.querySelector("[name='newMessageWebNot']").checked = false
                document.querySelector("[name='newChatRequestWebNot']").checked = false
                document.querySelector("[name='newMessageWebNot']").disabled = true
                document.querySelector("[name='newChatRequestWebNot']").disabled = true
            }
        }

        $(function() {
            (() => {
                // Csrf Field
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

                $('body').on('submit', '#livechat_enable_form', function(e) {
                    e.preventDefault();
                    var actionType = $('#livechatsubmitbtn').val();
                    var fewSeconds = 2;
                    $('#livechatsubmitbtn').html('Saving ... <i class="fa fa-spinner fa-spin"></i>');
                    $('#livechatsubmitbtn').prop('disabled', true);
                    setTimeout(function() {
                        $('#livechatsubmitbtn').prop('disabled', false);
                        $('#livechatsubmitbtn').html('Save Changes');
                    }, fewSeconds * 1000);
                    var formData = new FormData(this);
                    $.ajax({
                        type: 'POST',
                        url: '{{ url('admin/livechat/livechat-credentials') }}',
                        data: formData,
                        cache: false,
                        contentType: false,
                        processData: false,

                        success: (data) => {
                            $('#liveChatPortError').html('');
                            $('#livechat_enable_form').trigger("reset");
                            $('#livechatsubmitbtn').html('Save Changes');
                            toastr.success(data.success);
                            location.reload();
                        },
                        error: function(data) {
                            console.log('error data',data.responseJSON.error);
                            $('#liveChatPortError').html('');
                            $('#liveChatPortError').html(data?.responseJSON?.errors?.liveChatPort);
                            $('#livechatsubmitbtn').html('Save Changes');
                            if(data?.responseJSON?.error){
                                toastr.error(data.responseJSON.error);
                            }
                        }
                    });
                });
            })();
        })

    </script>

    <script type="text/javascript">
        Dropzone.autoDiscover = false;

        $(document).ready(function() {

            $("#formSSL").validate({
                submit: false,
                ignore: ".ignore",
                rules: {
                    sslcertificate: {
                        required: true,
                    },
                    sslkey: {
                        required: true,
                    },
                },
                messages: {
                    sslcertificate: {
                        required: "El parametro es necesario.",
                    },
                    sslkey: {
                        required: "El parametro es necesario.",
                    },
                },
                submitHandler: function(form) {

                    var $form = $('#formSSL');
                    var formData = new FormData($form[0]);
                    var sslcertificate = $("#sslcertificate").val();
                    var sslkey = $("#sslkey").val();

                    formData.append('sslcertificate', sslcertificate);
                    formData.append('sslkey', sslkey);

                    $.ajax({
                        url: "{{ route(''manager.livechats.ssldstore') }}",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        type: "POST",
                        contentType: false,
                        processData: false,
                        data: formData,
                        success: function(data) {

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



        });

    </script>

@endpush






@extends('layouts.managers')

@php
    use App\Models\Setting;
    use App\Library\Tool;
@endphp

@section('content')

    @include('managers.includes.card', ['title' => 'Importar suscripciones - '. $import->title])

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <div class="card-body border-top">

                    <div class="row">

                        <form id="importForm" action="{{ route('manager.subscribers.import.dispatch', ['import_uid' => $import->uid]) }}" method="POST" class="ajax_upload_form form-validate-jquery">

                            {{ csrf_field() }}

                            <div class="upload_file before">

                                <div class="form-group row ">

                                    <p class="card-subtitle mb-3 mt-3">
                                        {!! 'El tamaño máximo de archivo de carga del servidor está limitado a ' . Tool::maxFileUploadInBytes() . 'B.
                                             Asegúrese de que su archivo de entrada no supere este límite.
                                             El tipo de archivo aceptable es CSV con una fila de encabezado que contenga los nombres de las columnas y los campos, como EMAIL, FIRST_NAME, LAST_NAME...
                                             Puede descargar un archivo de entrada de muestra aquí: <a href="' . url('files/csv_import_example.csv') . '" target="_blank">Ejemplo.csv</a>.' !!}
                                    </p>

                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label for="lists" class="control-label col-form-label">Listado a importar</label>
                                            @include('helpers.form_control', ['type' => 'file', 'label' => '', 'name' => 'file', 'value' => $import->uid])
                                        </div>
                                    </div>

                                </div>

                                <div class="border-top pt-1 mt-3">
                                    <button type="submit" class="btn btn-info px-4 waves-effect waves-light mt-2 w-100">
                                        Importar
                                    </button>
                                </div>
                                </div>


                            </div>

                            @foreach ($importNotifications as $notification)
                                <div class="errors">
                                    {!! $notification !!}
                                </div>
                            @endforeach

                            <div class="form-group processing hide">
                                <h4 style="margin-bottom: 20px" id="notice">La importación se está ejecutando en segundo plano, espere... <br>Puede navegar o incluso cerrar esta página y volver más tarde para comprobar el progreso.</h4>
                                <div id="errorBox" class="alert alert-danger" style="display: none; flex-direction: row; align-items: center; justify-content: space-between;">
                                    <div style="display: flex; flex-direction: row; align-items: center;">
                                        <div style="margin-right:15px">
                                            <i class="lnr lnr-circle-minus"></i>
                                        </div>
                                        <div style="padding-right: 40px">
                                            <h4>ERROR</h4>
                                            <p id="errorMsg"></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="progress">

                                    <div class="progress-bar text-bg-danger" style="width: 0%" role="progressbar">
                                        <span><span class="number">0</span>% Error</span>
                                    </div>

                                    <div class="progress-bar text-bg-success  progress-total active" style="width: 0%" role="progressbar">
                                        <span><span class="number">0</span>% Completado</span>
                                    </div>

                                </div>
                                <label style="margin-bottom:20px;font-style:italic;" id="bottomNotice"></label>

                                <a id="cancelBtn" class="btn btn-info px-4 waves-effect waves-light mt-2 w-100 cancel processing border-top pt-1 mt-3">
                                    Cancelar
                                </a>
                            </div>

                            <div class="form-group finish hide">
                                <div class="text-left">
                                    <a  href="{{ route('manager.subscribers') }}"  class="btn btn-info px-4 waves-effect waves-light mt-2 w-100">
                                       Listado
                                    </a>
                                    <a href="{{ route('manager.subscribers.create') }}" class="btn btn-info px-4 waves-effect waves-light mt-2 w-100 ">
                                        Importar suscripciones
                                    </a>
                                </div>
                            </div>

                        </form>


                    </div>
                </div>

            </div>

        </div>




        @endsection





        @push('scripts')

            <script type="text/javascript">
                var SubscriptionsImport = {

                    // Current import job if any
                    currentImportJobUid: null,
                    progressCheckUrl: null,
                    cancelUrl: null,
                    logDownloadUrl: null,

                    progressCheck: null,

                    // Update import progress
                    updateProgressBar: function(percentage, message) { // percentage from 0 to 100
                        var form = $("form.ajax_upload_form");
                        var bar =  $('.progress-total');

                        form.find("#bottomNotice").show();
                        form.find("#bottomNotice").html(message);
                        bar.find(".number").html(percentage);
                        bar.css({
                            width: (percentage) + '%'
                        });
                    },

                    resetCurrentJob: function() {
                        SubscriptionsImport.currentImportJobUid = null;
                        SubscriptionsImport.progressCheckUrl = null;
                        SubscriptionsImport.cancelUrl = null;
                        SubscriptionsImport.logDownloadUrl = null;
                    },

                    setCurrentJob: function(data) {
                        SubscriptionsImport.currentImportJobUid = data.currentImportJobUid;
                        SubscriptionsImport.progressCheckUrl = data.progressCheckUrl;
                        SubscriptionsImport.cancelUrl = data.cancelUrl;
                        SubscriptionsImport.logDownloadUrl = data.logDownloadUrl;
                    },

                    // Toggle: show progress bar, hide input upload bar
                    showProgressBar: function() {
                        // Also hide upload input
                        $(".before").addClass('hide');
                        $(".processing").removeClass('hide');
                        //$('#errorBox').hide();
                    },

                    hideProgressBar: function() {
                        // Also show upload input
                       // var form = $("form.ajax_upload_form");
                        form.find('.before').removeClass("hide");
                        form.find(".processing").addClass('hide');
                        //$('#errorBox').hide();
                    },

                    showCancelButton: function() {
                        $('#cancelBtn').removeClass('hide');
                    },

                    hideCancelButton: function() {
                        $('#cancelBtn').removeClass('hide');
                    },

                    checkProgress: function(completeAlert = true) {
                        var form = $("form.ajax_upload_form");
                        var bar = form.find('.progress-total');
                        var bar_s = form.find('.progress-success');
                        var bar_e = form.find('.progress-error');

                        $.ajax({
                            url : SubscriptionsImport.progressCheckUrl,
                            type: "GET",
                            success: function(result, textStatus, jqXHR) {
                                // Upgrade progress, no matter which status is
                                SubscriptionsImport.showProgressBar();
                                SubscriptionsImport.updateProgressBar(result.percentage, result.message);

                                if (result.status == "failed") {
                                    SubscriptionsImport.showFinishButtonBar();
                                    SubscriptionsImport.hideCancelButton();
                                    $("#notice").hide();
                                    $("#bottomNotice").hide();
                                    $('#errorBox').show();
                                    $('#errorMsg').html(result.error);
                                } else if (result.status == "done") {

                                    SubscriptionsImport.hideCancelButton();
                                    $("#notice").show();
                                    $("#notice").html('El proceso de exportación finalizó exitosamente. .');
                                    $('#bottomNotice').show();
                                    $("#bottomNotice").html(result.message);
                                    $('.progress-bar').addClass('success');
                                    $('.finish').removeClass('hide');
                                    $('.cancel').addClass("hide");
                                    $('.success').removeClass("hide");


                                    if (completeAlert) {

                                        toastr.success("El proceso de exportación finalizó exitosamente.", "Operación exitosa", {
                                            closeButton: true,
                                            progressBar: true,
                                            positionClass: "toast-bottom-right"
                                        });

                                    }

                                } else if (result.status == "cancelled") {
                                    /*
                                    SubscriptionsImport.hideProgressBar();
                                    form.find('.finish').addClass("hide");
                                    form.find('.success').removeClass("hide");
                                    */
                                } else if (result.status == "running" || result.status == "queued") {
                                    SubscriptionsImport.showProgressBar();
                                    SubscriptionsImport.progressCheck = setTimeout(function() {
                                        SubscriptionsImport.checkProgress();
                                    }, 2000);
                                } else {
                                    alert('Invalid result status');
                                    console.log(result);
                                }
                            }
                        });
                    },

                    showFinishButtonBar: function() {
                        $(".finish").removeClass('hide');
                    },

                    hideFinishButtonBar: function() {
                        $(".finish").addClass('hide');
                    },

                    stopCheckingProgress: function() {
                        clearTimeout(SubscriptionsImport.progressCheck);
                    },

                    upload: function() {
                        var form = $("form.ajax_upload_form");

                        if (!form.valid()) {
                            $("label.error").insertAfter(".uploader");
                            return false;
                        }

                        var formData = new FormData(form[0]); // Make the upload form and submit
                        var url = form.attr('action');
                        SubscriptionsImport.showProgressBar();
                        SubscriptionsImport.updateProgressBar(0, "A partir de...");

                        $.ajax({
                            url: url,
                            type: 'POST',
                            data: formData,
                            success: function (data) {
                                SubscriptionsImport.setCurrentJob(data);
                                SubscriptionsImport.checkProgress();
                            },
                            cache: false,
                            contentType: false,
                            processData: false
                        }).fail(function( jqXHR, textStatus, errorThrown ) {
                            SubscriptionsImport.hideProgressBar();
                            notify({
                                title: "{{ trans('messages.notify.error') }}",
                                message: errorThrown,
                            });
                        });
                    },

                    importAnotherFile: function() {
                        // Same as cancel, simply delete the only import job associated with list
                        SubscriptionsImport.stopCheckingProgress();
                        var token = $('form#importForm').find('input[name="_token"]').val();

                        $.ajax({
                            url : SubscriptionsImport.cancelUrl,
                            type: "POST",
                            data: {
                                '_token': token
                            },
                            success: function(result, textStatus, jqXHR) {
                                SubscriptionsImport.hideFinishButtonBar();
                                SubscriptionsImport.hideProgressBar();
                                SubscriptionsImport.resetCurrentJob();
                            }
                        }).fail(function(jqXHR, textStatus, errorThrown) {
                            notify({
                                title: "{{ trans('messages.notify.error') }}",
                                message: errorThrown,
                            });
                            SubscriptionsImport.checkProgress();
                            return false;
                        });
                    },

                    cancel: function() {
                        SubscriptionsImport.stopCheckingProgress();
                        var token = $('form#importForm').find('input[name="_token"]').val();

                        $.ajax({
                            url : SubscriptionsImport.cancelUrl,
                            type: "POST",
                            data: {
                                '_token': token
                            },
                            success: function(result, textStatus, jqXHR) {
                                SubscriptionsImport.hideFinishButtonBar();
                                SubscriptionsImport.hideProgressBar();
                                SubscriptionsImport.resetCurrentJob();
                            }
                        }).fail(function(jqXHR, textStatus, errorThrown) {
                            new Dialog('alert', {
                                title: "{{ trans('messages.notify.error') }}",
                                message: errorThrown,
                            });

                            // Resume progress checking
                            SubscriptionsImport.checkProgress();

                            return false;
                        });
                    }
                }

                $(document).ready(function() {

                    // Event bindings
                    $(document).on("submit", "form.ajax_upload_form", function() {
                        SubscriptionsImport.upload();
                        return false; // avoid triggering the click action of <A>
                    });

                    $(document).on("click", ".retry", function() {
                        SubscriptionsImport.importAnotherFile();
                        return false;
                    });

                    $(document).on("click", "#cancelBtn", function() {
                        var cancelConfirm = confirm("{{ trans('messages.list.import.cancel') }}");

                        if (cancelConfirm) {
                            SubscriptionsImport.cancel();
                        }

                        return false;
                    });

                    $(document).on("click", "#downloadLog", function() {
                        window.location.href = SubscriptionsImport.logDownloadUrl;
                        return false;
                    });

                    // In case of existing job, start checking it
                    @if (isset($currentJobUid))
                    // Temporary show the progress bar of 0 percentage, waiting for the checkProgress() call to update it
                    SubscriptionsImport.showProgressBar();
                    SubscriptionsImport.updateProgressBar(0, 'Checking...');

                    // Set up current job information
                    SubscriptionsImport.setCurrentJob({
                        currentImportJobUid: '{{ $currentJobUid }}',
                        progressCheckUrl: '{{ $progressCheckUrl }}',
                        cancelUrl: '{{ $cancelUrl }}',
                        logDownloadUrl: '{{ $logDownloadUrl }}'
                    });

                    // false means do not show the alert popup when progress is complete
                    // Don't worry, this is for the first check only
                    SubscriptionsImport.checkProgress(false);
                    @endif
                });

            </script>

        @endpush



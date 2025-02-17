@extends('layouts.managers')

@section('content')

    @include('managers.includes.card', ['title' => 'Exportar suscripciones - '. $list->title])



    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                    {{ csrf_field() }}


                    <div class="card-body border-top">


                        <div class="row">

                            <form action="#" id="formExport" method="POST" class="ajax_upload_form form-validate-jquery">
                                {{ csrf_field() }}

                                <div class="upload_file before">

                                    <div class="form-group row ">

                                        <p class="card-subtitle mb-3 mt-3">
                                            Seleccione lo que desea exportar y luego haga clic en el botón <mark><code>Exportar</code></mark> a continuación para iniciar la exportación.
                                        </p>


                                        <div class="col-12 mt-3">
                                            <div class="mb-3">
                                                <div class="form-check form-switch d-flex m-0 p-0">
                                                    <div class="col-10">
                                                        <label class="form-check-label" for="subscribe_confirmation">
                                                            <h5 class="checkbox-description mt-1">Exportar lista completa</h5>
                                                            <span class="checkbox-description mt-1">
                                                              Que contiene ( {{ $list->subscribersCount() }} {{ strtolower('Suscripciones') }} )
                                                        </span>
                                                        </label>
                                                    </div>
                                                    <div class="col-2 d-flex justify-content-center align-items-center">
                                                        <input class="form-check-input" type="radio" {{ request()->segment_uid ? '' : 'checked' }}  value="whole_list" id="which" name="which">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12 mt-3 ">
                                            <div class="mb-3">
                                                <div class="form-check form-switch d-flex m-0 p-0">
                                                    <div class="col-10">
                                                        <label class="form-check-label" for="subscribe_confirmation">
                                                            <h5 class="checkbox-description mt-1">Elige segmento</h5>
                                                            <span class="checkbox-description mt-1">
                                                              Es necesario seleccioar un segmento que quieres exportar
                                                        </span>
                                                        </label>
                                                    </div>
                                                    <div class="col-2 d-flex justify-content-center align-items-center">
                                                        <input class="form-check-input" type="radio"   value="segment" id="which" name="which" data-popup='tooltip'  title="" {{ $list->segments()->count() == 0 ? 'disabled' : '' }}>
                                                    </div>
                                                </div>
                                                <div class="segment_box pt-3 d-none" >
                                                    @include('helpers.form_control', [
                                                        'value' => '',
                                                        'type' => 'select',
                                                        'name' => 'segment_uid',
                                                        'label' => '',
                                                        'value' => request()->segment_uid,
                                                        'options' => $list->getSegmentSelectOptions(),
                                                        'placeholder' => 'Elige segmento',
                                                    ])
                                                </div>

                                            </div>
                                        </div>


                                    </div>

                                    <div class="border-top pt-1 mt-3">
                                        <button type="submit" class="btn btn-info px-4 waves-effect waves-light mt-2 w-100">
                                            Exportar
                                        </button>
                                    </div>

                                </div>

                                <div class="form-group processing hide">
                                    <h4 style="margin-bottom: 20px" id="notice">Espere a que se complete la exportación; puede tardar unos minutos. Puede navegar o incluso cerrar el navegador y volver más tarde para descargar el archivo exportado.</h4>
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


                                <div class="col-12">
                                    <div class="errors d-none">
                                    </div>
                                </div>


                                <div class="form-group finish hide">
                                    <div class="border-top pt-1 mt-3">
                                        <a id="downloadBtn" target="_blank" href="#" role="button" class="btn btn-info px-4 waves-effect waves-light mt-2 w-100 success">
                                            Descargar archivo exportado
                                        </a>
                                        <a href="#retry" class="btn btn-info px-4 waves-effect waves-light mt-2 w-100 retry">
                                            Reintentar
                                        </a>
                                    </div>
                                </div>

                            </form>


                        </div>
                    </div>
            </div>

        </div>

    </div>




@endsection





@push('scripts')

    <script type="text/javascript">

        var SuscriptionExport = {
            // Current export job if any
            currentExportJobUid: null,

            progressCheckUrl: null,

            cancelUrl: null,

            downloadUrl: null,

            progressCheck: null,

            resetCurrentJob: function() {
                SuscriptionExport.currentExportJobUid = null;
                SuscriptionExport.progressCheckUrl = null;
                SuscriptionExport.cancelUrl = null;
                SuscriptionExport.downloadUrl = null;
            },

            setCurrentJob: function(data) {
                SuscriptionExport.currentExportJobUid = data.currentExportJobUid;
                SuscriptionExport.progressCheckUrl = data.progressCheckUrl;
                SuscriptionExport.cancelUrl = data.cancelUrl;
                SuscriptionExport.downloadUrl = data.downloadUrl;
            },

            // Update import progress
            updateProgressBar: function(percentage, message) { // percentage from 0 to 100
                var form = $("form.ajax_upload_form");
                var bar = form.find('.progress-total');

                form.find("#bottomNotice").show();
                form.find("#bottomNotice").html(message);
               // bar.find(".number").html(percentage);
               // bar.css({
               //     width: (percentage) + '%'
               // });

                $(".progress-total").css("width", percentage + "%");
                $(".progress-total .number").text(percentage);


            },

            stopCheckingProgress: function() {
                clearTimeout(SuscriptionExport.progressCheck);
            },

            checkProgress: function(completeAlert = true) {
                var form = $("form.ajax_upload_form");
                var bar = form.find('.progress-total');
                var bar_s = form.find('.progress-success');
                var bar_e = form.find('.progress-error');

                $.ajax({
                    url : SuscriptionExport.progressCheckUrl,
                    type: "GET",
                    success: function(result, textStatus, jqXHR) {
                        // Upgrade progress, no matter which status is
                        SuscriptionExport.showProgressBar();
                        SuscriptionExport.updateProgressBar(result.percentage, result.message);

                        if (result.status == "failed") {

                            SuscriptionExport.showFinishButtonBar();
                            SuscriptionExport.hideCancelButton();
                            $("#notice").hide();
                            $("#bottomNotice").hide();
                            $('#errorBox').show();
                            $('#errorMsg').html(result.error);

                        } else if (result.status == "done") {

                            SuscriptionExport.hideCancelButton();
                            $("#notice").show();
                            $("#notice").html('El proceso de exportación finalizó exitosamente. Haga clic en el botón de descarga para obtener el archivo de salida.');
                            $('#bottomNotice').show();
                            $("#bottomNotice").html(result.message);
                            form.find('.progress-bar').addClass('success');
                            form.find('.finish').removeClass('hide');
                            form.find('.success').removeClass("hide");

                            if (completeAlert) {

                                toastr.success("El proceso de exportación finalizó exitosamente. Haga clic en el botón de descarga para obtener el archivo de salida.", "Operación exitosa", {
                                    closeButton: true,
                                    progressBar: true,
                                    positionClass: "toast-bottom-right"
                                });

                            }
                        } else if (result.status == "cancelled") {
                            /*
                            SuscriptionExport.hideProgressBar();
                            form.find('.finish').addClass("hide");
                            form.find('.success').removeClass("hide");
                            */
                        } else if (result.status == "running" || result.status == "queued") {
                            SuscriptionExport.showProgressBar();
                            SuscriptionExport.progressCheck = setTimeout(function() {
                                SuscriptionExport.checkProgress();
                            }, 2000);
                        }
                    }
                });
            },

            export: function() {
                var which = $('[name="which"]:checked').val();
                var data = {
                    _token: $('#formExport').find('input[name="_token"]').val()
                };

                if (which == 'segment') {
                    data.segment_uid = $('[name=segment_uid]').val();
                }

                SuscriptionExport.showProgressBar();
                SuscriptionExport.updateProgressBar(0, "A partir de...");

                //$(".processing label").html("A partir de...");
                //$(".before").addClass('hide');
                //$(".processing").removeClass('hide');
                $.ajax({
                    url: '{{ route('manager.campaigns.maillists.subscribers.export.dispatch', [ 'list_uid' => $list->uid]) }}',
                    type: 'POST',
                    data: data,
                    success: function (data) {
                        SuscriptionExport.setCurrentJob(data);
                        SuscriptionExport.checkProgress();
                    }
                }).fail(function( jqXHR, textStatus, errorThrown ) {
                    return true;
                    SuscriptionExport.hideProgressBar();
                    notify({
                        title: "{{ trans('messages.notify.error') }}",
                        message: errorThrown,
                    });
                });
            },

            cancel: function() {
                SuscriptionExport.stopCheckingProgress();

                var token = $('form#formExport').find('input[name="_token"]').val();

                $.ajax({
                    url : SuscriptionExport.cancelUrl,
                    type: "POST",
                    data: {
                        '_token': token
                    },
                    success: function(result, textStatus, jqXHR) {
                        SuscriptionExport.hideFinishButtonBar();
                        SuscriptionExport.hideProgressBar();
                        SuscriptionExport.resetCurrentJob();
                    }
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    notify({
                        title: "{{ trans('messages.notify.error') }}",
                        message: errorThrown,
                    });

                    // Resume progress checking
                    SuscriptionExport.checkProgress();

                    return false;
                });
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
                $(".before").removeClass('hide');
                $(".processing").addClass('hide');
                //$('#errorBox').hide();
            },

            showFinishButtonBar: function() {
                $(".finish").removeClass('hide');
            },

            hideFinishButtonBar: function() {
                $(".finish").addClass('hide');
            },

            showCancelButton: function() {
                $('#cancelBtn').removeClass('hide');
            },

            hideCancelButton: function() {
                $('#cancelBtn').addClass('hide');
            },
        }

        $(document).ready(function() {


            $(document).on("change", 'input[name="which"]', function () {
                if ($(this).val() === 'segment') {
                    $('.segment_box').slideDown();
                } else {
                    $('.segment_box').slideUp();
                }
            });

            $(document).on("submit", "form.ajax_upload_form", function(e) {
                e.preventDefault();

                SuscriptionExport.export();
            });

            $(document).on("click", "#cancelBtn", function(e) {
                e.preventDefault();

                var cancelConfirm = confirm("¿Está seguro de que desea cancelar el trabajo de exportación?");

                if (cancelConfirm) {
                    SuscriptionExport.cancel();
                }
            });

            $(document).on("click", ".retry", function(e) {
                e.preventDefault();

                SuscriptionExport.cancel();
            });

            $(document).on("click", "#downloadBtn", function(e) {
                e.preventDefault();

                window.location.href = SuscriptionExport.downloadUrl;
                return false;
            });


            // SET CURRENT JOB IF ANY
            @if (isset($currentJobUid))
            // Temporary show the progress bar of 0 percentage, waiting for the checkProgress() call to update it
            SuscriptionExport.showProgressBar();
            SuscriptionExport.updateProgressBar(0, 'Inicializando...');

            // Set up current job information
            SuscriptionExport.setCurrentJob({
                currentExportJobUid: '{{ $currentJobUid }}',
                progressCheckUrl: '{{ $progressCheckUrl }}',
                cancelUrl: '{{ $cancelUrl }}',
                downloadUrl: '{{ $downloadUrl }}'
            });

            // false means do not show the alert popup when progress is complete
            // Don't worry, this is for the first check only
            SuscriptionExport.checkProgress(false);
            @endif
        });


    </script>

@endpush




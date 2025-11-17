<div id="addassigned"  class="modal fade " >
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title "></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div>
            <div class="modal-body text-center">
                <div class="display-4 text-danger"><i data-feather="x-octagon"></i></div>
                <form method="POST" enctype="multipart/form-data" id="assigned_form" name="assigned_form">
                    @csrf

                    <input type="hidden" name="assigned_id" id="assigned_id">

                    <div class="row justify-content-center mt-20  ">
                        <h4 class="my-0 modal-title-assign"></h4>
                        <p>Todos los datos relacionados con esto pueden eliminarse</p>

                        <div class="custom-controls-stacked d-md-flex">
                            <select class="form-control modalassign" multiple data-placeholder="Seleccionar agente" name="assigned_user_id[]" id="username"></select>
                        </div>

                        <span id="AssignError" class="text-danger"></span>

                        <div class="d-flex justify-content-center gap-2 mt-3">
                            <a href="" id="btnsave" class="btn btn-danger">Confirmar</a>
                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cancelar</button>
                        </div>

                    </div>


                </form>
            </div>
        </div>
    </div>
</div>


@push('scripts')

    <script type="text/javascript">
        $(document).ready(function() {

            $('body').on('click', '#assigned', function () {
                var assigned_id = $(this).data('id');
                $('.select2_modalassign').select2({
                    dropdownParent: ".sprukosearch",
                    minimumResultsForSearch: '',
                    placeholder: "Search",
                    width: '100%'
                });

                $.get('ticketassigneds/' + assigned_id , function (data) {
                    $('#AssignError').html('');
                    $('#assigned_id').val(data.assign_data.id);
                    $(".modal-title").text('Assign To Agent');
                    $('#username').html(data.table_data);
                    $('#addassigned').modal('show');
                });

            });

// Assigned Button submit
            $('body').on('submit', '#assigned_form', function (e) {
                e.preventDefault();
                var actionType = $('#btnsave').val();
                var fewSeconds = 2;
                $('#btnsave').html('Sending..');
                $('#btnsave').prop('disabled', true);
                setTimeout(function(){
                    $('#btnsave').prop('disabled', false);
                }, fewSeconds*1000);
                var formData = new FormData(this);
                $.ajax({
                    type:'POST',
                    url: SITEURL + "/admin/assigned/create",
                    data: formData,
                    cache:false,
                    contentType: false,
                    processData: false,
                    success: (data) => {
                        $('#AssignError').html('');
                        $('#assigned_form').trigger("reset");
                        $('#addassigned').modal('hide');
                        $('#btnsave').html('Guardar');
                        toastr.success(data.success);
                        location.reload();
                    },
                    error: function(data){
                        $('#AssignError').html('');
                        $('#AssignError').html(data.responseJSON.errors.assigned_user_id);
                        $('#btnsave').html('Guardar');

                    }
                });
            });

        });

    </script>


@endpush

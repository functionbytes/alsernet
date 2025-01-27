<!-- Assigned Tickets to Agent-->
<div class="modal fade sprukosearch" id="addassigned" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <form method="POST" enctype="multipart/form-data" id="assigned_form" name="assigned_form">
                @csrf

                <input type="hidden" name="assigned_id" id="assigned_id">
                <div class="modal-body">

                    <div class="custom-controls-stacked d-md-flex">
                        <select class="form-control select2_modalassign" multiple
                            data-placeholder="Seleccionar agente" name="assigned_user_id[]" id="username">

                        </select>
                    </div>
                    <span id="AssignError" class="text-danger"></span>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-secondary" id="btnsave">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- End Assigned Tickets to Agent  -->


@push('scripts')

<script type="text/javascript">
    $(document).ready(function() {
// when user click its get modal popup to assigned the ticket
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

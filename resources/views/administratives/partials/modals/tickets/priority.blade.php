<div class="modal fade sprukopriority" id="addpriority" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <form method="POST" enctype="multipart/form-data" id="priority_form" name="priority_form">
                @csrf
                @honeypot
                <input type="hidden" name="priority_id" id="priority_id" value="{{$ticket->id}}">
                @csrf
                <div class="modal-body">

                    <div class="custom-controls-stacked d-md-flex">
                        <select class="form-control select2_modalpriority"
                            data-placeholder="Seleccionar prioridad" name="priority_user_id" id="priority">
                            <option label="Seleccionar prioridad"></option>
                           
                        </select>
                    </div>
                    <span id="PriorityError" class="text-danger"></span>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-secondary" id="pribtnsave">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- End priority Tickets  -->


@push('scripts')

<script type="text/javascript">
    $(document).ready(function() {

// change priority
$('#priority').on('click', function () {

$('#PriorityError').html('');
$('#btnsave').val("save");
$('#priority_form').trigger("reset");
$('.modal-title').html(`Prioridad`);
$('#addpriority').modal('show');
$('.select2_modalpriority').select2({
dropdownParent: ".sprukopriority",
minimumResultsForSearch: '',
placeholder: "Search",
width: '100%'
});


});

$('body').on('submit', '#priority_form', function (e) {
e.preventDefault();
var actionType = $('#pribtnsave').val();
var fewSeconds = 2;
$('#btnsave').html('Sending..');
var formData = new FormData(this);
$.ajax({
type:'POST',
url: SITEURL + "/admin/priority/change",
data: formData,
cache:false,
contentType: false,
processData: false,

success: (data) => {
$('#PriorityError').html('');
$('#priority_form').trigger("reset");
$('#addpriority').modal('hide');
$('#pribtnsave').html('Guardar');
location.reload();
toastr.success(data.success);


},
error: function(data){
$('#PriorityError').html('');
$('#PriorityError').html(data.responseJSON.errors.priority_user_id);
$('#btnsave').html('Guardar');
}
});
});
// end priority


        });

</script>


@endpush